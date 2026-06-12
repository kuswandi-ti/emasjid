# Requirements Document

## Introduction

Fitur ini memungkinkan Owner (super-admin) platform EMasjid untuk mengkonfigurasi fee platform melalui web panel. Fee platform dikenakan pada setiap donasi yang masuk melalui platform. Owner dapat mengatur persentase fee, mekanisme penarikan fee, dan mengaktifkan/menonaktifkan fee secara global. Konfigurasi disimpan dalam tabel `platform_settings` sebagai key-value store yang bersifat global (tidak scoped per masjid).

---

## Glossary

- **Owner**: Pengguna dengan role `super-admin` yang mengelola platform EMasjid secara keseluruhan.
- **Platform_Settings**: Tabel key-value store global (`platform_settings`) yang menyimpan konfigurasi platform, termasuk setting fee.
- **PlatformSettingService**: Service layer yang menangani operasi baca dan tulis ke `Platform_Settings`.
- **SettingController**: Controller pada namespace `Owner` yang menangani HTTP request konfigurasi fee.
- **Fee_Percentage**: Persentase fee platform dalam satuan basis poin (1 basis poin = 0.01%). Disimpan dengan key `platform_fee_percentage`. Rentang valid: 0–10000 (setara 0%–100%).
- **Fee_Mechanism**: Mekanisme pengenaan fee. Disimpan dengan key `platform_fee_mechanism`. Nilai valid: `added_to_donor` atau `deducted_from_donation`.
- **Fee_Active**: Status aktif/nonaktif fee platform. Disimpan dengan key `platform_fee_active`. Nilai: `1` (aktif) atau `0` (nonaktif).
- **Donation_Amount**: Nominal donasi yang diinginkan donatur, dalam satuan Rupiah (unsignedBigInteger).
- **Fee_Amount**: Jumlah fee yang dihitung dari donasi. Rumus: `floor(Donation_Amount * Fee_Percentage / 10000)`.
- **Payment_Amount**: Jumlah total yang dibayarkan oleh donatur.
- **Mosque_Receives**: Jumlah yang diterima masjid setelah fee diperhitungkan.
- **Basis_Point**: Satuan persentase. 1 basis poin = 0.01%. Contoh: 250 basis poin = 2.5%.
- **UpdateFeeSettingRequest**: Form Request Laravel yang memvalidasi input update konfigurasi fee.
- **Fee_Preview**: Kalkulasi fee secara real-time yang ditampilkan kepada Owner sebagai ilustrasi dampak konfigurasi.

---

## Requirements

### Requirement 1: Akses Halaman Konfigurasi Fee

**User Story:** Sebagai Owner, saya ingin mengakses halaman konfigurasi fee platform, sehingga saya bisa melihat dan mengubah setting fee kapan saja.

#### Acceptance Criteria

1. WHEN Owner mengakses halaman konfigurasi fee, THE SettingController SHALL menampilkan nilai `Fee_Percentage`, `Fee_Mechanism`, dan `Fee_Active` yang tersimpan saat ini dari `Platform_Settings`, dan form SHALL di-pre-populate dengan nilai tersebut.
2. IF pengguna yang mengakses halaman konfigurasi fee tidak memiliki role `super-admin`, THEN THE SettingController SHALL menolak akses dan mengembalikan HTTP 403 tanpa menampilkan data apapun.
3. THE PlatformSettingService SHALL membaca semua konfigurasi fee dari `Platform_Settings` dalam satu operasi (single query) saat halaman dimuat.
4. IF tabel `Platform_Settings` belum memiliki record untuk key fee tertentu saat halaman dimuat, THEN THE SettingController SHALL menampilkan nilai default: `Fee_Percentage = 0`, `Fee_Mechanism = added_to_donor`, `Fee_Active = 0`.

---

### Requirement 2: Update Konfigurasi Fee Percentage

**User Story:** Sebagai Owner, saya ingin mengatur persentase fee platform, sehingga platform mendapatkan pendapatan yang sesuai dari setiap donasi.

#### Acceptance Criteria

