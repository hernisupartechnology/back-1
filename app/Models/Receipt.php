<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Receipt — Recibo adjunto a un gasto o ingreso.
 * Los archivos NUNCA se sirven públicamente — siempre vía controlador autenticado.
 * Se genera un thumbnail automático para imágenes con Intervention Image.
 */
class Receipt extends Model
{
    protected $fillable = [
        'expense_id',
        'income_id',
        'user_id',
        'file_path',
        'file_name',
        'thumbnail_path',
        'file_type',
        'file_size',
        'description',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'file_size'   => 'integer',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Tamaño del archivo formateado (KB o MB).
     */
    public function getFileSizeFormattedAttribute(): string
    {
        if ($this->file_size < 1024 * 1024) {
            return round($this->file_size / 1024, 1) . ' KB';
        }

        return round($this->file_size / (1024 * 1024), 2) . ' MB';
    }

    /**
     * ¿Es una imagen? (para mostrar thumbnail o ícono PDF)
     */
    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }
}
