<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recibos adjuntos a gastos o ingresos.
 * Los archivos se almacenan en disco privado y nunca se sirven públicamente.
 * Se generan thumbnails para imágenes con Intervention Image.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')
                  ->nullable()
                  ->constrained('expenses')
                  ->cascadeOnDelete();
            $table->foreignId('income_id')
                  ->nullable()
                  ->constrained('incomes')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Quien subió
            $table->string('file_path');
            $table->string('file_name');
            $table->string('thumbnail_path')->nullable(); // Miniatura para imágenes
            $table->enum('file_type', ['image', 'pdf']);
            $table->unsignedInteger('file_size'); // Tamaño en bytes
            $table->string('description')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();

            $table->index(['expense_id']);
            $table->index(['income_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