1. WHEN Owner mengubah `Fee_Percentage` dengan nilai integer antara 0 dan 10000 (inklusif), THE PlatformSettingService SHALL menyimpan nilai tersebut ke `Platform_Settings` dengan key `platform_fee_percentage` menggunakan operasi upsert, dan mengembalikan konfirmasi nilai yang tersimpan.
2. IF Owner mengirimkan nilai `Fee_Percentage` di luar rentang 0–10000, THEN THE UpdateFeeSettingRequest SHALL menolak permintaan dan mengembalikan pesan validasi error yang menyebutkan rentang yang diizinkan (0–10000).
3. IF Owner mengirimkan nilai `Fee_Percentage` yang bukan bilangan bulat non-negatif (contoh: desimal, string, negatif), THEN THE UpdateFeeSettingRequest SHALL menolak permintaan dan mengembalikan pesan validasi error yang menyebutkan persyaratan integer non-negatif.
4. IF pengguna yang mengirimkan form bukan Owner (bukan role `super-admin`), THEN THE SettingController SHALL menolak permintaan dan mengembalikan HTTP 403 tanpa mengubah nilai di `Platform_Settings`.

---

### Requirement 3: Update Mekanisme Fee

**User Story:** Sebagai Owner, saya ingin memilih mekanisme pengenaan fee, sehingga saya bisa menentukan apakah fee ditanggung donatur atau dipotong dari donasi.

#### Acceptance Criteria

1. WHEN Owner memilih `Fee_Mechanism` bernilai `added_to_donor`, THE PlatformSettingService SHALL menyimpan nilai `added_to_donor` ke `Platform_Settings` dengan key `platform_fee_mechanism` menggunakan operasi upsert (buat jika belum ada, update jika sudah ada), dan mengembalikan respons sukses.
2. WHEN Owner memilih `Fee_Mechanism` bernilai `deducted_from_donation`, THE PlatformSettingService SHALL menyimpan nilai `deducted_from_donation` ke `Platform_Settings` dengan key `platform_fee_mechanism` menggunakan operasi upsert, dan mengembalikan respons sukses.
3. IF Owner mengirimkan nilai `Fee_Mechanism` selain `added_to_donor` atau `deducted_from_donation`, THEN THE UpdateFeeSettingRequest SHALL menolak permintaan, mengembalikan pesan validasi error, dan nilai `platform_fee_mechanism` di `Platform_Settings` SHALL tetap tidak berubah.

---

### Requirement 4: Mengaktifkan dan Menonaktifkan Fee

**User Story:** Sebagai Owner, saya ingin mengaktifkan atau menonaktifkan fee platform, sehingga saya bisa menjalankan periode bebas fee tanpa mengubah konfigurasi lainnya.

#### Acceptance Criteria

1. WHEN Owner mengatur `Fee_Active` menjadi aktif, THE PlatformSettingService SHALL menyimpan nilai `1` ke `Platform_Settings` dengan key `platform_fee_active`, dan mengembalikan respons sukses; IF penyimpanan gagal, THE PlatformSettingService SHALL mengembalikan error.
2. WHEN Owner mengatur `Fee_Active` menjadi nonaktif, THE PlatformSettingService SHALL menyimpan nilai `0` ke `Platform_Settings` dengan key `platform_fee_active`, dan mengembalikan respons sukses; IF penyimpanan gagal, THE PlatformSettingService SHALL mengembalikan error.
3. IF `Fee_Active` bernilai `1`, THEN THE PlatformSettingService SHALL menghitung `Fee_Amount` menggunakan rumus `floor(Donation_Amount * Fee_Percentage / 10000)` dalam satuan Rupiah (bilangan bulat).
4. IF `Fee_Active` bernilai `0`, THEN THE PlatformSettingService SHALL mengembalikan `Fee_Amount` sebesar `0` Rupiah untuk semua nilai `Donation_Amount`, tanpa menjalankan rumus kalkulasi.
5. IF Owner mengirimkan nilai `Fee_Active` selain `0` atau `1`, THEN THE UpdateFeeSettingRequest SHALL menolak permintaan dan mengembalikan pesan validasi error.

---

### Requirement 5: Kalkulasi Fee Amount

**User Story:** Sebagai sistem, saya ingin menghitung fee yang tepat pada setiap donasi, sehingga nilai fee selalu konsisten dan akurat.

#### Acceptance Criteria

