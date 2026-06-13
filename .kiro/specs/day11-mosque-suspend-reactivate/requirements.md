# Requirements Document

## Introduction

Fitur ini mengimplementasikan aksi **suspend** dan **reactivate** masjid di Owner Panel (panel super-admin platform eMasjid), beserta notifikasi ke admin masjid yang terdampak. Fitur ini melengkapi alur manajemen masjid di Day 8 (approve/reject) dengan kemampuan menangguhkan masjid aktif yang melanggar ketentuan, dan mengaktifkan kembali masjid yang ditangguhkan.

Cakupan Day 11:
- **Backend**: Aksi suspend dan reactivate pada model `Mosque`, Event/Listener untuk notifikasi, Form Request, dan guard middleware.
- **Frontend (Owner Panel)**: Tombol suspend/reactivate kontekstual di halaman detail masjid, konfirmasi SweetAlert, flash message, dan polish responsivitas UI owner panel.
- **Deliverable**: Owner panel lengkap dan fungsional sebagai penutup Fase 2 (Day 7–11).

---

## Glossary

- **Owner / Super-Admin**: Pengguna dengan role `super-admin` yang mengelola seluruh platform eMasjid. Tidak terikat pada masjid tertentu.
- **Mosque_Admin**: Pengguna yang terdaftar sebagai `admin_user_id` pada record `mosques`. Bertanggung jawab atas satu masjid.
- **Suspension_System**: Komponen backend yang menangani perubahan status masjid dari `active` ke `suspended` beserta side-effects-nya.
- **Reactivation_System**: Komponen backend yang menangani perubahan status masjid dari `suspended` ke `active` beserta side-effects-nya.
- **MosqueStatus**: Enum PHP dengan nilai: `pending`, `active`, `suspended`, `rejected`.
- **Owner_Panel**: Antarmuka web Laravel Blade yang diakses oleh Owner.
- **Notification_Service**: Komponen yang mengirim notifikasi (FCM push notification) kepada pengguna yang bersangkutan.
- **SweetAlert**: Library JavaScript untuk dialog konfirmasi di Owner Panel.
- **FCM**: Firebase Cloud Messaging — layanan push notification ke perangkat mobile.

---

## Requirements

### Requirement 1: Suspend Masjid Aktif

**User Story:** Sebagai Owner, saya ingin dapat menangguhkan masjid yang berstatus aktif, agar saya bisa menghentikan akses masjid yang melanggar ketentuan platform sementara menunggu penyelesaian masalah.

#### Acceptance Criteria

1. WHEN Owner membuka halaman detail masjid dengan status `active`, THE Owner_Panel SHALL menampilkan tombol bertuliskan "Tangguhkan" dan tidak menampilkan tombol "Aktifkan Kembali".
2. WHEN Owner menekan tombol "Tangguhkan", THE Owner_Panel SHALL menampilkan dialog konfirmasi SweetAlert dengan teks "Tangguhkan masjid ini? Masjid tidak akan bisa diakses sampai diaktifkan kembali." sebelum melanjutkan aksi. IF Owner menekan cancel pada dialog tersebut, THEN dialog menutup tanpa mengubah status masjid dan tanpa mengirimkan request apapun ke server.
3. WHEN Owner mengonfirmasi aksi suspend pada dialog konfirmasi, THE Suspension_System SHALL mengubah kolom `status` pada record `mosques` dari `active` menjadi `suspended`.
4. WHEN Suspension_System berhasil mengubah status masjid menjadi `suspended`, THE Suspension_System SHALL memperbarui kolom `updated_at` pada record `mosques` yang bersangkutan.
5. WHEN Suspension_System berhasil mengubah status masjid menjadi `suspended`, THE Owner_Panel SHALL menampilkan flash message bertuliskan "Masjid berhasil ditangguhkan." dan mengarahkan Owner kembali ke halaman detail masjid yang sama.
6. WHEN Owner membuka kembali halaman detail masjid yang telah ditangguhkan, THE Owner_Panel SHALL menampilkan badge status "Ditangguhkan" dan tombol "Aktifkan Kembali", serta tidak menampilkan tombol "Tangguhkan".
7. IF Owner mencoba melakukan aksi suspend terhadap masjid yang berstatus selain `active`, THEN THE Suspension_System SHALL mengembalikan respons HTTP 422 dengan pesan "Masjid tidak dapat ditangguhkan karena statusnya bukan aktif."
8. IF pengguna yang melakukan request suspend tidak memiliki role `super-admin`, THEN THE Suspension_System SHALL mengembalikan respons HTTP 403 sebelum melakukan validasi status apapun.
9. IF terjadi kegagalan pada sisi server (error database atau network failure) saat memproses aksi suspend, THEN THE Owner_Panel SHALL menampilkan pesan error kepada Owner dan status masjid SHALL tetap tidak berubah.

