<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerEventController;

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
});



require __DIR__.'/auth.php';





Route::post('customers/batch-delete', [CustomerController::class, 'batchDelete'])->name('customers.batchDelete');


// 匯出/匯入在前，避免被 resource route 蓋掉
Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');

// 客戶 CRUD（排除 show 方法）
Route::resource('customers', CustomerController::class)->except(['show']);

Route::resource('customer-events', CustomerEventController::class)->only(['store', 'update', 'destroy']);

Route::resource('admin/drivers', App\Http\Controllers\Admin\DriverController::class);

Route::resource('orders', OrderController::class);

Route::get('/carpool-search', [CustomerController::class, 'carpoolSearch']);

Route::get('/drivers/fleet-search', [App\Http\Controllers\Admin\DriverController::class, 'searchByFleetNumber']);


