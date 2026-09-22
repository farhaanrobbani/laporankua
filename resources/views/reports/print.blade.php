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
        <button onclick="window.location='{{ route('reports.show', $report) }}'" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-semibold rounded-md hover:bg-gray-300">Tutup</button>
    </div>

    <div class="print-sheet overflow-x-auto">
        @if (! empty($dataset['table_layout']))
            @php
                $layout = $dataset['table_layout'];
                $columns = $layout['columns'] ?? [];
                $l2Title = 'LAPORAN';

                // Aggregation: group data if configured
                if (! empty($layout['aggregation']) && isset($layout['aggregation']['group_by']) && ($layout['type'] ?? '') !== 'grouped_detail' && ($layout['type'] ?? '') !== 'laporan_na') {
                    $groupBy = $layout['aggregation']['group_by'];
                    $grouped = [];
                    foreach ($dataset['rows'] as $row) {
                        $key = $row[$groupBy] ?? 'Lainnya';
                        if (! isset($grouped[$key])) {
                            $grouped[$key] = [];
                        }
                        $grouped[$key][] = $row;
                    }

                    // Collect aggregate columns from layout (recursively, with parent group label)
                    $collectAggCols = function ($cols, $parentLabel = null) use (&$collectAggCols) {
                        $result = [];
                        foreach ($cols as $col) {
                            if ($col['type'] === 'aggregate_count' || $col['type'] === 'aggregate_total' || $col['type'] === 'aggregate_range') {
                                $compositeKey = $parentLabel ? $parentLabel . '|' . ($col['label'] ?? '') : ($col['label'] ?? '');
                                $result[] = array_merge($col, ['_key' => $compositeKey, '_parent' => $parentLabel]);
                            }
                            if (isset($col['children'])) {
                                $childParent = $col['label'] ?? $parentLabel;
                                $result = array_merge($result, $collectAggCols($col['children'], $childParent));
                            }
                        }
                        return $result;
                    };
                    $aggCols = $collectAggCols($columns);

                    foreach ($aggCols as $col) {
                        $field = $col['field'] ?? '';
                        if (str_contains($field, 'Pendidikan')) {
                            $l2Title = 'LAPORAN PENDIDIKAN PENGANTIN';
                            break;
                        }
                        if (str_contains($field, 'Usia')) {
                            $l2Title = 'LAPORAN USIA PENGANTIN';
                            break;
                        }
                    }

                    // Build aggregated rows
                    $staticValues = $dataset['daftar_desa'] ?? $layout['aggregation']['static_values'] ?? null;
                    $groupNames = $staticValues ?? array_keys($grouped);
                    sort($groupNames);

                    $aggRows = [];
                    foreach ($groupNames as $groupName) {
                        $rows = $grouped[$groupName] ?? [];
                        $aggRow = [$groupBy => $groupName];
                        foreach ($aggCols as $col) {
                            $key = $col['_key'];
                            if ($col['type'] === 'aggregate_total') {
                                $aggRow[$key] = count($rows);
                            } elseif ($col['type'] === 'aggregate_count') {
                                $count = 0;
                                $field = $col['field'] ?? '';
                                $matchList = $col['match'] ?? [];
                                foreach ($rows as $row) {
                                    $val = mb_strtolower((string) ($row[$field] ?? ''));
                                    foreach ($matchList as $m) {
                                        if (str_contains($val, mb_strtolower($m))) {
                                            $count++;
                                            break;
                                        }
                                    }
                                }
                                $aggRow[$key] = $count;
                            } elseif ($col['type'] === 'aggregate_range') {
                                $count = 0;
                                $field = $col['field'] ?? '';
                                $min = $col['min'] ?? null;
                                $max = $col['max'] ?? null;
                                foreach ($rows as $row) {
                                    $val = (int) ($row[$field] ?? 0);
                                    if ($min !== null && $val < $min) {
                                        continue;
                                    }
                                    if ($max !== null && $val >= $max) {
                                        continue;
                                    }
                                    $count++;
                                }
                                $aggRow[$key] = $count;
                            }
                        }
                        $aggRows[] = $aggRow;
                    }
                    $dataset['rows'] = $aggRows;

                    // Hitung total row untuk laporan L2
                    $totalRow = [$groupBy => 'Jumlah'];
                    foreach ($aggCols as $col) {
                        $key = $col['_key'];
                        $sum = 0;
                        foreach ($aggRows as $r) {
                            $sum += (int) ($r[$key] ?? 0);
                        }
                        $totalRow[$key] = $sum;
                    }
                }

                $applyTransform = function ($value, $transform) {
                    if ($transform === 'klinik_balai_nikah') {
                        $lower = mb_strtolower((string) $value);
                        return str_contains($lower, 'bedol') ? 'LK' : 'K';
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
                $nikahDiField = null;
                foreach ($columns as $col) {
                    if ($col['type'] === 'field' && ($col['field'] ?? '') === 'Tanggal Nikah') {
                        $tanggalNikahField = $col['field'];
                    }
                    if ($col['type'] === 'field' && ($col['field'] ?? '') === 'Nikah Di') {
                        $nikahDiField = $col['field'];
                    }
                    if ($col['type'] === 'group') {
                        foreach ($col['children'] ?? [] as $child) {
                            if (($child['field'] ?? '') === 'Tanggal Nikah') {
                                $tanggalNikahField = $child['field'];
                            }
                            if (($child['field'] ?? '') === 'Nikah Di') {
                                $nikahDiField = $child['field'];
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
                    if ($nikahDiField !== null) {
                        $nikahDi = mb_strtolower((string) ($row[$nikahDiField] ?? ''));
                        if (str_contains($nikahDi, 'bedol')) {
                            $countLK++;
                        } else {
                            $countK++;
                        }
                    }
                    if ($tanggalNikahField !== null && ! empty($row[$tanggalNikahField])) {
                        try {
                            $date = \Carbon\Carbon::parse($row[$tanggalNikahField]);
                            if ($lastDate === null || $date->gt($lastDate)) {
                                $lastDate = $date;
                            }
                        } catch (\Exception $e) {}
                    }
                }

                $countAll = $countK + $countLK;
                $hariName = $lastDate ? $monthDays[$lastDate->dayOfWeek] : '-';
                $tanggalFormatted = $lastDate ? $lastDate->day . ' ' . $monthNames[$lastDate->month] . ' ' . $lastDate->year : '-';
                $bulanName = $dataset['bulan_override'] ?? ($lastDate ? $monthNames[$lastDate->month] : '-');
                $tahunName = $dataset['tahun_override'] ?? ($lastDate ? $lastDate->year : '-');
                $isL2Report = !empty($dataset['table_layout']['aggregation']) && ($layout['type'] ?? '') !== 'grouped_detail' && ($layout['type'] ?? '') !== 'formulir' && ($layout['type'] ?? '') !== 'laporan_na';
                $isL4Report = ($layout['type'] ?? '') === 'grouped_detail';
                $isL3Report = ($layout['type'] ?? '') === 'formulir';
                $isLaporanNA = ($layout['type'] ?? '') === 'laporan_na';
                $isL5Report = false;
                foreach ($columns as $col) {
                    if (($col['type'] ?? '') === 'group' && ($col['label'] ?? '') === 'Memiliki Sertifikat Suscatin') {
                        $isL5Report = true;
                        break;
                    }
                }

                $l4Groups = [];
                if ($isL4Report) {
                    $groupBy = $layout['aggregation']['group_by'] ?? null;
                    if ($groupBy) {
                        $grouped = [];
                        foreach ($dataset['rows'] as $row) {
                            $key = $row[$groupBy] ?? 'Lainnya';
                            if (! isset($grouped[$key])) {
                                $grouped[$key] = [];
                            }
                            $grouped[$key][] = $row;
                        }
                        ksort($grouped);

                        foreach ($grouped as &$gRows) {
                            usort($gRows, function ($a, $b) {
                                $dateA = strtotime($a['Tanggal dan Jam Setor'] ?? '') ?: 0;
                                $dateB = strtotime($b['Tanggal dan Jam Setor'] ?? '') ?: 0;

                                return $dateA - $dateB;
                            });
                        }
                        unset($gRows);

                        $rowNum = 1;
                        foreach ($grouped as $groupName => $rows) {
                            $l4Groups[] = [
                                'name' => $groupName,
                                'count' => count($rows),
                                'rows' => $rows,
                                'startRowNum' => $rowNum,
                            ];
                            $rowNum += count($rows);
                        }
                    }

                }
            @endphp

            <div class="mb-0">
                @if ($isL2Report && ! $isL5Report)
                    <div class="flex items-start mb-3">
                        <span style="font-size: 21px; font-weight: bold;">L2</span>
                        <div class="text-center flex-1">
                            <h1 class="font-bold uppercase" style="font-size: 14px;">{{ $l2Title }}</h1>
                            <p class="uppercase" style="font-size: 12px;">KANTOR URUSAN AGAMA KECAMATAN {{ strtoupper($dataset['kecamatan'] ?? '') }}</p>
                            <p style="font-size: 12px;">BULAN {{ strtoupper($bulanName) }} TAHUN {{ $tahunName }}</p>
                        </div>
                    </div>
                @elseif ($isL5Report)
                    <div class="flex items-start mb-3">
                        <span style="font-size: 21px; font-weight: bold;">L5</span>
                        <div class="text-center flex-1">
                            <h1 class="font-bold uppercase" style="font-size: 14px;">LAPORAN</h1>
                            <h2 class="font-bold uppercase" style="font-size: 13px;">KURSUS CALON PENGANTIN</h2>
                            <p class="uppercase" style="font-size: 12px;">KANTOR URUSAN AGAMA KECAMATAN {{ strtoupper($dataset['kecamatan'] ?? '') }}</p>
                            <p style="font-size: 12px;">BULAN {{ strtoupper($bulanName) }} TAHUN {{ $tahunName }}</p>
                        </div>
                    </div>
                @elseif ($isL3Report)
                    <div class="flex items-start mb-3">
                        <span style="font-size: 21px; font-weight: bold;">L3</span>
                        <div class="text-center flex-1">
                            <h1 class="font-bold uppercase" style="font-size: 14px;">LAPORAN</h1>
                            <h2 class="font-bold uppercase" style="font-size: 13px;">FORMULIR PERKAWINAN ATAU RUJUK</h2>
                            <p class="uppercase" style="font-size: 12px;">KANTOR URUSAN AGAMA KECAMATAN {{ strtoupper($dataset['kecamatan'] ?? '') }}</p>
                            <p style="font-size: 12px;">BULAN {{ strtoupper($bulanName) }} TAHUN {{ $tahunName }}</p>
                        </div>
                    </div>
                @elseif ($isLaporanNA)
                    <div class="mb-3">
                        <h1 class="font-bold uppercase" style="font-size: 14px; text-align: left; padding-left: 40px;">BUKU STOK KHUSUS</h1>
                        <p style="font-size: 12px; padding-left: 40px;">Bulan : {{ strtoupper($bulanName) }}</p>
                        <div style="font-size: 12px; padding-left: 40px; display: flex; gap: 20px;">
                            <span>Tahun : {{ $tahunName }}</span>
                            <span>Model : NA</span>
                        </div>
                    </div>
                @elseif ($isL4Report)
                    <div class="flex items-start mb-3">
                        <span style="font-size: 21px; font-weight: bold;">L4</span>
                        <div class="text-center flex-1">
                            <h1 class="font-bold uppercase" style="font-size: 14px;">LAPORAN</h1>
                            <h2 class="font-bold uppercase" style="font-size: 13px;">PNPB NIKAH ATAU RUJUK</h2>
                            <p class="uppercase" style="font-size: 12px;">KANTOR URUSAN AGAMA KECAMATAN {{ strtoupper($dataset['kecamatan'] ?? '') }}</p>
                            <p style="font-size: 12px;">BULAN {{ strtoupper($bulanName) }} TAHUN {{ $tahunName }}</p>
                        </div>
                    </div>
                @else
                    @if (! empty($dataset['nama_kementerian']) || ! empty($dataset['nama_kantor']))
                        @if (! empty($dataset['logo_kantor']))
                            <table class="w-full mb-1" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="align-top pr-1" style="width: 80px;">
                                        <img src="{{ asset('storage/' . $dataset['logo_kantor']) }}" alt="Logo" style="margin-left: 64px; max-height: 80px; max-width: 80px;" />
                                    </td>
                                    <td class="text-center">
                                        <p class="font-bold uppercase" style="font-size: {{ $dataset['font_size_kop_kementerian'] ?? '12' }}px;">{{ $dataset['nama_kementerian'] ?? 'Kementerian Agama' }}</p>
                                        @if (! empty($dataset['nama_kantor_kota']))
                                            <p class="font-bold uppercase" style="font-size: {{ $dataset['font_size_kop_kantor_kota'] ?? '12' }}px;">{{ $dataset['nama_kantor_kota'] }}</p>
                                        @endif
                                        @if (! empty($dataset['nama_kantor']))
                                            <p class="font-bold uppercase" style="font-size: {{ $dataset['font_size_kop_kantor'] ?? '12' }}px;">{{ $dataset['nama_kantor'] }}</p>
                                        @endif
                                        @if (! empty($dataset['alamat_kantor']))
                                            <p style="font-size: {{ $dataset['font_size_kop_alamat'] ?? '10' }}px;">{{ $dataset['alamat_kantor'] }}</p>
                                        @endif
                                        @if (! empty($dataset['telepon_kantor']) || ! empty($dataset['email_kantor']))
                                            <p style="font-size: {{ $dataset['font_size_kop_kontak'] ?? '10' }}px;">
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
                                <p class="font-bold uppercase" style="font-size: {{ $dataset['font_size_kop_kementerian'] ?? '12' }}px;">{{ $dataset['nama_kementerian'] ?? 'Kementerian Agama' }}</p>
                                @if (! empty($dataset['nama_kantor_kota']))
                                    <p class="font-bold uppercase" style="font-size: {{ $dataset['font_size_kop_kantor_kota'] ?? '12' }}px;">{{ $dataset['nama_kantor_kota'] }}</p>
                                @endif
                                @if (! empty($dataset['nama_kantor']))
                                    <p class="font-bold uppercase" style="font-size: {{ $dataset['font_size_kop_kantor'] ?? '12' }}px;">{{ $dataset['nama_kantor'] }}</p>
                                @endif
                                @if (! empty($dataset['alamat_kantor']))
                                    <p style="font-size: {{ $dataset['font_size_kop_alamat'] ?? '10' }}px;">{{ $dataset['alamat_kantor'] }}</p>
                                @endif
                                @if (! empty($dataset['telepon_kantor']) || ! empty($dataset['email_kantor']))
                                    <p style="font-size: {{ $dataset['font_size_kop_kontak'] ?? '10' }}px;">
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

                    <h1 class="font-bold text-center" style="font-size: 14px;">REKAP PENDAFTARAN NIKAH/RUJUK</h1>
                    <div class="mt-2 mb-3" style="font-size: 12px;">
                        <p><span style="display:inline-block; width:5.5ch; font-weight: bold;">Bulan</span> : {{ $bulanName }}</p>
                        <p><span style="display:inline-block; width:5.5ch; font-weight: bold;">Tahun</span> : {{ $tahunName }}</p>
                    </div>
                @endif
            </div>

            <table class="w-full border-collapse border border-gray-700" style="font-size: 12px;">
                <thead>
                    <tr class="bg-gray-100">
                    @foreach ($columns as $col)
                        @if ($col['type'] === 'row_number')
                            <th rowspan="{{ $col['rowspan'] ?? 1 }}" @if(!empty($col['width']))style="max-width:{{ $col['width'] }};width:{{ $col['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $col['label'] ?? '#' }}</th>
                        @elseif ($col['type'] === 'group')
                            <th colspan="{{ $col['colspan'] ?? 1 }}" class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $col['label'] ?? '' }}</th>
                        @elseif ($col['type'] === 'field' || $col['type'] === 'aggregate_total' || $col['type'] === 'aggregate_count' || $col['type'] === 'aggregate_range' || $col['type'] === 'static' || $col['type'] === 'static_value' || $col['type'] === 'manual')
                            <th rowspan="{{ $col['rowspan'] ?? 1 }}" @if(!empty($col['width']))style="max-width:{{ $col['width'] }};width:{{ $col['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $col['label'] ?? $col['field'] ?? '' }}</th>
                        @endif
                    @endforeach
                    </tr>
                    <tr class="bg-gray-100">
                    @foreach ($columns as $col)
                        @if ($col['type'] === 'group')
                            @foreach ($col['children'] ?? [] as $child)
                                @if ($child['type'] === 'group')
                                    <th colspan="{{ $child['colspan'] ?? 1 }}" class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $child['label'] ?? '' }}</th>
                                @else
                                    <th @if(!empty($child['width']))style="max-width:{{ $child['width'] }};width:{{ $child['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $child['label'] ?? $child['field'] ?? '' }}</th>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                    </tr>
                    @php
                        // Third header row: render children of nested groups
                        $hasNestedGroups = false;
                        foreach ($columns as $col) {
                            if ($col['type'] === 'group') {
                                foreach ($col['children'] ?? [] as $child) {
                                    if ($child['type'] === 'group') {
                                        $hasNestedGroups = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    @endphp
                    @if ($hasNestedGroups)
                    <tr class="bg-gray-100">
                        @foreach ($columns as $col)
                            @if ($col['type'] === 'group')
                                @foreach ($col['children'] ?? [] as $child)
                                    @if ($child['type'] === 'group')
                                        @foreach ($child['children'] ?? [] as $grandchild)
                                            <th @if(!empty($grandchild['width']))style="max-width:{{ $grandchild['width'] }};width:{{ $grandchild['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center font-semibold">{{ $grandchild['label'] ?? $grandchild['field'] ?? '' }}</th>
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </tr>
                    @endif
                </thead>
                <tbody style="font-size: 11px;">
                    @if ($isL4Report)
                        @php $l4GroupNum = 1; @endphp
                        @foreach ($l4Groups as $group)
                            @foreach ($group['rows'] as $i => $row)
                                <tr>
                                    @foreach ($columns as $col)
                                        @if ($col['type'] === 'row_number')
                                            @if ($i === 0)
                                                <td rowspan="{{ $group['count'] }}" class="border border-gray-700 px-1 py-0.5 text-center">{{ $l4GroupNum++ }}</td>
                                            @endif
                                        @elseif ($col['type'] === 'field')
                                            @if (($col['field'] ?? '') === ($layout['aggregation']['group_by'] ?? ''))
                                                @if ($i === 0)
                                                    <td rowspan="{{ $group['count'] }}" class="border border-gray-700 px-1 py-0.5 text-center">{{ $group['name'] }}</td>
                                                @endif
                                            @else
                                                @php
                                                    $value = $row[$col['field']] ?? '';
                                                    if (($col['format'] ?? '') === 'date_id' && $value !== '' && $value !== null) {
                                                        try {
                                                            $d = \Carbon\Carbon::parse($value);
                                                            $value = $d->format('d-m-Y');
                                                        } catch (\Exception $e) {}
                                                    }
                                                @endphp
                                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                            @endif
                                        @elseif ($col['type'] === 'aggregate_total')
                                            @if ($i === 0)
                                                <td rowspan="{{ $group['count'] }}" class="border border-gray-700 px-1 py-0.5 text-center">{{ $group['count'] }}</td>
                                            @endif
                                        @elseif ($col['type'] === 'static')
                                            <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $col['value'] ?? '' }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                            @if ($group['count'] === 0)
                                <tr>
                                    @foreach ($columns as $col)
                                        @if ($col['type'] === 'row_number')
                                            <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $l4GroupNum++ }}</td>
                                        @elseif ($col['type'] === 'field')
                                            @if (($col['field'] ?? '') === ($layout['aggregation']['group_by'] ?? ''))
                                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $group['name'] }}</td>
                                            @else
                                                <td class="border border-gray-700 px-1 py-0.5 text-center"></td>
                                            @endif
                                        @elseif ($col['type'] === 'aggregate_total')
                                            <td class="border border-gray-700 px-1 py-0.5 text-center">0</td>
                                        @elseif ($col['type'] === 'static')
                                            <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $col['value'] ?? '' }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                        @php
                            $l4TotalPerkawinan = 0;
                            foreach ($l4Groups as $g) {
                                $l4TotalPerkawinan += $g['count'];
                            }
                            $l4TotalSetor = $l4TotalPerkawinan * 600000;
                        @endphp
                        <tr style="font-weight: bold;">
                            @foreach ($columns as $col)
                                @if ($col['type'] === 'row_number')
                                    <td class="border border-gray-700 px-1 py-0.5 text-center"></td>
                                @elseif ($col['type'] === 'field')
                                    @if (($col['field'] ?? '') === ($layout['aggregation']['group_by'] ?? ''))
                                        <td class="border border-gray-700 px-1 py-0.5 text-center">Jumlah</td>
                                    @else
                                        <td class="border border-gray-700 px-1 py-0.5 text-center"></td>
                                    @endif
                                @elseif ($col['type'] === 'aggregate_total')
                                    <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $l4TotalPerkawinan }}</td>
                                @elseif ($col['type'] === 'static')
                                    <td class="border border-gray-700 px-1 py-0.5 text-center">{{ number_format($l4TotalSetor, 0, ',', '.') }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @elseif ($isL3Report)
                        @php
                            $manualRaw = $dataset['config_json']['manual_data'] ?? null;
                            $manualData = is_array($manualRaw) && isset($manualRaw['rows']) ? $manualRaw['rows'] : $manualRaw;
                            $staticRows = $dataset['config_json']['table_layout']['static_rows'] ?? $layout['static_rows'] ?? [];
                        @endphp
                        @foreach ($staticRows as $sr)
                            @php
                                $idx = $sr['row_num'] - 1;
                                $md = is_array($manualData) ? ($manualData[$idx] ?? null) : null;
                            @endphp
                            <tr>
                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $sr['row_num'] }}</td>
                                <td class="border border-gray-700 px-1 py-0.5">{{ $md['formulir'] ?? $sr['formulir'] ?? '' }}</td>
                                {{-- Masuk --}}
                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $md['masuk_jumlah'] ?? '' }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $md['masuk_seri'] ?? '' }}</td>
                                {{-- Keluar --}}
                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $md['keluar_jumlah'] ?? '' }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $md['keluar_seri'] ?? '' }}</td>
                                {{-- Sisa --}}
                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $md['sisa_jumlah'] ?? '' }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center">{{ $md['sisa_seri'] ?? '' }}</td>
                                {{-- Keterangan --}}
                                <td class="border border-gray-700 px-1 py-0.5">{{ $md['keterangan'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    @elseif ($isLaporanNA)
                        @php
                            $manualRaw = $dataset['config_json']['manual_data'] ?? null;
                            $sisaBLRaw = is_array($manualRaw) ? ($manualRaw['sisa_bulan_lalu'] ?? []) : [];
                            $sisaBLEntries = [];
                            if (! empty($sisaBLRaw)) {
                                if (isset($sisaBLRaw['masuk']) || isset($sisaBLRaw['seri_dari'])) {
                                    $sisaBLEntries = [$sisaBLRaw];
                                } else {
                                    $sisaBLEntries = is_array($sisaBLRaw) ? $sisaBLRaw : [];
                                }
                            }
                            $prefixLength = 6;
                            $runningSisa = [];
                            foreach ($sisaBLEntries as $sbl) {
                                $sblMasuk = (int) ($sbl['masuk'] ?? 0);
                                $sblKeluar = (int) ($sbl['keluar'] ?? 0);
                                $sblSisa = max(0, $sblMasuk - $sblKeluar);
                                $sblSeri = preg_replace('/\s*-\s*\d+$/', '', preg_replace('/^JT\s*/i', '', trim((string) ($sbl['seri_dari'] ?? ''))));
                                $pf = substr($sblSeri, 0, $prefixLength);
                                $runningSisa[$pf] = $sblSisa;
                            }
                            $rows = $dataset['rows'] ?? [];
                            $grouped = [];
                            foreach ($rows as $row) {
                                $key = $row['Tanggal Cetak'] ?? 'Lainnya';
                                try { $key = \Carbon\Carbon::parse($key)->format('Y-m-d'); } catch (\Exception $e) {}
                                if (! isset($grouped[$key])) {
                                    $grouped[$key] = [];
                                }
                                $grouped[$key][] = $row;
                            }
                            ksort($grouped);
                            $dateSisaBD = [];
                            $dateSisaD = [];
                            foreach ($grouped as $dateKey => $dateRows) {
                                $byPrefix = [];
                                foreach ($dateRows as $r) {
                                    $p = preg_replace('/\s*-\s*\d+$/', '', preg_replace('/^JT\s*/i', '', trim((string) ($r['Nomor Perforasi'] ?? ''))));
                                    $pf = substr((string) $p, 0, $prefixLength);
                                    if (! isset($byPrefix[$pf])) {
                                        $byPrefix[$pf] = [];
                                    }
                                    $byPrefix[$pf][] = $r;
                                }
                                $dateSisaBD[$dateKey] = [];
                                $dateSisaD[$dateKey] = [];
                                foreach ($byPrefix as $pf => $pfRows) {
                                    $bdCount = count(array_values(array_filter($pfRows, fn ($r) => mb_strtolower($r['Keterangan'] ?? '') !== 'duplikat')));
                                    $dCount = count(array_values(array_filter($pfRows, fn ($r) => mb_strtolower($r['Keterangan'] ?? '') === 'duplikat')));
                                    if (! isset($runningSisa[$pf])) {
                                        $runningSisa[$pf] = 0;
                                    }
                                    $runningSisa[$pf] -= $bdCount;
                                    $dateSisaBD[$dateKey][$pf] = max(0, $runningSisa[$pf]);
                                    $runningSisa[$pf] -= $dCount;
                                    $dateSisaD[$dateKey][$pf] = max(0, $runningSisa[$pf]);
                                }
                            }
                            ksort($grouped);
                            $rowNum = 1;
                        @endphp
                        {{-- Sisa Bulan Lalu rows --}}
                        @foreach ($sisaBLEntries as $sblEntry)
                            @php
                                $sblMasuk = (int) ($sblEntry['masuk'] ?? 0);
                                $sblKeluar = (int) ($sblEntry['keluar'] ?? 0);
                                $sblSisa = max(0, $sblMasuk - $sblKeluar);
                                $sblDari = preg_replace('/^JT\s*/i', '', trim((string) ($sblEntry['seri_dari'] ?? '')));
                                $sblSampai = preg_replace('/^JT\s*/i', '', trim((string) ($sblEntry['seri_sampai'] ?? '')));
                                $sblSeriDisplay = $sblDari ? 'JT '.$sblDari.($sblSampai && $sblSampai !== $sblDari ? ' - '.$sblSampai : '') : '';
                            @endphp
                            <tr>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $rowNum++ }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">01/{{ str_pad((string) ($dataset['filter_month'] ?? ''), 2, '0', STR_PAD_LEFT) }}/{{ $dataset['filter_year'] ?? '' }}</td>
                                <td class="border border-gray-700 px-1 py-0.5" style="font-size:10px;">Sisa bulan lalu</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $sblEntry['masuk'] ?? '' }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $sblEntry['keluar'] ?? '' }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $sblSisa }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">NA</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $sblSeriDisplay }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">Buku</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $sblEntry['penerimaan'] ?? '' }}</td>
                                <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $sblEntry['pengeluaran'] ?? '' }}</td>
                            </tr>
                        @endforeach
                        @foreach ($grouped as $dateKey => $dateRows)
                            @php
                                $byPrefix = [];
                                foreach ($dateRows as $r) {
                                    $p = preg_replace('/\s*-\s*\d+$/', '', preg_replace('/^JT\s*/i', '', trim((string) ($r['Nomor Perforasi'] ?? ''))));
                                    $pf = substr((string) $p, 0, $prefixLength);
                                    if (! isset($byPrefix[$pf])) {
                                        $byPrefix[$pf] = ['bd' => [], 'd' => []];
                                    }
                                    if (mb_strtolower($r['Keterangan'] ?? '') === 'duplikat') {
                                        $byPrefix[$pf]['d'][] = $r;
                                    } else {
                                        $byPrefix[$pf]['bd'][] = $r;
                                    }
                                }
                                $dateDisplay = $dateKey;
                                try { $dateDisplay = \Carbon\Carbon::parse($dateKey)->format('d/m/Y'); } catch (\Exception $e) {}
                            @endphp
                            @foreach ($byPrefix as $pf => $pfData)
                                @php
                                    $bukanDuplikat = $pfData['bd'];
                                    $duplikat = $pfData['d'];
                                    $keluarBD = count($bukanDuplikat);
                                    $keluarD = count($duplikat);
                                    $sisaBD = $dateSisaBD[$dateKey][$pf] ?? 0;
                                    $sisaD = $dateSisaD[$dateKey][$pf] ?? 0;
                                    $porforasiNums = array_map(function ($r) {
                                        $p = preg_replace('/\s*-\s*\d+$/', '', preg_replace('/^JT\s*/i', '', trim((string) ($r['Nomor Perforasi'] ?? ''))));
                                        return (int) $p;
                                    }, $bukanDuplikat);
                                    $porforasiNums = array_filter($porforasiNums);
                                    $seriRange = $porforasiNums ? 'JT '.min($porforasiNums).' - '.max($porforasiNums) : '';
                                    $aktaList = array_filter(array_map(fn ($r) => $r['_nomor_akta'] ?? null, $bukanDuplikat));
                                    $aktaNums = array_map(fn ($a) => (int) $a, $aktaList);
                                    $aktaMin = $aktaNums ? min($aktaNums) : null;
                                    $aktaMax = $aktaNums ? max($aktaNums) : null;
                                    $aktaRange = $aktaMin !== null ? ($aktaMin === $aktaMax ? (string) $aktaMin : $aktaMin.' - '.$aktaMax) : '';
                                    $uraian = '';
                                    if ($keluarBD > 0) {
                                        $names = [];
                                        foreach ($bukanDuplikat as $bd) {
                                            $n = trim($bd['Nama Catin'] ?? '');
                                            if ($n !== '' && ! in_array($n, $names)) {
                                                $names[] = $n;
                                            }
                                        }
                                        $uraian = count($names) > 1 ? $names[0].' Cs.' : ($names[0] ?? '');
                                    }
                                    $dupliPorforasiNums = array_map(function ($r) {
                                        $p = preg_replace('/\s*-\s*\d+$/', '', preg_replace('/^JT\s*/i', '', trim((string) ($r['Nomor Perforasi'] ?? ''))));
                                        return (int) $p;
                                    }, $duplikat);
                                    $dupliPorforasiNums = array_filter($dupliPorforasiNums);
                                    $dupliSeriRange = '';
                                    if (count($dupliPorforasiNums) === 1) {
                                        $dupliSeriRange = 'JT '.reset($dupliPorforasiNums);
                                    } elseif (count($dupliPorforasiNums) > 1) {
                                        $dupliSeriRange = 'JT '.min($dupliPorforasiNums).' - '.max($dupliPorforasiNums);
                                    }
                                @endphp
                                @if ($keluarBD > 0)
                                    {{-- Bukan Duplikat row --}}
                                    <tr>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $rowNum++ }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5" style="font-size:10px;">{{ $dateDisplay }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5" style="font-size:10px;">{{ $uraian }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;"></td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $keluarBD ?: '' }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px; font-weight:bold;">{{ $sisaBD < 0 ? 0 : $sisaBD }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">NA</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $seriRange }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">Buku</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;"></td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $aktaRange }}</td>
                                    </tr>
                                @endif
                                @if ($keluarD > 0)
                                    {{-- Duplikat row --}}
                                    <tr>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $rowNum++ }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5" style="font-size:10px;">{{ $dateDisplay }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5" style="font-size:10px;">Duplikat</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;"></td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $keluarD }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px; font-weight:bold;">{{ $sisaD < 0 ? 0 : $sisaD }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">NA</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">{{ $dupliSeriRange }}</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;">Buku</td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;"></td>
                                        <td class="border border-gray-700 px-1 py-0.5 text-center" style="font-size:10px;"></td>
                                    </tr>
                                @endif
                            @endforeach
                        @endforeach
                    @else
                        @foreach ($dataset['rows'] as $rowIndex => $row)
                            <tr>
                                @foreach ($columns as $col)
                                    @if ($col['type'] === 'row_number')
                                        <td @if(!empty($col['width']))style="max-width:{{ $col['width'] }};width:{{ $col['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">{{ $rowIndex + 1 }}</td>
                                    @elseif ($col['type'] === 'group')
                                        @foreach ($col['children'] ?? [] as $child)
                                            @if ($child['type'] === 'group')
                                                @foreach ($child['children'] ?? [] as $grandchild)
                                                    @php
                                                        $compositeKey = ($child['label'] ?? '') . '|' . ($grandchild['label'] ?? '');
                                                        $value = $row[$compositeKey] ?? $row[$grandchild['label']] ?? $row[$grandchild['field'] ?? ''] ?? '';
                                                    @endphp
                                                    <td @if(!empty($grandchild['width']))style="max-width:{{ $grandchild['width'] }};width:{{ $grandchild['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                                @endforeach
                                            @elseif ($child['type'] === 'aggregate_count' || $child['type'] === 'aggregate_total' || $child['type'] === 'aggregate_range')
                                                @php
                                                    $compositeKey = ($col['label'] ?? '') . '|' . ($child['label'] ?? '');
                                                    $value = $row[$compositeKey] ?? $row[$child['label']] ?? '';
                                                @endphp
                                                <td @if(!empty($child['width']))style="max-width:{{ $child['width'] }};width:{{ $child['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                            @else
                                                @php
                                                    $cellData = $getDataCells($child);
                                                    $value = $row[$cellData[0]] ?? '';
                                                    if ($cellData[1] !== null) {
                                                        $value = $applyTransform($value, $cellData[1]);
                                                    }
                                                @endphp
                                                <td @if(!empty($child['width']))style="max-width:{{ $child['width'] }};width:{{ $child['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 @if(($child['align'] ?? '') === 'center')text-center @endif">{{ $value }}</td>
                                            @endif
                                        @endforeach
                                    @elseif ($col['type'] === 'field')
                                        @php
                                            $cellData = $getDataCells($col);
                                            $value = $row[$cellData[0]] ?? '';
                                            if ($cellData[1] !== null) {
                                                $value = $applyTransform($value, $cellData[1]);
                                            }
                                        @endphp
                                        <td @if(!empty($col['width']))style="max-width:{{ $col['width'] }};width:{{ $col['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                    @elseif ($col['type'] === 'aggregate_total' || $col['type'] === 'aggregate_count' || $col['type'] === 'aggregate_range')
                                        @php
                                            $value = $row[$col['label']] ?? '';
                                        @endphp
                                        <td @if(!empty($col['width']))style="max-width:{{ $col['width'] }};width:{{ $col['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @endif
            @if ($isL2Report)
                        <tr style="font-weight: bold;">
                            @foreach ($columns as $col)
                                @if ($col['type'] === 'row_number')
                                    <td @if(!empty($col['width']))style="max-width:{{ $col['width'] }};width:{{ $col['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center"></td>
                                @elseif ($col['type'] === 'group')
                                    @foreach ($col['children'] ?? [] as $child)
                                        @if ($child['type'] === 'group')
                                            @foreach ($child['children'] ?? [] as $grandchild)
                                                @php
                                                    $compositeKey = ($child['label'] ?? '') . '|' . ($grandchild['label'] ?? '');
                                                    $value = $totalRow[$compositeKey] ?? 0;
                                                @endphp
                                                <td @if(!empty($grandchild['width']))style="max-width:{{ $grandchild['width'] }};width:{{ $grandchild['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                            @endforeach
                                        @elseif ($child['type'] === 'aggregate_count' || $child['type'] === 'aggregate_total' || $child['type'] === 'aggregate_range')
                                            @php
                                                $compositeKey = ($col['label'] ?? '') . '|' . ($child['label'] ?? '');
                                                $value = $totalRow[$compositeKey] ?? 0;
                                            @endphp
                                            <td @if(!empty($child['width']))style="max-width:{{ $child['width'] }};width:{{ $child['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                        @else
                                            <td @if(!empty($child['width']))style="max-width:{{ $child['width'] }};width:{{ $child['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center"></td>
                                        @endif
                                    @endforeach
                                @elseif ($col['type'] === 'field')
                                    <td @if(!empty($col['width']))style="max-width:{{ $col['width'] }};width:{{ $col['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">Jumlah</td>
                                @elseif ($col['type'] === 'aggregate_total' || $col['type'] === 'aggregate_count' || $col['type'] === 'aggregate_range')
                                    @php
                                        $value = $totalRow[$col['label']] ?? 0;
                                    @endphp
                                    <td @if(!empty($col['width']))style="max-width:{{ $col['width'] }};width:{{ $col['width'] }};"@endif class="border border-gray-700 px-1 py-0.5 text-center">{{ $value }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endif
                </tbody>
            </table>

            @if ($isL2Report)
                <div class="mt-6 leading-relaxed" style="font-size: 12px;">
                    <div class="flex justify-end">
                        <div class="text-center">
                            <p>{{ $dataset['kecamatan'] ?? '-' }}, {{ \Carbon\Carbon::now()->day . ' ' . $monthNames[\Carbon\Carbon::now()->month] . ' ' . \Carbon\Carbon::now()->year }}</p>
                            <p>Kepala KUA {{ $dataset['kecamatan'] ?? '' }}</p>
                            <div class="h-16"></div>
                            <p class="font-semibold">{{ $dataset['nama_kepala_kua'] ?? '-' }}</p>
                            <p>NIP {{ $dataset['nip_kepala'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            @elseif ($isL4Report)
                <div class="mt-6 leading-relaxed" style="font-size: 12px;">
                    <div class="flex justify-end">
                        <div class="text-center">
                            <p>{{ $dataset['kecamatan'] ?? '-' }}, {{ \Carbon\Carbon::now()->day . ' ' . $monthNames[\Carbon\Carbon::now()->month] . ' ' . \Carbon\Carbon::now()->year }}</p>
                            <p>Kepala KUA {{ $dataset['kecamatan'] ?? '' }}</p>
                            <div class="h-16"></div>
                            <p class="font-semibold">{{ $dataset['nama_kepala_kua'] ?? '-' }}</p>
                            <p>NIP {{ $dataset['nip_kepala'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            @elseif ($isL3Report)
                <div class="mt-6 leading-relaxed" style="font-size: 12px;">
                    <div class="flex justify-end">
                        <div class="text-center">
                            <p>{{ $dataset['kecamatan'] ?? '-' }}, {{ \Carbon\Carbon::now()->day . ' ' . $monthNames[\Carbon\Carbon::now()->month] . ' ' . \Carbon\Carbon::now()->year }}</p>
                            <p>Kepala KUA {{ $dataset['kecamatan'] ?? '' }}</p>
                            <div class="h-16"></div>
                            <p class="font-semibold">{{ $dataset['nama_kepala_kua'] ?? '-' }}</p>
                            <p>NIP {{ $dataset['nip_kepala'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            @elseif ($isLaporanNA)
                <div class="mt-6 leading-relaxed" style="font-size: 12px;">
                    <div class="flex justify-end">
                        <div class="text-center">
                            <p>{{ $dataset['kecamatan'] ?? '-' }}, {{ \Carbon\Carbon::now()->day . ' ' . $monthNames[\Carbon\Carbon::now()->month] . ' ' . \Carbon\Carbon::now()->year }}</p>
                            <p>Kepala KUA {{ $dataset['kecamatan'] ?? '' }}</p>
                            <div class="h-16"></div>
                            <p class="font-semibold">{{ $dataset['nama_kepala_kua'] ?? '-' }}</p>
                            <p>NIP {{ $dataset['nip_kepala'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-4 leading-relaxed" style="font-size: 12px;">
                    <p>Pada hari ini <strong>{{ $hariName }}</strong>, tanggal <strong>{{ $tanggalFormatted }}</strong>, buku rekap pendaftaran di tutup dengan keadaan sebagai berikut :</p>
                    <p class="mt-2 ml-4"><span style="display:inline-block; width:25ch;">Jumlah Nikah Kantor</span> : <strong>{{ $countK }}</strong> N</p>
                    <p class="ml-4"><span style="display:inline-block; width:25ch;">Jumlah Nikah Luar Kantor</span> : <strong>{{ $countLK }}</strong> N</p>
                    <p class="ml-4"><span style="display:inline-block; width:25ch;">Jumlah Keseluruhan</span> : <strong>{{ $countAll }}</strong> N</p>

                    <div class="mt-6 flex justify-end">
                        <div class="text-center">
                            <p>{{ $dataset['kecamatan'] ?? '-' }}, {{ \Carbon\Carbon::now()->day . ' ' . $monthNames[\Carbon\Carbon::now()->month] . ' ' . \Carbon\Carbon::now()->year }}</p>
                            <p>Kepala KUA {{ $dataset['kecamatan'] ?? '' }}</p>
                            <div class="h-16"></div>
                            <p class="font-semibold">{{ $dataset['nama_kepala_kua'] ?? '-' }}</p>
                            <p>NIP {{ $dataset['nip_kepala'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            @endif

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
