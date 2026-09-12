<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $dataset['title'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            @page { size: A4; margin: 8mm 15mm 15mm 15mm; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
        @media screen {
            .print-sheet { max-width: 210mm; margin: 1.5rem auto; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.15); padding: 15mm; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900" style="font-family: Arial, sans-serif;">
    <div class="no-print max-w-3xl mx-auto mt-4 flex gap-3 justify-end">
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">Print / Simpan PDF</button>
        <button onclick="window.history.back()" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-semibold rounded-md hover:bg-gray-300">Tutup</button>
    </div>

    <div class="print-sheet">
        @if (! empty($dataset['table_layout']))
            @php
                $layout = $dataset['table_layout'];
                $columns = $layout['columns'] ?? [];

                $applyTransform = function ($value, $transform) {
                    if ($transform === 'klinik_balai_nikah') {
                        $lower = mb_strtolower((string) $value);
                        return str_contains($lower, 'balai nikah') ? 'K' : 'LK';
                    }
                    return $value;
                };

                $getDataCells = function ($col) use ($applyTransform) {
                    if ($col['type'] === 'field') {
                        $field = $col['field'] ?? '';
                        $transform = $col['transform'] ?? null;
                        return [$field, $transform];
                    }
                    return [null, null];
                };

                $tanggalNikahField = null;
                $tempatNikahField = null;
                foreach ($columns as $col) {
                    if ($col['type'] === 'field' && ($col['field'] ?? '') === 'Tanggal Nikah') {
                        $tanggalNikahField = $col['field'];
                    }
                    if ($col['type'] === 'field' && ($col['field'] ?? '') === 'Tempat Nikah') {
                        $tempatNikahField = $col['field'];
                    }
                    if ($col['type'] === 'group') {
                        foreach ($col['children'] ?? [] as $child) {
                            if (($child['field'] ?? '') === 'Tanggal Nikah') {
                                $tanggalNikahField = $child['field'];
                            }
                            if (($child['field'] ?? '') === 'Tempat Nikah') {
                                $tempatNikahField = $child['field'];
                            }
                        }
                    }
                }

                $lastDate = null;
                $countK = 0;
                $countLK = 0;
                $monthDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

                foreach ($dataset['rows'] as $row) {
                    if ($tempatNikahField !== null) {
                        $tempat = mb_strtolower((string) ($row[$tempatNikahField] ?? ''));
                        if (str_contains($tempat, 'balai nikah')) {
                            $countK++;
                        } else {
                            $countLK++;
                        }
                    }
                    if ($tanggalNikahField !== null && ! empty($row[$tanggalNikahField])) {
                        $date = \Carbon\Carbon::parse($row[$tanggalNikahField]);
                        if ($lastDate === null || $date->gt($lastDate)) {
                            $lastDate = $date;
                        }
                    }
                }

                $countAll = $countK + $countLK;
                $hariName = $lastDate ? $monthDays[$lastDate->dayOfWeek] : '-';
                $tanggalFormatted = $lastDate ? $lastDate->day . ' ' . $monthNames[$lastDate->month] . ' ' . $lastDate->year : '-';
                $bulanName = $lastDate ? $monthNames[$lastDate->month] : '-';
                $tahunName = $lastDate ? $lastDate->year : '-';
            @endphp

            <div class="mb-0">
                @if (! empty($dataset['nama_kementerian']) || ! empty($dataset['nama_kantor']))
                    @if (! empty($dataset['logo_kantor']))
                        <table class="w-full mb-1">
                            <tr>
                                <td class="align-top pr-1" style="width: 80px;">
                                    <img src="{{ asset('storage/' . $dataset['logo_kantor']) }}" alt="Logo" style="margin-left: 64px; max-height: 80px; max-width: 80px;" />
                                </td>
                                <td class="text-center">
                                    <p class="text-sm font-bold uppercase">{{ $dataset['nama_kementerian'] ?? 'Kementerian Agama' }}</p>
                                    @if (! empty($dataset['nama_kantor_kota']))
                                        <p class="text-sm font-bold uppercase">{{ $dataset['nama_kantor_kota'] }}</p>
                                    @endif
                                    @if (! empty($dataset['nama_kantor']))
                                        <p class="text-sm font-bold uppercase">{{ $dataset['nama_kantor'] }}</p>
                                    @endif
                                    @if (! empty($dataset['alamat_kantor']))
                                        <p class="text-xs">{{ $dataset['alamat_kantor'] }}</p>
                                    @endif
                                    @if (! empty($dataset['telepon_kantor']) || ! empty($dataset['email_kantor']))
                                        <p class="text-xs">
                                            @if (! empty($dataset['telepon_kantor']))
                                                Telp: {{ $dataset['telepon_kantor'] }}
                                            @endif
                                            @if (! empty($dataset['telepon_kantor']) && ! empty($dataset['email_kantor']))
                                                |
                                            @endif
                                            @if (! empty($dataset['email_kantor']))
                                                Email: {{ $dataset['email_kantor'] }}
                                            @endif
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @else
                        <div class="text-center mb-1">
                            <p class="text-sm font-bold uppercase">{{ $dataset['nama_kementerian'] ?? 'Kementerian Agama' }}</p>
                            @if (! empty($dataset['nama_kantor_kota']))
                                <p class="text-sm font-bold uppercase">{{ $dataset['nama_kantor_kota'] }}</p>
                            @endif
                            @if (! empty($dataset['nama_kantor']))
                                <p class="text-sm font-bold uppercase">{{ $dataset['nama_kantor'] }}</p>
                            @endif
                            @if (! empty($dataset['alamat_kantor']))
                                <p class="text-xs">{{ $dataset['alamat_kantor'] }}</p>
                            @endif
                            @if (! empty($dataset['telepon_kantor']) || ! empty($dataset['email_kantor']))
                                <p class="text-xs">
                                    @if (! empty($dataset['telepon_kantor']))
                                        Telp: {{ $dataset['telepon_kantor'] }}
                                    @endif
                                    @if (! empty($dataset['telepon_kantor']) && ! empty($dataset['email_kantor']))
                                        |
                                    @endif
                                    @if (! empty($dataset['email_kantor']))
                                        Email: {{ $dataset['email_kantor'] }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    @endif
                    <hr class="border-t border-gray-900 mb-1">
                @endif

                <h1 class="text-lg font-bold text-center">REKAP PENDAFTARAN NIKAH/RUJUK</h1>
                <div class="mt-2 mb-3 text-sm">
                    <p>Bulan : {{ $bulanName }}</p>
                    <p>Tahun : {{ $tahunName }}</p>
                </div>
            </div>

            <table class="w-full border-collapse border border-gray-700" style="font-size: 10px;">
                <thead>
                    <tr class="bg-gray-100">
                        @foreach ($columns as $col)
                            @if ($col['type'] === 'row_number')
                                <th rowspan="{{ $col['rowspan'] ?? 1 }}" class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $col['label'] ?? '#' }}</th>
                            @elseif ($col['type'] === 'group')
                                <th colspan="{{ $col['colspan'] ?? 1 }}" class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $col['label'] ?? '' }}</th>
                            @elseif ($col['type'] === 'field')
                                <th rowspan="{{ $col['rowspan'] ?? 1 }}" class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $col['label'] ?? $col['field'] ?? '' }}</th>
                            @endif
                        @endforeach
                    </tr>
                    <tr class="bg-gray-100">
                        @foreach ($columns as $col)
                            @if ($col['type'] === 'group')
                                @foreach ($col['children'] ?? [] as $child)
                                    <th class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $child['label'] ?? $child['field'] ?? '' }}</th>
                                @endforeach
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dataset['rows'] as $rowIndex => $row)
                        <tr>
                            @foreach ($columns as $col)
                                @if ($col['type'] === 'row_number')
                                    <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $rowIndex + 1 }}</td>
                                @elseif ($col['type'] === 'group')
                                    @foreach ($col['children'] ?? [] as $child)
                                        @php
                                            $cellData = $getDataCells($child);
                                            $value = $row[$cellData[0]] ?? '';
                                            if ($cellData[1] !== null) {
                                                $value = $applyTransform($value, $cellData[1]);
                                            }
                                        @endphp
                                        <td class="border border-gray-700 px-1 py-0.5">{{ $value }}</td>
                                    @endforeach
                                @elseif ($col['type'] === 'field')
                                    @php
                                        $cellData = $getDataCells($col);
                                        $value = $row[$cellData[0]] ?? '';
                                        if ($cellData[1] !== null) {
                                            $value = $applyTransform($value, $cellData[1]);
                                        }
                                    @endphp
                                    <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 text-sm leading-relaxed">
                <p>Pada hari ini <strong>{{ $hariName }}</strong>, tanggal <strong>{{ $tanggalFormatted }}</strong>, buku rekap pendaftaran di tutup dengan keadaan sebagai berikut :</p>
                <p class="mt-2 ml-4">Jumlah Nikah Kantor : <strong>{{ $countK }}</strong> N</p>
                <p class="ml-4">Jumlah Nikah Luar Kantor : <strong>{{ $countLK }}</strong> N</p>
                <p class="ml-4">Jumlah Keseluruhan : <strong>{{ $countAll }}</strong> N</p>

                <div class="mt-6 flex justify-end">
                    <div class="text-center">
                        <p>{{ $dataset['kecamatan'] ?? '-' }}, {{ $tanggalFormatted }}</p>
                        <p>Kepala KUA {{ $dataset['kecamatan'] ?? '' }}</p>
                        <div class="h-16"></div>
                        <p class="font-semibold">{{ $dataset['nama_kepala_kua'] ?? '-' }}</p>
                        <p>NIP {{ $dataset['nip_kepala'] ?? '-' }}</p>
                    </div>
                </div>
            </div>

        @else
            <div class="border-b-2 border-gray-900 pb-3 mb-4">
                <h1 class="text-2xl font-bold">{{ $dataset['title'] }}</h1>
                <p class="text-sm text-gray-500">Total {{ number_format($dataset['total']) }} baris &middot; Dibuat {{ $dataset['generated_at'] }}</p>
            </div>
            <table class="w-full text-sm border-collapse border border-gray-700">
                <thead>
                    <tr class="bg-gray-100">
                        @foreach ($dataset['headings'] as $heading)
                            <th class="border border-gray-700 px-1 py-0.5 text-left font-semibold">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dataset['rows'] as $row)
                        <tr>
                            @foreach ($dataset['headings'] as $heading)
                                <td class="border border-gray-700 px-1 py-0.5">{{ $row[$heading] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>
</body>
</html>
