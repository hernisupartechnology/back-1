<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Household — Representa el hogar familiar.
 *
 * Un hogar tiene un propietario (owner), múltiples miembros,
 * períodos de presupuesto, categorías propias, metas de ahorro y plantillas.
 */
class Household extends Model
{
    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'currency',
        'avatar',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    /**
     * Propietario del hogar.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Todos los miembros del hogar (incluye owner).
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Invitaciones pendientes o anteriores al hogar.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(HouseholdInvitation::class);
    }

    /**
     * Períodos de presupuesto del hogar.
     */
    public function budgetPeriods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    /**
     * Categorías de ingreso propias del hogar (no globales).
     */
    public function incomeCategories(): HasMany
    {
        return $this->hasMany(IncomeCategory::class);
    }

    /**
     * Categorías de gasto propias del hogar (no globales).
     */
    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    /**
     * Metas de ahorro del hogar.
     */
    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    /**
     * Plantillas de presupuesto del hogar.
     */
    public function budgetTemplates(): HasMany
    {
        return $this->hasMany(BudgetTemplate::class);
    }

    /**
     * Historial de reportes generados en el hogar.
     */
    public function reportHistory(): HasMany
    {
        return $this->hasMany(ReportHistory::class);
    }
}
