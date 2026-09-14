<x-admin.layout>
    <x-slot name="header">Download Management</x-slot>

    <div class="space-y-6">

        {{-- Add Form --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900">Tambah File / Link</h3>
            </div>
            <form method="POST" action="{{ route('admin.downloads.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Judul</label>
                    <input type="text" name="title" required class="mt-1 block w-full border-gray-300 rounded-md text-sm" placeholder="Judul file/link" />
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Deskripsi (opsional)</label>
                    <textarea name="description" rows="2" class="mt-1 block w-full border-gray-300 rounded-md text-sm" placeholder="Deskripsi singkat..."></textarea>
                </div>

                <div x-data="{ mode: 'upload' }">
                    <div class="flex items-center gap-1 bg-gray-200 rounded-lg p-1 w-fit mb-3">
                        <button type="button" @click="mode = 'upload'" :class="mode === 'upload' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'" class="px-4 py-2 text-sm font-medium rounded-md transition">
                            Upload File
                        </button>
                        <button type="button" @click="mode = 'link'" :class="mode === 'link' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'" class="px-4 py-2 text-sm font-medium rounded-md transition">
                            Link Eksternal
                        </button>
                    </div>

                    <div x-show="mode === 'upload'" x-transition>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih File (max 10MB)</label>
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition">
                            <x-heroicon-o-arrow-up-tray class="w-8 h-8 text-gray-400 mb-2" />
                            <span class="text-sm text-gray-500">Klik untuk memilih file</span>
                            <span class="text-xs text-gray-400 mt-1">PDF, Word, Excel, Gambar, ZIP</span>
                            <input type="file" name="file" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip,.rar" />
                        </label>
                        @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div x-show="mode === 'link'" x-transition>
                        <label class="block text-sm font-medium text-gray-700">Link Eksternal</label>
                        <input type="url" name="external_url" class="mt-1 block w-full border-gray-300 rounded-md text-sm" placeholder="https://drive.google.com/..." />
                        @error('external_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition">Simpan</button>
                </div>
            </form>
        </div>

        {{-- List --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900">Daftar File</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Judul</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Tipe</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Ukuran</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Upload</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Oleh</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($downloads as $dl)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ $dl->title }}</p>
                                    @if ($dl->description)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($dl->description, 60) }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($dl->isFile())
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">{{ strtoupper(pathinfo($dl->file_path, PATHINFO_EXTENSION)) }}</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">LINK</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ $dl->file_size ? number_format($dl->file_size / 1024, 1) . ' KB' : '-' }}</td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ $dl->created_at->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ $dl->uploader->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('admin.downloads.destroy', $dl) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus file ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada file</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin.layout>
