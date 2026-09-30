<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIngestToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.ingest.token');

        if (! $expected || ! hash_equals($expected, (string) $request->bearerToken())) {
            abort(401, 'Invalid or missing ingest token.');
        }

        return $next($request);
    }
}
