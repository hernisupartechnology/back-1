<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorías de gastos — tipadas por naturaleza financiera.
 * Los tipos reflejan la realidad del presupuesto de un hogar colombiano.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')
                  ->nullable()
                  ->constrained('households')
                  ->nullOnDelete(); // NULL = categoría global del sistema
            $table->string('name');
            $table->string('icon', 50)->nullable();
            $table->string('color', 10)->nullable();
            $table->enum('type', [
                'deduccion_nomina',
                'credito',
                'tarjeta_credito',
                'gasto_fijo',
                'gasto_variable',
                'servicio',
            ]);
            $table->boolean('is_fixed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['household_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
