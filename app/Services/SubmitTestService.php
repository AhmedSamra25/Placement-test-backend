<?php

namespace App\Services;

use App\Jobs\AnalyzeTestSubmission;
use App\Models\Student;

class SubmitTestService
{
    /**
     * Finalizes the student's test submission and dispatches the AI grading job.
     */
    public function submit(Student $student): void
    {
        $submission = $student->testSubmission;

        // 1. Move draft_answers to final answers
        $submission->update([
            'answers'   => $submission->draft_answers ?? [],
            'ai_status' => 'pending', // Queue indicator for Gemini processing
        ]);

        // 2. Mark student complete
        $student->update(['status' => 'completed']);

        // 3. Revoke student token so they can't access or edit anymore
        $student->tokens()->delete();

        // 4. Dispatch the background AI scoring job
        AnalyzeTestSubmission::dispatch($submission);
    }
}
