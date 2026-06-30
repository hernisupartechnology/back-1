<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Períodos de presupuesto mensual.
 * Un período puede ser del hogar completo (user_id = NULL) o de un miembro específico.
 * Constraint UNIQUE evita duplicar períodos del mismo mes/año/miembro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->cascadeOnDelete(); // NULL = período del hogar completo
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->text('notes')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            // Un usuario (o el hogar) solo puede tener un período por mes/año
            $table->unique(['household_id', 'user_id', 'year', 'month'], 'uq_period_household_user_month');
            $table->index(['household_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_periods');
    }
};
