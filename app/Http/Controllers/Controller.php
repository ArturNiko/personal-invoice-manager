<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

abstract class Controller
{
    protected function spaShellResponse(): Response
    {
        return response()->view('app');
    }
}