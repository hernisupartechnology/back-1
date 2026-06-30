<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo HouseholdInvitation — Invitación para unirse al hogar.
 * El token de 8 caracteres se genera automáticamente y expira en 72 horas.
 */
class HouseholdInvitation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'household_id',
        'email',
        'token',
        'role_assigned',
        'status',
        'invited_by',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'  => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Solo invitaciones válidas (pendientes y no expiradas).
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '>', now());
    }
}
