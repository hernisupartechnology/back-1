<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Income — Ingreso registrado en un período de presupuesto.
 * Solo owner y member pueden ver/registrar ingresos.
 */
class Income extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'budget_period_id',
        'user_id',
        'income_category_id',
        'description',
        'amount',
        'received_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'received_date' => 'date',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    /**
     * Recibos adjuntos a este ingreso.
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
