<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TourController extends Controller
{
    // Se llama al terminar el recorrido O al saltarlo (ver
    // partials/admin-tour.blade.php) — cualquiera de los dos casos
    // significa "ya lo vio", no vuelve a aparecer solo en el próximo
    // login.
    public function dismiss(Request $request): Response
    {
        $request->user()->forceFill(['tour_seen_at' => now()])->save();

        return response()->noContent();
    }
}
