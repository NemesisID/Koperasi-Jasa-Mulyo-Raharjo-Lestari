# WORK BREAKDOWN STRUCTURE (WBS) & PEMBAGIAN TIM PROYEK
## Sistem Informasi Koperasi & Bank Sampah "Koperasi Jasa Mulyo Raharjo Lestari"
**Arsitektur:** Decoupled Architecture / Web Service Murni  
- **Repository 1:** `repo-backend-koperasi` (Laravel 11 RESTful API + Laravel Sanctum)  
- **Repository 2:** `repo-frontend-koperasi` (React 18 SPA + Vite + Tailwind CSS)  
**Versi:** 2.1.0  
**Tanggal:** 4 September 2026  
**Status:** Approved Multi-Repository Team Execution Plan  

---

## 1. Tata Kelola Repository & Kolaborasi Tim (2 Repositories Setup)

Kedua repository dikembangkan secara independen dan hanya terhubung melalui **API Contract (JSON over HTTPS)**.

```
┌────────────────────────────────────────────────────────┐       ┌────────────────────────────────────────────────────────┐
│     REPOSITORY 1: BACKEND (Laravel 11 REST API)        │       │       REPOSITORY 2: FRONTEND (React 18 SPA + Vite)     │
│             `repo-backend-koperasi`                    │       │              `repo-frontend-koperasi`                  │
├────────────────────────────────────────────────────────┤       ├────────────────────────────────────────────────────────┤
│ • Database Migrations & Seeds (MySQL)                  │       │ • Single Page Application (React Router)               │
│ • Eloquent Models & Business Service Layer             │ HTTP  │ • Axios HTTP Client with Bearer Token Interceptor      │
│ • RESTful API Controllers & Form Requests              │ JSON  │ • UI Portal Pengurus / Admin (FE-A)                    │
│ • Authentication via Laravel Sanctum (Bearer Token)    │◄─────►│ • UI Portal Petugas Lapangan Mobile-Friendly (FE-B)    │
│ • Unit & Feature Tests (PHPUnit / Pest)                │       │ • UI Portal Anggota / Warga (FE-B)                     │
│ • Cron Jobs & Scheduled Tasks (Billing, SHU Engine)    │       │ • Mock API Handler (MSW / TanStack Query)              │
└────────────────────────────────────────────────────────┘       └────────────────────────────────────────────────────────┘
```

### Matriks Alokasi Anggota Tim:
- **Tim Backend (BE Lead & BE Dev)**: Sepenuhnya bertanggung jawab atas Repository Backend (`repo-backend-koperasi`).
- **Tim Frontend Core (FE-A)**: Bertanggung jawab atas Repository Frontend (`repo-frontend-koperasi`), fokus modul Admin/Pengurus & Keuangan.
- **Tim Frontend Field & Member (FE-B)**: Bertanggung jawab atas Repository Frontend (`repo-frontend-koperasi`), fokus modul Petugas Timbang Lapangan & Portal Anggota.
- **Fullstack / QA & DevOps**: Mengelola kontrak API (OpenAPI/Swagger), CI/CD runner pada kedua repo, integrasi CORS, dan staging environment.

---

## 2. Standar Kontrak API (API Contract & Response Envelope)

Agar Tim Frontend dapat bekerja secara paralel dengan Tim Backend (menggunakan *mock server* tanpa terblokir):

```json
// Response Sukses (HTTP 200 / 201)
{
  "success": true,
  "message": "Deskripsi hasil proses",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}

// Response Gagal / Error Validasi (HTTP 422 / 400 / 401 / 403 / 500)
{
  "success": false,
  "message": "Data yang dikirimkan tidak valid",
  "errors": {
    "weight_kg": ["Berat timbangan wajib bernilai positif"],
    "member_id": ["ID Anggota tidak ditemukan"]
  }
}
```

---

