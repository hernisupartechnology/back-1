<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

/**
 * Seeder de categorías de gasto globales del sistema.
 * Refleja la realidad financiera de un hogar colombiano:
 * deducciones de nómina, créditos, tarjetas, servicios, gastos fijos y variables.
 */
class ExpenseCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            // ── Deducciones de nómina ─────────────────────────────────────────
            ['name' => 'ADUCESAR',                                  'type' => 'deduccion_nomina', 'is_fixed' => true,  'icon' => 'building', 'color' => '#C62828', 'sort_order' => 1],
            ['name' => 'ASEGURAMOS LTDA',                           'type' => 'deduccion_nomina', 'is_fixed' => true,  'icon' => 'shield',   'color' => '#C62828', 'sort_order' => 2],
            ['name' => 'Autoseguro por Muerte',                     'type' => 'deduccion_nomina', 'is_fixed' => true,  'icon' => 'heart',    'color' => '#C62828', 'sort_order' => 3],
            ['name' => 'Aporte Fondo Prestaciones Magisterio (F.P.M)', 'type' => 'deduccion_nomina', 'is_fixed' => true, 'icon' => 'graduation-cap', 'color' => '#C62828', 'sort_order' => 4],
            ['name' => 'Aporte Fondo Solidaridad Magisterio (F.S.M)',  'type' => 'deduccion_nomina', 'is_fixed' => true, 'icon' => 'users',   'color' => '#C62828', 'sort_order' => 5],
            ['name' => 'Retención en la Fuente por Salarios',       'type' => 'deduccion_nomina', 'is_fixed' => true,  'icon' => 'file-text', 'color' => '#C62828', 'sort_order' => 6],
            ['name' => 'Seguros Bolívar',                           'type' => 'deduccion_nomina', 'is_fixed' => true,  'icon' => 'shield',   'color' => '#C62828', 'sort_order' => 7],

            // ── Créditos ──────────────────────────────────────────────────────
            ['name' => 'Crédito Hipotecario',      'type' => 'credito', 'is_fixed' => true, 'icon' => 'home',        'color' => '#1F3864', 'sort_order' => 10],
            ['name' => 'Crédito Carro',            'type' => 'credito', 'is_fixed' => true, 'icon' => 'car',         'color' => '#1F3864', 'sort_order' => 11],
            ['name' => 'Crédito Libre Inversión',  'type' => 'credito', 'is_fixed' => true, 'icon' => 'trending-up', 'color' => '#1F3864', 'sort_order' => 12],
            ['name' => 'Crédito del Lote',         'type' => 'credito', 'is_fixed' => true, 'icon' => 'map-pin',     'color' => '#1F3864', 'sort_order' => 13],

            // ── Tarjetas de crédito ───────────────────────────────────────────
            ['name' => 'Tarjeta de Crédito 1',    'type' => 'tarjeta_credito', 'is_fixed' => false, 'icon' => 'credit-card', 'color' => '#BF9000', 'sort_order' => 20],
            ['name' => 'Tarjeta de Crédito 2',    'type' => 'tarjeta_credito', 'is_fixed' => false, 'icon' => 'credit-card', 'color' => '#BF9000', 'sort_order' => 21],

            // ── Servicios (fijos) ─────────────────────────────────────────────
            ['name' => 'Agua',                     'type' => 'servicio', 'is_fixed' => true, 'icon' => 'droplets',    'color' => '#1565C0', 'sort_order' => 30],
            ['name' => 'Energía / Luz',            'type' => 'servicio', 'is_fixed' => true, 'icon' => 'zap',         'color' => '#F9A825', 'sort_order' => 31],
            ['name' => 'Internet y TV',            'type' => 'servicio', 'is_fixed' => true, 'icon' => 'wifi',        'color' => '#0277BD', 'sort_order' => 32],
            ['name' => 'Gas Natural',              'type' => 'servicio', 'is_fixed' => true, 'icon' => 'flame',       'color' => '#E64A19', 'sort_order' => 33],
            ['name' => 'Celular / Plan de Datos',  'type' => 'servicio', 'is_fixed' => true, 'icon' => 'smartphone',  'color' => '#00695C', 'sort_order' => 34],
            ['name' => 'Administración / Arriendo','type' => 'servicio', 'is_fixed' => true, 'icon' => 'building-2',  'color' => '#4527A0', 'sort_order' => 35],

            // ── Gastos fijos ──────────────────────────────────────────────────
            ['name' => 'Cuota Fija Mensual Mamá',  'type' => 'gasto_fijo', 'is_fixed' => true, 'icon' => 'heart',    'color' => '#AD1457', 'sort_order' => 40],

            // ── Gastos variables ──────────────────────────────────────────────
            ['name' => 'Mercado / Comida',                          'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'shopping-cart',  'color' => '#2E7D32', 'sort_order' => 50],
            ['name' => 'Gasolina / Transporte',                     'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'fuel',           'color' => '#558B2F', 'sort_order' => 51],
            ['name' => 'Útiles de Aseo',                            'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'sparkles',       'color' => '#00838F', 'sort_order' => 52],
            ['name' => 'Saldo Guardado para Fin de Mes (Imprevistos)', 'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'piggy-bank', 'color' => '#BF9000', 'sort_order' => 53],
            ['name' => 'Almuerzos (Intercalados)',                  'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'utensils',       'color' => '#6D4C41', 'sort_order' => 54],
            ['name' => 'Medicinas / Salud',                         'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'pill',           'color' => '#00695C', 'sort_order' => 55],
            ['name' => 'Educación (Útiles Escolares)',               'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'book-open',      'color' => '#1565C0', 'sort_order' => 56],
            ['name' => 'Entretenimiento / Recreación',              'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'film',           'color' => '#6A1B9A', 'sort_order' => 57],
            ['name' => 'Ropa y Calzado',                            'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'shopping-bag',   'color' => '#AD1457', 'sort_order' => 58],
            ['name' => 'Reparaciones / Mantenimiento',              'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'wrench',         'color' => '#37474F', 'sort_order' => 59],
            ['name' => 'Mesada / Dinero para Hijos',                'type' => 'gasto_variable', 'is_fixed' => false, 'icon' => 'baby',           'color' => '#F57C00', 'sort_order' => 60],
        ];

        foreach ($categorias as $categoria) {
            ExpenseCategory::firstOrCreate(
                ['name' => $categoria['name'], 'household_id' => null],
                array_merge($categoria, ['household_id' => null, 'is_active' => true])
            );
        }
    }
}
