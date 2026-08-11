# Enteraksi LMS — Redesign Tournament (Grok)

Mockup **desain saja** (HTML/CSS/JS). Bukan implementasi ke Laravel/Vue app.

## Struktur

```text
grok-design/
├── README.md
├── design-a/          # Clean · modern · visual polish
│   ├── index.html     # Hub navigasi semua screen
│   ├── assets/
│   └── pages/
└── design-b/          # First-timer · predictable · guided nav
    ├── index.html
    ├── assets/
    └── pages/
```

## Cara buka

Buka file HTML langsung di browser, atau:

```bash
# dari root project
cd redesign-tournament/grok-design/design-a && python3 -m http.server 5177
# Design A → http://localhost:5177

cd redesign-tournament/grok-design/design-b && python3 -m http.server 5178
# Design B → http://localhost:5178
```

## Screen set (identik di A & B)

| # | Screen | Role |
|---|--------|------|
| 01 | Home / Welcome | Publik |
| 02 | Login | Publik |
| 03 | Register | Publik |
| 04 | Dashboard learner | Learner |
| 05 | Browse kursus | Learner |
| 06 | Detail kursus + enroll | Learner |
| 07 | Pembelajaran saya | Learner |
| 08 | Lesson player | Learner |
| 09 | Assessment / kuis | Learner |
| 10 | Sertifikat saya | Learner |
| 11 | Verifikasi sertifikat (publik) | Publik |
| 12 | Learning paths | Learner |
| 13 | Progress learning path | Learner |
| 14 | Notifikasi | Learner |
| 15 | CM — kelola kursus | Content Manager |
| 16 | CM — editor outline | Content Manager |
| 17 | Admin overview | LMS Admin |

## Design A — prinsip

- Whitespace besar, hirarki tipografi tegas
- Visual minimal: border soft, shadow halus, accent monokrom + biru
- Navigasi top bar ringkas
- Fokus “content first”, UI tidak berisik
- Cocok user yang sudah nyaman dengan produk digital modern

## Design B — prinsip

- Sidebar tetap (app shell) — “di mana saya?” selalu jelas
- Breadcrumb + judul halaman + helper text
- Step indicator di alur belajar / kuis
- CTA primer selalu satu yang dominan
- Empty state & label eksplisit untuk first-timer
- Tetap menarik: warna progress, icon, card jelas

## Evaluasi (tournament)

Bandingkan A vs B pada:

1. First 30 detik (apakah tahu harus klik apa?)
2. Alur enroll → belajar → selesai → sertifikat
3. Kejelasan admin vs learner
4. Visual appeal
5. Density informasi vs ketenangan

## Catatan

- Data dummy / fake, tombol tidak memanggil backend
- Bahasa UI: **Indonesia**
- Brand: **Enteraksi** (kepatuhan perbankan / OJK)
