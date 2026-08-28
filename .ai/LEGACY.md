# Legacy AI planning docs

Status eksekusi development **pindah ke `tasks/`**.

| Dulu | Sekarang |
|------|----------|
| `.ai/investigation/*` | `tasks/audit/` + `tasks/done/` + `tasks/backlog/` |
| Ad-hoc tracker di chat | File di `tasks/` |
| Catatan kerja acak | `tasks/artifact/` (local, gitignored) |

Folder `investigation/` di-ignore di git agar tidak rancu dengan sistem baru.

---

## ⚠️ Ini arsip, bukan requirement

`requirement/`, `user story/`, `features/`, `implementation/`, dan `investigation/`
adalah **catatan sejarah**. Jangan mengambil requirement dari situ.

Otoritas yang berlaku sekarang:

| Pertanyaan | Jawabannya ada di |
|------------|-------------------|
| Produk ini apa, istilahnya apa, apa yang di luar cakupan | `CONTEXT.md` |
| Kenapa diputuskan begitu | `docs/adr/` |
| Syarat build lintas fitur | `.ai/guidelines/` |

**Hanya `.ai/guidelines/` yang hidup.** Boost menyusunnya ke dalam `CLAUDE.md`, jadi
ia masuk ke konteks setiap sesi. Folder lain di `.ai/` tidak.

Jangan pernah mengambil requirement dari folder arsip tanpa mengeceknya ke
`CONTEXT.md` lebih dulu.
