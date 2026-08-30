<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * El mensaje de "demasiados intentos" viene por defecto de un
     * withErrors() flasheado, que solo sobrevive UN request — si el
     * visitante refresca la página en vez de reenviar el form, el
     * contador desaparece aunque el bloqueo real (en RateLimiter)
     * sigue activo. Para que la página muestre el estado real incluso
     * después de un refresh, guardamos el último email intentado en
     * sesión normal (no flash) y volvemos a chequear el límite acá.
     */
    public function showLogin(Request $request)
    {
        $lockoutSeconds = null;
        $email = $request->session()->get('login_throttle_email');

        if ($email) {
            $throttleKey = $this->throttleKey($request, $email);
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                $lockoutSeconds = RateLimiter::availableIn($throttleKey);
            }
        }

        return view('admin.auth.login', ['lockoutSeconds' => $lockoutSeconds]);
    }

    /**
     * Máximo de intentos fallidos por combinación email+IP antes de
     * bloquear temporalmente — evita fuerza bruta contra el login de
     * admin. Se cuenta por email+IP (no solo IP) para que probar
     * muchos emails distintos desde la misma IP también se frene.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $request->session()->put('login_throttle_email', $credentials['email']);
        $throttleKey = $this->throttleKey($request, $credentials['email']);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Demasiados intentos. Prueba de nuevo en {$seconds} segundos.",
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => 'Esas credenciales no coinciden con ningún usuario.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget('login_throttle_email');
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function throttleKey(Request $request, string $email): string
    {
        return Str::transliterate(Str::lower($email)).'|'.$request->ip();
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
