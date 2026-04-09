<?php

namespace App\Jobs;

use App\Models\TestSubmission;
use App\Services\GeminiScoringService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeTestSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Set a higher timeout for the job logic itself since Gemini API can sometimes take 20-40 seconds
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public TestSubmission $submission
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GeminiScoringService $geminiScoringService): void
    {
        // 1. Mark as processing
        $this->submission->update(['ai_status' => 'processing']);

        try {
            // 2. Call the real AI Scoring Engine
            $answers = $this->submission->answers ?? [];
            $audioPaths = $this->submission->speaking_audio_paths ?? [];
            
            $aiResults = $geminiScoringService->evaluate($answers, $audioPaths);

            // 3. Save the results
            // Note: `feedback` is an array returned by Gemini. We combine them nicely.
            $feedbackText = is_array($aiResults['feedback']) ? implode("\n", $aiResults['feedback']) : $aiResults['feedback'];

            $this->submission->update([
                'reading_score'       => $aiResults['reading'],
                'writing_score'       => $aiResults['writing'],
                'speaking_score'      => $aiResults['speaking'],
                'listening_score'     => $aiResults['listening'],
                'vocabulary_score'    => $aiResults['vocabulary'],
                'grammar_score'       => $aiResults['grammar'],
                'pronunciation_score' => $aiResults['pronunciation'],
                'ai_feedback'         => $feedbackText,
                'ai_status'           => 'completed',
            ]);
            
            // 4. Finalize the student's overall score cache
            $this->submission->student->update([
                'overall_score' => $this->submission->calculateOverallScore()
            ]);

        } catch (Exception $e) {
            Log::error("AnalyzeTestSubmission failed for ID {$this->submission->id}: " . $e->getMessage());
            
            // Move back to pending for retry, or failed
            $this->submission->update([
                'ai_status' => 'failed',
                'ai_feedback' => 'The AI Scoring Engine encountered an error: ' . $e->getMessage()
            ]);
            
            // Rethrow so the queue worker knows it failed
            throw $e;
        }
    }
}
