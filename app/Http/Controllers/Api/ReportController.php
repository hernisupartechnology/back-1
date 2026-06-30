<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportJob;
use App\Models\ActivityLog;
use App\Models\BudgetPeriod;
use App\Models\ReportHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controlador de Reportes.
 *
 * La generación es asíncrona vía Job en Laravel Queue.
 * Los archivos se conservan 30 días en disco.
 */
class ReportController extends Controller
{
    /**
     * Iniciar la generación de un reporte.
     * POST /api/reports/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario->isViewer()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'scope'           => ['required', 'in:personal,member,household'],
            'target_user_id'  => ['nullable', 'exists:users,id'],
            'period_type'     => ['required', 'in:month,range,year'],
            'year'            => ['required', 'integer'],
            'month'           => ['nullable', 'required_if:period_type,month', 'integer', 'min:1', 'max:12'],
            'from_month'      => ['nullable', 'required_if:period_type,range', 'integer', 'min:1', 'max:12'],
            'to_month'        => ['nullable', 'required_if:period_type,range', 'integer', 'min:1', 'max:12'],
            'sections'        => ['array'],
            'format'          => ['required', 'in:pdf,excel'],
            'orientation'     => ['nullable', 'in:portrait,landscape'],
            'include_receipts' => ['boolean'],
        ]);

        // Obtener IDs de períodos según el tipo de período seleccionado
        $periodIds = $this->obtenerPeriodIds($usuario, $request);

        if (empty($periodIds)) {
            return response()->json([
                'message' => 'No se encontraron datos para el período seleccionado.',
            ], 404);
        }

        // Generar etiqueta legible del período
        $etiqueta = $this->generarEtiquetaPeriodo($request);

        // Crear el historial del reporte (estado inicial: sin archivo)
        $historial = ReportHistory::create([
            'user_id'         => $usuario->id,
            'household_id'    => $usuario->household_id,
            'title'           => 'Reporte ' . $etiqueta,
            'period_label'    => $etiqueta,
            'scope'           => $request->scope,
            'target_user_id'  => $request->target_user_id,
            'format'          => $request->format,
            'include_receipts' => $request->boolean('include_receipts', false),
            'file_path'       => null,
            'generated_at'    => null,
            'created_at'      => now(),
        ]);

        // Encolar el Job de generación
        GenerateReportJob::dispatch($historial->id, [
            'period_ids'      => $periodIds,
            'target_user_id'  => $request->target_user_id,
            'sections'        => $request->sections ?? [],
            'format'          => $request->format,
            'orientation'     => $request->orientation ?? 'portrait',
            'include_receipts' => $request->boolean('include_receipts'),
        ]);

        ActivityLog::record('report.generate', $historial, [
            'format' => $request->format,
            'scope'  => $request->scope,
        ]);

        return response()->json([
            'message'   => 'Reporte en generación. Te notificaremos cuando esté listo.',
            'report_id' => $historial->id,
        ], 202);
    }

    /**
     * Historial de últimos reportes.
     * GET /api/reports/history
     */
    public function history(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $reportes = ReportHistory::where('user_id', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'title'            => $r->title,
                'period_label'     => $r->period_label,
                'scope'            => $r->scope,
                'format'           => $r->format,
                'include_receipts' => $r->include_receipts,
                'is_ready'         => $r->isReady(),
                'file_exists'      => $r->fileExists(),
                'generated_at'     => $r->generated_at?->toISOString(),
                'created_at'       => $r->created_at?->toISOString(),
            ]);

        return response()->json(['history' => $reportes]);
    }

    /**
     * Descargar un reporte generado.
     * GET /api/reports/{id}/download
     */
    public function download(Request $request, int $id): mixed
    {
        $usuario   = $request->user();
        $historial = ReportHistory::where('user_id', $usuario->id)->findOrFail($id);

        if (! $historial->isReady()) {
            return response()->json(['message' => 'El reporte aún está siendo generado.'], 202);
        }

        if (! $historial->fileExists()) {
            return response()->json(['message' => 'El archivo ya no está disponible (expiró después de 30 días).'], 410);
        }

        $extension = $historial->format === 'pdf' ? 'pdf' : 'xlsx';
        $nombreDescarga = 'UparFinanzas_' . str_replace(' ', '_', $historial->period_label) . '.' . $extension;

        return response()->download(
            storage_path('app/' . $historial->file_path),
            $nombreDescarga
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function obtenerPeriodIds(mixed $usuario, Request $request): array
    {
        $query = BudgetPeriod::where('household_id', $usuario->household_id);

        if ($request->target_user_id) {
            $query->where('user_id', $request->target_user_id);
        } elseif ($request->scope === 'personal') {
            $query->where('user_id', $usuario->id);
        }

        switch ($request->period_type) {
            case 'month':
                $query->where('year', $request->year)->where('month', $request->month);
                break;
            case 'range':
                $query->where('year', $request->year)
                    ->whereBetween('month', [$request->from_month, $request->to_month]);
                break;
            case 'year':
                $query->where('year', $request->year);
                break;
        }

        return $query->pluck('id')->toArray();
    }

    private function generarEtiquetaPeriodo(Request $request): string
    {
        $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return match ($request->period_type) {
            'month'  => ($meses[$request->month] ?? '') . ' ' . $request->year,
            'range'  => ($meses[$request->from_month] ?? '') . '–' . ($meses[$request->to_month] ?? '') . ' ' . $request->year,
            'year'   => (string) $request->year,
            default  => (string) $request->year,
        };
    }
}
