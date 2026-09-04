# PRD — Website Koperasi & Bank Sampah

## 1. Latar Belakang & Tujuan
Koperasi ini menjalankan dua aktivitas utama yang saling terhubung:
1. **Simpan pinjam / koperasi** — anggota membayar simpanan pokok, wajib, sukarela, dan menerima Sisa Hasil Usaha (SHU) tahunan.
2. **Bank sampah** — anggota (warga) menyetor sampah, ditimbang oleh pengepul, dan langsung dikonversi jadi saldo tunai.

Tujuan sistem: mendigitalkan pencatatan simpanan, transaksi bank sampah, dan pembagian SHU, sekaligus memberi transparansi harga & nota ke anggota.

## 2. Peran Pengguna (Role)

| Role | Deskripsi Singkat |
|---|---|
| **Admin** | Kelola master data (kategori sampah, user, konfigurasi), akses penuh laporan |
| **Bendahara/Operator** | Kelola keuangan: update harga harian, verifikasi simpanan, proses SHU |
| **Pengepul** | Input hasil timbang sampah per anggota (jenis + berat) |
| **Anggota/Warga** | Registrasi, setor sampah, lihat saldo/nota, ajukan komplain, lihat SHU |

## 3. Modul Simpanan

| Jenis | Nominal | Ketentuan |
|---|---|---|
| Simpanan Pokok | Rp50.000 | Dibayar sekali di awal saat registrasi |
| Simpanan Wajib | Rp5.000/bulan | Akumulasi ±Rp60.000/tahun |
| Simpanan Sukarela | Nominal bebas | Makin besar → makin besar porsi SHU (rumus final: **TBD**, lihat §8) |
| Tipping Fee | Rp40.000/bulan | Biaya layanan bulanan |
| **Total setoran bulanan** | **Rp45.000** | Tipping fee + simpanan wajib |

**Fitur yang dibutuhkan:**
- Pencatatan status bayar bulanan per anggota (lunas/belum, riwayat)
- Reminder/tagihan bulanan otomatis
- Riwayat simpanan per anggota (pokok, wajib, sukarela terpisah)

## 4. Modul SHU (Sisa Hasil Usaha)

- Sumber: laba bersih dari seluruh aktivitas penjualan koperasi (termasuk bank sampah).
- **Rumus saat ini** (asumsi belum ada simpanan sukarela aktif):
  `20% dari laba bersih tahunan ÷ jumlah anggota = SHU per anggota (rata)`
- Saat simpanan sukarela sudah berjalan, rumus pembagian akan berubah menjadi proporsional terhadap besaran simpanan sukarela masing-masing anggota (**rumus detail: TBD**).
- Pembagian dilakukan di **akhir tahun**.
- SHU **otomatis dipindahkan ke saldo** anggota, dan anggota bisa mengklaim/mencairkan per tahun.

**Fitur yang dibutuhkan:**
- Kalkulasi otomatis SHU per anggota berdasar laba bersih tahun berjalan
- Halaman "Klaim SHU" untuk anggota
- Riwayat SHU per tahun per anggota
- Laporan SHU untuk admin/bendahara (total dibagikan, sisa, dsb)

## 5. Modul Bank Sampah

### 5.1 Kategori & Harga

| Kategori | Harga |
|---|---|
| Sampah campur | Rp300/kg |
| Organik | Rp500/kg |
| Anorganik | Bervariasi per sub-kategori, per kg atau per biji — lihat §5.1.1 |

- Harga anorganik **bisa berubah-ubah harian**, mengikuti info dari grup WA paguyuban pengepul sampah.
- Yang berhak mengubah harga di sistem: **bendahara/operator**, lewat sisi admin.
- Ada **papan info harga** yang menampilkan harga sampah anorganik hari itu (bisa dilihat semua anggota).
- Saldo yang didapat anggota dari setoran mengikuti harga yang berlaku **pada hari penyetoran**.
- **Skema potongan koperasi:** saat anggota menjual sampah ke pengepul, harga jual dipotong **20% sebagai biaya admin**, dan potongan ini masuk sebagai pendapatan koperasi. Dana inilah yang menjadi sumber pool 20% SHU di §4 — jadi kedua angka 20% ini **saling terkait** (satu adalah mekanisme pemotongan di transaksi, satu lagi adalah mekanisme pembagian pool tahunan ke anggota).

