<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Categorías (Ingresos y Gastos).
 * Solo el owner puede crear/editar/eliminar categorías del hogar.
 */
class CategoryController extends Controller
{
    // ── Categorías de Ingreso ─────────────────────────────────────────────────

    public function incomeIndex(Request $request): JsonResponse
    {
        $usuario    = $request->user();
        $categorias = IncomeCategory::forHousehold($usuario->household_id)->get();

        return response()->json(['categories' => $categorias]);
    }

    public function incomeStore(Request $request): JsonResponse
    {
        $this->soloOwner($request);

        $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'icon'  => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:10'],
        ]);

        $cat = IncomeCategory::create([
            'household_id' => $request->user()->household_id,
            'name'         => $request->name,
            'icon'         => $request->icon,
            'color'        => $request->color,
            'is_active'    => true,
        ]);

        return response()->json(['message' => 'Categoría creada.', 'category' => $cat], 201);
    }

    public function incomeUpdate(Request $request, int $id): JsonResponse
    {
        $this->soloOwner($request);
        $cat = IncomeCategory::where('household_id', $request->user()->household_id)->findOrFail($id);

        $request->validate([
            'name'      => ['sometimes', 'string', 'max:100'],
            'icon'      => ['nullable', 'string', 'max:50'],
            'color'     => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $cat->update($request->only(['name', 'icon', 'color', 'is_active']));

        return response()->json(['message' => 'Categoría actualizada.', 'category' => $cat]);
    }

    public function incomeDestroy(Request $request, int $id): JsonResponse
    {
        $this->soloOwner($request);
        $cat = IncomeCategory::where('household_id', $request->user()->household_id)->findOrFail($id);
        $cat->delete();

        return response()->json(['message' => 'Categoría eliminada.']);
    }

    // ── Categorías de Gasto ───────────────────────────────────────────────────

    public function expenseIndex(Request $request): JsonResponse
    {
        $usuario    = $request->user();
        $categorias = ExpenseCategory::forHousehold($usuario->household_id)->get();

        return response()->json(['categories' => $categorias]);
    }

    public function expenseStore(Request $request): JsonResponse
    {
        $this->soloOwner($request);

        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'type'     => ['required', 'in:deduccion_nomina,credito,tarjeta_credito,gasto_fijo,gasto_variable,servicio'],
            'icon'     => ['nullable', 'string', 'max:50'],
            'color'    => ['nullable', 'string', 'max:10'],
            'is_fixed' => ['boolean'],
        ]);

        $cat = ExpenseCategory::create([
            'household_id' => $request->user()->household_id,
            'name'         => $request->name,
            'type'         => $request->type,
            'icon'         => $request->icon,
            'color'        => $request->color,
            'is_fixed'     => $request->boolean('is_fixed', false),
            'is_active'    => true,
        ]);

        return response()->json(['message' => 'Categoría creada.', 'category' => $cat], 201);
    }

    public function expenseUpdate(Request $request, int $id): JsonResponse
    {
        $this->soloOwner($request);
        $cat = ExpenseCategory::where('household_id', $request->user()->household_id)->findOrFail($id);

        $request->validate([
            'name'      => ['sometimes', 'string', 'max:100'],
            'icon'      => ['nullable', 'string', 'max:50'],
            'color'     => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $cat->update($request->only(['name', 'icon', 'color', 'is_active']));

        return response()->json(['message' => 'Categoría actualizada.', 'category' => $cat]);
    }

    public function expenseDestroy(Request $request, int $id): JsonResponse
    {
        $this->soloOwner($request);
        $cat = ExpenseCategory::where('household_id', $request->user()->household_id)->findOrFail($id);
        $cat->delete();

        return response()->json(['message' => 'Categoría eliminada.']);
    }

    /**
     * Reordenar categorías de gasto (drag and drop).
     * PATCH /api/expense-categories/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->soloOwner($request);

        $request->validate([
            'order'    => ['required', 'array'],
            'order.*'  => ['integer'],
        ]);

        foreach ($request->order as $posicion => $catId) {
            ExpenseCategory::where('id', $catId)
                ->where('household_id', $request->user()->household_id)
                ->update(['sort_order' => $posicion]);
        }

        return response()->json(['message' => 'Orden actualizado.']);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function soloOwner(Request $request): void
    {
        if (! $request->user()->isOwner()) {
            abort(403, 'Solo el propietario del hogar puede gestionar categorías.');
        }
    }
}
