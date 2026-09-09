<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;

class MemberRegistrationTest extends ApiTestCase
{
    #[Test]
    public function pengurus_can_register_member_in_one_call(): void
    {
        [$pengurus] = $this->makeUserWithMember('pengurus');

        $response = $this->actingAs($pengurus)->postJson('/api/v1/members/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'member_type' => 'pasar',
            'phone' => '081234567890',
            'address' => 'Jl. Pasar No. 1',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'anggota')
            ->assertJsonPath('data.member.name', 'Budi Santoso');

        $memberCode = $response->json('data.member.member_code');
        $this->assertMatchesRegularExpression('/^MBR-\d{6}-\d{4}$/', $memberCode);
    }

    #[Test]
    public function duplicate_registration_returns_422(): void
    {
        [$pengurus] = $this->makeUserWithMember('pengurus');
        $payload = [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'member_type' => 'rumah',
            'phone' => '081234567890',
            'address' => 'Jl. Pasar No. 1',
        ];

        $this->actingAs($pengurus)->postJson('/api/v1/members/register', $payload)->assertStatus(201);
        // Hit kedua — email sama wajib ditolak
        $this->actingAs($pengurus)->postJson('/api/v1/members/register', $payload)->assertStatus(422);
    }

    #[Test]
    public function anggota_cannot_register_member(): void
    {
        [$anggota] = $this->makeUserWithMember('anggota');

        $this->actingAs($anggota)->postJson('/api/v1/members/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'member_type' => 'rumah',
            'phone' => '081234567890',
            'address' => 'Jl. Pasar No. 1',
        ])->assertStatus(403);
    }
}
