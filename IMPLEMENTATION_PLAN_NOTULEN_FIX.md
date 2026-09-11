# Implementation Plan — Perbaikan 6 Issue Notulen

Pengerjaan: BE = `Penelitian PPPGS/` (Laravel), FE = `Koperasi-Jasa-Mulyo-Raharjo-Lestari-FE/`.
Satu akar bersama: **tabel `finance_categories` di DB live (kjps-mural) di-seed sebelum baris kategori sistem ditambahkan ke `DatabaseSeeder`** — ini yang meledakkan issue #3 dan #6 sekaligus.

---

## Issue 6 — SQL 1048 `category_id` cannot be null saat timbang sampah (KERJAKAN PERTAMA)

**Akar:** `TransactionRepository::findCategoryIdByName('Beli Sampah Anggota')` return `null` karena baris itu tidak ada di DB live (seeder sudah punya, DB belum di-reseed) → `Transaction::create` insert `category_id = null` → MySQL tolak (kolom NOT NULL, lihat migrasi `2026_09_09_000015`).

**Fix:**
  1. **Data (sekali):** buat `DatabaseSeeder` idempotent — ganti `FinanceCategory::insert([...])` menjadi loop `FinanceCategory::firstOrCreate(['name' => ...], ['type' => ..., 'group_type' => ...])` (tabel tidak punya unique index di `name`, jadi `insertOrIgnore` tidak bisa dipakai). Lalu jalankan `php artisan db:seed --force` di DB kjps-mural.
1. **Guard (root, mencegah SQL 1048 berubah jadi pesan jelas):** di `app/Repositories/Eloquent/TransactionRepository.php::findCategoryIdByName()` — throw `BusinessLogicException("Kategori jurnal kas '{name}' tidak ditemukan — jalankan ulang database seeder.")` bila null. Semua 8 caller service (weighing, SHU, savings, wallet, complaint) langsung terlindungi tanpa diubah.
2. **Test:** tambahkan `'Beli Sampah Anggota'` ke `ApiTestCase::seedCore()` (baris ini hilang — jurnal expense timbang tidak pernah ketest) + satu assert di `TrashWeighingTest` bahwa transaksi expense "Beli sampah anggota" tercatat dengan `category_id` tidak null.

## Issue 3 — Button SHU belum work

