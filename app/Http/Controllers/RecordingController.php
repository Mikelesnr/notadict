<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAudioTranscriptionJob;
use App\Models\Transcription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class RecordingController extends Controller
{
    /**
     * Render the audio recording view.
     */
    public function create(): Response
    {
        return Inertia::render('Recorder/Index');
    }

    /**
     * Store incoming audio chunks in temporary storage.
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'chunk' => 'required|file|mimes:webm,wav,mp4,ogg,mp3',
        ]);

        $sessionId = $request->input('session_id');
        $directory = storage_path("app/tmp/chunks/{$sessionId}");

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $chunkIndex = count(File::files($directory)) + 1;
        $request->file('chunk')->move($directory, sprintf('%04d.webm', $chunkIndex));

        return response()->json([
            'status' => 'chunk_received',
            'chunk_index' => $chunkIndex
        ]);
    }

    /**
     * Finalize the recording session and queue whisper.cpp processing.
     */
    public function finalize(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'title' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $sessionId = $request->input('session_id');

        $transcription = Transcription::create([
            'user_id' => $user->id,
            'title' => $request->input('title'),
            'status' => 'pending',
        ]);

        ProcessAudioTranscriptionJob::dispatch($transcription, $sessionId);

        return response()->json([
            'message' => 'Transcription queued successfully',
            'transcription_id' => $transcription->id,
        ]);
    }
}