---

### Requirement 2: Reactivate Masjid yang Ditangguhkan

**User Story:** Sebagai Owner, saya ingin dapat mengaktifkan kembali masjid yang sebelumnya ditangguhkan, agar masjid tersebut bisa beroperasi kembali setelah permasalahan diselesaikan.

#### Acceptance Criteria

1. WHEN Owner membuka halaman detail masjid dengan status `suspended`, THE Owner_Panel SHALL menampilkan tombol bertuliskan "Aktifkan Kembali" dan tidak menampilkan tombol "Tangguhkan".
2. WHEN Owner menekan tombol "Aktifkan Kembali", THE Owner_Panel SHALL menampilkan dialog konfirmasi SweetAlert dengan teks "Aktifkan kembali masjid ini? Masjid akan langsung bisa diakses kembali." sebelum melanjutkan aksi. IF Owner menekan cancel pada dialog tersebut, THEN dialog menutup tanpa mengubah status masjid dan tanpa mengirimkan request apapun ke server.
3. WHEN Owner mengonfirmasi aksi reactivate pada dialog konfirmasi, THE Reactivation_System SHALL mengubah kolom `status` pada record `mosques` dari `suspended` menjadi `active`.
4. WHEN Reactivation_System berhasil mengubah status masjid menjadi `active`, THE Owner_Panel SHALL menampilkan flash message bertuliskan "Masjid berhasil diaktifkan kembali."
5. WHEN Owner membuka kembali halaman detail masjid yang telah diaktifkan, THE Owner_Panel SHALL menampilkan badge status "Aktif" dan tombol "Tangguhkan", serta tidak menampilkan tombol "Aktifkan Kembali".
6. IF Owner menekan cancel pada dialog konfirmasi reactivate, THEN THE Owner_Panel SHALL menutup dialog tanpa mengubah status masjid dan tanpa mengirimkan request apapun ke server.
7. IF Owner mencoba melakukan aksi reactivate terhadap masjid yang berstatus selain `suspended`, THEN THE Reactivation_System SHALL mengembalikan respons HTTP 422 dengan pesan "Masjid tidak dapat diaktifkan kembali karena statusnya bukan ditangguhkan."
8. IF pengguna yang melakukan request reactivate tidak memiliki role `super-admin`, THEN THE Reactivation_System SHALL mengembalikan respons HTTP 403 sebelum melakukan validasi status apapun.
9. IF terjadi kegagalan pada sisi server saat memproses aksi reactivate, THEN THE Owner_Panel SHALL menampilkan pesan error kepada Owner dan status masjid SHALL tetap `suspended`.

---

### Requirement 3: Notifikasi ke Mosque_Admin Saat Masjid Ditangguhkan

**User Story:** Sebagai Mosque_Admin, saya ingin mendapat notifikasi ketika masjid saya ditangguhkan oleh Owner, agar saya segera mengetahui kondisi tersebut dan bisa mengambil tindakan.

#### Acceptance Criteria

1. WHEN Suspension_System berhasil mengubah status masjid menjadi `suspended`, THE Notification_Service SHALL mengirimkan notifikasi ke Mosque_Admin yang tercatat pada kolom `admin_user_id` masjid tersebut.
2. WHEN Notification_Service mengirim notifikasi suspend, THE Notification_Service SHALL menggunakan judul yang mengindikasikan penangguhan masjid dan isi notifikasi yang menyebutkan nama masjid beserta instruksi untuk menghubungi admin platform.
3. WHERE FCM token Mosque_Admin tersedia di tabel `fcm_tokens` dan belum ditandai tidak valid atau dicabut, THE Notification_Service SHALL mengirimkan push notification FCM ke seluruh token tersebut yang dimiliki Mosque_Admin.
4. THE Notification_Service SHALL memproses pengiriman notifikasi secara asinkron melalui queue job, sehingga aksi suspend tidak memblokir maupun menunggu selesainya proses pengiriman notifikasi.
5. IF `admin_user_id` pada record masjid bernilai null atau user tidak ditemukan, THEN THE Notification_Service SHALL mencatat log warning dan tidak melempar exception.
6. IF Mosque_Admin tidak memiliki FCM token yang terdaftar, THEN THE Notification_Service SHALL mencatat log warning dengan format "No FCM token found for mosque admin user_id={id}" dan tidak melempar exception.
7. IF pengiriman FCM gagal karena alasan apapun (termasuk token tidak valid, expired, atau kegagalan layanan eksternal), THEN THE Notification_Service SHALL mencatat log error dengan detail kegagalan dan melanjutkan proses tanpa melempar exception ke aksi utama.

