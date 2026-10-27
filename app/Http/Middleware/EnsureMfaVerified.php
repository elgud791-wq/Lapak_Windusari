<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     $user = $request->user();

    //     // Kalau belum login, biarkan middleware auth yang urus
    //     if (! $user) {
    //         return $next($request);
    //     }

    //     // Kalau user belum pernah setup MFA sama sekali, paksa setup dulu
    //     if (! $user->google2fa_enabled) {
    //         if ($request->routeIs('mfa.setup') || $request->routeIs('mfa.confirm')) {
    //             return $next($request);
    //         }

    //         return redirect()->route('mfa.setup')
    //             ->with('warning', 'Anda wajib mengaktifkan MFA (Google Authenticator) sebelum melanjutkan.');
    //     }

    //     // Kalau MFA sudah aktif tapi sesi ini belum verifikasi kode, paksa verifikasi
    //     if (! session('mfa_verified')) {
    //         if ($request->routeIs('mfa.verify') || $request->routeIs('mfa.verify.submit')) {
    //             return $next($request);
    //         }

    //         return redirect()->route('mfa.verify');
    //     }

    //     return $next($request);
    // }




    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
    }

        // === SEMENTARA UNTUK PENGUJIAN KINERJA ===
        // Jika user ini dikecualikan dari MFA (misalnya untuk uji tanpa MFA)
        $excludedEmails = [
            'desa.balesari@windusari.test',   // akun uji tanpa MFA
        ];

        if (in_array($user->email, $excludedEmails)) {
            return $next($request); // langsung lolos, tidak dipaksa MFA
        }
            // === AKHIR BAGIAN SEMENTARA ===

            // Kalau user belum pernah setup MFA sama sekali, paksa setup dulu
                if (!$user->google2fa_enabled) {
                    if ($request->routeIs('mfa.setup') || $request->routeIs('mfa.confirm')) {
                        return $next($request);
                }

                return redirect()->route('mfa.setup')
                    ->with('warning', 'Anda wajib mengaktifkan MFA (Google Authenticator) sebelum melanjutkan.');
                }

            // Kalau MFA sudah aktif tapi sesi ini belum verifikasi kode
                if (!session('mfa_verified')) {
                    if ($request->routeIs('mfa.verify') || $request->routeIs('mfa.verify.submit')) {
                        return $next($request);
                }

                return redirect()->route('mfa.verify');
                }

        return $next($request);
    }
}