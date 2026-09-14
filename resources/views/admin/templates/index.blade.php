<x-admin.layout>
    <x-slot name="header">Template Management</x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Daftar Template</h3>
            <a href="{{ route('admin.templates.create') }}" style="background-color: #111827; color: #fff;" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md hover:opacity-90">
                + Tambah Template
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Nama</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Format</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Kolom</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Default</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Dibuat</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($templates as $template)
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900">{{ $template->name }}</p>
                                @if ($template->description)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($template->description, 50) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $template->output_format === 'pdf' ? 'bg-red-100 text-red-800' :
                                       ($template->output_format === 'word' ? 'bg-blue-100 text-blue-800' :
                                       ($template->output_format === 'excel' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) }}">
                                    {{ strtoupper($template->output_format) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">{{ count($template->fields_json ?? []) }}</td>
                            <td class="px-6 py-4">
                                @if ($template->is_default)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Default</span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">{{ $template->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if (! $template->is_default)
                                    <form method="POST" action="{{ route('admin.templates.default', $template) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-purple-600 hover:text-purple-800 text-sm font-medium">Set Default</button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.templates.edit', $template) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                                <form method="POST" action="{{ route('admin.templates.destroy', $template) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus template ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada template</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $templates->links() }}
        </div>
    </div>
</x-admin.layout>
