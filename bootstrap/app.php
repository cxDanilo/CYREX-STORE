<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->redirectUsersTo('/admin/dashboard');
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureUserIsAdmin::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Página de error con la identidad visual del admin (CYREX ADMIN)
        // — SOLO para rutas /admin/*, nunca para la web pública (esa
        // sigue con la página de error default de Laravel, más genérica
        // pero sin tocar). Si el código no está en la lista de abajo, o
        // la request no es de admin, se deja que Laravel siga su curso
        // normal (return null).
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->is('admin/*')) {
                return null;
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Auth\AuthenticationException) {
                // Estas dos NO implementan HttpExceptionInterface (Laravel
                // recién las convierte a 404/401 más adelante en su propio
                // pipeline) — acá interceptamos antes, así que hay que
                // mapearlas a mano o quedan como 500 genérico.
                $status = $e instanceof \Illuminate\Auth\AuthenticationException ? 401 : 404;
            } else {
                $status = 500;
            }

            $messages = [
                401 => 'Necesitás iniciar sesión para ver esto.',
                403 => 'No tenés permiso para acceder a esto.',
                404 => 'Esta página no existe.',
                405 => 'Esa acción no está permitida desde acá.',
                419 => 'La página expiró — volvé a intentar.',
                429 => 'Demasiados intentos — esperá un momento.',
                500 => 'Algo salió mal de nuestro lado.',
                503 => 'El sitio está en mantenimiento — volvé en un rato.',
            ];

            if (! isset($messages[$status])) {
                return null;
            }

            return response()->view('errors.admin', [
                'code' => $status,
                'message' => $messages[$status],
            ], $status);
        });
    })->create();
