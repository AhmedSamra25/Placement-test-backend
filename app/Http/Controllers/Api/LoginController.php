<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * POST /api/auth/login
     *
     * Accepts email + password and returns a Sanctum token.
     * Works for Org Admins, Moderators (role != student stored in users table).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only allow active staff users to log in via this endpoint
        if ($user->status !== 'active') {
            Auth::logout();
            return response()->json([
                'message' => 'Your account is inactive. Please contact your administrator.',
            ], 403);
        }

        // Revoke all existing tokens (single-session policy)
        $user->tokens()->delete();

        // Create a named token scoped by role
        $token = $user->createToken(
            name: "auth-{$user->role}",
            abilities: $this->abilitiesForRole($user->role),
        );

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'org'   => $user->organization?->only(['id', 'name']),
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Map a role to the Sanctum token abilities it may use.
     */
    private function abilitiesForRole(string $role): array
    {
        return match ($role) {
            'admin'     => ['admin', 'moderator', 'read'],
            'moderator' => ['moderator', 'read'],
            default     => ['read'],
        };
    }
}
