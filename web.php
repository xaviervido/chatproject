<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Broadcasting authentication route for WebSocket
Broadcast::routes(['middleware' => ['auth:sanctum']]);
