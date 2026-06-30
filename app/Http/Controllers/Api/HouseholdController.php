<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Household\CreateHouseholdRequest;
use App\Http\Requests\Household\InviteMemberRequest;
use App\Http\Resources\HouseholdResource;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controlador del Hogar.
 *
 * Gestiona: creación de hogar, miembros, invitaciones,
 * cambio de roles, supervisores y transferencia de propiedad.
 */
class HouseholdController extends Controller
{
    /**
     * Crear un nuevo hogar (el usuario se convierte en owner).
     * POST /api/households
     */
    public function store(CreateHouseholdRequest $request): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario->household_id) {
            return response()->json([
                'message' => 'Ya perteneces a un hogar. Debes salir de él antes de crear uno nuevo.',
            ], 422);
        }

        // Crear el hogar con owner_id temporal (propio usuario)
        $hogar = Household::create([
            'name'        => $request->name,
            'description' => $request->description,
            'owner_id'    => $usuario->id,
        ]);

        // Si se subió un avatar
        if ($request->hasFile('avatar')) {
            $ruta = $request->file('avatar')->store('households', 'public');
            $hogar->update(['avatar' => $ruta]);
        }

        // Asignar el hogar y rol owner al usuario
        $usuario->update([
            'household_id' => $hogar->id,
            'role'         => 'owner',
        ]);

        return response()->json([
            'message'   => '¡Hogar "' . $hogar->name . '" creado exitosamente!',
            'household' => new HouseholdResource($hogar->load('owner')),
        ], 201);
    }

    /**
     * Obtener el hogar actual del usuario autenticado.
     * GET /api/households/current
     */
    public function current(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario->household_id) {
            return response()->json(['household' => null]);
        }

        $hogar = Household::withCount('members')
            ->with(['owner', 'members'])
            ->findOrFail($usuario->household_id);

        $this->authorize('view', $hogar);

        return response()->json([
            'household' => new HouseholdResource($hogar),
        ]);
    }

    /**
     * Actualizar datos del hogar.
     * PUT /api/households/{id}
     */
    public function update(CreateHouseholdRequest $request, int $id): JsonResponse
    {
        $hogar = Household::findOrFail($id);
        $this->authorize('update', $hogar);

        $datos = $request->only(['name', 'description']);

        if ($request->hasFile('avatar')) {
            if ($hogar->avatar) {
                Storage::disk('public')->delete($hogar->avatar);
            }
            $datos['avatar'] = $request->file('avatar')->store('households', 'public');
        }

        $hogar->update($datos);

        return response()->json([
            'message'   => 'Hogar actualizado correctamente.',
            'household' => new HouseholdResource($hogar->fresh()->load('owner')),
        ]);
    }

    /**
     * Listar los miembros del hogar.
     * GET /api/households/{id}/members
     */
    public function members(Request $request, int $id): JsonResponse
    {
        $hogar = Household::findOrFail($id);
        $this->authorize('view', $hogar);

        $miembros = User::where('household_id', $id)
            ->withCount('supervisedViewers')
            ->with('supervisor')
            ->get();

        return response()->json([
            'members' => UserResource::collection($miembros),
        ]);
    }

    /**
     * Invitar un miembro al hogar.
     * POST /api/households/invite
     *
     * Si es menor sin email: generar PIN de 6 dígitos y crear la cuenta directamente.
     * Si tiene email: enviar correo con token de 8 caracteres.
     */
    public function invite(InviteMemberRequest $request): JsonResponse
    {
        $owner = $request->user();
        $hogar = Household::findOrFail($owner->household_id);
        $this->authorize('invite', $hogar);

        $esMinor = $request->boolean('is_minor', false);

        // ── Caso: menor sin email ─────────────────────────────────────────────
        if ($esMinor && ! $request->email) {
            $pin = (string) random_int(100000, 999999); // PIN de 6 dígitos

            $menor = User::create([
                'name'         => $request->name,
                'email'        => null,
                'password'     => $pin,
                'role'         => 'viewer',
                'is_minor'     => true,
                'household_id' => $hogar->id,
            ]);

            ActivityLog::record('household.invite_minor', $menor, [
                'household_id' => $hogar->id,
            ]);

            return response()->json([
                'message' => 'Cuenta de menor creada. Comparte el PIN con ' . $menor->name . '.',
                'member'  => new UserResource($menor),
                'pin'     => $pin, // Solo se muestra una vez
            ], 201);
        }

        // ── Caso: miembro con email ───────────────────────────────────────────
        // Verificar que el email no ya pertenezca a un miembro del hogar
        $yaEsMiembro = User::where('email', $request->email)
            ->where('household_id', $hogar->id)
            ->exists();

        if ($yaEsMiembro) {
            return response()->json([
                'message' => 'Este correo ya pertenece a un miembro del hogar.',
            ], 422);
        }

        // Generar token único de 8 caracteres
        do {
            $token = Str::upper(Str::random(8));
        } while (HouseholdInvitation::where('token', $token)->exists());

        $invitacion = HouseholdInvitation::create([
            'household_id'  => $hogar->id,
            'email'         => $request->email,
            'token'         => $token,
            'role_assigned' => $request->role_assigned,
            'status'        => 'pending',
            'invited_by'    => $owner->id,
            'expires_at'    => now()->addHours(72),
        ]);

        // Enviar correo con el token (Job asíncrono)
        // dispatch(new \App\Jobs\SendInvitationEmailJob($invitacion));

        return response()->json([
            'message'    => 'Invitación enviada a ' . $request->email . '. Expira en 72 horas.',
            'token'      => $token, // También disponible para compartir manualmente
            'invitation' => [
                'id'           => $invitacion->id,
                'email'        => $invitacion->email,
                'role'         => $invitacion->role_assigned,
                'expires_at'   => $invitacion->expires_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Aceptar una invitación con el token de 8 caracteres.
     * POST /api/households/join
     */
    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'size:8'],
        ]);

        $invitacion = HouseholdInvitation::valid()
            ->where('token', strtoupper($request->token))
            ->first();

        if (! $invitacion) {
            return response()->json([
                'message' => 'El código de invitación es inválido o ya expiró.',
            ], 404);
        }

        $usuario = $request->user();

        if ($usuario->household_id) {
            return response()->json([
                'message' => 'Ya perteneces a un hogar.',
            ], 422);
        }

        // Asignar al usuario al hogar con el rol de la invitación
        $usuario->update([
            'household_id' => $invitacion->household_id,
            'role'         => $invitacion->role_assigned,
        ]);

        $invitacion->update(['status' => 'accepted']);

        return response()->json([
            'message'   => '¡Bienvenido al hogar! Ya eres parte de la familia.',
            'household' => new HouseholdResource(
                Household::with('owner')->find($invitacion->household_id)
            ),
        ]);
    }

    /**
     * Cambiar el rol de un miembro.
     * PUT /api/households/{id}/members/{userId}/role
     */
    public function updateMemberRole(Request $request, int $id, int $userId): JsonResponse
    {
        $hogar = Household::findOrFail($id);
        $this->authorize('updateRole', $hogar);

        $request->validate([
            'role' => ['required', 'in:member,viewer'],
        ]);

        $miembro = User::where('id', $userId)
            ->where('household_id', $id)
            ->firstOrFail();

        // El owner no puede cambiar su propio rol
        if ($miembro->id === $hogar->owner_id) {
            return response()->json([
                'message' => 'No puedes cambiar el rol del propietario del hogar.',
            ], 422);
        }

        $rolAnterior = $miembro->role;
        $miembro->update(['role' => $request->role]);

        ActivityLog::record('household.role_changed', $miembro, [
            'previous_role' => $rolAnterior,
            'new_role'      => $request->role,
        ]);

        return response()->json([
            'message' => 'Rol de ' . $miembro->name . ' actualizado a ' . $request->role . '.',
            'member'  => new UserResource($miembro),
        ]);
    }

    /**
     * Asignar supervisor a un viewer.
     * PUT /api/households/{id}/members/{userId}/supervisor
     */
    public function updateSupervisor(Request $request, int $id, int $userId): JsonResponse
    {
        $hogar = Household::findOrFail($id);
        $this->authorize('updateRole', $hogar);

        $request->validate([
            'supervisor_id' => ['required', 'exists:users,id'],
        ]);

        $viewer = User::where('id', $userId)
            ->where('household_id', $id)
            ->where('role', 'viewer')
            ->firstOrFail();

        $supervisor = User::where('id', $request->supervisor_id)
            ->where('household_id', $id)
            ->whereIn('role', ['owner', 'member'])
            ->firstOrFail();

        $viewer->update(['supervised_by' => $supervisor->id]);

        return response()->json([
            'message' => $viewer->name . ' ahora es supervisado por ' . $supervisor->name . '.',
            'member'  => new UserResource($viewer->load('supervisor')),
        ]);
    }

    /**
     * Eliminar un miembro del hogar (soft-delete del usuario).
     * DELETE /api/households/{id}/members/{userId}
     */
    public function removeMember(Request $request, int $id, int $userId): JsonResponse
    {
        $hogar = Household::findOrFail($id);
        $this->authorize('removeMember', $hogar);

        $miembro = User::where('id', $userId)
            ->where('household_id', $id)
            ->firstOrFail();

        if ($miembro->id === $hogar->owner_id) {
            return response()->json([
                'message' => 'No puedes eliminar al propietario del hogar.',
            ], 422);
        }

        $miembro->update(['household_id' => null, 'role' => 'member']);
        $miembro->delete(); // Soft-delete

        ActivityLog::record('household.member_removed', $miembro, [
            'household_id' => $id,
        ]);

        return response()->json([
            'message' => $miembro->name . ' fue eliminado del hogar.',
        ]);
    }

    /**
     * Transferir la propiedad del hogar a otro miembro.
     * POST /api/households/{id}/transfer-ownership
     */
    public function transferOwnership(Request $request, int $id): JsonResponse
    {
        $hogar = Household::findOrFail($id);
        $this->authorize('transferOwnership', $hogar);

        $request->validate([
            'new_owner_id' => ['required', 'exists:users,id'],
        ]);

        $nuevoOwner = User::where('id', $request->new_owner_id)
            ->where('household_id', $id)
            ->where('role', 'member')
            ->firstOrFail();

        // Degradar owner actual a member
        $request->user()->update(['role' => 'member']);

        // Promover nuevo owner
        $nuevoOwner->update(['role' => 'owner']);
        $hogar->update(['owner_id' => $nuevoOwner->id]);

        ActivityLog::record('household.ownership_transferred', $hogar, [
            'previous_owner_id' => $request->user()->id,
            'new_owner_id'      => $nuevoOwner->id,
        ]);

        return response()->json([
            'message' => 'Propiedad del hogar transferida a ' . $nuevoOwner->name . '.',
        ]);
    }
}
