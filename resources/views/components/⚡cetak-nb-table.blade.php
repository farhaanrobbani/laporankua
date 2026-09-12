<?php

use App\Models\Import;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $importId = null;

    /** @var string[] */
    public array $columns = [];

    public string $search = '';

    public string $filterColumn = '';

    public string $filterValue = '';

    public string $sortColumn = 'row_number';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

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

    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = \App\Models\ImportData::where('import_id', $this->importId);

        if ($this->search !== '') {
            $query->search($this->search);
        }

        if ($this->filterColumn !== '' && $this->filterValue !== '') {
            $query->filterColumn($this->filterColumn, $this->filterValue);
        }

        $query->sortBy($this->sortColumn, $this->sortDirection);

        return $query;
    }
};
?>

<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari..." class="border-gray-300 rounded-md text-sm" />
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
    </div>

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
                        <th class="px-3 py-2 text-right font-medium text-gray-500">Aksi</th>
                        @foreach ($this->columns as $column)
                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">
                                <button type="button" wire:click="sortBy('{{ $column }}')" class="hover:text-gray-800">
                                    {{ $column }}
                                    @if ($this->sortColumn === $column) {{ $this->sortDirection === 'asc' ? '↑' : '↓' }} @endif
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($records as $record)
                        <tr>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('cetak-nb.print', ['import_id' => $this->importId, 'record' => $record->id]) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Cetak NB</a>
                            </td>
                            @foreach ($this->columns as $column)
                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap max-w-64 truncate" title="{{ $record->row_data[$column] ?? '' }}">
                                    {{ $record->row_data[$column] ?? '' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    @endif

</div>
