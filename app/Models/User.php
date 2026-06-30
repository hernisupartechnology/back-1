<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo User — Miembro del hogar familiar.
 *
 * Tres roles:
 *   - owner: propietario con acceso total
 *   - member: adulto con acceso a sus propios datos y de sus viewers supervisados
 *   - viewer: menor con acceso de solo lectura a sus propios gastos
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'household_id',
        'phone',
        'birthdate',
        'is_minor',
        'supervised_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'birthdate'         => 'date',
            'is_minor'          => 'boolean',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    /**
     * Hogar al que pertenece el usuario.
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Usuario padre/tutor que supervisa a este viewer.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervised_by');
    }

    /**
     * Viewers (hijos) supervisados por este usuario.
     */
    public function supervisedViewers(): HasMany
    {
        return $this->hasMany(User::class, 'supervised_by');
    }

    /**
     * Ingresos registrados por este usuario.
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Gastos que pertenecen a este usuario (puede ser un viewer).
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'user_id');
    }

    /**
     * Gastos que este usuario registró (puede ser a nombre de un viewer).
     */
    public function registeredExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'registered_by');
    }

    /**
     * Recibos subidos por este usuario.
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    /**
     * Metas de ahorro personales.
     */
    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    /**
     * Notificaciones del usuario.
     */
    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    /**
     * Períodos de presupuesto personales.
     */
    public function budgetPeriods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    // ─── Helpers de rol ───────────────────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /**
     * Verifica si el usuario puede administrar el hogar (owner o member con permisos).
     */
    public function canManageHousehold(): bool
    {
        return $this->isOwner();
    }
}
