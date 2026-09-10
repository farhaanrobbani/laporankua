<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Quick upload CTA --}}
            <div class="bg-blue-600 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold">Olah Data Excel Menjadi Laporan</h3>
                        <p class="text-blue-100 text-sm">Upload file .xlsx atau .xls, data otomatis tersimpan sebagai database.</p>
                    </div>
                    <a href="#" class="inline-flex items-center px-4 py-2 bg-white text-blue-700 font-semibold text-sm rounded-md hover:bg-blue-50">
                        Upload Excel
                    </a>
                </div>
            </div>

            {{-- Statistics cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm text-gray-500">Total File Import</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($totalImports) }}</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm text-gray-500">Total Data Record</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($totalRecords) }}</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm text-gray-500">Laporan Dibuat</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($totalReports) }}</p>
                    </div>
                </div>
            </div>

            {{-- Recent imports --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Import Terakhir</h3>
                    @if ($recentImports->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500 font-medium">Belum ada data import</p>
                            <p class="text-gray-400 text-sm mt-1">Upload file Excel untuk mulai mengolah data.</p>
                            <a href="#" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
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
                                        <th class="px-4 py-2 text-right font-medium text-gray-500">Baris</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($recentImports as $import)
                                        <tr>
                                            <td class="px-4 py-2 font-medium text-gray-900">{{ $import->file_name }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ $import->sheet_name ?? '-' }}</td>
                                            <td class="px-4 py-2 text-right text-gray-600">{{ number_format($import->import_data_count) }}</td>
                                            <td class="px-4 py-2">
                                                @if ($import->status === 'success')
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                                                @elseif ($import->status === 'processing' || $import->status === 'pending')
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ ucfirst($import->status) }}</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-gray-600">{{ $import->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent reports + quick actions --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Laporan Terbaru</h3>
                        @if ($recentReports->isEmpty())
                            <div class="text-center py-8">
                                <p class="text-gray-500 font-medium">Belum ada laporan</p>
                                <p class="text-gray-400 text-sm mt-1">Buat laporan pertama dari data Anda.</p>
                                <a href="#" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                                    Buat Laporan
                                </a>
                            </div>
                        @else
                            <ul class="divide-y divide-gray-200">
                                @foreach ($recentReports as $report)
                                    <li class="py-3 flex items-center justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $report->title }}</p>
                                            <p class="text-xs text-gray-500">{{ strtoupper($report->output_format) }} &middot; {{ $report->created_at->format('d M Y') }}</p>
                                        </div>
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 shrink-0">{{ ucfirst($report->status) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <a href="#" class="block p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50">
                                <p class="font-semibold text-gray-900 text-sm">Upload Excel</p>
                                <p class="text-xs text-gray-500 mt-1">Import file .xlsx / .xls</p>
                            </a>
                            <a href="#" class="block p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50">
                                <p class="font-semibold text-gray-900 text-sm">Buat Laporan</p>
                                <p class="text-xs text-gray-500 mt-1">PDF, Word, Excel, Print</p>
                            </a>
                            <a href="#" class="block p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50">
                                <p class="font-semibold text-gray-900 text-sm">Template</p>
                                <p class="text-xs text-gray-500 mt-1">Kelola template laporan</p>
                            </a>
                            <a href="{{ route('profile.edit') }}" class="block p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50">
                                <p class="font-semibold text-gray-900 text-sm">Profil</p>
                                <p class="text-xs text-gray-500 mt-1">Pengaturan akun</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
