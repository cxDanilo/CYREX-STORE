<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/tienda', [ShopController::class, 'index'])->name('shop');
Route::get('/producto/{slug}', [ShopController::class, 'show'])->name('product.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::redirect('/', '/admin/productos');

        Route::get('productos', [AdminProductController::class, 'index'])->name('productos.index');
        Route::get('productos/nuevo', [AdminProductController::class, 'create'])->name('productos.create');
        Route::post('productos', [AdminProductController::class, 'store'])->name('productos.store');
        Route::get('productos/{product}/editar', [AdminProductController::class, 'edit'])->name('productos.edit');
        Route::put('productos/{product}', [AdminProductController::class, 'update'])->name('productos.update');
        Route::delete('productos/{product}', [AdminProductController::class, 'destroy'])->name('productos.destroy');
        Route::patch('productos/{product}/estado', [AdminProductController::class, 'toggleStatus'])->name('productos.toggle-status');

        Route::get('usuarios', [AdminUserController::class, 'index'])->name('usuarios.index');
    });
});
