<?php

namespace App\Services;

use Carbon\Carbon;
use Throwable;

class RusakDetectionService
{
    /**
     * Deteksi nomor perforasi hilang (Rusak) per prefix.
     * Sumber data rusak: celah interior pada rentang nomor
     * yang benar-benar ada di baris data (nomor di luar
     * rentang min..max = stok, bukan rusak).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<int>> prefix => daftar nomor hilang (urut naik)
     */
    public function detectMissing(array $rows, int $prefixLength = 6): array
    {
        $perforasiByPrefix = [];
        foreach ($rows as $row) {
            $p = preg_replace('/\s*-\s*\d+$/', '', preg_replace('/^JT\s*/i', '', trim((string) ($row['Nomor Perforasi'] ?? ''))));
            if (! preg_match('/^\d+$/', $p)) {
                continue;
            }
            $pf = substr($p, 0, $prefixLength);
            $perforasiByPrefix[$pf][(int) $p] = true;
        }

        $missing = [];
        foreach ($perforasiByPrefix as $pf => $numSet) {
            $sortedNums = array_keys($numSet);
            sort($sortedNums);
            if (count($sortedNums) < 2) {
                continue;
            }
            $gap = [];
            for ($n = $sortedNums[0]; $n <= $sortedNums[count($sortedNums) - 1]; $n++) {
                if (! isset($numSet[$n])) {
                    $gap[] = $n;
                }
            }
            if ($gap !== []) {
                $missing[$pf] = $gap;
            }
        }

        return $missing;
    }

    /**
     * Hitung buku rusak per bulan: baris berstatus RUSAK (Keterangan
     * "Rusak") ditambah celah nomor perforasi pada bulan tersebut.
     * Nomor yang sama dihitung sekali, baik eksplisit maupun celah.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $bulanList
     * @return array{bulan: array<int, int>, total: int}
     */
    public function countsByMonth(array $rows, int $tahun, array $bulanList, int $prefixLength = 6): array
    {
        $bulanList = array_values(array_unique(array_map('intval', $bulanList)));
        sort($bulanList);

        $result = ['bulan' => [], 'total' => 0];
        foreach ($bulanList as $bulan) {
            $result['bulan'][$bulan] = 0;
        }

        $rowsByMonth = [];
        foreach ($rows as $row) {
            $date = $this->parseTanggalCetak($row['Tanggal Cetak'] ?? null);
            if ($date === null || $date->year !== $tahun) {
                continue;
            }
            $bulan = (int) $date->month;
            if (! isset($result['bulan'][$bulan])) {
                continue;
            }
            $rowsByMonth[$bulan][] = $row;
        }
        ksort($rowsByMonth);

        $seenEksplisit = [];
        foreach ($rowsByMonth as $bulan => $bulanRows) {
            foreach ($bulanRows as $row) {
                if (mb_strtolower(trim((string) ($row['Keterangan'] ?? ''))) !== 'rusak') {
                    continue;
                }
                $nomor = $this->normalizeNomor($row['Nomor Perforasi'] ?? '');
                if ($nomor !== '') {
                    if (isset($seenEksplisit[$nomor])) {
                        continue;
                    }
                    $seenEksplisit[$nomor] = true;
                }
                $result['bulan'][$bulan]++;
            }
        }

        $seenCelah = [];
        foreach ($rowsByMonth as $bulan => $bulanRows) {
            foreach ($this->detectMissing($bulanRows, $prefixLength) as $prefix => $numbers) {
                foreach ($numbers as $number) {
                    $key = $prefix.':'.$number;
                    if (isset($seenCelah[$key])) {
                        continue;
                    }
                    $seenCelah[$key] = true;
                    $result['bulan'][$bulan]++;
                }
            }
        }

        $result['total'] = array_sum($result['bulan']);

        return $result;
    }

    private function parseTanggalCetak(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeNomor(mixed $value): string
    {
        $nomor = preg_replace('/\s*-\s*\d+$/', '', preg_replace('/^JT\s*/i', '', trim((string) $value)));

        return is_string($nomor) ? $nomor : '';
    }
}
