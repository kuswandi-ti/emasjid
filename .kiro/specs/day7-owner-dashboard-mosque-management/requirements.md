# Requirements Document

## Introduction

Fitur ini memungkinkan Owner (super-admin platform eMasjid) untuk memantau statistik platform secara keseluruhan, melihat daftar semua masjid yang terdaftar, serta memproses antrian masjid yang menunggu verifikasi. Owner adalah satu-satunya role yang memiliki akses ke panel ini dan bertanggung jawab atas kesehatan platform secara keseluruhan.

Fitur ini mencakup:
- Backend: `MosqueService`, `Owner\DashboardController`, `Owner\MosqueController`, serta DTOs `ApproveMosqueDTO` dan `RejectMosqueDTO`
- Frontend (Blade + Bootstrap 5): halaman dashboard statistik platform, halaman daftar semua masjid dengan filter status, dan halaman daftar masjid pending

## Glossary

- **Owner**: Pengguna dengan role `super-admin` yang mengelola seluruh platform eMasjid
- **Platform**: Sistem eMasjid yang menampung banyak masjid
- **Mosque**: Entitas masjid yang terdaftar di platform dengan status: `pending`, `active`, `suspended`, atau `rejected`
- **Congregation**: Pengguna yang telah bergabung dengan masjid sebagai jamaah (anggota)
- **Platform_Fee**: Pendapatan platform yang berasal dari transaksi donasi dengan status `confirmed`
- **Dashboard_Service**: Backend service yang mengagregasi statistik platform secara keseluruhan
- **Mosque_Service**: Backend service yang menangani operasi manajemen daftar masjid
- **Dashboard_Controller**: Controller backend untuk permintaan halaman dashboard owner
- **Mosque_Controller**: Controller backend untuk permintaan halaman manajemen masjid
- **DataTable**: Komponen frontend berbasis Yajra DataTables yang menampilkan data tabular dengan paginasi, filter, dan pencarian
- **ApproveMosqueDTO**: Data Transfer Object untuk operasi persetujuan masjid
- **RejectMosqueDTO**: Data Transfer Object untuk operasi penolakan masjid
- **EnsureOwnerAccess**: Middleware yang memverifikasi role `super-admin` sebelum mengizinkan akses ke rute owner

## Requirements

### Requirement 1: Autentikasi dan Kontrol Akses Owner

**User Story:** Sebagai platform owner, saya ingin hanya owner yang bisa mengakses panel owner, sehingga manajemen platform terlindungi dari akses yang tidak sah.

#### Acceptance Criteria

1. THE Platform SHALL menerapkan middleware `EnsureOwnerAccess` dan `auth` pada seluruh route group owner, sehingga setiap request ke rute owner melewati kedua middleware tersebut sebelum mencapai controller
2. WHEN pengguna yang belum login mencoba mengakses rute owner, THE Platform SHALL mengarahkan pengguna ke halaman login dengan menyertakan URL asal sebagai parameter `redirect`
3. WHEN pengguna yang sudah login namun tidak memiliki role `super-admin` mencoba mengakses rute owner, THE Platform SHALL mengembalikan respons HTTP 403 Forbidden beserta pesan error "Anda tidak memiliki akses ke halaman ini"
4. WHILE owner memiliki sesi aktif dan mengakses rute owner mana pun, THE Platform SHALL menampilkan layout panel owner yang mencakup: sidebar navigasi dengan tautan Dashboard, Semua Masjid, Masjid Pending, dan Pengaturan; header dengan nama pengguna yang sedang login; dan konten halaman yang diminta

### Requirement 2: Dashboard Statistik Platform

**User Story:** Sebagai platform owner, saya ingin melihat statistik platform secara keseluruhan, sehingga saya dapat memantau kesehatan dan pertumbuhan platform eMasjid.

#### Acceptance Criteria

