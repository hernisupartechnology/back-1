<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presupuesto estimado vs real por categoría y período.
 * Permite comparar lo planeado contra lo ejecutado con alertas configurables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained('budget_periods')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('estimated_amount', 15, 2);
            $table->unsignedTinyInteger('alert_threshold')->default(90); // % para disparar alerta

            $table->timestamps();

            // Un usuario solo puede tener un estimado por categoría en un período
            $table->unique(
                ['budget_period_id', 'expense_category_id', 'user_id'],
                'uq_estimate_period_cat_user'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_estimates');
    }
};
