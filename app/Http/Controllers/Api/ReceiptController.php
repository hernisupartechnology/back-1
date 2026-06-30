<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReceiptResource;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Controlador de Recibos.
 *
 * Los archivos NUNCA se sirven públicamente.
 * Toda descarga va a través de este controlador con autenticación verificada.
 */
class ReceiptController extends Controller
{
    public function __construct(private readonly ReceiptService $receiptService)
    {
    }

    /**
     * Subir un recibo adjunto a un gasto o ingreso.
     * POST /api/receipts
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file'        => ['required', 'file', 'max:5120'], // 5MB máximo
            'expense_id'  => ['nullable', 'exists:expenses,id'],
            'income_id'   => ['nullable', 'exists:incomes,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $request->expense_id && ! $request->income_id) {
            return response()->json(['message' => 'Debes indicar el gasto o ingreso al que pertenece el recibo.'], 422);
        }

        $usuario = $request->user();

        // Obtener el modelo al que se adjunta y la información del período
        if ($request->expense_id) {
            $modelo  = Expense::with('budgetPeriod')->findOrFail($request->expense_id);
            $periodo = $modelo->budgetPeriod;
        } else {
            $modelo  = Income::with('budgetPeriod')->findOrFail($request->income_id);
            $periodo = $modelo->budgetPeriod;
        }

        // Verificar pertenencia al hogar
        if ($periodo->household_id !== $usuario->household_id) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        // Verificar límite de recibos por registro (máximo 10)
        $campo = $request->expense_id ? 'expense_id' : 'income_id';
        $conteo = Receipt::where($campo, $request->expense_id ?? $request->income_id)->count();

        if ($conteo >= 10) {
            return response()->json(['message' => 'Límite de 10 recibos por registro alcanzado.'], 422);
        }

        try {
            $recibo = $this->receiptService->subir(
                archivo:     $request->file('file'),
                modelo:      $modelo,
                userId:      $usuario->id,
                householdId: $usuario->household_id,
                year:        $periodo->year,
                month:       $periodo->month,
                descripcion: $request->description,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Recibo subido correctamente.',
            'receipt' => new ReceiptResource($recibo),
        ], 201);
    }

    /**
     * Servir el archivo del recibo (autenticado).
     * GET /api/receipts/{id}
     */
    public function show(Request $request, int $id): Response
    {
        $recibo  = Receipt::findOrFail($id);
        $usuario = $request->user();

        if (! $this->puedeVerRecibo($usuario, $recibo)) {
            abort(403, 'Acceso denegado.');
        }

        if (! Storage::disk('local')->exists($recibo->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return response(
            Storage::disk('local')->get($recibo->file_path),
            200,
            [
                'Content-Type'        => $recibo->file_type === 'image' ? 'image/jpeg' : 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $recibo->file_name . '"',
            ]
        );
    }

    /**
     * Servir el thumbnail del recibo.
     * GET /api/receipts/{id}/thumbnail
     */
    public function thumbnail(Request $request, int $id): Response
    {
        $recibo  = Receipt::findOrFail($id);
        $usuario = $request->user();

        if (! $this->puedeVerRecibo($usuario, $recibo)) {
            abort(403, 'Acceso denegado.');
        }

        $ruta = $recibo->thumbnail_path ?? $recibo->file_path;

        if (! Storage::disk('local')->exists($ruta)) {
            abort(404, 'Thumbnail no encontrado.');
        }

        return response(
            Storage::disk('local')->get($ruta),
            200,
            ['Content-Type' => 'image/jpeg']
        );
    }

    /**
     * Listar recibos de un período.
     * GET /api/receipts?periodId=&userId=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'periodId' => ['nullable', 'exists:budget_periods,id'],
            'userId'   => ['nullable', 'exists:users,id'],
        ]);

        $usuario = $request->user();

        $query = Receipt::whereHas('expense.budgetPeriod', function ($q) use ($usuario) {
            $q->where('household_id', $usuario->household_id);
        })->orWhereHas('income.budgetPeriod', function ($q) use ($usuario) {
            $q->where('household_id', $usuario->household_id);
        });

        if ($request->periodId) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('expense', fn ($sub) => $sub->where('budget_period_id', $request->periodId))
                  ->orWhereHas('income', fn ($sub) => $sub->where('budget_period_id', $request->periodId));
            });
        }

        // Los viewers solo ven sus propios recibos
        if ($usuario->isViewer()) {
            $query->where('user_id', $usuario->id);
        } elseif ($request->userId) {
            $query->where('user_id', $request->userId);
        }

        $recibos = $query->orderBy('uploaded_at', 'desc')->paginate(50);

        return response()->json([
            'receipts' => ReceiptResource::collection($recibos->items()),
            'total'    => $recibos->total(),
        ]);
    }

    /**
     * Eliminar un recibo (físico + registro BD).
     * DELETE /api/receipts/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $recibo  = Receipt::findOrFail($id);
        $usuario = $request->user();

        if (! $this->puedeVerRecibo($usuario, $recibo)) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $this->receiptService->eliminar($recibo);
        $recibo->delete();

        return response()->json(['message' => 'Recibo eliminado correctamente.']);
    }

    /**
     * Descarga múltiple en ZIP.
     * GET /api/receipts/download-zip?ids[]=
     */
    public function downloadZip(Request $request): mixed
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['integer', 'exists:receipts,id'],
        ]);

        $usuario  = $request->user();
        $recibos  = Receipt::whereIn('id', $request->ids)->get();

        $zipPath = storage_path('app/temp/recibos_' . now()->timestamp . '.zip');

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($recibos as $recibo) {
            if (! $this->puedeVerRecibo($usuario, $recibo)) {
                continue;
            }

            if (Storage::disk('local')->exists($recibo->file_path)) {
                $zip->addFromString(
                    $recibo->file_name,
                    Storage::disk('local')->get($recibo->file_path)
                );
            }
        }

        $zip->close();

        return response()->download($zipPath, 'recibos.zip')->deleteFileAfterSend();
    }

    // ─── Helper privado ───────────────────────────────────────────────────────

    private function puedeVerRecibo(mixed $usuario, Receipt $recibo): bool
    {
        // Owner: ve todos los recibos del hogar
        if ($usuario->isOwner()) {
            return true;
        }

        // El que subió el recibo siempre puede verlo
        if ($recibo->user_id === $usuario->id) {
            return true;
        }

        // Member puede ver recibos de gastos que él registró a nombre de un viewer
        if ($usuario->isMember() && $recibo->expense_id) {
            return Expense::where('id', $recibo->expense_id)
                ->where('registered_by', $usuario->id)
                ->exists();
        }

        return false;
    }
}
