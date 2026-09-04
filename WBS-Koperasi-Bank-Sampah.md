# WBS — Website Koperasi & Bank Sampah
**Stack:** Laravel (backend, REST API / web service) + React (frontend, konsumsi API)
**Referensi:** PRD-Koperasi-Bank-Sampah.md

Pola kerja: Laravel berperan murni sebagai **web service/API** (Laravel Sanctum untuk auth token, endpoint JSON per modul), React sebagai **SPA/klien** yang konsumsi API tersebut secara terpisah (bukan Blade).

---

## 1. Perencanaan & Setup Proyek

| Kode | Task | Deliverable |
|---|---|---|
| 1.1 | Finalisasi requirement & TBD dari PRD (rumus SHU sukarela, metode pencairan, dll) | Dokumen requirement final |
| 1.2 | Desain database (ERD) seluruh modul | ERD + skema migrasi |
| 1.3 | Desain kontrak API (daftar endpoint, request/response) | API spec (OpenAPI/Postman collection) |
| 1.4 | Setup repo backend (Laravel) — struktur project, environment, CI dasar | Repo backend siap |
| 1.5 | Setup repo frontend (React) — struktur project, routing, state management | Repo frontend siap |
| 1.6 | Setup autentikasi lintas layanan (Laravel Sanctum/Passport, token-based) | Auth service berjalan |

## 2. Modul Autentikasi & Manajemen User/Role

| Kode | Task | Deliverable |
|---|---|---|
| 2.1 | API: register, login, logout, refresh token | Endpoint auth |
| 2.2 | API: middleware role-based access (Admin, Bendahara/Operator, Pengepul, Anggota) | Middleware & policy |
| 2.3 | API: CRUD user & assign role (khusus Admin) | Endpoint user management |
| 2.4 | React: halaman login | UI login |
| 2.5 | React: halaman registrasi anggota (termasuk trigger simpanan pokok) | UI registrasi |
| 2.6 | React: proteksi route per role (route guard) | Routing berbasis role |

## 3. Modul Simpanan (Pokok, Wajib, Sukarela, Tipping Fee)

| Kode | Task | Deliverable |
|---|---|---|
| 3.1 | API: catat simpanan pokok saat registrasi (Rp50.000) | Endpoint & logic |
| 3.2 | API: catat setoran bulanan (tipping fee Rp40.000 + simpanan wajib Rp5.000 = Rp45.000) | Endpoint & logic |
| 3.3 | API: catat simpanan sukarela (nominal bebas) | Endpoint & logic |
| 3.4 | API: riwayat simpanan per anggota (pokok/wajib/sukarela terpisah) | Endpoint riwayat |
| 3.5 | API: status bayar bulanan (lunas/belum) + generate tagihan | Endpoint tagihan |
| 3.6 | React: dashboard anggota — ringkasan simpanan & status bayar | UI dashboard simpanan |
| 3.7 | React: sisi bendahara — verifikasi & input pembayaran anggota | UI verifikasi pembayaran |
| 3.8 | React: riwayat simpanan (tabel per jenis) | UI riwayat |

## 4. Modul Harga & Papan Info

| Kode | Task | Deliverable |
|---|---|---|
| 4.1 | API: CRUD kategori sampah (campur, organik, anorganik per item) | Endpoint kategori |
| 4.2 | API: seed data awal harga anorganik (dari lampiran PRD §5.1.1) | Seeder database |
| 4.3 | API: dukungan dua satuan harga (per kg & per biji) + rentang harga (min–max) | Skema harga fleksibel |
| 4.4 | API: dua tingkat harga (diantar gudang vs dijemput rumah, selisih logam vs non-logam) | Logic kalkulasi harga |
| 4.5 | API: update harga harian + riwayat perubahan (siapa, kapan) — akses Bendahara/Operator | Endpoint & audit log |
| 4.6 | React: halaman manajemen harga (sisi Bendahara/Operator) | UI kelola harga |
| 4.7 | React: papan info harga (publik/dashboard anggota) | UI papan info |

## 5. Modul Bank Sampah — Timbang & Nota

