<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $report->title }}
            </h2>
            <a href="{{ route('reports.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Laporan</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500">Judul</dt><dd class="font-medium text-gray-900">{{ $report->title }}</dd></div>
                        <div><dt class="text-gray-500">Format</dt><dd class="font-medium text-gray-900">{{ strtoupper($report->output_format) }}</dd></div>
                        <div><dt class="text-gray-500">Sumber data</dt><dd class="font-medium text-gray-900">{{ $report->import?->file_name ?? '-' }}</dd></div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd class="font-medium">
                                @if (in_array($report->status, ['pending', 'processing']))
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ ucfirst($report->status) }}</span>
                                @elseif ($report->status === 'failed')
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ ucfirst($report->status) }}</span>
                                @endif
                            </dd>
                        </div>
                        <div><dt class="text-gray-500">Ukuran file</dt><dd class="font-medium text-gray-900">{{ $report->file_size ? number_format($report->file_size / 1024, 1).' KB' : '-' }}</dd></div>
                        <div><dt class="text-gray-500">Dibuat pada</dt><dd class="font-medium text-gray-900">{{ $report->generated_at?->format('d M Y H:i') ?? '-' }}</dd></div>
                    </dl>

                    @if (in_array($report->status, ['pending', 'processing']))
                        <p class="mt-4 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-3">Laporan sedang dibuat di background. Muat ulang halaman ini untuk status terbaru.</p>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if ($report->file_path)
                            <a href="{{ route('reports.download', $report) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700">Download</a>
                        @endif
                        @if ($report->output_format === 'print')
                            <a href="{{ route('reports.print', $report) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-semibold rounded-md hover:bg-gray-700">Buka Print</a>
                        @endif
                        <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Hapus laporan ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
