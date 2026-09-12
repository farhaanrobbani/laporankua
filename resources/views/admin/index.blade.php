<x-admin.layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-sm font-medium text-gray-500">Total Users</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['users'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-sm font-medium text-gray-500">Total Import</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['imports'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-sm font-medium text-gray-500">Total Laporan</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['reports'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-sm font-medium text-gray-500">Total Records</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['records']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Import Terbaru</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($recentImports as $import)
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $import->filename }}</p>
                            <p class="text-xs text-gray-500">{{ $import->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $import->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $import->status }}
                        </span>
                    </div>
                @empty
                    <p class="px-6 py-4 text-sm text-gray-500 text-center">Belum ada import</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Laporan Terbaru</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($recentReports as $report)
                    <div class="px-6 py-3">
                        <p class="text-sm font-medium text-gray-900">{{ $report->title }}</p>
                        <p class="text-xs text-gray-500">{{ $report->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="px-6 py-4 text-sm text-gray-500 text-center">Belum ada laporan</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin.layout>
