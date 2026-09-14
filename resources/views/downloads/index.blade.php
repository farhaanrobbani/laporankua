<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Download') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($downloads->isEmpty())
                <x-empty-state title="Belum ada file" message="Admin belum mengunggah file untuk diunduh." />
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($downloads as $dl)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 flex flex-col">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center {{ $dl->isFile() ? 'bg-blue-50' : 'bg-purple-50' }}">
                                    @if ($dl->isFile())
                                        <x-heroicon-o-document-arrow-down class="w-5 h-5 text-blue-600" />
                                    @else
                                        <x-heroicon-o-link class="w-5 h-5 text-purple-600" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $dl->title }}</h3>
                                    <p class="text-xs text-gray-400">
                                        @if ($dl->isFile())
                                            {{ strtoupper(pathinfo($dl->file_path, PATHINFO_EXTENSION)) }}
                                            @if ($dl->file_size) &middot; {{ number_format($dl->file_size / 1024, 1) }} KB @endif
                                        @else
                                            Link Eksternal
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if ($dl->description)
                                <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $dl->description }}</p>
                            @endif

                            <div class="mt-auto pt-3 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-xs text-gray-400">{{ $dl->created_at->diffForHumans() }}</span>
                                @if ($dl->isFile())
                                    <a href="{{ route('downloads.download', $dl) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700 transition">
                                        <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                        Download
                                    </a>
                                @else
                                    <a href="{{ route('downloads.download', $dl) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-md hover:bg-purple-700 transition">
                                        <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                                        Buka Link
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
