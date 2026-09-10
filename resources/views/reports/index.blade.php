<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Laporan') }}
            </h2>
            <a href="{{ route('reports.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                Buat Laporan
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-green-100 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($reports->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500 font-medium">Belum ada laporan</p>
                            <p class="text-gray-400 text-sm mt-1">Buat laporan pertama dari data Anda.</p>
                            <a href="{{ route('reports.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                                Buat Laporan
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($reports as $report)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate">{{ $report->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                @if ($report->output_format === 'pdf')
                                                    <span class="font-bold text-red-600">PDF</span>
                                                @elseif ($report->output_format === 'word')
                                                    <span class="font-bold text-blue-600">Word</span>
                                                @elseif ($report->output_format === 'excel')
                                                    <span class="font-bold text-green-600">Excel</span>
                                                @else
                                                    <span class="font-bold text-gray-600">Print</span>
                                                @endif
                                                &middot; {{ $report->import?->file_name ?? '-' }}
                                                &middot; {{ $report->created_at->format('d M Y H:i') }}
                                            </p>
                                        </div>
                                        @if (in_array($report->status, ['pending', 'processing']))
                                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 shrink-0">{{ ucfirst($report->status) }}</span>
                                        @elseif ($report->status === 'failed')
                                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800 shrink-0">Failed</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 shrink-0">Selesai</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 flex gap-3 text-sm">
                                        <a href="{{ route('reports.show', $report) }}" class="text-blue-600 hover:text-blue-800 font-medium">Detail</a>
                                        @if ($report->file_path)
                                            <a href="{{ route('reports.download', $report) }}" class="text-green-600 hover:text-green-800 font-medium">Download</a>
                                        @endif
                                        @if ($report->output_format === 'print')
                                            <a href="{{ route('reports.print', $report) }}" target="_blank" class="text-gray-600 hover:text-gray-800 font-medium">Print</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">{{ $reports->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
