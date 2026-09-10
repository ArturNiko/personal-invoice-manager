<?php

namespace App\Http\Controllers;

use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExchangeRateController extends Controller
{
    public function show(Request $request, ExchangeRateService $service): JsonResponse|Response
    {
        if (!$request->expectsJson()) {
            return response()->view('app');
        }

        return response()->json($service->rates());
    }
}
