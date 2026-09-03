import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Notadict - Voice Dictation to Google Drive" />
            <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-between">
                <header className="max-w-7xl w-full mx-auto px-6 py-6 flex justify-between items-center">
                    <div className="flex items-center space-x-2">
                        <span className="text-2xl font-bold bg-gradient-to-r from-blue-400 to-indigo-500 bg-clip-text text-transparent">
                            Notadict
                        </span>
                    </div>
                    <nav className="space-x-4">
                        {auth.user ? (
                            <Link
                                href={route('documents.index')}
                                className="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-medium text-white transition"
                            >
                                Open App
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="px-4 py-2 text-slate-300 hover:text-white font-medium transition"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-medium text-white transition"
                                >
                                    Get Started
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                <main className="max-w-4xl mx-auto px-6 text-center py-20">
                    <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight mb-6">
                        Dictate naturally. <br />
                        <span className="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">
                            Sync straight to Google Drive.
                        </span>
                    </h1>
                    <p className="text-lg sm:text-xl text-slate-400 mb-10 max-w-2xl mx-auto">
                        Record voice notes directly in your browser. Transcribe local audio instantly with whisper.cpp and output neatly formatted Word documents to your Google Drive.
                    </p>
                    <div className="flex justify-center space-x-4">
                        <Link
                            href={auth.user ? route('documents.index') : route('register')}
                            className="px-8 py-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-lg text-white shadow-lg transition"
                        >
                            Start Dictating
                        </Link>
                    </div>
                </main>

                <footer className="py-8 text-center text-slate-500 text-sm border-t border-slate-800">
                    Notadict &copy; {new Date().getFullYear()} — Powered by Laravel, Inertia, & whisper.cpp
                </footer>
            </div>
        </>
    );
}