<?php

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [ReportController::class, 'webhook']);
Route::get('weekly-sales', [ReportController::class, 'getWeeklySales']);
Route::post('/telegram/webhook/generate-report', [ReportController::class, 'handleTelegramWebhook']);
Route::get('record-sale', [ReportController::class, 'recordSale']);
