# Audit

Hasil pemetaan codebase **sekarang** — kesiapan fitur **dan** stabilitas/tech debt.

| File | Isi | Kapan baca |
|------|-----|------------|
| [capability-map.md](./capability-map.md) | Ready / partial / missing per **capability produk** | Roadmap, status fitur |
| [tech-debt-architecture-2026-08-12.md](./tech-debt-architecture-2026-08-12.md) | **Tech debt / architecture / code smell** + go/no-go sebelum fitur baru | Sebelum B-013, B-001, multi-tenancy, atau “stabil dulu?” |

## Cara update

1. Setelah fitur selesai → status di `capability-map.md`
2. Setelah harden / debt burn-down → update atau supersede tech-debt audit (tanggal baru di nama file atau section “Updated”)
3. Pindahkan backlog selesai ke `tasks/done/`
4. Jangan mengedit file legacy di `.ai/investigation/` — itu arsip

## Sumber kebenaran

| Pertanyaan | File |
|------------|------|
| Fitur X sudah ready? | `capability-map.md` |
| Aman nambah fitur? Apa sisa debt? | `tech-debt-architecture-*.md` |
| Item kerja aktif | `tasks/backlog/` |
| Arah fase | `tasks/roadmap/` |
