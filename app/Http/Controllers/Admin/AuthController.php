<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ReferralRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
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
            $lockoutSeconds = $this->currentLockout($request, $email);
        }

        return view('admin.auth.login', [
            'lockoutSeconds' => $lockoutSeconds,
            'logoUrl' => Setting::logoUrl(),
        ]);
    }

    /**
     * Máximo de intentos fallidos por combinación email+IP antes de
     * bloquear temporalmente — evita fuerza bruta contra el login de
     * admin. Se cuenta por email+IP (no solo IP) para que probar
     * muchos emails distintos desde la misma IP también se frene.
     *
     * Además, un segundo límite SOLO por email (sin IP), con un umbral
     * más alto — el de arriba no frena a un atacante que rota de IP en
     * cada intento (botnet/proxies), ya que cada IP nueva le da 5
     * intentos frescos contra el mismo email. Ver auditoría de
     * seguridad, hallazgo F5.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $request->session()->put('login_throttle_email', $credentials['email']);

        if ($seconds = $this->currentLockout($request, $credentials['email'])) {
            return back()->withErrors([
                'email' => "Demasiados intentos. Prueba de nuevo en {$seconds} segundos.",
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request, $credentials['email']), 60);
            RateLimiter::hit($this->emailOnlyThrottleKey($credentials['email']), 3600);

            return back()->withErrors([
                'email' => 'Esas credenciales no coinciden con ningún usuario.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($this->throttleKey($request, $credentials['email']));
        RateLimiter::clear($this->emailOnlyThrottleKey($credentials['email']));
        $request->session()->forget('login_throttle_email');
        $request->session()->regenerate();

        // Para que el vendedor pueda navegar el sitio público desde su
        // propio celular/notebook (mostrarle algo a un cliente en
        // persona, o simplemente probar su link) y ya vea su propio
        // número de WhatsApp, sin tener que entrar primero por su link
        // con ?ref=. Acá SÍ se pisa cualquier cookie que hubiera —a
        // diferencia de CaptureReferral, que nunca pisa una existente
        // para proteger a un cliente ya referido— porque este es su
        // propio dispositivo, iniciando sesión como él mismo.
        if (Auth::user()->ref_code) {
            Cookie::queue(ReferralRouter::COOKIE_NAME, Auth::user()->ref_code, 60 * 24 * ReferralRouter::COOKIE_DAYS);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    private function throttleKey(Request $request, string $email): string
    {
        return Str::transliterate(Str::lower($email)).'|'.$request->ip();
    }

    private function emailOnlyThrottleKey(string $email): string
    {
        return 'login-email-only|'.Str::transliterate(Str::lower($email));
    }

    /**
     * Segundos de bloqueo restantes, el mayor entre los dos límites
     * (email+IP de 5 intentos, o email-solo de 20) — null si ninguno
     * está activo ahora mismo.
     */
    private function currentLockout(Request $request, string $email): ?int
    {
        $seconds = null;

        if (RateLimiter::tooManyAttempts($this->throttleKey($request, $email), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request, $email));
        }

        if (RateLimiter::tooManyAttempts($this->emailOnlyThrottleKey($email), 20)) {
            $seconds = max($seconds ?? 0, RateLimiter::availableIn($this->emailOnlyThrottleKey($email)));
        }

        return $seconds;
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
