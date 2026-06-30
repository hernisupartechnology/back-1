<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo BudgetEstimate — Presupuesto estimado vs. real por categoría.
 * Permite comparar lo planeado contra lo ejecutado y lanzar alertas.
 */
class BudgetEstimate extends Model
{
    protected $fillable = [
        'budget_period_id',
        'expense_category_id',
        'user_id',
        'estimated_amount',
        'alert_threshold',
    ];

    protected function casts(): array
    {
        return [
            'estimated_amount' => 'decimal:2',
            'alert_threshold'  => 'integer',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
