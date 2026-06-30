<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo ActivityLog — Log de auditoría para acciones críticas del sistema.
 *
 * Registra: login, cambio de rol, eliminación de registros,
 * generación de reportes, cierre de período.
 */
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'metadata',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Método estático de registro ──────────────────────────────────────────

    /**
     * Registra una acción crítica en el log de auditoría.
     *
     * @param string $action  Acción realizada (ej: 'expense.delete')
     * @param Model|null $model  Modelo afectado
     * @param array $metadata  Contexto adicional
     */
    public static function record(string $action, ?Model $model = null, array $metadata = []): void
    {
        static::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $model ? $model->getKey() : null,
            'metadata'   => $metadata,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
