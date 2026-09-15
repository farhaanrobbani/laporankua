<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Data Pelaksanaan Kantor') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (empty($importIds))
                        <x-empty-state title="Belum ada data" message="Upload laporan peristiwa nikah dan pendaftaran nikah terlebih dahulu." :action-url="route('imports.create')" action-label="Upload Excel" />
                    @else
                        <livewire:data-table
                            :import-ids="$importIds"
                            :join-column="'Nomor Daftar'"
                            :filter-column="'Tempat Nikah'"
                            :filter-value="'balai nikah'"
                            :key="'pelaksanaan-kantor'"
                        />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
