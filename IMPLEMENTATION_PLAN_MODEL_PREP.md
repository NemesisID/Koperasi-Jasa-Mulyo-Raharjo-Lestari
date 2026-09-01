# Implementation Plan: Model Preparation (Eloquent Data Fetching)

Dokumen ini berisi rencana implementasi mendetail untuk pembuatan dan konfigurasi 12 Model Eloquent baru serta perbaikan model `User` pendukung di aplikasi **Koperasi Jasa Mulyo Raharjo Lestari**.

---

## 🎯 Tujuan (Goals)
1. Membangun seluruh Eloquent Models sesuai 13 tabel migrasi database.
2. Mendefinisikan relasi antar-tabel (*Relationships*) secara lengkap dan presisi (`belongsTo`, `hasMany`, `hasOne`).
3. Mengatur `$fillable`, `$casts`, dan penanganan Primary Key (Auto-increment BigInt vs UUID String).
4. Menambahkan **Query Scopes** dan **Helper Attributes/Accessors** untuk mempermudah *fetching data* di Controller/Inertia (misalnya: filter status aktif, filter tipe transaksi, kalkulasi total).

---

## 🗂️ Daftar Model yang Akan Dibuat & Diperbarui

| # | Model Class | File Path | Table Name | Key Features & Scopes |
|---|---|---|---|---|
| 1 | `User` | `app/Models/User.php` | `users` | Relasi ke Member, Transactions, Reports, Setoran, DetailPengangkutan |
| 2 | `MemberCategory` | `app/Models/MemberCategory.php` | `member_categories` | Relasi `hasMany(Member)` |
| 3 | `Member` | `app/Models/Member.php` | `members` | Relasi User, Category, Pickups, Transactions, Scopes: `active()` |
| 4 | `TrashCategory` | `app/Models/TrashCategory.php` | `trash_categories` | Relasi PickupItems, Scopes: `active()` |
| 5 | `FinanceCategory` | `app/Models/FinanceCategory.php` | `finance_categories` | Relasi Transactions, Scopes: `income()`, `expense()` |
| 6 | `SetoranKoperasi` | `app/Models/SetoranKoperasi.php` | `setoran_koperasi` | UUID PK, Relasi User, Scopes: `pending()`, `completed()` |
| 7 | `DetailPengangkutan` | `app/Models/DetailPengangkutan.php` | `detail_pengangkutan` | UUID PK, Relasi User, Scopes: `byDateRange()` |
| 8 | `Pickup` | `app/Models/Pickup.php` | `pickups` | Relasi Officer (User), Member, PickupItems, Scopes: `pending()`, `completed()` |
| 9 | `PickupItem` | `app/Models/PickupItem.php` | `pickup_items` | Relasi Pickup, TrashCategory, Transaction |
| 10 | `Transaction` | `app/Models/Transaction.php` | `transactions` | Relasi Member, Category, HandledBy, Scopes: `income()`, `expense()`, `successful()` |
| 11 | `ShuDistribution` | `app/Models/ShuDistribution.php` | `shu_distributions` | Relasi HandledBy, ShuMembers, Scopes: `byYear()` |
| 12 | `ShuMember` | `app/Models/ShuMember.php` | `shu_members` | Relasi ShuDistribution, Member, Transaction |
| 13 | `Report` | `app/Models/Report.php` | `reports` | Relasi CreatedBy, Scopes: `finalized()`, `review()` |

---

## 🛠️ Langkah-Langkah Eksekusi (Proposed Steps)

### Step 1: Update Model `User.php`
- Menambahkan relasi ke model pendukung:
  - `member()`: `HasOne`
  - `setoranKoperasi()`: `HasMany`
  - `detailPengangkutan()`: `HasMany`
  - `handledTransactions()`: `HasMany` (foreignKey: `handled_by`)
  - `assignedPickups()`: `HasMany` (foreignKey: `officer_id`)
  - `createdReports()`: `HasMany` (foreignKey: `created_by`)
  - `handledShuDistributions()`: `HasMany` (foreignKey: `handled_by`)
- Menambahkan Query Scope: `scopeRole($query, $role)`

---

### Step 2: Modul Keanggotaan (`MemberCategory` & `Member`)

