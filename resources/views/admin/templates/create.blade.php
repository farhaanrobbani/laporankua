<x-admin.layout>
    <x-slot name="header">Buat Template</x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.templates.store') }}" class="p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama template</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full" />
                @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi (opsional)</label>
                <textarea name="description" rows="2" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Format output</label>
                    <select name="output_format" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full">
                        @foreach (['pdf' => 'PDF', 'word' => 'Word', 'excel' => 'Excel', 'print' => 'Print'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('output_format') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orientasi (PDF)</label>
                    <select name="orientation" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full">
                        <option value="portrait">Portrait</option>
                        <option value="landscape" @selected(old('orientation') === 'landscape')>Landscape</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Layout Tabel Khusus (JSON, opsional)</label>
                <textarea name="table_layout" rows="6" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full font-mono text-xs" placeholder='{"columns":[{"type":"row_number","label":"No","rowspan":2}]}'>{{ old('table_layout') }}</textarea>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Kosongkan untuk tabel default. Format: JSON dengan key "columns".</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contoh kolom dari file (opsional, untuk memilih kolom)</label>
                <select onchange="window.location.href='{{ route('admin.templates.create') }}?source_import_id='+this.value" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full sm:w-auto">
                    <option value="">-- Pilih file --</option>
                    @foreach ($imports as $import)
                        <option value="{{ $import->id }}" @selected($sourceImport?->id === $import->id)>{{ $import->table_name }}</option>
                    @endforeach
                </select>
            </div>

            @if (! empty($columns))
                <div>
                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kolom</span>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        @foreach ($columns as $column)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-md px-3 py-2 cursor-pointer hover:border-blue-400 dark:hover:border-blue-500">
                                <input type="checkbox" name="fields[]" value="{{ $column }}" @checked(in_array($column, old('fields', $columns))) class="rounded text-blue-600 dark:text-blue-400" />
                                {{ $column }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cari (default)</label>
                    <input type="text" name="search" value="{{ old('search') }}" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter kolom (default)</label>
                    <input type="text" name="filter_column" value="{{ old('filter_column') }}" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nilai filter (default)</label>
                    <input type="text" name="filter_value" value="{{ old('filter_value') }}" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Urut kolom (default)</label>
                    <input type="text" name="sort_column" value="{{ old('sort_column') }}" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Arah urutan</label>
                    <select name="sort_direction" class="mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm w-full">
                        <option value="asc">A → Z</option>
                        <option value="desc" @selected(old('sort_direction') === 'desc')>Z → A</option>
                    </select>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="is_default" value="1" class="rounded text-blue-600 dark:text-blue-400" />
                Jadikan template default
            </label>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.templates.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm font-semibold rounded-md hover:bg-blue-700 dark:hover:bg-blue-600">Simpan Template</button>
            </div>
        </form>
    </div>
</x-admin.layout>
