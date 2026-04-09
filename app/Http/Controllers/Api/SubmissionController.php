<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CefrLevel;
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

        // 2. Fetch the CEFR mapping rules for the org (ordered for correct range lookup)
        $cefrLevels = $user->organization->cefrLevels()->orderBy('score_min')->get();

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
                'reading'       => CefrLevel::resolveFromScore($submission->reading_score, $cefrLevels),
                'writing'       => CefrLevel::resolveFromScore($submission->writing_score, $cefrLevels),
                'speaking'      => CefrLevel::resolveFromScore($submission->speaking_score, $cefrLevels),
                'listening'     => CefrLevel::resolveFromScore($submission->listening_score, $cefrLevels),
                'vocabulary'    => CefrLevel::resolveFromScore($submission->vocabulary_score, $cefrLevels),
                'grammar'       => CefrLevel::resolveFromScore($submission->grammar_score, $cefrLevels),
                'pronunciation' => CefrLevel::resolveFromScore($submission->pronunciation_score, $cefrLevels),
                'overall'       => CefrLevel::resolveFromScore($overallScore, $cefrLevels),
            ],
        ]);
    }
}
