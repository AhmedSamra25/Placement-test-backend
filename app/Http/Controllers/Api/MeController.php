<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * GET /api/me
     *
     * Return the authenticated user's profile with their organization status.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->load('organization');

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
            'status' => $user->status,
            'organization' => $user->organization ? [
                'id'              => $user->organization->id,
                'name'            => $user->organization->name,
                'license_limit'   => $user->organization->license_limit,
                'license_used'    => $user->organization->license_used,
                'remaining'       => $user->organization->remaining_licenses,
            ] : null,
        ]);
    }
}
