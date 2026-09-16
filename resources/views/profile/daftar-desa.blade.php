<x-profile.layout>
    <x-slot name="header">Daftar Desa</x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Daftar Desa</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daftar desa yang selalu tampil di laporan agregasi (misal: L2 Pendidikan), meskipun tidak ada data pernikahan di bulan tersebut.</p>
        </div>
        <form method="post" action="{{ route('profile.update-desa') }}" class="p-6 space-y-5">
            @csrf
            @method('patch')

            <div>
                <label for="daftar_desa" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Daftar Desa (satu per baris)</label>
                <textarea id="daftar_desa" name="daftar_desa" rows="12" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-xs">{{ is_array($user->daftar_desa) ? implode("\n", $user->daftar_desa) : '' }}</textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Contoh: Masukkan satu nama desa per baris. Desa akan muncul di laporan meskipun tidak ada data pernikahan di bulan tersebut.</p>
                @error('daftar_desa') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" style="background-color: #111827; color: #fff;" class="px-4 py-2 text-sm font-semibold rounded-md hover:opacity-90">Simpan Perubahan</button>
                @if (session('status') === 'profile-desa-updated')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600 dark:text-gray-400">Tersimpan.</p>
                @endif
            </div>
        </form>
    </div>
</x-profile.layout>
