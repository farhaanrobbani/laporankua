<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Record') }}
            </h2>
            <a href="{{ route('data.index', ['import_id' => $record->import_id]) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; Kembali ke Data</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Baris {{ $record->row_number }}</h3>
                    <p class="text-sm text-gray-500 mb-4">Dari file: {{ $record->import->file_name }}</p>

                    <dl class="divide-y divide-gray-200 border border-gray-200 rounded-md">
                        @foreach ($record->row_data ?? [] as $key => $value)
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-4 px-4 py-2">
                                <dt class="text-sm font-medium text-gray-500">{{ $key }}</dt>
                                <dd class="text-sm text-gray-900 sm:col-span-2 break-words">{{ $value ?? '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <p class="mt-4 text-xs text-gray-500">Dibuat: {{ $record->created_at->format('d M Y H:i') }} &middot; Diubah: {{ $record->updated_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
