<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Header keamanan standar untuk semua respons.
 * CSP dikirim hanya pada respons HTML (agar tidak mengganggu tampilan PDF/gambar unggahan),
 * dan secara bawaan berupa Report-Only: pelanggaran hanya tampil di konsol browser
 * sampai SIAP_CSP=enforce diaktifkan setelah semua halaman diperiksa.
 */
class SecurityHeaders
{
    public const POLICY = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        ."font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob:; connect-src 'self'; "
        ."object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        if (!$headers->has('X-Frame-Options')) $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        if ($request->isSecure() && !app()->environment('local', 'testing')) {
            $headers->set('Strict-Transport-Security', 'max-age=15552000');
        }
        $mode = (string) config('siap.csp', 'report-only');
        $type = (string) $headers->get('Content-Type', '');
        if (in_array($mode, ['report-only', 'enforce'], true) && ($type === '' || str_starts_with($type, 'text/html'))) {
            $headers->set($mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only', self::POLICY);
        }
        return $response;
    }
}
