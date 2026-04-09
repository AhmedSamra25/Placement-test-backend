<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaUploadController extends Controller
{
    /**
     * POST /api/test/upload-media
     *
     * Handles file uploads for audio recordings (WebM, etc.) and images.
     * After storing, the file path is persisted into the student's TestSubmission
     * so the AI scoring job has access to it.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file'       => ['required', 'file', 'max:51200'], // 50MB max
            'type'       => ['required', 'string', 'in:audio,image'],
            'section_id' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');

        // Isolate in organized storage directories
        $dir  = $request->input('type') === 'audio' ? 'test_media/audio' : 'test_media/images';
        $path = $file->store($dir, 'public');

        // ── Persist the path into the student's submission record ──────────────
        /** @var Student $student */
        $student    = $request->user();
        $submission = $student->testSubmission;

        if ($submission) {
            $sectionId = $request->input('section_id', '');

            if ($request->input('type') === 'audio') {
                if (str_contains($sectionId, 'pronunciation')) {
                    // Single pronunciation recording overwrites the previous one
                    $submission->update(['pronunciation_audio_path' => $path]);
                } else {
                    // Speaking: append to the JSON array of speaking paths
                    $paths   = $submission->speaking_audio_paths ?? [];
                    $paths[] = $path;
                    $submission->update(['speaking_audio_paths' => $paths]);
                }
            }
        }
        // ────────────────────────────────────────────────────────────────────────

        return response()->json([
            'message' => 'File uploaded successfully',
            'path'    => $path,
            'url'     => asset('storage/' . $path),
        ]);
    }
}
