<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Keep everyone on the APP_URL address (e.g. 127.0.0.1:8000 -> localhost:8000).
 * Two addresses mean two separate sessions, which breaks sign-out and makes
 * Google reject the sign-in callback (redirect_uri_mismatch).
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next)
    {
        $app = parse_url((string) config('app.url'));
        $host = $app['host'] ?? null;
        $port = $app['port'] ?? (($app['scheme'] ?? 'http') === 'https' ? 443 : 80);

        // Only on a developer PC (localhost vs 127.0.0.1). On a real server the web server owns the hostname,
        // and behind a proxy the port seen here can differ, which would loop.
        if ($host && app()->environment('local') && ! app()->runningUnitTests() && $request->isMethod('GET') && ($request->getHost() !== $host || (int) $request->getPort() !== (int) $port)) {
            return redirect()->to(rtrim(config('app.url'), '/') . $request->getRequestUri());
        }

        return $next($request);
    }
}
