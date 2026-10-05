<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Data') }}
            </h2>
        </div>
    </x-slot>

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
                    </div>

                    @if (empty($importIds))
                        <x-empty-state title="Belum ada data" message="Upload laporan model L3 terlebih dahulu." :action-url="route('imports.create')" action-label="Upload Excel" />
                    @elseif ($totalRusak === 0)
                        <x-empty-state title="Tidak ada nomor rusak terdeteksi" message="Semua nomor perforasi dalam rentang lengkap." />
                    @else
                        <div class="mb-4 bg-amber-50 border border-amber-200 rounded-md px-4 py-3 text-sm text-amber-800">
                            <strong>{{ $totalRusak }}</strong> nomor terdeteksi rusak (hilang di tengah rentang) dari
                            <strong>{{ $prefixCount }}</strong> prefix.
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-md">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">No</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">Prefix</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">Nomor JT</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @php($no = $missingRows->firstItem() ?? 1)
                                    @foreach ($missingRows as $row)
                                        <tr>
                                            <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $no++ }}</td>
                                            <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $row['prefix'] }}</td>
                                            <td class="px-3 py-2 text-gray-700 whitespace-nowrap">JT {{ $row['number'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $missingRows->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
