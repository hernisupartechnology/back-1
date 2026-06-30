<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorías de ingreso — pueden ser globales (household_id = NULL) o del hogar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')
                  ->nullable()
                  ->constrained('households')
                  ->nullOnDelete(); // NULL = categoría global del sistema
            $table->string('name');
            $table->string('icon', 50)->nullable();
            $table->string('color', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['household_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_categories');
    }
};
