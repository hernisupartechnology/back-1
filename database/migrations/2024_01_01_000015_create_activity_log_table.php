<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log de auditoría para acciones críticas del sistema.
 * Registra: login, cambios de rol, eliminaciones, generación de reportes, cierre de períodos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('action', 100); // Ej: 'login', 'expense.delete', 'report.generate'
            $table->string('model_type', 100)->nullable(); // Ej: 'App\Models\Expense'
            $table->unsignedBigInteger('model_id')->nullable(); // ID del registro afectado
            $table->json('metadata')->nullable(); // Contexto adicional (IP, datos cambiados, etc.)
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
