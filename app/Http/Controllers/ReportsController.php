<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\Report;
use App\Services\MergeService;
use App\Services\ReportGenerationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $reports = Report::where('user_id', auth()->id())
            ->with('import')
            ->latest()
            ->paginate(10);

        return view('reports.index', compact('reports'));
    }

    public function create(): View
    {
        return view('reports.create');
    }

    public function show(Report $report): View
    {
        $this->authorize('view', $report);

        $report->load(['import', 'reportTemplate']);

        $firstRecord = ImportData::where('import_id', $report->import_id)
            ->orderBy('row_number')
            ->first();

        return view('reports.show', compact('report', 'firstRecord'));
    }

    public function download(Report $report): StreamedResponse|SymfonyRedirectResponse
    {
        $this->authorize('view', $report);

        if (! $report->file_path || ! Storage::disk('local')->exists($report->file_path)) {
            return redirect()->route('reports.show', $report)
                ->with('status', 'File laporan belum tersedia.');
        }

        $extensions = ['pdf' => 'pdf', 'word' => 'docx', 'excel' => 'xlsx'];
        $extension = $extensions[$report->output_format] ?? 'bin';

        if ($report->status !== 'downloaded') {
            $report->update(['status' => 'downloaded']);
        }

        return Storage::disk('local')->download(
            $report->file_path,
            $report->title.'.'.$extension
        );
    }

    public function print(Report $report, ReportGenerationService $service): View
    {
        $this->authorize('view', $report);

        $config = $report->config_json ?? [];
        $fields = array_values(array_filter($config['fields'] ?? []));

        $sortColumn = $config['sort_column'] ?? $report->import?->default_sort_column;
        $sortDirection = $config['sort_direction'] ?? $report->import?->default_sort_direction ?? 'asc';

        $filterMonth = $config['filter_month'] ?? null;
        $filterYear = $config['filter_year'] ?? null;
        $hasDateFilter = ($filterMonth !== null && $filterMonth !== '') || ($filterYear !== null && $filterYear !== '');

        if (! empty($config['is_merged']) && ! empty($config['merged_import_ids'])) {
            $mergeService = app(MergeService::class);
            $effectiveFields = $fields;
            if (! empty($config['table_layout'])) {
                $allColumns = $mergeService->getAllColumns($config['merged_import_ids']);
                $effectiveFields = array_values(array_unique(array_merge($fields, $allColumns)));
            }
            if ($hasDateFilter && ! in_array('Tanggal Nikah', $effectiveFields, true)) {
                $allCols = $mergeService->getAllColumns($config['merged_import_ids']);
                if (in_array('Tanggal Nikah', $allCols, true)) {
                    $effectiveFields[] = 'Tanggal Nikah';
                }
            }
            if (($config['table_layout']['type'] ?? '') === 'laporan_na' && ! in_array('Tanggal Cetak', $effectiveFields, true)) {
                $effectiveFields[] = 'Tanggal Cetak';
            }
            if (in_array(($config['table_layout']['type'] ?? ''), ['formulir', 'laporan_na'], true)) {
                $dataset = $mergeService->buildConcatDataset(
                    $config['merged_import_ids'],
                    $effectiveFields,
                    $config['search'] ?? null,
                    $config['filter_column'] ?? null,
                    $config['filter_value'] ?? null,
                    $sortColumn,
                    $sortDirection,
                );
            } else {
                $dataset = $mergeService->buildMergedDataset(
                    $config['merged_import_ids'],
                    $effectiveFields,
                    $config['search'] ?? null,
                    $config['filter_column'] ?? null,
                    $config['filter_value'] ?? null,
                    $sortColumn,
                    $sortDirection,
                );
            }
        } elseif (! empty($config['merged_import_ids'])) {
            $mergeService = app(MergeService::class);
            $allColumns = $mergeService->getAllColumns($config['merged_import_ids']);
            $effectiveFields = $fields === [] ? $allColumns : $fields;
            if ($hasDateFilter && ! in_array('Tanggal Nikah', $effectiveFields, true) && ($config['table_layout']['type'] ?? '') !== 'laporan_na') {
                if (in_array('Tanggal Nikah', $allColumns, true)) {
                    $effectiveFields[] = 'Tanggal Nikah';
                }
            }
            if (($config['table_layout']['type'] ?? '') === 'laporan_na' && ! in_array('Tanggal Cetak', $effectiveFields, true)) {
                $effectiveFields[] = 'Tanggal Cetak';
            }
            $dataset = $mergeService->buildConcatDataset(
                $config['merged_import_ids'],
                $effectiveFields,
                $config['search'] ?? null,
                $config['filter_column'] ?? null,
                $config['filter_value'] ?? null,
                $sortColumn,
                $sortDirection,
            );
        } else {
            $effectiveFields = $fields === [] ? $report->import->availableColumns() : $fields;
            if ($hasDateFilter && ! in_array('Tanggal Nikah', $effectiveFields, true)) {
                $available = $report->import->availableColumns();
                if (in_array('Tanggal Nikah', $available, true)) {
                    $effectiveFields[] = 'Tanggal Nikah';
                }
            }
            $dataset = $service->buildDataset(
                $report->import,
                $effectiveFields,
                $config['search'] ?? null,
                $config['filter_column'] ?? null,
                $config['filter_value'] ?? null,
                $sortColumn,
                $sortDirection,
            );
        }
        $dataset['title'] = $report->title;
        $dataset['table_layout'] = $config['table_layout'] ?? null;

        if (($config['table_layout']['type'] ?? '') === 'laporan_na' && isset($dataset['rows'])) {
            $dataset['rows'] = array_values(array_filter($dataset['rows'], fn ($row) => ! empty($row['Tanggal Cetak'])));
        }

        if (($config['table_layout']['type'] ?? '') === 'laporan_na') {
            $pnImports = Import::where('table_name', 'like', '%peristiwa nikah%')->pluck('id');
            $aktaMap = [];
            if ($pnImports->isNotEmpty()) {
                $pnData = ImportData::whereIn('import_id', $pnImports)
                    ->select('row_data')
                    ->get()
                    ->pluck('row_data');
                foreach ($pnData as $pn) {
                    $nomorAkta = $pn['Nomor Akta Nikah'] ?? '';
                    $suami = trim((string) ($pn['No Porforasi Suami'] ?? ''));
                    $istri = trim((string) ($pn['No Porforasi Istri'] ?? ''));
                    if ($suami !== '') {
                        $aktaMap[$suami] = $nomorAkta;
                    }
                    if ($istri !== '') {
                        $aktaMap[$istri] = $nomorAkta;
                    }
                }
            }
            foreach ($dataset['rows'] as &$row) {
                $namaCatin = $row['Nama Catin'] ?? '';
                $parts = explode(' - ', $namaCatin, 2);
                $row['_nama_suami'] = trim($parts[0] ?? $namaCatin);
                $porforasi = trim((string) ($row['Nomor Perforasi'] ?? ''));
                $row['_nomor_akta'] = $aktaMap[$porforasi] ?? null;
            }
            unset($row);
        }

        if ($hasDateFilter) {
            $tableLayout = $config['table_layout'] ?? [];
            $layoutColumns = $tableLayout['columns'] ?? [];
            $tanggalNikahField = null;
            foreach ($layoutColumns as $col) {
                if (($col['type'] ?? '') === 'field' && ($col['field'] ?? '') === 'Tanggal Nikah') {
                    $tanggalNikahField = $col['field'];
                    break;
                }
                if (($col['type'] ?? '') === 'group') {
                    foreach ($col['children'] ?? [] as $child) {
                        if (($child['field'] ?? '') === 'Tanggal Nikah') {
                            $tanggalNikahField = $child['field'];
                            break 2;
                        }
                    }
                }
            }

            if ($tanggalNikahField === null && in_array('Tanggal Nikah', $effectiveFields, true)) {
                $tanggalNikahField = 'Tanggal Nikah';
            }

            if ($tanggalNikahField === null) {
                $dateFilterField = $tableLayout['aggregation']['date_filter_field'] ?? null;
                if ($dateFilterField && in_array($dateFilterField, $effectiveFields, true)) {
                    $tanggalNikahField = $dateFilterField;
                }
            }

            if (($config['table_layout']['type'] ?? '') === 'laporan_na' && in_array('Tanggal Cetak', $effectiveFields, true)) {
                $tanggalNikahField = 'Tanggal Cetak';
            }

            if ($tanggalNikahField !== null) {
                $dataset['rows'] = array_values(array_filter($dataset['rows'], function ($row) use ($tanggalNikahField, $filterMonth, $filterYear) {
                    $dateVal = $row[$tanggalNikahField] ?? null;
                    if ($dateVal === null || $dateVal === '') {
                        return false;
                    }
                    try {
                        $date = Carbon::parse($dateVal);
                    } catch (\Exception $e) {
                        return false;
                    }
                    if ($filterMonth !== null && $filterMonth !== '' && (int) $date->month !== (int) $filterMonth) {
                        return false;
                    }
                    if ($filterYear !== null && $filterYear !== '' && (int) $date->year !== (int) $filterYear) {
                        return false;
                    }

                    return true;
                }));
            }

            $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            if ($filterMonth !== null && $filterMonth !== '') {
                $dataset['bulan_override'] = $monthNames[(int) $filterMonth] ?? '-';
            }
            if ($filterYear !== null && $filterYear !== '') {
                $dataset['tahun_override'] = $filterYear;
            }
            $dataset['filter_month'] = $filterMonth;
            $dataset['filter_year'] = $filterYear;
        }

        $dataset['kecamatan'] = $config['kecamatan'] ?? null;
        $dataset['nama_kepala_kua'] = $config['nama_kepala_kua'] ?? null;
        $dataset['nip_kepala'] = $config['nip_kepala'] ?? null;
        $dataset['nama_kementerian'] = $config['nama_kementerian'] ?? null;
        $dataset['nama_kantor_kota'] = $config['nama_kantor_kota'] ?? null;
        $dataset['nama_kantor'] = $config['nama_kantor'] ?? null;
        $dataset['alamat_kantor'] = $config['alamat_kantor'] ?? null;
        $dataset['telepon_kantor'] = $config['telepon_kantor'] ?? null;
        $dataset['email_kantor'] = $config['email_kantor'] ?? null;
        $dataset['logo_kantor'] = $config['logo_kantor'] ?? null;
        $dataset['daftar_desa'] = $config['daftar_desa'] ?? null;
        $dataset['font_size_kop_kementerian'] = $config['font_size_kop_kementerian'] ?? null;
        $dataset['font_size_kop_kantor_kota'] = $config['font_size_kop_kantor_kota'] ?? null;
        $dataset['font_size_kop_kantor'] = $config['font_size_kop_kantor'] ?? null;
        $dataset['font_size_kop_alamat'] = $config['font_size_kop_alamat'] ?? null;
        $dataset['font_size_kop_kontak'] = $config['font_size_kop_kontak'] ?? null;
        $dataset['config_json'] = $config;

        if (($config['table_layout']['type'] ?? '') === 'formulir') {
            $dataset['config_json']['manual_data'] = $this->buildL3ManualData($config);
        }
        if (($config['table_layout']['type'] ?? '') === 'laporan_na') {
            $dataset['config_json']['manual_data'] = $this->buildLaporanNaData($config);
        }

        return view('reports.print', compact('dataset', 'report'));
    }

    public function cetakNb(Report $report, int $record): View
    {
        $this->authorize('view', $report);

        $importData = ImportData::where('id', $record)
            ->where('import_id', $report->import_id)
            ->first();

        if (! $importData) {
            abort(404, 'Record tidak ditemukan.');
        }

        $recordData = $importData->row_data ?? [];

        $allIds = ImportData::where('import_id', $report->import_id)
            ->orderBy('row_number')
            ->pluck('id')
            ->all();

        $currentIndex = array_search($record, $allIds, true);

        return view('reports.cetak-nb', [
            'report' => $report,
            'importId' => $report->import_id,
            'records' => [$recordData],
            'recordData' => $recordData,
            'prevId' => $currentIndex > 0 ? $allIds[$currentIndex - 1] : null,
            'nextId' => $currentIndex < count($allIds) - 1 ? $allIds[$currentIndex + 1] : null,
            'currentPosition' => $currentIndex + 1,
            'totalRecords' => count($allIds),
            'entryMode' => 'report',
            'multiMode' => false,
            'showNav' => true,
        ]);
    }

    private function buildL3ManualData(array $config): array
    {
        $importIds = $config['merged_import_ids'] ?? [];
        $savedRows = $config['manual_data']['rows'] ?? [];

        if ($importIds === [] || $savedRows === []) {
            return [
                'rows' => $savedRows,
                'na_versions' => $config['manual_data']['na_versions'] ?? [],
                'static_rows' => $config['table_layout']['static_rows'] ?? $config['manual_data']['static_rows'] ?? [],
            ];
        }

        $prefixLength = $config['table_layout']['na_version_prefix_length'] ?? 6;
        $filterMonth = $config['filter_month'] ?? null;
        $filterYear = $config['filter_year'] ?? null;

        $allRows = ImportData::whereIn('import_id', $importIds)
            ->select('row_data')
            ->get()
            ->pluck('row_data')
            ->toArray();

        $groups = [];
        foreach ($allRows as $row) {
            $nomor = trim((string) ($row['Nomor Perforasi'] ?? ''));
            $nomor = preg_replace('/^JT\s*/i', '', $nomor);
            $nomor = preg_replace('/\s*-\s*\d+$/', '', $nomor);
            if ($nomor === '' || ! preg_match('/^\d+$/', $nomor)) {
                continue;
            }
            $prefix = substr($nomor, 0, $prefixLength);
            if (! isset($groups[$prefix])) {
                $groups[$prefix] = ['filtered_porp' => [], 'count' => 0];
            }
        }

        foreach ($allRows as $row) {
            $nomor = trim((string) ($row['Nomor Perforasi'] ?? ''));
            $nomor = preg_replace('/^JT\s*/i', '', $nomor);
            $nomor = preg_replace('/\s*-\s*\d+$/', '', $nomor);
            if ($nomor === '' || ! preg_match('/^\d+$/', $nomor)) {
                continue;
            }
            $prefix = substr($nomor, 0, $prefixLength);

            $tanggalCetak = $row['Tanggal Cetak'] ?? null;
            if ($tanggalCetak !== null && $tanggalCetak !== '') {
                try {
                    $date = Carbon::parse($tanggalCetak);
                    if ($filterMonth !== null && $filterMonth !== '' && (int) $date->month !== (int) $filterMonth) {
                        continue;
                    }
                    if ($filterYear !== null && $filterYear !== '' && (int) $date->year !== (int) $filterYear) {
                        continue;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (isset($groups[$prefix])) {
                $groups[$prefix]['count']++;
                $groups[$prefix]['filtered_porp'][] = (int) $nomor;
            }
        }

        $naVersions = [];
        foreach ($groups as $prefix => $data) {
            if ($data['count'] === 0) {
                continue;
            }
            $filteredPorp = $data['filtered_porp'];
            $naVersions[] = [
                'prefix' => (string) $prefix,
                'label' => 'Model NA ('.$prefix.')',
                'keluar_jumlah' => $data['count'],
                'min_porp' => (string) min($filteredPorp),
                'max_porp' => (string) max($filteredPorp),
            ];
        }

        $rows = [];
        $naIndex = 0;
        foreach ($savedRows as $saved) {
            $row = $saved;
            $formulir = $saved['formulir'] ?? '';
            $isNaRow = str_starts_with($formulir, 'Model NA');
            $ver = null;

            if ($isNaRow) {
                $ver = $naVersions[$naIndex] ?? null;
                $naIndex++;

                if ($ver) {
                    $row['keluar_jumlah'] = (string) $ver['keluar_jumlah'];
                    $row['keluar_seri_awal'] = $ver['min_porp'];
                    $row['keluar_seri_akhir'] = $ver['max_porp'];
                    $keluarSeri = 'JT '.$ver['min_porp'];
                    if ($ver['min_porp'] !== $ver['max_porp']) {
                        $keluarSeri .= ' - '.$ver['max_porp'];
                    }
                    $row['keluar_seri'] = $keluarSeri;
                } else {
                    $row['keluar_jumlah'] = '0';
                    $row['keluar_seri_awal'] = '';
                    $row['keluar_seri_akhir'] = '';
                    $row['keluar_seri'] = $row['masuk_seri'] ?? '';
                }
            }

            $masuk = (int) ($row['masuk_jumlah'] ?? 0);
            $keluar = (int) ($row['keluar_jumlah'] ?? 0);
            if ($masuk > 0) {
                $row['sisa_jumlah'] = (string) ($masuk - $keluar);
            }

            $masukAkhir = (int) ($row['masuk_seri_akhir'] ?? 0);
            $keluarAkhir = (int) ($row['keluar_seri_akhir'] ?? 0);
            $sisaJumlah = (int) ($row['sisa_jumlah'] ?? 0);
            if ($sisaJumlah > 0 && $keluarAkhir > 0 && $masukAkhir >= $keluarAkhir) {
                $row['sisa_seri_awal'] = (string) ($keluarAkhir + 1);
                $row['sisa_seri_akhir'] = (string) $masukAkhir;
                $sisaSeri = 'JT '.($keluarAkhir + 1);
                if (($keluarAkhir + 1) !== $masukAkhir) {
                    $sisaSeri .= ' - '.$masukAkhir;
                }
                $row['sisa_seri'] = $sisaSeri;
            } elseif ($isNaRow && $ver === null) {
                $row['sisa_jumlah'] = (string) $masuk;
                $row['sisa_seri'] = $row['masuk_seri'] ?? '';
                $row['sisa_seri_awal'] = $row['masuk_seri_awal'] ?? '';
                $row['sisa_seri_akhir'] = $row['masuk_seri_akhir'] ?? '';
            }

            $rows[] = $row;
        }

        $staticRows = [
            ['formulir' => 'Model N', 'row_num' => 1, 'dynamic' => false],
        ];
        $rowNum = 2;
        foreach ($rows as $r) {
            $formulir = $r['formulir'] ?? '';
            if (preg_match('/^Model NA/', $formulir)) {
                $staticRows[] = [
                    'formulir' => $formulir,
                    'row_num' => $rowNum,
                    'dynamic' => true,
                    'dynamic_type' => 'na_version',
                ];
                $rowNum++;
            }
        }
        $staticRows[] = ['formulir' => 'Model DN', 'row_num' => $rowNum, 'dynamic' => false];
        $rowNum++;
        $staticRows[] = ['formulir' => 'Model NB', 'row_num' => $rowNum, 'dynamic' => false];

        return [
            'rows' => $rows,
            'na_versions' => $naVersions,
            'static_rows' => $staticRows,
        ];
    }

    private function buildLaporanNaData(array $config): array
    {
        $saved = $config['manual_data'] ?? null;
        if (! is_array($saved)) {
            return ['sisa_bulan_lalu' => []];
        }

        return ['sisa_bulan_lalu' => $saved['sisa_bulan_lalu'] ?? []];
    }

    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        if ($report->file_path) {
            Storage::disk('local')->delete($report->file_path);
        }

        $report->delete();

        return redirect()->route('reports.index')
            ->with('status', 'Laporan berhasil dihapus.');
    }
}
