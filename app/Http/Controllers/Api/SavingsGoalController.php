<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavingsGoalResource;
use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Metas de Ahorro.
 */
class SavingsGoalController extends Controller
{
    /**
     * Listar metas de ahorro del hogar.
     * GET /api/savings-goals
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = SavingsGoal::where('household_id', $usuario->household_id)
            ->with('contributions.user');

        // Viewers solo ven sus propias metas
        if ($usuario->isViewer()) {
            $query->where(function ($q) use ($usuario) {
                $q->where('user_id', $usuario->id)->orWhereNull('user_id');
            });
        }

        $metas = $query->orderBy('status')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'goals' => SavingsGoalResource::collection($metas),
        ]);
    }

    /**
     * Crear una meta de ahorro.
     * POST /api/savings-goals
     */
    public function store(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario->isViewer()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'target_amount' => ['required', 'numeric', 'min:1'],
            'deadline'      => ['nullable', 'date', 'after:today'],
            'color'         => ['nullable', 'string', 'max:10'],
            'icon'          => ['nullable', 'string', 'max:50'],
            'user_id'       => ['nullable', 'exists:users,id'],
        ]);

        $targetId = $request->user_id ?? null; // null = meta del hogar

        $meta = SavingsGoal::create([
            'household_id'   => $usuario->household_id,
            'user_id'        => $targetId,
            'name'           => $request->name,
            'target_amount'  => $request->target_amount,
            'current_amount' => 0,
            'deadline'       => $request->deadline,
            'color'          => $request->color,
            'icon'           => $request->icon,
            'status'         => 'active',
        ]);

        return response()->json([
            'message' => 'Meta de ahorro creada.',
            'goal'    => new SavingsGoalResource($meta),
        ], 201);
    }

    /**
     * Actualizar una meta.
     * PUT /api/savings-goals/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();
        $meta    = SavingsGoal::where('household_id', $usuario->household_id)->findOrFail($id);

        if ($usuario->isViewer()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'name'          => ['sometimes', 'string', 'max:100'],
            'target_amount' => ['sometimes', 'numeric', 'min:1'],
            'deadline'      => ['nullable', 'date'],
            'color'         => ['nullable', 'string', 'max:10'],
            'icon'          => ['nullable', 'string', 'max:50'],
            'status'        => ['in:active,paused'],
        ]);

        $meta->update($request->only(['name', 'target_amount', 'deadline', 'color', 'icon', 'status']));

        return response()->json([
            'message' => 'Meta actualizada.',
            'goal'    => new SavingsGoalResource($meta->fresh()->load('contributions')),
        ]);
    }

    /**
     * Registrar un aporte a la meta.
     * POST /api/savings-goals/{id}/contributions
     */
    public function addContribution(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();
        $meta    = SavingsGoal::where('household_id', $usuario->household_id)
            ->where('status', 'active')
            ->findOrFail($id);

        $request->validate([
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'contribution_date' => ['required', 'date'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        SavingsContribution::create([
            'savings_goal_id'   => $meta->id,
            'user_id'           => $usuario->id,
            'amount'            => $request->amount,
            'contribution_date' => $request->contribution_date,
            'notes'             => $request->notes,
        ]);

        // Actualizar saldo acumulado
        $meta->increment('current_amount', $request->amount);
        $meta->refresh();

        // Verificar si se alcanzó la meta
        $meta->checkAndCompleteGoal();

        return response()->json([
            'message' => 'Aporte registrado.',
            'goal'    => new SavingsGoalResource($meta->load('contributions.user')),
        ]);
    }

    /**
     * Eliminar una meta.
     * DELETE /api/savings-goals/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario->isOwner()) {
            return response()->json(['message' => 'Solo el propietario puede eliminar metas.'], 403);
        }

        $meta = SavingsGoal::where('household_id', $usuario->household_id)->findOrFail($id);
        $meta->delete();

        return response()->json(['message' => 'Meta eliminada.']);
    }
}
