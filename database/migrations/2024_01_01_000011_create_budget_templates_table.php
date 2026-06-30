<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plantillas de presupuesto — permiten reutilizar montos estimados entre períodos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_templates');
    }
};
