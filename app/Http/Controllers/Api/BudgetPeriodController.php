<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetPeriodResource;
use App\Models\BudgetPeriod;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Períodos de Presupuesto.
 *
 * Gestiona: creación de períodos mensuales, cierre y copiado del mes anterior.
 */
class BudgetPeriodController extends Controller
{
    /**
     * Obtener o buscar el período de un mes/año.
     * GET /api/budget-periods?year=&month=&userId=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'year'   => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'  => ['required', 'integer', 'min:1', 'max:12'],
            'userId' => ['nullable', 'exists:users,id'],
        ]);

        $usuario  = $request->user();
        $targetId = $this->resolveTargetUserId($usuario, $request->userId);

        $periodo = BudgetPeriod::where('household_id', $usuario->household_id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->where('user_id', $targetId)
            ->with(['incomes.category', 'expenses.category', 'budgetEstimates.category'])
            ->first();

        return response()->json([
            'period' => $periodo ? new BudgetPeriodResource($periodo) : null,
        ]);
    }

    /**
     * Crear un nuevo período de presupuesto.
     * POST /api/budget-periods
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'year'    => ['required', 'integer', 'min:2020'],
            'month'   => ['required', 'integer', 'min:1', 'max:12'],
            'user_id' => ['nullable', 'exists:users,id'],
            'notes'   => ['nullable', 'string', 'max:1000'],
        ]);

        $usuario  = $request->user();
        $targetId = $this->resolveTargetUserId($usuario, $request->user_id);

        // Verificar que no exista ya
        $existe = BudgetPeriod::where([
            'household_id' => $usuario->household_id,
            'user_id'      => $targetId,
            'year'         => $request->year,
            'month'        => $request->month,
        ])->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Ya existe un período para ese mes y usuario.',
            ], 422);
        }

        $periodo = BudgetPeriod::create([
            'household_id' => $usuario->household_id,
            'user_id'      => $targetId,
            'year'         => $request->year,
            'month'        => $request->month,
            'notes'        => $request->notes,
            'is_closed'    => false,
        ]);

        return response()->json([
            'message' => 'Período de presupuesto creado.',
            'period'  => new BudgetPeriodResource($periodo),
        ], 201);
    }

    /**
     * Cerrar un período (solo owner).
     * PUT /api/budget-periods/{id}/close
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario->isOwner()) {
            return response()->json([
                'message' => 'Solo el propietario del hogar puede cerrar un período.',
            ], 403);
        }

        $periodo = BudgetPeriod::where('household_id', $usuario->household_id)
            ->findOrFail($id);

        if ($periodo->is_closed) {
            return response()->json(['message' => 'Este período ya está cerrado.'], 422);
        }

        $periodo->update(['is_closed' => true]);

        return response()->json([
            'message' => 'Período "' . $periodo->label . '" cerrado correctamente.',
            'period'  => new BudgetPeriodResource($periodo),
        ]);
    }

    /**
     * Copiar gastos recurrentes del mes anterior.
     * POST /api/budget-periods/{id}/copy-from-previous
     */
    public function copyFromPrevious(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();
        $periodo = BudgetPeriod::where('household_id', $usuario->household_id)->findOrFail($id);

        // Calcular mes anterior
        $mesAnterior = $periodo->month === 1
            ? ['year' => $periodo->year - 1, 'month' => 12]
            : ['year' => $periodo->year, 'month' => $periodo->month - 1];

        $periodoAnterior = BudgetPeriod::where([
            'household_id' => $usuario->household_id,
            'user_id'      => $periodo->user_id,
            'year'         => $mesAnterior['year'],
            'month'        => $mesAnterior['month'],
        ])->first();

        if (! $periodoAnterior) {
            return response()->json([
                'message' => 'No existe un período del mes anterior para copiar.',
            ], 404);
        }

        // Copiar solo los gastos marcados como recurrentes
        $gastosRecurrentes = Expense::where('budget_period_id', $periodoAnterior->id)
            ->recurring()
            ->get();

        $copiados = 0;
        foreach ($gastosRecurrentes as $gasto) {
            // Solo copiar si la frecuencia aplica al período actual
            if ($this->aplicaFrecuencia($gasto, $periodo)) {
                Expense::create([
                    'budget_period_id'     => $periodo->id,
                    'user_id'              => $gasto->user_id,
                    'registered_by'        => $usuario->id,
                    'expense_category_id'  => $gasto->expense_category_id,
                    'description'          => $gasto->description,
                    'amount'               => $gasto->amount,
                    'is_recurring'         => true,
                    'recurrence_frequency' => $gasto->recurrence_frequency,
                    'is_paid'              => false,
                    'payment_type'         => $gasto->payment_type,
                ]);
                $copiados++;
            }
        }

        return response()->json([
            'message'        => "Se copiaron {$copiados} gastos recurrentes del mes anterior.",
            'copied_count'   => $copiados,
        ]);
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    /**
     * Resuelve el user_id objetivo según el rol del usuario autenticado.
     * Owner puede seleccionar cualquier miembro; member solo sus viewers.
     */
    private function resolveTargetUserId(mixed $usuario, ?int $targetId): ?int
    {
        if (! $targetId || $targetId === $usuario->id) {
            return $usuario->id;
        }

        // El owner puede ver cualquier miembro del hogar
        if ($usuario->isOwner()) {
            return $targetId;
        }

        // El member solo puede ver sus viewers supervisados
        if ($usuario->isMember()) {
            $esVisorSuyo = $usuario->supervisedViewers()
                ->where('id', $targetId)
                ->exists();

            return $esVisorSuyo ? $targetId : $usuario->id;
        }

        return $usuario->id;
    }

    /**
     * Determina si un gasto recurrente aplica para el período actual según su frecuencia.
     */
    private function aplicaFrecuencia(Expense $gasto, BudgetPeriod $periodo): bool
    {
        return match ($gasto->recurrence_frequency) {
            'monthly'    => true,
            'bimonthly'  => $periodo->month % 2 === 0, // Meses pares: feb, abr, jun...
            'quarterly'  => in_array($periodo->month, [3, 6, 9, 12]),
            default      => true,
        };
    }
}
