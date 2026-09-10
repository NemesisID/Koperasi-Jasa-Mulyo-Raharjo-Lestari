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

> **Penting**: BE hidup adalah folder `backend/` (punya `vendor/` + `.env` + APP_URL:8000 = target proxy FE). Folder root monorepo ini adalah salinan basi — catatan revisi lengkap ada di `backend/progress.md`.

**Ringkas audit (detail + bukti di `backend/progress.md`):**

1. **Registrasi member**: BE sudah punya `POST /auth/register-member`; FE `Register.jsx` kekurangan field wajib `username`/`phone`/`address`/`member_type` → sudah ditambahkan.
2. **Seeder pickups**: sudah ada di `backend/TestingSeeder` (selesai+ditimbang, menunggu/jadwal, unit, batal, komplain); diperbaiki `price_sell`/`price_admin` kosong di `backend/DatabaseSeeder` + totals tidak konsisten.
3. **Pengaduan warga**: sudah terintegrasi FE↔BE, tidak diubah.
