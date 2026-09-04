# PANDUAN & SKENARIO PENGUJIAN RESTful API (TESTING.MD)
## Sistem Informasi Koperasi & Bank Sampah "Koperasi Jasa Mulyo Raharjo Lestari"
**Base URL:** `http://localhost:8000/api/v1`  
**Format Payload:** `application/json`  
**Header Wajib:**  
```http
Accept: application/json
Content-Type: application/json
```

---

## 1. Akun Default Pengujian (Hasil Seeder)

| Role | Username / Identity | Password | Deskripsi Akun |
|---|---|---|---|
| **Ketua (Admin)** | `ketua` | `password123` | Akses penuh, approval SHU & manajemen pengguna |
| **Pengurus (Bendahara)** | `bendahara` | `password123` | Kelola simpanan, update harga harian, kasir |
| **Petugas Lapangan** | `petugas` | `password123` | Input timbang sampah di lapangan & jadwal angkut |
| **Anggota (Warga)** | `warga` | `password123` | Cek saldo, lihat nota timbang, klaim SHU, komplain |

---

## 2. Matriks Rencana Skenario Pengujian (Test Matrix)

### Skenario 1: Global Exception Handler (Sesuai `be-architecture.md`)
1. **Test 401 Unauthenticated**:
   - Request ke endpoint terproteksi (`GET /api/v1/auth/me`) tanpa Bearer token.
   - **Ekspektasi:** Status `401 Unauthorized`, response JSON:
     ```json
     {
       "message": "Unauthenticated or invalid token.",
       "errors": null
     }
     ```
2. **Test 404 Route Not Found**:
   - Request ke endpoint tidak terdaftar (`GET /api/v1/unknown-endpoint`).
   - **Ekspektasi:** Status `404 Not Found`, response JSON:
     ```json
     {
       "message": "Endpoint or route not found.",
       "errors": null
     }
     ```
3. **Test 422 Validation Error**:
   - Request login (`POST /api/v1/auth/login`) dengan body kosong `{}`.
   - **Ekspektasi:** Status `422 Unprocessable Content`, response JSON:
     ```json
     {
       "message": "The given data was invalid.",
       "errors": {
         "identity": ["Kolom username atau email wajib diisi."],
         "password": ["Kolom password wajib diisi."]
       }
     }
     ```

---

### Skenario 2: Autentikasi & Penerbitan Token Sanctum
1. **Login Berhasil (`POST /api/v1/auth/login`)**:
   - **Body:**
     ```json
     {
       "identity": "bendahara",
       "password": "password123",
       "device_name": "testing_client"
     }
     ```
   - **Ekspektasi:** Status `200 OK`, mengembalikan plain text bearer token dan data user profil.
2. **Ambil Data Profil Login (`GET /api/v1/auth/me`)**:
   - Header: `Authorization: Bearer <TOKEN>`
   - **Ekspektasi:** Status `200 OK`, data profil user & role `pengurus`.

---

### Skenario 3: Papan Info Harga & Quick-Update Harga Harian
1. **Lihat Papan Harga Publik (`GET /api/v1/trash-categories/board`)**:
   - **Ekspektasi:** Status `200 OK`, menampilkan 22 item sampah lengkap dengan tarif gudang dan pengurangan tarif jemput (-Rp300 non-logam, -Rp2.000 logam).
2. **Update Harga Sampah Harian oleh Bendahara (`PATCH /api/v1/trash-categories/{id}/price`)**:
   - Header: `Authorization: Bearer <TOKEN_BENDAHARA>`
   - **Body:**
     ```json
     {
       "price_sorted": 135000,
       "price_unsorted": 128000,
       "notes": "Penyesuaian info WA paguyuban 4 September"
     }
     ```
   - **Ekspektasi:** Status `200 OK`, harga terupdate dan record tersimpan otomatis di `price_change_logs`.

---

