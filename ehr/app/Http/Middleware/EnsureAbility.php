<?php

namespace App\Http\Middleware;

use App\Support\RolePermissions;
use Closure;
use Illuminate\Http\Request;

class EnsureAbility
{
    public function handle(Request $request, Closure $next, string $ability)
    {
        $user = $request->user();

        if (! RolePermissions::allows($user, $ability)) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'required_ability' => $ability,
                'role' => $user?->role,
            ], 403);
        }

        return $next($request);
    }
}
