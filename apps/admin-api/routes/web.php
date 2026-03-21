<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'code' => '0000',
        'message' => 'IFOS admin API is available.',
        'data' => [
            'app' => config('app.name'),
            'api_health' => url('/api/v1/health'),
        ],
    ]);
});
