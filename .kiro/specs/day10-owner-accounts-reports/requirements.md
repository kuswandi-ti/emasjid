# Dokumen Kebutuhan — Day 10: Kelola Akun Owner & Laporan Fee

## Introduction

Fitur ini merupakan bagian dari **Owner Panel (Fase 2, Day 10)** pada platform eMasjid.
Owner (super-admin) perlu dapat mengelola akun owner lain (sesama super-admin) agar tanggung jawab platform dapat didelegasikan.
Selain itu, owner juga perlu dapat memantau pendapatan fee platform melalui laporan periodik yang dilengkapi dengan ringkasan angka dan visualisasi chart.

Fitur ini dibangun di atas pondasi yang telah selesai pada Day 1–9:
- Sistem autentikasi dan otorisasi berbasis peran (Spatie Permission, role `super-admin`)
- Layout dan komponen owner panel (Bootstrap 5, DataTables, Chart.js)
- Konfigurasi fee platform (`platform_settings`)
- Data donasi dengan field `fee_amount`, `fee_mechanism`, `status`, dan `confirmed_at`

---

## Glossary

- **Owner / Super-Admin**: Pengguna dengan role `super-admin` yang memiliki akses penuh ke Owner Panel. Disebut "Admin Platform" di UI.
- **UserController**: `Owner\UserController` — Controller untuk CRUD akun owner.
- **ReportController**: `Owner\ReportController` — Controller untuk laporan pendapatan fee platform.
- **Donation**: Donasi online dari jamaah ke masjid; menyimpan snapshot `fee_amount` dan `confirmed_at`.
- **PlatformFee**: Pendapatan platform yang berasal dari `fee_amount` pada donasi dengan status `confirmed`.
- **DataTable**: Komponen tabel interaktif (Yajra DataTables + DataTables.net-bs5) yang sudah dipakai di halaman lain owner panel.
- **Chart**: Visualisasi data menggunakan library Chart.js yang sudah ter-install.
- **Basis_Points**: Satuan persentase (100 bp = 1%); dipakai di kolom `platform_fee_percentage` pada `platform_settings`.

---

## Requirements

### Requirement 1

**User Story:** Sebagai Owner, saya ingin melihat daftar semua akun owner lain, agar saya dapat memantau siapa saja yang memiliki akses Admin Platform.

#### Acceptance Criteria

1. THE `UserController` SHALL menampilkan halaman daftar akun owner yang memuat kolom nama, email, tanggal dibuat, dan aksi.
2. WHEN halaman daftar akun owner dimuat, THE `DataTable` SHALL menampilkan semua pengguna dengan role `super-admin` menggunakan server-side processing; jika tidak ada data, tabel akan ditampilkan kosong.
3. WHEN tidak ada pengguna yang ditampilkan di tabel akun owner, THE `DataTable` SHALL menampilkan pesan "Belum ada akun Admin Platform lain." terlepas dari penyebabnya.
4. THE `UserController` SHALL membatasi akses halaman daftar akun owner hanya untuk pengguna dengan role `super-admin`; kontrol akses dan tampilan data bersifat independen.

---

### Requirement 2

**User Story:** Sebagai Owner, saya ingin dapat menambahkan akun owner baru, agar tanggung jawab pengelolaan platform dapat didelegasikan kepada orang lain.

#### Acceptance Criteria

1. THE `UserController` SHALL menampilkan form tambah akun owner dengan field nama, email, dan password.
2. WHEN Owner mengisi form dengan data valid dan menekan tombol simpan, THE `UserController` SHALL membuat user baru dengan role `super-admin` dan menyimpannya ke tabel `users`.
3. IF email yang dimasukkan sudah terdaftar di tabel `users`, THEN THE `UserController` SHALL mengembalikan pesan validasi "Email sudah terdaftar."
4. IF password yang dimasukkan kurang dari 8 karakter, THEN THE `UserController` SHALL mengembalikan pesan validasi "Password minimal 8 karakter."
5. WHEN proses pembuatan akun selesai, THE `UserController` SHALL mengarahkan Owner kembali ke halaman daftar.
6. THE `UserController` SHALL membatasi pembuatan akun owner baru hanya untuk pengguna dengan role `super-admin`.

---

### Requirement 3

**User Story:** Sebagai Owner, saya ingin dapat mengubah data akun owner lain, agar informasi akun tetap akurat.

#### Acceptance Criteria

1. THE `UserController` SHALL menampilkan form edit akun owner yang memuat nilai nama dan email yang sudah tersimpan.
2. WHEN Owner mengubah nama atau email dengan data valid dan menekan tombol simpan, THE `UserController` SHALL memperbarui data user di tabel `users`; pesan sukses hanya ditampilkan apabila pembaruan database berhasil.
3. IF Owner mengisi field password pada form edit, THEN THE `UserController` SHALL memperbarui password user dengan nilai yang baru.
4. WHILE field password dibiarkan kosong pada form edit, THE `UserController` SHALL mempertahankan password yang ada tanpa perubahan.
5. IF email yang diubah sudah dipakai oleh user lain, THEN THE `UserController` SHALL mengembalikan pesan validasi "Email sudah terdaftar." dan hanya memblokir perubahan field email, sementara perubahan field lain yang valid tetap dapat diproses secara terpisah.
6. WHEN akun owner berhasil diperbarui di database, THE `UserController` SHALL menampilkan flash message "Akun Admin Platform berhasil diperbarui." dan mengarahkan kembali ke halaman daftar.
7. THE `UserController` SHALL memastikan owner tidak dapat mengedit akun miliknya sendiri melalui fitur ini.

