<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('BIMWIN') }}
            </h2>
        </div>
    </x-slot>

    @php
        $wilayahLengkap = filled($user->provinsi) && filled($user->kota_kabupaten) && filled($user->kecamatan);
        $endpointCount = count(array_filter($endpoints));
        $endpointFields = [
            'cek' => ['label' => 'Cek status catin', 'hint' => 'Permintaan cek BIMWIN per calon pengantin'],
            'dialog' => ['label' => 'Buka dialog BIMWIN', 'hint' => 'Permintaan form BIMWIN berdasarkan nomor daftar'],
            'simpan' => ['label' => 'Simpan jadwal', 'hint' => 'Permintaan simpan form BIMWIN'],
            'selesai' => ['label' => 'Tandai selesai', 'hint' => 'Permintaan status selesai (SS_ALL)'],
        ];
    @endphp

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="p-4 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Otomasi Pengisian BIMWIN</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                                Script berjalan di browser pada halaman SIMKAH4 dan memakai sesi login Anda. Aplikasi Laporan tidak menyimpan kredensial SIMKAH4.
                            </p>
                        </div>
                        <a href="{{ url('/userscripts/simkah4-bimwin.user.js') }}" target="_blank" rel="noopener"
                           class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700 transition">
                            <x-heroicon-o-arrow-down-tray class="h-5 w-5" />
                            {{ __('Install Userscript') }}
                        </a>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Provinsi</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user->provinsi ?: 'Belum diisi' }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Kabupaten/Kota</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user->kota_kabupaten ?: 'Belum diisi' }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Kecamatan</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user->kecamatan ?: 'Belum diisi' }}</p>
                        </div>
                    </div>

                    @unless ($wilayahLengkap)
                        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                            Lengkapi provinsi, kabupaten/kota, dan kecamatan di
                            <a href="{{ route('profile.daftar-desa') }}" class="font-semibold underline">Profil → Daftar Desa</a>
                            sebelum menjalankan script.
                        </div>
                    @endunless
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-4 sm:px-6 dark:border-gray-700">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Endpoint BIMWIN Akun Ini</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Endpoint berbeda untuk setiap akun SIMKAH4. Rekam dari akun Anda sendiri; jangan memakai endpoint milik akun lain.
                            </p>
                        </div>
                        @if ($endpointsLengkap)
                            <span class="inline-flex w-fit items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200">
                                {{ $endpointCount }}/4 tersimpan
                            </span>
                        @else
                            <span class="inline-flex w-fit items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-200">
                                {{ $endpointCount }}/4 — belum lengkap
                            </span>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('bimwin.update-endpoints') }}" class="p-4 sm:p-6">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        @foreach ($endpointFields as $key => $field)
                            <div>
                                <label for="endpoint-{{ $key }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $field['label'] }}
                                </label>
                                <input type="url" id="endpoint-{{ $key }}" name="endpoints[{{ $key }}]"
                                       value="{{ old('endpoints.' . $key, $endpoints[$key]) }}"
                                       placeholder="https://simkah4.kemenag.go.id/..."
                                       autocomplete="off" spellcheck="false"
                                       class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 @error('endpoints.' . $key) border-red-500 @enderror">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                                @error('endpoints.' . $key)
                                    <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    @error('endpoints')
                        <p class="mt-3 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">
                        <p class="font-semibold">Cara merekam otomatis</p>
                        <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs sm:text-sm">
                            <li>Buka SIMKAH4 dengan akun ini, lalu buka halaman daftar nikah.</li>
                            <li>Di panel userscript BIMWIN, klik <strong>Rekam endpoint</strong>.</li>
                            <li>Lakukan satu alur BIMWIN secara manual: cek catin, buka dialog, isi jadwal, lalu simpan/tandai selesai.</li>
                            <li>Setelah 4/4 endpoint terekam, klik <strong>Kirim ke Laporan</strong>. Form di halaman ini akan terisi otomatis.</li>
                        </ol>
                        <p class="mt-2 text-xs">
                            Perekaman hanya mengamati permintaan dari alur manual. Proses simpan/selesai tetap menulis ke SIMKAH4, jadi gunakan data uji atau data yang memang akan diproses.
                        </p>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                            <x-heroicon-o-check class="h-4 w-4" />
                            {{ __('Simpan Endpoint') }}
                        </button>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Isi keempatnya, atau kosongkan semuanya untuk menghapus.</span>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-4 py-4 sm:px-6 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Kode Konfigurasi</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Salin kode ini, lalu tempel di panel userscript pada halaman SIMKAH4.</p>
                    </div>
                    <div class="p-4 sm:p-6">
                        <textarea id="bimwin-config" readonly rows="11"
                                  class="block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs text-gray-800 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">{{ $configJson }}</textarea>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <button type="button" id="bimwin-copy"
                                    class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                                <x-heroicon-o-clipboard-document class="h-4 w-4" />
                                {{ __('Salin Kode') }}
                            </button>
                            <span id="bimwin-copy-status" class="text-sm font-medium text-gray-500 dark:text-gray-400"></span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            Kode ini memuat <span class="font-mono">appUrl</span> dan keempat endpoint akun Anda. Kosongkan fasilitator jika ingin memilih otomatis berdasarkan sisa kuota terbanyak. Nilai <span class="font-mono">maxCoba</span> adalah batas percobaan saat kuota penuh.
                        </p>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-4 py-4 sm:px-6 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Cara Pakai</h3>
                    </div>
                    <div class="p-4 sm:p-6">
                        <ol class="list-decimal space-y-3 pl-5 text-sm text-gray-700 dark:text-gray-300">
                            <li>Pasang ekstensi <strong>Tampermonkey</strong> di Chrome, Edge, atau Firefox.</li>
                            <li>Buka <a href="{{ url('/userscripts/simkah4-bimwin.user.js') }}" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">link install userscript BIMWIN</a>, lalu setujui pemasangan.</li>
                            <li>Login ke SIMKAH4 dan buka halaman daftar nikah yang ingin diproses.</li>
                            <li>Jika endpoint belum 4/4, klik <strong>Rekam endpoint</strong> di panel userscript dan ikuti satu alur BIMWIN manual. Klik <strong>Kirim ke Laporan</strong>, lalu simpan form endpoint di halaman ini.</li>
                            <li>Klik tombol <strong>BIMWIN</strong> di kanan bawah, tempel kode konfigurasi, lalu klik <strong>Muat</strong>.</li>
                            <li>Jalankan <strong>Cek saja</strong> lebih dulu. Pastikan nomor daftar dan status calon sudah benar.</li>
                            <li>Setelah yakin, pilih <strong>Isi jadwal</strong> atau <strong>Isi + tandai selesai</strong>, lalu konfirmasi.</li>
                        </ol>
                        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                            Script hanya memproses baris yang sudah dimuat di grid SIMKAH4. Atur filter dan halaman di SIMKAH4 secara manual.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                <p class="font-semibold">Peringatan</p>
                <p class="mt-1">
                    Mode <strong>Isi jadwal</strong> dan <strong>Isi + tandai selesai</strong> benar-benar menyimpan data ke SIMKAH4, bukan ke aplikasi Laporan.
                    Gunakan hanya dengan izin/SOP yang berlaku, uji satu data terlebih dahulu, dan periksa hasilnya di SIMKAH4.
                    Endpoint internal SIMKAH4 dapat berbeda antar akun dan dapat berubah sewaktu-waktu. Rekam ulang dari akun Anda jika alur BIMWIN berubah.
                </p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('bimwin-copy')?.addEventListener('click', async function () {
            var textarea = document.getElementById('bimwin-config');
            var status = document.getElementById('bimwin-copy-status');
            var ok = false;

            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(textarea.value);
                    ok = true;
                } catch (e) {
                    ok = false;
                }
            }

            if (!ok) {
                textarea.focus();
                textarea.select();
                try {
                    ok = document.execCommand('copy');
                } catch (e) {
                    ok = false;
                }
            }

            status.textContent = ok ? 'Tersalin.' : 'Gagal menyalin — pilih teks lalu Ctrl+C.';
            status.className = ok
                ? 'text-sm font-medium text-emerald-600 dark:text-emerald-400'
                : 'text-sm font-medium text-red-600 dark:text-red-400';

            setTimeout(function () { status.textContent = ''; }, 4000);
        });

        (function () {
            var params = new URLSearchParams(window.location.hash.replace(/^#/, ''));
            var payload = params.get('endpoints');
            if (!payload) return;

            try {
                var endpoints = JSON.parse(payload);
                ['cek', 'dialog', 'simpan', 'selesai'].forEach(function (key) {
                    var input = document.getElementById('endpoint-' + key);
                    if (input && typeof endpoints[key] === 'string') {
                        input.value = endpoints[key];
                    }
                });

                history.replaceState(null, '', window.location.pathname + window.location.search);
            } catch (e) {
                return;
            }
        })();
    </script>
</x-app-layout>
