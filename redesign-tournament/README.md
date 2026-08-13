# Redesign Tournament

Mockup kompetisi desain UI/UX untuk EnterLMS (**bukan** implementasi app).

Setiap entry berisi dua arah desain:

- **Design A** — clean, modern, visually appealing
- **Design B** — mudah dinavigasi, intuitif, ramah pengguna pertama kali

---

## Entry

| Entry | Arah desain | Layar | Buka |
|-------|-------------|-------|------|
| [claude-design/](./claude-design/) | A "Tenang" · B "Terpandu" | 23 × 2 | [`claude-design/index.html`](./claude-design/index.html) |
| [grok-design/](./grok-design/) | A clean · B first-timer | 17 × 2 | [`grok-design/design-a/index.html`](./grok-design/design-a/index.html) |

### Buka cepat

```bash
# dari root project
cd redesign-tournament/claude-design && python3 -m http.server 5177   # → http://localhost:5177
cd redesign-tournament/grok-design/design-a && python3 -m http.server 5178
cd redesign-tournament/grok-design/design-b && python3 -m http.server 5179
```

Entry Claude punya halaman perbandingan A vs B sendiri di `claude-design/index.html`,
lengkap dengan tabel keputusan desain dan panduan penilaian.

---

## Cakupan

| Area | Claude | Grok |
|------|--------|------|
| Publik (beranda, login, daftar, verifikasi sertifikat) | ✅ | ✅ |
| Alur learner (jelajah → enroll → belajar → kuis → hasil → sertifikat) | ✅ | ✅ |
| Jalur pembelajaran + progres | ✅ | ✅ |
| Notifikasi & undangan | ✅ | ✅ |
| Content Manager (kelola kursus, editor outline) | ✅ | ✅ |
| Admin overview | ✅ | ✅ |
| **Pembayaran / checkout** | ✅ | — |
| **Bank soal** | ✅ | — |
| **Penilaian esai (Trainer)** | ✅ | — |
| **Laporan kepatuhan / audit trail** | ✅ | — |
| **Pengaturan (profil, sandi, 2FA, tampilan)** | ✅ | — |

---

## Kriteria penilaian

1. **Uji 30 detik** — buka dashboard tanpa penjelasan. Jelas apa yang harus diklik?
2. **Alur inti** — jelajah → detail → daftar → belajar → kuis → hasil → sertifikat.
3. **Alur pengelola** — seberapa cepat terlihat *mengapa* sebuah kursus belum bisa terbit?
4. **Kejelasan peran** — layar learner vs admin terasa satu produk, atau dua?
5. **Daya tarik visual** — mana yang lebih meyakinkan sebagai produk perbankan?
6. **Kepadatan vs ketenangan** — setelah 10 layar, mana yang masih nyaman dilihat?
7. **Ponsel & mode gelap** — kecilkan ke 375 px, nyalakan mode gelap.
8. **Kemudahan adopsi** — seberapa besar pekerjaan untuk membawanya ke Vue/Tailwind?

---

## Catatan

- Semua data dummy/fiktif, tombol tidak memanggil backend.
- Bahasa UI: **Indonesia**. Konteks: perbankan, kepatuhan OJK.
- Brand: **EnterLMS**.
- Tidak ada satu pun mockup yang diimplementasikan ke aplikasi.
