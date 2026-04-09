<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\StudentInviteMail;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * GET /api/students
     * List all students for the organization with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $query = Student::where('org_id', $user->org_id)
            ->with('testSubmission');

        // Filter by status — validate against the allowed enum values first
        if ($request->filled('status')) {
            $request->validate([
                'status' => ['required', Rule::in(['pending', 'accepted', 'in_progress', 'completed'])],
            ]);
            $query->where('status', $request->input('status'));
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $students = $query->latest()->paginate(15);

        return response()->json($students);
    }

    /**
     * POST /api/students/invite
     * Create a student entry and send the invitation email.
     * Admin only.
     */
    public function invite(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $org = $user->organization;

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => [
                'required', 'email',
                // Unique to this specific organization
                Rule::unique('students')->where(function ($query) use ($org) {
                    return $query->where('org_id', $org->id);
                }),
            ],
            'target_language' => ['nullable', 'string', 'max:50'],
        ]);

        // Enforce license limit
        if ($org->license_used >= $org->license_limit && $org->license_limit > 0) {
            return response()->json([
                'message' => 'License limit reached. Cannot invite more students.',
            ], 403);
        }

        $student = Student::create([
            'org_id'          => $org->id,
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'target_language' => $validated['target_language'] ?? null,
            'status'          => 'pending',
        ]);

        // Increment org license used tracking
        $org->increment('license_used');

        $this->sendInviteEmail($student);

        return response()->json([
            'message' => 'Student invited successfully.',
            'student' => $student,
        ], 201);
    }

    /**
     * POST /api/students/{student}/resend-invite
     * Admin only.
     */
    public function resendInvite(Request $request, Student $student): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($student->org_id !== $user->org_id) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        if ($student->status !== 'pending') {
            return response()->json(['message' => 'Test already started or completed.'], 400);
        }

        $this->sendInviteEmail($student);

        return response()->json([
            'message' => 'Invitation resent successfully.',
        ]);
    }

    /**
     * Helper to dispatch the invite email.
     */
    private function sendInviteEmail(Student $student): void
    {
        // Uses FRONTEND_URL from .env so the link points to the student-facing app,
        // not the Laravel API server.
        $inviteLink = rtrim(config('app.frontend_url'), '/') . '/take-test'
            . '?email=' . urlencode($student->email)
            . '&org_id=' . $student->org_id;

        Mail::to($student->email)->queue(new StudentInviteMail($student, $inviteLink));
    }
}
