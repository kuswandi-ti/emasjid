---
inclusion: auto
---

# Project Rules - EMasjid

Kamu sedang bekerja di project EMasjid. Selalu ikuti dokumen-dokumen berikut sebagai acuan utama sebelum menulis atau mengubah kode:

## Dokumen Acuan

1. **Aturan Arsitektur & Koding:** #[[file:docs/AGENTS.md]]
2. **Pattern Implementasi:** #[[file:docs/SKILLS.md]]
3. **Istilah UI:** #[[file:docs/copywriting.md]]
4. **Kontrak API:** #[[file:docs/api-contract.md]]
5. **Desain Database:** #[[file:docs/database-design.md]]
6. **Panduan Deploy:** #[[file:docs/deployment.md]]
7. **Rencana Pengembangan:** #[[file:docs/development-plan.md]]
8. **Strategi Git:** #[[file:docs/git-strategy.md]]

## Aturan Ringkas

- Arsitektur: Controller → Service → Repository → Model
- Multi-tenant: semua data masjid harus scoped via `mosque_id`
- Repository: selalu inject interface, bukan implementasi
- Service: input harus via DTO, bukan Request atau array
- Terminologi kode: Bahasa Inggris
- Terminologi UI: Bahasa Indonesia
- Nilai uang: unsignedBigInteger (Rupiah, bukan decimal)
- Queue: database driver (shared hosting, tanpa Redis)
- Formatter: Laravel Pint
- Commit message: `{type}: {deskripsi}` (bahasa Inggris, present tense)
