<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\SimkahEndpoint;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BimwinController extends Controller
{
    private const ENDPOINT_KEYS = ['cek', 'dialog', 'simpan', 'selesai'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $endpoints = $this->endpointsFor($user);

        $config = [
            'appUrl' => rtrim(url('/'), '/'),
            'endpoints' => $endpoints,
            'provinsi' => $user->provinsi ?: '',
            'kabkota' => $user->kota_kabupaten ?: '',
            'kecamatan' => $user->kecamatan ?: '',
            'fasilitatorSuami' => '',
            'fasilitatorIstri' => '',
            'maxCoba' => 10,
        ];

        return view('bimwin.index', [
            'user' => $user,
            'endpoints' => $endpoints,
            'endpointsLengkap' => $this->endpointsComplete($endpoints),
            'configJson' => json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function updateEndpoints(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'endpoints' => ['nullable', 'array'],
            'endpoints.cek' => ['nullable', 'string', 'max:2048', new SimkahEndpoint],
            'endpoints.dialog' => ['nullable', 'string', 'max:2048', new SimkahEndpoint],
            'endpoints.simpan' => ['nullable', 'string', 'max:2048', new SimkahEndpoint],
            'endpoints.selesai' => ['nullable', 'string', 'max:2048', new SimkahEndpoint],
        ]);

        $endpoints = [];
        foreach (self::ENDPOINT_KEYS as $key) {
            $endpoints[$key] = trim((string) ($validated['endpoints'][$key] ?? ''));
        }

        $filled = array_filter($endpoints, fn (string $value) => $value !== '');

        if ($filled !== [] && count($filled) !== count(self::ENDPOINT_KEYS)) {
            throw ValidationException::withMessages([
                'endpoints' => 'Lengkapi keempat endpoint atau kosongkan semuanya.',
            ]);
        }

        $request->user()->fill([
            'bimwin_endpoints' => $filled === [] ? null : $endpoints,
        ])->save();

        return redirect()
            ->route('bimwin.index')
            ->with('status', 'Endpoint BIMWIN berhasil disimpan.');
    }

    /**
     * @return array{cek: string, dialog: string, simpan: string, selesai: string}
     */
    private function endpointsFor(User $user): array
    {
        $stored = is_array($user->bimwin_endpoints) ? $user->bimwin_endpoints : [];

        return [
            'cek' => (string) ($stored['cek'] ?? ''),
            'dialog' => (string) ($stored['dialog'] ?? ''),
            'simpan' => (string) ($stored['simpan'] ?? ''),
            'selesai' => (string) ($stored['selesai'] ?? ''),
        ];
    }

    /**
     * @param  array{cek: string, dialog: string, simpan: string, selesai: string}  $endpoints
     */
    private function endpointsComplete(array $endpoints): bool
    {
        foreach (self::ENDPOINT_KEYS as $key) {
            if (($endpoints[$key] ?? '') === '') {
                return false;
            }
        }

        return true;
    }
}
