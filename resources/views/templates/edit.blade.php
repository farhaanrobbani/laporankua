<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ubah Template') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('templates.update', $template) }}" class="p-6 space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama template</label>
                        <input type="text" name="name" value="{{ old('name', $template->name) }}" required class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi (opsional)</label>
                        <textarea name="description" rows="2" class="mt-1 border-gray-300 rounded-md text-sm w-full">{{ old('description', $template->description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Format output</label>
                            <select name="output_format" class="mt-1 border-gray-300 rounded-md text-sm w-full">
                                @foreach (['pdf' => 'PDF', 'word' => 'Word', 'excel' => 'Excel', 'print' => 'Print'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('output_format', $template->output_format) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Orientasi (PDF)</label>
                            <select name="orientation" class="mt-1 border-gray-300 rounded-md text-sm w-full">
                                <option value="portrait" @selected(old('orientation', $template->layout_json['orientation'] ?? 'portrait') === 'portrait')>Portrait</option>
                                <option value="landscape" @selected(old('orientation', $template->layout_json['orientation'] ?? 'portrait') === 'landscape')>Landscape</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Layout Tabel Khusus (JSON, opsional)</label>
                        <textarea name="table_layout" rows="6" class="mt-1 border-gray-300 rounded-md text-sm w-full font-mono text-xs" placeholder='{"columns":[{"type":"row_number","label":"No","rowspan":2}]}'>{{ old('table_layout', !empty($template->layout_json['table_layout']) ? json_encode($template->layout_json['table_layout'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                        <p class="mt-1 text-xs text-gray-400">Kosongkan untuk tabel default. Format: JSON dengan key "columns".</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contoh kolom dari file (opsional, untuk memilih kolom)</label>
                        <select onchange="window.location.href='{{ route('templates.edit', $template) }}?source_import_id='+this.value" class="mt-1 border-gray-300 rounded-md text-sm w-full sm:w-auto">
                            <option value="">-- Pilih file --</option>
                            @foreach ($imports as $import)
                                <option value="{{ $import->id }}" @selected(request('source_import_id') == $import->id)>{{ $import->file_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (! empty($columns))
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-2">Kolom</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                @foreach ($columns as $column)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 border border-gray-200 rounded-md px-3 py-2 cursor-pointer hover:border-blue-400">
                                        <input type="checkbox" name="fields[]" value="{{ $column }}" @checked(in_array($column, old('fields', $template->fields_json ?? []))) class="rounded text-blue-600" />
                                        {{ $column }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cari (default)</label>
                            <input type="text" name="search" value="{{ old('search', $template->filters_json['search'] ?? '') }}" class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Filter kolom (default)</label>
                            <input type="text" name="filter_column" value="{{ old('filter_column', $template->filters_json['filter_column'] ?? '') }}" class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nilai filter (default)</label>
                            <input type="text" name="filter_value" value="{{ old('filter_value', $template->filters_json['filter_value'] ?? '') }}" class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Urut kolom (default)</label>
                            <input type="text" name="sort_column" value="{{ old('sort_column', $template->sorting_json['column'] ?? '') }}" class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Arah urutan</label>
                            <select name="sort_direction" class="mt-1 border-gray-300 rounded-md text-sm w-full">
                                <option value="asc" @selected(old('sort_direction', $template->sorting_json['direction'] ?? 'asc') === 'asc')>A → Z</option>
                                <option value="desc" @selected(old('sort_direction', $template->sorting_json['direction'] ?? 'asc') === 'desc')>Z → A</option>
                            </select>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_default" value="1" @checked($template->is_default) class="rounded text-blue-600" />
                        Jadikan template default
                    </label>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('templates.index') }}" class="px-4 py-2 bg-gray-100 text-gray-800 text-sm font-semibold rounded-md hover:bg-gray-200">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
