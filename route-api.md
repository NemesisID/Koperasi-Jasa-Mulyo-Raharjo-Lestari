# DAFTAR SPESIFIKASI ROUTE RESTful API (Web Service Backend)
## Sistem Informasi Koperasi & Bank Sampah "Koperasi Jasa Mulyo Raharjo Lestari"
**Base URL:** `https://api.koperasimulyoraharjo.com/api/v1`  
**Autentikasi:** Bearer Token (`Authorization: Bearer <sanctum_token>`)  
**Header Wajib:**  
- `Accept: application/json`  
- `Content-Type: application/json`  
**Format Respon Standar:**
```json
{
  "success": true,
  "message": "Deskripsi pesan",
  "data": { ... },
  "meta": { "total": 100, "page": 1, "per_page": 15 } // Opsional untuk pagination
}
```

---

## 1. Modul Autentikasi & Profil Pengguna (`/auth`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `POST` | `/auth/login` | Public | **Body:** `{ "identity": "username_atau_email", "password": "xxx", "device_name": "react_spa" }`<br>Autentikasi & return Sanctum token. | `200 OK`<br>`token`, `user`, `role` |
| `POST` | `/auth/register-member` | Public | **Body:** `{ "name", "email", "username", "password", "phone", "address", "nik" }`<br>Registrasi mandiri calon anggota (status awal pending simpanan pokok). | `201 Created`<br>`user`, `member` |
| `GET` | `/auth/me` | All Authenticated | Ambil profil pengguna yang sedang login beserta data role & identitas member. | `200 OK`<br>`user`, `member_profile` |
| `PUT` | `/auth/profile` | All Authenticated | **Body:** `{ "name", "phone", "address", "avatar" }`<br>Pembaruan informasi kontak profil mandiri. | `200 OK`<br>`user` |
| `PUT` | `/auth/change-password` | All Authenticated | **Body:** `{ "current_password", "new_password", "new_password_confirmation" }` | `200 OK` |
| `POST` | `/auth/logout` | All Authenticated | Cabut (*revoke*) Sanctum personal access token saat ini. | `200 OK` |

---

## 2. Modul Manajemen Pengguna & Hak Akses (`/users`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/users` | Ketua, Pengurus | Ambil daftar pengguna sistem. Filter: `?role=petugas&search=budi&page=1`. | `200 OK` (Paginated) |
| `POST` | `/users` | Ketua | **Body:** `{ "name", "username", "email", "password", "role", "address" }`<br>Buat akun internal (Pengurus/Petugas). | `201 Created` |
| `GET` | `/users/{id}` | Ketua, Pengurus | Detail informasi akun pengguna. | `200 OK` |
| `PUT` | `/users/{id}` | Ketua | **Body:** `{ "name", "email", "role", "address", "is_active" }`<br>Update data akun pengguna internal. | `200 OK` |
| `DELETE` | `/users/{id}` | Ketua | Nonaktifkan / hapus akun pengguna sistem. | `200 OK` |

---

## 3. Modul Master Data Keanggotaan (`/members` & `/member-categories`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/member-categories` | Ketua, Pengurus, Petugas | Ambil daftar kategori anggota (Biasa, Inti, Mitra). | `200 OK` |
| `POST` | `/member-categories` | Ketua, Pengurus | **Body:** `{ "name": "Anggota Inti" }` | `201 Created` |
| `GET` | `/members` | Ketua, Pengurus, Petugas | Ambil daftar anggota. Query: `?status=aktif&category_id=1&search=nama/kode`. | `200 OK` (Paginated) |
| `POST` | `/members` | Ketua, Pengurus | **Body:** `{ "user_id", "member_category_id", "name", "address", "phone", "join_date" }`<br>Sistem men-generate `member_code` otomatis (`MBR-202609-XXXX`). | `201 Created` |
| `GET` | `/members/{id}` | Ketua, Pengurus, Petugas, Anggota (Own) | Detail profil anggota, status simpanan pokok, dan rekap partisipasi bank sampah. | `200 OK` |
| `PUT` | `/members/{id}` | Ketua, Pengurus | Update informasi profil anggota. | `200 OK` |
| `PATCH` | `/members/{id}/status` | Ketua, Pengurus | **Body:** `{ "status": "aktif" / "nonaktif" / "suspend", "notes": "alasan" }` | `200 OK` |

