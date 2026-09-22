<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\Pickup;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class DailyPickupTest extends ApiTestCase
{
    private function makePetugas(string $username): User
    {
        return User::create([
            'name' => 'Petugas '.$username,
            'username' => $username,
            'password' => bcrypt('password123'),
            'role' => 'petugas',
        ]);
    }

    #[Test]
    public function daily_command_creates_one_ticket_per_active_member_address(): void
    {
        $this->seedCore();
        $petugasRumah = $this->makePetugas('petugas_rumah');
        $petugasPasar = $this->makePetugas('petugas_pasar');
        [, $memberRumah] = $this->makeUserWithMember('anggota');
        $memberRumah->update(['officer_id' => $petugasRumah->id]);

        // Alamat kedua untuk user yang sama → baris member terpisah, petugas terpisah.
        $memberPasar = Member::create([
            'user_id' => $memberRumah->user_id,
            'member_category_id' => MemberCategory::create(['name' => 'pasar'])->id,
            'member_code' => 'MBR-TEST-PSR',
            'name' => 'Punya Dua Tempat',
            'status' => 'aktif',
            'join_date' => now()->toDateString(),
            'officer_id' => $petugasPasar->id,
        ]);

        $this->artisan('pickups:generate-daily')->assertSuccessful();

        // Tiap alamat dapat tiketnya sendiri, lokasi mengikuti kategori alamat,
        // dan petugasnya mengikuti plotting anggota.
        $this->assertDatabaseHas('pickups', [
            'member_id' => $memberRumah->id,
            'officer_id' => $petugasRumah->id,
            'location_type' => 'jemput_rumah',
            'status' => 'menunggu',
        ]);
        $this->assertDatabaseHas('pickups', [
            'member_id' => $memberPasar->id,
            'officer_id' => $petugasPasar->id,
            'location_type' => 'jemput_pasar',
            'status' => 'menunggu',
        ]);
    }

    #[Test]
    public function creating_anggota_account_autogenerates_one_ticket_per_category(): void
    {
        $this->seedCore();
        MemberCategory::create(['name' => 'rumah']);
        MemberCategory::create(['name' => 'pasar']);
        $pengurus = User::create([
            'name' => 'Pengurus Pembuat',
            'username' => 'pengurus_pembuat',
            'password' => bcrypt('password123'),
            'role' => 'pengurus',
        ]);

        $this->actingAs($pengurus)->postJson('/api/v1/users', [
            'name' => 'Anggota Dua Lokasi',
            'username' => 'anggota_dua_lokasi',
            'password' => 'password123',
            'role' => 'anggota',
            'member_types' => ['rumah', 'pasar'],
        ])->assertStatus(201);

        $member = Member::where('name', 'Anggota Dua Lokasi')->firstOrFail();

        // Dua kategori terpilih → dua tiket hari ini (rumah + pasar), langsung masuk antrean.
        $this->assertSame(2, Pickup::where('member_id', $member->id)->count());
        $this->assertDatabaseHas('pickups', [
            'member_id' => $member->id,
            'location_type' => 'jemput_rumah',
            'status' => 'menunggu',
        ]);
        $this->assertDatabaseHas('pickups', [
            'member_id' => $member->id,
            'location_type' => 'jemput_pasar',
            'status' => 'menunggu',
        ]);
    }

    #[Test]
    public function creating_anggota_with_single_category_creates_one_ticket(): void
    {
        $this->seedCore();
        MemberCategory::create(['name' => 'rumah']);
        MemberCategory::create(['name' => 'pasar']);
        $pengurus = User::create([
            'name' => 'Pengurus Pembuat',
            'username' => 'pengurus_pembuat',
            'password' => bcrypt('password123'),
            'role' => 'pengurus',
        ]);

        $this->actingAs($pengurus)->postJson('/api/v1/users', [
            'name' => 'Anggota Pasar Saja',
            'username' => 'anggota_pasar_saja',
            'password' => 'password123',
            'role' => 'anggota',
            'member_types' => ['pasar'],
        ])->assertStatus(201);

        $member = Member::where('name', 'Anggota Pasar Saja')->firstOrFail();

        // Satu kategori → satu tiket, lokasinya mengikuti kategori terpilih.
        $this->assertSame(1, Pickup::where('member_id', $member->id)->count());
        $this->assertDatabaseHas('pickups', [
            'member_id' => $member->id,
            'location_type' => 'jemput_pasar',
            'status' => 'menunggu',
        ]);
        $this->assertDatabaseMissing('pickups', [
            'member_id' => $member->id,
            'location_type' => 'jemput_rumah',
        ]);
    }

    #[Test]
    public function creating_internal_account_creates_no_pickup_tickets(): void
    {
        $this->seedCore();
        MemberCategory::create(['name' => 'rumah']);
        $pengurus = User::create([
            'name' => 'Pengurus Pembuat',
            'username' => 'pengurus_pembuat',
            'password' => bcrypt('password123'),
            'role' => 'pengurus',
        ]);

        $this->actingAs($pengurus)->postJson('/api/v1/users', [
            'name' => 'Petugas Baru',
            'username' => 'petugas_baru',
            'password' => 'password123',
            'role' => 'petugas',
        ])->assertStatus(201);

        // Akun internal (petugas/pengurus) tidak punya kategori → tanpa tiket penjemputan.
        $this->assertSame(0, Pickup::count());
    }

    #[Test]
    public function daily_command_creates_one_ticket_per_category_for_dual_member(): void
    {
        $this->seedCore();
        MemberCategory::create(['name' => 'pasar']);
        [, $member] = $this->makeUserWithMember('anggota');
        $member->update(['categories' => ['rumah', 'pasar']]);

        $this->artisan('pickups:generate-daily')->assertSuccessful();

        // Anggota dual-status dapat dua tiket harian: satu rute rumah, satu rute pasar.
        $this->assertSame(2, Pickup::where('member_id', $member->id)->count());
        $this->assertDatabaseHas('pickups', [
            'member_id' => $member->id,
            'location_type' => 'jemput_rumah',
            'status' => 'menunggu',
        ]);
        $this->assertDatabaseHas('pickups', [
            'member_id' => $member->id,
            'location_type' => 'jemput_pasar',
            'status' => 'menunggu',
        ]);
    }

    #[Test]
    public function running_twice_does_not_duplicate_tickets_for_the_same_day(): void
    {
        $this->seedCore();
        [, $member] = $this->makeUserWithMember('anggota');

        $this->artisan('pickups:generate-daily')->assertSuccessful();
        $this->artisan('pickups:generate-daily')->assertSuccessful();

        $this->assertSame(1, Pickup::where('member_id', $member->id)->count());
    }

    #[Test]
    public function ticket_is_skipped_when_the_member_already_requested_that_day(): void
    {
        $this->seedCore();
        [, $member] = $this->makeUserWithMember('anggota');

        // Permintaan manual anggota hari ini → scheduler tidak boleh menambah tiket kedua.
        Pickup::create([
            'member_id' => $member->id,
            'location_type' => 'gudang',
            'is_sorted' => false,
            'scheduled_at' => now()->setTime(9, 0),
            'status' => 'menunggu',
        ]);

        $this->artisan('pickups:generate-daily')->assertSuccessful();

        $this->assertSame(1, Pickup::where('member_id', $member->id)->count());
    }

    #[Test]
    public function inactive_members_get_no_ticket(): void
    {
        $this->seedCore();
        [, $member] = $this->makeUserWithMember('anggota');
        $member->update(['status' => 'nonaktif']);

        $this->artisan('pickups:generate-daily')->assertSuccessful();

        $this->assertSame(0, Pickup::where('member_id', $member->id)->count());
    }

    #[Test]
    public function member_without_plotting_still_gets_a_ticket(): void
    {
        $this->seedCore();
        [, $member] = $this->makeUserWithMember('anggota');

        $this->artisan('pickups:generate-daily')->assertSuccessful();

        // officer_id null supaya pengurus bisa menugaskan manual — bukan hilang dari daftar harian.
        $this->assertDatabaseHas('pickups', [
            'member_id' => $member->id,
            'officer_id' => null,
            'status' => 'menunggu',
        ]);
    }
}
