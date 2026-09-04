<?php

namespace App\Http\Middleware;

use App\Support\PageLabelResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Un vendedor no va a ir a buscar "su link" a una pantalla aparte del
// admin — vende copiando directo la URL que tiene abierta en ese
// momento. Esto hace que cualquier página pública que visite logueado
// ya lleve su propio ?ref= puesto en la barra de direcciones, así lo
// que copie y comparta siempre lo enruta a él (ver ReferralRouter).
class PropagateOwnReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (
            $request->isMethod('GET')
            && ! $request->ajax()
            && ! $request->filled('ref')
            && $user?->ref_code
            && PageLabelResolver::resolve($request) !== null
        ) {
            return redirect()->to($request->fullUrlWithQuery(['ref' => $user->ref_code]));
        }

        return $next($request);
    }
}
