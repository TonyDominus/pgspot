<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // geolocation=(self) is required for "vicino a me" / contribute locate.
        // If nginx/CDN also sends Permissions-Policy, it must not set geolocation=().
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(self), camera=(self), microphone=()',
        );
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        return $response;
    }
}
