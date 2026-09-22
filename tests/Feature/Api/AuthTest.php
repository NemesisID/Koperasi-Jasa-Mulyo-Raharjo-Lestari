<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Hash;
use App\Models\MemberCategory;

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

    #[Test]
    public function public_self_registration_is_disabled(): void
    {
        $this->seedCore();

        $this->postJson('/api/v1/auth/register-member', [
            'name' => 'Penyusup',
            'username' => 'penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password123',
            'member_types' => ['rumah'],
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 1',
        ])->assertStatus(404);

        $this->assertDatabaseMissing('users', ['username' => 'penyusup']);
    }

    #[Test]
    public function pengurus_can_still_create_member_account(): void
    {
        $this->seedCore();
        MemberCategory::create(['name' => 'rumah']);
        [$pengurus] = $this->makeUserWithMember('pengurus');

        $this->actingAs($pengurus)
            ->postJson('/api/v1/users', [
                'name' => 'Anggota Baru',
                'username' => 'anggota_baru',
                'email' => 'anggota_baru@example.com',
                'password' => 'password123',
                'role' => 'anggota',
                'member_types' => ['rumah'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['username' => 'anggota_baru', 'role' => 'anggota']);
        $this->assertDatabaseHas('members', ['name' => 'Anggota Baru', 'status' => 'aktif']);
    }

    #[Test]
    public function non_pengurus_cannot_create_accounts(): void
    {
        $this->seedCore();
        MemberCategory::create(['name' => 'rumah']);
        [$petugas] = $this->makeUserWithMember('petugas');

        $this->actingAs($petugas)
            ->postJson('/api/v1/users', [
                'name' => 'Anggota Gelap',
                'username' => 'anggota_gelap',
                'email' => 'anggota_gelap@example.com',
                'password' => 'password123',
                'role' => 'anggota',
                'member_types' => ['rumah'],
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('users', ['username' => 'anggota_gelap']);
    }
}
