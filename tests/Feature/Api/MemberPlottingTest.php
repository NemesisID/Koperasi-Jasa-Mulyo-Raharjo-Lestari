<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class MemberPlottingTest extends ApiTestCase
{
    private function makePetugas(string $username, ?string $createdAt = null): User
    {
        $user = User::create([
            'name' => 'Petugas '.$username,
            'username' => $username,
            'email' => "{$username}@example.com",
            'password' => bcrypt('password123'),
            'role' => 'petugas',
        ]);

        if ($createdAt !== null) {
            DB::table('users')->where('id', $user->id)->update(['created_at' => $createdAt]);
        }

        return $user;
    }

    private function makeBareUser(string $username): User
    {
        return User::create([
            'name' => 'Calon '.$username,
            'username' => $username,
            'email' => "{$username}@example.com",
            'password' => bcrypt('password123'),
            'role' => 'anggota',
        ]);
    }

    #[Test]
    public function new_member_is_auto_plotted_to_oldest_petugas(): void
    {
        $this->seedCore();
        $category = MemberCategory::create(['name' => 'rumah']);
        [, $pengurus] = $this->makeUserWithMember('pengurus');

        // petugas_lama dibuat belakangan (id lebih besar) tapi created_at lebih awal —
        // yang menentukan harus created_at, bukan id.
        $this->makePetugas('petugas_baru');
        $petugasLama = $this->makePetugas('petugas_lama', now()->subDay()->toDateTimeString());

        $calon = $this->makeBareUser('calon_satu');

        $this->actingAs($pengurus)
            ->postJson('/api/v1/members', [
                'user_id' => $calon->id,
                'member_category_id' => $category->id,
                'name' => 'Calon Satu',
                'address' => 'Jl. Melati No. 1',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.officer.id', $petugasLama->id);

        $this->assertDatabaseHas('members', [
            'name' => 'Calon Satu',
            'officer_id' => $petugasLama->id,
        ]);
    }

    #[Test]
    public function member_created_through_user_management_is_also_plotted(): void
    {
        $this->seedCore();
        MemberCategory::create(['name' => 'rumah']);
        [, $pengurus] = $this->makeUserWithMember('pengurus');
        $petugas = $this->makePetugas('petugas_satu');

        $this->actingAs($pengurus)
            ->postJson('/api/v1/users', [
                'name' => 'Anggota Baru',
                'username' => 'anggota_baru',
                'email' => 'anggota_baru@example.com',
                'password' => 'password123',
                'role' => 'anggota',
                'member_types' => ['rumah'],
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('members', [
            'name' => 'Anggota Baru',
            'officer_id' => $petugas->id,
        ]);
    }

    #[Test]
    public function member_stays_unplotted_when_no_petugas_exists(): void
    {
        $this->seedCore();
        $category = MemberCategory::create(['name' => 'rumah']);
        [, $pengurus] = $this->makeUserWithMember('pengurus');
        $calon = $this->makeBareUser('calon_tanpa_petugas');

        $this->actingAs($pengurus)
            ->postJson('/api/v1/members', [
                'user_id' => $calon->id,
                'member_category_id' => $category->id,
                'name' => 'Calon Tanpa Petugas',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.officer', null);

        $this->assertDatabaseHas('members', [
            'name' => 'Calon Tanpa Petugas',
            'officer_id' => null,
        ]);
    }

    #[Test]
    public function plotting_list_shows_one_row_per_member_address(): void
    {
        $this->seedCore();
        $rumah = MemberCategory::create(['name' => 'rumah']);
        $pasar = MemberCategory::create(['name' => 'pasar']);
        [, $pengurus] = $this->makeUserWithMember('pengurus');

        $petugasA = $this->makePetugas('petugas_a', now()->subDays(2)->toDateTimeString());
        $petugasB = $this->makePetugas('petugas_b', now()->subDay()->toDateTimeString());

        $user = $this->makeBareUser('punya_dua_tempat');
        Member::create([
            'user_id' => $user->id, 'member_category_id' => $rumah->id,
            'member_code' => 'MBR-TEST-0001', 'name' => 'Dua Tempat',
            'address' => 'Jl. Rumah', 'status' => 'aktif', 'join_date' => now()->toDateString(),
            'officer_id' => $petugasA->id,
        ]);
        Member::create([
            'user_id' => $user->id, 'member_category_id' => $pasar->id,
            'member_code' => 'MBR-TEST-0002', 'name' => 'Dua Tempat',
            'address' => 'Pasar Blok C', 'status' => 'aktif', 'join_date' => now()->toDateString(),
            'officer_id' => $petugasB->id,
        ]);

        $response = $this->actingAs($pengurus)
            ->getJson('/api/v1/members?search=Dua Tempat')
            ->assertStatus(200);

        // 1 user, 2 alamat → 2 baris ploting, bukan 1.
        $this->assertCount(2, $response->json('data'));
        $this->assertEqualsCanonicalizing(
            [$petugasA->id, $petugasB->id],
            array_map(fn ($row) => $row['officer']['id'], $response->json('data')),
        );
    }

    #[Test]
    public function plotting_list_can_be_filtered_by_officer(): void
    {
        $this->seedCore();
        $category = MemberCategory::create(['name' => 'rumah']);
        [, $pengurus] = $this->makeUserWithMember('pengurus');

        $petugasA = $this->makePetugas('petugas_a', now()->subDays(2)->toDateTimeString());
        $petugasB = $this->makePetugas('petugas_b', now()->subDay()->toDateTimeString());

        foreach ([['Plot A', $petugasA->id], ['Plot B', $petugasB->id]] as $i => [$nama, $officerId]) {
            $user = $this->makeBareUser('warga_'.$i);
            Member::create([
                'user_id' => $user->id, 'member_category_id' => $category->id,
                'member_code' => 'MBR-FLT-000'.$i, 'name' => $nama,
                'status' => 'aktif', 'join_date' => now()->toDateString(),
                'officer_id' => $officerId,
            ]);
        }

        $response = $this->actingAs($pengurus)
            ->getJson("/api/v1/members?officer_id={$petugasA->id}")
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Plot A', $response->json('data.0.name'));
    }
}
