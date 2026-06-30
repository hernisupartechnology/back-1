<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración principal de la tabla users.
 * Incluye campos custom del proyecto: role, household_id, is_minor, supervised_by.
 * Va después de households para resolver la dependencia circular (households.owner_id -> users).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable(); // Nullable para menores sin email
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->enum('role', ['owner', 'member', 'viewer'])->default('member');
            $table->unsignedBigInteger('household_id')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('birthdate')->nullable();
            $table->boolean('is_minor')->default(false);
            $table->unsignedBigInteger('supervised_by')->nullable(); // FK a users (padre/tutor)
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('household_id')
                  ->references('id')
                  ->on('households')
                  ->nullOnDelete();

            $table->foreign('supervised_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });

        // Agregar FK de households.owner_id -> users.id (ahora que users existe)
        Schema::table('households', function (Blueprint $table) {
            $table->foreign('owner_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });
        Schema::dropIfExists('users');
    }
};
