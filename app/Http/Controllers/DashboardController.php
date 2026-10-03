<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\ImportData;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public const MONTH_NAMES = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function index(Request $request): View
    {
        $userId = auth()->id();

        $bulan = $request->integer('bulan', (int) now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) now()->month;
        }
        $tahun = $request->integer('tahun', (int) now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) now()->year;
        }

        $totalImports = Import::where('user_id', $userId)->count();

        $totalRecords = ImportData::whereHas('import', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();

        $totalReports = Report::where('user_id', $userId)->count();

        $recentImports = Import::where('user_id', $userId)
            ->withCount('importData')
            ->latest()
            ->take(5)
            ->get();

        $recentReports = Report::where('user_id', $userId)
            ->with('import')
            ->latest()
            ->take(5)
            ->get();

        $stats = $this->buildStats($userId, $bulan, $tahun);
        $monthName = self::MONTH_NAMES[$bulan - 1];

        return view('dashboard', compact(
            'totalImports',
            'totalRecords',
            'totalReports',
            'recentImports',
            'recentReports',
            'stats',
            'bulan',
            'tahun',
            'monthName',
        ));
    }

    private function buildStats(int $userId, int $bulan, int $tahun): array
    {
        $stats = [
            'pn_bulan' => 0,
            'pn_tahun' => 0,
            'pdk_bulan' => 0,
            'pdk_tahun' => 0,
            'dup_bulan' => 0,
            'dup_tahun' => 0,
            'pn_kantor' => 0,
            'pn_luar' => 0,
            'pdk_kantor' => 0,
            'pdk_luar' => 0,
            'pn_kantor_tahun' => 0,
            'pn_luar_tahun' => 0,
            'pdk_kantor_tahun' => 0,
            'pdk_luar_tahun' => 0,
            'ph_kantor_bulan' => 0,
            'ph_luar_bulan' => 0,
            'ph_kantor_tahun' => 0,
            'ph_luar_tahun' => 0,
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
            ->select('imports.table_name', 'import_data.row_data')
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
                $pnRows[] = $row;
            } elseif (str_contains((string) $raw->table_name, 'pendaftaran nikah')) {
                $pdkRows[] = $row;
            } else {
                $l3Rows[] = $row;
            }
        }

        $pdkByNomorDaftar = [];
        foreach ($pdkRows as $row) {
            $nd = trim((string) ($row['Nomor Daftar'] ?? ''));
            if ($nd !== '') {
                $pdkByNomorDaftar[$nd] = $row;
            }
        }

        foreach ($pnRows as $row) {
            $date = $this->parseDate($row['Tanggal Nikah'] ?? null);
            if ($date === null) {
                continue;
            }
            $matchYear = $date->year === $tahun;
            $matchMonth = $matchYear && $date->month === $bulan;
            $pdk = $pdkByNomorDaftar[trim((string) ($row['Nomor Daftar'] ?? ''))] ?? null;
            $isLuar = $pdk !== null && $this->isLuarKantor($pdk);
            $hasPenghulu = trim((string) ($row['Penghulu'] ?? '')) !== '';

            if ($matchYear) {
                $stats['pn_tahun']++;
                if ($isLuar) {
                    $stats['pn_luar_tahun']++;
                } else {
                    $stats['pn_kantor_tahun']++;
                }
                if ($hasPenghulu) {
                    if ($isLuar) {
                        $stats['ph_luar_tahun']++;
                    } else {
                        $stats['ph_kantor_tahun']++;
                    }
                }
            }
            if ($matchMonth) {
                $stats['pn_bulan']++;
                if ($isLuar) {
                    $stats['pn_luar']++;
                } else {
                    $stats['pn_kantor']++;
                }
                if ($hasPenghulu) {
                    if ($isLuar) {
                        $stats['ph_luar_bulan']++;
                    } else {
                        $stats['ph_kantor_bulan']++;
                    }
                }
            }
        }

        foreach ($pdkRows as $row) {
            $date = $this->parseDate($row['Tanggal Daftar'] ?? null);
            if ($date === null) {
                continue;
            }
            $matchYear = $date->year === $tahun;
            $matchMonth = $matchYear && $date->month === $bulan;
            $isLuar = $this->isLuarKantor($row);
            if ($matchYear) {
                $stats['pdk_tahun']++;
                if ($isLuar) {
                    $stats['pdk_luar_tahun']++;
                } else {
                    $stats['pdk_kantor_tahun']++;
                }
            }
            if ($matchMonth) {
                $stats['pdk_bulan']++;

                if ($isLuar) {
                    $stats['pdk_luar']++;
                } else {
                    $stats['pdk_kantor']++;
                }
            }
        }

        $seenPerforasi = [];
        foreach ($l3Rows as $row) {
            if (mb_strtolower(trim((string) ($row['Keterangan'] ?? ''))) !== 'duplikat') {
                continue;
            }
            $date = $this->parseDate($row['Tanggal Cetak'] ?? null);
            if ($date === null) {
                continue;
            }
            $perforasi = trim((string) ($row['Nomor Perforasi'] ?? ''));
            if ($perforasi !== '') {
                if (isset($seenPerforasi[$perforasi])) {
                    continue;
                }
                $seenPerforasi[$perforasi] = true;
            }
            $matchYear = $date->year === $tahun;
            if ($matchYear) {
                $stats['dup_tahun']++;
            }
            if ($matchYear && $date->month === $bulan) {
                $stats['dup_bulan']++;
            }
        }

        return $stats;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isLuarKantor(array $row): bool
    {
        $nikahDi = mb_strtoupper(trim((string) ($row['Nikah Di'] ?? '')));

        return str_contains($nikahDi, 'LUAR') || str_contains($nikahDi, 'BEDOL');
    }
}