---

### Requirement 4: Notifikasi ke Mosque_Admin Saat Masjid Diaktifkan Kembali

**User Story:** Sebagai Mosque_Admin, saya ingin mendapat notifikasi ketika masjid saya diaktifkan kembali oleh Owner, agar saya segera mengetahui bahwa masjid sudah bisa beroperasi kembali.

#### Acceptance Criteria

1. WHEN Reactivation_System berhasil mengubah status masjid menjadi `active`, THE Notification_Service SHALL mengirimkan notifikasi ke Mosque_Admin yang tercatat pada kolom `admin_user_id` masjid tersebut.
2. WHEN Notification_Service mengirim notifikasi reactivate, THE Notification_Service SHALL menggunakan judul yang mengindikasikan pengaktifan kembali masjid dan isi notifikasi yang menyebutkan nama masjid beserta informasi bahwa masjid dapat dikelola kembali.
3. WHERE FCM token Mosque_Admin tersedia di tabel `fcm_tokens` dan belum ditandai tidak valid atau dicabut, THE Notification_Service SHALL mengirimkan push notification FCM ke seluruh token tersebut yang dimiliki Mosque_Admin.
4. THE Notification_Service SHALL memproses pengiriman notifikasi reactivate secara asinkron melalui queue job, sehingga proses pengiriman tidak memblokir maupun membatalkan perubahan status masjid.
5. IF Mosque_Admin tidak memiliki FCM token yang terdaftar, THEN THE Notification_Service SHALL mencatat log warning dengan format "No FCM token found for mosque admin user_id={id}" dan tidak melempar exception.
6. IF pengiriman FCM gagal karena alasan apapun (termasuk token tidak valid, expired, atau kegagalan layanan eksternal), THEN THE Notification_Service SHALL mencatat log error dengan detail kegagalan dan melanjutkan proses tanpa melempar exception ke aksi utama.

---

### Requirement 5: Kontrol Akses Masjid Ditangguhkan di Admin Panel

**User Story:** Sebagai sistem, saya ingin memastikan bahwa Admin Masjid tidak dapat mengakses panel administrasi ketika masjidnya berstatus suspended, agar integritas data dan keamanan akses terjaga.

#### Acceptance Criteria

1. WHILE status masjid adalah `suspended`, THE Owner_Panel SHALL menampilkan badge status "Ditangguhkan" (warna oranye/warning) secara konsisten di halaman detail masjid, halaman daftar masjid, dan halaman statistik/laporan terkait masjid tersebut.
2. IF Mosque_Admin mencoba mengakses route yang dilindungi middleware `EnsureMosqueActive` milik masjid yang berstatus `suspended`, THEN THE Suspension_System SHALL menolak akses dan mengarahkan ke halaman informasi "Masjid Anda sedang ditangguhkan." tanpa menyebabkan redirect loop.
3. IF Mosque_Admin mencoba mengakses route yang dilindungi middleware `EnsureMosqueActive` milik masjid yang berstatus `active`, THEN THE middleware SHALL mengizinkan request berlanjut (HTTP 200 atau respons normal route tersebut).
4. IF Mosque_Admin memiliki session aktif pada saat status masjid diubah menjadi `suspended`, THEN THE Suspension_System SHALL memastikan bahwa request berikutnya dari session tersebut ke route yang dilindungi `EnsureMosqueActive` ditolak dan diarahkan ke halaman informasi penangguhan.

---

### Requirement 6: Tombol Aksi Kontekstual di Halaman Detail Masjid

**User Story:** Sebagai Owner, saya ingin tombol aksi yang ditampilkan di halaman detail masjid selalu relevan dengan status masjid saat itu, agar saya tidak salah melakukan aksi yang tidak sesuai konteks.

#### Acceptance Criteria

