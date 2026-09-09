<?php

namespace Tests\Feature\Api;

use App\Models\Pickup;
use App\Models\Transaction;
use App\Services\TrashWeighingService;
use App\Services\WalletService;
use PHPUnit\Framework\Attributes\Test;

class TrashWeighingTest extends ApiTestCase
{
    #[Test]
    public function weighing_charges_manual_admin_fee_and_credits_net_to_member(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [$warga, $member] = $this->makeUserWithMember('anggota');

        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'gudang'], $petugas);

        // API: petugas submit timbangan — 2kg Tembaga (price_sell 125.000/kg, price_admin 25.000/kg)
        $response = $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [
                ['category_id' => 1, 'weight_kg' => 2],
            ],
        ]);

        // gross 250.000, fee 25.000x2 = 50.000, net 200.000
        $response->assertStatus(200)
            ->assertJsonPath('data.pickup.status', 'selesai')
            ->assertJsonPath('data.net_earned', 200000);

        $pickup = Pickup::find($ticket->id);
        $this->assertEquals('250000', $pickup->total_gross);
        $this->assertEquals('50000', $pickup->total_fee);
        $this->assertEquals('200000', $pickup->total_net);

        // Jurnal kas: biaya admin tercatat income
        $fee = Transaction::where('type', 'income')->where('amount', 50000)->first();
        $this->assertNotNull($fee, 'fee admin journal missing');
        $this->assertEquals('berhasil', $fee->status);

        // Saldo dompet anggota naik 200.000
        $this->assertEquals(200000.0, app(WalletService::class)->getMemberWalletSummary($member->id)['current_balance']);
    }

    #[Test]
    public function anggota_can_order_pickup_without_member_id(): void
    {
        $this->seedCore();
        [$anggota, $member] = $this->makeUserWithMember('anggota');

        // Pesan ala gojek: cukup location_type — member_id otomatis dari user login
        $this->actingAs($anggota)->postJson('/api/v1/pickups', [
            'location_type' => 'jemput_rumah',
        ])->assertStatus(201)
            ->assertJsonPath('data.member.id', $member->id)
            ->assertJsonPath('data.source', 'manual');
    }

    #[Test]
    public function pickup_price_uses_pickup_location_discount(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [, $member] = $this->makeUserWithMember('anggota');

        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'jemput_rumah'], $petugas);

        // 2kg Tembaga jemput: 125.000 - 2.000 (logam) = 123.000/kg → gross 246.000, fee 50.000, net 196.000
        $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 2]],
        ])->assertStatus(200);

        $pickup = Pickup::find($ticket->id);
        $this->assertEquals('246000', $pickup->total_gross);
        $this->assertEquals('196000', $pickup->total_net);
    }

    #[Test]
    public function double_weighing_is_rejected(): void
    {
        $this->seedCore();
        [$petugas] = $this->makeUserWithMember('petugas');
        [, $member] = $this->makeUserWithMember('anggota');

        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'gudang'], $petugas);

        $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 1]],
        ])->assertStatus(200);

        $this->actingAs($petugas)->postJson("/api/v1/pickups/{$ticket->id}/weigh-items", [
            'items' => [['category_id' => 1, 'weight_kg' => 1]],
        ])->assertStatus(400);
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
}
