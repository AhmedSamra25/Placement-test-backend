<?php

namespace App\Jobs;

use App\Models\TestSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeTestSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public TestSubmission $submission
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Mark as processing
        $this->submission->update(['ai_status' => 'processing']);

        // 2. Prepare payload for the LLM scoring engine
        $payload = [
            'answers' => $this->submission->answers,
            // Assuming S3 or absolute public path URLs if needed:
            'speaking_audio_paths' => $this->submission->speaking_audio_paths,
        ];

        // 3. Call the simulated AI Scoring Engine
        $aiResults = $this->callScoringEngine($payload);

        // 4. Save the results
        $this->submission->update([
            'reading_score'       => $aiResults['reading'],
            'writing_score'       => $aiResults['writing'],
            'speaking_score'      => $aiResults['speaking'],
            'listening_score'     => $aiResults['listening'],
            'vocabulary_score'    => $aiResults['vocabulary'],
            'grammar_score'       => $aiResults['grammar'],
            'pronunciation_score' => $aiResults['pronunciation'],
            'ai_feedback'         => reset($aiResults['feedback']), // Just grabbing the first feedback string mock
            'ai_status'           => 'completed',
        ]);
        
        // Finalize the student's overall score cache
        $this->submission->student->update([
            'overall_score' => $this->submission->calculateOverallScore()
        ]);
    }

    /**
     * Mocks a call to a Gemini LLM API to evaluate the test payload.
     * Returns structured skill scores 1-6 (simulating strict multi-modal structural output).
     */
    private function callScoringEngine(array $payload): array
    {
        // ... Normally we would build an HTTP request to Gemini Multi-Modal API here.
        // For now, simulate network overhead:
        sleep(2);

        // Mimic structural Gemini response processing
        return [
            'reading'       => rand(1, 6),
            'writing'       => rand(2, 6),
            'speaking'      => rand(1, 4), // Assuming speaking is notoriously harder
            'listening'     => rand(2, 6),
            'vocabulary'    => rand(1, 6),
            'grammar'       => rand(1, 6),
            'pronunciation' => rand(1, 5),
            'feedback'      => [
                "The student demonstrates a solid baseline but struggles with advanced grammar constraints.",
                "Speaking rhythm is satisfactory, but vocabulary range is slightly restrictive.",
                "Excellent listening comprehension."
            ]
        ];
    }
}
