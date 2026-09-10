<?php

use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->guest()) {
        return redirect()->route('login');
    }

    return redirect()->to(config('invoices.landing_path', '/dashboard'));
});

Route::middleware('auth')->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::match(['put', 'patch'], '/invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
    Route::post('/invoices/import', [InvoiceController::class, 'import']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    Route::get('/exchange-rates', [ExchangeRateController::class, 'show']);
});

require __DIR__.'/auth.php';

Route::fallback(function () {
    $request = request();

    if ($request->expectsJson()) {
        abort(404);
    }

    return response()->view('app');
});
