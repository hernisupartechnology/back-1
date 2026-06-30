<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Notificaciones In-App.
 */
class NotificationController extends Controller
{
    /**
     * Listar notificaciones paginadas.
     * GET /api/notifications?page=
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $notificaciones = AppNotification::where('user_id', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $noLeidas = AppNotification::where('user_id', $usuario->id)
            ->unread()
            ->count();

        return response()->json([
            'notifications' => NotificationResource::collection($notificaciones->items()),
            'unread_count'  => $noLeidas,
            'total'         => $notificaciones->total(),
            'current_page'  => $notificaciones->currentPage(),
            'last_page'     => $notificaciones->lastPage(),
        ]);
    }

    /**
     * Marcar una notificación como leída.
     * PATCH /api/notifications/{id}/read
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notificacion = AppNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notificacion->markAsRead();

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    /**
     * Marcar todas las notificaciones como leídas.
     * PATCH /api/notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas.']);
    }

    /**
     * Eliminar una notificación.
     * DELETE /api/notifications/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['message' => 'Notificación eliminada.']);
    }
}
