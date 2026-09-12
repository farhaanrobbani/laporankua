<?php

use App\Models\Import;
use App\Models\ImportData;
use App\Services\MergeService;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $importId = null;

    /** @var int[] */
    public array $importIds = [];

    public string $joinColumn = '';

    public bool $isMergeMode = false;

    /** @var string[] */
    public array $columns = [];

    public string $search = '';

    public string $filterColumn = '';

    public string $filterValue = '';

    public string $sortColumn = 'row_number';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public int $mergePage = 1;

    /** @var int[] */
    public array $selected = [];

    public function mount(?int $importId = null, ?array $importIds = null, ?string $joinColumn = null): void
    {
        if ($importIds !== null && count($importIds) >= 2 && $joinColumn !== null && $joinColumn !== '') {
            $this->isMergeMode = true;
            $this->importIds = $importIds;
            $this->joinColumn = $joinColumn;
            $this->columns = app(MergeService::class)->getAllColumns($importIds);
            $this->sortColumn = $joinColumn;
        } elseif ($importId !== null) {
            $import = Import::where('user_id', auth()->id())->findOrFail($importId);
            $this->importId = $import->id;
            $this->columns = $import->availableColumns();
        }
    }

    public function updatingSearch(): void
    {
        $this->mergePage = 1;
        $this->resetPage();
    }

    public function updatingFilterColumn(): void
    {
        $this->mergePage = 1;
        $this->resetPage();
    }

    public function updatingFilterValue(): void
    {
        $this->mergePage = 1;
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->mergePage = 1;
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        $allowed = array_merge(['row_number'], $this->columns);

        if (! in_array($column, $allowed, true)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->mergePage = 1;
        $this->resetPage();
    }

    public function toggleSelectAll(bool $checked): void
    {
        if ($this->isMergeMode) {
            $this->selected = $checked ? range(1, count($this->filteredMergeRows())) : [];
        } else {
            $this->selected = $checked ? $this->baseQuery()->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
        }
    }

    public function deleteRecord(int $id): void
    {
        if ($this->isMergeMode) {
            return;
        }

        ImportData::forImport($this->importId)->whereKey($id)->firstOrFail()->delete();
        $this->selected = array_values(array_diff($this->selected, [$id]));
        session()->flash('status', 'Record berhasil dihapus.');
    }

    public function deleteSelected(): void
    {
        if ($this->isMergeMode) {
            return;
        }

        $this->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer',
        ]);

        $count = ImportData::forImport($this->importId)->whereKey($this->selected)->delete();
        $this->selected = [];
        $this->resetPage();
        session()->flash('status', $count.' record berhasil dihapus.');
    }

    public function getExportUrlProperty(): string
    {
        if ($this->isMergeMode) {
            return '#';
        }

        return route('data.export', [
            'import_id' => $this->importId,
            'search' => $this->search ?: null,
            'filter_column' => $this->filterColumn ?: null,
            'filter_value' => $this->filterValue ?: null,
            'sort_column' => $this->sortColumn !== 'row_number' ? $this->sortColumn : null,
            'sort_direction' => $this->sortDirection,
        ]);
    }

    public function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return ImportData::forImport($this->importId)
            ->search($this->search)
            ->filterColumn($this->filterColumn, $this->filterValue)
            ->sortBy($this->sortColumn, $this->sortDirection);
    }

    public function mergeQuery(): array
    {
        return app(MergeService::class)->mergeByColumn($this->importIds, $this->joinColumn);
    }

    public function filteredMergeRows(): array
    {
        $mergeResult = $this->mergeQuery();
        $rows = $mergeResult['rows'];

        if ($this->search !== '') {
            $lowerSearch = mb_strtolower($this->search);
            $rows = array_filter($rows, function ($row) use ($lowerSearch) {
                foreach ($row as $val) {
                    if ($val !== null && mb_strpos(mb_strtolower((string) $val), $lowerSearch) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        if ($this->filterColumn !== '' && $this->filterValue !== '') {
            $lowerFilter = mb_strtolower($this->filterValue);
            $filterCol = $this->filterColumn;
            $rows = array_filter($rows, function ($row) use ($filterCol, $lowerFilter) {
                $val = $row[$filterCol] ?? null;

                return $val !== null && mb_strpos(mb_strtolower((string) $val), $lowerFilter) !== false;
            });
        }

        $rows = array_values($rows);

        if ($this->sortColumn !== '' && $this->sortColumn !== 'row_number') {
            $sortCol = $this->sortColumn;
            $dir = $this->sortDirection;
            usort($rows, function ($a, $b) use ($sortCol, $dir) {
                $valA = $a[$sortCol] ?? '';
                $valB = $b[$sortCol] ?? '';
                $cmp = strcasecmp((string) $valA, (string) $valB);

                return $dir === 'desc' ? -$cmp : $cmp;
            });
        }

        return $rows;
    }

    public function mergePaginatedRows(): array
    {
        $rows = $this->filteredMergeRows();
        $offset = max(0, ($this->mergePage - 1) * $this->perPage);

        return array_slice($rows, $offset, $this->perPage);
    }

    public function gotoMergePage(int $page): void
    {
        $this->mergePage = max(1, $page);
    }

    public function nextMergePage(): void
    {
        $total = count($this->filteredMergeRows());
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        $this->mergePage = min($this->mergePage + 1, $lastPage);
    }

    public function prevMergePage(): void
    {
        $this->mergePage = max(1, $this->mergePage - 1);
    }
};
?>

<div>
    @if ($this->isMergeMode)
        <div class="mb-4 bg-blue-50 border border-blue-200 rounded-md px-4 py-3 flex items-center gap-2 text-sm text-blue-800">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
            <span>Mode Gabung: JOIN berdasarkan kolom <strong>{{ $this->joinColumn }}</strong> dari {{ count($this->importIds) }} file import</span>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-3 mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari data..." class="border-gray-300 rounded-md text-sm lg:w-64" />
        <select wire:model.live="filterColumn" class="border-gray-300 rounded-md text-sm">
            <option value="">Semua kolom</option>
            @foreach ($this->columns as $column)
                <option value="{{ $column }}">{{ $column }}</option>
            @endforeach
        </select>
        <input type="text" wire:model.live.debounce.300ms="filterValue" placeholder="Nilai filter..." class="border-gray-300 rounded-md text-sm lg:w-48" />
        <select wire:model.live="perPage" class="border-gray-300 rounded-md text-sm">
            <option value="10">10 / halaman</option>
            <option value="25">25 / halaman</option>
            <option value="50">50 / halaman</option>
            <option value="100">100 / halaman</option>
        </select>
        @if (! $this->isMergeMode)
            <a href="{{ $this->exportUrl }}" class="lg:ml-auto inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700">
                Export Excel
            </a>
        @endif
    </div>

    @if (! $this->isMergeMode && ! empty($this->selected))
        <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 rounded-md px-4 py-2 text-sm">
            <span class="text-red-800 font-medium">{{ count($this->selected) }} dipilih</span>
            <button type="button" wire:click="deleteSelected" wire:confirm="Hapus {{ count($this->selected) }} record yang dipilih?" class="px-3 py-1 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">Hapus Terpilih</button>
            <button type="button" wire:click="$set('selected', [])" class="text-xs text-gray-600 underline">Batal</button>
        </div>
    @endif

    @if ($this->isMergeMode)
        @php
            $allMergeRows = $this->filteredMergeRows();
            $mergeTotal = count($allMergeRows);
            $mergeLastPage = max(1, (int) ceil($mergeTotal / $this->perPage));
            $mergePage = max(1, $this->mergePage);
            $mergeOffset = ($mergePage - 1) * $this->perPage;
            $mergeRows = array_slice($allMergeRows, $mergeOffset, $this->perPage);
        @endphp
        @if (empty($mergeRows))
            <div class="text-center py-8">
                <p class="text-gray-500 font-medium">Tidak ada data ditemukan</p>
                <p class="text-gray-400 text-sm mt-1">Ubah kata kunci pencarian atau filter.</p>
            </div>
        @else
            <div class="overflow-x-auto border border-gray-200 rounded-md">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach ($this->columns as $column)
                                <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">
                                    <button type="button" wire:click="sortBy('{{ $column }}')" class="hover:text-gray-800">
                                        {{ $column }}
                                        @if ($column === $this->joinColumn)
                                            <span class="text-blue-600 text-xs">(JOIN)</span>
                                        @endif
                                        @if ($this->sortColumn === $column) {{ $this->sortDirection === 'asc' ? '↑' : '↓' }} @endif
                                    </button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($mergeRows as $record)
                            <tr>
                                @foreach ($this->columns as $column)
                                    <td class="px-3 py-2 text-gray-700 whitespace-nowrap max-w-64 truncate" title="{{ $record[$column] ?? '' }}">
                                        {{ $record[$column] ?? '' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($mergeLastPage > 1)
                <div class="mt-4 flex items-center justify-between">
                    <p class="text-sm text-gray-700">
                        Menampilkan <span class="font-medium">{{ $mergeOffset + 1 }}</span> - <span class="font-medium">{{ min($mergeOffset + $this->perPage, $mergeTotal) }}</span> dari <span class="font-medium">{{ $mergeTotal }}</span> data
                    </p>
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="gotoMergePage(1)" @disabled($mergePage <= 1)
                            class="px-3 py-1 text-sm border rounded {{ $mergePage <= 1 ? 'text-gray-300 border-gray-200 cursor-not-allowed' : 'text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            &laquo;
                        </button>
                        <button type="button" wire:click="prevMergePage()" @disabled($mergePage <= 1)
                            class="px-3 py-1 text-sm border rounded {{ $mergePage <= 1 ? 'text-gray-300 border-gray-200 cursor-not-allowed' : 'text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            &lsaquo;
                        </button>
                        @php
                            $startPage = max(1, $mergePage - 2);
                            $endPage = min($mergeLastPage, $mergePage + 2);
                        @endphp
                        @for ($i = $startPage; $i <= $endPage; $i++)
                            <button type="button" wire:click="gotoMergePage({{ $i }})"
                                class="px-3 py-1 text-sm border rounded {{ $i === $mergePage ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                                {{ $i }}
                            </button>
                        @endfor
                        <button type="button" wire:click="nextMergePage()" @disabled($mergePage >= $mergeLastPage)
                            class="px-3 py-1 text-sm border rounded {{ $mergePage >= $mergeLastPage ? 'text-gray-300 border-gray-200 cursor-not-allowed' : 'text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            &rsaquo;
                        </button>
                        <button type="button" wire:click="gotoMergePage({{ $mergeLastPage }})" @disabled($mergePage >= $mergeLastPage)
                            class="px-3 py-1 text-sm border rounded {{ $mergePage >= $mergeLastPage ? 'text-gray-300 border-gray-200 cursor-not-allowed' : 'text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            &raquo;
                        </button>
                    </div>
                </div>
            @endif
        @endif
    @else
        @php($records = $this->baseQuery()->paginate($this->perPage))
        @if ($records->isEmpty())
            <div class="text-center py-8">
                <p class="text-gray-500 font-medium">Tidak ada data ditemukan</p>
                <p class="text-gray-400 text-sm mt-1">Ubah kata kunci pencarian atau filter.</p>
            </div>
        @else
            <div class="overflow-x-auto border border-gray-200 rounded-md">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2">
                                <input type="checkbox" @checked(count($this->selected) > 0) wire:change="toggleSelectAll($event.target.checked)" class="rounded" />
                            </th>
                            @foreach ($this->columns as $column)
                                <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">
                                    <button type="button" wire:click="sortBy('{{ $column }}')" class="hover:text-gray-800">
                                        {{ $column }}
                                        @if ($this->sortColumn === $column) {{ $this->sortDirection === 'asc' ? '↑' : '↓' }} @endif
                                    </button>
                                </th>
                            @endforeach
                            <th class="px-3 py-2 text-right font-medium text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-3 py-2"><input type="checkbox" wire:model.live="selected" value="{{ $record->id }}" class="rounded" /></td>
                                @foreach ($this->columns as $column)
                                    <td class="px-3 py-2 text-gray-700 whitespace-nowrap max-w-64 truncate" title="{{ $record->row_data[$column] ?? '' }}">
                                        {{ $record->row_data[$column] ?? '' }}
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('data.show', $record) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                                    <button type="button" wire:click="deleteRecord({{ $record->id }})" wire:confirm="Hapus record ini?" class="ml-2 text-red-600 hover:text-red-800 text-sm font-medium">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $records->links() }}</div>
        @endif
    @endif
</div>
