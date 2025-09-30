<?php

use App\Http\Controllers\CarpoolGroupController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerEventController;
use App\Http\Controllers\LandmarkController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 客戶管理
    Route::post('customers/batch-delete', [CustomerController::class, 'batchDelete'])->name('customers.batchDelete');
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::post('customers/queued-import', [CustomerController::class, 'queuedImport'])->name('customers.queuedImport');
    Route::get('customers/import-progress/{sessionId}', [CustomerController::class, 'importProgress'])->name('customers.import.progress');
    Route::get('api/customers/import-progress/{sessionId}', [CustomerController::class, 'getImportProgress'])->name('api.customers.import.progress');
    Route::post('api/customers/start-import/{sessionId}', [CustomerController::class, 'startImportProcess'])->name('api.customers.start.import');
    Route::get('customers/template', [CustomerController::class, 'downloadTemplate'])->name('customers.template');
    Route::patch('customers/{customer}/note', [CustomerController::class, 'updateNote'])->name('customers.updateNote');
    Route::resource('customers', CustomerController::class)->except(['show']);

    // 客戶事件
    Route::resource('customer-events', CustomerEventController::class)->only(['store', 'update', 'destroy']);

    // 司機管理路由 - 匯入匯出路由必須在 resource 路由之前，避免路由衝突
    Route::get('/admin/drivers/export', [App\Http\Controllers\Admin\DriverController::class, 'export'])->name('drivers.export');
    Route::post('/admin/drivers/import', [App\Http\Controllers\Admin\DriverController::class, 'import'])->name('drivers.import');
    Route::get('/admin/drivers/template', [App\Http\Controllers\Admin\DriverController::class, 'downloadTemplate'])->name('drivers.template');
    Route::resource('admin/drivers', App\Http\Controllers\Admin\DriverController::class);
    Route::get('/drivers/fleet-search', [App\Http\Controllers\Admin\DriverController::class, 'searchByFleetNumber']);

    // 訂單管理路由 - 匯入匯出路由必須在 resource 路由之前，避免路由衝突
    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('/orders/export-simple', [OrderController::class, 'exportSimple'])->name('orders.export.simple');
    Route::get('/orders/export-simple-by-date', [OrderController::class, 'exportSimpleByDate'])->name('orders.export.simple.by-date');
    Route::post('/orders/import', [OrderController::class, 'import'])->name('orders.import');
    Route::post('/orders/batch-update', [OrderController::class, 'batchUpdate'])->name('orders.batch-update');
    Route::post('/orders/queued-import', [OrderController::class, 'queuedImport'])->name('orders.queuedImport');
    Route::get('/orders/import-progress/{batchId}', [OrderController::class, 'importProgress'])->name('orders.import.progress');
    Route::get('/api/orders/import-progress/{batchId}', [OrderController::class, 'getImportProgress'])->name('api.orders.import.progress');
    Route::post('/orders/start-queue-worker', [OrderController::class, 'startQueueWorker'])->name('orders.startQueueWorker');
    Route::get('/orders/template', [OrderController::class, 'downloadTemplate'])->name('orders.template');
    Route::get('/orders/template-simple', [OrderController::class, 'downloadSimpleTemplate'])->name('orders.template.simple');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::resource('orders', OrderController::class);
    Route::post('/orders/batch', [OrderController::class, 'storeBatch'])->name('orders.storeBatch');
    Route::get('/carpool-search', [CustomerController::class, 'carpoolSearch']);
    Route::get('/customers/{customer}/history-orders', [OrderController::class, 'getCustomerHistoryOrders'])->name('customers.history-orders');
    Route::post('/orders/check-duplicate', [OrderController::class, 'checkDuplicateOrder'])->name('orders.checkDuplicate');
    Route::post('/orders/check-date-pickup-duplicate', [OrderController::class, 'checkDatePickupDuplicate'])->name('orders.checkDatePickupDuplicate');
    Route::post('/orders/check-batch-duplicate', [OrderController::class, 'checkBatchDuplicateOrders'])->name('orders.checkBatchDuplicate');

    // 地標管理路由 - 匯入匯出路由必須在 resource 路由之前，避免路由衝突
    Route::get('/landmarks/export', [LandmarkController::class, 'export'])->name('landmarks.export');
    Route::post('/landmarks/import', [LandmarkController::class, 'import'])->name('landmarks.import');
    Route::get('/landmarks/template', [LandmarkController::class, 'downloadTemplate'])->name('landmarks.template');
    Route::resource('landmarks', LandmarkController::class);
    Route::get('/landmarks-search', [LandmarkController::class, 'search'])->name('landmarks.search');
    Route::get('/landmarks-popular', [LandmarkController::class, 'popular'])->name('landmarks.popular');
    Route::post('/landmarks-by-ids', [LandmarkController::class, 'getByIds'])->name('landmarks.getByIds');
    Route::post('/landmarks/batch-destroy', [LandmarkController::class, 'batchDestroy'])->name('landmarks.batchDestroy');
    Route::post('/landmarks/batch-toggle', [LandmarkController::class, 'batchToggle'])->name('landmarks.batchToggle');
    Route::post('/landmarks-usage', [OrderController::class, 'updateLandmarkUsage'])->name('landmarks.updateUsage');

    // 共乘群組管理路由
    Route::post('/carpool-groups/batch-action', [CarpoolGroupController::class, 'batchAction'])->name('carpool-groups.batch-action');
    Route::get('/carpool-groups', [CarpoolGroupController::class, 'index'])->name('carpool-groups.index');
    Route::get('/carpool-groups/{groupId}', [CarpoolGroupController::class, 'show'])->name('carpool-groups.show');
    Route::post('/carpool-groups/{groupId}/assign-driver', [CarpoolGroupController::class, 'assignDriver'])->name('carpool-groups.assign-driver');
    Route::post('/carpool-groups/{groupId}/cancel', [CarpoolGroupController::class, 'cancelGroup'])->name('carpool-groups.cancel');
    Route::post('/carpool-groups/{groupId}/dissolve', [CarpoolGroupController::class, 'dissolveGroup'])->name('carpool-groups.dissolve');
    Route::post('/carpool-groups/{groupId}/update-status', [CarpoolGroupController::class, 'updateStatus'])->name('carpool-groups.update-status');
});

require __DIR__.'/auth.php';

// 測試地標搜尋
Route::get('/test-landmark-search', function () {
    $landmarks = \App\Models\Landmark::where('name', 'like', '%台北%')->get();

    return response()->json([
        'count' => $landmarks->count(),
        'landmarks' => $landmarks->toArray(),
    ]);
});
