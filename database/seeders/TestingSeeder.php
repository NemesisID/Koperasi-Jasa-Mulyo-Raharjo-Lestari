<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\DetailPengangkutan;
use App\Models\FinanceCategory;
use App\Models\Member;
use App\Models\Pickup;
use App\Models\PickupItem;
use App\Models\PriceChangeLog;
use App\Models\Report;
use App\Models\SetoranKoperasi;
use App\Models\ShuDistribution;
use App\Models\ShuMember;
use App\Models\TrashCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data lengkap untuk testing per fitur.
 * Jalankan: php artisan db:seed --class=TestingSeeder
 * (memanggil DatabaseSeeder dulu untuk akun & master data)
 */
class TestingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $ketua = User::where('username', 'ketua')->first();
        $pengurus = User::where('username', 'bendahara')->first();
        $petugas = User::where('username', 'petugas')->first();

        // Anggota tambahan agar fitur keanggotaan/filter status bisa dites
        $extraAnggota = collect([
            ['name' => 'Joko Susilo', 'username' => 'joko', 'status' => 'aktif', 'code' => 'MBR-202609-0002'],
            ['name' => 'Rina Mulyani', 'username' => 'rina', 'status' => 'aktif', 'code' => 'MBR-202609-0003'],
            ['name' => 'Agus Darmawan', 'username' => 'agus', 'status' => 'nonaktif', 'code' => 'MBR-202609-0004'],
            ['name' => 'Dewi Lestari', 'username' => 'dewi', 'status' => 'suspend', 'code' => 'MBR-202609-0005'],
        ])->map(function (array $a) {
            $user = User::create([
                'name' => $a['name'],
                'username' => $a['username'],
                'email' => $a['username'].'@koperasimulyoraharjo.com',
                'password' => Hash::make('password123'),
                'role' => 'anggota',
                'address' => 'Dusun Mulyo RT 0'.rand(1, 5).' RW 01',
                'phone' => '0812'.rand(10000000, 99999999),
            ]);

            return Member::create([
                'user_id' => $user->id,
                'member_category_id' => rand(1, 3),
                'member_code' => $a['code'],
                'name' => $a['name'],
                'address' => $user->address,
                'phone' => $user->phone,
                'status' => $a['status'],
                'join_date' => now()->subMonths(rand(1, 6))->toDateString(),
            ]);
        });

        $members = Member::pluck('id'); // termasuk Siti (MBR-0001) dari DatabaseSeeder
        $aktif = Member::where('status', 'aktif')->pluck('id');

        /* ===================== SIMPANAN (setoran_koperasi) ===================== */
        // POKOK lunas untuk semua anggota aktif
        foreach ($aktif as $memberId) {
            SetoranKoperasi::create([
                'user_id' => Member::find($memberId)->user_id,
                'jenis' => 'PEMASUKAN',
                'jumlah' => 100000,
                'status' => 'SELESAI',
                'label' => 'POKOK',
                'catatan' => 'Pelunasan simpanan pokok saat pendaftaran',
            ]);
        }
        // WAJIB bulan berjalan: campuran status untuk test billing & filter
        foreach ($aktif as $i => $memberId) {
            SetoranKoperasi::create([
                'user_id' => Member::find($memberId)->user_id,
                'jenis' => 'PEMASUKAN',
                'jumlah' => 5000,
                'status' => $i === 0 ? 'PENDING' : 'SELESAI', // anggota pertama belum bayar -> muncul di billing-status
                'label' => 'WAJIB',
                'catatan' => 'Iuran wajib bulan '.now()->format('F Y'),
            ]);
        }
        // TIPPING fee & SUKARELA
        SetoranKoperasi::create(['user_id' => $aktif->first() ? Member::find($aktif[0])->user_id : $pengurus->id, 'jenis' => 'PEMASUKAN', 'jumlah' => 40000, 'status' => 'SELESAI', 'label' => 'TIPPING', 'catatan' => 'Tipping fee bulan '.now()->format('F Y')]);
        SetoranKoperasi::create(['user_id' => Member::find($aktif[0])->user_id, 'jenis' => 'PEMASUKAN', 'jumlah' => 250000, 'status' => 'SELESAI', 'label' => 'SUKARELA', 'catatan' => 'Simpanan sukarela']);
        SetoranKoperasi::create(['user_id' => Member::find($aktif[1])->user_id, 'jenis' => 'PEMASUKAN', 'jumlah' => 50000, 'status' => 'PROSES', 'label' => 'WAJIB', 'catatan' => 'Menunggu konfirmasi kasir']);

        /* ===================== TRANSAKSI KAS ===================== */
        $fcSimpanan = FinanceCategory::where('group_type', 'simpanan_pokok')->first();
        $fcOperasional = FinanceCategory::where('name', 'Biaya Operasional Lapangan')->first();
        $fcPotongan = FinanceCategory::where('name', 'Potongan Admin Sampah 20%')->first();

        $trx1 = Transaction::create([
            'transaction_code' => 'TRX-202608-0001',
            'member_id' => $aktif[0],
            'category_id' => $fcSimpanan->id,
            'type' => 'income',
            'amount' => 100000,
            'description' => 'Simpanan pokok anggota baru',
            'payment_method' => 'tunai',
            'status' => 'berhasil',
            'transaction_date' => now()->subMonth(),
            'handled_by' => $pengurus->id,
        ]);
        $trx2 = Transaction::create([
            'transaction_code' => 'TRX-202609-0001',
            'member_id' => null,
            'category_id' => $fcOperasional->id,
            'type' => 'expense',
            'amount' => 350000,
            'description' => 'BBM armada pengangkutan bulan ini',
            'payment_method' => 'tunai',
            'status' => 'berhasil',
            'transaction_date' => now()->subDays(3),
            'handled_by' => $pengurus->id,
        ]);
        $trx3 = Transaction::create([
            'transaction_code' => 'TRX-202609-0002',
            'member_id' => $aktif[0],
            'category_id' => $fcPotongan->id,
            'type' => 'income',
            'amount' => 7300,
            'description' => 'Potongan 20% transaksi timbangan NTC-202609-0001',
            'payment_method' => 'sampah',
            'status' => 'berhasil',
            'transaction_date' => now()->subDays(2),
            'handled_by' => $petugas->id,
        ]);

        /* ===================== PICKUP & TIMBANG ===================== */
        $tembaga = TrashCategory::where('name', 'Tembaga')->first();
        $aqua = TrashCategory::where('name', 'Aqua bersih')->first();
        $kardus = TrashCategory::where('name', 'Kardus')->first();
        $monitor = TrashCategory::where('name', 'Monitor tabung')->first();

        // Pickup selesai + item timbangan (anggota pertama, nilai konsisten)
        // gross 36500 = tembaga 0.2kg*125000 + aqua 5kg*4500 -> fee 20% = 7300 -> net 29200
        $pickupSelesai = Pickup::create([
            'officer_id' => $petugas->id,
            'member_id' => $aktif[0],
            'location_type' => 'jemput_rumah',
            'is_sorted' => true,
            'scheduled_at' => now()->subDays(2)->setTime(9, 0),
            'completed_at' => now()->subDays(2)->setTime(10, 30),
            'status' => 'selesai',
            'notes' => 'Sampah sudah dipisah per kategori',
            'total_gross' => 36500,
            'total_fee' => 7300,
            'total_net' => 29200,
        ]);
        PickupItem::create(['pickup_id' => $pickupSelesai->id, 'category_id' => $tembaga->id, 'weight_kg' => 0.2, 'unit_count' => 0, 'total_value' => 25000, 'deposit_date' => now()->subDays(2), 'transaction_id' => $trx3->id]);
        PickupItem::create(['pickup_id' => $pickupSelesai->id, 'category_id' => $aqua->id, 'weight_kg' => 5, 'unit_count' => 0, 'total_value' => 22500, 'deposit_date' => now()->subDays(2)]);

        // Pickup menunggu penjemputan (untuk test weigh-items)
        $pickupMenunggu = Pickup::create([
            'officer_id' => $petugas->id,
            'member_id' => $aktif[1],
            'location_type' => 'gudang',
            'is_sorted' => false,
            'scheduled_at' => now()->addDays(1)->setTime(8, 0),
            'status' => 'menunggu',
            'notes' => 'Antar langsung ke gudang',
        ]);

        // Pickup elektronik per unit (untuk test unit_count)
        $pickupUnit = Pickup::create([
            'officer_id' => $petugas->id,
            'member_id' => $aktif[2] ?? $aktif[0],
            'location_type' => 'gudang',
            'is_sorted' => false,
            'scheduled_at' => now()->subDays(5)->setTime(13, 0),
            'completed_at' => now()->subDays(5)->setTime(14, 0),
            'status' => 'selesai',
            'notes' => 'Setoran elektronik bekas',
            'total_gross' => 75000,
            'total_fee' => 15000,
            'total_net' => 60000,
        ]);
        PickupItem::create(['pickup_id' => $pickupUnit->id, 'category_id' => $monitor->id, 'weight_kg' => 0, 'unit_count' => 3, 'total_value' => 75000, 'deposit_date' => now()->subDays(5)]);

        // Pickup batal (untuk test fitur cancel & filter status)
        $pickupBatal = Pickup::create([
            'officer_id' => $petugas->id,
            'member_id' => $aktif[0],
            'location_type' => 'jemput_rumah',
            'is_sorted' => false,
            'scheduled_at' => now()->subDays(7)->setTime(10, 0),
            'status' => 'batal',
            'notes' => 'Dibatalkan: anggota berhalangan',
        ]);

        /* ===================== KOMPLAIN ===================== */
        Complaint::create([
            'pickup_id' => $pickupSelesai->id,
            'member_id' => $aktif[0],
            'issue_type' => 'berat_salah',
            'description' => 'Berat tembaga tercatat 0.2 kg, seharusnya 0.5 kg.',
            'status' => 'diajukan', // untuk test fitur resolve
        ]);
        Complaint::create([
            'pickup_id' => $pickupUnit->id,
            'member_id' => $pickupUnit->member_id,
            'issue_type' => 'kategori_salah',
            'description' => 'Monitor seharusnya dihitung sebagai CPU utuh.',
            'status' => 'diterima',
            'adjustment_amount' => 15000,
            'resolution_note' => 'Revisi kategori, selisih dikreditkan ke saldo.',
            'resolved_by' => $pengurus->id,
            'resolved_at' => now()->subDays(3),
        ]);
        Complaint::create([
            'pickup_id' => $pickupBatal->id,
            'member_id' => $pickupBatal->member_id,
            'issue_type' => 'lainnya',
            'description' => 'Petugas tidak datang sesuai jadwal.',
            'status' => 'ditolak',
            'resolution_note' => 'Jadwal sudah diinformasikan dibatalkan sebelumnya.',
            'resolved_by' => $ketua->id,
            'resolved_at' => now()->subDays(6),
        ]);

        /* ===================== RIWAYAT HARGA ===================== */
        PriceChangeLog::create([
            'trash_category_id' => $aqua->id,
            'old_price_sorted' => 4800, 'new_price_sorted' => 5000,
            'old_price_unsorted' => 4200, 'new_price_unsorted' => 4500,
            'notes' => 'Update WA paguyuban',
            'changed_by' => $pengurus->id,
        ]);
        PriceChangeLog::create([
            'trash_category_id' => $tembaga->id,
            'old_price_sorted' => 128000, 'new_price_sorted' => 130000,
            'old_price_unsorted' => 123000, 'new_price_unsorted' => 125000,
            'notes' => 'Harga pasar logam naik',
            'changed_by' => $pengurus->id,
        ]);

        /* ===================== DOMPET: PENARIKAN ===================== */
        WithdrawRequest::create([
            'member_id' => $aktif[0],
            'amount' => 20000,
            'method' => 'tunai',
            'status' => 'pending', // untuk test approve
        ]);
        WithdrawRequest::create([
            'member_id' => $aktif[1],
            'amount' => 150000,
            'method' => 'transfer',
            'bank_name' => 'BRI',
            'account_number' => '1234567890',
            'account_holder' => 'Joko Susilo',
            'status' => 'disetujui',
            'proof_file' => 'bukti-transfer/123456.pdf',
            'notes' => 'Transfer selesai',
            'processed_by' => $pengurus->id,
            'processed_at' => now()->subDays(1),
        ]);
        WithdrawRequest::create([
            'member_id' => $aktif[2] ?? $aktif[0],
            'amount' => 50000,
            'method' => 'tunai',
            'status' => 'ditolak',
            'notes' => 'Saldo tidak mencukupi',
            'processed_by' => $pengurus->id,
            'processed_at' => now()->subDays(2),
        ]);

        /* ===================== SHU ===================== */
        // Periode lalu: sudah dibagikan
        $shu2025 = ShuDistribution::create([
            'year' => 2025,
            'total_shu' => 25000000,
            'reserve_amount' => 2500000,
            'distributed_amount' => 22500000,
            'recipient_count' => $aktif->count(),
            'status' => 'dibagikan',
            'distribution_date' => '2026-01-15',
            'handled_by' => $ketua->id,
        ]);
        foreach ($aktif as $i => $memberId) {
            ShuMember::create([
                'shu_distribution_id' => $shu2025->id,
                'member_id' => $memberId,
                'simpanan_pokok_amount' => 100000,
                'simpanan_wajib_amount' => 60000,
                'participation_amount' => 90000,
                'total_shu' => 250000,
                'status' => 'sudah_dibagikan',
                'paid_at' => '2026-01-15 10:00:00',
            ]);
        }
        // Periode berjalan: draft (untuk test simulate & publish)
        $shu2026 = ShuDistribution::create([
            'year' => 2026,
            'total_shu' => 50000000,
            'reserve_amount' => 5000000,
            'distributed_amount' => 45000000,
            'recipient_count' => $aktif->count(),
            'status' => 'draft',
            'handled_by' => $ketua->id,
        ]);
        foreach ($aktif as $memberId) {
            ShuMember::create([
                'shu_distribution_id' => $shu2026->id,
                'member_id' => $memberId,
                'simpanan_pokok_amount' => 100000,
                'simpanan_wajib_amount' => 55000,
                'participation_amount' => 95000,
                'total_shu' => 250000,
                'status' => 'menunggu',
            ]);
        }

        /* ===================== LAPORAN ===================== */
        Report::create([
            'title' => 'Laporan Keuangan Semester I 2026',
            'type' => 'laba_rugi',
            'period_start' => '2026-01-01',
            'period_end' => '2026-06-30',
            'file_path' => 'reports/laba-rugi-s1-2026.pdf',
            'status' => 'finalized',
            'created_by' => $pengurus->id,
            'created_at' => now()->subDays(10),
        ]);
        Report::create([
            'title' => 'Laporan Saldo Anggota Agustus 2026',
            'type' => 'saldo',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'file_path' => 'reports/saldo-anggota-202608.pdf',
            'status' => 'review',
            'created_by' => $pengurus->id,
            'created_at' => now()->subDays(2),
        ]);

        /* ===================== LOGISTIK ===================== */
        DetailPengangkutan::create([
            'user_id' => $petugas->id,
            'jadwal_angkut' => now()->subDays(1)->setTime(6, 30),
            'total_organik' => 120.5,
            'total_anorganik' => 85.25,
            'kecamatan' => 'Ponorogo',
            'desa' => 'Mulyoagung',
            'dusun' => 'Mulyo',
            'rw' => '01',
            'rt' => '02',
            'alamat' => 'Jl. Raya Mulyo No. 10',
        ]);
        DetailPengangkutan::create([
            'user_id' => $petugas->id,
            'jadwal_angkut' => now()->setTime(6, 30),
            'total_organik' => 98.75,
            'total_anorganik' => 110.0,
            'kecamatan' => 'Ponorogo',
            'desa' => 'Sumberejo',
            'dusun' => 'Rejo',
            'rw' => '02',
            'rt' => '01',
            'alamat' => 'Jl. Diponegoro No. 5',
        ]);

        $this->command?->info('TestingSeeder selesai. Login: ketua / bendahara / petugas / warga / joko / rina / agus / dewi — password: password123');
    }
}
