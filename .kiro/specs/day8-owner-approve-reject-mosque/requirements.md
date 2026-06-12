# Requirements Document

## Introduction

Dokumen ini menjelaskan requirement untuk fitur **Owner: Approve/Reject & Mosque Detail** pada platform eMasjid. Fitur ini memungkinkan platform owner (super-admin) untuk menyetujui atau menolak pendaftaran masjid baru, menampilkan detail lengkap masjid untuk keperluan verifikasi, dan mengirimkan notifikasi email otomatis ke admin masjid setelah keputusan diambil. Ini adalah alur kerja kritikal yang menentukan apakah masjid yang baru mendaftar dapat aktif di platform.

## Glossary

- **Owner**: Pengguna dengan role `super-admin` yang mengelola seluruh platform eMasjid
- **Mosque_Admin**: Pengguna yang terdaftar sebagai admin utama sebuah masjid (kolom `admin_user_id` di tabel `mosques`)
- **Mosque_Controller**: Controller Laravel di namespace `App\Http\Controllers\Owner` yang menangani aksi approve dan reject
- **Mosque_Service**: Service class yang mengandung business logic approve dan reject masjid
- **ApproveMosqueRequest**: Form Request Laravel untuk memvalidasi permintaan approve masjid
- **RejectMosqueRequest**: Form Request Laravel untuk memvalidasi permintaan reject masjid
- **MosqueApproved**: Event Laravel yang di-dispatch setelah masjid berhasil di-approve
- **MosqueRejected**: Event Laravel yang di-dispatch setelah masjid berhasil di-reject
- **Notification_Listener**: Listener Laravel yang mengirimkan email notifikasi ke Mosque_Admin sebagai respons terhadap event
- **Invitation_Code**: Kode unik alphanumeric 20-karakter yang di-generate saat masjid di-approve, digunakan jamaah untuk bergabung
- **SweetAlert2**: Library JavaScript untuk menampilkan modal konfirmasi interaktif
- **Flash_Message**: Pesan sesi sementara yang ditampilkan ke pengguna setelah suatu aksi selesai dilakukan

---

## Requirements

### Requirement 1: Tampilan Detail Masjid untuk Owner

**User Story:** Sebagai platform owner, saya ingin melihat informasi masjid secara lengkap di halaman detail, sehingga saya dapat membuat keputusan approve/reject yang tepat berdasarkan data yang komprehensif.

#### Acceptance Criteria

1. WHEN owner membuka halaman detail masjid, THE Platform SHALL menampilkan semua field profil masjid: nama, deskripsi, alamat, kota, provinsi, kode pos, telepon, dan email
2. WHEN owner membuka halaman detail masjid, THE Platform SHALL menampilkan foto masjid jika tersedia, atau placeholder image jika foto tidak tersedia
3. WHEN owner membuka halaman detail masjid, THE Platform SHALL menampilkan informasi admin masjid: nama, email, dan nomor telepon
4. WHEN owner membuka halaman detail masjid, THE Platform SHALL menampilkan informasi rekening bank: nama bank, nama pemilik rekening, dan nomor rekening
5. WHEN latitude dan longitude masjid tersedia, THE Platform SHALL menampilkan peta lokasi masjid menggunakan embedded Google Maps iframe
6. WHEN owner membuka halaman detail masjid, THE Platform SHALL menampilkan statistik: total jumlah jamaah terdaftar dan total donasi yang diterima dalam Rupiah
7. WHEN owner membuka halaman detail masjid, THE Platform SHALL menampilkan tanggal registrasi masjid dalam format `d M Y H:i`
8. THE Platform SHALL menampilkan status masjid saat ini dengan badge warna: `active` = hijau, `pending` = kuning, `suspended` = merah, `rejected` = abu-abu
9. WHEN status masjid adalah `pending`, THE Platform SHALL menampilkan tombol Approve dan tombol Reject
10. WHEN status masjid bukan `pending`, THE Platform SHALL menyembunyikan tombol Approve dan tombol Reject

---

### Requirement 2: Fungsionalitas Approve Masjid

**User Story:** Sebagai platform owner, saya ingin menyetujui pendaftaran masjid yang telah diverifikasi, sehingga masjid tersebut dapat mulai aktif menggunakan platform eMasjid.

#### Acceptance Criteria

