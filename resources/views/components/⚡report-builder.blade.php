<?php

use App\Jobs\GenerateReport;
use App\Models\Import;
use App\Models\Report;
use App\Models\ReportTemplate;
use App\Services\MergeService;
use App\Services\ReportGenerationService;
use Livewire\Component;

new class extends Component
{
    public ?int $importId = null;

    /** @var array<int, array{id: int, file_name: string, table_name: string|null}> */
    public array $imports = [];

    /** @var string[] */
    public array $columns = [];

    /** @var string[] */
    public array $fields = [];

    public string $search = '';

    public string $filterColumn = '';

    public string $filterValue = '';

    public string $sortColumn = '';

    public string $sortDirection = 'asc';

    public string $title = '';

    public string $format = 'pdf';

    public string $orientation = 'portrait';

    public string $filterMonth = '';

    public string $filterYear = '';

    /** @var array{headings: string[], rows: array, total: int}|null */
    public ?array $preview = null;

    public bool $hasDefaultTemplate = false;

    public bool $isMergeMode = false;

    /** @var int[] */
    public array $mergeImportIds = [];

    /** @var int[] */
    public array $selectedImportIds = [];

    /** @var string[] */
    public array $autoJoinColumns = [];

    /** @var array<int, array{id: int, name: string, description: string|null, output_format: string, fields_json: array, filters_json: array, sorting_json: array, layout_json: array}> */
    public array $globalTemplates = [];

    public ?int $selectedTemplateId = null;

    public ?array $tableLayout = null;

    /** @var array<int, array{prefix: string, label: string, keluar_jumlah: int, min_porp: string, max_porp: string}> */
    public array $naVersions = [];

    /** @var array<int, array{masuk_jumlah: string, masuk_seri: string, keluar_jumlah: string, keluar_seri: string, sisa_jumlah: string, sisa_seri: string, keterangan: string}> */
    public array $manualData = [];

    public function mount(): void
    {
        $this->imports = Import::where('user_id', auth()->id())
            ->latest()
            ->withCount('importData')
            ->get(['id', 'file_name', 'table_name'])
            ->groupBy('table_name')
            ->map(fn ($rows, $tableName) => [
                'id' => $rows->first()->id,
                'table_name' => $tableName,
                'total_rows' => $rows->sum('import_data_count'),
                'import_ids' => $rows->pluck('id')->toArray(),
            ])
            ->values()
            ->all();

        $this->globalTemplates = ReportTemplate::where('is_global', true)
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'output_format' => $t->output_format,
                'fields_json' => $t->fields_json ?? [],
                'filters_json' => $t->filters_json ?? [],
                'sorting_json' => $t->sorting_json ?? [],
                'layout_json' => $t->layout_json ?? [],
            ])
            ->all();

        $this->hasDefaultTemplate = ReportTemplate::where('is_global', true)
            ->where('is_active', true)
            ->where('is_default', true)
            ->exists();
    }

    public function updatedMergeImportIds(): void
    {
        $this->columns = [];
        $this->fields = [];
        $this->preview = null;
        $this->tableLayout = null;
        $this->autoJoinColumns = [];

        if (count($this->mergeImportIds) >= 2) {
            $this->columns = app(MergeService::class)->getAllColumns($this->mergeImportIds);
            $this->fields = $this->columns;
            $this->autoJoinColumns = app(MergeService::class)->getSharedColumns($this->mergeImportIds);

            if ($this->selectedTemplateId) {
                $this->applySelectedTemplate();
            }
        }
    }

    public function toggleImportGroup(array $ids): void
    {
        $allChecked = count(array_intersect($ids, $this->mergeImportIds)) === count($ids);

        if ($allChecked) {
            $this->mergeImportIds = array_values(array_diff($this->mergeImportIds, $ids));
        } else {
            $this->mergeImportIds = array_values(array_unique(array_merge($this->mergeImportIds, $ids)));
        }

        $this->updatedMergeImportIds();
    }

    public function loadDefaultTemplate(): void
    {
        $template = ReportTemplate::where('is_global', true)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (! $template) {
            return;
        }

        $this->applyTemplateConfig($template);
    }

    public function applySelectedTemplate(): void
    {
        if (! $this->selectedTemplateId) {
            return;
        }

        $template = ReportTemplate::where('is_global', true)
            ->where('is_active', true)
            ->find($this->selectedTemplateId);

        if (! $template) {
            return;
        }

        $this->applyTemplateConfig($template);
    }

    private function applyTemplateConfig(ReportTemplate $template): void
    {
        $this->format = $template->output_format;
        $this->orientation = $template->layout_json['orientation'] ?? 'portrait';
        $this->search = $template->filters_json['search'] ?? '';
        $this->filterColumn = $template->filters_json['filter_column'] ?? '';
        $this->filterValue = $template->filters_json['filter_value'] ?? '';
        $this->sortColumn = $template->sorting_json['column'] ?? '';
        $this->sortDirection = $template->sorting_json['direction'] ?? 'asc';
        $this->tableLayout = $template->layout_json['table_layout'] ?? null;
        $this->filterMonth = '';
        $this->filterYear = '';

        if ($this->isMergeMode && count($this->mergeImportIds) >= 2) {
            $this->columns = app(MergeService::class)->getAllColumns($this->mergeImportIds);
            $this->fields = array_values(array_intersect($template->fields_json ?? [], $this->columns));
            if ($this->fields === []) {
                $this->fields = $this->columns;
            }
            $this->loadPreview();
        } elseif ($this->importId) {
            $import = $this->selectedImport();
            if ($import) {
                $this->columns = $import->availableColumns();
                $this->fields = array_values(array_intersect($template->fields_json ?? [], $this->columns));
                if ($this->fields === []) {
                    $this->fields = $this->columns;
                }
                $this->loadPreview();
            }
        }

        if (($this->tableLayout['type'] ?? '') === 'formulir') {
            $this->initManualData();
        }
    }

    private function initManualData(): void
    {
        $empty = ['formulir' => '', 'masuk_jumlah' => '', 'masuk_seri_awal' => '', 'masuk_seri_akhir' => '', 'keluar_jumlah' => '', 'keluar_seri_awal' => '', 'keluar_seri_akhir' => '', 'sisa_jumlah' => '', 'sisa_seri_awal' => '', 'sisa_seri_akhir' => '', 'keterangan' => ''];
        $this->naVersions = $this->detectNaVersions();

        $baseRows = [
            array_merge($empty, ['formulir' => 'Model N']),
        ];

        foreach ($this->naVersions as $i => $ver) {
            $baseRows[] = array_merge($empty, [
                'formulir' => $ver['label'],
                'keluar_jumlah' => (string) $ver['keluar_jumlah'],
                'keluar_seri_awal' => $ver['min_porp'],
                'keluar_seri_akhir' => $ver['max_porp'],
            ]);
        }

        $baseRows[] = array_merge($empty, ['formulir' => 'Model DN']);
        $baseRows[] = array_merge($empty, ['formulir' => 'Model NB']);

        $this->manualData = $baseRows;
        $this->rebuildStaticRows();
    }

    private function detectNaVersions(): array
    {
        if (! $this->importId) {
            return [];
        }

        $prefixLength = $this->tableLayout['na_version_prefix_length'] ?? 6;

        $allRows = \App\Models\ImportData::where('import_id', $this->importId)
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
                $groups[$prefix] = ['porporasi' => [], 'count' => 0];
            }
            $groups[$prefix]['porporasi'][] = (int) $nomor;
        }

        $filterMonth = $this->filterMonth !== '' ? (int) $this->filterMonth : null;
        $filterYear = $this->filterYear !== '' ? (int) $this->filterYear : null;

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
                    $date = \Carbon\Carbon::parse($tanggalCetak);
                    if ($filterMonth !== null && (int) $date->month !== $filterMonth) {
                        continue;
                    }
                    if ($filterYear !== null && (int) $date->year !== $filterYear) {
                        continue;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (isset($groups[$prefix])) {
                $groups[$prefix]['count']++;
            }
        }

        ksort($groups);

        $versions = [];
        $i = 1;
        foreach ($groups as $prefix => $data) {
            $min = (string) min($data['porporasi']);
            $max = (string) max($data['porporasi']);
            $versions[] = [
                'prefix' => $prefix,
                'label' => 'Model NA ('.$prefix.')',
                'keluar_jumlah' => $data['count'],
                'min_porp' => $min,
                'max_porp' => $max,
            ];
            $i++;
        }

        return $versions;
    }

    public function updatedManualData(): void
    {
        $this->computeMasukJumlah();
        $this->recalculateSisa();
    }

    private function computeMasukJumlah(): void
    {
        $naCount = count($this->naVersions);
        if ($naCount === 0) {
            return;
        }

        for ($i = 0; $i < $naCount; $i++) {
            $idx = $i + 1;
            if (! isset($this->manualData[$idx])) {
                continue;
            }
            $awal = (int) ($this->manualData[$idx]['masuk_seri_awal'] ?? 0);
            $akhir = (int) ($this->manualData[$idx]['masuk_seri_akhir'] ?? 0);

            if ($awal > 0 && $akhir >= $awal) {
                $this->manualData[$idx]['masuk_jumlah'] = (string) ($akhir - $awal + 1);
            } else {
                $this->manualData[$idx]['masuk_jumlah'] = '';
            }
        }
    }

    private function recalculateSisa(): void
    {
        $naCount = count($this->naVersions);
        if ($naCount === 0) {
            return;
        }

        for ($i = 0; $i < $naCount; $i++) {
            $idx = $i + 1;
            if (! isset($this->manualData[$idx])) {
                continue;
            }
            $masuk = (int) ($this->manualData[$idx]['masuk_jumlah'] ?? 0);
            $keluar = (int) ($this->manualData[$idx]['keluar_jumlah'] ?? 0);
            $this->manualData[$idx]['sisa_jumlah'] = $masuk > 0 ? (string) ($masuk - $keluar) : '';
        }
    }

    private function rebuildStaticRows(): void
    {
        if (! $this->tableLayout) {
            return;
        }

        $staticRows = [
            ['formulir' => 'Model N', 'row_num' => 1, 'dynamic' => false],
        ];

        $rowNum = 2;
        foreach ($this->naVersions as $ver) {
            $staticRows[] = [
                'formulir' => $ver['label'],
                'row_num' => $rowNum,
                'dynamic' => true,
                'dynamic_type' => 'na_version',
            ];
            $rowNum++;
        }

        $staticRows[] = ['formulir' => 'Model DN', 'row_num' => $rowNum, 'dynamic' => false];
        $rowNum++;
        $staticRows[] = ['formulir' => 'Model NB', 'row_num' => $rowNum, 'dynamic' => false];

        $this->tableLayout['static_rows'] = $staticRows;
    }

    private function buildSeriRange(string $awal, string $akhir): string
    {
        if ($awal === '' && $akhir === '') {
            return '';
        }
        if ($awal === $akhir || $akhir === '') {
            return 'JT '.$awal;
        }
        if ($awal === '') {
            return 'JT '.$akhir;
        }

        return 'JT '.$awal.' - '.$akhir;
    }

    private function buildManualDataForSave(): array
    {
        $data = array_map(function ($row) {
            $row['masuk_seri'] = $this->buildSeriRange($row['masuk_seri_awal'] ?? '', $row['masuk_seri_akhir'] ?? '');
            $row['keluar_seri'] = $this->buildSeriRange($row['keluar_seri_awal'] ?? '', $row['keluar_seri_akhir'] ?? '');
            $row['sisa_seri'] = $this->buildSeriRange($row['sisa_seri_awal'] ?? '', $row['sisa_seri_akhir'] ?? '');

            return $row;
        }, $this->manualData);

        return [
            'rows' => $data,
            'na_versions' => $this->naVersions,
            'static_rows' => $this->tableLayout['static_rows'] ?? [],
        ];
    }

    public function updatedImportId(): void
    {
        if ($this->isMergeMode) {
            return;
        }

        $this->reset(['columns', 'fields', 'preview', 'filterColumn', 'filterValue', 'sortColumn', 'search']);
        $this->tableLayout = null;

        $import = $this->selectedImport();

        if (! $import) {
            $this->selectedImportIds = [];

            return;
        }

        $group = collect($this->imports)->firstWhere('id', $import->id);
        $this->selectedImportIds = $group['import_ids'] ?? [$import->id];

        $this->columns = $import->availableColumns();
        $this->fields = $this->columns;

        if ($this->title === '') {
            $this->title = 'Laporan '.($import->table_name ?? pathinfo($import->file_name, PATHINFO_FILENAME));
        }

        if ($this->selectedTemplateId) {
            $this->applySelectedTemplate();
        } else {
            $this->loadPreview();
        }
    }

    public function loadPreview(): void
    {
        if ($this->isMergeMode) {
            $this->loadMergePreview();

            return;
        }

        if ($this->selectedImportIds === [] || $this->fields === []) {
            $this->preview = null;

            return;
        }

        $hasFilter = $this->filterMonth !== '' || $this->filterYear !== '';
        $effectiveFields = $this->fields;
        if ($hasFilter && ! in_array('Tanggal Nikah', $effectiveFields, true)) {
            $import = Import::whereIn('id', $this->selectedImportIds)->first();
            if ($import && in_array('Tanggal Nikah', $import->availableColumns(), true)) {
                $effectiveFields[] = 'Tanggal Nikah';
            }
        }

        $dataset = app(MergeService::class)->buildConcatDataset(
            $this->selectedImportIds,
            $effectiveFields,
            $this->search ?: null,
            $this->filterColumn ?: null,
            $this->filterValue ?: null,
            $this->sortColumn ?: null,
            $this->sortDirection,
        );

        $this->preview = [
            'headings' => $dataset['headings'],
            'rows' => $dataset['rows'],
            'total' => $dataset['total'],
        ];

        $this->applyPreviewFilter();

        $hasFilter = $this->filterMonth !== '' || $this->filterYear !== '';
        if (! $hasFilter && count($this->preview['rows']) > 10) {
            $this->preview['rows'] = array_slice($this->preview['rows'], 0, 10);
        }

        if (($this->tableLayout['type'] ?? '') === 'formulir') {
            $this->initManualData();
        }
    }

    private function loadMergePreview(): void
    {
        if (count($this->mergeImportIds) < 2 || $this->fields === []) {
            $this->preview = null;

            return;
        }

        $hasFilter = $this->filterMonth !== '' || $this->filterYear !== '';
        $effectiveFields = $this->fields;
        if ($hasFilter && ! in_array('Tanggal Nikah', $effectiveFields, true)) {
            $allCols = app(MergeService::class)->getAllColumns($this->mergeImportIds);
            if (in_array('Tanggal Nikah', $allCols, true)) {
                $effectiveFields[] = 'Tanggal Nikah';
            }
        }

        $dataset = app(MergeService::class)->buildMergedDataset(
            $this->mergeImportIds,
            $effectiveFields,
            $this->search ?: null,
            $this->filterColumn ?: null,
            $this->filterValue ?: null,
            $this->sortColumn ?: null,
            $this->sortDirection,
        );

        $this->preview = [
            'headings' => $dataset['headings'],
            'rows' => $dataset['rows'],
            'total' => $dataset['total'],
        ];

        $this->applyPreviewFilter();

        $hasFilter = $this->filterMonth !== '' || $this->filterYear !== '';
        if (! $hasFilter && count($this->preview['rows']) > 10) {
            $this->preview['rows'] = array_slice($this->preview['rows'], 0, 10);
        }
    }

    private function applyPreviewFilter(): void
    {
        if (! $this->preview || ($this->filterMonth === '' && $this->filterYear === '')) {
            return;
        }

        $tanggalNikahField = null;
        if ($this->tableLayout) {
            foreach ($this->tableLayout['columns'] ?? [] as $col) {
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
        }

        if ($tanggalNikahField === null && in_array('Tanggal Nikah', $this->preview['headings'] ?? [], true)) {
            $tanggalNikahField = 'Tanggal Nikah';
        }

        if ($tanggalNikahField === null && $this->tableLayout) {
            $dateFilterField = $this->tableLayout['aggregation']['date_filter_field'] ?? null;
            if ($dateFilterField && in_array($dateFilterField, $this->preview['headings'] ?? [], true)) {
                $tanggalNikahField = $dateFilterField;
            }
        }

        if (! $tanggalNikahField || ! in_array($tanggalNikahField, $this->preview['headings'] ?? [], true)) {
            return;
        }

        $filterMonth = $this->filterMonth;
        $filterYear = $this->filterYear;

        $this->preview['rows'] = array_values(array_filter(
            $this->preview['rows'],
            function ($row) use ($tanggalNikahField, $filterMonth, $filterYear) {
                $dateVal = $row[$tanggalNikahField] ?? null;
                if ($dateVal === null || $dateVal === '') {
                    return false;
                }
                try {
                    $date = \Carbon\Carbon::parse($dateVal);
                } catch (\Exception $e) {
                    return false;
                }
                if ($filterMonth !== '' && (int) $date->month !== (int) $filterMonth) {
                    return false;
                }
                if ($filterYear !== '' && (int) $date->year !== (int) $filterYear) {
                    return false;
                }

                return true;
            }
        ));

        $this->preview['total'] = count($this->preview['rows']);
    }

    public function generate(): void
    {
        if ($this->isMergeMode) {
            $this->generateMergeReport();

            return;
        }

        $this->validate([
            'importId' => 'required|integer',
            'title' => 'required|string|max:255',
            'format' => 'required|in:pdf,word,excel,print',
            'orientation' => 'required|in:portrait,landscape',
            'sortDirection' => 'required|in:asc,desc',
        ]);

        $import = $this->selectedImport();

        if (! $import) {
            $this->addError('importId', 'Sumber data tidak valid.');

            return;
        }

        $fields = array_values(array_intersect($this->fields, $import->availableColumns()));

        if ($fields === [] && $this->format !== 'print') {
            $this->addError('fields', 'Pilih minimal satu kolom.');

            return;
        }

        $hasFilter = $this->filterMonth !== '' || $this->filterYear !== '';
        if ($hasFilter && ! in_array('Tanggal Nikah', $fields, true)) {
            if (in_array('Tanggal Nikah', $import->availableColumns(), true)) {
                $fields[] = 'Tanggal Nikah';
            }
        }

        $report = Report::create([
            'user_id' => auth()->id(),
            'import_id' => $import->id,
            'title' => $this->title,
            'output_format' => $this->format,
            'config_json' => array_merge($this->getUserConfig(), [
                'fields' => $fields === [] ? $import->availableColumns() : $fields,
                'search' => $this->search ?: null,
                'filter_column' => $this->filterColumn ?: null,
                'filter_value' => $this->filterValue ?: null,
                'sort_column' => $this->sortColumn ?: null,
                'sort_direction' => $this->sortDirection,
                'orientation' => $this->orientation,
                'filter_month' => $this->filterMonth ?: null,
                'filter_year' => $this->filterYear ?: null,
                'merged_import_ids' => $this->selectedImportIds,
                'manual_data' => ($this->tableLayout['type'] ?? '') === 'formulir' ? $this->buildManualDataForSave() : null,
            ], $this->tableLayout ? ['table_layout' => $this->tableLayout] : []),
            'status' => $this->format === 'print' ? 'generated' : 'pending',
            'generated_at' => $this->format === 'print' ? now() : null,
        ]);

        if ($this->format === 'print') {
            $this->redirectRoute('reports.print', $report);

            return;
        }

        GenerateReport::dispatch($report);
        $this->redirectRoute('reports.index');
    }

    private function generateMergeReport(): void
    {
        $this->validate([
            'mergeImportIds' => 'required|array|min:2',
            'title' => 'required|string|max:255',
            'format' => 'required|in:pdf,word,excel,print',
            'orientation' => 'required|in:portrait,landscape',
            'sortDirection' => 'required|in:asc,desc',
        ]);

        if ($this->fields === [] && $this->format !== 'print') {
            $this->addError('fields', 'Pilih minimal satu kolom.');

            return;
        }

        $firstImport = Import::where('id', $this->mergeImportIds[0])->first();

        if (! $firstImport) {
            $this->addError('importId', 'Sumber data tidak valid.');

            return;
        }

        $effectiveFields = $this->fields;
        $hasFilter = $this->filterMonth !== '' || $this->filterYear !== '';
        if ($hasFilter && ! in_array('Tanggal Nikah', $effectiveFields, true)) {
            $allCols = app(MergeService::class)->getAllColumns($this->mergeImportIds);
            if (in_array('Tanggal Nikah', $allCols, true)) {
                $effectiveFields[] = 'Tanggal Nikah';
            }
        }

        $report = Report::create([
            'user_id' => auth()->id(),
            'import_id' => $firstImport->id,
            'title' => $this->title,
            'output_format' => $this->format,
            'config_json' => array_merge($this->getUserConfig(), [
                'fields' => $effectiveFields,
                'search' => $this->search ?: null,
                'filter_column' => $this->filterColumn ?: null,
                'filter_value' => $this->filterValue ?: null,
                'sort_column' => $this->sortColumn ?: null,
                'sort_direction' => $this->sortDirection,
                'orientation' => $this->orientation,
                'is_merged' => true,
                'merged_import_ids' => $this->mergeImportIds,
                'filter_month' => $this->filterMonth ?: null,
                'filter_year' => $this->filterYear ?: null,
            ], $this->tableLayout ? ['table_layout' => $this->tableLayout] : []),
            'status' => $this->format === 'print' ? 'generated' : 'pending',
            'generated_at' => $this->format === 'print' ? now() : null,
        ]);

        if ($this->format === 'print') {
            $this->redirectRoute('reports.print', $report);

            return;
        }

        GenerateReport::dispatch($report);
        $this->redirectRoute('reports.index');
    }

    private function selectedImport(): ?Import
    {
        if (! $this->importId) {
            return null;
        }

        return Import::where('user_id', auth()->id())->find($this->importId);
    }

    private function getUserConfig(): array
    {
        $user = auth()->user();

        return [
            'kecamatan' => $user->kecamatan ?? null,
            'nama_kepala_kua' => $user->nama_kepala_kua ?? null,
            'nip_kepala' => $user->nip_kepala ?? null,
            'nama_kementerian' => $user->nama_kementerian ?? null,
            'nama_kantor_kota' => $user->nama_kantor_kota ?? null,
            'nama_kantor' => $user->nama_kantor ?? null,
            'alamat_kantor' => $user->alamat_kantor ?? null,
            'telepon_kantor' => $user->telepon_kantor ?? null,
            'email_kantor' => $user->email_kantor ?? null,
            'logo_kantor' => $user->logo_kantor ?? null,
            'daftar_desa' => $user->daftar_desa ?? null,
            'font_size_kop_kementerian' => $user->font_size_kop_kementerian ?? null,
            'font_size_kop_kantor_kota' => $user->font_size_kop_kantor_kota ?? null,
            'font_size_kop_kantor' => $user->font_size_kop_kantor ?? null,
            'font_size_kop_alamat' => $user->font_size_kop_alamat ?? null,
            'font_size_kop_kontak' => $user->font_size_kop_kontak ?? null,
        ];
    }
};
?>

