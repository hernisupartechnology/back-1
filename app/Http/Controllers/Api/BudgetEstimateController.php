<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetEstimate;
use App\Models\BudgetPeriod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Estimados de Presupuesto.
 *
 * Permite comparar lo planeado vs. lo ejecutado por categoría y período.
 */
class BudgetEstimateController extends Controller
{
    /**
     * Listar estimados de un período.
     * GET /api/budget-estimates?periodId=&userId=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'periodId' => ['required', 'exists:budget_periods,id'],
            'userId'   => ['nullable', 'exists:users,id'],
        ]);

        $usuario   = $request->user();
        $targetId  = $request->userId ?? $usuario->id;

        $estimados = BudgetEstimate::where('budget_period_id', $request->periodId)
            ->where('user_id', $targetId)
            ->with('category')
            ->get()
            ->map(fn ($e) => [
                'id'                  => $e->id,
                'expense_category_id' => $e->expense_category_id,
                'estimated_amount'    => (float) $e->estimated_amount,
                'alert_threshold'     => $e->alert_threshold,
                'category'            => [
                    'id'    => $e->category->id,
                    'name'  => $e->category->name,
                    'icon'  => $e->category->icon,
                    'color' => $e->category->color,
                    'type'  => $e->category->type,
                ],
            ]);

        return response()->json(['estimates' => $estimados]);
    }

    /**
     * Crear o actualizar estimados (upsert por período + categoría + usuario).
     * POST /api/budget-estimates
     */
    public function upsert(Request $request): JsonResponse
    {
        $request->validate([
            'budget_period_id'    => ['required', 'exists:budget_periods,id'],
            'estimates'           => ['required', 'array', 'min:1'],
            'estimates.*.expense_category_id' => ['required', 'exists:expense_categories,id'],
            'estimates.*.estimated_amount'    => ['required', 'numeric', 'min:0'],
            'estimates.*.alert_threshold'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $usuario = $request->user();

        // Verificar que el período pertenece al hogar del usuario
        $periodo = BudgetPeriod::where('household_id', $usuario->household_id)
            ->findOrFail($request->budget_period_id);

        foreach ($request->estimates as $item) {
            BudgetEstimate::updateOrCreate(
                [
                    'budget_period_id'    => $periodo->id,
                    'expense_category_id' => $item['expense_category_id'],
                    'user_id'             => $usuario->id,
                ],
                [
                    'estimated_amount' => $item['estimated_amount'],
                    'alert_threshold'  => $item['alert_threshold'] ?? 90,
                ]
            );
        }

        return response()->json([
            'message' => 'Presupuesto estimado guardado correctamente.',
        ]);
    }

    /**
     * Comparativo en tiempo real: estimado vs. real por categoría.
     * GET /api/budget-estimates/vs-real?periodId=&userId=
     */
    public function vsReal(Request $request): JsonResponse
    {
        $request->validate([
            'periodId' => ['required', 'exists:budget_periods,id'],
            'userId'   => ['nullable', 'exists:users,id'],
        ]);

        $usuario  = $request->user();
        $targetId = $request->userId ?? $usuario->id;

        // Obtener estimados del período para el usuario
        $estimados = BudgetEstimate::where('budget_period_id', $request->periodId)
            ->where('user_id', $targetId)
            ->with('category')
            ->get()
            ->keyBy('expense_category_id');

        // Calcular gastos reales agrupados por categoría
        $gastosReales = Expense::where('budget_period_id', $request->periodId)
            ->where('user_id', $targetId)
            ->selectRaw('expense_category_id, SUM(amount) as total_real')
            ->groupBy('expense_category_id')
            ->pluck('total_real', 'expense_category_id');

        // Construir el comparativo
        $comparativo = $estimados->map(function ($estimado) use ($gastosReales) {
            $real       = (float) ($gastosReales[$estimado->expense_category_id] ?? 0);
            $estimadoAmt = (float) $estimado->estimated_amount;
            $porcentaje = $estimadoAmt > 0 ? round(($real / $estimadoAmt) * 100, 1) : 0;
            $alerta     = $porcentaje >= $estimado->alert_threshold;

            return [
                'expense_category_id' => $estimado->expense_category_id,
                'category'            => [
                    'id'    => $estimado->category->id,
                    'name'  => $estimado->category->name,
                    'icon'  => $estimado->category->icon,
                    'color' => $estimado->category->color,
                    'type'  => $estimado->category->type,
                ],
                'estimated_amount'    => $estimadoAmt,
                'real_amount'         => $real,
                'difference'          => $estimadoAmt - $real,
                'percentage_used'     => $porcentaje,
                'alert_threshold'     => $estimado->alert_threshold,
                'is_over_budget'      => $porcentaje >= 100,
                'is_in_alert'         => $alerta,
            ];
        })->values();

        return response()->json(['comparison' => $comparativo]);
    }
}
