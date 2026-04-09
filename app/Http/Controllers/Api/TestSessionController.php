<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TestSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestSessionController extends Controller
{
    /**
     * POST /api/test/register
     * Handle student entering the test via email or signed URL token.
     */
    public function register(Request $request): JsonResponse
    {
        // 1. Validate request (email + org_id from invite link)
        $validated = $request->validate([
            'email'  => ['required', 'email'],
            'org_id' => ['required', 'integer', 'exists:organizations,id'],
        ]);

        // 2. Find the student
        $student = Student::where('email', $validated['email'])
                          ->where('org_id', $validated['org_id'])
                          ->first();

        if (!$student) {
            return response()->json(['message' => 'Student record not found or invalid organization.'], 404);
        }

        if ($student->status === 'completed') {
            return response()->json(['message' => 'This test has already been completed.'], 400);
        }

        // 3. Mark accepted (User clicked link, but hasn't started timer)
        if ($student->status === 'pending') {
            $student->update(['status' => 'accepted']);
        }

        // 4. Create TestSubmission if it doesn't exist
        $submission = TestSubmission::firstOrCreate(
            ['student_id' => $student->id],
            ['answers' => [], 'draft_answers' => []]
        );

        // 5. Issue Sanctum token explicitly scoped for the student
        $student->tokens()->delete(); // Clear old ones to enforce single active session
        $token = $student->createToken('test-taker', ['student'])->plainTextToken;

        return response()->json([
            'token'      => $token,
            'student'    => $student,
            'submission' => $submission,
        ]);
    }

    /**
     * POST /api/test/start
     * Called when the accepted student clicks "Start Test Now"
     */
    public function start(Request $request): JsonResponse
    {
        /** @var \App\Models\Student $student */
        $student = $request->user();

        if ($student->status !== 'accepted') {
            return response()->json(['message' => 'Test cannot be started. Invalid status.'], 400);
        }

        $student->update([
            'status' => 'in_progress',
            'test_date' => now()
        ]);

        return response()->json([
            'message' => 'Test started successfully.',
            'student' => $student,
        ]);
    }

    /**
     * GET /api/test/session
     * Return current progress and test metadata
     */
    public function session(Request $request): JsonResponse
    {
        /** @var \App\Models\Student $student */
        $student = $request->user();
        $student->load('testSubmission');

        return response()->json([
            'student'    => $student,
            'submission' => $student->testSubmission,
        ]);
    }

    /**
     * POST /api/test/save-section
     * Save progress for a section directly into a draft_answers JSONB column.
     */
    public function saveSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section_id' => ['required', 'string'],
            'answers'    => ['required', 'array'],
        ]);

        /** @var \App\Models\Student $student */
        $student = $request->user();
        $submission = $student->testSubmission;

        // Fetch current draft, merge new section, save back
        $draft = $submission->draft_answers ?? [];
        $draft[$validated['section_id']] = $validated['answers'];
        
        $submission->update(['draft_answers' => $draft]);

        return response()->json([
            'message'       => 'Test section progress saved.',
            'draft_answers' => $draft,
        ]);
    }

    /**
     * POST /api/test/submit
     * Final submission wrapper.
     */
    public function submit(Request $request, \App\Services\SubmitTestService $submitService): JsonResponse
    {
        /** @var \App\Models\Student $student */
        $student = $request->user();

        // Delegate to the service to lock the test and dispatch the AI scoring Job
        $submitService->submit($student);

        return response()->json([
            'message' => 'Complete! Your test has been submitted successfully.',
        ]);
    }
}
