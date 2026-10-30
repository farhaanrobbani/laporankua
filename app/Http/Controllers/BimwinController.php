<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BimwinController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $config = [
            'provinsi' => $user->provinsi ?: '',
            'kabkota' => $user->kota_kabupaten ?: '',
            'kecamatan' => $user->kecamatan ?: '',
            'fasilitatorSuami' => '',
            'fasilitatorIstri' => '',
            'maxCoba' => 10,
        ];

        return view('bimwin.index', [
            'user' => $user,
            'configJson' => json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
