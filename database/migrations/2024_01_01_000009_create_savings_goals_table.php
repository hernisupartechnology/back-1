<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metas de ahorro del hogar o de un miembro específico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete(); // NULL = meta del hogar completo
            $table->string('name');
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->string('color', 10)->nullable();
            $table->string('icon', 50)->nullable();
            $table->enum('status', ['active', 'completed', 'paused'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_goals');
    }
};
