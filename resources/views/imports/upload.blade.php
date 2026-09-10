<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload Excel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <livewire:import-uploader />
            <p class="mt-4 text-xs text-gray-500">Setelah konfirmasi, file diproses di background. Anda akan diarahkan ke halaman detail untuk memantau status import.</p>
        </div>
    </div>
</x-app-layout>
