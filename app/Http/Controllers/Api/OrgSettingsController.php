<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CefrLevel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OrgSettingsController extends Controller
{
    // =========================================================================
    // CEFR LEVELS CRUD
    // =========================================================================

    public function indexCefr(Request $request): JsonResponse
    {
        $levels = $request->user()->organization->cefrLevels()->orderBy('score_min')->get();
        return response()->json($levels);
    }

    public function storeCefr(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'cefr_map'  => ['required', 'string', 'max:50'],
            'score_min' => ['required', 'integer', 'min:0', 'max:100'],
            'score_max' => ['required', 'integer', 'min:0', 'max:100', 'gte:score_min'],
            'goals'     => ['nullable', 'string'],
            'color'     => ['nullable', 'string', 'max:30'],
        ]);

        $level = CefrLevel::create(array_merge($validated, ['org_id' => $orgId]));

        return response()->json($level, 201);
    }

    public function updateCefr(Request $request, CefrLevel $cefrLevel): JsonResponse
    {
        if ($cefrLevel->org_id !== $request->user()->org_id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'cefr_map'  => ['sometimes', 'string', 'max:50'],
            'score_min' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'score_max' => ['sometimes', 'integer', 'min:0', 'max:100', 'gte:score_min'],
            'goals'     => ['nullable', 'string'],
            'color'     => ['nullable', 'string', 'max:30'],
        ]);

        $cefrLevel->update($validated);

        return response()->json($cefrLevel);
    }

    public function destroyCefr(Request $request, CefrLevel $cefrLevel): JsonResponse
    {
        if ($cefrLevel->org_id !== $request->user()->org_id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $cefrLevel->delete();

        return response()->json(null, 204);
    }


    // =========================================================================
    // TEAM MEMBERS CRUD
    // =========================================================================

    public function indexTeam(Request $request): JsonResponse
    {
        $members = User::where('org_id', $request->user()->org_id)
            ->whereIn('role', ['admin', 'moderator'])
            ->get();
            
        return response()->json($members);
    }

    public function storeTeam(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', Rule::in(['admin', 'moderator'])],
            'status'   => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $user = User::create([
            'org_id'   => $orgId,
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'status'   => $validated['status'],
        ]);

        return response()->json($user, 201);
    }

    public function updateTeam(Request $request, User $teamMember): JsonResponse
    {
        // Don't allow cross-org editing or editing students through this endpoint
        if ($teamMember->org_id !== $request->user()->org_id || $teamMember->role === 'student') {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', Rule::unique('users')->ignore($teamMember->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role'     => ['sometimes', Rule::in(['admin', 'moderator'])],
            'status'   => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $teamMember->update($validated);

        return response()->json($teamMember);
    }

    public function destroyTeam(Request $request, User $teamMember): JsonResponse
    {
        if ($teamMember->org_id !== $request->user()->org_id || $teamMember->role === 'student') {
            return response()->json(['message' => 'Not found'], 404);
        }

        // Prevent deleting yourself
        if ($teamMember->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 400);
        }

        $teamMember->delete();

        return response()->json(null, 204);
    }
}
