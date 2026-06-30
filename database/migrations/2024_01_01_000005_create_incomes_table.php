<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ingresos del presupuesto.
 * Solo visibles para owner y member — los viewers no tienen acceso a esta tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained('budget_periods')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('income_category_id')->constrained('income_categories')->restrictOnDelete();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['budget_period_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
