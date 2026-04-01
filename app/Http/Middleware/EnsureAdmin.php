<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureAdmin
 *
 * Allows only users with role = 'admin'.
 * Use for any route that modifies org-wide settings (CEFR levels, team members, etc.).
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden. This action requires the Admin role.',
            ], 403);
        }

        return $next($request);
    }
}
