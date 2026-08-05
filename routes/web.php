<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/tienda', [ShopController::class, 'index'])->name('shop');
Route::get('/producto/{slug}', [ShopController::class, 'show'])->name('product.show');
