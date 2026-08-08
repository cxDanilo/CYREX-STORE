<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class CacheController extends Controller
{
    /**
     * Limpia toda la caché de la app (config, vistas compiladas, rutas,
     * y la caché de datos donde vive el nav de categorías y los Settings
     * cacheados "forever") — para no depender de entrar por SSH cada vez
     * que un cambio no se refleja en el sitio.
     */
    public function purge()
    {
        Artisan::call('optimize:clear');

        return back()->with('status', 'Caché purgada — los cambios recientes ya deberían verse en el sitio.');
    }
}
