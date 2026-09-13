<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $tableName }}
            </h2>
            <a href="{{ route('imports.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Daftar File</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">#</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">File</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">Sheet</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-500">Baris</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-500">Berhasil</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">Tanggal</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($imports as $index => $import)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-4 py-2 font-medium text-gray-900">{{ $import->file_name }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $import->sheet_name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-right text-gray-600">{{ number_format($import->total_rows) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-600">{{ number_format($import->imported_rows) }}</td>
                                        <td class="px-4 py-2">
                                            <x-status-badge :status="$import->status" />
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ $import->created_at->format('d M Y H:i') }}</td>
                                        <td class="px-4 py-2 text-right whitespace-nowrap">
                                            @if ($import->status === 'success')
                                                <a href="{{ route('imports.data', $import) }}" class="text-green-600 hover:text-green-800 text-sm font-medium">Data</a>
                                                <span class="text-gray-300 mx-1">|</span>
                                            @elseif ($import->status === 'appended')
                                                <a href="{{ route('imports.data', $import) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Data</a>
                                                <span class="text-gray-300 mx-1">|</span>
                                            @endif
                                            @if ($import->reports_count > 0)
                                                <span class="text-gray-400 text-sm cursor-not-allowed" title="Digunakan oleh {{ $import->reports_count }} laporan">Hapus</span>
                                            @else
                                                <span class="text-red-600 hover:text-red-800 text-sm font-medium cursor-pointer"
                                                      x-data=""
                                                      x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-import-{{ $import->id }}')">
                                                    Hapus
                                                </span>
                                                <x-modal name="confirm-delete-import-{{ $import->id }}" :show="false" focusable>
                                                    <form method="POST" action="{{ route('imports.destroy', $import) }}" class="p-6">
                                                        @csrf
                                                        @method('DELETE')
                                                        <h2 class="text-lg font-medium text-gray-900">Hapus file ini?</h2>
                                                        <p class="mt-1 text-sm text-gray-600">File "{{ $import->file_name }}" beserta {{ number_format($import->import_data_count) }} record-nya akan dihapus permanen.</p>
                                                        <div class="mt-6 flex justify-end gap-3">
                                                            <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                                            <x-danger-button>Hapus</x-danger-button>
                                                        </div>
                                                    </form>
                                                </x-modal>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada file ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($imports->isNotEmpty())
                        <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div class="bg-gray-50 rounded-md px-4 py-3">
                                <div class="text-gray-500">Total File</div>
                                <div class="font-semibold text-gray-900">{{ $imports->count() }}</div>
                            </div>
                            <div class="bg-gray-50 rounded-md px-4 py-3">
                                <div class="text-gray-500">Total Baris</div>
                                <div class="font-semibold text-gray-900">{{ number_format($imports->sum('total_rows')) }}</div>
                            </div>
                            <div class="bg-green-50 rounded-md px-4 py-3">
                                <div class="text-green-600">Berhasil</div>
                                <div class="font-semibold text-green-700">{{ number_format($imports->sum('imported_rows')) }}</div>
                            </div>
                            <div class="bg-red-50 rounded-md px-4 py-3">
                                <div class="text-red-600">Gagal</div>
                                <div class="font-semibold text-red-700">{{ number_format($imports->sum('failed_rows')) }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
