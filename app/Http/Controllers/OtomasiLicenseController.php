<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class OtomasiLicenseController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $payload = json_decode(
                Crypt::decryptString($validated['token']),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (Throwable) {
            return $this->deny('invalid_token', 401);
        }

        if (! is_array($payload)
            || ! in_array($payload['purpose'] ?? null, ['otomasi', 'bimwin'], true)
            || ! is_int($payload['user_id'] ?? null)
            || ! is_int($payload['exp'] ?? null)) {
            return $this->deny('invalid_token', 401);
        }

        if ($payload['exp'] < now()->timestamp) {
            return $this->deny('token_expired', 401);
        }

        $user = User::find($payload['user_id']);

        if (! $user || ! $user->isActive()) {
            return $this->deny('user_inactive', 403);
        }

        if (! $user->isPremium()) {
            return $this->deny('not_premium', 403);
        }

        return response()->json([
            'allowed' => true,
            'plan' => $user->plan,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function deny(string $reason, int $status): JsonResponse
    {
        return response()->json([
            'allowed' => false,
            'reason' => $reason,
        ], $status);
    }
}
