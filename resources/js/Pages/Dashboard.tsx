import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Dashboard({ transcriptions = [] }) {
    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this transcription record?')) {
            router.delete(route('documents.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Document Hub
                    </h2>
                    <Link
                        href={route('recorder.index')}
                        className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg shadow transition"
                    >
                        + New Recording
                    </Link>
                </div>
            }
        >
            <Head title="Document Hub" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                        {transcriptions.length === 0 ? (
                            <div className="text-center py-12">
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    No transcriptions recorded yet.
                                </p>
                                <Link
                                    href={route('recorder.index')}
                                    className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 transition"
                                >
                                    Start your first dictation
                                </Link>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b dark:border-gray-700 text-gray-400 text-sm">
                                            <th className="pb-3 font-semibold">Title</th>
                                            <th className="pb-3 font-semibold">Status</th>
                                            <th className="pb-3 font-semibold">Created</th>
                                            <th className="pb-3 font-semibold text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y dark:divide-gray-700">
                                        {transcriptions.map((item) => (
                                            <tr key={item.id} className="text-gray-200">
                                                <td className="py-4 font-medium">{item.title}</td>
                                                <td className="py-4">
                                                    <span className={`px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                        item.status === 'completed' ? 'bg-green-500/10 text-green-400' :
                                                        item.status === 'processing' ? 'bg-blue-500/10 text-blue-400' :
                                                        item.status === 'failed' ? 'bg-red-500/10 text-red-400' :
                                                        'bg-yellow-500/10 text-yellow-400'
                                                    }`}>
                                                        {item.status.toUpperCase()}
                                                    </span>
                                                </td>
                                                <td className="py-4 text-sm text-gray-400">
                                                    {new Date(item.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="py-4 text-right space-x-3">
                                                    {item.status === 'completed' && (
                                                        <>
                                                            <Link
                                                                href={route('documents.show', item.id)}
                                                                className="text-indigo-400 hover:text-indigo-300 font-medium text-sm"
                                                            >
                                                                View Embed
                                                            </Link>
                                                            {item.drive_download_link && (
                                                                <a
                                                                    href={item.drive_download_link}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="text-gray-400 hover:text-gray-200 font-medium text-sm"
                                                                >
                                                                    Download
                                                                </a>
                                                            )}
                                                        </>
                                                    )}
                                                    <button
                                                        onClick={() => handleDelete(item.id)}
                                                        className="text-red-400 hover:text-red-300 font-medium text-sm"
                                                    >
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}