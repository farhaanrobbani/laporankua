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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($reports->isEmpty())
                        <x-empty-state title="Belum ada laporan" message="Buat laporan pertama dari data Anda." :action-url="route('reports.create')" action-label="Buat Laporan" />
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($reports as $report)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate">{{ $report->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <x-status-badge :status="$report->output_format" />
                                                &middot; {{ $report->import?->file_name ?? '-' }}
                                                &middot; {{ $report->created_at->format('d M Y H:i') }}
                                            </p>
                                        </div>
                                        <x-status-badge :status="$report->status" class="shrink-0" />
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
