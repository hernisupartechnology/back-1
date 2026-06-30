<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal de la base de datos.
 * Ejecuta en orden: primero categorías de ingreso, luego de gasto.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IncomeCategoriesSeeder::class,
            ExpenseCategoriesSeeder::class,
        ]);
    }
}
