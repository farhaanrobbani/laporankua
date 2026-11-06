<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('BIMWIN') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="p-6 sm:p-8">
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-200">
                        Premium
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        BIMWIN hanya untuk pengguna Premium
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Akun Anda saat ini menggunakan plan Free. Hubungi admin untuk mengaktifkan plan Premium agar dapat memakai otomasi BIMWIN dan userscript SIMKAH4.
                    </p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Status plan: <strong>{{ $user->isPremium() ? 'Premium' : 'Free' }}</strong>
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                            Kembali ke Dashboard
                        </a>
                        <a href="{{ route('profile.info') }}"
                           class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            Lihat Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
