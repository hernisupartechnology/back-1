<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Expense — Gasto registrado en un período de presupuesto.
 *
 * Características clave:
 * - user_id: dueño del gasto (puede ser un viewer/hijo)
 * - registered_by: quien lo registró (el padre puede hacerlo por el hijo)
 * - Soporta tipos de pago de tarjeta de crédito y recurrencia
 */
class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'budget_period_id',
        'user_id',
        'registered_by',
        'expense_category_id',
        'description',
        'amount',
        'due_date',
        'paid_date',
        'is_paid',
        'payment_type',
        'partial_amount',
        'is_recurring',
        'recurrence_frequency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'partial_amount' => 'decimal:2',
            'due_date'       => 'date',
            'paid_date'      => 'date',
            'is_paid'        => 'boolean',
            'is_recurring'   => 'boolean',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    /**
     * Usuario dueño del gasto (puede ser un viewer).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Usuario que registró el gasto (padre/tutor si es de un viewer).
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Recibos adjuntos a este gasto.
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Solo gastos no pagados con vencimiento hoy o pasado.
     */
    public function scopeOverdue($query)
    {
        return $query->where('is_paid', false)
                     ->whereNotNull('due_date')
                     ->where('due_date', '<=', now()->toDateString());
    }

    /**
     * Gastos con vencimiento próximo (en los siguientes N días).
     */
    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('is_paid', false)
                     ->whereNotNull('due_date')
                     ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    /**
     * Solo gastos recurrentes (para copiar al siguiente período).
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }
}
