<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncomeResource;
use App\Models\BudgetPeriod;
use App\Models\Income;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Ingresos.
 * Solo accesible para owner y member (los viewers no tienen acceso).
 */
class IncomeController extends Controller
{
    /**
     * Listar ingresos de un período.
     * GET /api/incomes?periodId=&userId=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'periodId' => ['required', 'exists:budget_periods,id'],
            'userId'   => ['nullable', 'exists:users,id'],
        ]);

        $usuario = $request->user();

        // Viewers no pueden ver ingresos
        if ($usuario->isViewer()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $targetId = $request->userId ?? $usuario->id;

        // Validar que el período pertenece al hogar
        BudgetPeriod::where('household_id', $usuario->household_id)->findOrFail($request->periodId);

        $ingresos = Income::where('budget_period_id', $request->periodId)
            ->where('user_id', $targetId)
            ->with(['category', 'receipts'])
            ->get();

        return response()->json([
            'incomes' => IncomeResource::collection($ingresos),
            'total'   => $ingresos->sum('amount'),
        ]);
    }

    /**
     * Registrar un nuevo ingreso.
     * POST /api/incomes
     */
    public function store(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario->isViewer()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'budget_period_id'   => ['required', 'exists:budget_periods,id'],
            'income_category_id' => ['required', 'exists:income_categories,id'],
            'description'        => ['required', 'string', 'max:255'],
            'amount'             => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'received_date'      => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $periodo = BudgetPeriod::where('household_id', $usuario->household_id)->findOrFail($request->budget_period_id);

        if ($periodo->is_closed) {
            return response()->json(['message' => 'No puedes agregar ingresos a un período cerrado.'], 422);
        }

        $ingreso = Income::create([
            'budget_period_id'   => $periodo->id,
            'user_id'            => $usuario->id,
            'income_category_id' => $request->income_category_id,
            'description'        => $request->description,
            'amount'             => $request->amount,
            'received_date'      => $request->received_date,
            'notes'              => $request->notes,
        ]);

        return response()->json([
            'message' => 'Ingreso registrado correctamente.',
            'income'  => new IncomeResource($ingreso->load('category')),
        ], 201);
    }

    /**
     * Actualizar un ingreso.
     * PUT /api/incomes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();
        $ingreso = Income::findOrFail($id);

        if ($usuario->isViewer() || ($usuario->isMember() && $ingreso->user_id !== $usuario->id)) {
            return response()->json(['message' => 'No tienes permiso para editar este ingreso.'], 403);
        }

        $request->validate([
            'income_category_id' => ['sometimes', 'exists:income_categories,id'],
            'description'        => ['sometimes', 'string', 'max:255'],
            'amount'             => ['sometimes', 'numeric', 'min:0.01'],
            'received_date'      => ['nullable', 'date'],
            'notes'              => ['nullable', 'string'],
        ]);

        $ingreso->update($request->only([
            'income_category_id', 'description', 'amount', 'received_date', 'notes',
        ]));

        return response()->json([
            'message' => 'Ingreso actualizado correctamente.',
            'income'  => new IncomeResource($ingreso->fresh()->load('category')),
        ]);
    }

    /**
     * Eliminar un ingreso (soft-delete).
     * DELETE /api/incomes/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();
        $ingreso = Income::findOrFail($id);

        if ($usuario->isViewer() || ($usuario->isMember() && $ingreso->user_id !== $usuario->id)) {
            return response()->json(['message' => 'No tienes permiso para eliminar este ingreso.'], 403);
        }

        $ingreso->delete();

        return response()->json(['message' => 'Ingreso eliminado correctamente.']);
    }
}
