<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BudgetEstimateController;
use App\Http\Controllers\Api\BudgetPeriodController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SavingsGoalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — UparFinanzas
|--------------------------------------------------------------------------
|
| Todas las rutas protegidas requieren el middleware auth:sanctum.
| Rate limiting: 60 req/min para auth, 300 req/min para el resto.
|
*/

// ── Salud del backend (público — usado por el service worker PWA) ─────────
Route::get('/health-check', fn () => response()->json([
    'status'    => 'ok',
    'app'       => config('app.name'),
    'timestamp' => now()->toISOString(),
]));

// ── Autenticación (público, rate-limited a 60/min) ────────────────────────
Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
    Route::post('/register',       [AuthController::class, 'register']);
    Route::post('/login',          [AuthController::class, 'login']);
    Route::post('/forgot-password',[AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Rutas protegidas de auth
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});

// ── Rutas protegidas (auth:sanctum + throttle 300/min) ────────────────────
Route::middleware(['auth:sanctum', 'throttle:300,1'])->group(function () {

    // ── Hogares ───────────────────────────────────────────────────────────
    Route::prefix('households')->group(function () {
        Route::post('/',                              [HouseholdController::class, 'store']);
        Route::get('/current',                        [HouseholdController::class, 'current']);
        Route::post('/invite',                        [HouseholdController::class, 'invite']);
        Route::post('/join',                          [HouseholdController::class, 'join']);
        Route::put('/{id}',                           [HouseholdController::class, 'update']);
        Route::get('/{id}/members',                   [HouseholdController::class, 'members']);
        Route::put('/{id}/members/{userId}/role',     [HouseholdController::class, 'updateMemberRole']);
        Route::put('/{id}/members/{userId}/supervisor',[HouseholdController::class, 'updateSupervisor']);
        Route::delete('/{id}/members/{userId}',       [HouseholdController::class, 'removeMember']);
        Route::post('/{id}/transfer-ownership',       [HouseholdController::class, 'transferOwnership']);
    });

    // ── Períodos de presupuesto ────────────────────────────────────────────
    Route::prefix('budget-periods')->group(function () {
        Route::get('/',                         [BudgetPeriodController::class, 'index']);
        Route::post('/',                        [BudgetPeriodController::class, 'store']);
        Route::put('/{id}/close',               [BudgetPeriodController::class, 'close']);
        Route::post('/{id}/copy-from-previous', [BudgetPeriodController::class, 'copyFromPrevious']);
    });

    // ── Estimados de presupuesto ───────────────────────────────────────────
    Route::prefix('budget-estimates')->group(function () {
        Route::get('/',         [BudgetEstimateController::class, 'index']);
        Route::post('/',        [BudgetEstimateController::class, 'upsert']);
        Route::get('/vs-real',  [BudgetEstimateController::class, 'vsReal']);
    });

    // ── Ingresos ──────────────────────────────────────────────────────────
    Route::prefix('incomes')->group(function () {
        Route::get('/',     [IncomeController::class, 'index']);
        Route::post('/',    [IncomeController::class, 'store']);
        Route::put('/{id}', [IncomeController::class, 'update']);
        Route::delete('/{id}', [IncomeController::class, 'destroy']);
    });

    // ── Gastos ────────────────────────────────────────────────────────────
    Route::prefix('expenses')->group(function () {
        Route::get('/children',        [ExpenseController::class, 'children']);
        Route::get('/',                [ExpenseController::class, 'index']);
        Route::post('/',               [ExpenseController::class, 'store']);
        Route::put('/{id}',            [ExpenseController::class, 'update']);
        Route::patch('/{id}/toggle-paid', [ExpenseController::class, 'togglePaid']);
        Route::delete('/{id}',         [ExpenseController::class, 'destroy']);
    });

    // ── Recibos ───────────────────────────────────────────────────────────
    Route::prefix('receipts')->group(function () {
        Route::get('/download-zip',       [ReceiptController::class, 'downloadZip']);
        Route::get('/{id}/thumbnail',     [ReceiptController::class, 'thumbnail'])
            ->name('api.receipts.thumbnail');
        Route::get('/{id}',               [ReceiptController::class, 'show'])
            ->name('api.receipts.show');
        Route::get('/',                   [ReceiptController::class, 'index']);
        Route::post('/',                  [ReceiptController::class, 'store']);
        Route::delete('/{id}',            [ReceiptController::class, 'destroy']);
    });

    // ── Reportes ──────────────────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::post('/generate',         [ReportController::class, 'generate']);
        Route::get('/history',           [ReportController::class, 'history']);
        Route::get('/{id}/download',     [ReportController::class, 'download']);
    });

    // ── Metas de ahorro ───────────────────────────────────────────────────
    Route::prefix('savings-goals')->group(function () {
        Route::get('/',                    [SavingsGoalController::class, 'index']);
        Route::post('/',                   [SavingsGoalController::class, 'store']);
        Route::put('/{id}',                [SavingsGoalController::class, 'update']);
        Route::post('/{id}/contributions', [SavingsGoalController::class, 'addContribution']);
        Route::delete('/{id}',             [SavingsGoalController::class, 'destroy']);
    });

    // ── Categorías de Ingreso ─────────────────────────────────────────────
    Route::prefix('income-categories')->group(function () {
        Route::get('/',     [CategoryController::class, 'incomeIndex']);
        Route::post('/',    [CategoryController::class, 'incomeStore']);
        Route::put('/{id}', [CategoryController::class, 'incomeUpdate']);
        Route::delete('/{id}', [CategoryController::class, 'incomeDestroy']);
    });

    // ── Categorías de Gasto ───────────────────────────────────────────────
    Route::prefix('expense-categories')->group(function () {
        Route::get('/',         [CategoryController::class, 'expenseIndex']);
        Route::post('/',        [CategoryController::class, 'expenseStore']);
        Route::put('/{id}',     [CategoryController::class, 'expenseUpdate']);
        Route::delete('/{id}',  [CategoryController::class, 'expenseDestroy']);
        Route::patch('/reorder',[CategoryController::class, 'reorder']);
    });

    // ── Dashboard ─────────────────────────────────────────────────────────
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary',           [DashboardController::class, 'summary']);
        Route::get('/chart-monthly',     [DashboardController::class, 'chartMonthly']);
        Route::get('/chart-categories',  [DashboardController::class, 'chartCategories']);
        Route::get('/alerts',            [DashboardController::class, 'alerts']);
        Route::get('/upcoming-payments', [DashboardController::class, 'upcomingPayments']);
    });

    // ── Notificaciones ────────────────────────────────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get('/',                  [NotificationController::class, 'index']);
        Route::patch('/read-all',        [NotificationController::class, 'markAllRead']);
        Route::patch('/{id}/read',       [NotificationController::class, 'markRead']);
        Route::delete('/{id}',           [NotificationController::class, 'destroy']);
    });
});
