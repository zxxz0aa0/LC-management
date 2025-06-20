<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
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



//EXCEL匯入匯出
Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');

Route::resource('customers', CustomerController::class)->except(['show']);

Route::post('customers/batch-delete', [CustomerController::class, 'batchDelete'])->name('customers.batchDelete');

//個案是建簿連接
Route::resource('customer-events', CustomerEventController::class)->only(['store', 'update', 'destroy']);

