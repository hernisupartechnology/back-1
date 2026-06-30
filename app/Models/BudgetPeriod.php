<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo BudgetPeriod — Período de presupuesto mensual.
 * Cada usuario tiene su propio período mensual dentro del hogar.
 */
class BudgetPeriod extends Model
{
    protected $fillable = [
        'household_id',
        'user_id',
        'year',
        'month',
        'notes',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'year'      => 'integer',
            'month'     => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function budgetEstimates(): HasMany
    {
        return $this->hasMany(BudgetEstimate::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Nombre del mes en español colombiano.
     */
    public function getLabelAttribute(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $meses[$this->month] . ' ' . $this->year;
    }

    /**
     * Total de ingresos del período.
     */
    public function getTotalIncomesAttribute(): float
    {
        return (float) $this->incomes()->sum('amount');
    }

    /**
     * Total de gastos del período.
     */
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    /**
     * Balance neto del período.
     */
    public function getBalanceAttribute(): float
    {
        return $this->total_incomes - $this->total_expenses;
    }
}
