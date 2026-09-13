<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $import->file_name }}
            </h2>
            <a href="{{ route('imports.show', $import) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-6">
                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <div class="text-gray-500">Sheet</div>
                            <div class="font-semibold text-gray-900">{{ $import->sheet_name ?? '-' }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <div class="text-gray-500">Total Baris</div>
                            <div class="font-semibold text-gray-900">{{ number_format($import->total_rows) }}</div>
                        </div>
                        <div class="bg-green-50 rounded-md px-4 py-3">
                            <div class="text-green-600">Berhasil</div>
                            <div class="font-semibold text-green-700">{{ number_format($import->imported_rows) }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-md px-4 py-3">
                            <div class="text-gray-500">Status</div>
                            <div class="font-semibold"><x-status-badge :status="$import->status" /></div>
                        </div>
                    </div>

                    <livewire:data-table :import-id="$import->id" :key="'file-data-'.$import->id" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
