<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4">
                        <a href="{{ route('document.upload') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Upload New Document
                        </a>
                    </div>

                    <h3 class="text-lg font-semibold mb-3">Your Documents</h3>
                    @if($documents->count())
                        <ul class="space-y-3">
                            @foreach($documents as $document)
                                <li class="p-3 border rounded-md hover:bg-gray-50 flex justify-between items-center">
                                    <div>
                                        <a href="{{ route('document.editor', $document) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ $document->title ?: $document->original_filename }}
                                        </a>
                                        <p class="text-sm text-gray-500">Status: {{ Str::title($document->status) }} | Pages: {{ $document->pages()->count() }}</p>
                                        <p class="text-xs text-gray-400">Uploaded: {{ $document->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                    <div>
                                        <a href="{{ route('document.editor', $document) }}" class="text-sm text-indigo-500 hover:text-indigo-700">Edit</a>
                                        </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p>You haven't uploaded any documents yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>