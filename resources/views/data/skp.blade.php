<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Data') }}
            </h2>
        </div>
    </x-slot>

    @php
        $bulanNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700 mb-6 flex-wrap">
                        <a href="{{ route('data.index') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.index') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('Data Import') }}
                        </a>
                        <a href="{{ route('data.pelaksanaan-kantor') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.pelaksanaan-kantor') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('Pelaksanaan Kantor') }}
                        </a>
                        <a href="{{ route('data.pelaksanaan-luar-kantor') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.pelaksanaan-luar-kantor') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('Pelaksanaan Luar Kantor') }}
                        </a>
                        <a href="{{ route('data.duplikat') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.duplikat') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('Duplikat') }}
                        </a>
                        <a href="{{ route('data.bukan-duplikat') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.bukan-duplikat') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('Bukan Duplikat') }}
                        </a>
                        <a href="{{ route('data.rusak') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.rusak') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('Rusak') }}
                        </a>
                        <a href="{{ route('data.bukti-bayar') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.bukti-bayar') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('Bukti Bayar') }}
                        </a>
                        <a href="{{ route('data.surat-tugas') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.surat-tugas') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('Surat Tugas') }}
                        </a>
                        <a href="{{ route('data.skp') }}" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition {{ request()->routeIs('data.skp') ? 'border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ __('SKP') }}
                        </a>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Statistik SKP') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Periode') }}: {{ $periodeLabel }}</p>
                        </div>
                        <form method="GET" action="{{ route('data.skp') }}" class="flex flex-wrap items-end gap-3">
                            <div>
                                <label for="mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Mode Periode') }}</label>
                                <select id="mode" name="mode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                                    <option value="bulanan" @selected($mode === 'bulanan')>{{ __('Bulanan (1 bulan)') }}</option>
                                    <option value="kuartal" @selected($mode === 'kuartal')>{{ __('Kuartal (3 bulan)') }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="bulan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bulan') }}</label>
                                <select id="bulan" name="bulan" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                                    @foreach ($bulanNames as $angka => $nama)
                                        <option value="{{ $angka }}" @selected($bulan === $angka)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="tahun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tahun') }}</label>
                                <select id="tahun" name="tahun" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                                    @foreach ($tahunTersedia as $tahunOption)
                                        <option value="{{ $tahunOption }}" @selected($tahun === $tahunOption)>{{ $tahunOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">{{ __('Terapkan') }}</button>
                                <a href="{{ route('data.skp') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">{{ __('Reset') }}</a>
                            </div>
                        </form>
                    </div>

                    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Pendaftaran mengikuti Tanggal Daftar; pelaksanaan mengikuti Tanggal Nikah (peristiwa nikah) dengan pecahan kantor/luar dari kolom Nikah Di; duplikat unik per Nomor Perforasi dengan Tanggal Cetak — konsisten dengan Statistik Laporan di dashboard.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Pendaftaran Nikah') }}</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stats['pendaftaran']) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $periodeLabel }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Pelaksanaan Nikah') }}</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stats['pelaksanaan']) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $periodeLabel }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Pelaksanaan Kantor') }}</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stats['pelaksanaan_kantor']) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $periodeLabel }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Pelaksanaan Luar Kantor') }}</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stats['pelaksanaan_luar']) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $periodeLabel }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Duplikat') }}</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stats['duplikat']) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $periodeLabel }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
