<x-profile.layout>
    <x-slot name="header">Informasi Profil</x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Informasi Profil</h3>
            <p class="text-sm text-gray-500 mt-1">Perbarui informasi akun dan kontak KUA Anda.</p>
        </div>
        <form method="post" action="{{ route('profile.update') }}" class="p-6 space-y-5">
            @csrf
            @method('patch')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="kecamatan" class="block text-sm font-medium text-gray-700">Kecamatan</label>
                    <input type="text" id="kecamatan" name="kecamatan" value="{{ old('kecamatan', $user->kecamatan) }}" class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                    @error('kecamatan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nama_kepala_kua" class="block text-sm font-medium text-gray-700">Nama Kepala KUA</label>
                    <input type="text" id="nama_kepala_kua" name="nama_kepala_kua" value="{{ old('nama_kepala_kua', $user->nama_kepala_kua) }}" class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                    @error('nama_kepala_kua') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nip_kepala" class="block text-sm font-medium text-gray-700">NIP Kepala KUA</label>
                    <input type="text" id="nip_kepala" name="nip_kepala" value="{{ old('nip_kepala', $user->nip_kepala) }}" class="mt-1 border-gray-300 rounded-md text-sm w-full" />
                    @error('nip_kepala') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" style="background-color: #111827; color: #fff;" class="px-4 py-2 text-sm font-semibold rounded-md hover:opacity-90">Simpan Perubahan</button>
                @if (session('status') === 'profile-updated')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">Tersimpan.</p>
                @endif
            </div>
        </form>
    </div>
</x-profile.layout>
