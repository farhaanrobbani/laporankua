<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Riwayat Import') }}
            </h2>
            <a href="{{ route('imports.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                Upload Excel
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
                    @if ($imports->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500 font-medium">Belum ada data import</p>
                            <p class="text-gray-400 text-sm mt-1">Upload file Excel untuk mulai mengolah data.</p>
                            <a href="{{ route('imports.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                                Upload Excel
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">File</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Sheet</th>
                                        <th class="px-4 py-2 text-right font-medium text-gray-500">Berhasil</th>
                                        <th class="px-4 py-2 text-right font-medium text-gray-500">Gagal</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Tanggal</th>
                                        <th class="px-4 py-2 text-right font-medium text-gray-500">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($imports as $import)
                                        <tr>
                                            <td class="px-4 py-2 font-medium text-gray-900">{{ $import->file_name }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ $import->sheet_name ?? '-' }}</td>
                                            <td class="px-4 py-2 text-right text-gray-600">{{ number_format($import->imported_rows) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-600">{{ number_format($import->failed_rows) }}</td>
                                            <td class="px-4 py-2">
                                                @if ($import->status === 'success')
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                                                @elseif ($import->status === 'processing' || $import->status === 'pending')
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ ucfirst($import->status) }}</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-gray-600">{{ $import->created_at->format('d M Y H:i') }}</td>
                                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                                @if ($import->status === 'success')
                                                    <a href="{{ route('data.index', ['import_id' => $import->id]) }}" class="text-green-600 hover:text-green-800 text-sm font-medium">Data</a>
                                                    <span class="text-gray-300 mx-1">|</span>
                                                @endif
                                                <a href="{{ route('imports.show', $import) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $imports->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
