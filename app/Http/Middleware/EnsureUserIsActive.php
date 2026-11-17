<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencabut session pengguna yang dinonaktifkan setelah login.
 *
 * LoginRequest sudah menolak akun nonaktif, tetapi tanpa middleware ini
 * session yang sudah aktif tetap dapat memakai permission lama sampai logout
 * atau expired (TECHNICAL-SPEC §7).
 */
final class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => __('auth.inactive'),
            ]);
        }

        return $next($request);
    }
}
