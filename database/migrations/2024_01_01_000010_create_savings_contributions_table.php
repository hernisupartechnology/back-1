<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aportes a las metas de ahorro — timeline cronológico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_goal_id')->constrained('savings_goals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('contribution_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['savings_goal_id', 'contribution_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_contributions');
    }
};
