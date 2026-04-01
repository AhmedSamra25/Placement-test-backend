<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * GET /api/submissions/{submission}
     *
     * Returns full submission data along with CEFR mappings for each skill
     * and the overall score, based on the organization's custom CEFR levels.
     */
    public function show(Request $request, TestSubmission $submission): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // 1. Ensure submission belongs to the same org
        $submission->load('student');
        if ($submission->student->org_id !== $user->org_id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        // 2. Fetch the CEFR mapping rules for the org
        $cefrLevels = $user->organization->cefrLevels()->orderBy('score_min')->get();

        // 3. Helper to map a numeric score to a CEFR level object
        $mapScore = function ($score) use ($cefrLevels) {
            if ($score === null) return null;
            
            $match = $cefrLevels->first(function ($level) use ($score) {
                return $score >= $level->score_min && $score <= $level->score_max;
            });

            return $match ? [
                'name'     => $match->name,
                'cefr_map' => $match->cefr_map,
                'color'    => $match->color,
                'goals'    => $match->goals,
            ] : null;
        };

        $overallScore = $submission->calculateOverallScore();

        return response()->json([
            'id'                       => $submission->id,
            'student'                  => $submission->student,
            'answers'                  => $submission->answers,
            'writing_essay_path'       => $submission->writing_essay_path,
            'speaking_audio_paths'     => $submission->speaking_audio_paths,
            'pronunciation_audio_path' => $submission->pronunciation_audio_path,
            'ai_status'                => $submission->ai_status,
            'ai_feedback'              => $submission->ai_feedback,
            'created_at'               => $submission->created_at,
            'updated_at'               => $submission->updated_at,
            
            'scores' => [
                'reading'       => $submission->reading_score,
                'writing'       => $submission->writing_score,
                'speaking'      => $submission->speaking_score,
                'listening'     => $submission->listening_score,
                'vocabulary'    => $submission->vocabulary_score,
                'grammar'       => $submission->grammar_score,
                'pronunciation' => $submission->pronunciation_score,
                'overall'       => $overallScore,
            ],

            'cefr_bands' => [
                'reading'       => $mapScore($submission->reading_score),
                'writing'       => $mapScore($submission->writing_score),
                'speaking'      => $mapScore($submission->speaking_score),
                'listening'     => $mapScore($submission->listening_score),
                'vocabulary'    => $mapScore($submission->vocabulary_score),
                'grammar'       => $mapScore($submission->grammar_score),
                'pronunciation' => $mapScore($submission->pronunciation_score),
                'overall'       => $mapScore($overallScore),
            ],
        ]);
    }
}
