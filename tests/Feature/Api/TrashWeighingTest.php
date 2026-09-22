<?php

namespace Tests\Feature\Api;

use App\Models\MemberCategory;
use App\Models\Pickup;
use App\Models\Transaction;
use App\Services\TrashWeighingService;
use App\Services\WalletService;
use PHPUnit\Framework\Attributes\Test;

class TrashWeighingTest extends ApiTestCase
{
    #[Test]
    public function weighing_pays_the_catalog_member_price_without_a_second_deduction(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [$warga, $member] = $this->makeUserWithMember('anggota');

        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'gudang'], $petugas);

        // API: petugas submit timbangan — 2kg Tembaga unsorted (kotor 125.000 → anggota 100.000/kg)
        $response = $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [
                ['category_id' => 1, 'weight_kg' => 2],
            ],
        ]);

        // Harga anggota = 80% harga kotor = 100.000/kg → 200.000, tanpa potongan lagi.
        $response->assertStatus(200)
            ->assertJsonPath('data.pickup.status', 'selesai')
            ->assertJsonPath('data.net_earned', 200000);

        $pickup = Pickup::find($ticket->id);
        $this->assertEquals(200000, $pickup->total_gross);
        $this->assertEquals(0, $pickup->total_fee);
        $this->assertEquals(200000, $pickup->total_net);

        // Tidak ada jurnal income potongan 20% lagi — margin koperasi melekat di
        // selisih harga jual vs harga beli, bukan potongan saat menimbang.
        $this->assertNull(
            Transaction::where('type', 'income')->where('amount', 50000)->first(),
            'jurnal potongan 20% seharusnya sudah tidak dibuat',
        );

        // Jurnal kas: beli sampah (expense) harus punya category_id — regression SQL 1048
        $purchase = Transaction::where('type', 'expense')->where('amount', 200000)
            ->where('description', 'Beli sampah anggota #'.$ticket->id)->first();
        $this->assertNotNull($purchase, 'purchase journal missing');
        $this->assertNotNull($purchase->category_id, 'purchase journal category_id is null');
        $this->assertEquals($petugas->id, $purchase->handled_by, 'petugas harus user yang login');

        // Saldo dompet anggota naik 200.000 — angka yang diterima anggota tidak berubah
        $this->assertEquals(200000.0, app(WalletService::class)->getMemberWalletSummary($member->id)['current_balance']);
    }

    #[Test]
    public function pickup_price_uses_pickup_location_discount(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [, $member] = $this->makeUserWithMember('anggota');

        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'jemput_rumah'], $petugas);

        // 2kg Tembaga unsorted jemput: harga anggota 100.000 - 2.000 (ongkos logam) = 98.000/kg → 196.000
        $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 2]],
        ])->assertStatus(200);

        $pickup = Pickup::find($ticket->id);
        $this->assertEquals(196000, $pickup->total_gross);
        $this->assertEquals(196000, $pickup->total_net);
    }

    #[Test]
    public function reweigh_replaces_journal_and_items(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [, $member] = $this->makeUserWithMember('anggota');

        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'gudang'], $petugas);

        $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 1]],
        ])->assertStatus(200);

        // Timbang ulang = edit timbangan: jurnal lama dibatalkan, diganti baru.
        $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 2]],
        ])->assertStatus(200);

        $purchase = Transaction::where('description', 'Beli sampah anggota #'.$ticket->id)->get();
        $this->assertCount(2, $purchase, 'reweigh harus meninggalkan jurnal lama (gagal) + baru (berhasil)');
        $this->assertEqualsCanonicalizing(['berhasil', 'gagal'], $purchase->pluck('status')->all());
        $purchase->pluck('category_id')->each(fn ($id) => $this->assertNotNull($id, 'jurnal beli sampah tidak boleh category_id null'));
    }

    #[Test]
    public function member_cannot_weigh(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [$warga, $member] = $this->makeUserWithMember('anggota');

        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'gudang'], $petugas);

        $this->actingAs($warga)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 1]],
        ])->assertStatus(403);
    }

    #[Test]
    public function member_pickup_request_completes_when_officer_weighs_it(): void
    {
        // Regresi bug "minta jemput nyangkut": tiket buatan anggota harus selesai
        // saat ditimbang petugas — bukan ditinggal dan dibuat tiket baru.
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [$warga, $member] = $this->makeUserWithMember('anggota');

        $ticket = app(TrashWeighingService::class)
            ->createTicket(['member_id' => $member->id, 'location_type' => 'jemput_rumah'], $warga);
        $this->assertNull($ticket->officer_id, 'tiket minta jemput dibuat tanpa petugas');

        $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 2]],
        ])->assertStatus(200);

        $pickup = Pickup::find($ticket->id);
        $this->assertEquals('selesai', $pickup->status);
        $this->assertEquals($petugas->id, $pickup->officer_id, 'petugas penimbang tercatat di tiket anggota');
    }

    #[Test]
    public function gudang_sorted_uses_the_member_price(): void
    {
        // Sorted gudang dinilai harga anggota katalog (80% harga jual), tanpa potongan lagi.
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [, $member] = $this->makeUserWithMember('anggota');

        $ticket = app(TrashWeighingService::class)->createTicket(
            ['member_id' => $member->id, 'location_type' => 'gudang', 'is_sorted' => true],
            $petugas,
        );

        // 2kg × 80% × price_sell 130.000 = 208.000
        $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 2]],
        ])->assertStatus(200);

        $this->assertEquals(208000, Pickup::find($ticket->id)->total_gross);
    }

    #[Test]
    public function pengurus_creates_anggota_with_multiple_categories(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        MemberCategory::create(['name' => 'rumah']);
        MemberCategory::create(['name' => 'pasar']);

        $response = $this->actingAs($pengurus)->postJson('/api/v1/users', [
            'name' => 'Anggota Pasar',
            'username' => 'anggota_pasar',
            'email' => 'pasar@example.com',
            'password' => 'password123',
            'role' => 'anggota',
            'member_types' => ['rumah', 'pasar'],
            'phone' => '081234567890',
            'address' => 'Pasar Mulyo',
        ]);

        $response->assertStatus(201);
        $member = \App\Models\Member::where('user_id', $response->json('data.id'))->first();
        $this->assertNotNull($member);
        $this->assertEquals(['rumah', 'pasar'], $member->categories);
        $this->assertEquals('aktif', $member->status, 'akun buatan pengurus langsung aktif');

        // Tanpa kategori → 422
        $this->actingAs($pengurus)->postJson('/api/v1/users', [
            'name' => 'Anggota X',
            'username' => 'anggota_x',
            'email' => 'x@example.com',
            'password' => 'password123',
            'role' => 'anggota',
        ])->assertStatus(422);
    }
}
