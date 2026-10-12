<?php

namespace App\Http\Controllers;

use App\Exports\ImportDataExport;
use App\Models\Import;
use App\Models\ImportData;
use App\Services\ReportGenerationService;
use App\Services\RusakDetectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataController extends Controller
{
    use AuthorizesRequests;

    private const MONTH_NAMES = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function index(Request $request): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->withCount('importData')
            ->latest()
            ->get();

        $grouped = $imports->groupBy('table_name')->map(function ($rows, $tableName) {
            return [
                'table_name' => $tableName,
                'total_rows' => $rows->sum('import_data_count'),
                'import_ids' => $rows->pluck('id')->toArray(),
            ];
        })->values();

        $selectedGroup = null;
        if ($request->filled('import_id')) {
            $import = Import::where('user_id', auth()->id())
                ->findOrFail($request->integer('import_id'));
            $selectedGroup = $grouped->firstWhere('table_name', $import->table_name);
        }

        return view('data.index', compact('grouped', 'selectedGroup'));
    }

    public function pelaksanaanKantor(): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->whereIn('table_name', ['laporan peristiwa nikah', 'laporan pendaftaran nikah'])
            ->get();

        $importIds = $imports->pluck('id')->toArray();

        return view('data.pelaksanaan-kantor', compact('importIds'));
    }

    public function pelaksanaanLuarKantor(): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->whereIn('table_name', ['laporan peristiwa nikah', 'laporan pendaftaran nikah'])
            ->get();

        $importIds = $imports->pluck('id')->toArray();

        return view('data.pelaksanaan-luar-kantor', compact('importIds'));
    }

    public function duplikat(): View
    {
        $importIds = Import::where('user_id', auth()->id())
            ->where('table_name', 'laporan model l3')
            ->pluck('id')
            ->toArray();

        return view('data.duplikat', compact('importIds'));
    }

    public function bukanDuplikat(): View
    {
        $importIds = Import::where('user_id', auth()->id())
            ->where('table_name', 'laporan model l3')
            ->pluck('id')
            ->toArray();

        return view('data.bukan-duplikat', compact('importIds'));
    }

    public function rusak(Request $request): View
    {
        $importIds = Import::where('user_id', auth()->id())
            ->where('table_name', 'laporan model l3')
            ->pluck('id')
            ->toArray();

        $rows = $importIds === [] ? [] : ImportData::whereIn('import_id', $importIds)
            ->get(['row_data'])
            ->pluck('row_data')
            ->all();

        $missingByPrefix = app(RusakDetectionService::class)->detectMissing($rows);

        $detected = [];
        foreach ($missingByPrefix as $prefix => $numbers) {
            foreach ($numbers as $number) {
                $detected[] = ['prefix' => $prefix, 'number' => $number];
            }
        }

        usort($detected, fn (array $a, array $b) => [$a['prefix'], $a['number']] <=> [$b['prefix'], $b['number']]);

        $page = max(1, $request->integer('page'));
        $missingRows = new LengthAwarePaginator(
            array_slice($detected, ($page - 1) * 50, 50),
            count($detected),
            50,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return view('data.rusak', [
            'importIds' => $importIds,
            'missingRows' => $missingRows,
            'totalRusak' => count($detected),
            'prefixCount' => count($missingByPrefix),
        ]);
    }

    public function buktiBayar(Request $request): View
    {
        $importIds = $this->pdkImportIds(auth()->id());
        $pelaksanaan = $this->pdkLuarMap(auth()->id());

        $simponiIds = Import::where('user_id', auth()->id())
            ->where('table_name', 'like', '%simponi%')
            ->whereIn('status', ['success', 'appended'])
            ->pluck('id')
            ->toArray();

        $semuaSimponi = collect($simponiIds === [] ? [] : ImportData::whereIn('import_id', $simponiIds)
            ->get(['row_data'])
            ->pluck('row_data')
            ->filter(fn ($row) => is_array($row)))
            ->map(fn (array $row) => [
                'no_billing' => trim((string) ($row['No Billing'] ?? '')),
                'tanggal_billing' => trim((string) ($row['Tanggal Billing'] ?? '')),
                'nama' => trim((string) ($row['Nama Wajib Bayar'] ?? '')),
                'nominal' => trim((string) ($row['Nominal Setor'] ?? '')),
                'no_pendaftaran' => trim((string) ($row['No Pendaftaran'] ?? '')),
                'no_ntb' => trim((string) ($row['No NTB'] ?? '')),
            ])
            ->filter(fn (array $row) => $row['no_billing'] !== '')
            ->values();

        $billings = $semuaSimponi
            ->filter(fn (array $row) => $pelaksanaan->has($row['no_pendaftaran']))
            ->map(function (array $row) use ($pelaksanaan) {
                $row['tanggal_pelaksanaan'] = $pelaksanaan->get($row['no_pendaftaran'])['tanggal'];

                return $row;
            })
            ->values();

        $tanpaPadanan = $semuaSimponi->count() - $billings->count();

        $tahunTersedia = $billings
            ->filter(fn (array $row) => $row['tanggal_pelaksanaan'] !== null)
            ->map(fn (array $row) => $row['tanggal_pelaksanaan']->year)
            ->unique()
            ->sortDesc()
            ->values();

        $bulan = $request->integer('bulan') ?: null;
        $tahun = $request->integer('tahun') ?: null;
        $q = trim((string) $request->query('q', ''));

        $billings = $billings->filter(function (array $row) use ($bulan, $tahun) {
            if ($bulan === null && $tahun === null) {
                return true;
            }

            $tanggal = $row['tanggal_pelaksanaan'];
            if ($tanggal === null) {
                return false;
            }

            return ($bulan === null || $tanggal->month === $bulan)
                && ($tahun === null || $tanggal->year === $tahun);
        });

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $billings = $billings->filter(fn (array $row) => mb_strpos(mb_strtolower($row['no_billing']), $needle) !== false
                || mb_strpos(mb_strtolower($row['nama']), $needle) !== false);
        }

        $billings = $billings
            ->sort(fn (array $a, array $b) => [$a['tanggal_pelaksanaan']?->getTimestamp() ?? PHP_INT_MAX, $a['no_billing']]
                <=> [$b['tanggal_pelaksanaan']?->getTimestamp() ?? PHP_INT_MAX, $b['no_billing']])
            ->values();

        $daftarSalin = $billings
            ->unique('no_billing')
            ->map(fn (array $row) => $row['nama'] !== '' ? $row['no_billing'].' - '.$row['nama'] : $row['no_billing'])
            ->values()
            ->all();

        $page = max(1, $request->integer('page'));
        $rows = new LengthAwarePaginator(
            array_slice($billings->all(), ($page - 1) * 50, 50),
            $billings->count(),
            50,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
        $rows->appends($request->query());

        return view('data.bukti-bayar', [
            'importIds' => $importIds,
            'billings' => $rows,
            'totalBillings' => $billings->count(),
            'tanpaPadanan' => $tanpaPadanan,
            'daftarSalin' => $daftarSalin,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'q' => $q,
            'tahunTersedia' => $tahunTersedia,
        ]);
    }

    private function parseTanggalPelaksanaan(string $value): ?Carbon
    {
        foreach (['d-m-Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date;
                }
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        return null;
    }

    private function pdkImportIds(int $userId): array
    {
        return Import::where('user_id', $userId)
            ->where('table_name', 'laporan pendaftaran nikah')
            ->whereIn('status', ['success', 'appended'])
            ->pluck('id')
            ->toArray();
    }

    private function pdkLuarMap(int $userId): Collection
    {
        $importIds = $this->pdkImportIds($userId);

        return collect($importIds === [] ? [] : ImportData::whereIn('import_id', $importIds)
            ->get(['row_data'])
            ->pluck('row_data')
            ->filter(fn ($row) => is_array($row)))
            ->filter(fn (array $row) => mb_stripos((string) ($row['Nikah Di'] ?? ''), 'BEDOL') !== false)
            ->mapWithKeys(function (array $row) {
                $nd = trim((string) ($row['Nomor Daftar'] ?? ''));

                return $nd === '' ? [] : [$nd => [
                    'tanggal' => $this->parseTanggalPelaksanaan((string) ($row['Tanggal Nikah'] ?? '')),
                    'suami' => trim((string) ($row['Nama Suami'] ?? '')),
                    'istri' => trim((string) ($row['Nama Istri'] ?? '')),
                ]];
            });
    }

    public function suratTugas(Request $request): View
    {
        $importIds = $this->pdkImportIds(auth()->id());

        $rows = $this->pdkLuarMap(auth()->id())
            ->map(function (array $info, string $nd) {
                return [
                    'nomor_daftar' => $nd,
                    'tanggal_pelaksanaan' => $info['tanggal'],
                    'suami' => $info['suami'],
                    'istri' => $info['istri'],
                ];
            });

        $tahunTersedia = $rows
            ->filter(fn (array $row) => $row['tanggal_pelaksanaan'] !== null)
            ->map(fn (array $row) => $row['tanggal_pelaksanaan']->year)
            ->unique()
            ->sortDesc()
            ->values();

        $bulan = $request->integer('bulan') ?: null;
        $tahun = $request->integer('tahun') ?: null;
        $q = trim((string) $request->query('q', ''));

        $rows = $rows->filter(function (array $row) use ($bulan, $tahun) {
            if ($bulan === null && $tahun === null) {
                return true;
            }

            $tanggal = $row['tanggal_pelaksanaan'];
            if ($tanggal === null) {
                return false;
            }

            return ($bulan === null || $tanggal->month === $bulan)
                && ($tahun === null || $tanggal->year === $tahun);
        });

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(fn (array $row) => mb_strpos(mb_strtolower($row['nomor_daftar']), $needle) !== false
                || mb_strpos(mb_strtolower($row['suami']), $needle) !== false
                || mb_strpos(mb_strtolower($row['istri']), $needle) !== false);
        }

        $rows = $rows
            ->sort(fn (array $a, array $b) => [$a['tanggal_pelaksanaan']?->getTimestamp() ?? PHP_INT_MAX, $a['nomor_daftar']]
                <=> [$b['tanggal_pelaksanaan']?->getTimestamp() ?? PHP_INT_MAX, $b['nomor_daftar']])
            ->values();

        $daftarSalin = $rows->map(function (array $row) {
            $nama = match (true) {
                $row['suami'] !== '' && $row['istri'] !== '' => $row['suami'].' dan '.$row['istri'],
                default => $row['suami'].$row['istri'],
            };

            return $nama !== '' ? $row['nomor_daftar'].' - '.$nama : $row['nomor_daftar'];
        })->values()->all();

        $page = max(1, $request->integer('page'));
        $paginator = new LengthAwarePaginator(
            array_slice($rows->all(), ($page - 1) * 50, 50),
            $rows->count(),
            50,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
        $paginator->appends($request->query());

        return view('data.surat-tugas', [
            'importIds' => $importIds,
            'rows' => $paginator,
            'totalRows' => $rows->count(),
            'daftarSalin' => $daftarSalin,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'q' => $q,
            'tahunTersedia' => $tahunTersedia,
        ]);
    }

    public function skp(Request $request): View
    {
        $mode = $request->query('mode', 'bulanan') === 'kuartal' ? 'kuartal' : 'bulanan';

        $bulan = $request->integer('bulan') ?: (int) now()->month;
        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) now()->month;
        }

        $tahun = $request->integer('tahun') ?: (int) now()->year;
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) now()->year;
        }

        if ($mode === 'kuartal') {
            $kuartal = (int) ceil($bulan / 3);
            $bulanAwal = ($kuartal - 1) * 3 + 1;
            $start = Carbon::create($tahun, $bulanAwal, 1)->startOfDay();
            $end = Carbon::create($tahun, $bulanAwal + 2, 1)->endOfMonth()->endOfDay();
            $periodeLabel = self::MONTH_NAMES[$bulanAwal - 1].'–'.self::MONTH_NAMES[$bulanAwal + 1].' '.$tahun;
        } else {
            $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();
            $periodeLabel = self::MONTH_NAMES[$bulan - 1].' '.$tahun;
        }

        return view('data.skp', [
            'stats' => $this->skpStats(auth()->id(), $start, $end),
            'mode' => $mode,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periodeLabel' => $periodeLabel,
            'tahunTersedia' => range((int) now()->year, max((int) now()->year - 5, 2020)),
        ]);
    }

    private function skpStats(int $userId, Carbon $start, Carbon $end): array
    {
        $stats = [
            'pendaftaran' => 0,
            'pelaksanaan_kantor' => 0,
            'pelaksanaan_luar' => 0,
            'duplikat' => 0,
        ];

        $rawRows = DB::table('import_data')
            ->join('imports', 'imports.id', '=', 'import_data.import_id')
            ->where('imports.user_id', $userId)
            ->whereIn('imports.status', ['success', 'appended'])
            ->where(function ($query) {
                $query->where('imports.table_name', 'like', '%peristiwa nikah%')
                    ->orWhere('imports.table_name', 'like', '%pendaftaran nikah%')
                    ->orWhere('imports.table_name', 'like', '%model l3%');
            })
            ->orderBy('import_data.row_number')
            ->select('imports.table_name', 'import_data.import_id', 'import_data.row_data')
            ->get();

        $pnRows = [];
        $pdkRows = [];
        $l3Rows = [];
        foreach ($rawRows as $raw) {
            $row = json_decode((string) $raw->row_data, true);
            if (! is_array($row)) {
                continue;
            }
            if (str_contains((string) $raw->table_name, 'peristiwa nikah')) {
                $pnRows[] = ['import_id' => (int) $raw->import_id, 'row' => $row];
            } elseif (str_contains((string) $raw->table_name, 'pendaftaran nikah')) {
                $pdkRows[] = ['import_id' => (int) $raw->import_id, 'row' => $row];
            } else {
                $l3Rows[] = $row;
            }
        }

        $pnRows = $this->skpDedupNewestWins($pnRows);
        $pdkRows = $this->skpDedupNewestWins($pdkRows);

        $pdkByNomorDaftar = [];
        foreach ($pdkRows as $row) {
            $nd = trim((string) ($row['Nomor Daftar'] ?? ''));
            if ($nd !== '') {
                $pdkByNomorDaftar[$nd] = $row;
            }
        }

        $inRange = fn (?Carbon $date) => $date !== null && $date->betweenIncluded($start, $end);

        foreach ($pnRows as $row) {
            if (! $inRange($this->skpParseDate($row['Tanggal Nikah'] ?? null))) {
                continue;
            }
            $pdk = $pdkByNomorDaftar[trim((string) ($row['Nomor Daftar'] ?? ''))] ?? null;
            $isLuar = $pdk !== null && $this->skpIsLuarKantor($pdk);
            if ($isLuar) {
                $stats['pelaksanaan_luar']++;
            } else {
                $stats['pelaksanaan_kantor']++;
            }
        }

        foreach ($pdkRows as $row) {
            if ($inRange($this->skpParseDate($row['Tanggal Daftar'] ?? null))) {
                $stats['pendaftaran']++;
            }
        }

        $seenPerforasi = [];
        foreach ($l3Rows as $row) {
            if (mb_strtolower(trim((string) ($row['Keterangan'] ?? ''))) !== 'duplikat') {
                continue;
            }
            if (! $inRange($this->skpParseDate($row['Tanggal Cetak'] ?? null))) {
                continue;
            }
            $perforasi = trim((string) ($row['Nomor Perforasi'] ?? ''));
            if ($perforasi !== '') {
                if (isset($seenPerforasi[$perforasi])) {
                    continue;
                }
                $seenPerforasi[$perforasi] = true;
            }
            $stats['duplikat']++;
        }

        return $stats;
    }

    /**
     * @param  array<int, array{import_id: int, row: array<string, mixed>}>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function skpDedupNewestWins(array $rows): array
    {
        $newest = [];
        $tanpaNomor = [];

        foreach ($rows as $item) {
            $nd = trim((string) ($item['row']['Nomor Daftar'] ?? ''));

            if ($nd === '') {
                $tanpaNomor[] = $item['row'];

                continue;
            }

            if (! isset($newest[$nd]) || $item['import_id'] >= $newest[$nd]['import_id']) {
                $newest[$nd] = $item;
            }
        }

        return array_merge(array_column($newest, 'row'), $tanpaNomor);
    }

    private function skpParseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function skpIsLuarKantor(array $row): bool
    {
        $nikahDi = mb_strtoupper(trim((string) ($row['Nikah Di'] ?? '')));

        return str_contains($nikahDi, 'LUAR') || str_contains($nikahDi, 'BEDOL');
    }

    public function show(ImportData $record): View
    {
        $this->authorize('view', $record->import);

        $record->load('import');

        return view('data.show', compact('record'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'import_id' => 'required|integer',
            'search' => 'nullable|string|max:255',
            'filter_column' => 'nullable|string|max:255',
            'filter_value' => 'nullable|string|max:255',
            'sort_column' => 'nullable|string|max:255',
            'sort_direction' => 'nullable|in:asc,desc',
        ]);

        $import = Import::where('user_id', auth()->id())->findOrFail($validated['import_id']);

        $headings = $import->availableColumns();

        $dataset = app(ReportGenerationService::class)->buildDataset(
            $import,
            $headings,
            $validated['search'] ?? null,
            $validated['filter_column'] ?? null,
            $validated['filter_value'] ?? null,
            $validated['sort_column'] ?? null,
            $validated['sort_direction'] ?? 'asc',
        );

        $filename = 'data_import_'.$import->id.'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ImportDataExport($headings, $dataset['rows']), $filename);
    }

    public function cetakNb(Request $request): View
    {
        $validated = $request->validate([
            'import_id' => 'required|integer',
            'record' => 'nullable|integer',
        ]);

        $import = Import::where('user_id', auth()->id())->findOrFail($validated['import_id']);
        $this->authorize('view', $import);

        $allIds = ImportData::where('import_id', $import->id)
            ->orderBy('row_number')
            ->pluck('id')
            ->all();

        if ($allIds === []) {
            abort(404, 'Tidak ada data untuk import ini.');
        }

        $recordId = $validated['record'] ?? $allIds[0];

        $importData = ImportData::where('id', $recordId)
            ->where('import_id', $import->id)
            ->first();

        if (! $importData) {
            abort(404, 'Record tidak ditemukan.');
        }

        $recordData = $importData->row_data ?? [];
        $currentIndex = array_search($recordId, $allIds, true);

        return view('reports.cetak-nb', [
            'report' => null,
            'importId' => $import->id,
            'recordData' => $recordData,
            'prevId' => $currentIndex > 0 ? $allIds[$currentIndex - 1] : null,
            'nextId' => $currentIndex < count($allIds) - 1 ? $allIds[$currentIndex + 1] : null,
            'currentPosition' => $currentIndex + 1,
            'totalRecords' => count($allIds),
            'entryMode' => 'data',
        ]);
    }
}
