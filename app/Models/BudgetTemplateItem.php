<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo BudgetTemplateItem — Ítem individual de una plantilla de presupuesto.
 * Puede ser un ingreso o un gasto con monto estimado.
 */
class BudgetTemplateItem extends Model
{
    protected $fillable = [
        'budget_template_id',
        'type',
        'category_id',
        'description',
        'estimated_amount',
    ];

    protected function casts(): array
    {
        return [
            'estimated_amount' => 'decimal:2',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function template(): BelongsTo
    {
        return $this->belongsTo(BudgetTemplate::class, 'budget_template_id');
    }

    /**
     * Categoría de ingreso (si type = 'income').
     */
    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'category_id');
    }

    /**
     * Categoría de gasto (si type = 'expense').
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
