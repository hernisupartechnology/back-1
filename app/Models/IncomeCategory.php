<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo IncomeCategory — Categoría de ingreso.
 * household_id = NULL indica categoría global del sistema (ej: Salario Mensual).
 */
class IncomeCategory extends Model
{
    protected $fillable = [
        'household_id',
        'name',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Solo categorías globales del sistema.
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('household_id');
    }

    /**
     * Categorías disponibles para un hogar (globales + propias del hogar).
     */
    public function scopeForHousehold($query, int $householdId)
    {
        return $query->where(function ($q) use ($householdId) {
            $q->whereNull('household_id')
              ->orWhere('household_id', $householdId);
        })->where('is_active', true)
          ->orderBy('sort_order');
    }
}