1. WHEN owner mengakses halaman dashboard, THE Dashboard_Service SHALL menghitung total masjid berdasarkan masing-masing status: `active`, `pending`, `suspended`, dan `rejected`, menghasilkan empat angka terpisah
2. WHEN owner mengakses halaman dashboard, THE Dashboard_Service SHALL menghitung jumlah distinct `user_id` pada tabel `mosque_user` yang terhubung ke masjid berstatus `active` dan tidak soft-deleted, sebagai total jamaah aktif platform
3. WHEN owner mengakses halaman dashboard, THE Dashboard_Service SHALL menghitung akumulasi total `fee_amount` sepanjang masa dari seluruh record donasi dengan status `confirmed` sebagai pendapatan fee platform
4. THE Dashboard_Controller SHALL meneruskan data statistik ke view dalam format Rupiah: prefix "Rp " diikuti angka bulat tanpa desimal dengan pemisah ribuan menggunakan titik (contoh: Rp 1.250.000)
5. THE Platform SHALL menampilkan statistik pada halaman dashboard dalam bentuk kartu visual yang mencakup: total masjid aktif, total masjid pending, total jamaah, dan total pendapatan fee
6. WHEN jumlah masjid `pending` lebih dari 0, THE Platform SHALL menampilkan badge yang memuat angka jumlah pending pada kartu statistik masjid pending dan tautan cepat ke halaman masjid pending; IF jumlah masjid `pending` kembali menjadi 0, THE Platform SHALL menyembunyikan badge tersebut

### Requirement 3: Daftar Semua Masjid

**User Story:** Sebagai platform owner, saya ingin melihat semua masjid yang terdaftar dalam satu tabel, sehingga saya dapat memiliki gambaran lengkap tentang seluruh masjid di platform.

#### Acceptance Criteria

1. WHEN owner mengakses halaman daftar masjid, THE Mosque_Controller SHALL mengembalikan data masjid dalam format paginasi dengan ukuran halaman antara 10 hingga 50 record per halaman
2. THE Mosque_Service SHALL menyertakan data nama dan email admin user untuk setiap masjid dalam satu query yang sama (tanpa query terpisah per baris)
3. THE Mosque_Service SHALL menyertakan hitungan anggota jamaah (`congregation_count`) untuk setiap masjid
4. THE DataTable SHALL menampilkan kolom: Nama Masjid, Kota, Status, Admin, Jumlah Jamaah, Tanggal Daftar, dan Aksi
5. THE DataTable SHALL mendukung filter berdasarkan status masjid: semua, `active`, `pending`, `suspended`, `rejected`
6. THE DataTable SHALL mendukung fungsi pencarian case-insensitive partial-match pada kolom nama masjid dan kota, dan menampilkan hasil yang cocok secara real-time saat pengguna mengetik
7. WHEN status masjid adalah `active`, THE DataTable SHALL menampilkan badge status berwarna hijau
8. WHEN status masjid adalah `pending`, THE DataTable SHALL menampilkan badge status berwarna kuning/oranye
9. WHEN status masjid adalah `suspended`, THE DataTable SHALL menampilkan badge status berwarna merah
10. WHEN status masjid adalah `rejected`, THE DataTable SHALL menampilkan badge status berwarna abu-abu
11. THE DataTable SHALL menampilkan tautan aksi "Lihat Detail" pada setiap baris yang mengarahkan ke halaman detail masjid tersebut
12. WHEN pengambilan data masjid gagal karena error server, THE Platform SHALL menampilkan pesan error yang menginformasikan bahwa data tidak dapat dimuat dan menyarankan pengguna untuk mencoba lagi
13. WHEN tidak ada masjid yang cocok dengan kriteria filter atau pencarian aktif, THE Platform SHALL menampilkan pesan keadaan kosong yang menyebutkan filter atau kata kunci yang digunakan

### Requirement 4: Daftar Masjid Pending

**User Story:** Sebagai platform owner, saya ingin melihat daftar masjid yang menunggu verifikasi di halaman khusus, sehingga saya dapat mengidentifikasi dan memproses pendaftaran baru dengan cepat.

#### Acceptance Criteria

