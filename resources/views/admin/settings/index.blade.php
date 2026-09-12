<x-admin.layout>
    <x-slot name="header">Pengaturan Website</x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Pengaturan Umum</h3>
        </div>
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Website</label>
                <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required class="w-full border-gray-300 rounded-md text-sm" />
                @error('site_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Website</label>
                <input type="text" id="site_description" name="site_description" value="{{ old('site_description', $settings['site_description']) }}" class="w-full border-gray-300 rounded-md text-sm" />
                @error('site_description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email Kontak</label>
                <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" class="w-full border-gray-300 rounded-md text-sm" />
                @error('contact_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="footer_text" class="block text-sm font-medium text-gray-700 mb-1">Teks Footer</label>
                <input type="text" id="footer_text" name="footer_text" value="{{ old('footer_text', $settings['footer_text']) }}" class="w-full border-gray-300 rounded-md text-sm" />
                @error('footer_text') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo Website</label>
                @if ($settings['site_logo'])
                    <div class="mb-2 flex items-center gap-3">
                        <img src="{{ Storage::url($settings['site_logo']) }}" alt="Logo" class="h-12 w-auto rounded border border-gray-200" />
                        <label class="inline-flex items-center gap-1 text-xs text-red-600 cursor-pointer">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-red-300 text-red-600" />
                            Hapus logo
                        </label>
                    </div>
                @endif
                <input type="file" id="site_logo" name="site_logo" accept="image/jpeg,image/png,image/svg+xml,image/webp" class="w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200" />
                <p class="text-xs text-gray-400 mt-1">JPEG, PNG, SVG, atau WebP. Maks 2MB.</p>
                @error('site_logo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="pt-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-md hover:bg-gray-800">Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</x-admin.layout>
