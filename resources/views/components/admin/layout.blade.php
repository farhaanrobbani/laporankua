<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $header ?? 'Admin Panel' }}</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">Kembali ke Aplikasi</a>
        </div>
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
                        $isActiveDashboard = request()->routeIs('admin.index');
                        $isActiveUsers = request()->routeIs('admin.users.*');
                        $isActiveSettings = request()->routeIs('admin.settings.*');
                    @endphp

                    <a href="{{ route('admin.index') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActiveDashboard ? '' : 'text-gray-700 hover:bg-gray-100' }}"
                       @if($isActiveDashboard) style="background-color: #111827; color: #fff;" @endif>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.users.index') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActiveUsers ? '' : 'text-gray-700 hover:bg-gray-100' }}"
                       @if($isActiveUsers) style="background-color: #111827; color: #fff;" @endif>
                        User Management
                    </a>
                    <a href="{{ route('admin.settings.index') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActiveSettings ? '' : 'text-gray-700 hover:bg-gray-100' }}"
                       @if($isActiveSettings) style="background-color: #111827; color: #fff;" @endif>
                        Pengaturan
                    </a>
                </nav>
            </aside>

            <div class="flex-1 min-w-0">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-app-layout>
