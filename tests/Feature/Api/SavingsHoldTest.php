<?php

namespace Tests\Feature\Api;

use App\Models\SetoranKoperasi;
use App\Models\TrashCategory;
use App\Services\SavingsService;
use App\Services\TrashWeighingService;
use App\Services\WalletService;
use PHPUnit\Framework\Attributes\Test;

class SavingsHoldTest extends ApiTestCase
{
    /**
     * Beri anggota saldo sampah: timbang 1 kg Tembaga di gudang (belum terpilah)
     * → bruto 125.000, potongan 20% 25.000, net 100.000.
     */
    private function giveTrashBalance(int $memberId): void
    {
        $petugas = $this->makeUserWithMember('petugas')[0];
        $weighing = app(TrashWeighingService::class);
        $ticket = $weighing->createTicket(['member_id' => $memberId, 'location_type' => 'gudang'], $petugas);
        $weighing->weighAndComplete($ticket->id, [['category_id' => TrashCategory::first()->id, 'weight_kg' => 1]], $petugas);
    }

    #[Test]
    public function hold_deducts_50k_from_trash_balance(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        $this->giveTrashBalance($member->id);

        $wallet = app(WalletService::class);
        $this->assertEquals(100000.0, $wallet->getMemberWalletSummary($member->id)['balance_from_trash']);

        $result = app(SavingsService::class)->holdMonthlyWajib($member, $pengurus);

        $this->assertTrue($result['held']);

        // Paket rutin dipecah 45.000 operasional + 5.000 simpanan, ditandai sumber 'saldo'.
        $this->assertSame(2, SetoranKoperasi::where('status', 'SELESAI')->count());
        $this->assertSame('saldo', SetoranKoperasi::where('label', 'WAJIB')->first()->sumber);

        $after = $wallet->getMemberWalletSummary($member->id);
        $this->assertEquals(50000.0, $after['balance_from_trash']);
        $this->assertEquals(50000.0, $after['savings_hold']);
    }

    #[Test]
    public function hold_is_skipped_when_trash_balance_is_insufficient(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        $result = app(SavingsService::class)->holdMonthlyWajib($member, $pengurus);

        $this->assertFalse($result['held']);
        $this->assertSame('saldo_kurang', $result['reason']);
        $this->assertSame(0, SetoranKoperasi::count());
        // Baru 1 bulan gagal — belum sampai batas 3 bulan.
        $this->assertSame(1, $result['months_failed']);
        $this->assertSame('aktif', $member->fresh()->status);
    }

    #[Test]
    public function member_is_deactivated_after_three_consecutive_failed_months(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        // Bergabung 3 bulan lalu, belum pernah melunasi tagihan rutin.
        $member->update(['join_date' => now()->subMonths(3)->toDateString()]);

        $result = app(SavingsService::class)->holdMonthlyWajib($member->fresh(), $pengurus);

        $this->assertSame(3, $result['months_failed']);
        $this->assertSame('saldo_kurang_dinonaktifkan', $result['reason']);
        $this->assertSame('nonaktif', $member->fresh()->status);
    }

    #[Test]
    public function manual_50k_payment_reactivates_nonaktif_member(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');
        $member->update(['status' => 'nonaktif']);

        // Jalur "bisa manual bayar" — paket Rp50.000 tetap boleh meski nonaktif.
        $this->actingAs($pengurus)->postJson('/api/v1/savings/pay', [
            'member_id' => $member->id,
            'label' => 'WAJIB',
            'jumlah' => 50000,
            'metode' => 'tunai',
        ])->assertStatus(201);

        $this->assertSame('aktif', $member->fresh()->status);
        $this->assertSame('tunai', SetoranKoperasi::where('label', 'WAJIB')->first()->sumber);
    }

    #[Test]
    public function hold_does_not_double_charge_in_the_same_month(): void
    {
        $this->seedCore();
        [$pengurus] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');
        $this->giveTrashBalance($member->id);

        $service = app(SavingsService::class);

        $this->assertTrue($service->holdMonthlyWajib($member, $pengurus)['held']);

        $second = $service->holdMonthlyWajib($member->fresh(), $pengurus);
        $this->assertFalse($second['held']);
        $this->assertSame('sudah_lunas', $second['reason']);

        // Tetap satu paket: 2 baris (WAJIB + TIPPING), potongan total 50.000.
        $this->assertSame(2, SetoranKoperasi::count());
        $this->assertEquals(50000.0, app(WalletService::class)->getMemberWalletSummary($member->id)['savings_hold']);
    }

    #[Test]
    public function artisan_command_runs_the_hold(): void
    {
        $this->seedCore();
        $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');
        $this->giveTrashBalance($member->id);

        $this->artisan('savings:hold-monthly')->assertSuccessful();

        $this->assertEquals(50000.0, app(WalletService::class)->getMemberWalletSummary($member->id)['savings_hold']);
    }
}
