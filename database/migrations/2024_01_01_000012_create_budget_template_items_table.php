<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ítems de cada plantilla de presupuesto (ingresos o gastos esperados).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_template_id')->constrained('budget_templates')->cascadeOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->unsignedBigInteger('category_id'); // FK polimórfica: income_category o expense_category
            $table->string('description');
            $table->decimal('estimated_amount', 15, 2);
            $table->timestamps();

            $table->index(['budget_template_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_template_items');
    }
};
