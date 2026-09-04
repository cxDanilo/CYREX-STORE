<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ReferralRouter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

// Primer toque gana: si el visitante YA tiene la cookie puesta (de este
// vendedor o de otro), no se pisa — si dejáramos que gane el último link
// visitado, cualquier vendedor podría "robarse" un cliente que ya venía
// referido de otro mandándole su propio link después. Justo lo que este
// feature existe para evitar.
class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->filled('ref')
            && ! $request->cookie(ReferralRouter::COOKIE_NAME)
            && ! $request->is('admin/*')
        ) {
            $code = Str::lower($request->query('ref'));

            if (User::where('ref_code', $code)->exists()) {
                Cookie::queue(ReferralRouter::COOKIE_NAME, $code, 60 * 24 * ReferralRouter::COOKIE_DAYS);

                // Cookie::queue() solo va en la respuesta que sale — recién
                // estaría disponible para el navegador en el PRÓXIMO pedido.
                // Sin esto, la primera página que ve un cliente que acaba de
                // llegar por el link compartido (el momento que más importa)
                // mostraría el número general en vez del del vendedor.
                $request->cookies->set(ReferralRouter::COOKIE_NAME, $code);
            }
        }

        return $next($request);
    }
}
