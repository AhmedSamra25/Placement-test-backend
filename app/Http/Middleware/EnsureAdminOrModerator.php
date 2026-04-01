<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureAdminOrModerator
 *
 * Allows users with role = 'admin' OR role = 'moderator'.
 * Use for dashboard, submission review, and student listing routes.
 * Blocks students or unauthenticated users.
 */
class EnsureAdminOrModerator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['admin', 'moderator'], true)) {
            return response()->json([
                'message' => 'Forbidden. Staff access required.',
            ], 403);
        }

        return $next($request);
    }
}
