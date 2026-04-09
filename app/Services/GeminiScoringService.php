<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiScoringService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        // We use gemini-1.5-pro for complex reasoning required in language assessment
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent';
    }

    /**
     * Call the Gemini APIs
     */
    public function evaluate(array $answers, array $audioPaths): array
    {
        if (empty($this->apiKey)) {
            throw new Exception("Gemini API key is not configured.");
        }

        $parts = $this->buildPromptParts($answers, $audioPaths);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts
                ]
            ],
            'generationConfig' => [
                // Enforce structured JSON output
                'responseMimeType' => 'application/json',
                'temperature' => 0.2, // Low temperature for consistent grading
            ]
        ];

        // Use Authorization header — keeps key out of URL / server access logs
        $response = Http::timeout(60)
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->post($this->baseUrl, $payload);

        if ($response->failed()) {
            Log::error('Gemini API Error: ' . $response->body());
            throw new Exception('Failed to get a valid response from the AI Scoring Engine.');
        }

        $result = $response->json();
        
        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            Log::error('Unexpected Gemini Response: ' . json_encode($result));
            throw new Exception('Unexpected response format from Gemini.');
        }

        $jsonText = $result['candidates'][0]['content']['parts'][0]['text'];
        
        $decoded = json_decode($jsonText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Gemini returned invalid JSON string.');
        }

        // Clamp scores to 1-6 (CEFR scale). Null means skill was not evaluated.
        $clamp = fn($val) => ($val !== null) ? max(1, min(6, (int) $val)) : null;

        return [
            'reading'       => $clamp($decoded['reading'] ?? null),
            'writing'       => $clamp($decoded['writing'] ?? null),
            'speaking'      => $clamp($decoded['speaking'] ?? null),
            'listening'     => $clamp($decoded['listening'] ?? null),
            'vocabulary'    => $clamp($decoded['vocabulary'] ?? null),
            'grammar'       => $clamp($decoded['grammar'] ?? null),
            'pronunciation' => $clamp($decoded['pronunciation'] ?? null),
            'feedback'      => is_array($decoded['feedback']) ? $decoded['feedback'] : [$decoded['feedback'] ?? ''],
        ];
    }

    /**
     * Construct the Prompt array including raw text and Base64 encoded audio bytes.
     */
    private function buildPromptParts(array $answers, array $audioPaths): array
    {
        $parts = [];

        // 1. The Persona & Instruction
        $instruction = <<<PROMPT
You are an expert Cambridge-certified ESL examiner assessing a student's placement test.
You will be provided with the student's text-based answers and standard audio responses (if any).
Based on the answers shown, assign a score for each skill between 1 and 6 (where 1 is Beginner (A1) and 6 is Advanced (C2)).
If a skill is completely missing or cannot be evaluated from the answers, return null.

You must respond ONLY in strict JSON format with exactly these keys:
{
    "reading": integer | null,
    "writing": integer | null,
    "speaking": integer | null,
    "listening": integer | null,
    "vocabulary": integer | null,
    "grammar": integer | null,
    "pronunciation": integer | null,
    "feedback": [
        "A highly detailed, constructive string explaining the student's strengths and weaknesses.",
        "Another sentence regarding actionable improvements."
    ]
}

Here are the text-based answers provided by the student:
PROMPT;

        $parts[] = ['text' => $instruction];
        $parts[] = ['text' => json_encode($answers, JSON_PRETTY_PRINT)];

        if (!empty($audioPaths)) {
            $parts[] = ['text' => "\nHere are the audio recordings for the Speaking/Pronunciation section to evaluate:"];
            // We read each audio file from local storage, encode as base64, and append as inlineData
            foreach ($audioPaths as $path) {
                // If the path is stored on the public disk
                if (Storage::disk('public')->exists($path)) {
                    $binaryData = Storage::disk('public')->get($path);
                    $base64 = base64_encode($binaryData);
                    
                    // Default to audio/webm (standard for browser uploads)
                    // We can also try to grab the extension if we need
                    $extension = pathinfo($path, PATHINFO_EXTENSION);
                    $mimeType = $extension === 'mp3' ? 'audio/mp3' : 'audio/webm';
                    $parts[] = [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $base64
                        ]
                    ];
                }
            }
        }

        return $parts;
    }
}
