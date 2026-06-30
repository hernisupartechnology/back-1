<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo BudgetTemplate — Plantilla de presupuesto reutilizable.
 * Permite pre-cargar montos estimados al iniciar un nuevo período.
 */
class BudgetTemplate extends Model
{
    protected $fillable = [
        'household_id',
        'name',
        'description',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetTemplateItem::class);
    }
}
