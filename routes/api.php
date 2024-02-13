<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/scan', [\App\Http\Controllers\ApiController::class, 'scan'])->name('api.scan');

Route::get('/scan/status', [\App\Http\Controllers\ApiController::class, 'scanStatus'])->name('api.scan.status');

Route::get('/scan/{key}', [\App\Http\Controllers\ApiController::class, 'getResults'])->name('api.scan.key');

Route::get('/scan/{key}/stop', [\App\Http\Controllers\ApiController::class, 'stopCrawl'])->name('api.scan.stop');

