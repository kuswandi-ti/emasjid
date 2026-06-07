# Git Strategy - EMasjid

> Panduan strategi Git untuk pengembangan project EMasjid.
> Berlaku untuk semua developer dan AI agent yang bekerja di repository ini.

---

## Daftar Isi

1. [Repository Structure](#1-repository-structure)
2. [Branch Strategy](#2-branch-strategy)
3. [Alur Kerja](#3-alur-kerja)
4. [Naming Convention](#4-naming-convention)
5. [Commit Message](#5-commit-message)
6. [Tagging & Versioning](#6-tagging--versioning)
7. [Pull Request](#7-pull-request)
8. [GitHub Settings](#8-github-settings)
9. [Gitignore](#9-gitignore)
10. [Aturan & Larangan](#10-aturan--larangan)

---

## 1. Repository Structure

Project ini menggunakan **monorepo** — satu repository untuk semua codebase.

```text
emasjid/
├── web/              ← Laravel (Backend + Owner Panel + Admin Panel)
├── emasjid_app/      ← Flutter (Mobile App Jamaah)
├── docs/             ← Dokumentasi project
├── .gitignore
└── README.md
```

### Alasan Monorepo

- Satu tempat untuk semua kode dan dokumentasi
- Docs selalu sinkron dengan kode
- Mudah di-manage untuk tim kecil
- Tidak perlu sinkronisasi antar repo

---

## 2. Branch Strategy

### Diagram

```text
main (production)
 └── staging (pre-production testing)
      └── develop (development aktif)
           ├── feature/mosque-registration
           ├── feature/donation-flow
           ├── fix/fee-calculation
           └── fix/tenant-scope-leak
```

### Definisi Branch

| Branch | Fungsi | Siapa yang merge | Deploy ke |
|--------|--------|------------------|-----------|
| `main` | Kode production yang live dan stabil | Lead / solo developer | Production (shared hosting) |
| `staging` | Testing sebelum ke production | Lead / solo developer | Staging environment |
| `develop` | Integrasi semua fitur yang sedang dikembangkan | Developer | Local / dev server |
| `feature/*` | Pengembangan fitur baru | Developer | - |
| `fix/*` | Perbaikan bug non-urgent | Developer | - |
| `hotfix/*` | Perbaikan bug urgent di production | Developer | - |

### Lifecycle Branch

| Branch | Dibuat dari | Merge ke | Dihapus setelah merge? |
|--------|-------------|----------|------------------------|
| `main` | - | - | Tidak pernah |
| `staging` | `main` (sekali) | `main` | Tidak pernah |
| `develop` | `staging` (sekali) | `staging` | Tidak pernah |
| `feature/*` | `develop` | `develop` | Ya |
| `fix/*` | `develop` | `develop` | Ya |
| `hotfix/*` | `main` | `main` + `develop` | Ya |

---

## 3. Alur Kerja

### 3.1 Develop Fitur Baru

```bash
# 1. Pastikan develop up-to-date
git checkout develop
git pull origin develop

# 2. Buat branch fitur
git checkout -b feature/mosque-registration

# 3. Coding & commit (bisa multiple commits)
git add .
git commit -m "feat: add mosque registration form"
git commit -m "feat: add mosque approval flow"

# 4. Push branch
git push -u origin feature/mosque-registration

# 5. Buat Pull Request ke develop (via GitHub)
# Atau merge lokal:
git checkout develop
git merge feature/mosque-registration
git push origin develop

# 6. Hapus branch fitur
git branch -d feature/mosque-registration
git push origin --delete feature/mosque-registration
```

### 3.2 Testing di Staging

```bash
# 1. Merge develop ke staging
git checkout staging
git pull origin staging
git merge develop
git push origin staging

# 2. Deploy ke staging environment
# 3. Test manual / otomatis
# 4. Jika ada bug, fix di develop lalu merge ulang ke staging
```

### 3.3 Release ke Production

```bash
# 1. Merge staging ke main
git checkout main
git pull origin main
git merge staging
git push origin main

# 2. Beri tag versi
git tag -a v1.0.0 -m "Release v1.0.0: MVP launch"
git push origin v1.0.0

# 3. Deploy ke production (shared hosting)
```

### 3.4 Hotfix (Bug Urgent di Production)

```bash
# 1. Buat branch dari main
git checkout main
git pull origin main
git checkout -b hotfix/fix-payment-callback

# 2. Fix & commit
git add .
git commit -m "hotfix: fix duitku callback signature validation"

# 3. Merge ke main
git checkout main
git merge hotfix/fix-payment-callback
git push origin main
git tag -a v1.0.1 -m "Hotfix: payment callback"
git push origin v1.0.1

# 4. Deploy ke production

# 5. Merge balik ke develop (agar fix tidak hilang)
git checkout develop
git merge hotfix/fix-payment-callback
git push origin develop

# 6. Hapus branch hotfix
git branch -d hotfix/fix-payment-callback
git push origin --delete hotfix/fix-payment-callback
```

### 3.5 Fase Awal (Simplified)

Untuk fase awal (solo developer, belum ada staging server), bisa skip branch `staging`:

```text
main (production)
 └── develop (development)
      └── feature/*
      └── fix/*
```

Alur: `feature → develop → main (deploy)`

Tambahkan `staging` nanti saat:
- Tim bertambah (perlu review sebelum production)
- Ada staging server terpisah
- Volume fitur meningkat

---

## 4. Naming Convention

### Branch Names

| Tipe | Format | Contoh |
|------|--------|--------|
| Feature | `feature/{domain}-{deskripsi}` | `feature/mosque-registration` |
| Feature | `feature/{domain}-{deskripsi}` | `feature/donation-duitku-integration` |
| Bug fix | `fix/{deskripsi}` | `fix/donation-fee-calculation` |
| Bug fix | `fix/{domain}-{deskripsi}` | `fix/finance-export-pdf-error` |
| Hotfix | `hotfix/{deskripsi}` | `hotfix/fix-payment-callback` |

### Rules

- Gunakan lowercase dan dash (`-`) sebagai separator
- Nama harus deskriptif tapi singkat
- Prefix domain jika relevan (mosque, donation, finance, schedule, announcement)
- Jangan pakai spasi, underscore, atau karakter spesial

---

## 5. Commit Message

### Format

```text
{type}: {deskripsi singkat}

{body opsional - penjelasan lebih detail}
```

### Types

| Type | Kapan Dipakai | Contoh |
|------|---------------|--------|
| `feat` | Fitur baru | `feat: add mosque registration with approval flow` |
| `fix` | Bug fix | `fix: fix tenant scope leak on donation query` |
| `refactor` | Refactor tanpa ubah behavior | `refactor: extract fee calculation to service` |
| `docs` | Update dokumentasi | `docs: update api-contract with notification endpoints` |
| `style` | Formatting, tidak ubah logic | `style: run pint formatter on all files` |
| `test` | Tambah/update test | `test: add donation flow feature test` |
| `chore` | Maintenance | `chore: upgrade spatie/laravel-permission to 6.x` |
| `hotfix` | Fix urgent production | `hotfix: fix duitku callback signature` |
| `perf` | Improvement performa | `perf: add index on donations mosque_id + status` |

### Rules

- Deskripsi singkat, maksimal 72 karakter
- Gunakan bahasa Inggris
- Gunakan present tense ("add" bukan "added")
- Tidak perlu titik di akhir
- Body opsional, untuk penjelasan "mengapa" bukan "apa"

### Contoh Lengkap

```text
feat: add donation flow with duitku payment gateway

- Create DonationService with fee calculation
- Integrate Duitku API for payment request
- Add callback handler for payment confirmation
- Record confirmed donation as cash income
```

---

## 6. Tagging & Versioning

### Format

```text
v{major}.{minor}.{patch}
```

| Bagian | Kapan Naik | Contoh |
|--------|------------|--------|
| Major | Breaking change, rewrite besar | `v2.0.0` |
| Minor | Fitur baru, non-breaking | `v1.1.0` |
| Patch | Bug fix, hotfix | `v1.0.1` |

### Contoh Timeline

```text
v1.0.0  - MVP launch (7 modul dasar)
v1.0.1  - Hotfix: payment callback
v1.1.0  - Fitur: export PDF laporan
v1.2.0  - Fitur: push notification kegiatan
v2.0.0  - Redesign arsitektur / migrasi VPS
```

### Cara Membuat Tag

```bash
# Lightweight tag
git tag v1.0.0

# Annotated tag (recommended)
git tag -a v1.0.0 -m "Release v1.0.0: MVP launch with 7 modules"

# Push tag
git push origin v1.0.0

# Push semua tag
git push origin --tags
```

---

## 7. Pull Request

### Kapan Pakai PR

- Wajib jika tim > 1 developer
- Opsional untuk solo developer (bisa merge lokal)
- Wajib untuk merge ke `main` (agar ada audit trail)

### Template PR

```markdown
## Ringkasan

{Deskripsi singkat perubahan}

## Tipe Perubahan

- [ ] Fitur baru
- [ ] Bug fix
- [ ] Refactor
- [ ] Dokumentasi
- [ ] Lainnya

## Area yang Terpengaruh

- [ ] Web (Laravel)
- [ ] Mobile (Flutter)
- [ ] Dokumentasi

## Checklist

- [ ] Kode sudah diformat (Pint / dart format)
- [ ] Tidak ada `dd()`, `dump()`, atau `print()` debug
- [ ] Migration sudah ditest (up + down)
- [ ] Multi-tenant scope aman (tidak bocor lintas masjid)
- [ ] Test sudah ditulis / diupdate
- [ ] Dokumentasi diupdate (jika perlu)

## Screenshots (jika ada perubahan UI)

{Tambahkan screenshot}
```

---

## 8. GitHub Settings

### Repository Settings

| Setting | Nilai |
|---------|-------|
| Visibility | **Private** (closed source) |
| Default branch | **`develop`** |
| Issues | Enabled |
| Projects | Opsional |
| Wiki | Disabled (pakai docs/ di repo) |

### Ubah Default Branch

1. Settings → General → Default branch
2. Ubah dari `main` ke `develop`
3. Ini membuat PR baru default target ke `develop`

### Branch Protection Rules (Opsional)

Untuk branch `main`:

1. Settings → Branches → Add rule
2. Branch name pattern: `main`
3. Centang:
   - ✅ Require a pull request before merging
   - ✅ Require approvals: 1 (skip jika solo)
   - ✅ Do not allow bypassing the above settings

Untuk solo developer di fase awal, branch protection bisa di-skip dulu.

---

## 9. Gitignore

File `.gitignore` di root repo harus cover Laravel + Flutter + IDE:

```text
# ===== Laravel (web/) =====
web/vendor/
web/node_modules/
web/.env
web/.env.backup
web/.env.production
web/storage/*.key
web/storage/framework/cache/data/*
web/storage/framework/sessions/*
web/storage/framework/views/*
web/storage/logs/*
web/public/hot
web/public/storage
web/public/build/
web/bootstrap/cache/*

# ===== Flutter (emasjid_app/) =====
emasjid_app/.dart_tool/
emasjid_app/.packages
emasjid_app/build/
emasjid_app/.flutter-plugins
emasjid_app/.flutter-plugins-dependencies
emasjid_app/android/.gradle/
emasjid_app/android/local.properties
emasjid_app/android/app/google-services.json
emasjid_app/ios/Pods/
emasjid_app/ios/.symlinks/
emasjid_app/ios/Flutter/Generated.xcconfig
emasjid_app/ios/Runner/GoogleService-Info.plist
emasjid_app/.pub-cache/
emasjid_app/pubspec.lock

# ===== IDE =====
.idea/
.vscode/
*.swp
*.swo
.DS_Store
Thumbs.db

# ===== OS =====
*.log
*.tmp
*.bak
```

### Catatan `.gitignore`

| File | Alasan di-ignore |
|------|------------------|
| `.env` | Berisi secrets (DB password, API keys) |
| `google-services.json` | Firebase credentials (jangan expose) |
| `vendor/`, `node_modules/` | Dependencies (install via composer/npm) |
| `build/` | Generated files |
| `storage/logs/*` | Log files |

### File yang HARUS di-commit

| File | Alasan |
|------|--------|
| `.env.example` | Template environment variables |
| `composer.lock` | Lock versi exact dependencies |
| `package-lock.json` | Lock versi exact npm dependencies |
| `pubspec.yaml` | Flutter dependencies |
| `docs/*` | Dokumentasi project |

---

## 10. Aturan & Larangan

### Aturan Wajib

| # | Aturan |
|---|--------|
| 1 | Tidak boleh push langsung ke `main` (selalu via merge dari staging/develop) |
| 2 | Setiap merge ke `main` harus diberi tag versi |
| 3 | Branch `feature/*` dan `fix/*` harus dihapus setelah merge |
| 4 | Commit message harus mengikuti format yang ditentukan |
| 5 | Tidak boleh commit file `.env`, secrets, atau credentials |
| 6 | Tidak boleh commit file debug (`dd()`, `dump()`, `print()` untuk debugging) |
| 7 | Jalankan formatter sebelum commit (`./vendor/bin/pint` untuk Laravel) |
| 8 | Pastikan `composer.lock` dan `package-lock.json` ikut ter-commit |

### Larangan

| # | Larangan | Alasan |
|---|----------|--------|
| 1 | `git push --force` ke `main` atau `develop` | Bisa menghapus history orang lain |
| 2 | `git reset --hard` di shared branch | Rewrite history yang sudah di-push |
| 3 | Commit file besar (>10MB) | Memperlambat clone/pull |
| 4 | Commit generated files (vendor, node_modules, build) | Bloat repository |
| 5 | Merge branch yang belum ditest | Bisa break develop/staging |

### Tips

- Commit sering, dengan pesan yang jelas
- Satu commit = satu perubahan logis (jangan campur fitur + fix di satu commit)
- Pull sebelum push untuk menghindari conflict
- Jika conflict, resolve di branch fitur sebelum merge ke develop

---

## Setup Awal Repository

```bash
# 1. Inisialisasi
git init
git add .
git commit -m "chore: initial project setup"

# 2. Buat branch structure
git branch -m main
git checkout -b develop

# 3. Tambahkan remote
git remote add origin https://github.com/username/emasjid.git

# 4. Push semua branch
git push -u origin main
git push -u origin develop

# 5. (Opsional) Buat staging
git checkout -b staging main
git push -u origin staging
git checkout develop
```

Setelah ini, development dilakukan di branch `develop` dan `feature/*`.

---

## Penutup

Strategi ini dirancang agar fleksibel:
- Fase awal (solo): cukup `develop` + `main`
- Fase scale (tim): tambah `staging` + branch protection + PR wajib

Yang penting konsisten. Lebih baik strategi sederhana yang diikuti, daripada strategi kompleks yang diabaikan.
