<?php

use App\Models\Import;
use App\Models\ImportData;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $importId;

    /** @var string[] */
    public array $columns = [];

    public string $search = '';

    public string $filterColumn = '';

    public string $filterValue = '';

    public string $sortColumn = 'row_number';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    /** @var int[] */
    public array $selected = [];

    public function mount(int $importId): void
    {
        $import = Import::where('user_id', auth()->id())->findOrFail($importId);

        $this->importId = $import->id;
        $this->columns = $import->availableColumns();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterColumn(): void
    {
        $this->resetPage();
    }

    public function updatingFilterValue(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
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

        $this->resetPage();
    }

    public function toggleSelectAll(bool $checked): void
    {
        if ($checked) {
            $this->selected = $this->baseQuery()->pluck('id')->map(fn ($id) => (int) $id)->all();
        } else {
            $this->selected = [];
        }
    }

    public function deleteRecord(int $id): void
    {
        ImportData::forImport($this->importId)->whereKey($id)->firstOrFail()->delete();

        $this->selected = array_values(array_diff($this->selected, [$id]));

        session()->flash('status', 'Record berhasil dihapus.');
    }

    public function deleteSelected(): void
    {
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
        return route('data.export', [
            'import_id' => $this->importId,
            'search' => $this->search ?: null,
            'filter_column' => $this->filterColumn ?: null,
            'filter_value' => $this->filterValue ?: null,
            'sort_column' => $this->sortColumn !== 'row_number' ? $this->sortColumn : null,
            'sort_direction' => $this->sortDirection,
        ]);
    }

    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return ImportData::forImport($this->importId)
            ->search($this->search)
            ->filterColumn($this->filterColumn, $this->filterValue)
            ->sortBy($this->sortColumn, $this->sortDirection);
    }

    private function records(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->baseQuery()->paginate($this->perPage);
    }
};
?>

<div>
    @if (session('status'))
        <div class="mb-4 bg-green-100 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
            {{ session('status') }}
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
        <a href="{{ $this->exportUrl }}" class="lg:ml-auto inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700">
            Export Excel
        </a>
    </div>

    @if (! empty($this->selected))
        <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 rounded-md px-4 py-2 text-sm">
            <span class="text-red-800 font-medium">{{ count($this->selected) }} dipilih</span>
            <button type="button" wire:click="deleteSelected" wire:confirm="Hapus {{ count($this->selected) }} record yang dipilih?" class="px-3 py-1 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">Hapus Terpilih</button>
            <button type="button" wire:click="$set('selected', [])" class="text-xs text-gray-600 underline">Batal</button>
        </div>
    @endif

    @php($records = $this->records())
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
                        <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('row_number')" class="hover:text-gray-800"># @if ($this->sortColumn === 'row_number') {{ $this->sortDirection === 'asc' ? '↑' : '↓' }} @endif</button>
                        </th>
                        @foreach ($this->columns as $column)
                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">
                                <button type="button" wire:click="sortBy('{{ $column }}')" class="hover:text-gray-800">{{ $column }} @if ($this->sortColumn === $column) {{ $this->sortDirection === 'asc' ? '↑' : '↓' }} @endif</button>
                            </th>
                        @endforeach
                        <th class="px-3 py-2 text-right font-medium text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($records as $record)
                        <tr>
                            <td class="px-3 py-2"><input type="checkbox" wire:model.live="selected" value="{{ $record->id }}" class="rounded" /></td>
                            <td class="px-3 py-2 text-gray-500">{{ $record->row_number }}</td>
                            @foreach ($this->columns as $column)
                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap max-w-64 truncate" title="{{ $record->row_data[$column] ?? '' }}">{{ $record->row_data[$column] ?? '' }}</td>
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
</div>
