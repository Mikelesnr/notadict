<?php

namespace App\Http\Controllers;

use App\Models\Transcription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentHubController extends Controller
{
    /**
     * Display a listing of all transcriptions for the authenticated user.
     */
    public function index(Request $request): Response
    {
        $transcriptions = $request->user()
            ->transcriptions()
            ->latest()
            ->get(['id', 'title', 'status', 'drive_file_id', 'drive_web_view_link', 'drive_download_link', 'created_at']);

        return Inertia::render('Dashboard', [
            'transcriptions' => $transcriptions,
        ]);
    }

    /**
     * Display the Classgap-style preview for a specific transcription.
     */
    public function show(Request $request, Transcription $transcription): Response
    {
        if ($transcription->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to document.');
        }

        return Inertia::render('Documents/Show', [
            'transcription' => $transcription,
            'embedUrl' => "https://drive.google.com/file/d/{$transcription->drive_file_id}/preview",
        ]);
    }

    /**
     * Remove a transcription record from the database.
     */
    public function destroy(Request $request, Transcription $transcription)
    {
        if ($transcription->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $transcription->delete();

        return redirect()->route('documents.index')->with('status', 'Document record deleted.');
    }
}
