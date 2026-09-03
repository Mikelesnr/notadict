import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ transcription, embedUrl }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {transcription.title}
                    </h2>
                    <Link
                        href={route('documents.index')}
                        className="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg transition"
                    >
                        Back to Hub
                    </Link>
                </div>
            }
        >
            <Head title={`View - ${transcription.title}`} />

            <div className="py-6">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 overflow-hidden">
                        {transcription.drive_file_id ? (
                            <iframe
                                src={embedUrl}
                                className="w-full h-[75vh] border-0 rounded-lg"
                                allow="autoplay"
                                title={transcription.title}
                            ></iframe>
                        ) : (
                            <div className="p-12 text-center text-gray-400">
                                Document file preview is not available for this record.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}