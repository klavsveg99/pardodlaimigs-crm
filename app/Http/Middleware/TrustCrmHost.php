<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustCrmHost
{
    /**
     * Only the CRM subdomain may reach this app.
     * Defends against requests via https://pardodlaimigs.lv/crm/...
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('wp-bridge.host', 'crm.pardodlaimigs.lv');
        $host = $request->getHost();

        // Allow CLI and tests through
        if (app()->runningInConsole() || app()->environment('testing') || $expected === '*') {
            return $next($request);
        }

        if ($host !== $expected) {
            // Allow local development access (serve --host / port forward).
            if (in_array($host, ['localhost', '127.0.0.1'], true)) {
                return $next($request);
            }

            return redirect('https://'.$expected.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
