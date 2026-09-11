<?php

namespace App\Services;

use App\Models\Import;
use App\Models\ImportData;

class MergeService
{
    /**
     * JOIN data dari multiple imports berdasarkan kolom tertentu.
     *
     * @param  int[]  $importIds
     * @return array{headings: string[], rows: array<int, array<string, mixed>>, total: int}
     */
    public function mergeByColumn(array $importIds, string $joinColumn): array
    {
        $allColumns = [];
        $indexedData = [];
        $allKeys = [];
        $importLabels = [];

        foreach ($importIds as $importId) {
            $import = Import::where('id', $importId)->first();

            if (! $import) {
                continue;
            }

            $importLabels[$importId] = $import->file_name;

            $columns = $import->availableColumns();
            foreach ($columns as $col) {
                if (! in_array($col, $allColumns, true)) {
                    $allColumns[] = $col;
                }
            }

            $rows = ImportData::where('import_id', $importId)
                ->orderBy('row_number')
                ->cursor()
                ->map(fn (ImportData $record) => $record->row_data ?? [])
                ->all();

            foreach ($rows as $row) {
                $key = isset($row[$joinColumn]) ? (string) $row[$joinColumn] : null;

                if ($key === null || $key === '') {
                    $key = '__unmatched_'.$importId.'_'.(array_key_exists('__unmatched_'.$importId, $allKeys) ? count($allKeys['__unmatched_'.$importId]) : 0);
                    $allKeys[$key] = true;
                } else {
                    $allKeys[$key] = true;
                }

                if (! isset($indexedData[$key])) {
                    $indexedData[$key] = [];
                }

                foreach ($row as $col => $val) {
                    $indexedData[$key][$col] = $val;
                }
            }
        }

        $mergedRows = [];
        foreach ($indexedData as $key => $row) {
            $mergedRow = [];
            foreach ($allColumns as $col) {
                $mergedRow[$col] = $row[$col] ?? null;
            }
            $mergedRows[] = $mergedRow;
        }

        usort($mergedRows, function ($a, $b) use ($joinColumn) {
            $valA = $a[$joinColumn] ?? '';
            $valB = $b[$joinColumn] ?? '';

            return strcasecmp((string) $valA, (string) $valB);
        });

        return [
            'headings' => $allColumns,
            'rows' => $mergedRows,
            'total' => count($mergedRows),
        ];
    }

    /**
     * Build dataset untuk laporan dari merge results.
     *
     * @param  int[]  $importIds
     * @param  string[]  $fields
     * @return array{title: string, headings: string[], rows: array<int, array<string, mixed>>, total: int, generated_at: string}
     */
    public function buildMergedDataset(
        array $importIds,
        string $joinColumn,
        array $fields,
        ?string $search = null,
        ?string $filterColumn = null,
        ?string $filterValue = null,
        ?string $sortColumn = null,
        string $sortDirection = 'asc',
        ?int $limit = null,
    ): array {
        $mergeResult = $this->mergeByColumn($importIds, $joinColumn);
        $rows = $mergeResult['rows'];

        if ($search !== null && $search !== '') {
            $lowerSearch = mb_strtolower($search);
            $rows = array_filter($rows, function ($row) use ($lowerSearch) {
                foreach ($row as $val) {
                    if ($val !== null && mb_strpos(mb_strtolower((string) $val), $lowerSearch) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        if ($filterColumn !== null && $filterColumn !== '' && $filterValue !== null && $filterValue !== '') {
            $lowerFilter = mb_strtolower($filterValue);
            $rows = array_filter($rows, function ($row) use ($filterColumn, $lowerFilter) {
                $val = $row[$filterColumn] ?? null;

                return $val !== null && mb_strpos(mb_strtolower((string) $val), $lowerFilter) !== false;
            });
        }

        $rows = array_values($rows);

        if ($sortColumn !== null && $sortColumn !== '' && isset($rows[0][$sortColumn])) {
            $dir = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';
            usort($rows, function ($a, $b) use ($sortColumn, $dir) {
                $valA = $a[$sortColumn] ?? '';
                $valB = $b[$sortColumn] ?? '';

                $cmp = strcasecmp((string) $valA, (string) $valB);

                return $dir === 'desc' ? -$cmp : $cmp;
            });
        }

        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }

        $filteredRows = [];
        foreach ($rows as $row) {
            $filteredRow = [];
            foreach ($fields as $field) {
                $filteredRow[$field] = $row[$field] ?? null;
            }
            $filteredRows[] = $filteredRow;
        }

        return [
            'title' => 'Gabungan '.count($importIds).' file import',
            'headings' => array_values($fields),
            'rows' => $filteredRows,
            'total' => count($filteredRows),
            'generated_at' => now()->format('d M Y H:i'),
        ];
    }

    /**
     * Get columns shared between multiple imports.
     *
     * @param  int[]  $importIds
     * @return string[]
     */
    public function getSharedColumns(array $importIds): array
    {
        if (count($importIds) < 2) {
            return [];
        }

        $allColumns = [];
        $columnCounts = [];

        foreach ($importIds as $importId) {
            $import = Import::where('id', $importId)->first();

            if (! $import) {
                continue;
            }

            $columns = $import->availableColumns();

            foreach ($columns as $col) {
                if (! in_array($col, $allColumns, true)) {
                    $allColumns[] = $col;
                    $columnCounts[$col] = 0;
                }
                $columnCounts[$col] = ($columnCounts[$col] ?? 0) + 1;
            }
        }

        $totalImports = count($importIds);

        return array_values(array_filter($allColumns, function ($col) use ($columnCounts) {
            return ($columnCounts[$col] ?? 0) >= 2;
        }));
    }

    /**
     * Get all unique columns across multiple imports.
     *
     * @param  int[]  $importIds
     * @return string[]
     */
    public function getAllColumns(array $importIds): array
    {
        $allColumns = [];

        foreach ($importIds as $importId) {
            $import = Import::where('id', $importId)->first();

            if (! $import) {
                continue;
            }

            foreach ($import->availableColumns() as $col) {
                if (! in_array($col, $allColumns, true)) {
                    $allColumns[] = $col;
                }
            }
        }

        return $allColumns;
    }
}
