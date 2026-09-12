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
            @page { size: A4; margin: 15mm; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
        @media screen {
            .print-sheet { max-width: 210mm; margin: 1.5rem auto; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.15); padding: 15mm; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="no-print max-w-3xl mx-auto mt-4 flex gap-3 justify-end">
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">Print / Simpan PDF</button>
        <button onclick="window.history.back()" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-semibold rounded-md hover:bg-gray-300">Tutup</button>
    </div>

    <div class="print-sheet">
        <div class="border-b-2 border-gray-900 pb-3 mb-4">
            <h1 class="text-2xl font-bold">{{ $dataset['title'] }}</h1>
            <p class="text-sm text-gray-500">Total {{ number_format($dataset['total']) }} baris &middot; Dibuat {{ $dataset['generated_at'] }}</p>
        </div>

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
            @endphp
            <table class="w-full text-sm border-collapse border border-gray-700">
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
        @else
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

        <p class="mt-4 text-xs text-gray-500">Halaman dicetak dari aplikasi Laporan pada {{ $dataset['generated_at'] }}.</p>
    </div>
</body>
</html>
