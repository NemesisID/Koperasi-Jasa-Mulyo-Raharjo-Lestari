<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use App\Models\TrashCategory;
use App\Models\User;
use App\Services\ShuCalculationEngine;
use App\Services\SavingsService;
use App\Services\TrashWeighingService;
use App\Services\WalletService;
use PHPUnit\Framework\Attributes\Test;

class ShuDistributionTest extends ApiTestCase
{
    #[Test]
    public function simulate_computes_20_percent_pool(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        // Kontribusi anggota: setor sampah + simpanan POKOK
        $tembaga = TrashCategory::first();
        $petugas = $this->makeUserWithMember('petugas')[0];
        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'gudang'], $petugas);
        $weighing->weighAndComplete($ticket->id, [['category_id' => $tembaga->id, 'weight_kg' => 2]], $petugas);
        app(SavingsService::class)->recordSavingsPayment(
            ['member_id' => $member->id, 'label' => 'POKOK', 'jumlah' => 100000, 'metode' => 'tunai'],
            $this->makeUserWithMember('pengurus')[0],
        );

        $response = $this->actingAs($pengurus)->postJson('/api/v1/shu/simulate', [
            'year' => now()->year,
            'net_profit' => 1000000,
            'shu_pool_percentage' => 20,
        ]);

        // Pool 20% dari 1jt = 200rb, dibagi 50/50 modal vs partisipasi
        $response->assertStatus(200)
            ->assertJsonPath('data.shu_pool', 200000)
            ->assertJsonPath('data.recipient_count', 1)
            ->assertJsonPath('data.members.0.total_shu', 200000);

        // Simulasi murni tidak menulis DB
        $this->assertSame(0, \App\Models\ShuDistribution::count());
    }

    #[Test]
    public function publish_credits_member_wallet_massally(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        $tembaga = TrashCategory::first();
        $petugas = $this->makeUserWithMember('petugas')[0];
        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'gudang'], $petugas);
        $weighing->weighAndComplete($ticket->id, [['category_id' => $tembaga->id, 'weight_kg' => 2]], $petugas);

        // Simpanan POKOK agar anggota dapat jasa modal + jasa partisipasi (50/50 → 200rb total)
        app(SavingsService::class)->recordSavingsPayment(
            ['member_id' => $member->id, 'label' => 'POKOK', 'jumlah' => 100000, 'metode' => 'tunai'],
            $this->makeUserWithMember('pengurus')[0],
        );

        $engine = app(ShuCalculationEngine::class);
        $sim = $engine->simulateDistribution(now()->year, 1000000, 20);
        $draft = $engine->saveDraft($sim, $pengurus);

        $balanceBefore = app(WalletService::class)->getMemberWalletSummary($member->id)['current_balance'];

        $this->actingAs($pengurus)->postJson('/api/v1/shu/publish', [
            'shu_distribution_id' => $draft->id,
        ])->assertStatus(200)->assertJsonPath('data.status', 'dibagikan');

        // Saldo naik 200rb (dividen SHU)
        $balanceAfter = app(WalletService::class)->getMemberWalletSummary($member->id)['current_balance'];
        $this->assertEquals(200000.0, $balanceAfter - $balanceBefore);

        // Jurnal expense dividen tercatat
        $this->assertSame(1, \App\Models\Transaction::where('type', 'expense')->where('amount', 200000)->count());
    }

    #[Test]
    public function double_publish_is_rejected(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        $tembaga = TrashCategory::first();
        $petugas = $this->makeUserWithMember('petugas')[0];
        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $member->id, 'location_type' => 'gudang'], $petugas);
        $weighing->weighAndComplete($ticket->id, [['category_id' => $tembaga->id, 'weight_kg' => 2]], $petugas);

        $engine = app(ShuCalculationEngine::class);
        $draft = $engine->saveDraft($engine->simulateDistribution(now()->year, 1000000, 20), $pengurus);

        $this->actingAs($pengurus)->postJson('/api/v1/shu/publish', ['shu_distribution_id' => $draft->id])->assertStatus(200);
        $this->actingAs($pengurus)->postJson('/api/v1/shu/publish', ['shu_distribution_id' => $draft->id])->assertStatus(400);
    }

    #[Test]
    public function only_pengurus_can_publish(): void
    {
        $this->seedCore();
        [$bendahara] = $this->makeUserWithMember('pengurus');

        $this->actingAs($bendahara)->postJson('/api/v1/shu/publish', [
            'shu_distribution_id' => 999,
        ])->assertStatus(403);
    }

    #[Test]
    public function distribution_is_proportional_not_equal(): void
    {
        $this->seedCore();
        $pengurus = $this->makeUserWithMember('pengurus')[0];
        [, $memberA] = $this->makeUserWithMember('anggota');

        // Anggota kedua dibuat langsung: helper memakai username tetap sehingga bentrok.
        $userB = User::create([
            'name' => 'Test Anggota B',
            'username' => 'test_anggota_b',
            'email' => 'test_anggota_b@example.com',
            'password' => bcrypt('password123'),
            'role' => 'anggota',
        ]);
        $memberB = Member::create([
            'user_id' => $userB->id,
            'member_category_id' => $memberA->member_category_id,
            'member_code' => 'MBR-'.now()->format('Ym').'-0002',
            'name' => $userB->name,
            'status' => 'aktif',
            'join_date' => now()->toDateString(),
        ]);

        // Simpanan 3:1 → jasa modal harus ikut rasio itu, bukan dibagi rata.
        $savings = app(SavingsService::class);
        $savings->recordSavingsPayment(['member_id' => $memberA->id, 'label' => 'POKOK', 'jumlah' => 300000, 'metode' => 'tunai'], $pengurus);
        $savings->recordSavingsPayment(['member_id' => $memberB->id, 'label' => 'POKOK', 'jumlah' => 100000, 'metode' => 'tunai'], $pengurus);

        // Hanya anggota B yang setor sampah → dia satu-satunya penerima jasa partisipasi.
        $petugas = $this->makeUserWithMember('petugas')[0];
        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $memberB->id, 'location_type' => 'gudang'], $petugas);
        $weighing->weighAndComplete($ticket->id, [['category_id' => TrashCategory::first()->id, 'weight_kg' => 2]], $petugas);

        $rows = collect(
            app(ShuCalculationEngine::class)->simulateDistribution(now()->year, 1000000, 20)['members']
        )->keyBy('member_id');

        // Pool 200rb → modal 100rb (3:1) + partisipasi 100rb (hanya B).
        $this->assertEquals(75000.0, (float) $rows[$memberA->id]['total_shu']);
        $this->assertEquals(125000.0, (float) $rows[$memberB->id]['total_shu']);

        // Pembagian rata akan memberi keduanya 100rb.
        $this->assertNotEquals($rows[$memberA->id]['total_shu'], $rows[$memberB->id]['total_shu']);
    }
}
