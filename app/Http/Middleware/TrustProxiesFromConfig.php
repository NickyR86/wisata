<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Mempercayai header X-Forwarded-* hanya dari proxy yang dinyatakan di SIAP_TRUSTED_PROXIES.
 * Tanpa ini, di balik reverse proxy HTTPS aplikasi menganggap permintaan bukan HTTPS
 * (cookie tanpa flag Secure, URL http://, HSTS tidak terkirim).
 * Nilai '*' berarti percaya pada koneksi langsung yang masuk; pakai hanya bila server
 * aplikasi tidak dapat dijangkau langsung dari internet.
 */
class TrustProxiesFromConfig
{
    public function handle(Request $request, Closure $next)
    {
        $raw = trim((string) config('siap.trusted_proxies'));
        if ($raw !== '') {
            $proxies = $raw === '*'
                ? [(string) $request->server->get('REMOTE_ADDR')]
                : array_values(array_filter(array_map('trim', explode(',', $raw))));
            if ($proxies !== [] && $proxies !== ['']) {
                Request::setTrustedProxies(
                    $proxies,
                    Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
                );
            }
        }
        return $next($request);
    }
}
