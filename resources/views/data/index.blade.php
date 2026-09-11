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
                        <x-empty-state title="Belum ada data import" message="Upload file Excel untuk mulai mengolah data." :action-url="route('imports.create')" action-label="Upload Excel" />
                    @else
                        @php
                            $mode = request('mode', 'single');
                            $mergeImports = request('merge_imports', []);
                            $joinColumn = request('join_column', '');
                            $mergeGroupId = request('merge_group_id');
                        @endphp

                        {{-- Mode Toggle --}}
                        <div class="mb-6">
                            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1 w-fit">
                                <a href="{{ route('data.index', ['mode' => 'single', 'import_id' => request('import_id')]) }}"
                                   class="px-4 py-2 text-sm font-medium rounded-md transition {{ $mode === 'single' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                                    Single Import
                                </a>
                                <a href="{{ route('data.index', ['mode' => 'merge']) }}"
                                   class="px-4 py-2 text-sm font-medium rounded-md transition {{ $mode === 'merge' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                                    Gabung Data
                                </a>
                            </div>
                        </div>

                        @if ($mode === 'single')
                            {{-- Single Import Mode --}}
                            <form method="GET" action="{{ route('data.index') }}" class="mb-6 flex flex-col sm:flex-row gap-3 sm:items-center">
                                <input type="hidden" name="mode" value="single" />
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
                                <x-empty-state title="Pilih file import untuk melihat datanya" />
                            @endif

                        @else
                            {{-- Merge Mode --}}
                            @if ($mergeGroupId)
                                {{-- Merge Group View --}}
                                @php
                                    $mergeGroup = \App\Models\MergeGroup::with('imports:id,file_name')->where('id', $mergeGroupId)->where('user_id', auth()->id())->first();
                                @endphp

                                @if ($mergeGroup)
                                    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-md px-4 py-3 flex items-center justify-between">
                                        <div class="text-sm text-blue-800">
                                            <span class="font-medium">{{ $mergeGroup->name }}</span>
                                            · JOIN kolom: <span class="font-mono">{{ $mergeGroup->join_column }}</span>
                                            · {{ $mergeGroup->imports_count }} file import
                                        </div>
                                        <a href="{{ route('data.index', ['mode' => 'merge']) }}" class="text-sm text-blue-600 hover:text-blue-800 underline">
                                            Kembali ke Grup
                                        </a>
                                    </div>
                                    <livewire:data-table
                                        :import-ids="$mergeGroup->imports->pluck('id')->toArray()"
                                        :join-column="$mergeGroup->join_column"
                                        :key="'data-table-merge-group-'.$mergeGroup->id"
                                    />
                                @else
                                    <x-empty-state title="Grup tidak ditemukan" />
                                @endif

                            @else
                                {{-- Merge Group List --}}
                                <livewire:⚡merge-groups />

                                {{-- Manual merge fallback --}}
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <h4 class="font-medium text-gray-700 mb-3">Atau Gabung Manual</h4>
                                    <form method="GET" action="{{ route('data.index') }}" class="space-y-4">
                                        <input type="hidden" name="mode" value="merge" />

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih file import (minimal 2)</label>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                                @foreach ($imports as $import)
                                                    <label class="flex items-center gap-2 text-sm text-gray-700 border border-gray-200 rounded-md px-3 py-2 cursor-pointer hover:border-blue-400">
                                                        <input type="checkbox" name="merge_imports[]" value="{{ $import->id }}"
                                                            @checked(in_array($import->id, $mergeImports))
                                                            class="rounded text-blue-600" />
                                                        {{ $import->file_name }} ({{ number_format($import->import_data_count) }} baris)
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        @if (count($mergeImports) >= 2)
                                            @php
                                                $sharedColumns = app(App\Services\MergeService::class)->getSharedColumns($mergeImports);
                                            @endphp
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Kolom penggabung (JOIN key)</label>
                                                <select name="join_column" onchange="this.form.submit()" class="border-gray-300 rounded-md text-sm w-full sm:w-auto">
                                                    <option value="">-- Pilih kolom --</option>
                                                    @foreach ($sharedColumns as $col)
                                                        <option value="{{ $col }}" @selected($joinColumn === $col)>{{ $col }}</option>
                                                    @endforeach
                                                </select>
                                                @if (empty($sharedColumns))
                                                    <p class="text-xs text-amber-600 mt-1">Tidak ada kolom yang sama antara file yang dipilih.</p>
                                                @endif
                                            </div>
                                        @endif

                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                                            Tampilkan Gabungan
                                        </button>
                                    </form>

                                    @if (count($mergeImports) >= 2 && $joinColumn)
                                        <div class="mt-4">
                                            <livewire:data-table
                                                :import-ids="$mergeImports"
                                                :join-column="$joinColumn"
                                                :key="'data-table-merge-'.implode('-', $mergeImports).'-'.$joinColumn"
                                            />
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
