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

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Format output</label>
                        <select name="output_format" class="mt-1 border-gray-300 rounded-md text-sm w-full sm:w-auto">
                            @foreach (['pdf' => 'PDF', 'word' => 'Word', 'excel' => 'Excel', 'print' => 'Print'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('output_format', $template->output_format) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="text-sm text-gray-500">
                        Konfigurasi kolom &amp; filter: {{ count($template->fields_json ?? []) }} kolom tersimpan.
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