---

## 4. Modul Katalog Sampah & Manajemen Harga (`/trash-categories`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/trash-categories` | All (Termasuk Public/Warga) | Ambil seluruh katalog sampah. Query: `?type=anorganik/organik/campur&is_active=1`. | `200 OK` |
| `GET` | `/trash-categories/board` | All (Termasuk Public/Warga) | **Papan Info Harga Terkini**: Menampilkan harga sampah hari ini, fluktuasi naik/turun, dan tarif antar gudang vs jemput. | `200 OK` |
| `POST` | `/trash-categories` | Ketua, Pengurus | **Body:** `{ "name", "type", "unit", "price_sorted", "price_unsorted", "price_pickup_deduction", "is_active" }` | `201 Created` |
| `GET` | `/trash-categories/{id}` | All Authenticated | Detail kategori sampah tertentu. | `200 OK` |
| `PUT` | `/trash-categories/{id}` | Ketua, Pengurus | Pembaruan data kategori sampah. | `200 OK` |
| `PATCH` | `/trash-categories/{id}/price` | Bendahara, Pengurus | **Quick Update Harga Harian:**<br>**Body:** `{ "price_sorted": 5000, "price_unsorted": 2200, "notes": "Update WA paguyuban" }`<br>Otomatis mencatat log audit perubahan harga. | `200 OK` |
| `GET` | `/trash-categories/{id}/price-history` | Ketua, Pengurus | Ambil riwayat audit fluktuasi harga sampah (siapa yang ubah, nominal lama, nominal baru, tanggal). | `200 OK` |

---

## 5. Modul Operasional Bank Sampah: Penjemputan & Timbang (`/pickups`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/pickups` | Ketua, Pengurus, Petugas | Ambil daftar order/jadwal penjemputan sampah. Query: `?status=menunggu/selesai&date=YYYY-MM-DD`. | `200 OK` (Paginated) |
| `POST` | `/pickups` | Petugas, Anggota, Pengurus | **Buat Tiket Setor/Jemput Sampah:**<br>**Body:** `{ "member_id", "scheduled_at", "location_type": "gudang" / "jemput_rumah", "notes" }` | `201 Created`<br>`pickup_id` |
| `POST` | `/pickups/{id}/weigh-items` | Petugas, Pengurus | **Input Timbangan & Kalkulasi Real-time:**<br>**Body:** `{ "items": [ { "category_id": 1, "weight_kg": 15.5, "unit_count": 0 }, { "category_id": 12, "weight_kg": 0, "unit_count": 2 } ] }`<br>**Business Logic Otomatis:**<br>1. Menghitung nilai bruto berdasarkan tarif hari ini.<br>2. Menghitung potongan 20% biaya koperasi.<br>3. Saldo bersih langsung dikreditkan ke saldo anggota.<br>4. Status pickup berubah jadi `selesai`. | `200 OK`<br>`pickup`, `items`, `net_earned`, `receipt_number` |
| `GET` | `/pickups/{id}` | Ketua, Pengurus, Petugas, Anggota (Own) | Detail transaksi penjemputan & rincian item timbangan. | `200 OK` |
| `GET` | `/pickups/{id}/receipt` | All Authenticated | **Payload Nota Digital:** Detail data nota timbang, QR Code URL, nominal kotor, potongan 20%, saldo bersih masuk. | `200 OK` |
| `PATCH` | `/pickups/{id}/cancel` | Ketua, Pengurus | Batalkan transaksi timbangan jika terjadi kesalahan fatal (rollback saldo otomatis). | `200 OK` |

---