1. WHEN Owner membuka halaman detail masjid dengan status `pending`, THE Owner_Panel SHALL menampilkan tombol "Setujui Pendaftaran" dan "Tolak Pendaftaran", serta tidak menampilkan tombol "Tangguhkan" maupun "Aktifkan Kembali".
2. WHEN Owner membuka halaman detail masjid dengan status `active`, THE Owner_Panel SHALL menampilkan tombol "Tangguhkan" dengan warna kuning/oranye (warning), serta tidak menampilkan tombol "Setujui Pendaftaran", "Tolak Pendaftaran", maupun "Aktifkan Kembali".
3. WHEN Owner membuka halaman detail masjid dengan status `suspended`, THE Owner_Panel SHALL menampilkan tombol "Aktifkan Kembali" dengan warna hijau (success), serta tidak menampilkan tombol "Setujui Pendaftaran", "Tolak Pendaftaran", maupun "Tangguhkan".
4. WHEN Owner membuka halaman detail masjid dengan status `rejected`, THE Owner_Panel SHALL tidak menampilkan tombol "Setujui Pendaftaran", "Tolak Pendaftaran", "Tangguhkan", maupun "Aktifkan Kembali".
5. THE Owner_Panel SHALL menempatkan seluruh tombol aksi status masjid dalam satu grup tombol di area action di bagian atas halaman detail, di bawah heading nama masjid, pada semua kondisi status masjid.

---

### Requirement 7: Polish UI dan Responsivitas Owner Panel

**User Story:** Sebagai Owner, saya ingin Owner Panel tampil rapi, responsif, dan konsisten di berbagai ukuran layar, agar saya bisa bekerja dengan nyaman baik di desktop maupun tablet.

#### Acceptance Criteria

1. THE Owner_Panel SHALL menampilkan seluruh halaman (dashboard, daftar masjid, detail masjid, pengaturan, laporan, daftar akun) dengan layout yang responsif menggunakan grid system Bootstrap 5, dapat diakses pada lebar layar minimal 768px hingga 1920px, tanpa horizontal scrollbar, tanpa elemen yang terpotong atau tumpang tindih di luar batas viewport.
2. THE Owner_Panel SHALL menggunakan badge warna yang konsisten untuk setiap nilai `MosqueStatus`: `pending` → warna abu-abu (secondary), `active` → warna hijau (success), `suspended` → warna oranye (warning), `rejected` → warna merah (danger).
3. THE Owner_Panel SHALL menggunakan tipografi, spacing, dan komponen Bootstrap 5 secara konsisten di seluruh halaman, tanpa inline style yang mendefinisikan ulang properti yang sudah ditetapkan oleh kelas Bootstrap 5 pada elemen yang sama.
4. IF Owner menggunakan Owner Panel pada perangkat dengan lebar layar kurang dari 992px, THEN THE Owner_Panel SHALL menyembunyikan sidebar dan menampilkan hamburger menu. IF Owner menekan hamburger menu, THEN sidebar terbuka. IF Owner menekan hamburger menu kembali atau menekan area di luar sidebar, THEN sidebar menutup.
5. WHEN Owner menekan tombol aksi (suspend, reactivate, approve, reject), THE Owner_Panel SHALL segera mengubah label teks tombol menjadi "Memproses..." dan menonaktifkan tombol tersebut untuk mencegah double-submit. IF aksi selesai (berhasil atau gagal), THEN tombol diaktifkan kembali.

---

### Requirement 8: Audit Trail Perubahan Status Masjid

**User Story:** Sebagai Owner, saya ingin setiap perubahan status masjid tercatat secara otomatis, agar ada jejak histori yang dapat ditelusuri ketika dibutuhkan untuk audit atau penyelesaian sengketa.

#### Acceptance Criteria

1. WHEN Suspension_System berhasil mengubah status masjid menjadi `suspended`, THE Suspension_System SHALL mencatat log aktivitas menggunakan Laravel Log dengan level `info` dan format "Mosque suspended: mosque_id={id}, mosque_name={name}, by_user_id={owner_id}".
2. WHEN Reactivation_System berhasil mengubah status masjid menjadi `active`, THE Reactivation_System SHALL mencatat log aktivitas menggunakan Laravel Log dengan level `info` dan format "Mosque reactivated: mosque_id={id}, mosque_name={name}, by_user_id={owner_id}".
3. THE Suspension_System dan THE Reactivation_System SHALL selalu membungkus setiap operasi perubahan status masjid dalam satu database transaction, sehingga jika terjadi kegagalan parsial, tidak ada perubahan yang tersimpan secara tidak konsisten.
4. IF database transaction pada aksi suspend atau reactivate gagal di-commit, THEN THE Suspension_System atau THE Reactivation_System SHALL mencatat log error dengan detail exception dan mengembalikan respons kegagalan kepada Owner_Panel, tanpa menyimpan perubahan status apapun.
