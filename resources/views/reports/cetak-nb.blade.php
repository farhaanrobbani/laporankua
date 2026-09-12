<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak NB — {{ $report->title }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page { size: A4; margin: 15mm 18mm; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .page { page-break-after: always; }
            .page:last-child { page-break-after: auto; }
        }

        @media screen {
            body { background: #e5e7eb; font-family: 'Times New Roman', Times, serif; }
            .no-print {
                position: fixed; top: 0; left: 0; right: 0; z-index: 10;
                background: #fff; border-bottom: 1px solid #d1d5db;
                padding: 10px 20px; display: flex; align-items: center; justify-content: space-between;
            }
            .no-print a, .no-print button {
                padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none;
            }
            .no-print .nav-btn { background: #e5e7eb; color: #374151; border: 1px solid #d1d5db; }
            .no-print .nav-btn:hover { background: #d1d5db; }
            .no-print .nav-btn:disabled { opacity: 0.4; cursor: not-allowed; }
            .no-print .print-btn { background: #2563eb; color: #fff; border: none; }
            .no-print .print-btn:hover { background: #1d4ed8; }
            .no-print .close-btn { background: #6b7280; color: #fff; border: none; }
            .no-print .close-btn:hover { background: #4b5563; }
            .no-print .record-info { font-size: 14px; color: #6b7280; }
            .page {
                max-width: 210mm; min-height: 297mm; margin: 60px auto 20px; background: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,.15); padding: 15mm 18mm; position: relative;
            }
        }

        @media screen and (max-width: 800px) {
            .page { margin: 60px 10px 20px; min-height: auto; padding: 10mm; }
        }

        .field { position: absolute; }
        .field-label { font-weight: bold; font-size: 14px; }
        .field-value { font-size: 14px; }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="flex gap-2">
            @if ($prevId !== null)
                <a href="{{ route('reports.cetak-nb', ['report' => $report->id, 'record' => $prevId]) }}" class="nav-btn">&larr; Sebelumnya</a>
            @else
                <button class="nav-btn" disabled>&larr; Sebelumnya</button>
            @endif

            <span class="record-info">Record {{ $currentPosition }} dari {{ $totalRecords }}</span>

            @if ($nextId !== null)
                <a href="{{ route('reports.cetak-nb', ['report' => $report->id, 'record' => $nextId]) }}" class="nav-btn">Berikutnya &rarr;</a>
            @else
                <button class="nav-btn" disabled>Berikutnya &rarr;</button>
            @endif
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="print-btn">Print / Simpan PDF</button>
            <button onclick="window.close()" class="close-btn">Tutup</button>
        </div>
    </div>

    <div class="page">
        <div class="field" style="top: 30mm; left: 0;">
            <span class="field-label">Nomor Akta:</span>
            <span class="field-value">{{ $recordData['Nomor Akta Nikah'] ?? '-' }}</span>
        </div>

        <div class="field" style="top: 120mm; right: 0;">
            <span class="field-value">{{ $recordData['No Porforasi Suami'] ?? '-' }} & {{ $recordData['No Porforasi Istri'] ?? '-' }}</span>
        </div>
    </div>
</body>
</html>
