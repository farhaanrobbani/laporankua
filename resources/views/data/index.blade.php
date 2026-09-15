<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Data') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex gap-1 border-b border-gray-200 mb-6">
                        <a href="{{ route('data.index') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.index') && !request()->routeIs('data.pelaksanaan-kantor') && !request()->routeIs('data.pelaksanaan-luar-kantor') ? 'border-indigo-400 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Data Import') }}
                        </a>
                        <a href="{{ route('data.pelaksanaan-kantor') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.pelaksanaan-kantor') ? 'border-indigo-400 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Pelaksanaan Kantor') }}
                        </a>
                        <a href="{{ route('data.pelaksanaan-luar-kantor') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.pelaksanaan-luar-kantor') ? 'border-indigo-400 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Pelaksanaan Luar Kantor') }}
                        </a>
                    </div>

                    @if ($grouped->isEmpty())
                        <x-empty-state title="Belum ada data import" message="Upload file Excel untuk mulai mengolah data." :action-url="route('imports.create')" action-label="Upload Excel" />
                    @else
                        <form method="GET" action="{{ route('data.index') }}" class="mb-6 flex flex-col sm:flex-row gap-3 sm:items-center">
                            <label class="text-sm text-gray-700">
                                Sumber data:
                                <select name="import_id" onchange="this.form.submit()" class="ml-2 border-gray-300 rounded-md text-sm">
                                    <option value="">-- Pilih file import --</option>
                                    @foreach ($grouped as $group)
                                        <option value="{{ $group['import_ids'][0] }}" @selected($selectedGroup && $selectedGroup['table_name'] === $group['table_name'])>
                                            {{ $group['table_name'] }} ({{ number_format($group['total_rows']) }} baris)
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </form>

                        @if ($selectedGroup)
                            <livewire:data-table :import-ids="$selectedGroup['import_ids']" :key="'data-table-'.$selectedGroup['table_name']" />
                        @else
                            <x-empty-state title="Pilih file import untuk melihat datanya" />
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
