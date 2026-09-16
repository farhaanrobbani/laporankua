<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $import->file_name }}
            </h2>
            <a href="{{ route('imports.show', $import) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">&larr; Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-6">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-md px-4 py-3">
                            <div class="text-gray-500 dark:text-gray-400">Sheet</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $import->sheet_name ?? '-' }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-md px-4 py-3">
                            <div class="text-gray-500 dark:text-gray-400">Total Baris</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($import->total_rows) }}</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/50 rounded-md px-4 py-3">
                            <div class="text-green-600 dark:text-green-400">Berhasil</div>
                            <div class="font-semibold text-green-700 dark:text-green-300">{{ number_format($import->imported_rows) }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-md px-4 py-3">
                            <div class="text-gray-500 dark:text-gray-400">Status</div>
                            <div class="font-semibold"><x-status-badge :status="$import->status" /></div>
                        </div>
                    </div>

                    <livewire:data-table :import-id="$import->id" :key="'file-data-'.$import->id" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