1. WHEN kalkulasi fee diminta dengan `Fee_Active` bernilai `1` dan `Donation_Amount` ≥ 1 Rupiah serta `Fee_Percentage` dalam rentang 0–10000, THE PlatformSettingService SHALL menghitung `Fee_Amount` menggunakan rumus `floor(Donation_Amount * Fee_Percentage / 10000)` dan mengembalikan hasil sebagai bilangan bulat non-negatif dalam satuan Rupiah.
2. WHEN kalkulasi fee diminta dengan `Fee_Active` bernilai `0`, THE PlatformSettingService SHALL mengembalikan `Fee_Amount` sebesar `0` tanpa memandang nilai `Donation_Amount` dan `Fee_Percentage`.
3. IF `Fee_Percentage` bernilai `0`, THEN THE PlatformSettingService SHALL mengembalikan `Fee_Amount` sebesar `0` untuk semua nilai `Donation_Amount` yang valid.
4. IF `Donation_Amount` atau `Fee_Percentage` berada di luar domain valid (Donation_Amount < 1 atau Fee_Percentage < 0 atau Fee_Percentage > 10000), THEN THE PlatformSettingService SHALL menolak kalkulasi dan mengembalikan error.

---

### Requirement 6: Mekanisme `added_to_donor`

**User Story:** Sebagai sistem, saya ingin menghitung total pembayaran donatur saat mekanisme `added_to_donor` aktif, sehingga masjid menerima penuh nominal donasi yang diinginkan donatur.

#### Acceptance Criteria

1. IF `Fee_Mechanism` adalah `added_to_donor` dan `Donation_Amount` ≥ 1 Rupiah, THEN THE PlatformSettingService SHALL menghitung `Payment_Amount` sebesar `Donation_Amount + Fee_Amount`, di mana `Fee_Amount = floor(Donation_Amount * Fee_Percentage / 10000)`.
2. IF `Fee_Mechanism` adalah `added_to_donor`, THEN THE PlatformSettingService SHALL menghitung `Mosque_Receives` sebesar `Donation_Amount` (sama persis dengan nominal donasi, tanpa potongan).
3. IF `Fee_Mechanism` adalah `added_to_donor` dan `Donation_Amount` < 1, THEN THE PlatformSettingService SHALL menolak kalkulasi dan mengembalikan error yang menyatakan nilai donasi tidak valid.

---

### Requirement 7: Mekanisme `deducted_from_donation`

**User Story:** Sebagai sistem, saya ingin menghitung penerimaan masjid saat mekanisme `deducted_from_donation` aktif, sehingga donatur membayar persis sesuai nominal yang diinginkan.

#### Acceptance Criteria

1. IF `Fee_Mechanism` adalah `deducted_from_donation`, THEN THE PlatformSettingService SHALL menghitung `Payment_Amount` sebesar `Donation_Amount` (donatur membayar tepat sesuai nominal yang diinginkan).
2. IF `Fee_Mechanism` adalah `deducted_from_donation` dan `Fee_Amount` < `Donation_Amount`, THEN THE PlatformSettingService SHALL menghitung `Mosque_Receives` sebesar `Donation_Amount - Fee_Amount`, dan SHALL memastikan `Mosque_Receives + Fee_Amount = Donation_Amount`.
3. IF `Fee_Mechanism` adalah `deducted_from_donation` dan `Fee_Amount` ≥ `Donation_Amount` (contoh: `Fee_Percentage = 10000`), THEN THE PlatformSettingService SHALL menghitung `Mosque_Receives` sebesar `0` (bukan nilai negatif) untuk menjaga integritas tipe `unsignedBigInteger`.
4. IF `Fee_Mechanism` adalah `deducted_from_donation` dan `Donation_Amount` < 1, THEN THE PlatformSettingService SHALL menolak kalkulasi dan mengembalikan error yang menyatakan nilai donasi tidak valid.

---

### Requirement 8: Tampilan Form Konfigurasi Fee

**User Story:** Sebagai Owner, saya ingin melihat form yang jelas dan mudah dipahami, sehingga saya tidak salah mengisi konfigurasi fee.

#### Acceptance Criteria

1. THE SettingController SHALL menampilkan form dengan: input numerik `Fee_Percentage`, pilihan radio/select `Fee_Mechanism` dengan dua opsi (`added_to_donor` dan `deducted_from_donation`), dan toggle boolean `Fee_Active`.
2. THE SettingController SHALL menampilkan helper text di bawah input `Fee_Percentage` yang mencantumkan rumus konversi (`basis_points / 100 = persentase`) beserta contoh konkret (contoh: "250 = 2.5%").
3. THE SettingController SHALL menampilkan deskripsi di samping setiap opsi `Fee_Mechanism` yang menyebutkan siapa yang menanggung fee dan efeknya: opsi `added_to_donor` SHALL menjelaskan bahwa donatur membayar lebih dari nominal donasi dan masjid menerima penuh; opsi `deducted_from_donation` SHALL menjelaskan bahwa donatur membayar tepat nominal donasi dan masjid menerima setelah dipotong fee.
4. WHEN Owner membuka halaman konfigurasi, THE form SHALL di-pre-populate dengan nilai `Fee_Percentage`, `Fee_Mechanism`, dan `Fee_Active` yang tersimpan di `Platform_Settings` saat ini.
5. WHEN Owner menyimpan konfigurasi berhasil, THE SettingController SHALL menampilkan flash message sukses yang terlihat selama minimal 3 detik kepada Owner.
6. IF validasi input gagal saat Owner mengirimkan form, THEN THE SettingController SHALL menampilkan pesan error per field di bawah field yang bermasalah, dan field lain yang valid SHALL tetap mempertahankan nilai yang diisikan Owner.

