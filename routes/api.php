<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Speaking Genie Placement Test Platform
|--------------------------------------------------------------------------
|
| Middleware aliases registered in bootstrap/app.php:
|   auth:sanctum        — Sanctum token guard
|   org.access          — EnsureOrgAccess (multi-tenant scoping)
|   role.staff          — EnsureAdminOrModerator
|   role.admin          — EnsureAdmin
|
*/

// ─── Public: Authentication ──────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
});

// ─── Protected: All authenticated staff ──────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    // Current user profile
    Route::get('me', [MeController::class, 'show']);

    // Logout
    Route::post('auth/logout', [LoginController::class, 'logout']);

    // ── Staff-only routes (Admin + Moderator) ────────────────────────────────
    Route::middleware(['role.staff'])->group(function () {

        // Dashboard — GET /api/dashboard/stats
        // Dashboard — GET /api/dashboard/stats
        Route::get('dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

        // Students — list, invite, resend invite
        Route::get('students', [\App\Http\Controllers\Api\StudentController::class, 'index']);
        
        // Detailed submissions
        Route::get('submissions/{submission}', [\App\Http\Controllers\Api\SubmissionController::class, 'show']);

        // ── Admin-only routes ─────────────────────────────────────────────────
        Route::middleware(['role.admin'])->group(function () {

            // Invite / create student
            Route::post('students/invite', [\App\Http\Controllers\Api\StudentController::class, 'invite']);
            Route::post('students/{student}/resend-invite', [\App\Http\Controllers\Api\StudentController::class, 'resendInvite']);

            // CEFR Levels management (via OrgSettingsController)
            Route::get('settings/cefr',     [\App\Http\Controllers\Api\OrgSettingsController::class, 'indexCefr']);
            Route::post('settings/cefr',    [\App\Http\Controllers\Api\OrgSettingsController::class, 'storeCefr']);
            Route::put('settings/cefr/{cefrLevel}', [\App\Http\Controllers\Api\OrgSettingsController::class, 'updateCefr']);
            Route::delete('settings/cefr/{cefrLevel}', [\App\Http\Controllers\Api\OrgSettingsController::class, 'destroyCefr']);

            // Team management (via OrgSettingsController)
            Route::get('team',           [\App\Http\Controllers\Api\OrgSettingsController::class, 'indexTeam']);
            Route::post('team',          [\App\Http\Controllers\Api\OrgSettingsController::class, 'storeTeam']);
            Route::put('team/{user}',    [\App\Http\Controllers\Api\OrgSettingsController::class, 'updateTeam']);
            Route::delete('team/{user}', [\App\Http\Controllers\Api\OrgSettingsController::class, 'destroyTeam']);
        });
    });
});

// ─── Test-Taker API (student flow) ───────────────────────────────────────────
Route::prefix('test')->group(function () {
    // org.access middleware validates that the org_id in the request body
    // matches a real organization, providing an extra guard on the register step.
    Route::post('register', [\App\Http\Controllers\Api\TestSessionController::class, 'register'])
        ->middleware('org.access');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('start',        [\App\Http\Controllers\Api\TestSessionController::class, 'start']);
        Route::get('session',       [\App\Http\Controllers\Api\TestSessionController::class, 'session']);
        Route::post('save-section', [\App\Http\Controllers\Api\TestSessionController::class, 'saveSection']);
        Route::post('upload-media', [\App\Http\Controllers\Api\MediaUploadController::class, 'upload']);
        Route::post('submit',       [\App\Http\Controllers\Api\TestSessionController::class, 'submit']);
    });
});