---

### Requirement 4

**User Story:** Sebagai Owner, saya ingin dapat menghapus akun owner lain yang sudah tidak diperlukan, agar hak akses platform tetap terkendali.

#### Acceptance Criteria

1. THE `UserController` SHALL menampilkan tombol hapus pada setiap baris tabel daftar akun owner.
2. WHEN Owner menekan tombol hapus, THE halaman SHALL menampilkan dialog konfirmasi SweetAlert sebelum menjalankan penghapusan.
3. WHEN Owner mengkonfirmasi penghapusan, THE `UserController` SHALL menghapus user yang dipilih beserta role `super-admin`-nya dari database.
4. IF penghapusan akun sendiri terdeteksi, THEN THE `UserController` SHALL membatalkan proses penghapusan sepenuhnya dan mengembalikan pesan error "Anda tidak dapat menghapus akun Anda sendiri." tanpa melanjutkan ke tahap apapun.
5. WHEN penghapusan berhasil, THE `UserController` SHALL menampilkan flash message "Akun Admin Platform berhasil dihapus." dan memperbarui tabel.

---

### Requirement 5

**User Story:** Sebagai Owner, saya ingin melihat laporan pendapatan fee platform per bulan, agar saya dapat memantau performa keuangan platform secara periodik.

#### Acceptance Criteria

1. THE `ReportController` SHALL menampilkan halaman laporan fee yang memuat ringkasan bulan terpilih: total fee terkumpul, jumlah donasi terkonfirmasi, dan rata-rata fee per donasi.
2. WHEN Owner memilih periode bulan dan tahun kemudian menekan tombol Filter, THE `ReportController` SHALL menampilkan data laporan sesuai periode yang dipilih.
3. THE `ReportController` SHALL menghitung total fee dari kolom `fee_amount` pada tabel `donations` dengan kondisi `status = 'confirmed'` dan `confirmed_at` dalam periode bulan yang dipilih; nilai hasil perhitungan dapat berupa pecahan desimal sesuai hasil kalkulasi.
4. WHEN tidak ada donasi terkonfirmasi pada periode yang dipilih, THE halaman laporan SHALL menampilkan ringkasan dengan nilai nol dan pesan "Belum ada data fee pada periode ini."
5. THE `ReportController` SHALL membatasi akses halaman laporan hanya untuk pengguna dengan role `super-admin`.

---

### Requirement 6

**User Story:** Sebagai Owner, saya ingin melihat grafik tren pendapatan fee dalam 12 bulan terakhir, agar saya dapat dengan cepat memahami pola pendapatan platform.

#### Acceptance Criteria

1. WHEN halaman laporan fee ditampilkan, THE halaman SHALL merender bar chart yang memvisualisasikan total fee per bulan selama 12 bulan terakhir menggunakan Chart.js.
2. THE `Chart` SHALL menggunakan label bulan dalam format "MMM YYYY" (contoh: "Jan 2026") pada sumbu X dan nilai nominal Rupiah pada sumbu Y.
3. WHEN data fee untuk suatu bulan adalah nol, THE `Chart` SHALL tetap menampilkan bulan tersebut dengan nilai 0 agar kontinyuitas grafik terjaga.
4. THE `Chart` SHALL diperbarui secara otomatis setiap kali halaman laporan dimuat tanpa memerlukan interaksi tambahan dari Owner.

---

### Requirement 7

**User Story:** Sebagai Owner, saya ingin dapat mengunduh laporan fee dalam format CSV, agar saya dapat mengolah data lebih lanjut di spreadsheet.

#### Acceptance Criteria

1. THE halaman laporan fee SHALL menampilkan tombol "Export CSV" yang dapat diklik oleh Owner.
2. WHEN Owner menekan tombol "Export CSV", THE `ReportController` SHALL menghasilkan file CSV berisi data donasi terkonfirmasi pada periode yang dipilih dengan kolom: tanggal konfirmasi, ID donasi, nama masjid, nominal donasi, fee, dan mekanisme fee; file hanya dibuat saat tombol diklik tanpa penyimpanan persisten di server.
3. THE `ReportController` SHALL menggunakan nama file dengan format `laporan-fee-{tahun}-{bulan}.csv` (contoh: `laporan-fee-2026-06.csv`).
4. IF tidak ada data pada periode yang dipilih, THEN THE `ReportController` SHALL menghasilkan file CSV berisi baris header saja tanpa baris data.
5. THE `ReportController` SHALL membatasi akses endpoint export hanya untuk pengguna dengan role `super-admin`.
