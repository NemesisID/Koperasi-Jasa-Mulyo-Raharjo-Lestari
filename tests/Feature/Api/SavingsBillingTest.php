<?php

namespace Tests\Feature\Api;

use App\Models\SetoranKoperasi;
use App\Services\SavingsService;
use PHPUnit\Framework\Attributes\Test;

class SavingsBillingTest extends ApiTestCase
{
    #[Test]
    public function monthly_billing_is_45k_split_wajib_and_tipping(): void
    {
        $this->seedCore();
        [, $member] = $this->makeUserWithMember('anggota');

        $result = app(SavingsService::class)->generateMonthlyBilling();

        $this->assertSame(2, $result['invoices_created']);
        $this->assertSame(2, SetoranKoperasi::count());

        $wajib = SetoranKoperasi::where('label', 'WAJIB')->first();
        $tipping = SetoranKoperasi::where('label', 'TIPPING')->first();

        $this->assertEquals(5000, $wajib->jumlah);
        $this->assertEquals(40000, $tipping->jumlah);
        $this->assertEquals('PENDING', $wajib->status);
        $this->assertEquals('PENDING', $tipping->status);
    }

    #[Test]
    public function billing_generation_is_idempotent(): void
    {
        $this->seedCore();
        $this->makeUserWithMember('anggota');

        $service = app(SavingsService::class);
        $service->generateMonthlyBilling();

        $this->assertSame(0, $service->generateMonthlyBilling()['invoices_created']);
    }

    #[Test]
    public function paying_savings_records_journal_and_marks_lunas(): void
    {
        $this->seedCore();
        [$bendahara] = $this->makeUserWithMember('pengurus');
        [, $member] = $this->makeUserWithMember('anggota');

        $service = app(SavingsService::class);
        $service->generateMonthlyBilling();

        // Anggota bayar WAJIB + TIPPING via API
        $this->actingAs($bendahara)->postJson('/api/v1/savings/pay', [
            'member_id' => $member->id,
            'label' => 'WAJIB',
            'jumlah' => 5000,
            'metode' => 'tunai',
        ])->assertStatus(201);

        $this->actingAs($bendahara)->postJson('/api/v1/savings/pay', [
            'member_id' => $member->id,
            'label' => 'TIPPING',
            'jumlah' => 40000,
            'metode' => 'transfer',
        ])->assertStatus(201);

        $status = $service->getBillingStatus($member->id);
        $this->assertTrue($status['fully_paid']);
        $this->assertEquals(45000, $status['total_monthly']);

        // Dua jurnal income tercatat (5rb + 40rb)
        $this->assertSame(2, \App\Models\Transaction::where('type', 'income')->count());
    }

    #[Test]
    public function billing_status_endpoint_returns_own_member_data(): void
    {
        $this->seedCore();
        [$warga, $member] = $this->makeUserWithMember('anggota');
        app(SavingsService::class)->generateMonthlyBilling();

        $this->actingAs($warga)
            ->getJson('/api/v1/savings/billing-status')
            ->assertStatus(200)
            ->assertJsonPath('data.member.id', $member->id)
            ->assertJsonPath('data.total_monthly', 45000)
            ->assertJsonPath('data.fully_paid', false);
    }
}