### 5.1.1 Lampiran Daftar Harga Anorganik (Referensi: UD Sapu Jagad Ponorogo, update 23 April 2026)

Catatan penting yang harus direfleksikan di desain sistem:
- Harga di bawah adalah **harga diantar ke gudang**. Untuk pengambilan di rumah/alamat, ada selisih: **−Rp300/kg** untuk kategori non-logam, **−Rp2.000/kg** untuk kategori Logam.
- Sebagian item dihargai **per kg**, sebagian lagi **per biji/unit** (misal elektronik utuh) — sistem perlu mendukung dua satuan ini.
- Harga bersifat dinamis ("sewaktu-waktu bisa berubah"), sehingga tabel ini adalah **data awal/seed**, bukan harga tetap — pembaruan tetap lewat fitur manajemen harga di §5.3.

**Logam**
| Item | Harga/kg |
|---|---|
| Tembaga | Rp130.000 |
| Tembaga Dandang | Rp98.000 |
| Tembaga Radiator | Rp49.000 |
| Kuningan | Rp73.000 |
| Aki | Rp10.000 |
| Alumunium campur | Rp18.000 |
| Elemen | Rp10.000 |
| Diral | Rp12.000 |
| Kampas kotor | Rp10.000 |

**Besi**
| Item | Harga/kg |
|---|---|
| Besi | Rp4.300 |
| Kaleng | Rp2.500 |
| Pipa/Kompor | Rp2.500 |
| Drum | Rp2.500 |
| Seng | Rp1.200 |
| Kawat | Rp300 |

**Kertas**
| Item | Harga/kg |
|---|---|
| Kardus | Rp1.800 |
| Sak Semen | Rp4.500 |
| Sak Bima | Rp2.000 |
| Duplek | Rp700 |
| Buku campur | Rp1.000 |
| Buku Mangkak (coklat) | Rp1.300 |
| HVS bersih | Rp2.000 |
| Buram bersih | Rp1.300 |

**Atom / Plastik**
| Item | Harga/kg |
|---|---|
| Atom C | Rp1.200 |
| Aqua bersih | Rp5.000 |
| Aqua kotor | Rp2.200 |
| Putihan/Oli | Rp3.200 |
| Warna | Rp1.800 |
| PET bersih | Rp2.000 |
| PET kotor | Rp1.500 |
| PET warna (biru/hijau) | Rp1.000 |
| Tutup PET | Rp3.500 |
| Tutup galon | Rp4.000 |
| PS Kaca | Rp3.000 |
| Bok TV & motor | Rp1.000 |
| Sak/karung | Rp500 |

**Lain-lain**
| Item | Harga | Satuan |
|---|---|---|
| Kerasan | Rp300 | /kg |
| Gembos | Rp400 | /kg |
| Batok kelapa | Rp500 | /kg |
| Magic com | Rp6.000 | /kg |
| PCB | Rp4.000 | /kg |
| CPU utuh/komplit | Rp50.000 | /biji |
| Monitor tabung | Rp25.000 | /biji |
| HP Cina/Android | Rp2.000 | /biji |
| HP Nokia | Rp4.000 | /biji |
| TV tabung 14" utuh | Rp10.000 | /biji |
| TV tabung 21" utuh | Rp20.000 | /biji |
| Laptop utuh | Rp30.000 | /biji |
| Pompa air Shimizu utuh | Rp60.000–90.000 | /biji |
| Mesin cuci utuh/komplit | Rp100.000–200.000 | /biji |
| Kulkas utuh/komplit | Rp100.000–250.000 | /biji |
| Galon utuh | Rp3.000 | /biji |
| Galon pecah | Rp2.000 | /biji |
| Galon Le Mineral | Rp2.000 | /kg |

*Catatan: sistem sebaiknya menyediakan field range harga (min–max) untuk item seperti pompa air/mesin cuci/kulkas yang harganya berupa rentang, bukan angka tunggal — bendahara/operator menentukan harga final saat transaksi.*

### 5.2 Alur Setor & Timbang

