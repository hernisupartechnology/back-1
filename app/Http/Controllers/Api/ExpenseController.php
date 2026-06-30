<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\ActivityLog;
use App\Models\BudgetPeriod;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Gastos.
 *
 * Reglas clave:
 * - registered_by siempre se llena con auth()->id() — el padre puede registrar por el hijo.
 * - Un viewer NO puede crear, editar ni eliminar gastos (validado en Policy).
 * - Un member puede registrar gastos a nombre de sus viewers supervisados.
 */
class ExpenseController extends Controller
{
    /**
     * Listar gastos de un período.
     * GET /api/expenses?periodId=&userId=&type=&registeredBy=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'periodId'    => ['required', 'exists:budget_periods,id'],
            'userId'      => ['nullable', 'exists:users,id'],
            'type'        => ['nullable', 'in:deduccion_nomina,credito,tarjeta_credito,gasto_fijo,gasto_variable,servicio'],
            'registeredBy' => ['nullable', 'exists:users,id'],
        ]);

        $usuario  = $request->user();
        $targetId = $request->userId ?? $usuario->id;

        // Viewers solo pueden ver sus propios gastos
        if ($usuario->isViewer() && $targetId !== $usuario->id) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        BudgetPeriod::where('household_id', $usuario->household_id)->findOrFail($request->periodId);

        $query = Expense::where('budget_period_id', $request->periodId)
            ->where('user_id', $targetId)
            ->with(['category', 'user', 'registeredBy', 'receipts']);

        if ($request->type) {
            $query->whereHas('category', fn ($q) => $q->where('type', $request->type));
        }

        if ($request->registeredBy) {
            $query->where('registered_by', $request->registeredBy);
        }

        $gastos = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'expenses' => ExpenseResource::collection($gastos),
            'total'    => $gastos->sum('amount'),
        ]);
    }

    /**
     * Registrar un gasto.
     * POST /api/expenses
     */
    public function store(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Los viewers NO pueden crear gastos via API
        $this->authorize('create', Expense::class);

        $request->validate([
            'budget_period_id'     => ['required', 'exists:budget_periods,id'],
            'user_id'              => ['nullable', 'exists:users,id'],
            'expense_category_id'  => ['required', 'exists:expense_categories,id'],
            'description'          => ['required', 'string', 'max:255'],
            'amount'               => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'due_date'             => ['nullable', 'date'],
            'is_recurring'         => ['boolean'],
            'recurrence_frequency' => ['nullable', 'required_if:is_recurring,true', 'in:monthly,bimonthly,quarterly'],
            'payment_type'         => ['in:total,minimo,parcial'],
            'partial_amount'       => ['nullable', 'required_if:payment_type,parcial', 'numeric', 'min:0.01'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ]);

        $periodo = BudgetPeriod::where('household_id', $usuario->household_id)->findOrFail($request->budget_period_id);

        if ($periodo->is_closed) {
            return response()->json(['message' => 'No puedes agregar gastos a un período cerrado.'], 422);
        }

        // Resolver user_id objetivo (puede ser un viewer supervisado)
        $targetUserId = $request->user_id ?? $usuario->id;

        // Validar que el member solo puede registrar gastos a sus viewers
        if ($usuario->isMember() && $targetUserId !== $usuario->id) {
            $esVisorSuyo = $usuario->supervisedViewers()->where('id', $targetUserId)->exists();
            if (! $esVisorSuyo) {
                return response()->json([
                    'message' => 'Solo puedes registrar gastos a nombre de los hijos que supervisas.',
                ], 403);
            }
        }

        $gasto = Expense::create([
            'budget_period_id'     => $periodo->id,
            'user_id'              => $targetUserId,
            'registered_by'        => $usuario->id, // Siempre el usuario autenticado
            'expense_category_id'  => $request->expense_category_id,
            'description'          => $request->description,
            'amount'               => $request->amount,
            'due_date'             => $request->due_date,
            'is_paid'              => false,
            'payment_type'         => $request->payment_type ?? 'total',
            'partial_amount'       => $request->partial_amount,
            'is_recurring'         => $request->boolean('is_recurring', false),
            'recurrence_frequency' => $request->recurrence_frequency,
            'notes'                => $request->notes,
        ]);

        return response()->json([
            'message' => 'Gasto registrado correctamente.',
            'expense' => new ExpenseResource($gasto->load(['category', 'user', 'registeredBy'])),
        ], 201);
    }

    /**
     * Actualizar un gasto.
     * PUT /api/expenses/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $gasto = Expense::with('budgetPeriod')->findOrFail($id);
        $this->authorize('update', $gasto);

        $request->validate([
            'expense_category_id'  => ['sometimes', 'exists:expense_categories,id'],
            'description'          => ['sometimes', 'string', 'max:255'],
            'amount'               => ['sometimes', 'numeric', 'min:0.01'],
            'due_date'             => ['nullable', 'date'],
            'is_recurring'         => ['boolean'],
            'recurrence_frequency' => ['nullable', 'in:monthly,bimonthly,quarterly'],
            'payment_type'         => ['in:total,minimo,parcial'],
            'partial_amount'       => ['nullable', 'numeric', 'min:0.01'],
            'notes'                => ['nullable', 'string'],
        ]);

        $gasto->update($request->only([
            'expense_category_id', 'description', 'amount', 'due_date',
            'is_recurring', 'recurrence_frequency', 'payment_type', 'partial_amount', 'notes',
        ]));

        return response()->json([
            'message' => 'Gasto actualizado correctamente.',
            'expense' => new ExpenseResource($gasto->fresh()->load(['category', 'user'])),
        ]);
    }

    /**
     * Marcar/desmarcar un gasto como pagado.
     * PATCH /api/expenses/{id}/toggle-paid
     */
    public function togglePaid(Request $request, int $id): JsonResponse
    {
        $gasto = Expense::with('budgetPeriod')->findOrFail($id);
        $this->authorize('update', $gasto);

        $gasto->update([
            'is_paid'   => ! $gasto->is_paid,
            'paid_date' => ! $gasto->is_paid ? now()->toDateString() : null,
        ]);

        $estado = $gasto->fresh()->is_paid ? 'pagado' : 'pendiente';

        return response()->json([
            'message' => 'Gasto marcado como ' . $estado . '.',
            'expense' => new ExpenseResource($gasto->fresh()),
        ]);
    }

    /**
     * Eliminar un gasto (soft-delete).
     * DELETE /api/expenses/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $gasto = Expense::with('budgetPeriod')->findOrFail($id);
        $this->authorize('delete', $gasto);

        ActivityLog::record('expense.delete', $gasto, [
            'description' => $gasto->description,
            'amount'      => $gasto->amount,
        ]);

        $gasto->delete();

        return response()->json(['message' => 'Gasto eliminado correctamente.']);
    }

    /**
     * Listar gastos de todos los viewers del hogar (hijos).
     * GET /api/expenses/children?periodId=
     */
    public function children(Request $request): JsonResponse
    {
        $request->validate([
            'periodId' => ['required', 'exists:budget_periods,id'],
        ]);

        $usuario = $request->user();

        if ($usuario->isViewer()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        // Obtener IDs de viewers del hogar
        $viewerIds = User::where('household_id', $usuario->household_id)
            ->where('role', 'viewer')
            ->when($usuario->isMember(), fn ($q) => $q->where('supervised_by', $usuario->id))
            ->pluck('id');

        $gastosPorHijo = User::whereIn('id', $viewerIds)
            ->with(['expenses' => fn ($q) => $q
                ->where('budget_period_id', $request->periodId)
                ->with(['category', 'registeredBy', 'receipts'])
            ])
            ->get()
            ->map(fn ($viewer) => [
                'viewer' => [
                    'id'     => $viewer->id,
                    'name'   => $viewer->name,
                    'avatar' => $viewer->avatar ? asset('storage/' . $viewer->avatar) : null,
                ],
                'total_expenses' => $viewer->expenses->sum('amount'),
                'expenses'       => ExpenseResource::collection($viewer->expenses),
            ]);

        return response()->json(['children_expenses' => $gastosPorHijo]);
    }
}
