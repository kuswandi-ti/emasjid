# Copywriting Guide - EMasjid

> Kamus istilah dan panduan penulisan UI untuk seluruh area aplikasi EMasjid.
> Gunakan dokumen ini agar bahasa di tampilan konsisten, ramah, dan sesuai konteks pengguna.

---

## Daftar Isi

1. [Prinsip Umum](#1-prinsip-umum)
2. [Owner Panel (Super Admin)](#2-owner-panel-super-admin)
3. [Admin Panel (Admin Masjid & Pengurus)](#3-admin-panel-admin-masjid--pengurus)
4. [Mobile App / API (Jamaah)](#4-mobile-app--api-jamaah)
5. [Istilah Domain](#5-istilah-domain)
6. [Format Angka dan Tanggal](#6-format-angka-dan-tanggal)
7. [Pesan Validasi](#7-pesan-validasi)
8. [Flash Message](#8-flash-message)
9. [Empty State](#9-empty-state)
10. [Tombol dan Aksi](#10-tombol-dan-aksi)

---

## 1. Prinsip Umum

| Prinsip | Penjelasan |
|---------|------------|
| Bahasa Indonesia | Semua label UI, pesan, dan copy menggunakan bahasa Indonesia |
| Formal tapi ramah | Tidak kaku, tidak terlalu santai. Seperti berbicara kepada pengurus masjid. |
| Jelas dan singkat | Hindari kalimat panjang. Langsung ke inti. |
| Konsisten | Sekali pakai istilah tertentu, pakai di semua tempat |
| Kontekstual | Istilah disesuaikan dengan siapa yang membaca (owner, admin, jamaah) |

### Istilah Teknis yang Dipertahankan (Tidak Diterjemahkan)

Istilah berikut tetap dalam bahasa Inggris di semua area:

```text
ID, API, CSV, PDF, QRIS, URL, QR Code, email, password, username, login, logout, export, import, dashboard
```

---

## 2. Owner Panel (Super Admin)

Owner panel adalah area internal platform. Pengguna memahami konteks teknis, tetapi label UI tetap dalam bahasa Indonesia.

### Istilah yang Digunakan

| Teknis (Kode) | Tampilan UI |
|----------------|-------------|
| mosque | Masjid |
| owner / super-admin | Admin Platform |
| congregation | Jamaah |
| staff | Pengurus |
| mosque-admin | Admin Masjid |
| pending | Menunggu Verifikasi |
| active | Aktif |
| suspended | Ditangguhkan |
| rejected | Ditolak |
| platform_fee_percentage | Persentase Fee Platform |
| platform_fee_mechanism | Mekanisme Fee |
| added_to_donor | Ditanggung Jamaah |
| deducted_from_donation | Dipotong dari Donasi |
| approve | Setujui |
| reject | Tolak |
| donation | Donasi |
| cash_transaction | Transaksi Kas |
| announcement | Pengumuman |

### Contoh Copy Owner Panel

| Konteks | Copy |
|---------|------|
| Judul halaman | Daftar Masjid, Masjid Menunggu Verifikasi |
| Tombol approve | Setujui Pendaftaran |
| Tombol reject | Tolak Pendaftaran |
| Label fee | Persentase Fee Platform (%) |
| Helper fee | Contoh: 250 = 2.5%. Fee akan otomatis terhitung pada setiap donasi. |
| Dashboard stat | Total Masjid Aktif, Total Jamaah, Pendapatan Fee Bulan Ini |
| Konfirmasi approve | Setujui pendaftaran masjid ini? Masjid akan langsung aktif dan bisa diakses. |
| Konfirmasi suspend | Tangguhkan masjid ini? Masjid tidak akan bisa diakses sampai diaktifkan kembali. |

---

## 3. Admin Panel (Admin Masjid & Pengurus)

Admin panel digunakan oleh pengurus masjid yang belum tentu paham teknologi. Bahasa harus natural dan dekat dengan keseharian pengelolaan masjid.

### Istilah yang Digunakan

| Teknis (Kode) | Tampilan UI |
|----------------|-------------|
| mosque | Masjid |
| schedule | Jadwal Sholat |
| activity | Kegiatan |
| cash_transaction | Transaksi Kas |
| income | Pemasukan |
| expense | Pengeluaran |
| donation | Donasi |
| announcement | Pengumuman |
| congregation | Jamaah |
| staff | Pengurus |
| permission | Izin Akses |
| role | Peran |
| finance | Keuangan |
| report | Laporan |
| export | Export |
| category | Kategori |
| amount | Nominal |
| description | Keterangan |
| transaction_date | Tanggal Transaksi |
| confirmed | Terkonfirmasi |
| pending | Menunggu Pembayaran |
| failed | Gagal |
| expired | Kedaluwarsa |
| anonymous | Anonim |
| publish | Publikasikan |
| draft | Konsep |
| archived | Diarsipkan |

### Istilah yang Tidak Boleh Dipakai di Admin Panel

| Jangan Pakai | Gunakan |
|--------------|---------|
| tenant | masjid |
| owner | admin platform |
| user | pengguna / jamaah / pengurus |
| record | data |
| entity | - (hilangkan, pakai nama spesifik) |
| CRUD | - (gunakan aksi spesifik: tambah, ubah, hapus) |
| fee mechanism | mekanisme fee |
| deducted | dipotong |

### Contoh Copy Admin Panel

| Konteks | Copy |
|---------|------|
| Judul halaman keuangan | Keuangan Masjid |
| Tab pemasukan | Pemasukan |
| Tab pengeluaran | Pengeluaran |
| Tombol tambah | Tambah Pemasukan |
| Label nominal | Nominal (Rp) |
| Label keterangan | Keterangan |
| Placeholder keterangan | Contoh: Infaq Jumat, Kotak Amal, dll. |
| Judul pengumuman | Pengumuman |
| Tombol publish | Publikasikan |
| Konfirmasi publish | Publikasikan pengumuman ini? Notifikasi akan dikirim ke semua jamaah. |
| Judul jamaah | Daftar Jamaah |
| Empty state jamaah | Belum ada jamaah yang bergabung. Bagikan link atau QR Code masjid agar jamaah bisa bergabung. |
| Judul pengurus | Daftar Pengurus |
| Tombol assign permission | Atur Izin Akses |
| Label jadwal | Jadwal Sholat Hari Ini |

---

## 4. Mobile App / API (Jamaah)

Jamaah adalah pengguna awam. Bahasa harus paling sederhana, ramah, dan mudah dipahami siapa saja.

### Istilah yang Digunakan

| Teknis (Kode) | Tampilan UI (Mobile) |
|----------------|----------------------|
| mosque | Masjid |
| schedule | Jadwal Sholat |
| activity | Kegiatan |
| donation | Donasi |
| infaq | Infaq |
| zakat | Zakat |
| sadaqah | Sedekah |
| waqf | Wakaf |
| announcement | Pengumuman |
| finance_report | Laporan Keuangan |
| amount | Nominal |
| is_anonymous | Sembunyikan nama saya |
| payment_method | Metode Pembayaran |
| history | Riwayat |
| join | Gabung |
| leave | Keluar |
| profile | Profil |
| notification | Notifikasi |

### Istilah yang Tidak Boleh Dipakai di Mobile

| Jangan Pakai | Gunakan |
|--------------|---------|
| congregation | jamaah / Anda |
| mosque-admin | pengurus masjid |
| staff | pengurus |
| permission | - (tidak relevan untuk jamaah) |
| cash_transaction | - (jamaah lihat sebagai "Laporan Keuangan") |
| fee | biaya layanan |
| platform | - (tidak perlu disebut) |

### Contoh Copy Mobile App

| Konteks | Copy |
|---------|------|
| Tombol gabung | Gabung Masjid |
| Tombol donasi | Donasi Sekarang |
| Label nominal | Nominal donasi |
| Placeholder nominal | Masukkan nominal (min. Rp 10.000) |
| Checkbox anonim | Sembunyikan nama saya |
| Info fee (ditanggung jamaah) | Nominal donasi: Rp 100.000 + biaya layanan Rp 2.500 |
| Info fee (dipotong donasi) | Nominal donasi: Rp 100.000 (termasuk biaya layanan 2.5%) |
| Konfirmasi donasi | Lanjutkan pembayaran Rp 102.500? |
| Sukses donasi | Terima kasih! Donasi Anda telah diterima. |
| Riwayat kosong | Belum ada riwayat donasi. |
| Switch masjid | Pilih Masjid |
| Pengumuman kosong | Belum ada pengumuman terbaru. |
| Jadwal sholat title | Jadwal Sholat Hari Ini |
| Notifikasi pengumuman | Pengumuman baru dari [Nama Masjid] |
| Notifikasi donasi | Donasi Anda sebesar Rp 100.000 telah dikonfirmasi. Jazakallahu khairan. |

---

## 5. Istilah Domain

### Kategori Donasi

| Enum Value | Tampilan |
|------------|----------|
| infaq | Infaq |
| zakat | Zakat |
| sadaqah | Sedekah |
| waqf | Wakaf |

Catatan: Gunakan "Infaq" (bukan "Infak"), "Sedekah" (bukan "Shadaqah").

### Status Masjid

| Enum Value | Owner Panel | Admin Panel |
|------------|-------------|-------------|
| pending | Menunggu Verifikasi | Menunggu Verifikasi |
| active | Aktif | Aktif |
| suspended | Ditangguhkan | Ditangguhkan |
| rejected | Ditolak | Ditolak |

### Status Donasi

| Enum Value | Admin Panel | Mobile |
|------------|-------------|--------|
| pending | Menunggu Pembayaran | Menunggu Pembayaran |
| confirmed | Terkonfirmasi | Berhasil |
| failed | Gagal | Gagal |
| expired | Kedaluwarsa | Kedaluwarsa |

### Tipe Transaksi

| Enum Value | Tampilan |
|------------|----------|
| income | Pemasukan |
| expense | Pengeluaran |

### Status Pengumuman

| Enum Value | Tampilan |
|------------|----------|
| draft | Konsep |
| published | Dipublikasikan |
| archived | Diarsipkan |

---

## 6. Format Angka dan Tanggal

| Jenis | Format | Contoh |
|-------|--------|--------|
| Nominal uang | Rp + titik ribuan | Rp 1.500.000 |
| Nominal input | Tanpa "Rp", angka saja | 1500000 |
| Tanggal tampil | dd MMM yyyy | 07 Jun 2026 |
| Tanggal input | yyyy-mm-dd (HTML date) | 2026-06-07 |
| Waktu sholat | HH:mm | 04:35, 12:05, 18:20 |
| Persentase fee | X.X% | 2.5% |

---

## 7. Pesan Validasi

Semua pesan validasi dalam bahasa Indonesia. Gunakan format:

```text
{Nama field} {aturan}.
```

Contoh:

| Rule | Pesan |
|------|-------|
| required | Nominal wajib diisi. |
| min (angka) | Nominal minimal Rp 1. |
| max (string) | Keterangan maksimal 255 karakter. |
| date | Format tanggal tidak valid. |
| before_or_equal:today | Tanggal tidak boleh melebihi hari ini. |
| email | Format email tidak valid. |
| unique | Email sudah terdaftar. |
| confirmed | Konfirmasi password tidak cocok. |

---

## 8. Flash Message

### Sukses

```text
Pemasukan berhasil dicatat.
Pengeluaran berhasil dicatat.
Pengumuman berhasil dipublikasikan.
Jadwal sholat berhasil diperbarui.
Pengurus berhasil ditambahkan.
Izin akses berhasil diperbarui.
Donasi berhasil dikonfirmasi.
Masjid berhasil disetujui.
```

### Error

```text
Gagal menyimpan data. Silakan coba lagi.
Terjadi kesalahan. Silakan hubungi admin.
```

### Warning

```text
Data yang dihapus tidak dapat dikembalikan.
Masjid akan ditangguhkan dan tidak bisa diakses.
```

Format pola: `{Objek} berhasil {aksi}.`

---

## 9. Empty State

| Konteks | Copy |
|---------|------|
| Tabel kosong (umum) | Belum ada data. |
| Jamaah kosong | Belum ada jamaah yang bergabung. |
| Pengumuman kosong | Belum ada pengumuman. |
| Transaksi kosong | Belum ada transaksi pada periode ini. |
| Donasi kosong | Belum ada donasi masuk. |
| Riwayat donasi (jamaah) | Belum ada riwayat donasi. |
| Kegiatan kosong | Belum ada kegiatan yang dijadwalkan. |
| Masjid pending kosong (owner) | Tidak ada masjid yang menunggu verifikasi. |

---

## 10. Tombol dan Aksi

### Label Tombol Standar

| Aksi | Label |
|------|-------|
| Create | Tambah {Objek} |
| Save | Simpan |
| Update | Perbarui |
| Delete | Hapus |
| Cancel | Batal |
| Back | Kembali |
| Search | Cari |
| Filter | Filter |
| Export CSV | Export CSV |
| Export PDF | Export PDF |
| Approve | Setujui |
| Reject | Tolak |
| Publish | Publikasikan |
| Archive | Arsipkan |
| Assign | Tetapkan |
| Join | Gabung |
| Leave | Keluar |

### State Tombol Saat Proses

| Aksi | Processing Text |
|------|-----------------|
| Simpan | Menyimpan... |
| Perbarui | Memperbarui... |
| Hapus | Menghapus... |
| Setujui | Memproses... |
| Tolak | Memproses... |
| Publikasikan | Mempublikasikan... |
| Export | Mengexport... |
| Gabung | Memproses... |

---

## Penutup

Jika menemukan istilah baru yang belum ada di dokumen ini, tentukan terjemahannya dan tambahkan ke dokumen sebelum dipakai di UI. Konsistensi bahasa sama pentingnya dengan konsistensi kode.
