<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Controlador de Autenticación.
 *
 * Maneja: registro, login, logout, recuperación de contraseña y perfil.
 * Tokens gestionados por Laravel Sanctum.
 */
class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario.
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $usuario = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'phone'     => $request->phone,
            'birthdate' => $request->birthdate,
            'role'      => 'member', // Por defecto, sin hogar asignado
        ]);

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => '¡Cuenta creada exitosamente! Bienvenido a UparFinanzas.',
            'token'   => $token,
            'user'    => new UserResource($usuario),
        ], 201);
    }

    /**
     * Iniciar sesión.
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = User::where('email', $request->email)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales ingresadas son incorrectas.'],
            ]);
        }

        // Revocar tokens anteriores para evitar sesiones huérfanas
        $usuario->tokens()->delete();

        $token = $usuario->createToken('auth_token')->plainTextToken;

        // Registrar en el log de auditoría
        ActivityLog::record('login', null, ['ip' => $request->ip()]);

        return response()->json([
            'message' => '¡Bienvenido de nuevo, ' . $usuario->name . '!',
            'token'   => $token,
            'user'    => new UserResource($usuario->load('household')),
        ]);
    }

    /**
     * Cerrar sesión (revocar token actual).
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Obtener el usuario autenticado.
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user()->load(['household', 'supervisor']);

        return response()->json([
            'user' => new UserResource($usuario),
        ]);
    }

    /**
     * Actualizar perfil del usuario autenticado.
     * PUT /api/auth/profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $usuario = $request->user();

        $datos = [
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'birthdate' => $request->birthdate,
        ];

        // Actualizar avatar si se subió uno
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior si existe
            if ($usuario->avatar) {
                Storage::disk('public')->delete($usuario->avatar);
            }

            $ruta = $request->file('avatar')->store('avatars', 'public');
            $datos['avatar'] = $ruta;
        }

        // Actualizar contraseña si se solicitó
        if ($request->filled('password')) {
            if (! Hash::check($request->current_password, $usuario->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['La contraseña actual es incorrecta.'],
                ]);
            }

            $datos['password'] = $request->password;
        }

        $usuario->update($datos);

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user'    => new UserResource($usuario->fresh()->load('household')),
        ]);
    }

    /**
     * Enviar enlace de recuperación de contraseña por correo.
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $estado = Password::sendResetLink($request->only('email'));

        if ($estado !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($estado)],
            ]);
        }

        return response()->json([
            'message' => 'Te enviamos un correo con el enlace para restablecer tu contraseña.',
        ]);
    }

    /**
     * Restablecer contraseña con el token recibido por email.
     * POST /api/auth/reset-password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $usuario, string $password) {
                $usuario->update(['password' => $password]);
                $usuario->tokens()->delete(); // Invalidar sesiones anteriores
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($estado)],
            ]);
        }

        return response()->json([
            'message' => 'Contraseña restablecida correctamente. Ahora puedes iniciar sesión.',
        ]);
    }
}
