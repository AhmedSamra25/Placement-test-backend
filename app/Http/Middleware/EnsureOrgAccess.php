<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureOrgAccess
 *
 * Verifies that the authenticated user belongs to the same organization
 * as the resource they are trying to access.
 *
 * Usage: Apply to any route that receives an {org_id} route parameter,
 * or override $orgIdKey per route group if the param has a different name.
 */
class EnsureOrgAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Guests — let the auth middleware handle it
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Resolve the org_id from the route parameters
        $routeOrgId = $request->route('org_id')
            ?? $request->route('organization')
            ?? $request->input('org_id');

        // If no org_id is in the route, we cannot scope — pass through.
        // Routes that need strict scoping MUST include an org_id segment.
        if ($routeOrgId === null) {
            return $next($request);
        }

        if ((int) $user->org_id !== (int) $routeOrgId) {
            return response()->json([
                'message' => 'You do not have access to this organization\'s data.',
            ], 403);
        }

        return $next($request);
    }
}
