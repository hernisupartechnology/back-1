<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo SavingsContribution — Aporte individual a una meta de ahorro.
 * Forma el timeline cronológico de aportes de la meta.
 */
class SavingsContribution extends Model
{
    protected $fillable = [
        'savings_goal_id',
        'user_id',
        'amount',
        'contribution_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'contribution_date' => 'date',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