**Akar (hipotesis kuat, satu rantai dengan #6):** `ShuCalculationEngine::publishDistribution()` → `findCategoryIdByName('Distribusi SHU Anggota')` → null di DB live → insert null → 500. Draft `shu_distributions` tertinggal → percobaan berikutnya ditolak `BusinessLogicException("Draft/periode SHU tahun 2026 sudah ada")` → tombol terlihat "tidak work".

**Fix:**
1. Ikut tertolong oleh fix #6 (kategori terisi + guard).
2. **Cleanup data (sekali):** hapus baris `shu_distributions` tahun berjalan yang berstatus `draft` (beserta `shu_members` ikut cascade) supaya alur "Distribusi Baru" bisa dipakai lagi.
3. Verifikasi manual: buka Pengurus → SHU → Distribusi Baru → submit → periode muncul `dibagikan`, expense "Distribusi SHU Anggota" masuk jurnal kas.
4. untuk pembagian SHU tambahhkan dual safety jika ada draft maka alurnya buat baru => mengisi draft tahuhn sekarang & otomatis get data penerima dari count role = anggota. lalu otomatis tertambah (draft). dan jika ingin menambah kembali terdapat alert "Pembagian SHU tahun berjalan sudah ada". sedangkan yang draft di sebelah kanannya ada tombol action untuk info, lalu ada form lagi untuk pengisian, dan simpan. serta ada button lagi untuk pembagian. jika button untuk pembagian maka otomatis terbagi rata ke seluruh anggota

## Issue 1 — Petugas di transaksi harusnya yang login, bukan yang didaftarkan

**Akar:** `AuthService::registerMember()` memanggil `recordInitialPokok($member, $user)` dengan `$user` = **user anggota yang baru didaftarkan** (`AuthService.php:83`), bukan pengurus yang login. Jurnal income "Simpanan pokok anggota" yang muncul di page pendapatan jadi `handled_by` = si anggota. (Transaksi manual & timbang sudah benar — `TransactionService:28` dan `TrashWeighingService:136,150` pakai `$request->user()`; tidak disentuh.)

**Fix:**
1. `AuthService::registerMember(array $data, string $status = 'nonaktif', ?User $creator = null)` → `recordInitialPokok($member, $creator)`.
2. `UserService::createUser(array $data, User $actor)` terima actor; `UserController::store()` pass `$request->user()`.
3. Self-register (`AuthController::registerMember`) pass `null` → fallback `$member->user_id` sudah ada di `SavingsService:314`.

## Issue 4 — Setelah add, form masih pakai state lama

**Akar:** `FormPopup` input uncontrolled (`defaultValue`) dan komponen **tidak pernah unmount** — saat `kind = null` dia hanya `return null` tapi instance tetap hidup, state React & nilai input DOM menetap. Buka form kedua kali → nilai submit sebelumnya masih ada.

**Fix (1 baris per pemakaian):** `<FormPopup key={form} ... />` di `Pengurus/Index.jsx` dan `Anggota/Index.jsx` ( semua tempat `FormPopup` dirender). Perubahan key `null` → `'income'` memaksa remount → semua state & input reset.

## Issue 2 — Riwayat harga di Harga Sampah belum kedeteksi

**Akar ganda:**
1. `PriceHistoryResource` **tidak expose** `old_price_sell`/`new_price_sell` → kolom "Harga Jual (lama → baru)" di `HistoryModal` selalu `-`.
2. Edit kategori via `PUT /trash-categories/{id}` (form Edit → `TrashCategoryRepository::update()`) **tidak menulis `PriceChangeLog`** — hanya `PATCH /price` yang log. Ubah harga lewat form Edit tidak pernah tercatat.

**Fix:**
1. Tambah `'old_price_sell'`, `'new_price_sell'` ke `PriceHistoryResource` (2 baris).
2. `TrashCategoryRepository::update()`: bila `price_unsorted`/`price_sell` ada di `$data` dan berbeda dari nilai tersimpan, tulis `PriceChangeLog` (pola sama dengan `updatePriceWithAudit`) sebelum update.

## Issue 5 — Alamat penjemputan otomatis dari `members.address`

**Akar:** anggota yang dibuat lewat Manajemen Pengguna (`Users.jsx` → `POST /users`, role anggota) hanya mengirim `address_rumah`/`address_pasar`; `registerMember` menyimpan `member.address = $data['address'] ?? null` → kosong. View yang pakai alamat umum (kolom Alamat di `PickupMonitor`, `WeighingForm`) tampil `-`. Fallback resource pakai `??` yang tidak menangkap string kosong.

**Fix:**
1. `AuthService::registerMember()`: `'address' => $data['address'] ?? $data['address_rumah'] ?? $data['address_pasar'] ?? null` (1 baris — alamat umum selalu terisi).
2. `PickupResource` + `MemberResource`: fallback `address_rumah/address_pasar` ganti `??` menjadi `?:` supaya string kosong juga fallback ke `address`.

---

## Urutan eksekusi

| # | Langkah | File |
|---|---------|------|
| 1 | Seeder idempotent + reseed DB live + guard kategori | `DatabaseSeeder.php`, `TransactionRepository.php` |
| 2 | Cleanup draft SHU rusak | data (tinker/SQL) |
| 3 | Fix petugas = yang login | `AuthService.php`, `UserService.php`, `UserController.php` |
| 4 | Fix form reset | `Pengurus/Index.jsx`, `Anggota/Index.jsx` |
| 5 | Fix riwayat harga | `PriceHistoryResource.php`, `TrashCategoryRepository.php` |
| 6 | Fix alamat fallback | `AuthService.php`, `PickupResource.php`, `MemberResource.php` |
| 7 | Test: `php artisan test` + tambah seed 'Beli Sampah Anggota' & assert expense | `ApiTestCase.php`, `TrashWeighingTest.php` |

**Verifikasi akhir:** timbang sampah via form petugas (jurnal fee+beli sampah tercatat, petugas = yang login), input pendapatan manual 2x berturut (form kosong kembali), edit harga lewat form Edit lalu buka Riwayat (lama → baru tampil termasuk harga jual), Distribusi SHU end-to-end, tiket jemput menampilkan alamat member.
