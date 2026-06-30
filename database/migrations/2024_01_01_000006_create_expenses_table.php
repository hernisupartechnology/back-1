<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gastos del presupuesto.
 * - user_id: a quién pertenece el gasto (puede ser un viewer/hijo)
 * - registered_by: quién lo registró (el padre puede registrar por el hijo)
 * - Soporta tipos de pago para tarjetas de crédito y recurrencia configurable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained('budget_periods')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Dueño del gasto
            $table->foreignId('registered_by')->constrained('users')->cascadeOnDelete(); // Quien registró
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('due_date')->nullable();  // Fecha de vencimiento
            $table->date('paid_date')->nullable(); // Fecha en que se pagó
            $table->boolean('is_paid')->default(false);
            $table->enum('payment_type', ['total', 'minimo', 'parcial'])->default('total');
            $table->decimal('partial_amount', 15, 2)->nullable(); // Solo si payment_type = parcial
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_frequency', ['monthly', 'bimonthly', 'quarterly'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['budget_period_id', 'user_id']);
            $table->index(['budget_period_id', 'registered_by']);
            $table->index(['is_paid', 'due_date']); // Para consultas de vencimientos
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
