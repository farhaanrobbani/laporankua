<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Data Import') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                        <form method="GET" action="{{ route('data.index') }}" class="mb-6 flex flex-col sm:flex-row gap-3 sm:items-center">
                            <label class="text-sm text-gray-700">
                                Sumber data:
                                <select name="import_id" onchange="this.form.submit()" class="ml-2 border-gray-300 rounded-md text-sm">
                                    <option value="">-- Pilih file import --</option>
                                    @foreach ($imports as $import)
                                        <option value="{{ $import->id }}" @selected($selectedImport?->id === $import->id)>
                                            {{ $import->file_name }} ({{ number_format($import->import_data_count) }} baris)
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </form>

                        @if ($selectedImport)
                            <livewire:data-table :import-id="$selectedImport->id" :key="'data-table-'.$selectedImport->id" />
                        @else
                            <div class="text-center py-8">
                                <p class="text-gray-500 font-medium">Pilih file import untuk melihat datanya</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
