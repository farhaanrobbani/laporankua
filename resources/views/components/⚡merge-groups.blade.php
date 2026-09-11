<?php

use App\Models\Import;
use App\Models\MergeGroup;
use App\Services\MergeService;
use Livewire\Component;

new class extends Component
{
    /** @var \Illuminate\Database\Eloquent\Collection<int, MergeGroup>|null */
    public $groups;

    public bool $showCreateForm = false;

    public string $newGroupName = '';

    /** @var int[] */
    public array $newGroupImportIds = [];

    public string $newGroupJoinColumn = '';

    /** @var string[] */
    public array $newGroupSharedColumns = [];

    /** @var array<int, array{id: int, file_name: string}> */
    public array $availableImports = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->groups = app(MergeService::class)->getUserGroups(auth()->id());

        $this->availableImports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->latest()
            ->get(['id', 'file_name'])
            ->map(fn (Import $import) => ['id' => $import->id, 'file_name' => $import->file_name])
            ->all();
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;

        if (! $this->showCreateForm) {
            $this->resetCreateForm();
        }
    }

    public function updatedNewGroupImportIds(): void
    {
        $this->newGroupSharedColumns = app(MergeService::class)->getSharedColumns($this->newGroupImportIds);
        $this->newGroupJoinColumn = '';
    }

    public function createGroup(): void
    {
        $this->validate([
            'newGroupName' => 'required|string|max:255',
            'newGroupImportIds' => 'required|array|min:2',
            'newGroupJoinColumn' => 'required|string',
        ]);

        $service = app(MergeService::class);
        $group = $service->createGroup(
            auth()->id(),
            $this->newGroupName,
            $this->newGroupJoinColumn,
            $this->newGroupImportIds,
        );

        session()->flash('status', 'Grup "'.$group->name.'" berhasil dibuat.');

        $this->resetCreateForm();
        $this->showCreateForm = false;
        $this->loadData();
    }

    public function deleteGroup(MergeGroup $group): void
    {
        $group->delete();
        session()->flash('status', 'Grup "'.$group->name.'" berhasil dihapus.');
        $this->loadData();
    }

    private function resetCreateForm(): void
    {
        $this->newGroupName = '';
        $this->newGroupImportIds = [];
        $this->newGroupJoinColumn = '';
        $this->newGroupSharedColumns = [];
        $this->resetValidation();
    }
};
?>

<div>
    @if (session('status'))
        <div class="mb-4 bg-green-50 border border-green-200 rounded-md px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Grup Data Gabungan</h3>
        <button type="button" wire:click="toggleCreateForm"
            class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
            {{ $showCreateForm ? 'Batal' : '+ Buat Grup Baru' }}
        </button>
    </div>

    {{-- Create Form --}}
    @if ($showCreateForm)
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-5">
            <h4 class="font-medium text-blue-900 mb-3">Buat Grup Gabungan Baru</h4>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Grup</label>
                    <input type="text" wire:model="newGroupName" placeholder="Contoh: Data Gabungan 1"
                        class="w-full border-gray-300 rounded-md text-sm" />
                    @error('newGroupName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih File Import (minimal 2)</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($availableImports as $import)
                            <label class="flex items-center gap-2 text-sm text-gray-700 border border-gray-200 rounded-md px-3 py-2 cursor-pointer hover:border-blue-400">
                                <input type="checkbox" wire:model="newGroupImportIds" value="{{ $import['id'] }}"
                                    class="rounded text-blue-600" />
                                {{ $import['file_name'] }}
                            </label>
                        @endforeach
                    </div>
                    @error('newGroupImportIds') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if (count($newGroupImportIds) >= 2)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kolom Penggabung (JOIN key)</label>
                        <select wire:model="newGroupJoinColumn" class="border-gray-300 rounded-md text-sm w-full sm:w-auto">
                            <option value="">-- Pilih kolom --</option>
                            @foreach ($newGroupSharedColumns as $col)
                                <option value="{{ $col }}">{{ $col }}</option>
                            @endforeach
                        </select>
                        @error('newGroupJoinColumn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @if (empty($newGroupSharedColumns))
                            <p class="text-xs text-amber-600 mt-1">Tidak ada kolom yang sama antara file yang dipilih.</p>
                        @endif
                    </div>
                @endif

                <button type="button" wire:click="createGroup"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 disabled:opacity-50"
                    @disabled(count($newGroupImportIds) < 2 || empty($newGroupJoinColumn))>
                    Simpan Grup
                </button>
            </div>
        </div>
    @endif

    {{-- Groups List --}}
    @if ($groups && $groups->isEmpty())
        <div class="text-center py-8 bg-gray-50 rounded-lg">
            <p class="text-gray-500 font-medium">Belum ada grup data gabungan</p>
            <p class="text-gray-400 text-sm mt-1">Klik "Buat Grup Baru" untuk mulai menggabungkan data dari beberapa file import.</p>
        </div>
    @elseif ($groups)
        <div class="space-y-3">
            @foreach ($groups as $group)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-medium text-gray-800">{{ $group->name }}</h4>
                            <p class="text-sm text-gray-500 mt-1">
                                JOIN kolom: <span class="font-mono text-blue-600">{{ $group->join_column }}</span>
                                · {{ $group->imports_count }} file import
                            </p>
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach ($group->imports as $import)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $import->file_name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-4">
                            <a href="{{ route('data.index', ['mode' => 'merge', 'merge_group_id' => $group->id]) }}"
                                class="px-3 py-1 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                Lihat Data
                            </a>
                            <button type="button" wire:click="deleteGroup({{ $group->id }})"
                                wire:confirm="Hapus grup '{{ $group->name }}'?"
                                class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-md hover:bg-red-200">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
