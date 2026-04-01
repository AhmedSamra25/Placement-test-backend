<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaUploadController extends Controller
{
    /**
     * POST /api/test/upload-media
     * 
     * Handles file uploads for audio recordings (WebM, etc.) and images.
     * Uses multipart/form-data. Stores files permanently on public disk (can map to S3).
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file'       => ['required', 'file', 'max:51200'], // 50MB max constraint
            'type'       => ['required', 'string', 'in:audio,image'],
            'section_id' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        
        // Isolate in organized storage directories
        $dir = $request->input('type') === 'audio' ? 'test_media/audio' : 'test_media/images';
        
        // Store on the configured default disk (local public, or S3 if configured via .env)
        // This generates a random hash name implicitly
        $path = $file->store($dir, 'public');

        return response()->json([
            'message' => 'File uploaded successfully',
            'path'    => $path,
            'url'     => asset('storage/' . $path),
        ]);
    }
}
