# Database Design - EMasjid

> Desain database untuk platform EMasjid.
> Dokumen ini menjadi acuan saat membuat migration, model, dan relasi.

---

## Daftar Isi

1. [Prinsip Umum](#1-prinsip-umum)
2. [ERD Overview](#2-erd-overview)
3. [Tabel Platform (Non Mosque-Scoped)](#3-tabel-platform-non-mosque-scoped)
4. [Tabel Mosque-Scoped](#4-tabel-mosque-scoped)
5. [Tabel Pivot / Relasi](#5-tabel-pivot--relasi)
6. [Tabel Spatie Permission](#6-tabel-spatie-permission)
7. [Index Strategy](#7-index-strategy)
8. [Catatan Penting](#8-catatan-penting)

---

## 1. Prinsip Umum

| Aturan | Penjelasan |
|--------|------------|
| Nilai uang | `unsignedBigInteger`, satuan Rupiah (bukan decimal) |
| Status/tipe | `string` di database, PHP enum di model cast |
| Tenant isolation | Semua tabel data masjid wajib punya `mosque_id` |
| Soft delete | Hanya untuk data yang perlu audit trail (mosque, user). Data transaksi tidak di-soft-delete. |
| Timestamp | Selalu pakai `timestamps()` |
| Foreign key | Selalu pakai `constrained()->cascadeOnDelete()` untuk data mosque-scoped |
| ID | Auto-increment `bigIncrements` (default Laravel) |

---

## 2. ERD Overview

```text
┌─────────────────────────────────────────────────────────────────────┐
│                         PLATFORM LEVEL                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────┐     ┌──────────────────┐     ┌───────────────────┐   │
│  │  users   │────▶│  mosque_user     │◀────│     mosques       │   │
│  └──────────┘     │  (pivot)         │     └───────────────────┘   │
│       │           └──────────────────┘            │                  │
│       │                                           │                  │
│       ▼                                           ▼                  │
│  ┌──────────┐                            ┌───────────────────┐      │
│  │fcm_tokens│                            │ platform_settings │      │
│  └──────────┘                            └───────────────────┘      │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     MOSQUE-SCOPED (per masjid)                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌───────────────┐  ┌─────────────────┐  ┌──────────────────────┐  │
│  │   schedules   │  │   activities    │  │   announcements      │  │
│  └───────────────┘  └─────────────────┘  └──────────────────────┘  │
│                                                                      │
│  ┌───────────────────────┐  ┌────────────────────────────────────┐  │
│  │   cash_transactions   │  │         donations                  │  │
│  └───────────────────────┘  └────────────────────────────────────┘  │
│                                                                      │
│  ┌───────────────┐                                                   │
│  │    staffs     │                                                   │
│  └───────────────┘                                                   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Tabel Platform (Non Mosque-Scoped)

### `users`

Semua pengguna (owner, admin masjid, pengurus, jamaah) dalam satu tabel.

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| name               | string(255)         |                            |
| email              | string(255)         | unique                     |
| phone              | string(20)          | nullable                   |
| email_verified_at  | timestamp           | nullable                   |
| password           | string(255)         |                            |
| avatar             | string(255)         | nullable, path to file     |
| active_mosque_id   | foreignId           | nullable, mosque aktif utk jamaah |
| remember_token     | string(100)         | nullable                   |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
| deleted_at         | timestamp           | nullable, soft delete      |
```

### `mosques`

Data masjid yang terdaftar di platform.

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| name               | string(255)         |                            |
| slug               | string(255)         | unique, untuk URL/QR       |
| address            | text                |                            |
| city               | string(100)         |                            |
| province           | string(100)         |                            |
| postal_code        | string(10)          | nullable                   |
| phone              | string(20)          | nullable                   |
| email              | string(255)         | nullable                   |
| description        | text                | nullable                   |
| photo              | string(255)         | nullable, path to file     |
| latitude           | decimal(10,7)       | nullable                   |
| longitude          | decimal(10,7)       | nullable                   |
| bank_name          | string(100)         | nullable                   |
| bank_account_name  | string(255)         | nullable                   |
| bank_account_number| string(50)          | nullable                   |
| qris_image         | string(255)         | nullable, path to file     |
| invitation_code    | string(20)          | unique, untuk join via code |
| status             | string(20)          | enum: pending, active, suspended, rejected |
| admin_user_id      | foreignId           | user yang jadi admin masjid |
| rejection_reason   | text                | nullable                   |
| approved_at        | timestamp           | nullable                   |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
| deleted_at         | timestamp           | nullable, soft delete      |
```

### `platform_settings`

Setting global platform (key-value).

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| key                | string(100)         | unique                     |
| value              | text                |                            |
| description        | string(255)         | nullable, penjelasan       |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

Setting yang diharapkan:

```
platform_fee_percentage = 250          (2.5%, basis poin)
platform_fee_mechanism = added_to_donor (atau deducted_from_donation)
platform_fee_active = 1                (aktif/nonaktif)
```

### `fcm_tokens`

Token FCM untuk push notification ke mobile app.

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| user_id            | foreignId           | FK ke users                |
| token              | text                |                            |
| device_type        | string(20)          | android / ios              |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

---

## 4. Tabel Mosque-Scoped

### `schedules`

Jadwal sholat 5 waktu + Jumat.

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| mosque_id          | foreignId           | FK ke mosques              |
| date               | date                | tanggal berlaku            |
| subuh              | time                |                            |
| subuh_iqomah       | time                | nullable                   |
| dzuhur             | time                |                            |
| dzuhur_iqomah      | time                | nullable                   |
| ashar              | time                |                            |
| ashar_iqomah       | time                | nullable                   |
| maghrib            | time                |                            |
| maghrib_iqomah     | time                | nullable                   |
| isya               | time                |                            |
| isya_iqomah        | time                | nullable                   |
| jumat_time         | time                | nullable                   |
| jumat_khatib       | string(255)         | nullable                   |
| jumat_imam         | string(255)         | nullable                   |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

### `activities`

Kegiatan masjid (kajian, TPA, dll).

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| mosque_id          | foreignId           | FK ke mosques              |
| title              | string(255)         |                            |
| description        | text                | nullable                   |
| speaker            | string(255)         | nullable, pemateri         |
| location           | string(255)         | nullable                   |
| start_date         | date                |                            |
| start_time         | time                | nullable                   |
| end_time           | time                | nullable                   |
| is_recurring       | boolean             | default false              |
| recurrence_note    | string(255)         | nullable, "Setiap Senin"   |
| status             | string(20)          | enum: upcoming, ongoing, completed, cancelled |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

### `cash_transactions`

Pencatatan kas masjid (pemasukan & pengeluaran).

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| mosque_id          | foreignId           | FK ke mosques              |
| type               | string(20)          | enum: income, expense      |
| amount             | unsignedBigInteger  | dalam Rupiah               |
| description        | string(255)         |                            |
| category           | string(100)         | nullable                   |
| transaction_date   | date                |                            |
| notes              | text                | nullable                   |
| recorded_by        | foreignId           | nullable, FK ke users      |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

### `donations`

Donasi online dari jamaah ke masjid.

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| mosque_id          | foreignId           | FK ke mosques              |
| user_id            | foreignId           | FK ke users (donatur)      |
| category           | string(20)          | enum: infaq, zakat, sadaqah, waqf |
| amount             | unsignedBigInteger  | nominal donasi asli        |
| fee_amount         | unsignedBigInteger  | fee platform               |
| payment_amount     | unsignedBigInteger  | total yang dibayar jamaah  |
| mosque_receives    | unsignedBigInteger  | yang diterima masjid       |
| fee_mechanism      | string(30)          | added_to_donor / deducted_from_donation |
| status             | string(20)          | enum: pending, confirmed, failed, expired |
| is_anonymous       | boolean             | default false              |
| merchant_order_id  | string(50)          | unique, ID order Duitku    |
| payment_url        | text                | nullable, URL pembayaran   |
| reference          | string(100)         | nullable, ref dari Duitku  |
| payment_method     | string(50)          | nullable, QRIS/VA/etc      |
| confirmed_at       | timestamp           | nullable                   |
| expired_at         | timestamp           | nullable                   |
| notes              | text                | nullable                   |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

### `announcements`

Pengumuman masjid.

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| mosque_id          | foreignId           | FK ke mosques              |
| title              | string(255)         |                            |
| content            | text                |                            |
| image              | string(255)         | nullable, path to file     |
| status             | string(20)          | enum: draft, published, archived |
| published_at       | timestamp           | nullable                   |
| published_by       | foreignId           | nullable, FK ke users      |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

### `staffs`

Pengurus masjid (tracking siapa yang jadi pengurus dan jabatannya).

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| mosque_id          | foreignId           | FK ke mosques              |
| user_id            | foreignId           | FK ke users                |
| position           | string(100)         | jabatan: Ketua DKM, Bendahara, dll |
| is_active          | boolean             | default true               |
| joined_at          | date                | nullable                   |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

---

## 5. Tabel Pivot / Relasi

### `mosque_user`

Relasi many-to-many: jamaah yang join masjid.

```
| Column             | Type                | Notes                      |
|--------------------|---------------------|----------------------------|
| id                 | bigIncrements       | PK                         |
| mosque_id          | foreignId           | FK ke mosques              |
| user_id            | foreignId           | FK ke users                |
| joined_at          | timestamp           | kapan join                 |
| created_at         | timestamp           |                            |
| updated_at         | timestamp           |                            |
```

**Unique constraint:** `mosque_id` + `user_id`

---

## 6. Tabel Spatie Permission

Tabel ini di-generate oleh Spatie laravel-permission. Yang perlu diperhatikan:

```
| Tabel              | Catatan                                      |
|--------------------|----------------------------------------------|
| permissions        | Daftar permission (name, guard_name)         |
| roles              | Daftar role (name, guard_name)               |
| model_has_roles    | User punya role (with team_id = mosque_id)   |
| model_has_permissions | User punya permission langsung            |
| role_has_permissions  | Role punya permission                     |
```

### Config Spatie yang Penting

```php
// config/permission.php
'teams' => true,
'team_foreign_key' => 'mosque_id',
```

Artinya:
- Satu user bisa punya role berbeda di masjid berbeda
- Permission check selalu dalam konteks `mosque_id` aktif
- `super-admin` role tidak terikat mosque (platform-level)

---

## 7. Index Strategy

### Index yang Wajib

```php
// mosques
$table->index('status');
$table->index('city');
$table->index(['latitude', 'longitude']); // untuk nearby search

// cash_transactions
$table->index(['mosque_id', 'type', 'transaction_date']);
$table->index(['mosque_id', 'transaction_date']);

// donations
$table->index(['mosque_id', 'status']);
$table->index(['user_id', 'status']);
$table->index('merchant_order_id'); // unique sudah jadi index

// announcements
$table->index(['mosque_id', 'status', 'published_at']);

// schedules
$table->index(['mosque_id', 'date']);

// activities
$table->index(['mosque_id', 'status', 'start_date']);

// mosque_user
$table->unique(['mosque_id', 'user_id']);

// fcm_tokens
$table->index('user_id');

// staffs
$table->unique(['mosque_id', 'user_id']);
```

---

## 8. Catatan Penting

### Relasi Utama

```text
User
├── hasMany: Donations (sebagai donatur)
├── hasMany: FcmTokens
├── belongsToMany: Mosques (via mosque_user, sebagai jamaah)
├── hasOne: Staff (per mosque)
└── hasOne: Mosque (sebagai admin, via mosques.admin_user_id)

Mosque
├── belongsTo: User (admin_user_id)
├── hasMany: Schedules
├── hasMany: Activities
├── hasMany: CashTransactions
├── hasMany: Donations
├── hasMany: Announcements
├── hasMany: Staffs
└── belongsToMany: Users (via mosque_user, jamaah)

Donation
├── belongsTo: Mosque
└── belongsTo: User (donatur)

CashTransaction
├── belongsTo: Mosque
└── belongsTo: User (recorded_by)

Announcement
├── belongsTo: Mosque
└── belongsTo: User (published_by)
```

### Catatan Fee Donasi

Kolom di tabel `donations` menangkap snapshot fee saat transaksi dibuat:

- `amount` = nominal yang diinginkan donatur
- `fee_amount` = fee platform (dihitung saat create)
- `payment_amount` = total yang dibayar (bisa = amount + fee, atau = amount)
- `mosque_receives` = yang diterima masjid (bisa = amount, atau = amount - fee)
- `fee_mechanism` = snapshot mekanisme fee saat itu

Ini penting karena setting fee bisa berubah kapan saja, tapi donasi lama harus tetap akurat.

### Kapan Pakai Soft Delete

| Tabel | Soft Delete? | Alasan |
|-------|--------------|--------|
| users | ✅ | Jamaah hapus akun, tapi perlu audit trail |
| mosques | ✅ | Masjid dinonaktifkan, data perlu dipertahankan sementara |
| cash_transactions | ❌ | Hapus = hapus permanen (dengan konfirmasi) |
| donations | ❌ | Tidak boleh dihapus (audit trail keuangan) |
| announcements | ❌ | Diarsipkan via status, bukan delete |
| schedules | ❌ | Overwrite, bukan delete |
| activities | ❌ | Status cancelled, bukan delete |

### Anonimisasi Saat Hapus Akun

Ketika jamaah hapus akun:
1. Soft delete user record
2. Hapus `fcm_tokens`
3. Hapus `mosque_user` entries
4. Donasi tetap ada, tapi `user_id` di-set null atau user name diganti "Anonim"
5. Hapus data personal (email, phone) dari user record

---

## Penutup

Dokumen ini harus di-update setiap kali ada migration baru yang mengubah struktur tabel. Pastikan ERD dan penjelasan tetap sinkron dengan kondisi database aktual.
