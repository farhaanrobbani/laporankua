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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($templates->isEmpty())
                        <x-empty-state title="Belum ada template" message="Simpan konfigurasi laporan sebagai template untuk dipakai berulang." :action-url="route('templates.create')" action-label="Buat Template" />
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($templates as $template)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate">
                                                {{ $template->name }}
                                                @if ($template->is_default)
                                                    <x-status-badge status="default" class="ml-1" />
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <x-status-badge :status="$template->output_format" />
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
                                        <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-template-{{ $template->id }}')" class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                        <x-modal :name="'confirm-delete-template-'.$template->id" :show="false" focusable>
                                            <form method="POST" action="{{ route('templates.destroy', $template) }}" class="p-6">
                                                @csrf
                                                @method('DELETE')
                                                <h2 class="text-lg font-medium text-gray-900">Hapus template?</h2>
                                                <p class="mt-1 text-sm text-gray-600">Template "{{ $template->name }}" akan dihapus permanen.</p>
                                                <div class="mt-6 flex justify-end gap-3">
                                                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                                    <x-danger-button>Hapus</x-danger-button>
                                                </div>
                                            </form>
                                        </x-modal>
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