## 6. Modul Pengaduan & Komplain Transaksi (`/complaints`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/complaints` | Ketua, Pengurus | Daftar seluruh komplain dari anggota. Filter: `?status=diajukan/proses/selesai`. | `200 OK` (Paginated) |
| `POST` | `/complaints` | Anggota | **Ajukan Komplain Nota:**<br>**Body:** `{ "pickup_id", "issue_type": "berat_salah" / "kategori_salah" / "harga_salah", "description", "proof_image" }` | `201 Created` |
| `GET` | `/complaints/{id}` | Ketua, Pengurus, Anggota (Own) | Detail komplain beserta lampiran foto dan riwayat percakapan/catatan. | `200 OK` |
| `PATCH` | `/complaints/{id}/resolve` | Ketua, Pengurus | **Resolusi Komplain:**<br>**Body:** `{ "status": "diterima" / "ditolak", "adjustment_amount": 15000, "resolution_note": "Revisi berat timbangan tembaga" }`<br>Jika diterima: otomatis update mutasi kas dan saldo anggota terkait. | `200 OK` |

---

## 7. Modul Keuangan Koperasi & Simpanan (`/savings` & `/transactions`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/finance-categories` | Ketua, Pengurus | Ambil daftar kategori keuangan (`income`, `expense`, `simpanan_pokok`, `tipping_fee`, dll). | `200 OK` |
| `POST` | `/finance-categories` | Ketua | Tambah kategori akun keuangan kas baru. | `201 Created` |
| `GET` | `/savings` | Ketua, Pengurus | Rekap simpanan seluruh anggota. Query: `?type=POKOK/WAJIB/SUKARELA/TIPPING&status=PENDING/SELESAI`. | `200 OK` (Paginated) |
| `POST` | `/savings/pay` | Pengurus, Bendahara | **Pencatatan Pembayaran Simpanan / Tipping Fee:**<br>**Body:** `{ "user_id", "member_id", "label": "POKOK" / "WAJIB" / "TIPPING" / "SUKARELA", "jumlah": 45000, "metode": "tunai" / "transfer", "catatan" }` | `201 Created` |
| `GET` | `/savings/billing-status` | Ketua, Pengurus, Anggota (Own) | Status tagihan iuran bulanan berjalan (Rp45.000: Rp5.000 wajib + Rp40.000 tipping) per anggota. | `200 OK` |
| `POST` | `/savings/generate-monthly-billing` | Ketua, Pengurus | Trigger pembuatan tagihan massal bulanan untuk semua anggota aktif (biasanya dipicu Scheduler cron bulanan). | `200 OK` |
| `GET` | `/transactions` | Ketua, Pengurus | Jurnal umum mutasi kas koperasi (pemasukan & pengeluaran). Filter: `?type=income/expense&start_date=&end_date=`. | `200 OK` (Paginated) |
| `POST` | `/transactions` | Ketua, Pengurus | **Pencatatan Transaksi Kas Keluar/Masuk Umum:**<br>**Body:** `{ "category_id", "type": "income" / "expense", "amount", "description", "payment_method": "tunai" / "transfer" }` | `201 Created` |

---

## 8. Modul Dompet Saldo Anggota & Penarikan Dana (`/wallet`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/wallet/summary` | Anggota (Own) | Ringkasan saldo anggota saat ini (akumulasi hasil sampah + dividen SHU). | `200 OK`<br>`current_balance`, `total_withdrawn` |
| `GET` | `/wallet/mutations` | Anggota (Own), Pengurus | Riwayat mutasi saldo masuk (setor sampah, SHU) dan saldo keluar (penarikan tunai). | `200 OK` (Paginated) |
| `POST` | `/wallet/withdraw` | Anggota | **Pengajuan Tarik Saldo:**<br>**Body:** `{ "amount": 100000, "method": "tunai" / "transfer", "bank_name", "account_number", "account_holder" }` | `201 Created`<br>`withdraw_request_id` |
| `GET` | `/wallet/withdraw-requests` | Ketua, Pengurus | Daftar antrean permintaan penarikan saldo dari seluruh anggota. | `200 OK` (Paginated) |
| `PATCH` | `/wallet/withdraw-requests/{id}/approve` | Bendahara, Ketua | **Konfirmasi Eksekusi Penarikan Dana:**<br>**Body:** `{ "action": "approve" / "reject", "proof_file", "notes" }`<br>Mencatat transaksi pengeluaran kas koperasi & mengurangi saldo anggota. | `200 OK` |

