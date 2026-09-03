import { Head, useForm } from '@inertiajs/react';
import axios from 'axios';
import { FormEvent, useRef, useState } from 'react';

export default function RecorderIndex() {
    const [recording, setRecording] = useState<boolean>(false);
    const [statusText, setStatusText] = useState<string>('Idle');
    const mediaRecorderRef = useRef<MediaRecorder | null>(null);
    const audioChunksRef = useRef<Blob[]>([]);
    
    const sessionIdRef = useRef<string>(Math.random().toString(36).substring(2));

    const { data, setData, post, processing } = useForm({
        title: '',
        session_id: sessionIdRef.current,
    });

    const startRecording = async (): Promise<void> => {
        audioChunksRef.current = [];
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorderRef.current = new MediaRecorder(stream, { mimeType: 'audio/webm' });

        mediaRecorderRef.current.ondataavailable = async (event: BlobEvent) => {
            if (event.data.size > 0) {
                audioChunksRef.current.push(event.data);
                const chunkBlob = new Blob([event.data], { type: 'audio/webm' });
                const formData = new FormData();
                formData.append('session_id', sessionIdRef.current);
                formData.append('chunk', chunkBlob, 'recording-chunk.webm');

                await axios.post('/recorder/chunk', formData);
            }
        };

        mediaRecorderRef.current.start(5000);
        setRecording(true);
        setStatusText('Recording...');
    };

    const stopRecording = (): void => {
        if (mediaRecorderRef.current) {
            mediaRecorderRef.current.stop();
            setRecording(false);
            setStatusText('Processing recording...');
        }
    };

    const submitTranscription = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        post(route('recorder.finalize'), {
            onSuccess: () => {
                setStatusText('Transcription queued successfully!');
            },
        });
    };

    return (
        <div className="py-12 max-w-4xl mx-auto sm:px-6 lg:px-8">
            <Head title="Audio Recorder" />
            
            <h1 className="text-2xl font-semibold mb-6">Record Audio Note</h1>

            <div className="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                <div>
                    <p className="text-sm text-gray-600 mb-2">
                        Status: <span className="font-medium text-indigo-600">{statusText}</span>
                    </p>
                    
                    <div className="flex gap-4">
                        {!recording ? (
                            <button
                                type="button"
                                onClick={startRecording}
                                className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                            >
                                Start Recording
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={stopRecording}
                                className="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700"
                            >
                                Stop Recording
                            </button>
                        )}
                    </div>
                </div>

                <form onSubmit={submitTranscription} className="space-y-4 border-t pt-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Transcription Title</label>
                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="My Audio Note"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing || recording}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Finalize & Transcribe
                    </button>
                </form>
            </div>
        </div>
    );
}