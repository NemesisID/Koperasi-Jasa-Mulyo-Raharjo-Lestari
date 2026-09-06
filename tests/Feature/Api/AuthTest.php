<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Hash;

class AuthTest extends ApiTestCase
{
    #[Test]
    public function login_returns_token_and_user(): void
    {
        [$user] = $this->makeUserWithMember('petugas');

        $response = $this->postJson('/api/v1/auth/login', [
            'identity' => 'test_petugas',
            'password' => 'password123',
            'device_name' => 'testing',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token', 'user' => ['id', 'name', 'role']],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    #[Test]
    public function invalid_credentials_return_422(): void
    {
        $this->makeUserWithMember('petugas');

        $response = $this->postJson('/api/v1/auth/login', [
            'identity' => 'test_petugas',
            'password' => 'wrong-password',
            'device_name' => 'testing',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    #[Test]
    public function me_returns_authenticated_user(): void
    {
        [$user] = $this->makeUserWithMember('petugas');

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'petugas');
    }

    #[Test]
    public function logout_revokes_token(): void
    {
        [$user] = $this->makeUserWithMember('petugas');
        $token = $user->createToken('testing')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Token sudah dicabut — request berikutnya harus 401.
        // Guard user di-cache per app instance dalam test, jadi reset guard dulu.
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(401);
    }
}
