@props(['status' => 'default'])

@php
    $map = [
        'success' => ['Berhasil', 'bg-green-100 text-green-800'],
        'generated' => ['Selesai', 'bg-green-100 text-green-800'],
        'downloaded' => ['Terunduh', 'bg-green-100 text-green-800'],
        'pending' => ['Menunggu', 'bg-yellow-100 text-yellow-800'],
        'processing' => ['Diproses', 'bg-yellow-100 text-yellow-800'],
        'failed' => ['Gagal', 'bg-red-100 text-red-800'],
        'pdf' => ['PDF', 'bg-red-100 text-red-700'],
        'word' => ['Word', 'bg-blue-100 text-blue-700'],
        'excel' => ['Excel', 'bg-green-100 text-green-700'],
        'print' => ['Print', 'bg-gray-200 text-gray-700'],
        'default' => ['Default', 'bg-purple-100 text-purple-800'],
    ];

    [$label, $classes] = $map[strtolower((string) $status)] ?? [ucfirst((string) $status), 'bg-gray-100 text-gray-800'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex px-2 py-0.5 text-xs font-semibold rounded-full '.$classes]) }}>{{ $label }}</span>
