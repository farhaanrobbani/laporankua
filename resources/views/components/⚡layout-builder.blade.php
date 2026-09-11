<?php

use App\Models\Import;
use App\Models\ImportData;
use App\Models\Report;
use App\Models\ReportTemplate;
use Livewire\Component;

new class extends Component
{
    public ?int $importId = null;

    /** @var string[] */
    public array $fields = [];

    /** @var array<int, array{column: string, x: float, y: float, font_size: int, bold: bool}> */
    public array $layoutFields = [];

    public ?int $selectedRecordId = null;

    public ?int $selectedField = null;

    /** @var array<string, mixed> */
    public array $previewRecord = [];

    public int $totalRecords = 0;

    public ?int $reportId = null;

    public string $templateName = 'Cetak NB';

    public function mount(?int $importId = null, array $fields = [], ?int $reportId = null): void
    {
        $this->importId = $importId;
        $this->fields = $fields;
        $this->reportId = $reportId;

        if ($importId && $fields !== []) {
            $this->totalRecords = ImportData::where('import_id', $importId)->count();
            $this->initDefaultLayout();
        }

        if ($reportId) {
            $report = Report::find($reportId);
            if ($report && ! empty($report->config_json['custom_layout']['fields'])) {
                $this->layoutFields = $report->config_json['custom_layout']['fields'];
            }
        }
    }

    private function initDefaultLayout(): void
    {
        if ($this->layoutFields !== []) {
            return;
        }

        $this->layoutFields = [];
        $y = 30;

        foreach ($this->fields as $index => $field) {
            $this->layoutFields[] = [
                'column' => $field,
                'x' => $index === 0 ? 20.0 : ($index === 1 ? 110.0 : 20.0),
                'y' => $index === 0 ? $y : ($index === 1 ? $y + 20 : $y + 40),
                'font_size' => 12,
                'bold' => false,
            ];
        }
    }

    public function selectField(?int $index): void
    {
        $this->selectedField = $index;
    }

    public function removeField(int $index): void
    {
        unset($this->layoutFields[$index]);
        $this->layoutFields = array_values($this->layoutFields);
        $this->selectedField = null;
    }

    public function updateFieldX(int $index, float $value): void
    {
        if (isset($this->layoutFields[$index])) {
            $this->layoutFields[$index]['x'] = round($value, 1);
        }
    }

    public function updateFieldY(int $index, float $value): void
    {
        if (isset($this->layoutFields[$index])) {
            $this->layoutFields[$index]['y'] = round($value, 1);
        }
    }

    public function updateFieldFontSize(int $index, int $value): void
    {
        if (isset($this->layoutFields[$index])) {
            $this->layoutFields[$index]['font_size'] = max(8, min(72, $value));
        }
    }

    public function updateFieldBold(int $index, bool $value): void
    {
        if (isset($this->layoutFields[$index])) {
            $this->layoutFields[$index]['bold'] = $value;
        }
    }

    public function loadRecord(): void
    {
        if ($this->selectedRecordId === null || $this->importId === null) {
            $this->previewRecord = [];

            return;
        }

        $record = ImportData::where('id', $this->selectedRecordId)
            ->where('import_id', $this->importId)
            ->first();

        $this->previewRecord = $record ? ($record->row_data ?? []) : [];
    }

    public function getRecordsProperty(): array
    {
        if ($this->importId === null) {
            return [];
        }

        return ImportData::where('import_id', $this->importId)
            ->orderBy('row_number')
            ->take(200)
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'row_number' => $r->row_number, 'label' => '#'.$r->row_number.' — '.$this->getRowLabel($r->row_data)])
            ->toArray();
    }

    private function getRowLabel(array $rowData): string
    {
        foreach (['Nomor Akta', 'Nama', 'NIK', 'ID'] as $key) {
            if (isset($rowData[$key]) && $rowData[$key] !== null && $rowData[$key] !== '') {
                return (string) $rowData[$key];
            }
        }

        $first = reset($rowData);

        return $first !== false ? (string) $first : '-';
    }

    public function saveLayout(): void
    {
        if ($this->importId === null || $this->layoutFields === []) {
            return;
        }

        $report = Report::find($this->reportId);

        if (! $report) {
            $import = Import::where('id', $this->importId)->where('user_id', auth()->id())->first();

            if (! $import) {
                return;
            }

            $report = Report::create([
                'user_id' => auth()->id(),
                'import_id' => $import->id,
                'title' => $this->templateName,
                'output_format' => 'print',
                'config_json' => [
                    'fields' => $this->fields,
                    'custom_layout' => ['fields' => $this->layoutFields],
                ],
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            $this->reportId = $report->id;
        } else {
            $config = $report->config_json ?? [];
            $config['custom_layout'] = ['fields' => $this->layoutFields];
            $report->update(['config_json' => $config]);
        }

        $this->dispatch('layout-saved', reportId: $report->id);
    }

    public function saveAsTemplate(): void
    {
        if ($this->layoutFields === []) {
            return;
        }

        $existingTemplate = ReportTemplate::where('user_id', auth()->id())
            ->where('name', $this->templateName)
            ->first();

        if ($existingTemplate) {
            $existingTemplate->update([
                'fields_json' => $this->fields,
                'layout_json' => ['custom_layout' => ['fields' => $this->layoutFields]],
            ]);
        } else {
            ReportTemplate::create([
                'user_id' => auth()->id(),
                'name' => $this->templateName,
                'description' => 'Template cetak NB dengan posisi custom',
                'output_format' => 'print',
                'fields_json' => $this->fields,
                'layout_json' => ['custom_layout' => ['fields' => $this->layoutFields]],
                'is_default' => false,
            ]);
        }

        session()->flash('template_saved', 'Template "'.$this->templateName.'" berhasil disimpan.');
    }

    public function loadTemplate(): void
    {
        $template = ReportTemplate::where('user_id', auth()->id())
            ->where('name', $this->templateName)
            ->first();

        if (! $template) {
            return;
        }

        $layout = $template->layout_json['custom_layout']['fields'] ?? [];

        if ($layout !== []) {
            $this->layoutFields = $layout;
        }
    }

    public function goToPrint(): void
    {
        if ($this->reportId === null) {
            $this->saveLayout();
        }

        if ($this->reportId === null || $this->selectedRecordId === null) {
            return;
        }

        $this->redirect(route('reports.custom-print', ['report' => $this->reportId, 'record' => $this->selectedRecordId]));
    }
};
?>

<div class="space-y-4">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-semibold text-gray-900">Custom Layout — Cetak NB</h3>
                <p class="text-sm text-gray-500">Drag &amp; drop field ke posisi yang diinginkan di canvas A4.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="loadTemplate" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">
                    Muat Template
                </button>
                <button type="button" wire:click="saveAsTemplate" class="px-3 py-1.5 bg-purple-600 text-white text-sm font-semibold rounded-md hover:bg-purple-700">
                    Simpan Template
                </button>
                <button type="button" wire:click="saveLayout" class="px-3 py-1.5 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                    Simpan Layout
                </button>
            </div>
        </div>

        <div class="flex gap-4">
            {{-- Field List --}}
            <div class="w-48 shrink-0">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Field</h4>
                <div class="space-y-1">
                    @foreach ($this->layoutFields as $index => $field)
                        <div wire:click="selectField({{ $index }})"
                             class="flex items-center gap-2 text-sm text-gray-700 border rounded-md px-3 py-2 cursor-pointer hover:border-blue-400 {{ $this->selectedField === $index ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                            <span class="truncate flex-1">{{ $field['column'] }}</span>
                            <span class="text-xs text-gray-400">({{ $field['x'] }}, {{ $field['y'] }})</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Canvas --}}
            <div class="flex-1 overflow-auto">
                <div class="mb-3 flex items-center gap-4">
                    <label class="text-sm text-gray-700">
                        Record:
                        <select wire:model.live="selectedRecordId" wire:change="loadRecord" class="ml-2 border-gray-300 rounded-md text-sm">
                            <option value="">-- Pilih record --</option>
                            @foreach ($this->records as $record)
                                <option value="{{ $record['id'] }}">{{ $record['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    @if ($this->totalRecords > 0)
                        <span class="text-xs text-gray-400">Total {{ number_format($this->totalRecords) }} record</span>
                    @endif
                </div>

                <div class="relative bg-white border border-gray-300 shadow-sm" style="width: 794px; height: 1123px; transform: scale(0.7); transform-origin: top left;" id="canvas">
                    @foreach ($this->layoutFields as $index => $field)
                        <div wire:click="selectField({{ $index }})"
                             class="absolute cursor-move border border-dashed rounded px-2 py-1 text-sm select-none {{ $this->selectedField === $index ? 'border-blue-600 bg-blue-100' : 'border-blue-400 bg-blue-50 hover:bg-blue-100' }}"
                             style="left: {{ $field['x'] * 3.7795 }}px; top: {{ $field['y'] * 3.7795 }}px; font-size: {{ $field['font_size'] }}px; {{ $field['bold'] ? 'font-weight:bold;' : '' }}"
                             data-index="{{ $index }}"
                             onmousedown="startDrag(event, this, {{ $index }})">
                            @if (! empty($previewRecord[$field['column']]))
                                {{ $previewRecord[$field['column']] }}
                            @else
                                <span class="text-gray-400 italic">{{ $field['column'] }}</span>
                            @endif
                        </div>
                    @endforeach

                    <div class="absolute bottom-2 right-3 text-xs text-gray-300">A4 — 210 × 297 mm</div>
                </div>
            </div>

            {{-- Properties Panel --}}
            @if ($this->selectedField !== null && isset($this->layoutFields[$this->selectedField]))
                @php $sf = $this->layoutFields[$this->selectedField]; @endphp
                <div class="w-56 shrink-0">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Properti</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Kolom</label>
                            <p class="text-sm font-medium text-gray-900">{{ $sf['column'] }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Posisi X (mm)</label>
                            <input type="number" step="0.5" wire:model.live="layoutFields.{{ $this->selectedField }}.x"
                                   class="w-full border-gray-300 rounded-md text-sm" min="0" max="210" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Posisi Y (mm)</label>
                            <input type="number" step="0.5" wire:model.live="layoutFields.{{ $this->selectedField }}.y"
                                   class="w-full border-gray-300 rounded-md text-sm" min="0" max="297" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Ukuran Font (px)</label>
                            <input type="number" wire:model.live="layoutFields.{{ $this->selectedField }}.font_size"
                                   class="w-full border-gray-300 rounded-md text-sm" min="8" max="72" />
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model.live="layoutFields.{{ $this->selectedField }}.bold" class="rounded text-blue-600" />
                                Bold
                            </label>
                        </div>
                        <button type="button" wire:click="removeField({{ $this->selectedField }})"
                                class="w-full px-3 py-1.5 bg-red-50 text-red-600 text-sm font-medium rounded-md hover:bg-red-100">
                            Hapus Field
                        </button>
                    </div>
                </div>
            @endif
        </div>

        @if (session('template_saved'))
            <p class="mt-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-4 py-2">{{ session('template_saved') }}</p>
        @endif
    </div>
</div>

<script>
let dragState = null;

function startDrag(e, el, index) {
    e.preventDefault();
    const canvas = document.getElementById('canvas');
    const canvasRect = canvas.getBoundingClientRect();
    const elRect = el.getBoundingClientRect();

    dragState = {
        index: index,
        offsetX: e.clientX - elRect.left,
        offsetY: e.clientY - elRect.top,
        canvas: canvas,
        canvasRect: canvasRect,
        el: el,
        wireId: el.closest('[wire\\:id]').getAttribute('wire:id')
    };

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
}

function onDrag(e) {
    if (!dragState) return;

    const scale = 0.7;
    let x = (e.clientX - dragState.canvasRect.left) / scale - dragState.offsetX / scale;
    let y = (e.clientY - dragState.canvasRect.top) / scale - dragState.offsetY / scale;

    x = Math.max(0, Math.min(794 / 3.7795, x / 3.7795));
    y = Math.max(0, Math.min(1123 / 3.7795, y / 3.7795));

    x = Math.round(x * 2) / 2;
    y = Math.round(y * 2) / 2;

    dragState.el.style.left = (x * 3.7795) + 'px';
    dragState.el.style.top = (y * 3.7795) + 'px';

    const component = Livewire.find(dragState.wireId);
    if (component) {
        component.set('layoutFields.' + dragState.index + '.x', x);
        component.set('layoutFields.' + dragState.index + '.y', y);
    }
}

function stopDrag() {
    dragState = null;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
}
</script>
