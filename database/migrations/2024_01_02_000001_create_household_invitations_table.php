<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de invitaciones al hogar.
 * El owner envía invitaciones por email con un token de 8 caracteres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('email')->nullable(); // Nullable para menores sin email
            $table->string('token', 8)->unique();
            $table->enum('role_assigned', ['member', 'viewer'])->default('member');
            $table->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at'); // 72 horas desde creación
            $table->timestamp('created_at')->useCurrent();

            $table->index(['token', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_invitations');
    }
};