---

## 9. Modul Mesin Kalkulasi & Pembagian SHU (`/shu`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/shu/periods` | Ketua, Pengurus | Riwayat periode pembagian SHU tahunan. | `200 OK` |
| `POST` | `/shu/simulate` | Ketua, Pengurus | **Simulasi Draft SHU Tahunan:**<br>**Body:** `{ "year": 2026, "net_profit": 50000000, "shu_pool_percentage": 20 }`<br>Menghitung preview dividen per anggota (berdasarkan bobot simpanan + partisipasi sampah). | `200 OK`<br>`simulation_result`, `total_distributed` |
| `POST` | `/shu/publish` | Ketua | **Finalisasi & Posting SHU:**<br>**Body:** `{ "shu_distribution_id": 1 }`<br>Mengunci draft SHU, mengkreditkan porsi dana ke saldo masing-masing anggota secara massal. | `200 OK` |
| `GET` | `/shu/my-history` | Anggota (Own) | Riwayat penerimaan dividen SHU tahunan beserta rincian perhitungannya (jasa modal vs jasa anggota). | `200 OK` |

---

## 10. Modul Logistik Wilayah & Rekapitulasi Pengangkutan (`/logistics`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/logistics/routes` | Ketua, Pengurus, Petugas | Log ritase armada pengangkutan per wilayah/desa/RT/RW (`detail_pengangkutan`). | `200 OK` (Paginated) |
| `POST` | `/logistics/routes` | Petugas, Pengurus | **Body:** `{ "jadwal_angkut", "total_organik", "total_anorganik", "kecamatan", "desa", "dusun", "rw", "rt", "alamat" }` | `201 Created` |

---

## 11. Modul Laporan Eksekutif & Analitik (`/reports`)

| Method | Endpoint | Role Akses | Deskripsi & Payload Kunci | Response / Status |
|:---:|---|:---:|---|:---:|
| `GET` | `/reports/dashboard-stats` | Ketua, Pengurus | Widget angka statistik: Total Kas, Saldo Mengendap, Tonase Sampah Bulan Ini, Jumlah Anggota Aktif. | `200 OK` |
| `GET` | `/reports/financial` | Ketua, Pengurus | Laporan Keuangan Neraca & Laba Rugi periode tertentu: `?period_start=YYYY-MM-DD&period_end=YYYY-MM-DD`. | `200 OK` |
| `GET` | `/reports/trash-volume` | Ketua, Pengurus | Rekapitulasi volume tonase sampah per kategori dan wilayah RT/RW. | `200 OK` |
| `GET` | `/reports/export` | Ketua, Pengurus | Download berkas laporan resmi yang telah difinalisasi dalam format PDF atau XLSX: `?report_id=1&type=pdf`. | `200 OK` (Binary File) |

---

## 12. Kode Status HTTP & Format Kesalahan Validasi

Jika terjadi kesalahan input atau otorisasi, Web Service Backend merespon dengan format JSON standar:

```json
// Contoh 422 Unprocessable Entity (Validasi Form)
{
  "success": false,
  "message": "Validasi formulir penimbangan gagal.",
  "errors": {
    "items.0.weight_kg": [
      "Berat timbangan wajib diisi angka lebih besar dari 0."
    ],
    "member_id": [
      "Anggota tidak aktif atau belum melunasi simpanan pokok."
    ]
  }
}

// Contoh 403 Forbidden (Hak Akses Ditolak)
{
  "success": false,
  "message": "Akses ditolak. Anda tidak memiliki izin untuk mengubah harga harian sampah.",
  "errors": null
}
```
