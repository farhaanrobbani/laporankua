<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Template Laporan') }}
            </h2>
            <a href="{{ route('templates.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                Buat Template
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-green-100 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($templates->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500 font-medium">Belum ada template</p>
                            <p class="text-gray-400 text-sm mt-1">Simpan konfigurasi laporan sebagai template untuk dipakai berulang.</p>
                            <a href="{{ route('templates.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                                Buat Template
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($templates as $template)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate">
                                                {{ $template->name }}
                                                @if ($template->is_default)
                                                    <span class="ml-1 inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Default</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ strtoupper($template->output_format) }}
                                                &middot; {{ count($template->fields_json ?? []) }} kolom
                                            </p>
                                            @if ($template->description)
                                                <p class="text-xs text-gray-500 mt-1 truncate">{{ $template->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-3 text-sm">
                                        <a href="{{ route('templates.use', $template) }}" class="text-green-600 hover:text-green-800 font-medium">Pakai</a>
                                        <a href="{{ route('templates.edit', $template) }}" class="text-blue-600 hover:text-blue-800 font-medium">Ubah</a>
                                        @if (! $template->is_default)
                                            <form method="POST" action="{{ route('templates.default', $template) }}">
                                                @csrf
                                                <button type="submit" class="text-purple-600 hover:text-purple-800 font-medium">Jadikan Default</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Hapus template ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">{{ $templates->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
