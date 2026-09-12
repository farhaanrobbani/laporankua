<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cetak NB') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($imports->isEmpty())
                        <x-empty-state title="Belum ada data import" message="Upload file Excel untuk mulai mengolah data." :action-url="route('imports.create')" action-label="Upload Excel" />
                    @else
                        <form method="GET" action="{{ route('cetak-nb.index') }}" class="mb-6 flex flex-col sm:flex-row gap-3 sm:items-center">
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
                            <livewire:cetak-nb-table :import-id="$selectedImport->id" :key="'cetak-nb-table-'.$selectedImport->id" />
                        @else
                            <x-empty-state title="Pilih file import untuk melihat datanya" />
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
