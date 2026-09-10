# PROGRESS PELAKSANAAN IMPLEMENTATION PLAN BACKEND
## Sistem Informasi Koperasi & Bank Sampah "Koperasi Jasa Mulyo Raharjo Lestari"
**Referensi:** [implementation-plan-backend.md](file:///c:/laragon/www/Koperasi-Jasa-Mulyo-Raharjo-Lestari/implementation-plan-backend.md) & [be-architecture.md](file:///c:/laragon/www/Koperasi-Jasa-Mulyo-Raharjo-Lestari/be-architecture.md)  
**Terakhir Diperbarui:** 6 September 2026 (MODUL 1–12 tuntas; test suite PHPUnit 24/24 hijau)  

---

## 📊 Ringkasan Status Keseluruhan

| Modul | Status | Keterangan Singkat |
|---|:---:|---|
| **Fondasi & Environment Database** | ✅ **SELESAI** | MySQL 127.0.0.1 (`koperasi_mulyo_raharjo`), migrasi 15 tabel + seed data awal berhasil |
| **MODUL 0: Exception Handler & Provider** | ✅ **SELESAI** | Global exception handler (JSON 401/404/405/422/500), custom `BusinessLogicException`, CORS config, dan `RepositoryServiceProvider` (binding User & Member repository) selesai. |
| **MODUL 1: Autentikasi & Akun (`/auth`)** | ✅ **SELESAI** | 6 endpoint Auth aktif: login (identity email/username + Sanctum token), register-member (auto `member_code` MBR-YYYYMM-XXXX, status nonaktif menunggu verifikasi), me, profile, change-password, logout. |
| **MODUL 2: Manajemen User (`/users`)** | ✅ **SELESAI** | CRUD user internal + middleware role `EnsureUserHasRole` (alias `role:`) + handler 403 JSON. Terverifikasi smoke test: 200/201/400/401/403/404/405/422 semua benar. |
| **MODUL 3: Keanggotaan (`/members`)** | ✅ **SELESAI** | CRUD anggota + kategori, auto `member_code` (`MBR-YYYYMM-XXXX`), PATCH status, anggota hanya bisa lihat profil sendiri (403 untuk milik orang lain). Terverifikasi smoke test. |
| **MODUL 4: Katalog Sampah (`/trash-categories`)** | ✅ **SELESAI** | Katalog publik + papan harga (jemput vs gudang, diskon logam Rp2.000/non-logam Rp300, trend naik/turun), PATCH harga dengan audit trail `price_change_logs`. Terverifikasi smoke test. |
| **MODUL 5: Timbang & Nota (`/pickups`)** | ✅ **SELESAI** | Engine fee 20% & jurnal kas instan, nota digital, rollback saat batal. Smoke test: gross 256.000 → fee 51.200 → net 204.800, cancel → jurnal `gagal`. |
| **MODUL 6: Komplain Nota (`/complaints`)** | ✅ **SELESAI** | Submit (batas 3x24 jam, dedup, own-check), resolve diterima → jurnal penyesuaian expense. Smoke test lulus. |
| **MODUL 7: Simpanan & Kas (`/savings`)** | ✅ **SELESAI** | Setor simpanan (POKOK/WAJIB/TIPPING/SUKARELA) + jurnal otomatis, billing bulanan Rp45.000 idempoten, status tagihan. Smoke test lulus. |
| **MODUL 8: Dompet & Cashout (`/wallet`)** | ✅ **SELESAI** | Saldo turunan (pickup + komplain + SHU − penarikan), tarik tunai/transfer, approval pengurus + jurnal expense. Smoke test lulus. |
| **MODUL 9: Mesin SHU (`/shu`)** | ✅ **SELESAI** | Simulasi pool 20% laba bersih, split 50/50 jasa modal vs partisipasi, draft → publish terkunci, jurnal per anggota. Smoke test lulus. |
| **MODUL 10: Logistik (`/logistics/routes`)** | ✅ **SELESAI** | Catat ritase armada per desa/RW/RT + tonase, filter laporan rute. Smoke test lulus. |
| **MODUL 11: Laporan (`/reports`)** | ✅ **SELESAI** | Dashboard stats, laporan kas masuk/keluar per kategori, rekap tonase, export CSV. Smoke test lulus. |
| **MODUL 12: Route Assembly & Testing** | ✅ **SELESAI** | 54 route terdaftar di `routes/api.php`; PHPUnit 24 test / 73 assertion hijau. |

---

## 📝 Audit Checklist Rinci Berdasarkan `implementation-plan-backend.md`

### Fondasi Database & Setup Lingkungan (Selesai Dieksekusi)
- [x] **Setup MySQL 127.0.0.1**: Database `koperasi_mulyo_raharjo` dibuat dan dikoneksikan ke `.env`.
- [x] **Generate Application Key**: `php artisan key:generate` sukses dieksekusi.
- [x] **Eksekusi 15 Tabel Migrasi Utama**:
  - `users` (dengan HasApiTokens Sanctum)
  - `member_categories`
  - `members`
  - `trash_categories` (ditambah kolom `type` dan `unit`)
  - `price_change_logs` (tabel baru untuk audit perubahan harga harian)
  - `finance_categories`
  - `setoran_koperasi`
  - `detail_pengangkutan`
  - `pickups`
  - `pickup_items`
  - `complaints` (tabel baru untuk aduan nota timbangan anggota)
  - `transactions`
  - `shu_distributions`
  - `shu_members`
  - `reports`
  - `personal_access_tokens` (Laravel Sanctum API tokens)
- [x] **Database Seeders**: Seeder akun default (Ketua, Bendahara, Petugas, Warga), Kategori Anggota, Akun Kas, serta 22 item Katalog Sampah Ponorogo berhasil di-seed (`php artisan db:seed`).
- [x] **Eloquent Models Lengkap**: 15 Model (`User`, `Member`, `TrashCategory`, `PriceChangeLog`, `Complaint`, `Pickup`, `Transaction`, dll) siap digunakan.

---

### MODUL 0: Core Setup, Exception Handler & Repository Provider
- [x] **Task 0.1**: Global Exception Handler terpusat di `bootstrap/app.php` untuk return format JSON standar saat error validasi (422), route/data tidak ditemukan (404), unauthenticated (401), method not allowed (405), dan server error (500).
- [x] **Task 0.2**: Custom Exception `app/Exceptions/BusinessLogicException.php` dengan HTTP status 400 auto-render response.
- [x] **Task 0.3**: `app/Providers/RepositoryServiceProvider.php` mendaftarkan binding `UserRepositoryInterface` & `MemberRepositoryInterface` ke implementasi Eloquent (terdaftar di `bootstrap/providers.php`; binding repository modul lain menyusul saat modulnya dikerjakan).
- [x] **Task 0.4**: Konfigurasi CORS `config/cors.php` untuk domain frontend React (`localhost:5173`).

---

### MODUL 1: Autentikasi & Akun Profil (`/api/v1/auth`) — SELESAI
- [x] **Task 1.1: Form Requests** — `LoginRequest` (identity/password/device_name), `RegisterMemberRequest` (name/username/email/password/phone/address/nik opsional), `UpdateProfileRequest`, `ChangePasswordRequest`. Catatan: `LoginRequest` Breeze lama (berbasis email+session) di-overwrite menjadi versi API identity-based.
- [x] **Task 1.2: Repository Layer** — `UserRepositoryInterface`/`UserRepository` (`findByEmailOrUsername`, `create`, `update`, `findWithMember`) & `MemberRepositoryInterface`/`MemberRepository` (`paginate`, `findById`, `create`, `update`, `generateMemberCode` format `MBR-YYYYMM-XXXX`, `getDefaultCategoryId`).
- [x] **Task 1.3: AuthService** — `authenticate` (Hash check + Sanctum token, kredensial salah → 422), `registerMember` (`DB::transaction` insert `users` role anggota + `members` status `nonaktif` menunggu verifikasi), `updateProfile`, `changePassword` (password saat ini salah → `BusinessLogicException` 400), `logout` (revoke current token).
- [x] **Task 1.4: API Resources** — `UserResource`, `AuthTokenResource`, `MemberResource`, `MemberCategoryResource` (Resource Member & Kategori dikerjakan lebih awal karena dibutuhkan respons `/auth/me` & register).
- [x] **Task 1.5: Controller & Routes** — `AuthController` tipis (delegasi penuh ke service, respons JSON standar `{success, message, data}`); route terdaftar di `routes/api.php` prefix `v1/auth` + middleware `auth:sanctum` & throttle pada endpoint publik.
- [x] **Migrasi tambahan**: kolom `phone` (nullable) di tabel `users` — dipakai untuk update profil kontak pengguna (sebelumnya hanya ada di `members`).

---

### MODUL 3: Master Data Keanggotaan (`/api/v1/members` & `/api/v1/member-categories`) — SELESAI
- [x] **Task 3.1: Form Requests** — `StoreMemberCategoryRequest`, `StoreMemberRequest`, `UpdateMemberRequest`, `UpdateMemberStatusRequest` (enum aktif/nonaktif/suspend; catatan: kolom `notes` belum ada di tabel members — alasan status tidak dipersist).
- [x] **Task 3.2: Repository** — `MemberRepository` sudah ada dari Modul 1, ditambah `listCategories()` & `createCategory()` (kategori di-domain-kan ke repo member, tanpa repo baru).
- [x] **Task 3.3: MemberService** — `createMember` (auto `member_code` + default join_date/status), `updateStatus`, CRUD lainnya.
- [x] **Task 3.4: Resources** — `MemberResource` & `MemberCategoryResource` (dibuat saat Modul 1).
- [x] **Task 3.5: Controllers & Routes** — `MemberController` (own-check anggota di `show`), `MemberCategoryController`; route `/members` & `/member-categories` dengan proteksi role.
- [x] **Verifikasi smoke test**: kategori list/create 200/201, member create auto-code `MBR-202609-0003` + status default `aktif`, PATCH status → `suspend`, PUT update 200, anggota akses profil sendiri 200 / milik lain 403 / list 403.

---

### MODUL 4: Katalog Sampah & Manajemen Harga (`/api/v1/trash-categories`) — SELESAI
- [x] **Task 4.1: Form Requests** — `StoreTrashCategoryRequest`, `UpdateTrashCategoryRequest` (extend store, unique name ignore self), `UpdateTrashPriceRequest`.
- [x] **Task 4.2: Repository** — `TrashCategoryRepositoryInterface`/`TrashCategoryRepository` (`getAll` filter type/is_active, `getPriceBoardData`, `updatePriceWithAudit` transaksi, `priceHistory`), binding terdaftar di `RepositoryServiceProvider`.
- [x] **Task 4.3: TrashCategoryService** — `getPriceBoard` (diskon jemput: logam Rp2.000, non-logam Rp300; trend dari log terakhir), `updateDailyPrice` (update + audit log dalam satu transaksi).
- [x] **Task 4.4: Resources** — `TrashCategoryResource`, `PriceHistoryResource`. Catatan: `TrashPriceBoardResource` diwakili array terstruktur langsung dari service (data hasil kalkulasi, bukan model) — tidak dibuat kelas resource terpisah.
- [x] **Task 4.5: Controller & Routes** — `TrashCategoryController`; `GET /` & `GET /board` publik, mutasi kategori & harga ketua/pengurus (bendahara = role pengurus), history ketua/pengurus.
- [x] **Verifikasi smoke test**: list publik filter type (6 logam), board 22 item — Tembaga pickup `125.000-2.000=123.000`, Kardus `1.500-300=1.200`; PATCH harga oleh bendahara → 200 + log audit (old 125.000 → new 126.000, oleh Bendahara); history 1 entri; board trend `naik` setelah update (bug `unique()` tanpa `keyBy` ditemukan & diperbaiki); create kategori 201; petugas create → 403.

---

### MODUL 5: Operasional Bank Sampah: Penjemputan & Timbang (`/api/v1/pickups`) — SELESAI
- [x] **Task 5.1**: `CreatePickupTicketRequest`, `SubmitWeighItemsRequest`, `CancelPickupRequest` (validasi reason, menggantikan `$request->validate()` inline yang melanggar aturan arsitektur).
- [x] **Task 5.2**: `PickupRepositoryInterface`/`PickupRepository` (`paginate`, `findByIdWithDetails`, `createHeader`, `addItemsAndComplete`, `cancel`), binding terdaftar.
- [x] **Task 5.3**: `TrashWeighingService` — engine `weighAndComplete`: nilai bruto per item (tarif hari ini, jemput vs gudang via `pickupPrice`), fee 20%, net 80%, `DB::transaction()` → simpan `pickup_items` + jurnal income fee di `transactions` + status `selesai`. `cancelPickup`: rollback jurnal (status `gagal`) + status `batal`. Lock `lockForUpdate` mencegah timbang ganda/double-cancel.
- [x] **Task 5.4**: `PickupResource`, `ReceiptResource` (nota digital: rincian item, fee 20%, net, verify URL).
- [x] **Task 5.5**: `PickupController` + route `/pickups` lengkap (anggota own-check di show/receipt/store).
- [x] **Verifikasi** (`php smoke-modul5.php`): tiket → timbang 2kg Tembaga + 4kg Kardus = gross 256.000, fee 51.200, net 204.800; timbang ulang ditolak; cancel → jurnal fee jadi `gagal` + status `batal`.

### MODUL 6: Pengaduan & Komplain Transaksi (`/api/v1/complaints`) — SELESAI
- [x] **Task 6.1**: `SubmitComplaintRequest` (pickup_id/issue_type/description/proof_image), `ResolveComplaintRequest` (status diterima/ditolak, adjustment_amount, resolution_note).
- [x] **Task 6.2**: `ComplaintRepositoryInterface`/`ComplaintRepository`, binding terdaftar.
- [x] **Task 6.3**: `ComplaintService` — `submitComplaint` (nota wajib `selesai`, batas 3x24 jam dari `completed_at`, dedup komplain aktif, own-check anggota), `resolveComplaint` (diterima + amount > 0 → `DB::transaction()` jurnal penyesuaian expense `tunai`).
- [x] **Task 6.4**: `ComplaintResource` (pickup, member, resolver, adjustment, resolusi).
- [x] **Task 6.5**: `ComplaintController` + route `/complaints` (submit semua role dengan own-check, resolve ketua/pengurus; upload bukti ke disk `public`).
- [x] **Verifikasi** (`php smoke-modul6.php`): submit `diajukan`; komplain ganda / nota pending / lewat 72 jam ditolak; resolve diterima 80.000 → jurnal expense tercatat; resolve ganda ditolak.

### MODUL 7: Simpanan & Kas Umum (`/api/v1/savings`) — SELESAI
- [x] **Task 7.1**: `PaySavingsRequest` (member_id/label/jumlah/metode/catatan); migrasi tambahan `WAJIB` di enum `setoran_koperasi.label`.
- [x] **Task 7.2**: `SetoranKoperasiRepositoryInterface`/`SetoranKoperasiRepository` (`paginate`, `findById`, `create`, `updateStatus`, `sumSettledByLabel`), binding terdaftar.
- [x] **Task 7.3**: `SavingsService` — `recordSavingsPayment` (`DB::transaction` insert setoran SELESAI + jurnal kas sesuai label via peta kategori); `generateMonthlyBilling` (idempoten: WAJIB Rp5.000 + TIPPING Rp40.000 per anggota aktif per bulan, status PENDING); `getBillingStatus` (prioritas baris SELESAI di atas PENDING).
- [x] **Task 7.4**: `SavingsResource`, `TransactionService` + `TransactionResource`/`FinanceCategoryResource` (mutasi kas dengan filter type/member/tanggal via `TransactionRepository::paginate`).
- [x] **Task 7.5**: `SavingsController`/`TransactionController`/`FinanceCategoryController` + route `/savings`, `/transactions`, `/finance-categories`.
- [x] **Verifikasi** (`php smoke-modul7.php` + `SavingsBillingTest`): setor POKOK → jurnal income; billing 45.000 split WAJIB/TIPPING; generate ulang idempoten; billing-status own-check anggota.

### MODUL 8: Dompet & Cashout (`/api/v1/wallet`) — SELESAI
- [x] **Task 8.1**: Migrasi `withdraw_requests` (method tunai/transfer + data bank, status pending/disetujui/ditolak, processed_by/processed_at) + model `WithdrawRequest` + scope `pending`.
- [x] **Task 8.2**: `WalletService` — saldo turunan dari mutasi (pickup selesai + komplain diterima + SHU sudah_dibagikan − penarikan disetujui); `requestWithdraw` cek saldo tersedia; `processWithdrawApproval` `lockForUpdate`, tolak double-process, approve → jurnal expense. *ponytail: saldo dihitung O(mutasi) per anggota — pindah ke kolom saldo jika volume transaksi membesar.*
- [x] **Task 8.3**: `WithdrawRequestForm`/`ApproveWithdrawRequest` (transfer wajib data bank), `WalletMutationResource`/`WithdrawRequestResource` (nomor rekening dimasking).
- [x] **Task 8.4**: `WalletController` (resolveMemberId: anggota = milik sendiri, pengurus wajib `?member_id=`) + route `/wallet`.
- [x] **Verifikasi** (`php smoke-modul8.php`): saldo, tarik > saldo ditolak, approve → saldo berkurang + jurnal, tolak penarikan, penarikan pending tidak mengurangi saldo tersedia.

### MODUL 9: Mesin SHU (`/api/v1/shu`) — SELESAI
- [x] **Task 9.1**: `ShuCalculationEngine::simulateDistribution` — pool `shu_pool_percentage` (default 20%) dari laba bersih, split 50/50 jasa modal (POKOK/WAJIB/SUKARELA SELESAI) vs jasa partisipasi (tonase pickup selesai). *ponytail: rasio 50/50 asumsi bisnis — konfirmasi AD/ART koperasi.*
- [x] **Task 9.2**: `saveDraft` (ShuDistribution `menunggu` + rows ShuMember) & `publishDistribution` (`lockForUpdate`, tolak double-publish, jurnal expense per anggota, status `sudah_dibagikan`).
- [x] **Task 9.3**: `ShuDistributionRepositoryInterface`/`ShuRepository`, binding terdaftar; `getMemberHistory`, `getPeriods`.
- [x] **Task 9.4**: `SimulateShuRequest`/`PublishShuRequest`, `ShuPeriodResource`, `ShuController` (simulate `?save=1` persist draft).
- [x] **Task 9.5**: Route `/shu` (publish ketua saja; my-history anggota).
- [x] **Verifikasi** (`php smoke-modul9.php` + `ShuDistributionTest`): pool 200rb dari laba 1jt, publish → saldo anggota naik + jurnal expense, double-publish 400, non-ketua 403.

### MODUL 10: Logistik (`/api/v1/logistics/routes`) — SELESAI
- [x] **Task 10.1**: `StoreLogisticsRouteRequest` (desa/rw/rt/pic/vehicle/ritase weight).
- [x] **Task 10.2**: `DetailPengangkutanRepositoryInterface`/`DetailPengangkutanRepository` (`paginate` filter desa/rw/rt/tanggal, `create`), binding terdaftar.
- [x] **Task 10.3**: `LogisticsRouteResource` + `LogisticsController` (store petugas+; index semua role operasional).
- [x] **Task 10.4**: Route `/logistics/routes`.
- [x] **Verifikasi** (`php smoke-modul1011.php`): catat ritase 201, filter per desa, validasi rt/rw.

### MODUL 11: Laporan & Export (`/api/v1/reports`) — SELESAI
- [x] **Task 11.1**: `ReportService` — `getDashboardStats` (total kas, saldo mengendap, tonase bulan ini, anggota aktif), `getFinancialReport` (income/expense per kategori).
- [x] **Task 11.2**: `getTrashVolumeReport` (per jenis sampah & per desa/rw dari detail pengangkutan), `exportReport` (hanya data finalized; flatten list/map/skalar).
- [x] **Task 11.3**: `ReportController` + route `/reports` (dashboard/financial/trash-volume/export; export streaming CSV stdlib fputcsv).
- [x] **Verifikasi** (`php smoke-modul1011.php`): dashboard, financial, tonase, export CSV. *ponytail: export CSV via stdlib — tambahkan PhpSpreadsheet/dompdf saat butuh XLSX/PDF asli.*

### MODUL 12: Route Assembly & Test Suite — SELESAI
- [x] **Task 12.1**: Seluruh route modul 1–11 terdaftar di `routes/api.php` (54 route, prefix `api/v1`, proteksi `auth:sanctum` + `role:` per modul).
- [x] **Task 12.2**: Test suite PHPUnit `tests/Feature/Api/` — `ApiTestCase` (seed finance categories + kategori sampah, RefreshDatabase sqlite `:memory:`), 24 test / 73 assertion hijau: `AuthTest` (login/422/401/me/logout-revoke — guard di-reset via `Auth::forgetGuards()` antar request karena user guard di-cache per app instance dalam test), `TrashWeighingTest` (fee 20%, diskon jemput, timbang ganda 400, anggota 403), `SavingsBillingTest`, `ShuDistributionTest`, `GlobalExceptionHandlerTest` (401/403/404/405/422/400).
- [x] **Catatan**: PHPUnit 12 memerlukan attribute `#[Test]`, bukan anotasi `/** @test */`. Smoke script `smoke-modul*.php` sudah dihapus — coverage penuh di PHPUnit; data uji manual tinggal `php artisan db:seed` (DatabaseSeeder).

---

## 🔁 REVISI (revisi.md) — 10 September 2026

**Audit jawaban 3 pertanyaan revisi (BE hidup = folder `backend/` ini; folder root monorepo adalah salinan basi):**

1. **Registrasi member FE↔BE: BE SUDAH, FE KURANG.** `POST /api/v1/auth/register-member` sudah ada (`routes/api.php` throttle 5/menit → `AuthController::registerMember` → `AuthService::registerMember`, status nonaktif menunggu verifikasi). Namun form FE `Register.jsx` hanya mengirim name/email/password — field wajib BE `username`/`phone`/`address`/`member_type` belum ada di form → registrasi dari UI selalu 422.
2. **Seeder pickups: SUDAH ADA di `TestingSeeder`** (pickup selesai+items, menunggu jadwal jemput, unit elektronik, batal, + komplain). Bug ditemukan & diperbaiki: (a) `DatabaseSeeder` tidak mengisi `price_sell`/`price_admin` katalog → service timbang & papan harga bernilai 0; kini `price_sell = price_sorted`, `price_admin = 20%`; (b) totals hardcoded TestingSeeder tidak konsisten dengan item-nya (36.500 vs 47.500 aktual) → diluruskan: gross 49.100 (tembaga 0.2kg×128.000 + aqua 5kg×4.700), fee 9.820, net 39.280; unit monitor gudang unsorted 60.000; jurnal fee trx3 ikut disamakan.
3. **Alur pengaduan warga: SUDAH terintegrasi.** FE `Receipts.jsx` GET/POST `/complaints` cocok `SubmitComplaintRequest`; `ComplaintsDesk.jsx` PATCH `/complaints/{id}/resolve` cocok `ResolveComplaintRequest`; own-check anggota di controller. Tidak diubah.

**Eksekusi:**
- [x] **R1**: FE `Register.jsx` + field `username` (autolowercase), `phone`, `member_type` (select rumah/pasar), `address` (textarea) + notifikasi sukses "menunggu verifikasi".
- [x] **R2**: `DatabaseSeeder` isi `price_sell`/`price_admin`; `TestingSeeder` totals diluruskan konsisten dengan `TrashWeighingService` (sorted − diskon jemput logam 2.000/non-logam 300, fee 20%).

**Tindak lanjut (audit lanjutan FE port Breeze):**

- [x] **F1 — Register tidak ter-route**: `main.jsx` hanya route Login/Pengurus/Anggota/Petugas; `Pages/Auth/*` (port Breeze) dead code & butuh komponen Breeze yang tidak ada. Solusi: halaman baru `Pages/Register.jsx` (style konsisten `Pages/Login.jsx`, field lengkap sesuai `RegisterMemberRequest`) + route `/register` di `main.jsx` + link "Daftar sebagai anggota" di Login. Folder `Pages/Auth` & `Pages/Profile` dead code (delete pending — shell gate down).
- [x] **F2 — Login aktif sudah benar**: `Pages/Login.jsx` kirim `identity`/`password` cocok `LoginRequest` (port Breeze `Pages/Auth/Login.jsx` yang salah field ikut dead code).
- [x] **F3 — Change password port Breeze salah field**: FE kirim `password`/`password_confirmation`, BE `ChangePasswordRequest` butuh `new_password` — ada di `Pages/Profile/Partials` yang memang dead code (tidak ter-route), tidak berdampak produksi; folder ikut dihapus bersama F1.

---

## 🔁 REVISI FASE-2 (revisi.md poin 4–5) — 10 September 2026

**Audit:**

4. **Foto timbang (kamera + timestamp + lokasi): BELUM.** `pickups` di BE hidup tidak punya kolom `photo_path`/`latitude`/`longitude` (hanya ada di salinan basi root monorepo), tidak ada endpoint upload, FE `WeighingForm.jsx` tidak ada input kamera/geo. Timestamp timbang sudah ada (`completed_at` terisi server saat weigh-items).
5. **Riwayat aduan + label proses/selesai: SEBAGIAN.** FE anggota `Receipts.jsx` sudah menampilkan komplain per nota (pill status), BE `/complaints` lengkap. Gap: label mentah enum (`diterima`/`ditolak` dll, tanpa kata "Diproses"/"Selesai"), bug typo `'diproses'` di `ComplaintsDesk.jsx` (nilai DB `proses` → hitungan "Menunggu Tindakan" salah), cek status `'selesai'` mati di ComplaintsDesk (tidak pernah ada di enum complaints).

**Plan eksekusi (ultra-minimal):**

- [x] **R3 (P4 BE)**: migrasi `2026_09_10_000001_add_photo_geo_to_pickups_table.php` (`photo_path`/`latitude`/`longitude` nullable); `PickupController::uploadPhoto` (validasi image jpg/png ≤5MB + lat/long opsional) + route `POST /pickups/{id}/photo` role ketua/pengurus/petugas; `PickupResource` + `photo_url`/`latitude`/`longitude`; model fillable + accessor `photoUrl()`. Timestamp = `completed_at` (sudah ada, tanpa kolom baru). Perlu `php artisan migrate` + `php artisan storage:link`.
- [x] **R4 (P4 FE)**: `WeighingForm.jsx` — `<input type="file" accept="image/*" capture="environment">` (buka kamera langsung di mobile, native tanpa lib), preview thumbnail + caption timestamp & koordinat GPS via `navigator.geolocation` (opsional, GPS ditolak → foto tetap naik tanpa koordinat), upload FormData ke `/pickups/{id}/photo` setelah weigh-items sukses (gagal upload tidak membatalkan timbangan).
- [x] **R5 (P5 FE)**: `src/lib/complaint.js` (map label: diajukan→Diajukan, proses→Diproses, diterima→Selesai, ditolak→Ditolak + kelas warna) dipakai di `Receipts.jsx` + `ComplaintsDesk.jsx` (pill tabel, modal detail); typo `diproses` → `proses` (statistik "Menunggu Tindakan" kini benar); cek `selesai` mati dihapus. Tanpa ubah skema — `diterima`/`ditolak` terminal, cukup label tampilan.

---

## 🔁 REVISI FASE-3 (5 poin revisi.md) — 10 September 2026

**Audit (BE hidup = folder `backend/`; root monorepo salinan basi):**

1. **Jadwal pakem**: live BE tidak punya endpoint pickup-schedules sama sekali (FE memanggil `/pickup-schedules` → 404). Rutin jadwal murni artefak FE. → Hapus UI rutin di FE, ganti kartu info jadwal pakem statis.
2. **Minta jemput nyangkut**: ROOT CAUSE — `Petugas/Index.jsx` "Timbang Sekarang" mengirim `p.member` ke WeighingForm, yang lalu membuat tiket BARU via POST /pickups dan menimbang tiket baru itu; tiket "minta jemput" asli tidak pernah ditimbang → status `menunggu` selamanya. → Kirim objek pickup, WeighingForm menimbang `pickup.id` langsung tanpa buat tiket baru.
3. **Kategori anggota rumah/pasar**: kolom belum ada. → `members.categories` (json array: rumah|pasar, pilih ≥1), `pickups.location_type` +`jemput_pasar`; form user anggota → checkbox kategori (bukan dropdown "Anggota Terhubung"); role `anggota` masuk validasi CreateUserRequest/UpdateUserRequest; requestPickup menawarkan lokasi sesuai kategori anggota.
4. **Harga 3 nominal**: Harga Kotor = `price_unsorted` (manual), Harga Jual = `price_sell` (manual), Harga Bersih = 80%×jual (auto, sudah ada accessor `price_member`). Engine: gudang sorted unit price `price_sorted` → `price_sell` agar net anggota pas 80%×jual. `price_sorted` jadi opsional.
5. **alert/confirm/prompt JS → popup**: 10 titik di 6 file FE.

**Plan eksekusi:**

- [x] **R6 (BE kategori)**: migrasi `2026_09_10_000002` — `members.categories` json nullable + `pickups.location_type` enum +`jemput_pasar`; `Member` fillable+cast, `MemberResource` +categories; `RegisterMemberRequest` `member_type`→`member_types` (array min:1 in:rumah,pasar); `AuthService::registerMember` terima status + member_types (kategori pertama jadi member_category_id utama); `CreateUserRequest`/`UpdateUserRequest` role +anggota & member_types required_if anggota; `UserService` delegate create anggota → registerMember(status aktif), update → sync categories; `UserRepository::paginate` eager `member`; `CreatePickupTicketRequest` location_type +jemput_pasar.
- [x] **R7 (BE harga + nyangkut)**: `TrashWeighingService` — cek jemput `str_starts_with(location_type,'jemput')`, gudang sorted pakai `price_sell`, isi `officer_id` saat complete tiket buatan anggota; `TrashCategoryService::pickupPrice` base sorted → `price_sell`; `StoreTrashCategoryRequest`/`UpdateTrashPriceRequest` price_sorted opsional; `TrashCategoryRepository::updatePriceWithAudit` price_sorted opsional; seeder member categories.
- [x] **R8 (FE jadwal + nyangkut)**: hapus rutin di `Popups.jsx`+`Anggota/Index.jsx` (+ kartu pakem, jemput-ulang modal reason), `Petugas/Index.jsx` kirim pickup, `WeighingForm.jsx` prop `initialPickup` (timbang tiket existing), label lokasi +jemput_pasar (PickupMonitor ikut).
- [x] **R9 (FE kategori + harga)**: `Users.jsx` checkbox Rumah/Pasar + hapus role pengepul + ConfirmPopup; `Popups.jsx` form anggota → POST /users; `requestPickup` lokasi dari `user.member.categories`; `Register.jsx` member_types checkbox; `TrashPrices.jsx` 3 nominal; `PriceBoard.jsx` sesuaikan kartu.
- [x] **R10 (FE popup)**: ganti semua alert/confirm/prompt (`WajibOverview`, `Withdrawals`, `Pengurus/Index`, `Users`, `TrashPrices`, `Anggota/Index`) → ConfirmPopup/StatusPopup/modal. Grep FE `alert(`/`confirm(`/`prompt(` → 0 sisa.
- [ ] **R11 (test)**: phpunit — timbang tiket existing (bug #2), harga sorted pakai price_sell, validasi member_types; FE `npm run build`. — 3 test baru sudah ditulis (`TrashWeighingTest`); **belum dijalankan** (tool shell tertutup sementara). Bonus fix saat menunggu: (a) `2026_09_10_000001` hapus `->after('source')` (kolom `source` tidak ada — after() diabaikan sqlite tapi error di mysql); (b) migrasi agregat duplikat `create_database_schema` (bikin semua tabel yang sama dengan migrasi individual 000001–000023, tanpa guard) → pindah ke `2026_09_09_999999_create_database_schema.php` dengan guard `hasTable('users')`, file lama jadi no-op; aman baik untuk fresh sqlite maupun DB live yang mencatat salah satu set. Perlu jalankan: `php artisan test --compact`, `php artisan migrate` (mysql live), FE `npm run build`.

## 🔁 REVISI TAMBAHAN (role) — 10 September 2026

**Role fix jadi 3: pengurus (menyerap ketua), petugas, warga (anggota).**

- [x] **R12 (BE)**: migrasi `2026_09_10_000003_drop_ketua_role.php` — baris `role=ketua` di-upgrade ke `pengurus`, lalu enum `users.role` dipersempit ke `['pengurus','petugas','anggota']`; enum di kedua create-migration ikut dipersempit; `routes/api.php` semua middleware `role:ketua,...` → `role:pengurus,...` (publish SHU & finance-category store kini `role:pengurus`); `CreateUserRequest`/`UpdateUserRequest` Rule::in tanpa ketua; `TrashWeighingService::createTicket` officer check tanpa ketua; `DatabaseSeeder` akun ketua → role pengurus; `ShuDistributionTest` ketua → pengurus (termasuk test `only_pengurus_can_publish`).
- [x] **R13 (FE)**: `Login.jsx` HOME_BY_ROLE tanpa ketua; `Users.jsx` — role select 3 opsi (Petugas/Pengurus/Warga), filter & StatCard tanpa Ketua, label pill "Warga" via ROLE_LABEL, header desc update; `MemberShell` badge "WARGA". Grep FE `ketua` → 0. (Nilai enum `anggota` dipertahankan di BE — "warga" hanya label tampilan.)
- [ ] **R14 (verifikasi role)**: `php artisan migrate` di mysql live (ketua lama otomatis jadi pengurus) + test suite + FE build.

---

## 🔁 KONSOLIDASI MONOREPO — 10 September 2026

**Projek backend = root monorepo ini; folder `backend/` nested dihapus** (perintah user). Semua revisi fase-1..3 + role fix dipindah dari `backend/` ke root.

Yang dilakukan:
- Semua diff `backend/` → root diterapkan (controllers, requests, resources, models, repositories, services, migrations, seeders, routes, tests, progress.md, openapi.json).
- **Keep versi root (superset)**: `ReportController` + `ReportService` (export xlsx/pdf on-the-fly via `getExportRows` + `per_item_category` — FE memakai `format=xlsx|pdf`), `WalletController` + `WalletService` + `WithdrawCashRequest` (endpoint `POST /wallet/withdraw-cash` dipakai FE `Withdrawals.jsx`; route ditambahkan kembali ke routes/api.php dengan `role:pengurus,petugas`), `app/Exports`, `resources/views/reports`.
- **Dihapus (lineage basi root)**: `PickupScheduleController`, `PickupSchedule` model, migrasi `add_pengepul_role`/`add_gps_to_pickups`/`create_pickup_schedules`, `app/Console/Commands/*` + entri Schedule di `routes/console.php` (fitur rutin dihapus revisi #1), `MemberRegistrationTest` (mengetes endpoint lama `/members/register` + field `member_type` yang sudah tidak ada).
- **Bug laten diperbaiki saat port**: `DatabaseSeeder` versi backend mengisi `price_admin` padahal kolom sudah dihapus migrasi fase-3 → seeder crash; dihapus dari seeder (harga anggota 80% dihitung on-the-fly).
- Catatan: `backend/database/temp.sqlite`, `backend/storage/laravel.log` tidak dipindah (junk). Mojibake UTF-8 di `backend/tests/.../TrashWeighingTest.php` (â€"/â†') tidak ikut — versi root bersih.

Sisa (butuh shell/mysql):
- [ ] `php artisan test --compact` dari root
- [ ] `php artisan migrate` + `php artisan storage:link` (mysql live; tentukan DB: root .env `kjps-mural` vs backend .env `pppgs`)
- [ ] Hapus folder `backend/` setelah verifikasi
- [ ] FE `npm run build`
- Gap diketahui (bukan bagian revisi): `public/docs/openapi.json` masih menyebut ketua/pengepul (46×) dan belum mendokumentasikan `/wallet/withdraw-cash` — perlu regenerasi docs terpisah.
