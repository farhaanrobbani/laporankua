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

    public bool $showCetakModal = false;

    public string $cetakSearch = '';

    /** @var array<int, array{id: int, row_number: int, data: array}> */
    public array $cetakRecords = [];

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

    public function openCetakModal(): void
    {
        $this->showCetakModal = true;
        $this->cetakSearch = '';
        $this->loadCetakRecords();
    }

    public function updatedCetakSearch(): void
    {
        $this->loadCetakRecords();
    }

    private function loadCetakRecords(): void
    {
        $query = \App\Models\ImportData::where('import_id', $this->importId)
            ->orderBy('row_number');

        if ($this->cetakSearch !== '') {
            $query->search($this->cetakSearch);
        }

        $this->cetakRecords = $query->limit(20)->get()
            ->map(fn ($r) => ['id' => $r->id, 'row_number' => $r->row_number, 'data' => $r->row_data])
            ->toArray();
    }

    public function selectCetakRecord(int $recordId): void
    {
        $this->showCetakModal = false;
        $url = route('cetak-nb.print', ['import_id' => $this->importId, 'record' => $recordId]);
        $this->dispatch('open-cetak-url', url: $url);
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
        <div class="lg:ml-auto">
            <button type="button" wire:click="openCetakModal" style="background-color: #4338ca;" class="inline-flex items-center justify-center px-4 py-2 text-white text-sm font-semibold rounded-md hover:opacity-90">
                Cetak
            </button>
        </div>
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
                        <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('row_number')" class="hover:text-gray-800"># @if ($this->sortColumn === 'row_number') {{ $this->sortDirection === 'asc' ? '↑' : '↓' }} @endif</button>
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
                            <td class="px-3 py-2 text-gray-500">{{ $record->row_number }}</td>
                            @foreach ($this->columns as $column)
                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap max-w-64 truncate" title="{{ $record->row_data[$column] ?? '' }}">
                                    {{ $record->row_data[$column] ?? '' }}
                                </td>
                            @endforeach
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('cetak-nb.print', ['import_id' => $this->importId, 'record' => $record->id]) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Cetak NB</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    @endif

    @if ($showCetakModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-data>
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Pilih Record untuk Dicetak</h3>
                    <button wire:click="$set('showCetakModal', false)" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-4">
                    <input type="text" wire:model.live.debounce.300ms="cetakSearch" placeholder="Cari nomor akta, nama, dll..." class="w-full border-gray-300 rounded-md text-sm mb-3" />
                    <div class="max-h-80 overflow-y-auto border border-gray-200 rounded-md divide-y divide-gray-100">
                        @forelse ($cetakRecords as $cr)
                            <button wire:click="selectCetakRecord({{ $cr['id'] }})" class="w-full text-left px-4 py-3 hover:bg-indigo-50 transition">
                                <span class="text-xs text-gray-500">#{{ $cr['row_number'] }}</span>
                                <p class="text-sm font-medium text-gray-900">{{ $cr['data']['Nomor Akta Nikah'] ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $cr['data']['No Porforasi Suami'] ?? '' }} & {{ $cr['data']['No Porforasi Istri'] ?? '' }}</p>
                            </button>
                        @empty
                            <p class="text-center text-gray-500 py-6">Tidak ada data ditemukan</p>
                        @endforelse
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 flex justify-end">
                    <button wire:click="$set('showCetakModal', false)" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    <script>
        Livewire.on('open-cetak-url', (url) => { window.open(url, '_blank'); });
    </script>
</div>
