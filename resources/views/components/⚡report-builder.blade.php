<?php

use App\Jobs\GenerateReport;
use App\Models\Import;
use App\Models\Report;
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

    public function mount(): void
    {
        $this->imports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->latest()
            ->get(['id', 'file_name'])
            ->map(fn (Import $import) => ['id' => $import->id, 'file_name' => $import->file_name])
            ->all();
    }

    public function updatedImportId(): void
    {
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
        @if (empty($this->imports))
            <p class="text-sm text-gray-500">Belum ada data import yang berhasil. <a href="{{ route('imports.create') }}" class="text-blue-600 font-medium">Upload Excel dulu</a>.</p>
        @else
            <select wire:model.live="importId" class="border-gray-300 rounded-md text-sm w-full sm:w-auto">
                <option value="">-- Pilih file import --</option>
                @foreach ($this->imports as $import)
                    <option value="{{ $import['id'] }}">{{ $import['file_name'] }}</option>
                @endforeach
            </select>
        @endif
        @error('importId') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @if ($this->importId && ! empty($this->columns))
        {{-- 2. Kolom --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-1">2. Kolom Laporan</h3>
            <p class="text-sm text-gray-500 mb-3">Centang kolom yang ditampilkan.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                @foreach ($this->columns as $column)
                    <label class="flex items-center gap-2 text-sm text-gray-700 border border-gray-200 rounded-md px-3 py-2 cursor-pointer hover:border-blue-400">
                        <input type="checkbox" wire:click="toggleField('{{ $column }}')" @checked(in_array($column, $this->fields, true)) class="rounded text-blue-600" />
                        {{ $column }}
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
    @endif
</div>