---

### Requirement 9: Preview Kalkulasi Fee Real-Time

**User Story:** Sebagai Owner, saya ingin melihat preview kalkulasi fee secara langsung saat mengisi form, sehingga saya bisa memahami dampak konfigurasi sebelum menyimpan.

#### Acceptance Criteria

1. THE SettingController SHALL menampilkan komponen `Fee_Preview` pada halaman konfigurasi dengan contoh nominal donasi tetap `Rp 100.000` sebagai `Donation_Amount` referensi.
2. WHEN Owner mengubah nilai `Fee_Percentage` pada form, THE `Fee_Preview` SHALL memperbarui tampilan `Fee_Amount`, `Payment_Amount`, dan `Mosque_Receives` secara real-time tanpa reload halaman dalam waktu ≤ 300ms setelah input berubah.
3. WHEN Owner mengubah pilihan `Fee_Mechanism` pada form, THE `Fee_Preview` SHALL memperbarui tampilan `Payment_Amount` dan `Mosque_Receives` secara real-time dalam waktu ≤ 300ms: jika `added_to_donor`, tampilkan `Payment_Amount = 100.000 + Fee_Amount` dan `Mosque_Receives = 100.000`; jika `deducted_from_donation`, tampilkan `Payment_Amount = 100.000` dan `Mosque_Receives = 100.000 - Fee_Amount`.
4. WHEN Owner menonaktifkan `Fee_Active` pada form, THE `Fee_Preview` SHALL memperbarui tampilan dalam waktu ≤ 300ms untuk menampilkan `Fee_Amount = Rp 0`, `Payment_Amount = Rp 100.000`, dan `Mosque_Receives = Rp 100.000`.
5. THE `Fee_Preview` SHALL menampilkan nilai `Donation_Amount`, `Fee_Amount`, `Payment_Amount`, dan `Mosque_Receives` dalam format mata uang Rupiah dengan pemisah ribuan menggunakan titik (contoh: "Rp 2.500").

---

### Requirement 10: Persistensi dan Konsistensi Data

**User Story:** Sebagai sistem, saya ingin konfigurasi fee tersimpan dengan benar dan konsisten, sehingga kalkulasi fee pada setiap donasi menggunakan setting terkini.

#### Acceptance Criteria

1. WHEN Owner menyimpan konfigurasi fee, THE PlatformSettingService SHALL menyimpan setiap key menggunakan operasi upsert, dan kalkulasi fee berikutnya SHALL menggunakan nilai terbaru yang tersimpan.
2. WHEN `Platform_Settings` belum memiliki record untuk key tertentu (`platform_fee_percentage`, `platform_fee_mechanism`, atau `platform_fee_active`), THE PlatformSettingService SHALL membuat record baru (insert) dengan key dan value yang diberikan.
3. WHEN `Platform_Settings` sudah memiliki record untuk key tertentu, THE PlatformSettingService SHALL memperbarui field `value` pada record yang ada (update) tanpa membuat duplikat.
4. THE `Platform_Settings` SHALL menyimpan nilai `Fee_Percentage` sebagai integer dalam rentang 0–10000 dalam satuan basis poin.
5. THE `Platform_Settings` SHALL menyimpan nilai `Fee_Mechanism` sebagai string dengan nilai tepat `added_to_donor` atau `deducted_from_donation`.
6. THE `Platform_Settings` SHALL menyimpan nilai `Fee_Active` sebagai string dengan nilai tepat `1` (aktif) atau `0` (nonaktif).
7. IF operasi upsert ke `Platform_Settings` gagal (contoh: database error), THEN THE PlatformSettingService SHALL mengembalikan error dan tidak mengubah state sebagian dari konfigurasi fee.
