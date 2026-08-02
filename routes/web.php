<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
Route::post('/invoices', [InvoiceController::class, 'store']);
Route::match(['put', 'patch'], '/invoices/{invoice}', [InvoiceController::class, 'update']);
Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
Route::post('/invoices/import', [InvoiceController::class, 'import']);

Route::view('/{any}', 'app')
    ->where('any', '^(?!invoices$).*$');
