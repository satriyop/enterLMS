# EnterLMS — Task System

Sistem kerja **baru** untuk development. Ganti pola lama di `.ai/investigation/` dan catatan ad-hoc yang sudah stale.

## Direktori

| Path | Isi | Di-track git? |
|------|-----|----------------|
| `tasks/roadmap/` | Urutan kerja ke ADR 001 | Ya |
| `tasks/audit/` | Mapping kesiapan (ready / partial / missing) | Ya |
| `tasks/backlog/` | Antrian visi (Tutor, Grade Proposal) | Ya |
| `tasks/parked/` | Item lama; bukan path ke ADR 001 | Ya |
| `tasks/done/` | Item selesai + ringkasan arsip | Ya |
| `tasks/artifact/` | Catatan kerja sementara, draft, dump | **Tidak** (gitignore) |

## Status legend

| Status | Arti |
|--------|------|
| **ready** | Bisa dipakai end-to-end untuk skenario utamanya; ada UI + domain + tests |
| **partial** | Fondasi ada, tapi alur belum utuh / ada gap penting |
| **missing** | Belum diimplementasi (atau hanya placeholder) |
| **n/a** | Tidak direncanakan untuk fase ini |

## Alur kerja

```text
1. Baca CONTEXT.md + docs/adr/001-ai-first-class-lms.md  → produk
2. Baca tasks/roadmap/                   → urutan kerja ke visi itu
3. Baca tasks/audit/capability-map.md    → apa yang siap / gap
4. Ambil 1 item dari tasks/backlog/      → implement + test
5. Selesai → pindahkan ke tasks/done/    → update capability-map
6. Catatan sementara hanya di artifact/  → jangan commit
```

## Aturan file backlog

- Satu item = satu file: `B-NNN-short-slug.md`
- Header wajib: id, title, status, priority, area, depends_on
- Acceptance criteria harus testable
- Setelah done: `git mv tasks/backlog/B-NNN-....md tasks/done/` dan set `status: done`

## Yang **tidak** dipakai lagi sebagai sumber kebenaran

| Legacy | Ganti dengan |
|--------|----------------|
| `.ai/investigation/*` | `tasks/audit/` + `tasks/done/` |
| Tracker status di chat | File di `tasks/` |
| TODO lokal acak di root | `tasks/artifact/` (local only) |

Requirement/user-story di `.ai/` boleh tetap ada sebagai referensi produk, tapi **status eksekusi** hanya di `tasks/`.
