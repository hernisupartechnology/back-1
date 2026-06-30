<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo ExpenseCategory — Categoría de gasto tipada por naturaleza financiera.
 * Los tipos representan la realidad del presupuesto de un hogar colombiano.
 */
class ExpenseCategory extends Model
{
    protected $fillable = [
        'household_id',
        'name',
        'icon',
        'color',
        'type',
        'is_fixed',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_fixed'   => 'boolean',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function budgetEstimates(): HasMany
    {
        return $this->hasMany(BudgetEstimate::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Categorías disponibles para un hogar (globales + propias).
     */
    public function scopeForHousehold($query, int $householdId)
    {
        return $query->where(function ($q) use ($householdId) {
            $q->whereNull('household_id')
              ->orWhere('household_id', $householdId);
        })->where('is_active', true)
          ->orderBy('sort_order');
    }

    /**
     * Filtrar por tipo de categoría.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