1. WHEN owner mengklik tombol Approve, THE Platform SHALL menampilkan modal konfirmasi SweetAlert2 bertema hijau dengan nama masjid dan dua tombol: Konfirmasi dan Batal
2. WHEN owner mengklik tombol Batal pada modal, THE Platform SHALL menutup modal tanpa melakukan aksi apapun
3. WHEN owner mengklik tombol Konfirmasi pada modal approve, THE Mosque_Controller SHALL menerima permintaan POST ke route `/owner/mosques/{id}/approve`
4. THE ApproveMosqueRequest SHALL memvalidasi bahwa `mosque_id` wajib ada dan merujuk ke masjid yang ada di tabel `mosques`
5. THE ApproveMosqueRequest SHALL memvalidasi bahwa masjid yang dirujuk memiliki status `pending`
6. THE Mosque_Service SHALL mengubah status masjid dari `pending` menjadi `active`
7. THE Mosque_Service SHALL men-generate `invitation_code` unik 20-karakter alphanumeric untuk masjid tersebut
8. THE Mosque_Service SHALL memastikan `invitation_code` yang di-generate tidak duplikat dengan kode yang sudah ada di tabel `mosques`
9. THE Mosque_Service SHALL mencatat timestamp persetujuan di kolom `approved_at`
10. WHEN proses approve berhasil, THE Mosque_Controller SHALL men-dispatch event `MosqueApproved`
11. WHEN proses approve berhasil, THE Platform SHALL mengarahkan owner ke halaman detail masjid dengan flash message sukses
12. IF masjid tidak ditemukan atau status bukan `pending`, THEN THE Platform SHALL mengembalikan flash message error tanpa mengubah data masjid

---

### Requirement 3: Fungsionalitas Reject Masjid

**User Story:** Sebagai platform owner, saya ingin menolak pendaftaran masjid dengan menyertakan alasan penolakan, sehingga admin masjid memahami alasan mengapa pendaftarannya ditolak.

#### Acceptance Criteria

1. WHEN owner mengklik tombol Reject, THE Platform SHALL menampilkan modal SweetAlert2 bertema merah dengan judul "Tolak Masjid Ini?" dan textarea untuk input alasan penolakan
2. THE Platform SHALL memvalidasi bahwa alasan penolakan tidak boleh kosong dan memiliki panjang minimal 10 karakter sebelum submit diizinkan
3. WHEN owner mengklik tombol Batal pada modal reject, THE Platform SHALL menutup modal tanpa melakukan aksi apapun
4. WHEN owner mengklik tombol Konfirmasi pada modal reject, THE Mosque_Controller SHALL menerima permintaan POST ke route `/owner/mosques/{id}/reject`
5. THE RejectMosqueRequest SHALL memvalidasi bahwa `mosque_id` wajib ada dan merujuk ke masjid yang ada di tabel `mosques`
6. THE RejectMosqueRequest SHALL memvalidasi bahwa masjid yang dirujuk memiliki status `pending`
7. THE RejectMosqueRequest SHALL memvalidasi bahwa `rejection_reason` wajib diisi, bertipe string, dan memiliki panjang minimal 10 karakter
8. THE Mosque_Service SHALL mengubah status masjid dari `pending` menjadi `rejected`
9. THE Mosque_Service SHALL menyimpan alasan penolakan di kolom `rejection_reason`
10. WHEN proses reject berhasil, THE Mosque_Controller SHALL men-dispatch event `MosqueRejected`
11. WHEN proses reject berhasil, THE Platform SHALL mengarahkan owner ke halaman daftar masjid pending dengan flash message sukses
12. IF masjid tidak ditemukan atau status bukan `pending`, THEN THE Platform SHALL mengembalikan flash message error tanpa mengubah data masjid

---

### Requirement 4: Sistem Event dan Notifikasi Email

**User Story:** Sebagai mosque admin, saya ingin menerima email notifikasi ketika pendaftaran masjid saya disetujui atau ditolak, sehingga saya mengetahui status pendaftaran tanpa harus mengecek panel secara manual.

#### Acceptance Criteria

