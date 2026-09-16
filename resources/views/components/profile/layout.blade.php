<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $header ?? 'Profile' }}</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex gap-6">
            <aside class="w-48 shrink-0">
                <nav class="space-y-1">
                    @php
                        $isActiveInfo = request()->routeIs('profile.info');
                        $isActiveKop = request()->routeIs('profile.kop-surat');
                        $isActiveDesa = request()->routeIs('profile.daftar-desa');
                        $isActivePassword = request()->routeIs('profile.password');
                        $isActiveDelete = request()->routeIs('profile.delete');
                    @endphp

                    <a href="{{ route('profile.info') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActiveInfo ? '' : 'text-gray-700 hover:bg-gray-100' }}"
                       @if($isActiveInfo) style="background-color: #111827; color: #fff;" @endif>
                        Informasi Profil
                    </a>
                    <a href="{{ route('profile.kop-surat') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActiveKop ? '' : 'text-gray-700 hover:bg-gray-100' }}"
                       @if($isActiveKop) style="background-color: #111827; color: #fff;" @endif>
                        Data Kop Surat
                    </a>
                    <a href="{{ route('profile.daftar-desa') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActiveDesa ? '' : 'text-gray-700 hover:bg-gray-100' }}"
                       @if($isActiveDesa) style="background-color: #111827; color: #fff;" @endif>
                        Daftar Desa
                    </a>
                    <a href="{{ route('profile.password') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActivePassword ? '' : 'text-gray-700 hover:bg-gray-100' }}"
                       @if($isActivePassword) style="background-color: #111827; color: #fff;" @endif>
                        Ubah Password
                    </a>
                    <a href="{{ route('profile.delete') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActiveDelete ? '' : 'text-gray-700 hover:bg-gray-100' }}"
                       @if($isActiveDelete) style="background-color: #111827; color: #fff;" @endif>
                        Hapus Akun
                    </a>
                </nav>
            </aside>

            <div class="flex-1 min-w-0">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-app-layout>
