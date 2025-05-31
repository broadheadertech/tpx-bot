<?php

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [ReportController::class, 'webhook']);
Route::get('weekly-sales', [ReportController::class, 'getWeeklySales']);

