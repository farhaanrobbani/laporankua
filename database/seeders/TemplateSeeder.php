<?php

namespace Database\Seeders;

use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $systemUser = User::firstOrCreate(
            ['email' => 'system@laporanku.id'],
            [
                'name' => 'System',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // REKAP NR1
        ReportTemplate::firstOrCreate(
            ['name' => 'REKAP NR1'],
            [
                'user_id' => $systemUser->id,
                'is_global' => true,
                'is_active' => true,
                'output_format' => 'print',
                'description' => 'Rekapitulasi nikah/rujuk',
                'fields_json' => ['Nama Suami', 'Nama Istri', 'Tanggal Nikah', 'Kelurahan', 'Tanggal Daftar', 'Nikah Di'],
                'filters_json' => ['search' => null, 'filter_column' => null, 'filter_value' => null],
                'sorting_json' => ['column' => 'Tanggal Nikah', 'direction' => 'asc'],
                'layout_json' => [
                    'orientation' => 'portrait',
                    'table_layout' => [
                        'columns' => [
                            ['type' => 'row_number', 'label' => 'No', 'rowspan' => 2],
                            ['type' => 'group', 'label' => 'Nama', 'colspan' => 2, 'children' => [
                                ['type' => 'field', 'field' => 'Nama Suami', 'label' => 'Suami'],
                                ['type' => 'field', 'field' => 'Nama Istri', 'label' => 'Istri'],
                            ]],
                            ['type' => 'field', 'field' => 'Kelurahan', 'label' => 'Alamat', 'rowspan' => 2],
                            ['type' => 'group', 'label' => 'Tanggal', 'colspan' => 2, 'children' => [
                                ['type' => 'field', 'field' => 'Tanggal Daftar', 'label' => 'Daftar', 'width' => '100px', 'align' => 'center'],
                                ['type' => 'field', 'field' => 'Tanggal Nikah', 'label' => 'Nikah', 'width' => '100px', 'align' => 'center'],
                            ]],
                            ['type' => 'field', 'field' => 'Nikah Di', 'label' => 'K/LK', 'rowspan' => 2, 'transform' => 'klinik_balai_nikah'],
                        ],
                    ],
                ],
            ]
        );

        // Laporan L2 Pendidikan
        ReportTemplate::firstOrCreate(
            ['name' => 'Laporan L2 Pendidikan'],
            [
                'user_id' => $systemUser->id,
                'is_global' => true,
                'is_active' => true,
                'output_format' => 'print',
                'description' => 'Laporan rekapitulasi pendidikan pengantin berdasarkan desa',
                'fields_json' => ['Kelurahan', 'Pendidikan Suami', 'Pendidikan Istri'],
                'filters_json' => ['search' => null, 'filter_column' => null, 'filter_value' => null],
                'sorting_json' => ['column' => 'Kelurahan', 'direction' => 'asc'],
                'layout_json' => [
                    'orientation' => 'landscape',
                    'table_layout' => [
                        'aggregation' => [
                            'group_by' => 'Kelurahan',
                            'static_values' => [
                                'ARGOYUWONO', 'LEBAKHARJO', 'MULYOASRI', 'PURWOHARJO',
                                'SIDORENGGO', 'SIMOJAYAN', 'SONOWANGI', 'TAMANASRI',
                                'TAMANSARI', 'TAWANGAGUNG', 'TIRTOMARTO', 'TIRTOMOYO', 'WIROTAMAN',
                            ],
                        ],
                        'columns' => [
                            ['type' => 'row_number', 'label' => 'No', 'rowspan' => 3],
                            ['type' => 'field', 'field' => 'Kelurahan', 'label' => 'Desa', 'rowspan' => 3],
                            ['type' => 'aggregate_total', 'label' => 'Jumlah Perkawinan', 'rowspan' => 3],
                            ['type' => 'group', 'label' => 'Pendidikan Pengantin', 'colspan' => 12, 'children' => [
                                ['type' => 'group', 'label' => 'Laki-laki', 'colspan' => 6, 'children' => [
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Suami', 'match' => ['SD'], 'label' => 'SD'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Suami', 'match' => ['SLTP'], 'label' => 'SMP'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Suami', 'match' => ['SLTA'], 'label' => 'SMA'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Suami', 'match' => ['SARJANA MUDA', 'STRATA I', 'DIPLOMA III', 'DIPLOMA IV'], 'label' => 'S1'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Suami', 'match' => ['STRATA II'], 'label' => 'S2'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Suami', 'match' => ['STRATA III'], 'label' => 'S3'],
                                ]],
                                ['type' => 'group', 'label' => 'Perempuan', 'colspan' => 6, 'children' => [
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Istri', 'match' => ['SD'], 'label' => 'SD'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Istri', 'match' => ['SLTP'], 'label' => 'SMP'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Istri', 'match' => ['SLTA'], 'label' => 'SMA'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Istri', 'match' => ['SARJANA MUDA', 'STRATA I', 'DIPLOMA III', 'DIPLOMA IV'], 'label' => 'S1'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Istri', 'match' => ['STRATA II'], 'label' => 'S2'],
                                    ['type' => 'aggregate_count', 'field' => 'Pendidikan Istri', 'match' => ['STRATA III'], 'label' => 'S3'],
                                ]],
                            ]],
                        ],
                    ],
                ],
            ]
        );

        // Laporan L2 Usia
        ReportTemplate::firstOrCreate(
            ['name' => 'Laporan L2 Usia'],
            [
                'user_id' => $systemUser->id,
                'is_global' => true,
                'is_active' => true,
                'output_format' => 'print',
                'description' => 'Laporan rekapitulasi usia pengantin berdasarkan desa',
                'fields_json' => ['Kelurahan', 'Usia Suami', 'Usia Istri'],
                'filters_json' => ['Tanggal Nikah'],
                'sorting_json' => ['Kelurahan' => 'asc'],
                'layout_json' => [
                    'orientation' => 'landscape',
                    'table_layout' => [
                        'aggregation' => [
                            'group_by' => 'Kelurahan',
                            'static_values' => [
                                'ARGOYUWONO', 'LEBAKHARJO', 'MULYOASRI', 'PURWOHARJO',
                                'SIDORENGGO', 'SIMOJAYAN', 'SONOWANGI', 'TAMANASRI',
                                'TAMANSARI', 'TAWANGAGUNG', 'TIRTOMARTO', 'TIRTOMOYO', 'WIROTAMAN',
                            ],
                        ],
                        'columns' => [
                            ['type' => 'row_number', 'label' => 'No', 'rowspan' => 3],
                            ['type' => 'field', 'field' => 'Kelurahan', 'label' => 'Desa', 'rowspan' => 3],
                            ['type' => 'aggregate_total', 'label' => 'Jumlah Perkawinan', 'rowspan' => 3],
                            ['type' => 'group', 'label' => 'Usia Pengantin', 'colspan' => 8, 'children' => [
                                ['type' => 'group', 'label' => 'Laki-laki', 'colspan' => 4, 'children' => [
                                    ['type' => 'aggregate_range', 'field' => 'Usia Suami', 'min' => null, 'max' => 19, 'label' => '<19'],
                                    ['type' => 'aggregate_range', 'field' => 'Usia Suami', 'min' => 19, 'max' => 21, 'label' => '19-20'],
                                    ['type' => 'aggregate_range', 'field' => 'Usia Suami', 'min' => 21, 'max' => 31, 'label' => '21-30'],
                                    ['type' => 'aggregate_range', 'field' => 'Usia Suami', 'min' => 31, 'max' => null, 'label' => '>30'],
                                ]],
                                ['type' => 'group', 'label' => 'Perempuan', 'colspan' => 4, 'children' => [
                                    ['type' => 'aggregate_range', 'field' => 'Usia Istri', 'min' => null, 'max' => 19, 'label' => '<19'],
                                    ['type' => 'aggregate_range', 'field' => 'Usia Istri', 'min' => 19, 'max' => 21, 'label' => '19-20'],
                                    ['type' => 'aggregate_range', 'field' => 'Usia Istri', 'min' => 21, 'max' => 31, 'label' => '21-30'],
                                    ['type' => 'aggregate_range', 'field' => 'Usia Istri', 'min' => 31, 'max' => null, 'label' => '>30'],
                                ]],
                            ]],
                        ],
                    ],
                ],
            ]
        );

        // Laporan L4 Simponi
        ReportTemplate::firstOrCreate(
            ['name' => 'Laporan L4 Simponi'],
            [
                'user_id' => $systemUser->id,
                'is_global' => true,
                'is_active' => true,
                'output_format' => 'print',
                'description' => 'Laporan rekapitulasi setoran simponi berdasarkan desa',
                'fields_json' => ['Nama Kelurahan', 'Tanggal dan Jam Setor', 'Nominal Setor', 'Tanggal Akad', 'Nama Suami'],
                'filters_json' => ['search' => null, 'filter_column' => null, 'filter_value' => null],
                'sorting_json' => ['column' => 'Nama Kelurahan', 'direction' => 'asc'],
                'layout_json' => [
                    'orientation' => 'portrait',
                    'table_layout' => [
                        'type' => 'grouped_detail',
                        'aggregation' => [
                            'group_by' => 'Nama Kelurahan',
                            'date_filter_field' => 'Tanggal Akad',
                        ],
                        'columns' => [
                            ['type' => 'row_number', 'label' => 'No'],
                            ['type' => 'field', 'field' => 'Nama Kelurahan', 'label' => 'Desa'],
                            ['type' => 'aggregate_total', 'label' => 'Jumlah Perkawinan'],
                            ['type' => 'field', 'field' => 'Tanggal dan Jam Setor', 'label' => 'Tanggal Setor', 'format' => 'date_id'],
                            ['type' => 'static', 'value' => '600000', 'label' => 'Jumlah Setor'],
                            ['type' => 'field', 'field' => 'Tanggal Akad', 'label' => 'Tanggal Perkawinan', 'format' => 'date_id'],
                            ['type' => 'field', 'field' => 'Nama Suami', 'label' => 'Penyetor'],
                        ],
                    ],
                ],
            ]
        );
    }
}