Penimbangan **tidak hanya di gudang/kantor koperasi** — pengepul juga bisa **mendatangi rumah anggota** atau menimbang di **pasar**. Sistem input timbang perlu bisa dipakai dari lapangan (mobile-friendly), bukan hanya dari satu lokasi tetap.

1. Anggota menyetor sampah — bisa datang langsung, atau pengepul yang menjemput ke rumah/pasar.
2. **Pengepul** menimbang di lokasi (gudang/rumah/pasar) dan input di sistem: nama anggota, jenis sampah, berat (kg), serta lokasi penimbangan (untuk menentukan harga diantar vs dijemput sesuai §5.1.1).
3. Sistem otomatis menghitung nilai rupiah berdasarkan harga hari itu, dan menambahkannya ke **saldo anggota — langsung cair**, tanpa perlu menunggu 3/6 bulan.
4. **Anggota** bisa langsung melihat hasil input tadi dalam bentuk **nota digital** (real-time).
5. Jika anggota merasa data tidak sesuai (jenis/berat salah), anggota bisa mengajukan **komplain** melalui sistem.

### 5.3 Fitur yang dibutuhkan
- Form input timbang (sisi pengepul): pilih anggota, pilih kategori sampah, input berat
- Riwayat nota per anggota (tanggal, kategori, berat, harga saat itu, nilai rupiah)
- Fitur komplain: anggota ajukan → masuk antrian ke admin/bendahara → status (diajukan/diproses/selesai)
- Manajemen harga: CRUD kategori sampah anorganik + harga, dengan riwayat perubahan harga (siapa ubah, kapan)
- Papan info harga (halaman publik/dashboard anggota)

## 6. Modul Saldo & Pencairan

- Saldo anggota terisi dari: hasil setor sampah (langsung cair) + SHU (dipindahkan otomatis akhir tahun).
- Metode pencairan saldo ke uang tunai/rekening: **belum ditentukan** — disarankan sistem dibuat fleksibel (mendukung beberapa metode: transfer manual, e-wallet, atau tunai di koperasi), diputuskan kemudian.

## 7. Non-Functional Requirements

| Aspek | Kebutuhan |
|---|---|
| Platform | Web app, mendukung semua 4 role (MVP) |
| Akses | Role-based access control (Admin/Bendahara/Pengepul/Anggota) |
| Audit trail | Wajib untuk perubahan harga & transaksi keuangan |
| Perangkat | Harus responsif, karena pengepul kemungkinan input dari lapangan (HP) |

## 8. Asumsi & Pertanyaan Terbuka

- [x] ~~Daftar lengkap kategori & harga sampah anorganik~~ — sudah dilampirkan di §5.1.1
- [x] ~~Alur setor & timbang di mana saja~~ — dikonfirmasi bisa di gudang, rumah anggota, atau pasar (§5.2)
- [x] ~~Rumus SHU~~ — dikonfirmasi sementara: 20% dari laba koperasi dibagi rata ke semua anggota (§4); rumus final untuk skema simpanan sukarela masih TBD
- [x] ~~Kaitan potongan 20% harga jual vs 20% SHU~~ — dikonfirmasi saling terkait: potongan 20% saat transaksi jadi sumber dana pool SHU (§5.1)
- [ ] Rumus final pembagian SHU proporsional saat simpanan sukarela sudah aktif (TBD)
- [ ] Metode pencairan saldo/SHU ke uang riil — transfer/e-wallet/tunai (TBD)
- [ ] Apakah pengepul bisa lebih dari satu orang/wilayah? Perlu pembagian wilayah kerja?
- [ ] Batas waktu/SLA penyelesaian komplain

## 9. Cakupan MVP (Usulan)
- Registrasi anggota + simpanan pokok
- Pencatatan simpanan wajib & tipping fee bulanan
- Input timbang sampah oleh pengepul (kategori campur, organik, anorganik dasar)
- Nota digital untuk anggota + fitur komplain
- Papan info harga (input oleh bendahara/operator)
- Saldo anggota (hasil sampah, real-time)
- Perhitungan SHU tahunan (rumus rata, tanpa simpanan sukarela) + klaim SHU

Fitur simpanan sukarela dengan rumus proporsional bisa masuk fase berikutnya, setelah rumus finalnya diputuskan.
