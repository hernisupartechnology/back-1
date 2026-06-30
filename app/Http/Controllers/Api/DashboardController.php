<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del Dashboard.
 *
 * Delega toda la lógica de aggregación al DashboardService.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * Resumen completo del período para el dashboard.
     * GET /api/dashboard/summary?year=&month=&userId=
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'year'   => ['required', 'integer'],
            'month'  => ['required', 'integer', 'min:1', 'max:12'],
            'userId' => ['nullable', 'exists:users,id'],
        ]);

        $usuario   = $request->user();
        $targetId  = $request->userId ?? $usuario->id;

        $resumenPersonal = $this->dashboardService->resumenPeriodo($usuario, $request->year, $request->month, $targetId);
        $resumenHogar    = null;

        if (! $usuario->isViewer()) {
            $resumenHogar = $this->dashboardService->resumenHogar($usuario, $request->year, $request->month);
        }

        return response()->json([
            'personal'  => $resumenPersonal,
            'household' => $resumenHogar,
        ]);
    }

    /**
     * Datos para la gráfica de los últimos N meses.
     * GET /api/dashboard/chart-monthly?months=6&userId=
     */
    public function chartMonthly(Request $request): JsonResponse
    {
        $request->validate([
            'months' => ['nullable', 'integer', 'min:1', 'max:12'],
            'userId' => ['nullable', 'exists:users,id'],
        ]);

        $usuario  = $request->user();
        $targetId = $request->userId ?? $usuario->id;
        $meses    = $request->months ?? 6;

        $datos = $this->dashboardService->graficaUltimosMeses($usuario, $meses, $targetId);

        return response()->json(['chart' => $datos]);
    }

    /**
     * Distribución de gastos por categoría (donut).
     * GET /api/dashboard/chart-categories?periodId=&userId=
     */
    public function chartCategories(Request $request): JsonResponse
    {
        $request->validate([
            'periodId' => ['required', 'exists:budget_periods,id'],
            'userId'   => ['nullable', 'exists:users,id'],
        ]);

        $usuario  = $request->user();
        $targetId = $request->userId ?? $usuario->id;

        $datos = $this->dashboardService->graficaCategorias($usuario, $request->periodId, $targetId);

        return response()->json(['chart' => $datos]);
    }

    /**
     * Categorías en alerta (estimado vs real).
     * GET /api/dashboard/alerts?periodId=&userId=
     */
    public function alerts(Request $request): JsonResponse
    {
        $request->validate([
            'periodId' => ['required', 'exists:budget_periods,id'],
            'userId'   => ['nullable', 'exists:users,id'],
        ]);

        $usuario  = $request->user();
        $targetId = $request->userId ?? $usuario->id;

        $alertas = $this->dashboardService->alertasPresupuesto($usuario, $request->periodId, $targetId);

        return response()->json(['alerts' => $alertas]);
    }

    /**
     * Próximos vencimientos.
     * GET /api/dashboard/upcoming-payments?days=7&userId=
     */
    public function upcomingPayments(Request $request): JsonResponse
    {
        $request->validate([
            'days'   => ['nullable', 'integer', 'min:1', 'max:30'],
            'userId' => ['nullable', 'exists:users,id'],
        ]);

        $usuario  = $request->user();
        $targetId = $request->userId ?? $usuario->id;
        $dias     = $request->days ?? 7;

        $pagos = $this->dashboardService->proximosVencimientos($usuario, $dias, $targetId);

        return response()->json(['upcoming_payments' => $pagos]);
    }
}