### Skenario 4: Transaksi Timbang Lapangan & Auto-Potongan Koperasi 20%
1. **Petugas Input Timbang Sampah (`POST /api/v1/pickups/{id}/weigh-items`)**:
   - Petugas menimbang 10 kg Tembaga (Harga Rp130.000/kg) dijemput di rumah warga.
   - **Kalkulasi Bisnis:**
     - Tarif per kg (jemput): Rp130.000 − Rp2.000 = Rp128.000/kg.
     - Nilai Kotor: 10 kg × Rp128.000 = Rp1.280.000.
     - Potongan Koperasi 20%: Rp1.280.000 × 20% = **Rp256.000** (masuk kas pendapatan koperasi).
     - Bersih Diterima Anggota: Rp1.280.000 − Rp256.000 = **Rp1.024.000** (kredit langsung ke dompet saldo).
   - **Ekspektasi:** Status `200 OK`, status pickup jadi `selesai`, kas koperasi bertambah Rp256.000, saldo anggota bertambah Rp1.024.000, nota digital terbit.
2. **Cek Nota Digital oleh Warga (`GET /api/v1/pickups/{id}/receipt`)**:
   - **Ekspektasi:** Status `200 OK`, payload nota digital memuat detail berat, potongan 20%, saldo masuk, dan UUID/QR verifikasi.

---

### Skenario 5: Pengaduan / Komplain Transaksi Nota
1. **Anggota Mengajukan Komplain (`POST /api/v1/complaints`)**:
   - Header: `Authorization: Bearer <TOKEN_WARGA>`
   - **Body:**
     ```json
     {
       "pickup_id": 1,
       "issue_type": "berat_salah",
       "description": "Timbangan tercatat 8 kg padahal seharusnya 10 kg",
       "proof_image": null
     }
     ```
   - **Ekspektasi:** Status `201 Created`, status tiket `diajukan`.
2. **Pengurus Menyetujui Revisi (`PATCH /api/v1/complaints/{id}/resolve`)**:
   - Header: `Authorization: Bearer <TOKEN_BENDAHARA>`
   - **Body:**
     ```json
     {
       "status": "diterima",
       "adjustment_amount": 204800,
       "resolution_note": "Disetujui selisih 2 kg timbangan tembaga"
     }
     ```
   - **Ekspektasi:** Status `200 OK`, saldo anggota otomatis disesuaikan bertambah Rp204.800.

---

### Skenario 6: Tagihan Simpanan Bulanan & Penarikan Saldo (Cashout)
1. **Cek Status Iuran Berjalan (`GET /api/v1/savings/billing-status`)**:
   - **Ekspektasi:** Status `200 OK`, menampilkan tagihan Rp45.000 (Wajib Rp5.000 + Tipping Fee Rp40.000).
2. **Anggota Tarik Saldo (`POST /api/v1/wallet/withdraw`)**:
   - Saldo dompet warga Rp1.024.000, warga menarik dana Rp500.000.
   - **Ekspektasi:** Status `201 Created`, status antrean withdraw `menunggu`.
3. **Uji Validasi Penarikan Melebihi Saldo**:
   - Warga mencoba menarik dana Rp2.000.000 (melebihi saldo).
   - **Ekspektasi:** Ditangkap oleh `BusinessLogicException`, status `400 Bad Request`:
     ```json
     {
       "message": "Saldo dompet tidak mencukupi untuk melakukan penarikan nominal tersebut.",
       "errors": null
     }
     ```

---

### Skenario 7: Kalkulasi & Pembagian SHU Tahunan
1. **Simulasi Draft SHU 20% Laba Bersih (`POST /api/v1/shu/simulate`)**:
   - Laba bersih tahunan: Rp50.000.000.
   - Pool 20% SHU: Rp10.000.000.
   - **Ekspektasi:** Status `200 OK`, kalkulasi draft dividen per anggota proporsional berdasarkan jasa modal dan keaktifan setor sampah.
2. **Posting & Bagikan SHU (`POST /api/v1/shu/publish`)**:
   - **Ekspektasi:** Status `200 OK`, saldo dompet seluruh anggota bertambah dividen secara massal.
