# Panduan Pengujian API E-Commerce & CMS (Scalar Docs)

Panduan ini berisi cara melakukan pengujian endpoint API melalui UI Dokumentasi Scalar di:
👉 **[http://127.0.0.1:8000/be/docs/api](http://127.0.0.1:8000/be/docs/api)**

---

## 1. Menjalankan Semua Seeder
Jika database di-reset atau database baru:
```bash
php artisan migrate:fresh --seed
```
Atau jalankan seeder spesifik per modul:
```bash
php artisan db:seed --class=ComproContentSeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=SiteSettingSeeder
php artisan db:seed --class=ShippingRateSeeder
php artisan db:seed --class=OrderSeeder
```

---

## 2. Struktur Data CMS (`compro_contents`)
Tabel `compro_contents` memiliki kolom:
* `page`: `welcome` (halaman landing page SPA)
* `section`: `hero`, `about`, `institusi`, `timeline`, `instagram`, `services`
* `key`: `judul`, `deskripsi`, `background_image`, `video_url`, `daftar_baris_1`, `daftar_baris_2`, `daftar`, `posts`, dsb.
* `type`: `text`, `richtext`, `image`, `url`, `repeater`
* `value`: nilai teks atau array JSON (untuk tipe `repeater`)
* `order`: nomor urutan tampilan (1, 2, 3, ...)

---

## 3. Daftar Endpoint & Parameter Pengujian di Scalar UI

### A. Konten Halaman Landing Page SPA (`GET /api/v1/cms/pages/{slug}`)
* **Method**: `GET`
* **URL**: `/api/v1/cms/pages/{slug}`
* **Parameter `slug`**:
  - `home` atau `welcome`
* **Status**: `200 OK`
* **Respons**: Mengelompokkan seluruh section (`hero`, `about`, `institusi`, `timeline`, `instagram`, `services`) berserta isi field dan item terurai untuk konsumsi Single Page Application (SPA).

---

### B. Konten Granular / Per Section (`GET /api/v1/cms/contents`)
* **Method**: `GET`
* **URL**: `/api/v1/cms/contents`
* **Query Parameters (Opsional)**:
  - `page`: `welcome` (default)
  - `section`: `hero` (atau `about`, `institusi`, `timeline`, `instagram`, `services`)
* **Status**: `200 OK`
* **Respons**: Menampilkan data grouped per section dan array `raw` sesuai format baris `compro_contents`.

---

### C. Detail Produk Berdasarkan Slug (`GET /api/v1/products/{slug}`)
> ⚠️ **Catatan Penting**: Parameter `{slug}` diisi dengan **slug teks produk**, BUKAN ID angka `1`.
* **Method**: `GET`
* **URL**: `/api/v1/products/{slug}`
* **Pilihan `slug` yang tersedia dari seeder**:
  1. `pupuk-kompos-premium-5kg`
  2. `pupuk-kompos-premium-1-5kg`
  3. `pupuk-organik-cair-poc-biourine-1-liter`
  4. `maggot-bsf-kering-500g`
  5. `tepung-maggot-super-protein-1kg`
  6. `tas-anyaman-daur-ulang-plastik-eco-tote`
  7. `pot-bunga-estetik-daur-ulang-galon-mini`
  8. `ember-komposter-rumah-tangga-20l`
* **Status**: `200 OK`
* **Respons**: Menampilkan info produk lengkap (nama, berat gram, stok, kategori) dan array tier harga (retail & distributor).

---

### D. Detail Pesanan (`GET /api/v1/orders/{id}`)
* **Method**: `GET`
* **URL**: `/api/v1/orders/{id}`
* **Parameter `id` (Nomor Pesanan) yang tersedia dari seeder**:
  1. `ORD-20261019-ABCDEF` *(Status: `pending` / Menunggu Pembayaran)*
  2. `ORD-20261019-XYZ789` *(Status: `processing` / Sudah Dibayar)*
* **Status**: `200 OK`

---

### E. Nota Digital Pesanan (`GET /api/v1/orders/{id}/invoice`)
* **Method**: `GET`
* **URL**: `/api/v1/orders/{id}/invoice`
* **Parameter `id` (Nomor Pesanan) yang tersedia**:
  - `ORD-20261019-ABCDEF`
  - `ORD-20261019-XYZ789`
* **Status**: `200 OK`

---

### F. Pengaturan Kontak & Website (`GET /api/v1/cms/site-config`)
* **Method**: `GET`
* **URL**: `/api/v1/cms/site-config`
* **Status**: `200 OK`
* **Respons**: Mengembalikan konfigurasi kontak, WhatsApp, dan nomor rekening pembayaran (Mandiri, BRI, Bank Jatim).

---

### G. Kalkulasi Ongkir / Checkout Preview (`POST /api/v1/checkout/preview`)
* **Method**: `POST`
* **URL**: `/api/v1/checkout/preview`
* **Headers**: `Accept: application/json`, `Content-Type: application/json`
* **Contoh Request Body**:
```json
{
  "shipping_province": "Jawa Timur",
  "shipping_city": "Malang",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```
* **Status**: `200 OK`
