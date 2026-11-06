<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class BimwinLicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_premium_token_is_allowed(): void
    {
        $user = User::factory()->create(['plan' => 'premium']);

        $this->postJson(route('api.bimwin.license'), [
            'token' => $this->tokenFor($user),
        ])
            ->assertOk()
            ->assertJson([
                'allowed' => true,
                'plan' => 'premium',
            ]);
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create(['plan' => 'premium']);

        $this->postJson(route('api.bimwin.license'), [
            'token' => $this->tokenFor($user, now()->subMinute()->timestamp),
        ])
            ->assertUnauthorized()
            ->assertJson([
                'allowed' => false,
                'reason' => 'token_expired',
            ]);
    }

    public function test_free_user_token_is_rejected(): void
    {
        $user = User::factory()->create(['plan' => 'free']);

        $this->postJson(route('api.bimwin.license'), [
            'token' => $this->tokenFor($user),
        ])
            ->assertForbidden()
            ->assertJson([
                'allowed' => false,
                'reason' => 'not_premium',
            ]);
    }

    public function test_inactive_user_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'plan' => 'premium',
            'status' => 'inactive',
        ]);

        $this->postJson(route('api.bimwin.license'), [
            'token' => $this->tokenFor($user),
        ])
            ->assertForbidden()
            ->assertJson([
                'allowed' => false,
                'reason' => 'user_inactive',
            ]);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->postJson(route('api.bimwin.license'), [
            'token' => 'bukan-token-lisensi',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'allowed' => false,
                'reason' => 'invalid_token',
            ]);
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->postJson(route('api.bimwin.license'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    private function tokenFor(User $user, ?int $expiresAt = null): string
    {
        return Crypt::encryptString(json_encode([
            'purpose' => 'bimwin',
            'user_id' => $user->id,
            'exp' => $expiresAt ?? now()->addDays(30)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }
}
