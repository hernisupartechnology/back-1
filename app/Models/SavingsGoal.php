<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo SavingsGoal — Meta de ahorro del hogar o de un miembro.
 * Se marca automáticamente como 'completed' cuando current_amount >= target_amount.
 */
class SavingsGoal extends Model
{
    protected $fillable = [
        'household_id',
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'deadline',
        'color',
        'icon',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_amount'  => 'decimal:2',
            'current_amount' => 'decimal:2',
            'deadline'       => 'date',
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

    public function contributions(): HasMany
    {
        return $this->hasMany(SavingsContribution::class)->orderBy('contribution_date', 'desc');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Porcentaje alcanzado de la meta.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return min(100, round(($this->current_amount / $this->target_amount) * 100, 1));
    }

    /**
     * Monto restante para alcanzar la meta.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->target_amount - (float) $this->current_amount);
    }

    /**
     * Marcar la meta como completada automáticamente.
     */
    public function checkAndCompleteGoal(): void
    {
        if ((float) $this->current_amount >= (float) $this->target_amount && $this->status === 'active') {
            $this->update(['status' => 'completed']);
        }
    }
}
