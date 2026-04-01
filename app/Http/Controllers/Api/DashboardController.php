<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/stats
     *
     * Returns:
     * - Total students
     * - Completion rate (% of students who finished)
     * - Remaining licenses
     * - Average overall score
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $orgId = $user->org_id;

        // Fetch students scoped to this org
        $students = Student::where('org_id', $orgId)->get();

        $totalStudents = $students->count();
        $completedStudents = $students->where('status', 'completed')->count();

        $completionRate = $totalStudents > 0 
            ? round(($completedStudents / $totalStudents) * 100, 1) 
            : 0;

        $averageScore = $students->whereNotNull('overall_score')->avg('overall_score') ?? 0;
        $averageScore = round((float) $averageScore, 2);

        $org = $user->organization;

        return response()->json([
            'total_students'     => $totalStudents,
            'completed_students' => $completedStudents,
            'completion_rate'    => $completionRate,
            'average_score'      => $averageScore,
            'licenses_remaining' => $org->remaining_licenses,
            'licenses_used'      => $org->license_used,
            'license_limit'      => $org->license_limit,
        ]);
    }
}