| Kode | Task | Deliverable |
|---|---|---|
| 5.1 | API: input hasil timbang (anggota, kategori, berat/jumlah, lokasi timbang) | Endpoint input timbang |
| 5.2 | API: kalkulasi nilai rupiah otomatis berdasarkan harga hari itu & lokasi | Logic kalkulasi |
| 5.3 | API: update saldo anggota secara langsung (real-time cair) | Logic saldo |
| 5.4 | API: generate & ambil nota digital per transaksi | Endpoint nota |
| 5.5 | React: form input timbang (sisi pengepul, mobile-friendly untuk lapangan) | UI input timbang |
| 5.6 | React: halaman nota/riwayat transaksi (sisi anggota) | UI nota |

## 6. Modul Komplain

| Kode | Task | Deliverable |
|---|---|---|
| 6.1 | API: ajukan komplain terhadap transaksi/nota tertentu | Endpoint komplain |
| 6.2 | API: status & tindak lanjut komplain (diajukan/diproses/selesai) | Endpoint status |
| 6.3 | React: form ajukan komplain (sisi anggota) | UI komplain |
| 6.4 | React: dashboard kelola komplain (sisi Admin/Bendahara) | UI kelola komplain |

## 7. Modul Saldo & SHU

| Kode | Task | Deliverable |
|---|---|---|
| 7.1 | API: agregasi saldo anggota (dari hasil sampah + SHU) | Endpoint saldo |
| 7.2 | API: kalkulasi SHU tahunan (20% laba koperasi ÷ jumlah anggota) | Job/command kalkulasi SHU |
| 7.3 | API: proses pembagian SHU akhir tahun + otomatis masuk saldo | Scheduled job |
| 7.4 | API: klaim SHU per anggota (per tahun) | Endpoint klaim |
| 7.5 | API: skema pencairan saldo (placeholder, metode masih TBD — desain fleksibel/pluggable) | Interface pencairan |
| 7.6 | React: halaman saldo anggota | UI saldo |
| 7.7 | React: halaman klaim SHU + riwayat SHU per tahun | UI SHU |
| 7.8 | React: laporan SHU sisi Admin/Bendahara (total dibagikan, sisa, dll) | UI laporan SHU |

## 8. Laporan & Dashboard Admin

| Kode | Task | Deliverable |
|---|---|---|
| 8.1 | API: laporan keuangan (simpanan, transaksi sampah, SHU) | Endpoint laporan |
| 8.2 | React: dashboard admin (ringkasan seluruh aktivitas koperasi) | UI dashboard admin |
| 8.3 | React: export laporan (misal ke Excel/PDF) | Fitur export |

## 9. Testing & QA

| Kode | Task | Deliverable |
|---|---|---|
| 9.1 | Unit test backend (kalkulasi harga, SHU, saldo — krusial karena menyangkut uang) | Test suite backend |
| 9.2 | Integration test API (auth, role, endpoint utama) | Test suite integrasi |
| 9.3 | Testing UI React per modul | Test/QA checklist frontend |
| 9.4 | UAT (User Acceptance Test) bersama pengurus koperasi | Berita acara UAT |

## 10. Deployment & Dokumentasi

| Kode | Task | Deliverable |
|---|---|---|
| 10.1 | Setup server/hosting untuk backend (Laravel) & frontend (React) | Environment production |
| 10.2 | Konfigurasi CORS, environment variable, secret management | Konfigurasi production |
| 10.3 | Deployment awal + smoke test | Aplikasi live |
| 10.4 | Dokumentasi API (untuk maintenance ke depan) | Dokumen API |
| 10.5 | Manual penggunaan per role (Admin, Bendahara, Pengepul, Anggota) | User manual |

---

## Catatan Urutan Pengerjaan (Saran)
1. **Fase 1 (Fondasi):** §1 → §2 — auth & role harus selesai dulu karena semua modul lain bergantung pada role access.
2. **Fase 2 (Core transaksi):** §3, §4, §5 — simpanan, harga, dan timbang sampah adalah inti bisnis, bisa dikerjakan paralel setelah fondasi selesai.
3. **Fase 3 (Pendukung):** §6, §7 — komplain & SHU, bergantung pada data transaksi dari Fase 2.
4. **Fase 4 (Pelengkap):** §8 — laporan, bisa dikerjakan setelah data dari fase sebelumnya tersedia.
5. **Fase 5 (Rilis):** §9 → §10 — testing menyeluruh lalu deployment.

**Item yang masih TBD di PRD (§7.5) sebaiknya didesain sebagai interface/abstraksi di awal**, supaya saat metode pencairan final diputuskan, tidak perlu refactor besar di modul saldo.