## 3. WBS REPOSITORY 1: BACKEND (Laravel 11 Web Service)

**Fokus:** REST API, Database, Business Logic, Validation, Authentication, dan Background Jobs.  
**Spesifikasi Detail Route & Payload API:** Lihat panduan lengkap di [route-api.md](file:///c:/laragon/www/Koperasi-Jasa-Mulyo-Raharjo-Lestari/route-api.md)  
**Rencana Implementasi & Task Breakdown Detail:** Ikuti panduan arsitektur layer di [implementation-plan-backend.md](file:///c:/laragon/www/Koperasi-Jasa-Mulyo-Raharjo-Lestari/implementation-plan-backend.md) berdasarkan aturan [be-architecture.md](file:///c:/laragon/www/Koperasi-Jasa-Mulyo-Raharjo-Lestari/be-architecture.md)

### BAGIAN BE-1: Environment Setup, Database & Autentikasi Sanctum (Sprint 1)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Deliverable |
|:---:|---|---|---|
| **BE-1.1** | **Backend Repo Initialization** | Setup Laravel 11 murni (tanpa Blade/Inertia views), konfigurasi `.env`, install `laravel/sanctum`, CORS (`config/cors.php`), dan standard JSON error response wrapper. | Skeleton Repo Backend Siap |
| **BE-1.2** | **DB Migrations & Integrity** | Implementasi 13 tabel migrasi MySQL lengkap dengan foreign key constraints, default value, dan indexing kolom pencarian (`user_id`, `member_id`, `status`, `scheduled_at`). | Skema DB MySQL Terpasang |
| **BE-1.3** | **Database Seeders** | Seeder untuk Role pengguna, User default (Admin, Bendahara, Petugas, Anggota), dan Katalog Sampah Anorganik lengkap (sesuai lampiran harga Ponorogo). | Database Seeders Siap Run |
| **BE-1.4** | **Eloquent Models & Scopes** | Pembuatan 13 Model Eloquent dengan relasi lengkap (`belongsTo`, `hasMany`), casting tipe data, UUID Primary Key handling, serta Query Scopes. | 13 File di `app/Models/*` |
| **BE-1.5** | **Auth REST API (Sanctum)** | Endpoint `POST /api/v1/auth/login` (return PlainTextToken), `POST /api/v1/auth/logout`, `GET /api/v1/auth/me`, dan middleware `auth:sanctum` + Role Authorization Policy. | Auth Endpoints Aktif |

### BAGIAN BE-2: Master Data Anggota, Kategori & Akun Kas (Sprint 2)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Deliverable |
|:---:|---|---|---|
| **BE-2.1** | **API Master Anggota (Members)** | Endpoint CRUD `api/v1/members`, otomasi kode anggota (`MBR-YYYYMM-XXXX`), filter status (`aktif`, `suspend`), pagination, dan upload lampiran/KTP. | API Resource `MemberController` |
| **BE-2.2** | **API Kategori Sampah & Harga** | Endpoint CRUD `api/v1/trash-categories`, skema multi-satuan (`kg`, `biji`), penyesuaian harga lokasi (gudang vs jemput lapangan: diskon non-logam Rp300, logam Rp2.000). | API `TrashCategoryController` |
| **BE-2.3** | **API Audit Update Harga Harian** | Endpoint update harga harian oleh Bendahara + logging histori perubahan harga (`price_change_logs`) untuk transparansi audit. | API Audit Harga |
| **BE-2.4** | **API Kategori Akun Kas** | Endpoint CRUD `api/v1/finance-categories` untuk pencatatan chart of accounts kas koperasi. | API `FinanceCategoryController` |

### BAGIAN BE-3: Engine Bank Sampah: Timbang & Nota Digital (Sprint 3)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Deliverable |
|:---:|---|---|---|
| **BE-3.1** | **Service Kalkulasi Timbang & Potongan 20%** | Business service `TrashWeighingService`: validasi input timbangan, kalkulasi nilai kotor, pemotongan biaya admin 20% otomatis ke kas koperasi, dan penentuan nilai bersih anggota. | Unit Test & Service Class |
| **BE-3.2** | **API Penjemputan & Input Timbang** | Endpoint `POST /api/v1/pickups` dan `POST /api/v1/pickups/{id}/items`: simpan rincian timbangan sampah dan langsung mengkredit saldo anggota secara instan (*real-time deposit*). | API Timbang Lapangan |
| **BE-3.3** | **API Nota Digital & Verifikasi** | Endpoint `GET /api/v1/pickups/{id}/receipt`: payload nota digital lengkap (ID unik, QR verification URL, rincian jenis sampah, berat, potongan 20%, saldo masuk). | API Nota Digital |
| **BE-3.4** | **API Pengaduan & Komplain Nota** | Endpoint `POST /api/v1/complaints` (anggota ajukan komplain selisih timbangan) dan `PATCH /api/v1/complaints/{id}` (admin selesaikan/koreksi saldo). | API Modul Komplain |

### BAGIAN BE-4: Simpanan Koperasi, Tagihan Bulanan & Saldo (Sprint 4)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Deliverable |
|:---:|---|---|---|
| **BE-4.1** | **API Simpanan Pokok & Billing Rutin** | Endpoint pencatatan Simpanan Pokok (Rp50.000) dan Scheduled Job bulanan tagihan Rp45.000 (Simpanan Wajib Rp5.000 + Tipping Fee Rp40.000). | API Simpanan & Billing Job |
| **BE-4.2** | **API Simpanan Sukarela & Mutasi Kas** | Endpoint setoran simpanan sukarela, pencatatan mutasi kas pemasukan/pengeluaran koperasi (`/api/v1/transactions`). | API Mutasi Keuangan |
| **BE-4.3** | **API Saldo & Penarikan Tunai (Cashout)** | Endpoint `GET /api/v1/wallet/balance` (agregasi saldo hasil sampah + SHU) dan `POST /api/v1/wallet/withdraw` (pengajuan penarikan dana tunai/transfer). | API Dompet & Cashout |

### BAGIAN BE-5: Mesin Kalkulasi SHU Tahunan & Laporan Keuangan (Sprint 5)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Deliverable |
|:---:|---|---|---|
| **BE-5.1** | **Engine Kalkulasi SHU Tahunan** | Service `ShuDistributionEngine`: ambil laba bersih koperasi, hitung pool 20%, alokasikan dividen ke anggota berdasarkan akumulasi simpanan + frekuensi transaksi sampah. | Service & Command `artisan shu:calculate` |
| **BE-5.2** | **API Eksekusi & Klaim SHU** | Endpoint posting pembagian SHU (otomatis top-up ke saldo anggota) dan endpoint klaim riwayat SHU tahunan per anggota. | API Eksekusi SHU |
| **BE-5.3** | **API Rekapitulasi Laporan Finansial** | Endpoint agregasi data laporan: Laporan Arus Saldo Kas, Laba Rugi Operasional, Rekap Simpanan Anggota, dan Rekapitulasi Volume Sampah (Export JSON/PDF/Excel). | Endpoint `/api/v1/reports/*` |

### BAGIAN BE-6: Testing, Keamanan & Deployment Backend (Sprint 6)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Deliverable |
|:---:|---|---|---|
| **BE-6.1** | **Automated API Testing (PHPUnit/Pest)** | Test suite mencakup: Autentikasi token, Role permission, kalkulasi timbangan sampah + potongan 20%, integritas saldo dompet, dan distribusi SHU. | Green Test Suite (Coverage > 80%) |
| **BE-6.2** | **Rate Limiting & Security Hardening** | Pengaturan throttle rate-limit API, SQL Injection audit, sanitasi input, token expiry, dan audit logging. | Security Hardening Report |
| **BE-6.3** | **Production API Server Deployment** | Konfigurasi server Nginx / Apache, PHP 8.3-FPM, SSL HTTPS, supervisord worker untuk queues/jobs, dan setup Cron Scheduler. | Backend Live Production |

---

## 4. WBS REPOSITORY 2: FRONTEND (React 18 SPA + Vite)

**Fokus:** UI/UX, State Management, Form Validation, Axios Interceptor, Mobile-First PWA/Web Views.

### BAGIAN FE-1: Fondasi Client, Routing & State Autentikasi (Sprint 1)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Penanggung Jawab | Deliverable |
|:---:|---|---|:---:|---|
| **FE-1.1** | **Frontend Repo Setup & Design Tokens** | Inisialisasi Vite + React 18, Tailwind CSS, konfigurasi Google Fonts (Inter), Icon Lucide-React, dan color tokens (Emerald/Navy/Slate). | FE-A + FE-B | Skeleton Repo Frontend Siap |
| **FE-1.2** | **Axios HTTP Client & Interceptors** | Konfigurasi Axios instance: base URL API backend, request interceptor (otomatis lampirkan `Authorization: Bearer <token>`), response interceptor (handle global 401 unauthenticated & redirect ke login). | FE-A | `src/services/api.js` |
| **FE-1.3** | **State Management & Auth Provider** | Setup Global Auth Context / Zustand Store: menyimpan state user login, token di `localStorage` / `sessionStorage`, dan method `login()`, `logout()`. | FE-A | `AuthContext` / Store |
| **FE-1.4** | **Routing & Protected Role Guards** | Setup React Router v6: Route Guards (`ProtectedRoute`) yang menyaring akses halaman berdasarkan role (`Pengurus`, `Petugas`, `Anggota`). | FE-A | `src/routes/AppRoutes.jsx` |
| **FE-1.5** | **UI Halaman Login Multi-Role** | Form login modern dengan tab pemilihan peran (Pengurus, Anggota, Petugas), show/hide password, integrasi API `POST /auth/login`, dan validasi error inline. | FE-A | Screen `src/pages/Auth/Login.jsx` |

### BAGIAN FE-2: Portal Pengurus & Admin (FE-A / Sprint 2-3)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Penanggung Jawab | Deliverable |
|:---:|---|---|:---:|---|
| **FE-2.1** | **Layout & Navigation Pengurus** | Sidebar navigasi responsif, header profil user, notifikasi lonceng, dan quick role switcher. | FE-A | Layout `ManagerLayout.jsx` |
| **FE-2.2** | **UI Manajemen Anggota (CRUD)** | Halaman tabel data anggota: filter status aktif/suspend, live search, pagination, modal formulir pendaftaran anggota baru, modal detail profil. | FE-A | Screen `Pengurus/Members.jsx` |
| **FE-2.3** | **UI Manajemen Harga Sampah Harian** | Tampilan katalog sampah per kategori (Logam, Plastik, Kertas, dll), input cepat pembaruan harga harian oleh Bendahara, modal riwayat histori perubahan harga. | FE-A | Screen `Pengurus/TrashPrices.jsx` |
| **FE-2.4** | **UI Rekapitulasi & Verifikasi Simpanan** | Tabel status iuran bulanan warga (lunas/belum lunas), verifikasi pembayaran kasir tunai/transfer, rekapan setoran pokok/sukarela. | FE-A | Screen `Pengurus/SavingsManager.jsx` |
| **FE-2.5** | **UI Helpdesk Pengaduan/Komplain** | Panel daftar komplain nota dari warga: detail selisih berat/kategori, dialog aksi persetujuan revisi saldo atau penolakan dengan alasan. | FE-A | Screen `Pengurus/ComplaintsDesk.jsx` |

### BAGIAN FE-3: Portal Petugas Lapangan & Mobile Timbang (FE-B / Sprint 2-3)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Penanggung Jawab | Deliverable |
|:---:|---|---|:---:|---|
| **FE-3.1** | **Mobile-First Layout Petugas** | Shell navigasi mobile-first khusus smartphone petugas lapangan: header ringkas, tombol cepat kembali, status koneksi server. | FE-B | Layout `OfficerMobileLayout.jsx` |
| **FE-3.2** | **UI Form Timbang Sampah Cepat** | Antarmuka penimbangan di lapangan: autocomplete pencarian nama/kode anggota, dropdown jenis sampah dinamis, input berat (angka besar/keypad friendly), toggle lokasi (diantar ke gudang vs dijemput di rumah/pasar). | FE-B | Screen `Petugas/WeighingForm.jsx` |
| **FE-3.3** | **Kalkulator Preview & Submit Timbang** | Real-time calculation preview saat input berat: menampilkan nilai kotor, potongan 20% koperasi, dan nominal bersih yang diterima anggota sebelum data disubmit ke API. | FE-B | Widget Kalkulator Form |
| **FE-3.4** | **UI Konfirmasi & Cetak/Bagikan Nota** | Layar sukses input timbangan: tombol kirim nota via WhatsApp / QR Code scan untuk dilihat langsung oleh warga di tempat. | FE-B | Modal `ReceiptSuccessModal.jsx` |

### BAGIAN FE-4: Portal Anggota / Warga (FE-B / Sprint 4-5)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Penanggung Jawab | Deliverable |
|:---:|---|---|:---:|---|
| **FE-4.1** | **Layout & Dashboard Portal Warga** | Tampilan ringkasan saldo dompet, saldo simpanan (pokok/wajib/sukarela), status iuran bulan berjalan, dan jalan pintas menu utama. | FE-B | Screen `Anggota/Dashboard.jsx` |
| **FE-4.2** | **UI Papan Info Harga Sampah Terkini** | Ticker harga live sampah harian, filter kategori, penanda fluktuasi harga naik/turun, informasi potongan jemput armada. | FE-B | Screen `Anggota/PriceBoard.jsx` |
| **FE-4.3** | **UI Riwayat Setor & Nota Digital** | Daftar riwayat transaksi setor sampah: card nota digital interaktif (tanggal, kategori, berat, potongan 20%, saldo diterima) + tombol "Ajukan Komplain". | FE-B | Screen `Anggota/Receipts.jsx` |
| **FE-4.4** | **UI Pengajuan Komplain Nota** | Formulir popup aduan transaksi timbang: pilihan alasan (berat salah/kategori salah), input angka koreksi, unggah foto bukti timbangan. | FE-B | Modal `ComplaintFormModal.jsx` |
| **FE-4.5** | **UI Dompet Saldo & Riwayat Dividen SHU** | Halaman mutasi saldo dompet hasil sampah, form pengajuan penarikan tunai/transfer rekening, dan transparansi rincian perolehan SHU tahunan. | FE-B | Screen `Anggota/WalletAndShu.jsx` |

### BAGIAN FE-5: Dashboard Finansial, SHU & Laporan Eksekutif (FE-A / Sprint 5)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Penanggung Jawab | Deliverable |
|:---:|---|---|:---:|---|
| **FE-5.1** | **UI Mesin Simulasi & Distribusi SHU** | Tampilan tabel draft SHU tahunan: simulasi alokasi 20% laba bersih koperasi, breakdown jasa modal & partisipasi usaha per anggota, tombol "Finalisasi & Bagikan Saldo". | FE-A | Screen `Pengurus/ShuEngine.jsx` |
| **FE-5.2** | **UI Laporan Keuangan & Grafik Analitik** | Visualisasi tren setoran sampah harian/bulanan, grafik pemasukan iuran vs operasional, tombol cetak dan ekspor laporan ke PDF/Excel. | FE-A | Screen `Pengurus/FinancialReports.jsx` |

### BAGIAN FE-6: E2E Testing, Optimasi & Deployment Frontend (Sprint 6)
| Kode Task | Nama Task / Modul | Deskripsi Pekerjaan Teknis | Penanggung Jawab | Deliverable |
|:---:|---|---|:---:|---|
| **FE-6.1** | **Cross-Device UI Testing & Mobile Polish** | Audit responsivitas UI pada resolusi HP (360px-420px) untuk petugas timbang dan resolusi Desktop untuk admin. | FE-A + FE-B | UI Polish Checklist |
| **FE-6.2** | **End-to-End Flow Testing** | Pengujian alur utuh: Petugas input timbang di HP → Saldo di Portal Anggota bertambah seketika → Admin melihat mutasi kas & rekapitulasi. | FS/QA | E2E Test Report |
| **FE-6.3** | **Frontend Production Build & Hosting** | Optimasi bundle (`npm run build`), setup reverse proxy / static hosting (Vercel / Netlify / Nginx SPA fallback to `index.html`). | FS/QA | Frontend Live Production |

---

## 5. Timeline Kolaborasi Antar-Repository (Gantt Chart 2 Repos)

```mermaid
gantt
    title Roadmap Pengerjaan 2 Repository (Laravel Web Service vs React SPA)
    dateFormat  YYYY-MM-DD
    
    section REPO 1: BACKEND (Laravel 11)
    BE-1: Setup Repo, Migrations & Sanctum Auth :crit, be1, 2026-09-07, 2026-09-13
    BE-2: API Anggota, Kategori & Audit Harga    :crit, be2, 2026-09-14, 2026-09-19
    BE-3: API Timbang, Potongan 20% & Nota       :crit, be3, 2026-09-20, 2026-09-27
    BE-4: API Simpanan, Tagihan & Saldo          :be4, 2026-09-28, 2026-10-05
    BE-5: Engine SHU Tahunan & API Laporan       :crit, be5, 2026-10-06, 2026-10-14
    BE-6: Security & Deployment Backend          :be6, 2026-10-15, 2026-10-20
    
    section REPO 2: FRONTEND (React 18 SPA)
    FE-1: Setup Vite, Axios Interceptor & Auth   :crit, fe1, 2026-09-07, 2026-09-14
    FE-2: Portal Admin/Pengurus - Core Master    :fe2, 2026-09-15, 2026-09-24
    FE-3: Portal Petugas - Mobile Form Timbang   :crit, fe3, 2026-09-20, 2026-09-29
    FE-4: Portal Anggota - Papan Info & Nota     :crit, fe4, 2026-09-28, 2026-10-07
    FE-5: UI SHU Distribusi & Laporan Finansial  :fe5, 2026-10-08, 2026-10-15
    FE-6: E2E Integration & Production Deploy   :crit, fe6, 2026-10-16, 2026-10-22
```

---

## 6. Git Workflow & Protokol Integrasi Multi-Repo

1. **Struktur Repository di Git (GitHub / GitLab)**:
   - `https://github.com/organization/koperasi-backend-api.git`
   - `https://github.com/organization/koperasi-frontend-spa.git`
2. **Standard Environment Variables**:
   - **Frontend `.env`**:
     ```env
     VITE_API_BASE_URL=https://api.koperasimulyoraharjo.com/api/v1
     ```
   - **Backend `.env`**:
     ```env
     APP_URL=https://api.koperasimulyoraharjo.com
     SANCTUM_STATEFUL_DOMAINS=app.koperasimulyoraharjo.com
     SESSION_DOMAIN=.koperasimulyoraharjo.com
     ```
3. **Penyelesaian Masalah CORS (Cross-Origin Resource Sharing)**:
   - File `config/cors.php` di repo backend diatur untuk mengizinkan origin dari domain frontend staging (`http://localhost:5173` saat dev, dan domain production frontend saat live).
