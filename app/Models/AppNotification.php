<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo AppNotification — Notificación in-app del sistema.
 * Se muestra en la campanita del header y en /notifications.
 * (Nombre AppNotification para evitar conflicto con Illuminate\Notifications\Notification)
 */
class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Solo notificaciones no leídas.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isRead(): bool
    {
        return ! is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