#### 2.1 `MemberCategory.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberCategory extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
```

#### 2.2 `Member.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'user_id', 'member_category_id', 'member_code',
        'name', 'address', 'phone', 'status', 'join_date'
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(MemberCategory::class, 'member_category_id'); }
    public function pickups(): HasMany { return $this->hasMany(Pickup::class); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function shuMembers(): HasMany { return $this->hasMany(ShuMember::class); }

    public function scopeActive($query) { return $query->where('status', 'aktif'); }
}
```

---

### Step 3: Modul Kategori Sampah & Keuangan (`TrashCategory` & `FinanceCategory`)

#### 3.1 `TrashCategory.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrashCategory extends Model
{
    protected $fillable = ['name', 'price_sorted', 'price_unsorted', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_sorted' => 'decimal:2',
            'price_unsorted' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function pickupItems(): HasMany { return $this->hasMany(PickupItem::class, 'category_id'); }
    public function scopeActive($query) { return $query->where('is_active', true); }
}
```

#### 3.2 `FinanceCategory.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCategory extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'type', 'group_type'];

    public function transactions(): HasMany { return $this->hasMany(Transaction::class, 'category_id'); }
    public function scopeIncome($query) { return $query->where('type', 'income'); }
    public function scopeExpense($query) { return $query->where('type', 'expense'); }
}
```

---

### Step 4: Modul UUID (`SetoranKoperasi` & `DetailPengangkutan`)

#### 4.1 `SetoranKoperasi.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SetoranKoperasi extends Model
{
    use HasUuids;

    protected $table = 'setoran_koperasi';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['user_id', 'jenis', 'jumlah', 'status', 'label', 'catatan'];

    protected function casts(): array
    {
        return ['jumlah' => 'decimal:2'];
    }

    public function user() { return $this->belongsTo(User::class); }
}
```

#### 4.2 `DetailPengangkutan.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DetailPengangkutan extends Model
{
    use HasUuids;

    protected $table = 'detail_pengangkutan';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'jadwal_angkut', 'total_organik', 'total_anorganik',
        'kecamatan', 'desa', 'dusun', 'rw', 'rt', 'alamat'
    ];

    protected function casts(): array
    {
        return [
            'jadwal_angkut' => 'datetime',
            'total_organik' => 'decimal:2',
            'total_anorganik' => 'decimal:2',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
}
```

---

### Step 5: Modul Bank Sampah (`Pickup` & `PickupItem`)

#### 5.1 `Pickup.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pickup extends Model
{
    protected $fillable = [
        'officer_id', 'member_id', 'is_sorted',
        'scheduled_at', 'completed_at', 'status', 'notes'
    ];

    protected function casts(): array
    {
        return [
            'is_sorted' => 'boolean',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function officer() { return $this->belongsTo(User::class, 'officer_id'); }
    public function member() { return $this->belongsTo(Member::class); }
    public function items() { return $this->hasMany(PickupItem::class); }
}
```

#### 5.2 `PickupItem.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['pickup_id', 'category_id', 'weight_kg', 'total_value', 'deposit_date', 'transaction_id'];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'total_value' => 'decimal:2',
            'deposit_date' => 'datetime',
        ];
    }

    public function pickup() { return $this->belongsTo(Pickup::class); }
    public function category() { return $this->belongsTo(TrashCategory::class, 'category_id'); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
}
```

---

### Step 6: Modul Transaksi Keuangan (`Transaction`)

#### 6.1 `Transaction.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_code', 'member_id', 'category_id', 'type',
        'amount', 'description', 'payment_method', 'status',
        'transaction_date', 'handled_by'
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    public function member() { return $this->belongsTo(Member::class); }
    public function category() { return $this->belongsTo(FinanceCategory::class, 'category_id'); }
    public function officer() { return $this->belongsTo(User::class, 'handled_by'); }
    public function pickupItems() { return $this->hasMany(PickupItem::class); }
    public function shuMembers() { return $this->hasMany(ShuMember::class); }

    public function scopeIncome($query) { return $query->where('type', 'income'); }
    public function scopeExpense($query) { return $query->where('type', 'expense'); }
    public function scopeSuccessful($query) { return $query->where('status', 'berhasil'); }
}
```

---

### Step 7: Modul SHU & Laporan (`ShuDistribution`, `ShuMember`, `Report`)

#### 7.1 `ShuDistribution.php` & `ShuMember.php`
- Menangani kalkulasi SHU tahunan per anggota.
- Relasi `ShuDistribution` -> `hasMany(ShuMember::class)`.
- Relasi `ShuMember` -> `belongsTo(Member::class)` & `belongsTo(Transaction::class)`.

#### 7.2 `Report.php`
- Mengatur laporan keuangan (`saldo`, `laba_rugi`, `simpanan`, `operasional`).
- Scope `scopeFinalized($query)` dan `scopeReview($query)`.

---

## 🧪 Verifikasi & Testing
1. **Model Generation Verification:** Pastikan seluruh file model ter-create di directory `app/Models`.
2. **Relationship Data Fetching Test:** Uji query Eloquent via `php artisan tinker` atau unit test:
   ```php
   // Contoh Uji Fetching Relasi:
   $user = User::with('member.category', 'setoranKoperasi')->first();
   $pickups = Pickup::with(['member', 'items.category', 'officer'])->get();
   $shu = ShuDistribution::with('shuMembers.member')->byYear(2026)->first();
   ```

---

## 📌 Kesimpulan
Dengan mengimplementasikan rencana persiapan model ini, seluruh Controller/Inertia Page akan dapat mengambil data (*data fetching*) secara ringkas, elegan, dan *eager loading* ramah memori (menghindari *N+1 query problem*).
