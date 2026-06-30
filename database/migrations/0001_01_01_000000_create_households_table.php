<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla households.
 * Debe crearse ANTES de users porque users tiene FK a households.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id'); // Se agrega FK después de crear users
            $table->string('currency', 10)->default('COP');
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
