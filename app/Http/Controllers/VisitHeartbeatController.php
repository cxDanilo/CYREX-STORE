<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsVisit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// Sin esto, alguien que se queda leyendo una sola página sin hacer clic
// "desaparece" de Conectados ahora a los 5 minutos aunque siga ahí — el
// layout público manda este ping cada 60s solo para refrescar last_seen_at,
// sin que cuente como una página vista nueva.
class VisitHeartbeatController extends Controller
{
    public function ping(Request $request): Response
    {
        if (! auth()->check()) {
            AnalyticsVisit::where('session_id', $request->session()->getId())
                ->update(['last_seen_at' => now()]);
        }

        return response()->noContent();
    }
}
