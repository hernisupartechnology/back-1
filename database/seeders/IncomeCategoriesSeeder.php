<?php

namespace Database\Seeders;

use App\Models\IncomeCategory;
use Illuminate\Database\Seeder;

/**
 * Seeder de categorías de ingreso globales del sistema.
 * household_id = NULL indica que son categorías disponibles para todos los hogares.
 */
class IncomeCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'name'       => 'Salario Mensual',
                'icon'       => 'wallet',
                'color'      => '#1F3864',
                'sort_order' => 1,
            ],
            [
                'name'       => 'Horas Extra',
                'icon'       => 'clock',
                'color'      => '#BF9000',
                'sort_order' => 2,
            ],
            [
                'name'       => 'Bonificación Mensual Docente',
                'icon'       => 'award',
                'color'      => '#2E7D32',
                'sort_order' => 3,
            ],
            [
                'name'       => 'Pago Sueldo de Vacaciones',
                'icon'       => 'sun',
                'color'      => '#F57C00',
                'sort_order' => 4,
            ],
            [
                'name'       => 'Otros Ingresos',
                'icon'       => 'plus-circle',
                'color'      => '#757575',
                'sort_order' => 5,
            ],
        ];

        foreach ($categorias as $categoria) {
            IncomeCategory::firstOrCreate(
                ['name' => $categoria['name'], 'household_id' => null],
                array_merge($categoria, ['household_id' => null, 'is_active' => true])
            );
        }
    }
}
