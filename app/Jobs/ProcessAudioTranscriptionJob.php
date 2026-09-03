<?php

namespace App\Jobs;

use App\Models\Transcription;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDriveService;
use Google\Service\Drive\DriveFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class ProcessAudioTranscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes max execution time

    public function __construct(
        public Transcription $transcription,
        public string $sessionId
    ) {}

    public function handle(): void
    {
        $this->transcription->update(['status' => 'processing']);

        $chunkDir = storage_path("app/tmp/chunks/{$this->sessionId}");
        $workDir = storage_path("app/tmp/processing/{$this->sessionId}");
        File::makeDirectory($workDir, 0755, true);

        $concatList = "{$workDir}/list.txt";
        $normalizedWav = "{$workDir}/normalized.wav";
        $transcriptPrefix = "{$workDir}/transcript";
        $transcriptTxt = "{$transcriptPrefix}.txt";
        $docxPath = "{$workDir}/{$this->transcription->title}.docx";

        try {
            // 1. Build FFmpeg concat list
            $chunks = File::glob("{$chunkDir}/*.webm");
            sort($chunks);

            $fileListContent = array_map(fn($file) => "file '{$file}'", $chunks);
            File::put($concatList, implode("\n", $fileListContent));

            // 2. Concatenate & normalize audio to 16kHz mono WAV via FFmpeg
            Process::mustRun("ffmpeg -f concat -safe 0 -i {$concatList} -ar 16000 -ac 1 -c:a pcm_s16le {$normalizedWav}");

            // 3. Execute whisper.cpp binary
            $whisperBinary = config('services.whisper.binary_path', base_path('whisper-cli'));
            $whisperModel = config('services.whisper.model_path', base_path('models/ggml-small.en.bin'));

            Process::mustRun("{$whisperBinary} -m {$whisperModel} -f {$normalizedWav} -otxt -of {$transcriptPrefix}");

            $rawText = File::get($transcriptTxt);

            // 4. Generate .docx document using PhpWord
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addTitle($this->transcription->title, 1);
            $section->addTextBreak();
            $section->addText($rawText);

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($docxPath);

            // 5. Upload document to Google Drive
            $user = $this->transcription->user;

            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->refreshToken(decrypt($user->google_refresh_token));

            $driveService = new GoogleDriveService($client);

            $fileMetadata = new DriveFile([
                'name' => "{$this->transcription->title}.docx",
                'mimeType' => 'application/vnd.google-apps.document', // Automatically converts to Google Doc
            ]);

            $content = File::get($docxPath);
            $driveFile = $driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink, webContentLink',
            ]);

            // 6. Update database status
            $this->transcription->update([
                'status' => 'completed',
                'drive_file_id' => $driveFile->id,
                'drive_web_view_link' => $driveFile->webViewLink,
                'drive_download_link' => $driveFile->webContentLink,
            ]);
        } catch (\Throwable $e) {
            $this->transcription->update(['status' => 'failed']);
            throw $e;
        } finally {
            // Clean up temporary local directories
            File::deleteDirectory($chunkDir);
            File::deleteDirectory($workDir);
        }
    }
}
