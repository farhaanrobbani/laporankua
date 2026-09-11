<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report->title }} — Cetak NB</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { size: A4; margin: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e5e5e5;
        }

        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .a4-page { box-shadow: none !important; margin: 0 !important; }
        }

        @media screen {
            .no-print {
                position: fixed; top: 12px; right: 12px; z-index: 100;
                display: flex; gap: 8px;
            }
            .a4-page {
                width: 210mm; height: 297mm;
                background: white;
                margin: 20px auto;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                position: relative;
                overflow: hidden;
            }
        }

        .field-value {
            position: absolute;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">
            Print / Simpan PDF
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">
            Tutup
        </button>
    </div>

    <div class="a4-page">
        @foreach ($layoutFields as $field)
            <div class="field-value"
                 style="left: {{ $field['x'] }}mm; top: {{ $field['y'] }}mm; font-size: {{ $field['font_size'] }}px; {{ $field['bold'] ? 'font-weight: bold;' : '' }}">
                {{ $recordData[$field['column']] ?? '' }}
            </div>
        @endforeach
    </div>
</body>
</html>