1. THE Platform SHALL menyediakan class event `MosqueApproved` dengan properti: objek `mosque`, objek `approvedBy`, dan timestamp `approvedAt`
2. THE Platform SHALL menyediakan class event `MosqueRejected` dengan properti: objek `mosque`, objek `rejectedBy`, timestamp `rejectedAt`, dan string `rejectionReason`
3. THE Platform SHALL menyediakan `Notification_Listener` yang terdaftar untuk merespons event `MosqueApproved` dan mengirimkan email ke Mosque_Admin
4. THE Platform SHALL menyediakan `Notification_Listener` yang terdaftar untuk merespons event `MosqueRejected` dan mengirimkan email ke Mosque_Admin
5. WHEN event `MosqueApproved` di-dispatch, THE Notification_Listener SHALL mengirimkan email yang berisi: nama masjid, tanggal persetujuan, `invitation_code`, dan link ke halaman login admin
6. WHEN event `MosqueRejected` di-dispatch, THE Notification_Listener SHALL mengirimkan email yang berisi: nama masjid, tanggal penolakan, dan alasan penolakan
7. THE Platform SHALL memproses pengiriman email secara asynchronous menggunakan Laravel queue untuk mencegah blocking pada response HTTP
8. IF pengiriman email gagal, THEN THE Platform SHALL mencatat detail kegagalan ke Laravel log tanpa membatalkan proses approve/reject yang sudah berhasil

---

### Requirement 5: Validasi Form Request

**User Story:** Sebagai backend developer, saya ingin menggunakan Form Request yang terdedikasi untuk validasi, sehingga logika validasi approve dan reject terpisah dari controller dan dapat diuji secara independen.

#### Acceptance Criteria

1. THE ApproveMosqueRequest SHALL mendefinisikan rule validasi: `mosque_id` wajib ada (required), bertipe integer, dan nilainya ada di tabel `mosques`
2. THE ApproveMosqueRequest SHALL memverifikasi bahwa masjid yang diminta memiliki status `pending` melalui custom validation rule
3. THE RejectMosqueRequest SHALL mendefinisikan rule validasi: `mosque_id` wajib ada (required), bertipe integer, dan nilainya ada di tabel `mosques`
4. THE RejectMosqueRequest SHALL mendefinisikan rule validasi: `rejection_reason` wajib ada (required), bertipe string, dan memiliki panjang minimal 10 karakter
5. THE RejectMosqueRequest SHALL memverifikasi bahwa masjid yang diminta memiliki status `pending` melalui custom validation rule
6. WHEN validasi gagal, THE Platform SHALL mengembalikan response HTTP 422 dengan pesan error validasi yang deskriptif dalam bahasa Indonesia

---

### Requirement 6: Routing dan Otorisasi

**User Story:** Sebagai backend developer, saya ingin mendaftarkan route approve dan reject yang terproteksi, sehingga hanya owner yang terautentikasi yang bisa mengakses endpoint tersebut.

#### Acceptance Criteria

1. THE Platform SHALL mendaftarkan route POST `/owner/mosques/{mosque}/approve` yang dipetakan ke `Mosque_Controller@approve` dengan nama route `owner.mosques.approve`
2. THE Platform SHALL mendaftarkan route POST `/owner/mosques/{mosque}/reject` yang dipetakan ke `Mosque_Controller@reject` dengan nama route `owner.mosques.reject`
3. THE routes tersebut SHALL dilindungi oleh middleware `auth` dan middleware khusus verifikasi role `owner`/`super-admin`
4. THE Platform SHALL menggunakan CSRF token protection pada semua POST request ke route approve dan reject
5. WHEN pengguna yang tidak terautentikasi mengakses route tersebut, THE Platform SHALL mengarahkan ke halaman login
6. WHEN pengguna yang terautentikasi namun bukan owner mengakses route tersebut, THE Platform SHALL mengembalikan response HTTP 403

---

### Requirement 7: Integritas Data dan Keamanan

**User Story:** Sebagai platform owner, saya ingin aksi approve/reject dijalankan secara aman dan konsisten, sehingga data masjid tidak korup akibat kondisi race condition atau akses tidak sah.

#### Acceptance Criteria

1. THE Mosque_Service SHALL mengeksekusi operasi update status, generate invitation_code, dan set timestamp di dalam satu database transaction
2. IF terjadi error selama database transaction, THEN THE Mosque_Service SHALL melakukan rollback seluruh perubahan sehingga tidak ada data yang tersimpan sebagian
3. THE Platform SHALL memverifikasi bahwa pengguna yang terautentikasi memiliki role `super-admin` sebelum mengizinkan aksi approve atau reject
4. THE Platform SHALL mencatat setiap aksi approve dan reject ke Laravel log dengan informasi: user ID, mosque ID, aksi yang dilakukan, dan timestamp

