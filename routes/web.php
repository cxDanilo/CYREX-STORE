<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PageBlockController as AdminPageBlockController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\MediaFolderController as AdminMediaFolderController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\SocialLinkController as AdminSocialLinkController;
use App\Http\Controllers\PageController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/tienda', [ShopController::class, 'index'])->name('shop');
Route::get('/producto/{slug}', [ShopController::class, 'show'])->name('product.show');
Route::get('/buscar-sugerencias', [ShopController::class, 'suggest'])->name('shop.suggest');

Route::post('/carrito/agregar', [CartController::class, 'add'])->name('cart.add');
Route::delete('/carrito/quitar/{key}', [CartController::class, 'remove'])->name('cart.remove');

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

        Route::get('categorias', [AdminCategoryController::class, 'index'])->name('categorias.index');
        Route::get('categorias/nueva', [AdminCategoryController::class, 'create'])->name('categorias.create');
        Route::post('categorias', [AdminCategoryController::class, 'store'])->name('categorias.store');
        Route::get('categorias/{category}/editar', [AdminCategoryController::class, 'edit'])->name('categorias.edit');
        Route::put('categorias/{category}', [AdminCategoryController::class, 'update'])->name('categorias.update');
        Route::delete('categorias/{category}', [AdminCategoryController::class, 'destroy'])->name('categorias.destroy');

        Route::get('usuarios', [AdminUserController::class, 'index'])->name('usuarios.index');

        Route::get('ajustes', [AdminSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('ajustes', [AdminSettingsController::class, 'update'])->name('settings.update');

        // --- Infraestructura CMS (sin lógica todavía, ver CMS_ARCHITECTURE.md) ---
        Route::get('paginas', [AdminPageController::class, 'index'])->name('paginas.index');
        Route::get('paginas/nueva', [AdminPageController::class, 'create'])->name('paginas.create');
        Route::post('paginas', [AdminPageController::class, 'store'])->name('paginas.store');
        Route::get('paginas/{page}/editar', [AdminPageController::class, 'edit'])->name('paginas.edit');
        Route::put('paginas/{page}', [AdminPageController::class, 'update'])->name('paginas.update');
        Route::delete('paginas/{page}', [AdminPageController::class, 'destroy'])->name('paginas.destroy');
        Route::get('paginas/{page}/contenido', [AdminPageController::class, 'content'])->name('paginas.content');
        Route::get('paginas/{page}/bloques', [AdminPageBlockController::class, 'index'])->name('paginas.bloques.index');
        Route::put('paginas/{page}/bloques', [AdminPageBlockController::class, 'store'])->name('paginas.bloques.store');
        Route::post('paginas/{page}/bloques/preview', [AdminPageBlockController::class, 'preview'])->name('paginas.bloques.preview');

        Route::get('plantillas', [AdminTemplateController::class, 'index'])->name('plantillas.index');
        Route::get('plantillas/nueva', [AdminTemplateController::class, 'create'])->name('plantillas.create');
        Route::post('plantillas', [AdminTemplateController::class, 'store'])->name('plantillas.store');
        Route::get('plantillas/{template}/editar', [AdminTemplateController::class, 'edit'])->name('plantillas.edit');
        Route::put('plantillas/{template}', [AdminTemplateController::class, 'update'])->name('plantillas.update');
        Route::delete('plantillas/{template}', [AdminTemplateController::class, 'destroy'])->name('plantillas.destroy');

        Route::get('medios', [AdminMediaController::class, 'index'])->name('medios.index');
        Route::post('medios', [AdminMediaController::class, 'store'])->name('medios.store');
        Route::put('medios/{medium}', [AdminMediaController::class, 'update'])->name('medios.update');
        Route::delete('medios/{medium}', [AdminMediaController::class, 'destroy'])->name('medios.destroy');
        Route::post('medios/carpetas', [AdminMediaFolderController::class, 'store'])->name('medios.carpetas.store');
        Route::delete('medios/carpetas/{folder}', [AdminMediaFolderController::class, 'destroy'])->name('medios.carpetas.destroy');

        Route::get('menus', [AdminMenuController::class, 'index'])->name('menus.index');
        Route::get('menus/nuevo', [AdminMenuController::class, 'create'])->name('menus.create');
        Route::post('menus', [AdminMenuController::class, 'store'])->name('menus.store');
        Route::get('menus/{menu}/editar', [AdminMenuController::class, 'edit'])->name('menus.edit');
        Route::put('menus/{menu}', [AdminMenuController::class, 'update'])->name('menus.update');
        Route::delete('menus/{menu}', [AdminMenuController::class, 'destroy'])->name('menus.destroy');

        Route::get('redes', [AdminSocialLinkController::class, 'index'])->name('redes.index');
        Route::get('redes/nueva', [AdminSocialLinkController::class, 'create'])->name('redes.create');
        Route::post('redes', [AdminSocialLinkController::class, 'store'])->name('redes.store');
        Route::get('redes/{socialLink}/editar', [AdminSocialLinkController::class, 'edit'])->name('redes.edit');
        Route::put('redes/{socialLink}', [AdminSocialLinkController::class, 'update'])->name('redes.update');
        Route::delete('redes/{socialLink}', [AdminSocialLinkController::class, 'destroy'])->name('redes.destroy');
    });
});

// --- CMS: catch-all de páginas públicas. DEBE quedar como la ÚLTIMA ruta del
// archivo — solo matchea un único segmento de URL sin coincidencias previas,
// así que nunca puede interceptar /tienda, /admin/*, /producto/{slug}, etc.
// Ver CMS_ARCHITECTURE.md sección 2.1.
Route::get('/{slug}', [PageController::class, 'show'])->name('page.show')->where('slug', '[a-z0-9-]+');
