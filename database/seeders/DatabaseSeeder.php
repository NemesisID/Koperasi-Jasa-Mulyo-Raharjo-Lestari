<?php

namespace Database\Seeders;

use App\Models\FinanceCategory;
use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\TrashCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Default untuk Setiap Role (3 role: pengurus, petugas, anggota)
        $admin = User::create([
            'name' => 'Ketua Koperasi',
            'username' => 'ketua',
            'email' => 'ketua@koperasimulyoraharjo.com',
            'password' => Hash::make('password123'),
            'role' => 'pengurus',
            'address' => 'Kantor Pusat Koperasi Mulyo Raharjo',
        ]);

        $pengurus = User::create([
            'name' => 'Bendahara Koperasi',
            'username' => 'bendahara',
            'email' => 'bendahara@koperasimulyoraharjo.com',
            'password' => Hash::make('password123'),
            'role' => 'pengurus',
            'address' => 'Kantor Kas Koperasi Mulyo Raharjo',
        ]);

        $petugas = User::create([
            'name' => 'Budi Petugas Lapangan',
            'username' => 'petugas',
            'email' => 'petugas@koperasimulyoraharjo.com',
            'password' => Hash::make('password123'),
            'role' => 'petugas',
            'address' => 'Gudang Logistik Unit 1',
        ]);

        $userWarga = User::create([
            'name' => 'Siti Warga Lestari',
            'username' => 'warga',
            'email' => 'warga@koperasimulyoraharjo.com',
            'password' => Hash::make('password123'),
            'role' => 'anggota',
            'address' => 'RT 02 RW 01 Dusun Mulyo',
        ]);

        // 2. Kategori Anggota (jenis member: rumah / pasar) & Profil Member
        $catRumah = MemberCategory::create(['name' => 'rumah']);
        MemberCategory::create(['name' => 'pasar']);

        $memberWarga = Member::create([
            'user_id' => $userWarga->id,
            'member_category_id' => $catRumah->id,
            'categories' => ['rumah'],
            'member_code' => 'MBR-202609-0001',
            'name' => $userWarga->name,
            'address' => $userWarga->address,
            'phone' => '081234567890',
            'status' => 'aktif',
            'join_date' => now()->toDateString(),
        ]);

        // 3. Kategori Keuangan (Chart of Accounts Kas)
        FinanceCategory::insert([
            ['name' => 'Simpanan Pokok', 'type' => 'income', 'group_type' => 'simpanan_pokok'],
            ['name' => 'Simpanan Wajib', 'type' => 'income', 'group_type' => 'simpanan_wajib'],
            ['name' => 'Simpanan Sukarela', 'type' => 'income', 'group_type' => 'operasional'],
            ['name' => 'Tipping Fee Pengangkutan', 'type' => 'income', 'group_type' => 'tipping_fee'],
            ['name' => 'Potongan Admin Sampah 20%', 'type' => 'income', 'group_type' => 'operasional'],
            ['name' => 'Pencairan Saldo Sampah / Cashout', 'type' => 'expense', 'group_type' => 'operasional'],
            ['name' => 'Distribusi SHU Anggota', 'type' => 'expense', 'group_type' => 'operasional'],
            ['name' => 'Biaya Operasional Lapangan', 'type' => 'expense', 'group_type' => 'operasional'],
            // Kategori jualan sampah (marketplace) + pemasukan dari sampah untuk labeling cashflow.
            ['name' => 'Penjualan Sampah', 'type' => 'income', 'group_type' => 'penjualan_sampah'],
            ['name' => 'Pemasukan Sampah Lainnya', 'type' => 'income', 'group_type' => 'penjualan_sampah'],
        ]);

        // 4. Katalog Sampah & Harga Awal (Referensi PRD §5.1.1 UD Sapu Jagad Ponorogo)
        $trashItems = [
            // Logam
            ['name' => 'Tembaga', 'type' => 'logam', 'unit' => 'kg', 'price_sorted' => 130000, 'price_unsorted' => 125000],
            ['name' => 'Tembaga Dandang', 'type' => 'logam', 'unit' => 'kg', 'price_sorted' => 98000, 'price_unsorted' => 92000],
            ['name' => 'Tembaga Radiator', 'type' => 'logam', 'unit' => 'kg', 'price_sorted' => 49000, 'price_unsorted' => 45000],
            ['name' => 'Kuningan', 'type' => 'logam', 'unit' => 'kg', 'price_sorted' => 73000, 'price_unsorted' => 68000],
            ['name' => 'Aki', 'type' => 'logam', 'unit' => 'kg', 'price_sorted' => 10000, 'price_unsorted' => 9000],
            ['name' => 'Alumunium campur', 'type' => 'logam', 'unit' => 'kg', 'price_sorted' => 18000, 'price_unsorted' => 16000],

            // Besi
            ['name' => 'Besi Tebal/Padat', 'type' => 'besi', 'unit' => 'kg', 'price_sorted' => 4300, 'price_unsorted' => 4000],
            ['name' => 'Kaleng', 'type' => 'besi', 'unit' => 'kg', 'price_sorted' => 2500, 'price_unsorted' => 2000],
            ['name' => 'Drum', 'type' => 'besi', 'unit' => 'kg', 'price_sorted' => 2500, 'price_unsorted' => 2200],
            ['name' => 'Seng', 'type' => 'besi', 'unit' => 'kg', 'price_sorted' => 1200, 'price_unsorted' => 1000],

            // Kertas
            ['name' => 'Kardus', 'type' => 'kertas', 'unit' => 'kg', 'price_sorted' => 1800, 'price_unsorted' => 1500],
            ['name' => 'Sak Semen', 'type' => 'kertas', 'unit' => 'kg', 'price_sorted' => 4500, 'price_unsorted' => 4000],
            ['name' => 'HVS bersih', 'type' => 'kertas', 'unit' => 'kg', 'price_sorted' => 2000, 'price_unsorted' => 1700],
            ['name' => 'Buram bersih', 'type' => 'kertas', 'unit' => 'kg', 'price_sorted' => 1300, 'price_unsorted' => 1000],

            // Atom / Plastik
            ['name' => 'Aqua bersih', 'type' => 'plastik', 'unit' => 'kg', 'price_sorted' => 5000, 'price_unsorted' => 4500],
            ['name' => 'Aqua kotor', 'type' => 'plastik', 'unit' => 'kg', 'price_sorted' => 2200, 'price_unsorted' => 1800],
            ['name' => 'Putihan / Botol Oli', 'type' => 'plastik', 'unit' => 'kg', 'price_sorted' => 3200, 'price_unsorted' => 2800],
            ['name' => 'Tutup Galon', 'type' => 'plastik', 'unit' => 'kg', 'price_sorted' => 4000, 'price_unsorted' => 3500],

            // Elektronik & Lainnya
            ['name' => 'CPU utuh/komplit', 'type' => 'elektronik', 'unit' => 'unit', 'price_sorted' => 50000, 'price_unsorted' => 45000],
            ['name' => 'Monitor tabung', 'type' => 'elektronik', 'unit' => 'unit', 'price_sorted' => 25000, 'price_unsorted' => 20000],
            ['name' => 'Laptop utuh', 'type' => 'elektronik', 'unit' => 'unit', 'price_sorted' => 30000, 'price_unsorted' => 25000],
            ['name' => 'Sampah Campur Organik', 'type' => 'campur', 'unit' => 'kg', 'price_sorted' => 500, 'price_unsorted' => 300],
        ];

        foreach ($trashItems as $item) {
            // price_sell = harga jual ke pengepul (pakai price_sorted);
            // harga anggota (80%) dihitung on-the-fly dari price_sell.
            TrashCategory::create([
                'name' => $item['name'],
                'type' => $item['type'],
                'unit' => $item['unit'],
                'price_sorted' => $item['price_sorted'],
                'price_unsorted' => $item['price_unsorted'],
                'price_sell' => $item['price_sorted'],
                'is_active' => true,
            ]);
        }
    }
}
