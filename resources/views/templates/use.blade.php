<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pakai Template') }}: {{ $template->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('templates.apply', $template) }}" class="p-6 space-y-5">
                    @csrf

                    <p class="text-sm text-gray-500">Template <span class="font-semibold">{{ $template->name }}</span> ({{ strtoupper($template->output_format) }}, {{ count($template->fields_json ?? []) }} kolom) akan diterapkan ke file yang Anda pilih. Kolom yang tidak ada di file target dilewati otomatis.</p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">File target</label>
                        <select name="import_id" required class="mt-1 border-gray-300 rounded-md text-sm w-full">
                            <option value="">-- Pilih file import --</option>
                            @foreach ($imports as $import)
                                <option value="{{ $import->id }}" @selected(old('import_id') == $import->id)>{{ $import->file_name }}</option>
                            @endforeach
                        </select>
                        @error('import_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Judul laporan</label>
                        <input type="text" name="title" value="{{ old('title', $template->name) }}" required class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('templates.index') }}" class="px-4 py-2 bg-gray-100 text-gray-800 text-sm font-semibold rounded-md hover:bg-gray-200">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700">Buat Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
