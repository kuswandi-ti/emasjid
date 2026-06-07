# API Contract - EMasjid

> Kontrak API untuk komunikasi antara Flutter mobile app dan Laravel backend.
> Dokumen ini menjadi acuan bagi frontend (Flutter) dan backend (Laravel) developer agar bisa bekerja paralel.

---

## Daftar Isi

1. [Informasi Umum](#1-informasi-umum)
2. [Authentication](#2-authentication)
3. [Response Format](#3-response-format)
4. [Pagination](#4-pagination)
5. [Mosque](#5-mosque)
6. [Schedule](#6-schedule)
7. [Announcement](#7-announcement)
8. [Finance (Read Only)](#8-finance-read-only)
9. [Donation](#9-donation)
10. [Congregation (Profile)](#10-congregation-profile)
11. [Notification](#11-notification)

---

## 1. Informasi Umum

### Base URL

```text
Production:  https://emasjid.id/api/v1
Staging:     https://staging.emasjid.id/api/v1
```

### Headers Wajib

```text
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}    (kecuali endpoint publik)
```

### Versioning

API menggunakan prefix URL (`/api/v1/`). Breaking changes akan menggunakan versi baru (`/api/v2/`).

### Rate Limiting

```text
Authenticated: 60 requests/menit
Unauthenticated: 20 requests/menit
```

---

## 2. Authentication

### Register

```
POST /auth/register
```

**Request Body:**

```json
{
    "name": "Ahmad Fauzi",
    "email": "ahmad@example.com",
    "phone": "081234567890",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Success Response (201):**

```json
{
    "success": true,
    "message": "Registrasi berhasil.",
    "data": {
        "user": {
            "id": 1,
            "name": "Ahmad Fauzi",
            "email": "ahmad@example.com",
            "phone": "081234567890"
        },
        "token": "1|abc123xyz..."
    }
}
```

### Login

```
POST /auth/login
```

**Request Body:**

```json
{
    "email": "ahmad@example.com",
    "password": "password123"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Login berhasil.",
    "data": {
        "user": {
            "id": 1,
            "name": "Ahmad Fauzi",
            "email": "ahmad@example.com",
            "phone": "081234567890",
            "avatar_url": null
        },
        "token": "2|def456uvw..."
    }
}
```

**Error Response (401):**

```json
{
    "success": false,
    "message": "Email atau password salah."
}
```

### Logout

```
POST /auth/logout
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Logout berhasil."
}
```

### Refresh Token

```
POST /auth/refresh
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "token": "3|ghi789rst..."
    }
}
```

---

## 3. Response Format

### Success Response

```json
{
    "success": true,
    "message": "Optional success message",
    "data": { }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Human-readable error message",
    "errors": {
        "field_name": ["Pesan error spesifik per field."]
    }
}
```

### HTTP Status Codes

| Code | Penggunaan |
|------|------------|
| 200 | Success (GET, PUT, PATCH) |
| 201 | Created (POST) |
| 204 | No Content (DELETE) |
| 400 | Bad Request |
| 401 | Unauthenticated |
| 403 | Forbidden (tidak punya akses) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

---

## 4. Pagination

Semua endpoint yang mengembalikan list menggunakan cursor-based atau offset pagination.

### Request Parameters

```text
?page=1&per_page=15
```

### Response Format

```json
{
    "success": true,
    "data": [...],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 73
    },
    "links": {
        "first": "/api/v1/resource?page=1",
        "last": "/api/v1/resource?page=5",
        "prev": null,
        "next": "/api/v1/resource?page=2"
    }
}
```

---

## 5. Mosque

### List Mosques (Search & Discovery)

```
GET /mosques?search={query}&lat={latitude}&lng={longitude}&radius={km}&page=1&per_page=15
```

Semua parameter opsional. Jika `lat` dan `lng` diberikan, hasil diurutkan berdasarkan jarak.

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Masjid Al-Ikhlas",
            "address": "Jl. Merdeka No. 10, Jakarta",
            "city": "Jakarta",
            "photo_url": "https://emasjid.id/storage/mosques/1/photo.jpg",
            "latitude": -6.1751,
            "longitude": 106.8650,
            "distance_km": 1.2,
            "congregation_count": 150,
            "is_joined": false
        }
    ],
    "meta": { ... }
}
```

### Get Mosque Detail

```
GET /mosques/{id}
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Masjid Al-Ikhlas",
        "address": "Jl. Merdeka No. 10, Jakarta",
        "city": "Jakarta",
        "description": "Masjid bersejarah yang berdiri sejak 1985...",
        "photo_url": "https://emasjid.id/storage/mosques/1/photo.jpg",
        "photos": [
            "https://emasjid.id/storage/mosques/1/photo1.jpg",
            "https://emasjid.id/storage/mosques/1/photo2.jpg"
        ],
        "latitude": -6.1751,
        "longitude": 106.8650,
        "phone": "021-1234567",
        "email": "alikhlas@example.com",
        "congregation_count": 150,
        "is_joined": true,
        "joined_at": "2026-01-15T10:30:00.000Z"
    }
}
```

### Join Mosque

```
POST /mosques/{id}/join
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Berhasil bergabung dengan masjid."
}
```

### Leave Mosque

```
POST /mosques/{id}/leave
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Berhasil keluar dari masjid."
}
```

### My Mosques (Joined)

```
GET /my/mosques
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Masjid Al-Ikhlas",
            "address": "Jl. Merdeka No. 10, Jakarta",
            "photo_url": "https://emasjid.id/storage/mosques/1/photo.jpg",
            "joined_at": "2026-01-15T10:30:00.000Z",
            "is_active": true
        }
    ]
}
```

### Switch Active Mosque

```
POST /my/mosques/{id}/activate
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Masjid aktif berhasil diubah.",
    "data": {
        "active_mosque_id": 1
    }
}
```

### Join via QR / Invitation Link

```
POST /mosques/join-by-code
```

**Request Body:**

```json
{
    "code": "ABC123XYZ"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Berhasil bergabung dengan Masjid Al-Ikhlas.",
    "data": {
        "mosque_id": 1,
        "mosque_name": "Masjid Al-Ikhlas"
    }
}
```

---

## 6. Schedule

### Get Prayer Schedule (Active Mosque)

```
GET /mosques/{mosque_id}/schedules/prayer
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "date": "2026-06-07",
        "schedules": [
            { "name": "Subuh", "time": "04:35", "iqomah": "04:45" },
            { "name": "Dzuhur", "time": "12:05", "iqomah": "12:15" },
            { "name": "Ashar", "time": "15:20", "iqomah": "15:30" },
            { "name": "Maghrib", "time": "18:05", "iqomah": "18:10" },
            { "name": "Isya", "time": "19:15", "iqomah": "19:25" }
        ],
        "jumat": {
            "khatib": "Ustadz Ahmad",
            "imam": "Ustadz Budi",
            "time": "12:00"
        }
    }
}
```

### Get Activities

```
GET /mosques/{mosque_id}/activities?status=upcoming&page=1&per_page=15
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Kajian Rutin Ba'da Maghrib",
            "description": "Kajian kitab Riyadhus Shalihin",
            "speaker": "Ustadz Ahmad",
            "start_date": "2026-06-08",
            "start_time": "18:30",
            "end_time": "19:30",
            "location": "Ruang utama masjid",
            "is_recurring": true,
            "recurrence": "Setiap Senin",
            "status": "upcoming"
        }
    ],
    "meta": { ... }
}
```

---

## 7. Announcement

### List Announcements

```
GET /mosques/{mosque_id}/announcements?page=1&per_page=15
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Pemberitahuan Jadwal Renovasi",
            "content": "Mulai tanggal 10 Juni 2026, masjid akan...",
            "image_url": null,
            "published_at": "2026-06-05T08:00:00.000Z",
            "published_at_formatted": "05 Jun 2026"
        }
    ],
    "meta": { ... }
}
```

### Get Announcement Detail

```
GET /mosques/{mosque_id}/announcements/{id}
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Pemberitahuan Jadwal Renovasi",
        "content": "Mulai tanggal 10 Juni 2026, masjid akan direnovasi bagian atap...",
        "image_url": "https://emasjid.id/storage/announcements/1/image.jpg",
        "published_at": "2026-06-05T08:00:00.000Z",
        "published_at_formatted": "05 Jun 2026"
    }
}
```

---

## 8. Finance (Read Only)

### Get Monthly Report Summary

```
GET /mosques/{mosque_id}/finance/report?year=2026&month=6
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "period": "2026-06",
        "period_formatted": "Juni 2026",
        "total_income": 15000000,
        "total_income_formatted": "Rp 15.000.000",
        "total_expense": 8500000,
        "total_expense_formatted": "Rp 8.500.000",
        "balance": 6500000,
        "balance_formatted": "Rp 6.500.000"
    }
}
```

### Get Transaction List (Read Only)

```
GET /mosques/{mosque_id}/finance/transactions?type=income&year=2026&month=6&page=1&per_page=15
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "type": "income",
            "type_label": "Pemasukan",
            "amount": 5000000,
            "amount_formatted": "Rp 5.000.000",
            "description": "Infaq Jumat",
            "category": "Infaq",
            "transaction_date": "2026-06-07",
            "transaction_date_formatted": "07 Jun 2026"
        }
    ],
    "meta": { ... }
}
```

---

## 9. Donation

### Get Donation Categories

```
GET /mosques/{mosque_id}/donations/categories
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        { "value": "infaq", "label": "Infaq" },
        { "value": "zakat", "label": "Zakat" },
        { "value": "sadaqah", "label": "Sedekah" },
        { "value": "waqf", "label": "Wakaf" }
    ]
}
```

### Get Payment Methods

```
GET /donations/payment-methods
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "code": "QRIS",
            "name": "QRIS",
            "icon_url": "https://emasjid.id/icons/qris.png",
            "fee": 700
        },
        {
            "code": "VA_BCA",
            "name": "BCA Virtual Account",
            "icon_url": "https://emasjid.id/icons/bca.png",
            "fee": 4000
        }
    ]
}
```

### Calculate Donation (Preview)

```
POST /donations/calculate
```

**Request Body:**

```json
{
    "amount": 100000,
    "payment_method": "QRIS"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "amount": 100000,
        "platform_fee": 2500,
        "payment_method_fee": 700,
        "total_payment": 103200,
        "mosque_receives": 100000,
        "fee_mechanism": "added_to_donor",
        "fee_mechanism_label": "Biaya ditanggung donatur",
        "breakdown": {
            "amount_formatted": "Rp 100.000",
            "platform_fee_formatted": "Rp 2.500",
            "payment_method_fee_formatted": "Rp 700",
            "total_payment_formatted": "Rp 103.200",
            "mosque_receives_formatted": "Rp 100.000"
        }
    }
}
```

### Create Donation

```
POST /donations
```

**Request Body:**

```json
{
    "mosque_id": 1,
    "category": "infaq",
    "amount": 100000,
    "payment_method": "QRIS",
    "is_anonymous": false
}
```

**Success Response (201):**

```json
{
    "success": true,
    "message": "Donasi berhasil dibuat. Silakan lakukan pembayaran.",
    "data": {
        "id": 42,
        "merchant_order_id": "DON-20260607-00042",
        "amount": 100000,
        "total_payment": 103200,
        "payment_url": "https://sandbox.duitku.com/topup/topuprequest?ref=ABC123",
        "payment_method": "QRIS",
        "status": "pending",
        "status_label": "Menunggu Pembayaran",
        "expires_at": "2026-06-08T10:30:00.000Z",
        "category": "infaq",
        "category_label": "Infaq",
        "mosque": {
            "id": 1,
            "name": "Masjid Al-Ikhlas"
        }
    }
}
```

### Get Donation Detail / Status

```
GET /donations/{id}
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 42,
        "merchant_order_id": "DON-20260607-00042",
        "amount": 100000,
        "fee_amount": 2500,
        "total_payment": 103200,
        "mosque_receives": 100000,
        "payment_method": "QRIS",
        "status": "confirmed",
        "status_label": "Terkonfirmasi",
        "is_anonymous": false,
        "category": "infaq",
        "category_label": "Infaq",
        "confirmed_at": "2026-06-07T10:35:00.000Z",
        "created_at": "2026-06-07T10:30:00.000Z",
        "mosque": {
            "id": 1,
            "name": "Masjid Al-Ikhlas"
        }
    }
}
```

### My Donation History

```
GET /my/donations?status=confirmed&page=1&per_page=15
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 42,
            "amount": 100000,
            "amount_formatted": "Rp 100.000",
            "category": "infaq",
            "category_label": "Infaq",
            "status": "confirmed",
            "status_label": "Terkonfirmasi",
            "mosque_name": "Masjid Al-Ikhlas",
            "created_at": "2026-06-07T10:30:00.000Z",
            "created_at_formatted": "07 Jun 2026"
        }
    ],
    "meta": { ... }
}
```

---

## 10. Congregation (Profile)

### Get My Profile

```
GET /my/profile
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Ahmad Fauzi",
        "email": "ahmad@example.com",
        "phone": "081234567890",
        "avatar_url": null,
        "active_mosque_id": 1,
        "active_mosque_name": "Masjid Al-Ikhlas",
        "joined_mosques_count": 2,
        "total_donations": 1500000,
        "total_donations_formatted": "Rp 1.500.000"
    }
}
```

### Update Profile

```
PUT /my/profile
```

**Request Body:**

```json
{
    "name": "Ahmad Fauzi Updated",
    "phone": "081234567899"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Profil berhasil diperbarui.",
    "data": {
        "id": 1,
        "name": "Ahmad Fauzi Updated",
        "email": "ahmad@example.com",
        "phone": "081234567899",
        "avatar_url": null
    }
}
```

### Update Avatar

```
POST /my/profile/avatar
Content-Type: multipart/form-data
```

**Request Body:**

```text
avatar: [file, max 2MB, jpg/png]
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Foto profil berhasil diperbarui.",
    "data": {
        "avatar_url": "https://emasjid.id/storage/avatars/1/photo.jpg"
    }
}
```

### Change Password

```
PUT /my/password
```

**Request Body:**

```json
{
    "current_password": "oldpass123",
    "password": "newpass456",
    "password_confirmation": "newpass456"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Password berhasil diubah."
}
```

### Delete Account

```
DELETE /my/account
```

**Request Body:**

```json
{
    "password": "currentpass123",
    "confirmation": "HAPUS AKUN"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Akun berhasil dihapus."
}
```

---

## 11. Notification

### Register FCM Token

```
POST /my/fcm-token
```

**Request Body:**

```json
{
    "token": "fcm_token_string_here",
    "device_type": "android"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Token berhasil didaftarkan."
}
```

### Get Notifications

```
GET /my/notifications?page=1&per_page=20
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-1",
            "type": "announcement",
            "title": "Pengumuman Baru",
            "body": "Pemberitahuan Jadwal Renovasi",
            "data": {
                "announcement_id": 1,
                "mosque_id": 1
            },
            "read_at": null,
            "created_at": "2026-06-07T08:00:00.000Z",
            "created_at_formatted": "07 Jun 2026, 08:00"
        },
        {
            "id": "uuid-2",
            "type": "donation",
            "title": "Donasi Dikonfirmasi",
            "body": "Donasi Anda sebesar Rp 100.000 telah dikonfirmasi.",
            "data": {
                "donation_id": 42,
                "mosque_id": 1
            },
            "read_at": "2026-06-07T09:00:00.000Z",
            "created_at": "2026-06-07T08:30:00.000Z",
            "created_at_formatted": "07 Jun 2026, 08:30"
        }
    ],
    "meta": {
        "unread_count": 3,
        "current_page": 1,
        "last_page": 2,
        "per_page": 20,
        "total": 25
    }
}
```

### Mark Notification as Read

```
POST /my/notifications/{id}/read
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Notifikasi ditandai sudah dibaca."
}
```

### Mark All as Read

```
POST /my/notifications/read-all
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Semua notifikasi ditandai sudah dibaca."
}
```

### Get Unread Count

```
GET /my/notifications/unread-count
```

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "count": 3
    }
}
```

---

## Catatan Penting

### Duitku Callback (Server-to-Server)

Callback dari Duitku bukan bagian dari API jamaah. Ini endpoint internal:

```
POST /webhooks/duitku/callback
```

Endpoint ini tidak memerlukan Sanctum auth, tetapi divalidasi via signature Duitku. Lihat `docs/SKILLS.md` untuk detail implementasi.

### Polling Strategy

Untuk update status donasi (menunggu pembayaran → dikonfirmasi), Flutter app bisa:

1. Polling `GET /donations/{id}` setiap 5 detik saat di halaman waiting
2. Atau menunggu push notification dari FCM

### File Upload Limits

| Jenis | Max Size | Format |
|-------|----------|--------|
| Avatar | 2 MB | jpg, png |
| Bukti transfer (manual) | 5 MB | jpg, png, pdf |

### Timezone

Semua datetime di response menggunakan ISO 8601 format (UTC). Konversi ke timezone lokal dilakukan di Flutter app.

---

## Penutup

Dokumen ini harus di-update setiap kali endpoint baru ditambahkan atau response format berubah. Pastikan Flutter developer selalu mereferensi versi terbaru dokumen ini.
