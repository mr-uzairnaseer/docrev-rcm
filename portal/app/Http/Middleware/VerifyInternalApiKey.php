<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyInternalApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-DocRev-Api-Key');
        $expected = config('docrev.internal_api_key');

        if (! $key || ! hash_equals($expected, $key)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
