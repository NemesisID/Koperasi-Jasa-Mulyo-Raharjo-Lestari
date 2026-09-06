<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
class GlobalExceptionHandlerTest extends ApiTestCase
{
    #[Test]
    public function unauthenticated_returns_401_json(): void
    {
        $this->getJson('/api/v1/users')
            ->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function unknown_route_returns_404_json(): void
    {
        $this->getJson('/api/v1/unknown-endpoint')
            ->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function validation_error_returns_422_json(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    #[Test]
    public function forbidden_role_returns_403_json(): void
    {
        [$petugas] = $this->makeUserWithMember('petugas');

        // Petugas tidak boleh create user
        $this->actingAs($petugas)
            ->postJson('/api/v1/users', [
                'name' => 'X', 'username' => 'x', 'email' => 'x@example.com',
                'password' => 'password123', 'role' => 'petugas',
            ])
            ->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function method_not_allowed_returns_405_json(): void
    {
        $this->deleteJson('/api/v1/auth/login')
            ->assertStatus(405)
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function business_logic_exception_returns_400_json(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');

        // Membuat tiket untuk member tak ada â†’ 422 (exists); timbang pickup tak ada â†’ 404;
        // timbang pickup milik member suspend â†’ 400 BusinessLogicException
        [, $member] = $this->makeUserWithMember('anggota');
        $member->update(['status' => 'suspend']);

        $this->actingAs($petugas)
            ->postJson('/api/v1/pickups', [
                'member_id' => $member->id,
                'location_type' => 'gudang',
            ])
            ->assertStatus(400)
            ->assertJsonStructure(['message']);
    }
}
