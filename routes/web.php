<?php

use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->guest()) {
        return redirect()->route('login');
    }

    return redirect()->to(config('invoices.landing_path', '/calendar'));
});

Route::middleware('auth')->group(function () {
    Route::get('/invoices', function (Request $request) {
        if (!$request->expectsJson()) {
            return response()->view('app');
        }

        return app(InvoiceController::class)->index($request);
    });
    Route::get('/invoices/{invoice}', function (Request $request, $invoice) {
        if (!$request->expectsJson()) {
            return response()->view('app');
        }

        return app(InvoiceController::class)->show($request, $invoice);
    });
    
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
