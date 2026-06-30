<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo ReportHistory — Historial de reportes generados.
 * Los archivos se conservan 30 días, luego son eliminados por el scheduler.
 */
class ReportHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'household_id',
        'title',
        'period_label',
        'scope',
        'target_user_id',
        'format',
        'include_receipts',
        'file_path',
        'generated_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'include_receipts' => 'boolean',
            'generated_at'     => 'datetime',
            'created_at'       => 'datetime',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Usuario objetivo del reporte (si scope = 'member').
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * ¿El reporte ya fue generado?
     */
    public function isReady(): bool
    {
        return ! is_null($this->generated_at) && ! is_null($this->file_path);
    }

    /**
     * ¿El archivo aún existe en disco?
     */
    public function fileExists(): bool
    {
        return $this->file_path && \Storage::disk('local')->exists($this->file_path);
    }
}
