<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de reportes generados.
 * Los archivos se conservan 30 días antes de ser eliminados automáticamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('title');
            $table->string('period_label'); // Ej: "Junio 2026" o "Ene–Jun 2026"
            $table->enum('scope', ['personal', 'member', 'household']);
            $table->foreignId('target_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete(); // Miembro objetivo si scope = member
            $table->enum('format', ['pdf', 'excel']);
            $table->boolean('include_receipts')->default(false);
            $table->string('file_path')->nullable(); // NULL si aún está generando
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_history');
    }
};
