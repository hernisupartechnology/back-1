<?php

namespace App\Jobs;

use App\Models\BudgetPeriod;
use App\Models\Expense;
use App\Models\Income;
use App\Models\ReportHistory;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Job para generar reportes en segundo plano.
 *
 * Se encola en la tabla `jobs` y el worker lo procesa asíncronamente.
 * Soporta PDF (DomPDF) y Excel (PhpSpreadsheet).
 */
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120; // 2 minutos máximo
    public int $tries   = 3;

    public function __construct(
        private readonly int $reportHistoryId,
        private readonly array $options
    ) {
    }

    public function handle(): void
    {
        $historial = ReportHistory::findOrFail($this->reportHistoryId);

        try {
            // Recopilar datos del reporte
            $datos = $this->recopilarDatos();

            // Generar el archivo según el formato
            $rutaArchivo = match ($historial->format) {
                'pdf'   => $this->generarPdf($datos, $historial),
                'excel' => $this->generarExcel($datos, $historial),
            };

            // Actualizar historial con la ruta del archivo generado
            $historial->update([
                'file_path'    => $rutaArchivo,
                'generated_at' => now(),
            ]);

        } catch (\Throwable $e) {
            $historial->update([
                'file_path'    => null,
                'generated_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Recopilar datos del período/usuario para el reporte.
     */
    private function recopilarDatos(): array
    {
        $periodIds = $this->options['period_ids'] ?? [];
        $userId    = $this->options['target_user_id'] ?? null;

        $ingresos = Income::whereIn('budget_period_id', $periodIds)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->with(['category', 'budgetPeriod'])
            ->get();

        $gastos = Expense::whereIn('budget_period_id', $periodIds)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->with(['category', 'budgetPeriod'])
            ->get();

        return [
            'incomes'        => $ingresos,
            'expenses'       => $gastos,
            'total_incomes'  => $ingresos->sum('amount'),
            'total_expenses' => $gastos->sum('amount'),
            'balance'        => $ingresos->sum('amount') - $gastos->sum('amount'),
            'options'        => $this->options,
        ];
    }

    /**
     * Generar PDF con DomPDF.
     */
    private function generarPdf(array $datos, ReportHistory $historial): string
    {
        $html = view('reports.pdf', [
            'datos'    => $datos,
            'historial' => $historial,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', $this->options['orientation'] ?? 'portrait');

        $nombreArchivo = 'reports/' . $historial->id . '_' . now()->timestamp . '.pdf';
        Storage::disk('local')->put($nombreArchivo, $pdf->output());

        return $nombreArchivo;
    }

    /**
     * Generar Excel con PhpSpreadsheet.
     */
    private function generarExcel(array $datos, ReportHistory $historial): string
    {
        $spreadsheet = new Spreadsheet();

        // Hoja de resumen
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Resumen');
        $hoja->setCellValue('A1', 'UparFinanzas — ' . $historial->title);
        $hoja->setCellValue('A2', 'Período: ' . $historial->period_label);
        $hoja->setCellValue('A4', 'Total Ingresos');
        $hoja->setCellValue('B4', $datos['total_incomes']);
        $hoja->setCellValue('A5', 'Total Gastos');
        $hoja->setCellValue('B5', $datos['total_expenses']);
        $hoja->setCellValue('A6', 'Balance');
        $hoja->setCellValue('B6', $datos['balance']);

        // Hoja de ingresos
        $hojaIngresos = $spreadsheet->createSheet();
        $hojaIngresos->setTitle('Ingresos');
        $hojaIngresos->fromArray(
            ['Categoría', 'Descripción', 'Monto', 'Fecha'],
            null, 'A1'
        );
        $fila = 2;
        foreach ($datos['incomes'] as $ingreso) {
            $hojaIngresos->setCellValue("A{$fila}", $ingreso->category?->name ?? '');
            $hojaIngresos->setCellValue("B{$fila}", $ingreso->description);
            $hojaIngresos->setCellValue("C{$fila}", $ingreso->amount);
            $hojaIngresos->setCellValue("D{$fila}", $ingreso->received_date?->format('d/m/Y'));
            $fila++;
        }

        // Hoja de gastos
        $hojaGastos = $spreadsheet->createSheet();
        $hojaGastos->setTitle('Gastos');
        $hojaGastos->fromArray(
            ['Categoría', 'Tipo', 'Descripción', 'Monto', 'Estado', 'Vencimiento'],
            null, 'A1'
        );
        $fila = 2;
        foreach ($datos['expenses'] as $gasto) {
            $hojaGastos->setCellValue("A{$fila}", $gasto->category?->name ?? '');
            $hojaGastos->setCellValue("B{$fila}", $gasto->category?->type ?? '');
            $hojaGastos->setCellValue("C{$fila}", $gasto->description);
            $hojaGastos->setCellValue("D{$fila}", $gasto->amount);
            $hojaGastos->setCellValue("E{$fila}", $gasto->is_paid ? 'Pagado' : 'Pendiente');
            $hojaGastos->setCellValue("F{$fila}", $gasto->due_date?->format('d/m/Y'));
            $fila++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $nombreArchivo = 'reports/' . $historial->id . '_' . now()->timestamp . '.xlsx';
        $rutaTemporal  = storage_path('app/' . $nombreArchivo);

        @mkdir(dirname($rutaTemporal), 0755, true);

        $writer = new Xlsx($spreadsheet);
        $writer->save($rutaTemporal);

        return $nombreArchivo;
    }
}
