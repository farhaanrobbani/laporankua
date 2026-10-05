<?php

namespace App\Services;

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
}