1. WHEN owner mengakses halaman masjid pending, THE Mosque_Controller SHALL mengambil hanya masjid dengan status `pending` melalui `Mosque_Service`
2. THE Mosque_Service SHALL mengurutkan masjid pending berdasarkan `created_at` secara ascending (terlama pertama)
3. THE Mosque_Service SHALL menyertakan data email dan nomor telepon admin user untuk setiap masjid pending
4. THE DataTable SHALL menampilkan kolom: Nama Masjid, Kota, Email Admin, Telepon Admin, Tanggal Daftar, Menunggu (hari), dan Aksi
5. THE DataTable SHALL menghitung dan menampilkan jumlah hari menunggu menggunakan rumus floor(selisih waktu sekarang − `created_at` dalam jam / 24) untuk setiap masjid pending
6. WHEN masjid pending telah menunggu lebih dari 7 hari, THE DataTable SHALL menyorot baris tersebut dengan warna latar kuning muda (#FFFDE7 atau setara)
7. THE DataTable SHALL mendukung fungsi pencarian case-insensitive substring-match secara OR pada kolom nama masjid, kota, dan email admin; hasil diperbarui setiap kali pengguna mengetik karakter baru
8. WHEN tidak ada masjid dengan status `pending`, THE Platform SHALL menampilkan pesan yang menyatakan "Tidak ada masjid yang menunggu verifikasi" beserta saran tindakan selanjutnya bagi owner

### Requirement 5: Detail Masjid

**User Story:** Sebagai platform owner, saya ingin melihat informasi lengkap tentang satu masjid tertentu, sehingga saya dapat meninjau semua data masjid sebelum membuat keputusan verifikasi.

#### Acceptance Criteria

1. WHEN owner memilih sebuah masjid dari daftar, THE Mosque_Controller SHALL mengambil record masjid yang lengkap berdasarkan ID melalui `Mosque_Service`
2. IF masjid tidak ditemukan berdasarkan ID yang diberikan, THEN THE Mosque_Controller SHALL mengembalikan respons HTTP 404 Not Found
3. THE Mosque_Service SHALL mengembalikan data masjid beserta field: `name`, `address`, `city`, `province`, `phone`, `email`, `description`, `bank_name`, `account_number`, `account_holder`, `photo_url`, `status`, `rejection_reason`, `approved_at`, `created_at`; detail admin user (`name`, `email`, `phone`); `congregation_count`; total donasi dengan status `confirmed`; dan `created_at` sebagai timestamp pendaftaran
4. THE Platform SHALL menampilkan halaman detail masjid dengan seksi: Informasi Dasar (name, city, province, address, phone, email, description), Kontak Admin (nama, email, telepon admin), Informasi Rekening (bank_name, account_number, account_holder), dan Statistik (congregation_count, total donasi confirmed)
5. IF masjid memiliki nilai `photo_url` yang tidak kosong, THEN THE Platform SHALL menampilkan foto masjid menggunakan URL tersebut
6. THE Platform SHALL menampilkan status masjid dengan badge warna yang berbeda untuk setiap nilai status yang mungkin (`pending`, `active`, `suspended`, `rejected`)
7. IF status masjid adalah `rejected` DAN `rejection_reason` tidak kosong, THEN THE Platform SHALL menampilkan alasan penolakan tersebut; IF `rejection_reason` kosong atau null, THEN THE Platform SHALL menampilkan teks "-" pada field alasan penolakan
8. IF status masjid adalah `active`, THEN THE Platform SHALL menampilkan tanggal persetujuan (`approved_at`) dalam format "DD MMMM YYYY"
9. WHEN pengguna yang sudah login namun tidak memiliki role `super-admin` mencoba mengakses halaman detail masjid, THE Platform SHALL mengembalikan respons HTTP 403 Forbidden

### Requirement 6: Data Transfer Objects untuk Manajemen Masjid

**User Story:** Sebagai backend developer, saya ingin menggunakan DTOs untuk operasi manajemen masjid, sehingga type safety dan konsistensi validasi terjaga di seluruh layer service.

#### Acceptance Criteria

1. THE Platform SHALL menyediakan `ApproveMosqueDTO` dengan field: `mosque_id` (integer, required, nilai minimum 1) dan `approved_by_user_id` (integer, required, nilai minimum 1)
2. THE Platform SHALL menyediakan `RejectMosqueDTO` dengan field: `mosque_id` (integer, required, nilai minimum 1), `rejected_by_user_id` (integer, required, nilai minimum 1), dan `rejection_reason` (string, required)
3. THE `RejectMosqueDTO` SHALL memvalidasi bahwa `rejection_reason` adalah string tidak kosong dengan panjang minimum 10 karakter dan maksimum 500 karakter
4. THE `Mosque_Service` SHALL menerima `ApproveMosqueDTO` sebagai parameter input untuk operasi persetujuan masjid
5. THE `Mosque_Service` SHALL menerima `RejectMosqueDTO` sebagai parameter input untuk operasi penolakan masjid
6. WHEN `Mosque_Service` menerima DTO dengan data yang tidak valid (melanggar constraint pada kriteria 1–3), THE `Mosque_Service` SHALL menolak operasi, mengembalikan error yang mengidentifikasi field mana yang tidak valid, dan tidak mengubah status masjid di database

### Requirement 7: Navigasi dan Antarmuka Pengguna

**User Story:** Sebagai platform owner, saya ingin navigasi yang intuitif di panel owner, sehingga saya dapat mengakses berbagai fitur manajemen secara efisien.

#### Acceptance Criteria

1. THE Platform SHALL menyediakan menu navigasi sidebar dengan tautan ke: Dashboard, Semua Masjid, Masjid Pending, dan Pengaturan
2. WHILE owner berada pada suatu halaman, THE Platform SHALL menyorot item navigasi sidebar yang sesuai dengan halaman aktif menggunakan penanda visual yang berbeda (misalnya warna latar atau teks tebal) dibandingkan item yang tidak aktif
3. WHEN jumlah masjid `pending` lebih dari 0, THE Platform SHALL menampilkan badge yang memuat angka jumlah pending pada item navigasi "Masjid Pending" di sidebar
4. THE Platform SHALL menggunakan komponen Bootstrap 5 di semua halaman panel owner
5. THE Platform SHALL memastikan semua halaman dapat digunakan sepenuhnya — termasuk navigasi, tabel, dan form — pada viewport lebar ≥768px (tablet) dan ≥992px (desktop), tanpa overflow horizontal atau elemen yang terpotong

### Requirement 8: Performa dan Pemuatan Data

**User Story:** Sebagai platform owner, saya ingin waktu muat halaman yang cepat, sehingga saya dapat mengelola platform secara efisien tanpa hambatan.

#### Acceptance Criteria

1. THE Mosque_Service SHALL mengambil data admin user dan `congregation_count` dalam satu query yang sama dengan data masjid, tanpa menghasilkan query tambahan per baris masjid
2. THE Dashboard_Service SHALL mengembalikan seluruh data statistik (empat hitungan status masjid, total jamaah, total fee) dalam waktu tidak lebih dari 2 detik pada dataset hingga 10.000 masjid
3. WHEN total jumlah masjid melebihi 100 record, THE DataTable SHALL menggunakan paginasi server-side dengan ukuran halaman default 25 record
4. WHILE data masjid atau statistik sedang diambil dari server, THE Platform SHALL menampilkan indikator loading berupa spinner atau skeleton di area konten yang sedang dimuat
5. WHEN tidak ada masjid yang cocok dengan kriteria filter atau pencarian aktif, THE Platform SHALL menampilkan pesan keadaan kosong yang menyebutkan filter atau kata kunci yang digunakan dan menyarankan tindakan selanjutnya
6. THE Platform SHALL menampilkan halaman daftar masjid (dengan data ≤100 record pertama) dalam waktu tidak lebih dari 1 detik setelah filter status diubah