<div class="space-y-6">
    {{-- 1. Sumber data --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">1. Sumber Data</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Pilih file import yang akan dijadikan laporan.</p>

        {{-- Mode Toggle --}}
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 w-fit mb-4">
            <button type="button" wire:click="$set('isMergeMode', false)"
                class="px-4 py-2 text-sm font-medium rounded-md transition {{ ! $this->isMergeMode ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100' }}">
                Single Import
            </button>
            <button type="button" wire:click="$set('isMergeMode', true)"
                class="px-4 py-2 text-sm font-medium rounded-md transition {{ $this->isMergeMode ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100' }}">
                Gabung Data
            </button>
        </div>

        @if ($this->isMergeMode)
            {{-- Merge Mode --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih file import (minimal 2)</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($this->imports as $import)
                        @php $allChecked = count(array_intersect($import['import_ids'], $this->mergeImportIds)) === count($import['import_ids']); @endphp
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-md px-3 py-2 cursor-pointer hover:border-blue-400 dark:hover:border-blue-500">
                            <input type="checkbox" wire:click="toggleImportGroup({{ json_encode($import['import_ids']) }})" @checked($allChecked) class="rounded text-blue-600 dark:text-blue-400" />
                            {{ $import['table_name'] }} ({{ number_format($import['total_rows']) }} baris)
                        </label>
                    @endforeach
                </div>

                @if (count($this->mergeImportIds) >= 2)
                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-700 rounded-md">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            Bergabung otomatis berdasarkan kolom: <strong>{{ implode(' + ', $this->autoJoinColumns) }}</strong>
                        </p>
                        @if (empty($this->autoJoinColumns))
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Tidak ada kolom yang sama antara file yang dipilih.</p>
                        @endif
                    </div>
                @endif
            </div>
        @else
            {{-- Single Mode --}}
            @if (empty($this->imports))
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data import yang berhasil. <a href="{{ route('imports.create') }}" class="text-blue-600 dark:text-blue-400 font-medium">Upload Excel dulu</a>.</p>
            @else
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                    <select wire:model.live="importId" class="border-gray-300 dark:border-gray-600 rounded-md text-sm w-full sm:w-auto">
                        <option value="">-- Pilih file import --</option>
                        @foreach ($this->imports as $import)
                            <option value="{{ $import['id'] }}">{{ $import['table_name'] }} ({{ number_format($import['total_rows']) }} baris)</option>
                        @endforeach
                    </select>
                </div>
            @endif
        @endif
        @if (! empty($this->globalTemplates))
            <div class="mt-3 flex items-center gap-3">
                <select wire:model.live="selectedTemplateId" wire:change="applySelectedTemplate" class="border-gray-300 dark:border-gray-600 rounded-md text-sm w-full sm:w-auto">
                    <option value="">-- Pilih Template --</option>
                    @foreach ($this->globalTemplates as $tpl)
                        <option value="{{ $tpl['id'] }}">{{ $tpl['name'] }} ({{ strtoupper($tpl['output_format']) }})</option>
                    @endforeach
                </select>
                @if ($this->hasDefaultTemplate)
                    <button type="button" wire:click="loadDefaultTemplate" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 font-medium">Muat template default</button>
                @endif
            </div>
        @endif
        @error('importId') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    @if (($this->isMergeMode && count($this->mergeImportIds) >= 2 && ! empty($this->autoJoinColumns)) || (! $this->isMergeMode && $this->importId && ! empty($this->columns)))
        {{-- 2. Filter & Urutan --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">2. Filter &amp; Urutan</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari..." class="border-gray-300 dark:border-gray-600 rounded-md text-sm" />
                <div class="flex gap-2">
                    <select wire:model.live="filterColumn" class="border-gray-300 dark:border-gray-600 rounded-md text-sm flex-1">
                        <option value="">Tanpa filter kolom</option>
                        @foreach ($this->columns as $column)
                            <option value="{{ $column }}">{{ $column }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model.live.debounce.300ms="filterValue" placeholder="Nilai" class="border-gray-300 dark:border-gray-600 rounded-md text-sm flex-1" />
                </div>
                <div class="flex gap-2">
                    <select wire:model.live="sortColumn" class="border-gray-300 dark:border-gray-600 rounded-md text-sm flex-1">
                        <option value="">Tanpa urutan khusus</option>
                        @foreach ($this->columns as $column)
                            <option value="{{ $column }}">{{ $column }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="sortDirection" class="border-gray-300 dark:border-gray-600 rounded-md text-sm">
                        <option value="asc">A → Z</option>
                        <option value="desc">Z → A</option>
                    </select>
                </div>
                @if ($this->format === 'print')
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Bulan/Tahun</label>
                        <div class="grid grid-cols-2 gap-3">
                            <select wire:model.live="filterMonth" class="border-gray-300 dark:border-gray-600 rounded-md text-sm w-full">
                                <option value="">Semua Bulan</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                                @endfor
                            </select>
                            <select wire:model.live="filterYear" class="border-gray-300 dark:border-gray-600 rounded-md text-sm w-full">
                                <option value="">Semua Tahun</option>
                                @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                @endif
                <button type="button" wire:click="loadPreview" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">Muat Pratinjau</button>
            </div>
        </div>

        {{-- Pratinjau --}}
        @if ($this->preview)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Pratinjau ({{ $this->preview['total'] }} baris pertama)</h3>
                @if ($this->tableLayout)
                    <p class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-700 rounded-md px-3 py-2 mb-3">Format final akan menggunakan layout struktural (kop surat, tabel grup, tanda tangan, dll).</p>
                @endif
                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                @foreach ($this->preview['headings'] as $heading)
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->preview['rows'] as $row)
                                <tr>
                                    @foreach ($this->preview['headings'] as $heading)
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $row[$heading] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 3. Isi Formulir (L3 only) --}}
        @if ($this->preview && ($this->tableLayout['type'] ?? '') === 'formulir')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">3. Isi Formulir</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Isi kolom manual. Keluar Jumlah dan Sisa Jumlah dihitung otomatis.</p>
                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md">
                    <table class="min-w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th rowspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center font-semibold">No</th>
                                <th rowspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center font-semibold">Nama Formulir</th>
                                <th colspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center font-semibold">Masuk</th>
                                <th colspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center font-semibold">Keluar</th>
                                <th colspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center font-semibold">Sisa</th>
                                <th rowspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center font-semibold">Keterangan</th>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-xs">Jumlah</th>
                                <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-xs">Seri (dari - sampai)</th>
                                <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-xs">Jumlah</th>
                                <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-xs">Seri (dari - sampai)</th>
                                <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-xs">Jumlah</th>
                                <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-xs">Seri (dari - sampai)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->manualData as $i => $row)
                                @php $isNaVersion = (($row['formulir'] ?? '') !== 'Model N' && ($row['formulir'] ?? '') !== 'Model DN' && ($row['formulir'] ?? '') !== 'Model NB'); @endphp
                                <tr>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center">{{ $i + 1 }}</td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-0.5">
                                        @if ($isNaVersion)
                                            <input type="text" wire:model.live="manualData.{{ $i }}.formulir" class="w-full border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5 font-medium" />
                                        @else
                                            <span class="text-xs font-medium px-1">{{ $row['formulir'] }}</span>
                                        @endif
                                    </td>
                                    {{-- Masuk --}}
                                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-0.5">
                                        <input type="text" wire:model.live="manualData.{{ $i }}.masuk_jumlah" class="w-full border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="0" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-0.5">
                                        <div class="flex gap-1">
                                            <input type="text" wire:model.live="manualData.{{ $i }}.masuk_seri_awal" class="w-1/2 border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="dari" />
                                            <input type="text" wire:model.live="manualData.{{ $i }}.masuk_seri_akhir" class="w-1/2 border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="sampai" />
                                        </div>
                                    </td>
                                    {{-- Keluar --}}
                                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-0.5">
                                        @if ($isNaVersion)
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ $this->manualData[$i]['keluar_jumlah'] ?? '' }}</span>
                                        @else
                                            <input type="text" wire:model.live="manualData.{{ $i }}.keluar_jumlah" class="w-full border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="0" />
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-0.5">
                                        <div class="flex gap-1">
                                            <input type="text" wire:model.live="manualData.{{ $i }}.keluar_seri_awal" class="w-1/2 border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="dari" />
                                            <input type="text" wire:model.live="manualData.{{ $i }}.keluar_seri_akhir" class="w-1/2 border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="sampai" />
                                        </div>
                                    </td>
                                    {{-- Sisa --}}
                                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-0.5">
                                        @if ($isNaVersion)
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ $this->manualData[$i]['sisa_jumlah'] ?? '' }}</span>
                                        @else
                                            <input type="text" wire:model.live="manualData.{{ $i }}.sisa_jumlah" class="w-full border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="0" />
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-0.5">
                                        <div class="flex gap-1">
                                            <input type="text" wire:model.live="manualData.{{ $i }}.sisa_seri_awal" class="w-1/2 border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="dari" />
                                            <input type="text" wire:model.live="manualData.{{ $i }}.sisa_seri_akhir" class="w-1/2 border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="sampai" />
                                        </div>
                                    </td>
                                    {{-- Keterangan --}}
                                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-0.5">
                                        <input type="text" wire:model.live="manualData.{{ $i }}.keterangan" class="w-full border-gray-300 dark:border-gray-600 rounded text-xs px-1 py-0.5" placeholder="Keterangan" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 4. Judul & Format Output --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ ($this->tableLayout['type'] ?? '') === 'formulir' ? '4' : '3' }}. Judul &amp; Format Output</h3>
            <input type="text" wire:model="title" placeholder="Judul laporan" class="mt-3 border-gray-300 dark:border-gray-600 rounded-md text-sm w-full" />
            @error('title') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                <label class="cursor-pointer border rounded-lg p-4 text-center {{ $this->format === 'pdf' ? 'border-red-500 bg-red-50 dark:bg-red-900/50 dark:border-red-700' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}">
                    <input type="radio" wire:model.live="format" value="pdf" class="sr-only" />
                    <p class="font-bold text-red-600 dark:text-red-400">PDF</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dokumen siap cetak</p>
                </label>
                <label class="cursor-pointer border rounded-lg p-4 text-center {{ $this->format === 'word' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/50 dark:border-blue-700' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}">
                    <input type="radio" wire:model.live="format" value="word" class="sr-only" />
                    <p class="font-bold text-blue-600 dark:text-blue-400">Word</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bisa diedit (.docx)</p>
                </label>
                <label class="cursor-pointer border rounded-lg p-4 text-center {{ $this->format === 'excel' ? 'border-green-500 bg-green-50 dark:bg-green-900/50 dark:border-green-700' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}">
                    <input type="radio" wire:model.live="format" value="excel" class="sr-only" />
                    <p class="font-bold text-green-600 dark:text-green-400">Excel</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Olah lanjut (.xlsx)</p>
                </label>
                <label class="cursor-pointer border rounded-lg p-4 text-center {{ $this->format === 'print' ? 'border-gray-500 bg-gray-100 dark:bg-gray-700 dark:border-gray-400' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}">
                    <input type="radio" wire:model.live="format" value="print" class="sr-only" />
                    <p class="font-bold text-gray-600 dark:text-gray-400">Print</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pratinjau cetak</p>
                </label>
            </div>

            @if ($this->format === 'pdf')
                <label class="block mt-4 text-sm text-gray-700 dark:text-gray-300">
                    Orientasi PDF:
                    <select wire:model.live="orientation" class="ml-2 border-gray-300 dark:border-gray-600 rounded-md text-sm">
                        <option value="portrait">Portrait</option>
                        <option value="landscape">Landscape</option>
                    </select>
                </label>
            @endif

            <div class="flex justify-end mt-6">
                <button type="button" wire:click="generate" wire:loading.attr="disabled" class="inline-flex items-center px-5 py-2 bg-blue-600 dark:bg-blue-500 text-white text-sm font-semibold rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 disabled:opacity-50">
                    <span wire:loading.remove wire:target="generate">Buat Laporan</span>
                    <span wire:loading wire:target="generate">Memproses...</span>
                </button>
            </div>
        </div>
    @endif
</div>
