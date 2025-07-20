<?php

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
    return view('welcome');
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
    Route::resource('customers', CustomerController::class)->except(['show']);

    // 客戶事件
    Route::resource('customer-events', CustomerEventController::class)->only(['store', 'update', 'destroy']);

    // 司機管理路由 - 匯入匯出路由必須在 resource 路由之前，避免路由衝突
    Route::get('/admin/drivers/export', [App\Http\Controllers\Admin\DriverController::class, 'export'])->name('drivers.export');
    Route::post('/admin/drivers/import', [App\Http\Controllers\Admin\DriverController::class, 'import'])->name('drivers.import');
    Route::get('/admin/drivers/template', [App\Http\Controllers\Admin\DriverController::class, 'downloadTemplate'])->name('drivers.template');
    Route::resource('admin/drivers', App\Http\Controllers\Admin\DriverController::class);
    Route::get('/drivers/fleet-search', [App\Http\Controllers\Admin\DriverController::class, 'searchByFleetNumber']);

    // 訂單管理
    Route::resource('orders', OrderController::class);
    Route::get('/carpool-search', [CustomerController::class, 'carpoolSearch']);
    Route::get('/customers/{customer}/history-orders', [OrderController::class, 'getCustomerHistoryOrders'])->name('customers.history-orders');

    // 地標管理路由 - 匯入匯出路由必須在 resource 路由之前，避免路由衝突
    Route::get('/landmarks/export', [LandmarkController::class, 'export'])->name('landmarks.export');
    Route::post('/landmarks/import', [LandmarkController::class, 'import'])->name('landmarks.import');
    Route::get('/landmarks/template', [LandmarkController::class, 'downloadTemplate'])->name('landmarks.template');
    Route::resource('landmarks', LandmarkController::class);
    Route::get('/landmarks-search', [LandmarkController::class, 'search'])->name('landmarks.search');
    Route::post('/landmarks/batch-destroy', [LandmarkController::class, 'batchDestroy'])->name('landmarks.batchDestroy');
    Route::post('/landmarks/batch-toggle', [LandmarkController::class, 'batchToggle'])->name('landmarks.batchToggle');
    Route::post('/landmarks-usage', [OrderController::class, 'updateLandmarkUsage'])->name('landmarks.updateUsage');
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
