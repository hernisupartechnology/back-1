<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notificaciones in-app.
 * Se muestran en la campanita del header y en /notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100); // Ej: 'budget_alert', 'payment_due', 'goal_completed'
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Metadatos adicionales para deep-links
            $table->timestamp('read_at')->nullable(); // NULL = no leída
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
