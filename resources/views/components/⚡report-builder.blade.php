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

    /** @var array<int, array{id: int, file_name: string}> */
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

    /** @var array{headings: string[], rows: array, total: int}|null */
    public ?array $preview = null;

    public bool $hasDefaultTemplate = false;

    public string $templateName = '';

    public bool $isMergeMode = false;

    /** @var int[] */
    public array $mergeImportIds = [];

    public string $joinColumn = '';

    /** @var string[] */
    public array $sharedColumns = [];

    public function mount(): void
    {
        $this->imports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->latest()
            ->get(['id', 'file_name'])
            ->map(fn (Import $import) => ['id' => $import->id, 'file_name' => $import->file_name])
            ->all();

        $this->hasDefaultTemplate = ReportTemplate::where('user_id', auth()->id())
            ->where('is_default', true)
            ->exists();
    }

    public function updatedMergeImportIds(): void
    {
        $this->sharedColumns = app(MergeService::class)->getSharedColumns($this->mergeImportIds);
        $this->joinColumn = '';
        $this->columns = [];
        $this->fields = [];
        $this->preview = null;

        if (count($this->mergeImportIds) >= 2) {
            $this->columns = app(MergeService::class)->getAllColumns($this->mergeImportIds);
            $this->fields = $this->columns;
        }
    }

    public function updatedJoinColumn(): void
    {
        $this->preview = null;
    }

    public function loadDefaultTemplate(): void
    {
        $template = ReportTemplate::where('user_id', auth()->id())
            ->where('is_default', true)
            ->first();

        if (! $template) {
            return;
        }

        $this->applyTemplateConfig($template);
    }

    public function saveAsTemplate(): void
    {
        $this->validate([
            'templateName' => 'required|string|max:255',
        ]);

        if ($this->isMergeMode) {
            if (count($this->mergeImportIds) < 2 || $this->joinColumn === '') {
                $this->addError('importId', 'Pilih minimal 2 file import dan kolom penggabung.');

                return;
            }
        } else {
            $this->validate(['importId' => 'required|integer']);

            $import = $this->selectedImport();
            if (! $import) {
                $this->addError('importId', 'Sumber data tidak valid.');

                return;
            }
        }

        ReportTemplate::create([
            'user_id' => auth()->id(),
            'name' => $this->templateName,
            'output_format' => $this->format,
            'fields_json' => $this->fields,
            'filters_json' => [
                'search' => $this->search ?: null,
                'filter_column' => $this->filterColumn ?: null,
                'filter_value' => $this->filterValue ?: null,
            ],
            'sorting_json' => [
                'column' => $this->sortColumn ?: null,
                'direction' => $this->sortDirection,
            ],
            'layout_json' => ['orientation' => $this->orientation],
            'is_default' => false,
        ]);

        $this->templateName = '';
        session()->flash('template_saved', 'Template berhasil disimpan.');
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

        if ($this->importId) {
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
    }

    public function updatedImportId(): void
    {
        if ($this->isMergeMode) {
            return;
        }

        $this->reset(['columns', 'fields', 'preview', 'filterColumn', 'filterValue', 'sortColumn', 'search']);

        $import = $this->selectedImport();

        if (! $import) {
            return;
        }

        $this->columns = $import->availableColumns();
        $this->fields = $this->columns;

        if ($this->title === '') {
            $this->title = 'Laporan '.pathinfo($import->file_name, PATHINFO_FILENAME);
        }

        $this->loadPreview();
    }

    public function loadPreview(): void
    {
        if ($this->isMergeMode) {
            $this->loadMergePreview();

            return;
        }

        $import = $this->selectedImport();

        if (! $import || $this->fields === []) {
            $this->preview = null;

            return;
        }

        $dataset = app(ReportGenerationService::class)->buildDataset(
            $import,
            $this->fields,
            $this->search ?: null,
            $this->filterColumn ?: null,
            $this->filterValue ?: null,
            $this->sortColumn ?: null,
            $this->sortDirection,
            10,
        );

        $this->preview = [
            'headings' => $dataset['headings'],
            'rows' => $dataset['rows'],
            'total' => $dataset['total'],
        ];
    }

    private function loadMergePreview(): void
    {
        if (count($this->mergeImportIds) < 2 || $this->joinColumn === '' || $this->fields === []) {
            $this->preview = null;

            return;
        }

        $dataset = app(MergeService::class)->buildMergedDataset(
            $this->mergeImportIds,
            $this->joinColumn,
            $this->fields,
            $this->search ?: null,
            $this->filterColumn ?: null,
            $this->filterValue ?: null,
            $this->sortColumn ?: null,
            $this->sortDirection,
            10,
        );

        $this->preview = [
            'headings' => $dataset['headings'],
            'rows' => $dataset['rows'],
            'total' => $dataset['total'],
        ];
    }

    public function toggleField(string $column): void
    {
        if (in_array($column, $this->fields, true)) {
            $this->fields = array_values(array_diff($this->fields, [$column]));
        } else {
            $this->fields[] = $column;
        }
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

        $report = Report::create([
            'user_id' => auth()->id(),
            'import_id' => $import->id,
            'title' => $this->title,
            'output_format' => $this->format,
            'config_json' => [
                'fields' => $fields === [] ? $import->availableColumns() : $fields,
                'search' => $this->search ?: null,
                'filter_column' => $this->filterColumn ?: null,
                'filter_value' => $this->filterValue ?: null,
                'sort_column' => $this->sortColumn ?: null,
                'sort_direction' => $this->sortDirection,
                'orientation' => $this->orientation,
            ],
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
            'joinColumn' => 'required|string',
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

        $report = Report::create([
            'user_id' => auth()->id(),
            'import_id' => $firstImport->id,
            'title' => $this->title,
            'output_format' => $this->format,
            'config_json' => [
                'fields' => $this->fields,
                'search' => $this->search ?: null,
                'filter_column' => $this->filterColumn ?: null,
                'filter_value' => $this->filterValue ?: null,
                'sort_column' => $this->sortColumn ?: null,
                'sort_direction' => $this->sortDirection,
                'orientation' => $this->orientation,
                'is_merged' => true,
                'merged_import_ids' => $this->mergeImportIds,
                'join_column' => $this->joinColumn,
            ],
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
};
?>

<div class="space-y-6">
    {{-- 1. Sumber data --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="font-semibold text-gray-900 mb-1">1. Sumber Data</h3>
        <p class="text-sm text-gray-500 mb-3">Pilih file import yang akan dijadikan laporan.</p>

        {{-- Mode Toggle --}}
        <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1 w-fit mb-4">
            <button type="button" wire:click="$set('isMergeMode', false)"
                class="px-4 py-2 text-sm font-medium rounded-md transition {{ ! $this->isMergeMode ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                Single Import
            </button>
            <button type="button" wire:click="$set('isMergeMode', true)"
                class="px-4 py-2 text-sm font-medium rounded-md transition {{ $this->isMergeMode ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                Gabung Data
            </button>
        </div>

        @if ($this->isMergeMode)
            {{-- Merge Mode --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih file import (minimal 2)</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($this->imports as $import)
                        <label class="flex items-center gap-2 text-sm text-gray-700 border border-gray-200 rounded-md px-3 py-2 cursor-pointer hover:border-blue-400">
                            <input type="checkbox" wire:model.live="mergeImportIds" value="{{ $import['id'] }}" class="rounded text-blue-600" />
                            {{ $import['file_name'] }}
                        </label>
                    @endforeach
                </div>

                @if (count($this->mergeImportIds) >= 2)
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kolom penggabung (JOIN key)</label>
                        <select wire:model.live="joinColumn" class="border-gray-300 rounded-md text-sm w-full sm:w-auto">
                            <option value="">-- Pilih kolom --</option>
                            @foreach ($this->sharedColumns as $col)
                                <option value="{{ $col }}">{{ $col }}</option>
                            @endforeach
                        </select>
                        @if (empty($this->sharedColumns))
                            <p class="text-xs text-amber-600 mt-1">Tidak ada kolom yang sama antara file yang dipilih.</p>
                        @endif
                    </div>
                @endif
            </div>
        @else
            {{-- Single Mode --}}
            @if (empty($this->imports))
                <p class="text-sm text-gray-500">Belum ada data import yang berhasil. <a href="{{ route('imports.create') }}" class="text-blue-600 font-medium">Upload Excel dulu</a>.</p>
            @else
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                    <select wire:model.live="importId" class="border-gray-300 rounded-md text-sm w-full sm:w-auto">
                        <option value="">-- Pilih file import --</option>
                        @foreach ($this->imports as $import)
                            <option value="{{ $import['id'] }}">{{ $import['file_name'] }}</option>
                        @endforeach
                    </select>
                    @if ($this->hasDefaultTemplate)
                        <button type="button" wire:click="loadDefaultTemplate" class="text-sm text-purple-600 hover:text-purple-800 font-medium">Muat template default</button>
                    @endif
                </div>
            @endif
        @endif
        @error('importId') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @if (($this->isMergeMode && count($this->mergeImportIds) >= 2 && $this->joinColumn !== '') || (! $this->isMergeMode && $this->importId && ! empty($this->columns)))
        {{-- 2. Kolom --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-1">2. Kolom Laporan</h3>
            <p class="text-sm text-gray-500 mb-3">Centang kolom yang ditampilkan.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                @foreach ($this->columns as $column)
                    <label class="flex items-center gap-2 text-sm text-gray-700 border border-gray-200 rounded-md px-3 py-2 cursor-pointer hover:border-blue-400">
                        <input type="checkbox" wire:click="toggleField('{{ $column }}')" @checked(in_array($column, $this->fields, true)) class="rounded text-blue-600" />
                        {{ $column }}
                        @if ($this->isMergeMode && $column === $this->joinColumn)
                            <span class="text-xs text-blue-600 font-medium">(JOIN)</span>
                        @endif
                    </label>
                @endforeach
            </div>
            @error('fields') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- 3. Filter & Urutan --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-1">3. Filter &amp; Urutan</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari..." class="border-gray-300 rounded-md text-sm" />
                <div class="flex gap-2">
                    <select wire:model.live="filterColumn" class="border-gray-300 rounded-md text-sm flex-1">
                        <option value="">Tanpa filter kolom</option>
                        @foreach ($this->columns as $column)
                            <option value="{{ $column }}">{{ $column }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model.live.debounce.300ms="filterValue" placeholder="Nilai" class="border-gray-300 rounded-md text-sm flex-1" />
                </div>
                <div class="flex gap-2">
                    <select wire:model.live="sortColumn" class="border-gray-300 rounded-md text-sm flex-1">
                        <option value="">Tanpa urutan khusus</option>
                        @foreach ($this->columns as $column)
                            <option value="{{ $column }}">{{ $column }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="sortDirection" class="border-gray-300 rounded-md text-sm">
                        <option value="asc">A → Z</option>
                        <option value="desc">Z → A</option>
                    </select>
                </div>
                <button type="button" wire:click="loadPreview" class="px-4 py-2 bg-gray-100 text-gray-800 text-sm font-semibold rounded-md hover:bg-gray-200">Muat Pratinjau</button>
            </div>
        </div>

        {{-- Pratinjau --}}
        @if ($this->preview)
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Pratinjau ({{ $this->preview['total'] }} baris pertama)</h3>
                <div class="overflow-x-auto border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach ($this->preview['headings'] as $heading)
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($this->preview['rows'] as $row)
                                <tr>
                                    @foreach ($this->preview['headings'] as $heading)
                                        <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $row[$heading] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 4. Format & Generate --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-1">4. Judul &amp; Format Output</h3>
            <input type="text" wire:model="title" placeholder="Judul laporan" class="mt-3 border-gray-300 rounded-md text-sm w-full" />
            @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                <label class="cursor-pointer border rounded-lg p-4 text-center {{ $this->format === 'pdf' ? 'border-red-500 bg-red-50' : 'border-gray-200 hover:border-gray-400' }}">
                    <input type="radio" wire:model.live="format" value="pdf" class="sr-only" />
                    <p class="font-bold text-red-600">PDF</p>
                    <p class="text-xs text-gray-500 mt-1">Dokumen siap cetak</p>
                </label>
                <label class="cursor-pointer border rounded-lg p-4 text-center {{ $this->format === 'word' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-400' }}">
                    <input type="radio" wire:model.live="format" value="word" class="sr-only" />
                    <p class="font-bold text-blue-600">Word</p>
                    <p class="text-xs text-gray-500 mt-1">Bisa diedit (.docx)</p>
                </label>
                <label class="cursor-pointer border rounded-lg p-4 text-center {{ $this->format === 'excel' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-400' }}">
                    <input type="radio" wire:model.live="format" value="excel" class="sr-only" />
                    <p class="font-bold text-green-600">Excel</p>
                    <p class="text-xs text-gray-500 mt-1">Olah lanjut (.xlsx)</p>
                </label>
                <label class="cursor-pointer border rounded-lg p-4 text-center {{ $this->format === 'print' ? 'border-gray-500 bg-gray-100' : 'border-gray-200 hover:border-gray-400' }}">
                    <input type="radio" wire:model.live="format" value="print" class="sr-only" />
                    <p class="font-bold text-gray-600">Print</p>
                    <p class="text-xs text-gray-500 mt-1">Pratinjau cetak</p>
                </label>
            </div>

            @if ($this->format === 'pdf')
                <label class="block mt-4 text-sm text-gray-700">
                    Orientasi PDF:
                    <select wire:model.live="orientation" class="ml-2 border-gray-300 rounded-md text-sm">
                        <option value="portrait">Portrait</option>
                        <option value="landscape">Landscape</option>
                    </select>
                </label>
            @endif

            <div class="flex justify-end mt-6">
                <button type="button" wire:click="generate" wire:loading.attr="disabled" class="inline-flex items-center px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="generate">Buat Laporan</span>
                    <span wire:loading wire:target="generate">Memproses...</span>
                </button>
            </div>
        </div>

        {{-- 5. Simpan sebagai template --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-1">5. Simpan sebagai Template <span class="font-normal text-gray-500">(opsional)</span></h3>
            <p class="text-sm text-gray-500 mb-3">Simpan konfigurasi di atas untuk dipakai berulang.</p>
            @if (session('template_saved'))
                <p class="mb-3 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-4 py-2">{{ session('template_saved') }}</p>
            @endif
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" wire:model="templateName" placeholder="Nama template, misal: Laporan Bulanan" class="border-gray-300 rounded-md text-sm flex-1" />
                <button type="button" wire:click="saveAsTemplate" class="px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-md hover:bg-purple-700">Simpan Template</button>
            </div>
            @error('templateName') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif
</div>
