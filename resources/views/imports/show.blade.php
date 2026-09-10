<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $import->file_name }}
            </h2>
            <a href="{{ route('imports.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Import</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500">File</dt><dd class="font-medium text-gray-900">{{ $import->file_name }}</dd></div>
                        <div><dt class="text-gray-500">Sheet</dt><dd class="font-medium text-gray-900">{{ $import->sheet_name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Ukuran</dt><dd class="font-medium text-gray-900">{{ number_format($import->file_size / 1024, 1) }} KB</dd></div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd class="font-medium">
                                <x-status-badge :status="$import->status" />
                            </dd>
                        </div>
                        <div><dt class="text-gray-500">Total baris</dt><dd class="font-medium text-gray-900">{{ number_format($import->total_rows) }}</dd></div>
                        <div><dt class="text-gray-500">Berhasil / Gagal</dt><dd class="font-medium text-gray-900">{{ number_format($import->imported_rows) }} / {{ number_format($import->failed_rows) }}</dd></div>
                        <div><dt class="text-gray-500">Data tersimpan</dt><dd class="font-medium text-gray-900">{{ number_format($import->import_data_count) }} record</dd></div>
                        <div><dt class="text-gray-500">Diimport pada</dt><dd class="font-medium text-gray-900">{{ $import->imported_at?->format('d M Y H:i') ?? '-' }}</dd></div>
                    </dl>

                    @if (in_array($import->status, ['pending', 'processing']))
                        <p class="mt-4 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-3">Import sedang diproses di background. Muat ulang halaman ini untuk melihat status terbaru.</p>
                    @endif

                    <div class="mt-6 flex gap-3">
                        @if ($import->status === 'success')
                            <a href="{{ route('data.index', ['import_id' => $import->id]) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">Lihat Data</a>
                        @endif
                        <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-import')">Hapus</x-danger-button>

                        <x-modal name="confirm-delete-import" :show="false" focusable>
                            <form method="POST" action="{{ route('imports.destroy', $import) }}" class="p-6">
                                @csrf
                                @method('DELETE')
                                <h2 class="text-lg font-medium text-gray-900">Hapus data import?</h2>
                                <p class="mt-1 text-sm text-gray-600">File "{{ $import->file_name }}" beserta seluruh record-nya akan dihapus permanen.</p>
                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                    <x-danger-button>Hapus</x-danger-button>
                                </div>
                            </form>
                        </x-modal>
                    </div>
                </div>
            </div>

            @if (! empty($import->error_log))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Log Error ({{ count($import->error_log) }} ditampilkan)</h3>
                        <ul class="text-sm text-red-700 space-y-1">
                            @foreach ($import->error_log as $error)
                                <li>Baris {{ $error['row'] ?? '-' }}: {{ $error['reason'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
