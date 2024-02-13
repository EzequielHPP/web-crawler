<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [App\Http\Controllers\HomepageController::class, 'index'])->name('home');

// redirect all other routes to the homepage
Route::get('/{any}', [App\Http\Controllers\HomepageController::class, 'redirect'])->where('any', '.*');
