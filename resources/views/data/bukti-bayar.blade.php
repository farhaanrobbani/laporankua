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
                    </div>

                    @if (empty($importIds))
                        <x-empty-state title="Belum ada data" message="Upload laporan simponi terlebih dahulu." :action-url="route('imports.create')" action-label="Upload Excel" />
                    @else
                        <form method="GET" action="{{ route('data.bukti-bayar') }}" class="mb-6 flex flex-wrap items-end gap-3">
                            <div>
                                <label for="bulan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bulan') }}</label>
                                <select id="bulan" name="bulan" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                                    <option value="">{{ __('Semua') }}</option>
                                    @foreach ($bulanNames as $angka => $nama)
                                        <option value="{{ $angka }}" @selected((int) $bulan === $angka)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="tahun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tahun') }}</label>
                                <select id="tahun" name="tahun" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                                    <option value="">{{ __('Semua') }}</option>
                                    @foreach ($tahunTersedia as $tahunOption)
                                        <option value="{{ $tahunOption }}" @selected($tahun === $tahunOption)>{{ $tahunOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 min-w-[200px]">
                                <label for="q" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Cari nama / no billing') }}</label>
                                <input type="text" id="q" name="q" value="{{ $q }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm" />
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">{{ __('Filter') }}</button>
                                @if ($bulan !== null || $tahun !== null || $q !== '')
                                    <a href="{{ route('data.bukti-bayar') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">{{ __('Reset') }}</a>
                                @endif
                            </div>
                        </form>

                        @if ($totalBillings === 0)
                            <x-empty-state title="Tidak ada data" message="Tidak ada bukti bayar yang cocok dengan filter." />
                        @else
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-4 rounded-lg bg-indigo-600 px-5 py-4 text-white shadow-md">
                                <div>
                                    <p class="text-base font-semibold">
                                        <strong>{{ $totalBillings }}</strong> bukti bayar{{ $totalBillings !== count($semuaNoBilling) ? ' (' . count($semuaNoBilling) . ' no billing unik)' : '' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-indigo-100">Klik tombol untuk menyalin daftar no billing, lalu tempel ke panel unduh massal di SIMKAH4.</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span id="salin-status" class="text-sm font-medium"></span>
                                    <button type="button" id="salin-btn" data-list='@json($semuaNoBilling)' class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700 shadow hover:bg-indigo-50 transition">
                                        <x-heroicon-o-clipboard-document class="h-5 w-5" />
                                        {{ __('Salin Daftar No Billing') }}
                                    </button>
                                </div>
                            </div>

                            <div id="salin-fallback" hidden class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3">
                                <p class="mb-2 text-sm font-medium text-amber-800">Salin manual: kolom di bawah sudah terpilih — tekan <kbd class="rounded bg-amber-200 px-1.5 py-0.5 font-mono text-xs">Ctrl+C</kbd> (mac: <kbd class="rounded bg-amber-200 px-1.5 py-0.5 font-mono text-xs">Cmd+C</kbd>).</p>
                                <textarea id="salin-fallback-textarea" readonly rows="6" class="w-full rounded-md border-amber-300 font-mono text-sm text-gray-800 focus:border-amber-400 focus:ring-amber-300"></textarea>
                            </div>

                            <div class="overflow-x-auto border border-gray-200 rounded-md">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">No</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">No Billing</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">Tanggal Billing</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">Nama Wajib Bayar</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">Nominal Setor</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">No Pendaftaran</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">No NTB</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @php($no = $billings->firstItem() ?? 1)
                                        @foreach ($billings as $row)
                                            <tr>
                                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $no++ }}</td>
                                                <td class="px-3 py-2 text-gray-900 font-mono whitespace-nowrap">{{ $row['no_billing'] }}</td>
                                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $row['tanggal_billing'] }}</td>
                                                <td class="px-3 py-2 text-gray-700">{{ $row['nama'] }}</td>
                                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $row['nominal'] }}</td>
                                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $row['no_pendaftaran'] }}</td>
                                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $row['no_ntb'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">{{ $billings->links() }}</div>
                        @endif

                        <details class="mt-6 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            <summary class="cursor-pointer font-medium">{{ __('Cara pakai unduh massal bukti bayar (SIMKAH4)') }}</summary>
                            <ol class="list-decimal ml-5 mt-2 space-y-1">
                                <li>Di laptop/PC, pasang ekstensi <strong>Tampermonkey</strong> di browser (Chrome/Edge/Firefox).</li>
                                <li>Buka <a href="{{ url('/userscripts/simkah4-bukti-bayar.user.js') }}" target="_blank" rel="noopener" class="text-indigo-600 underline">link pemasangan script unduh massal</a> — Tampermonkey akan menawarkan pemasangan.</li>
                                <li>Login ke SIMKAH4 (<span class="font-mono">https://simkah4.kemenag.go.id</span>).</li>
                                <li>Klik tombol <strong>"Unduh Massal Bukti Bayar"</strong> (panel Tampermonkey) di halaman SIMKAH4.</li>
                                <li>Sekali saja: tempel URL bukti bayar apa pun (mis. dari hasil klik manual) ke kolom <strong>UUID</strong> — otomatis tersimpan.</li>
                                <li>Tempel daftar No Billing (hasil tombol <strong>"Salin Daftar No Billing"</strong>) ke kolom daftar, lalu klik <strong>Mulai</strong>.</li>
                                <li>Browser mungkin meminta izin "unduh multiple files" — izinkan sekali. Hasil: <span class="font-mono">bukti-bayar-{no_billing}.pdf</span>.</li>
                            </ol>
                            <p class="mt-2 text-xs text-gray-500">Script memakai sesi login SIMKAH4-mu di browser; tidak ada kredensial yang disimpan aplikasi.</p>
                        </details>

                        <script>
                            document.getElementById('salin-btn')?.addEventListener('click', async function () {
                                var items = JSON.parse(this.dataset.list || '[]');
                                var text = items.join('\n');
                                var status = document.getElementById('salin-status');
                                var fallback = document.getElementById('salin-fallback');
                                var fallbackTextarea = document.getElementById('salin-fallback-textarea');
                                var ok = false;

                                if (navigator.clipboard && window.isSecureContext) {
                                    try {
                                        await navigator.clipboard.writeText(text);
                                        ok = true;
                                    } catch (e) {
                                        ok = false;
                                    }
                                }

                                if (!ok) {
                                    fallback.hidden = false;
                                    fallbackTextarea.value = text;
                                    fallbackTextarea.focus();
                                    fallbackTextarea.select();
                                    try {
                                        ok = document.execCommand('copy');
                                    } catch (e) {
                                        ok = false;
                                    }
                                    if (ok) {
                                        fallback.hidden = true;
                                    }
                                }

                                if (ok) {
                                    status.className = 'text-sm font-medium text-green-200';
                                    status.textContent = '✓ Tersalin (' + items.length + ' nomor)';
                                    setTimeout(function () { status.textContent = ''; }, 4000);
                                } else {
                                    status.className = 'text-sm font-medium text-amber-200';
                                    status.textContent = 'Gagal otomatis — salin manual dari kolom di bawah.';
                                }
                            });
                        </script>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
