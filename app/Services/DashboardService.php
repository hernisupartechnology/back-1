<?php

namespace App\Services;

use App\Models\BudgetEstimate;
use App\Models\BudgetPeriod;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Servicio del Dashboard.
 *
 * Centraliza las consultas de aggregación para el dashboard:
 * resumen personal, resumen del hogar, gráficos y alertas.
 */
class DashboardService
{
    /**
     * Resumen del período para un usuario (o el hogar completo).
     */
    public function resumenPeriodo(User $usuario, int $year, int $month, ?int $targetUserId = null): array
    {
        $targetId = $targetUserId ?? $usuario->id;

        $periodo = BudgetPeriod::where('household_id', $usuario->household_id)
            ->where('user_id', $targetId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (! $periodo) {
            return [
                'has_period'     => false,
                'total_incomes'  => 0,
                'total_expenses' => 0,
                'balance'        => 0,
                'budget_used_pct' => 0,
            ];
        }

        $totalIngresos = Income::where('budget_period_id', $periodo->id)->sum('amount');
        $totalGastos   = Expense::where('budget_period_id', $periodo->id)->sum('amount');
        $balance       = $totalIngresos - $totalGastos;
        $pctUsado      = $totalIngresos > 0 ? round(($totalGastos / $totalIngresos) * 100, 1) : 0;

        return [
            'has_period'       => true,
            'period_id'        => $periodo->id,
            'is_closed'        => $periodo->is_closed,
            'label'            => $periodo->label,
            'total_incomes'    => (float) $totalIngresos,
            'total_expenses'   => (float) $totalGastos,
            'balance'          => (float) $balance,
            'budget_used_pct'  => $pctUsado,
        ];
    }

    /**
     * Resumen del hogar completo (suma de todos los miembros).
     */
    public function resumenHogar(User $usuario, int $year, int $month): array
    {
        $periodIds = BudgetPeriod::where('household_id', $usuario->household_id)
            ->where('year', $year)
            ->where('month', $month)
            ->pluck('id');

        $totalIngresos = Income::whereIn('budget_period_id', $periodIds)->sum('amount');
        $totalGastos   = Expense::whereIn('budget_period_id', $periodIds)->sum('amount');

        return [
            'total_incomes'  => (float) $totalIngresos,
            'total_expenses' => (float) $totalGastos,
            'balance'        => (float) ($totalIngresos - $totalGastos),
        ];
    }

    /**
     * Evolución del balance de los últimos N meses.
     */
    public function graficaUltimosMeses(User $usuario, int $meses = 6, ?int $targetUserId = null): array
    {
        $targetId = $targetUserId ?? $usuario->id;
        $resultado = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $año   = (int) $fecha->format('Y');
            $mes   = (int) $fecha->format('n');

            $datos = $this->resumenPeriodo($usuario, $año, $mes, $targetId);
            $resultado[] = [
                'year'    => $año,
                'month'   => $mes,
                'label'   => $this->nombreMes($mes) . ' ' . $año,
                'incomes' => $datos['total_incomes'],
                'expenses'=> $datos['total_expenses'],
                'balance' => $datos['balance'],
            ];
        }

        return $resultado;
    }

    /**
     * Distribución de gastos por categoría (para el donut).
     */
    public function graficaCategorias(User $usuario, int $periodId, ?int $targetUserId = null): array
    {
        $targetId = $targetUserId ?? $usuario->id;

        return Expense::where('budget_period_id', $periodId)
            ->where('user_id', $targetId)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name, expense_categories.color, expense_categories.type, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color', 'expense_categories.type')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Categorías en alerta (superaron el umbral del estimado).
     */
    public function alertasPresupuesto(User $usuario, int $periodId, ?int $targetUserId = null): array
    {
        $targetId = $targetUserId ?? $usuario->id;

        $estimados = BudgetEstimate::where('budget_period_id', $periodId)
            ->where('user_id', $targetId)
            ->with('category')
            ->get();

        $gastosReales = Expense::where('budget_period_id', $periodId)
            ->where('user_id', $targetId)
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->pluck('total', 'expense_category_id');

        return $estimados
            ->filter(function ($est) use ($gastosReales) {
                $real = $gastosReales[$est->expense_category_id] ?? 0;
                $pct  = $est->estimated_amount > 0 ? ($real / $est->estimated_amount) * 100 : 0;
                return $pct >= $est->alert_threshold;
            })
            ->map(function ($est) use ($gastosReales) {
                $real = (float) ($gastosReales[$est->expense_category_id] ?? 0);
                $pct  = $est->estimated_amount > 0
                    ? round(($real / $est->estimated_amount) * 100, 1)
                    : 0;
                return [
                    'category'         => $est->category->name,
                    'color'            => $est->category->color,
                    'icon'             => $est->category->icon,
                    'estimated'        => (float) $est->estimated_amount,
                    'real'             => $real,
                    'percentage'       => $pct,
                    'is_over_budget'   => $pct >= 100,
                    'overage'          => max(0, $real - (float) $est->estimated_amount),
                ];
            })
            ->sortByDesc('percentage')
            ->values()
            ->toArray();
    }

    /**
     * Próximos vencimientos (gastos no pagados en los próximos N días).
     */
    public function proximosVencimientos(User $usuario, int $dias = 7, ?int $targetUserId = null): array
    {
        $targetId = $targetUserId ?? $usuario->id;

        return Expense::where('user_id', $targetId)
            ->upcoming($dias)
            ->with('category')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(fn ($g) => [
                'id'          => $g->id,
                'description' => $g->description,
                'amount'      => (float) $g->amount,
                'due_date'    => $g->due_date?->format('Y-m-d'),
                'category'    => $g->category?->name,
                'color'       => $g->category?->color,
            ])
            ->toArray();
    }

    private function nombreMes(int $mes): string
    {
        $nombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return $nombres[$mes] ?? '';
    }
}
