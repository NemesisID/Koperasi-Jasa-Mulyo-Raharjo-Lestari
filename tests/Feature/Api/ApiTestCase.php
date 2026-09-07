<?php

namespace Tests\Feature\Api;

use App\Models\FinanceCategory;
use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\TrashCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function seedCore(): void
    {
        // Kategori keuangan â€” dipakai jurnal kas seluruh modul
        FinanceCategory::insert([
            ['name' => 'Simpanan Pokok', 'type' => 'income', 'group_type' => 'simpanan_pokok'],
            ['name' => 'Simpanan Wajib', 'type' => 'income', 'group_type' => 'simpanan_wajib'],
            ['name' => 'Simpanan Sukarela', 'type' => 'income', 'group_type' => 'operasional'],
            ['name' => 'Tipping Fee Pengangkutan', 'type' => 'income', 'group_type' => 'tipping_fee'],
            ['name' => 'Potongan Admin Sampah 20%', 'type' => 'income', 'group_type' => 'operasional'],
            ['name' => 'Pencairan Saldo Sampah / Cashout', 'type' => 'expense', 'group_type' => 'operasional'],
            ['name' => 'Distribusi SHU Anggota', 'type' => 'expense', 'group_type' => 'operasional'],
        ]);

        TrashCategory::create([
            'name' => 'Tembaga', 'type' => 'logam', 'unit' => 'kg',
            'price_sorted' => 130000, 'price_unsorted' => 125000, 'is_active' => true,
        ]);
    }

    protected function makeUserWithMember(string $role = 'anggota'): array
    {
        $user = User::create([
            'name' => 'Test '.ucfirst($role),
            'username' => 'test_'.$role,
            'email' => "test_{$role}@example.com",
            'password' => bcrypt('password123'),
            'role' => $role,
        ]);

        if ($role === 'anggota') {
            $category = MemberCategory::create(['name' => 'rumah']);
            $member = Member::create([
                'user_id' => $user->id,
                'member_category_id' => $category->id,
                'member_code' => 'MBR-'.now()->format('Ym').'-0001',
                'name' => $user->name,
                'status' => 'aktif',
                'join_date' => now()->toDateString(),
            ]);

            return [$user, $member];
        }

        return [$user, null];
    }
}
