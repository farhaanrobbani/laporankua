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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($imports->isEmpty())
                        <x-empty-state title="Belum ada data import" message="Upload file Excel untuk mulai mengolah data." :action-url="route('imports.create')" action-label="Upload Excel" />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Tabel</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">File</th>
                                        <th class="px-4 py-2 text-right font-medium text-gray-500">Berhasil</th>
                                        <th class="px-4 py-2 text-right font-medium text-gray-500">Gagal</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Tanggal</th>
                                        <th class="px-4 py-2 text-right font-medium text-gray-500">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($imports as $group)
                                        <tr>
                                            <td class="px-4 py-2 font-medium text-gray-900">{{ $group->table_name ?? '-' }}</td>
                                            <td class="px-4 py-2 text-gray-600">
                                                @if ($group->file_count === 1)
                                                    {{ $group->file_names_list[0] }}
                                                @else
                                                    <ul class="list-disc list-inside space-y-0.5">
                                                        @foreach ($group->file_names_list as $fname)
                                                            <li>{{ $fname }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-right text-gray-600">{{ number_format($group->imported_rows) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-600">{{ number_format($group->failed_rows) }}</td>
                                            <td class="px-4 py-2">
                                                @foreach ($group->status_summary as $status => $count)
                                                    <x-status-badge :status="$status" /> <span class="text-xs text-gray-500">x{{ $count }}</span>
                                                    @if (! $loop->last)
                                                        <br>
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td class="px-4 py-2 text-gray-600">{{ $group->latest_created_at->format('d M Y H:i') }}</td>
                                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                                <a href="{{ route('data.index', ['import_id' => $group->first_id]) }}" class="text-green-600 hover:text-green-800 text-sm font-medium">Data</a>
                                                <span class="text-gray-300 mx-1">|</span>
                                                <a href="{{ route('imports.show', $group->latest_id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
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
