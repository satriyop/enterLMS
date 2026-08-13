# EnterLMS — Redesign Tournament (Claude)

Mockup **desain saja** (HTML/CSS/JS statis). Bukan implementasi ke aplikasi Laravel/Vue.

Dua arah desain, **23 layar identik** di masing-masing arah, mencakup seluruh alur aplikasi saat ini.

---

## Cara membuka

Buka `index.html` di browser untuk halaman perbandingan A vs B, atau jalankan server lokal:

```bash
# dari root project
cd redesign-tournament/claude-design && python3 -m http.server 5177
# → http://localhost:5177
```

| Halaman | Isi |
|---------|-----|
| `index.html` | Perbandingan A vs B + tabel keputusan desain + panduan penilaian |
| `design-a/index.html` | Hub navigasi 23 layar Design A |
| `design-b/index.html` | Hub navigasi 23 layar Design B |

---

## Struktur

```text
claude-design/
├── index.html              # halaman perbandingan & penjurian
├── design-a/               # "Tenang" — clean, editorial, modern
│   ├── index.html          # hub 23 layar
│   ├── assets/
│   │   ├── styles.css      # design system (token = nama token shadcn app)
│   │   ├── shell.js        # registry halaman + render top nav
│   │   └── app.js          # lapisan interaksi (dipakai bersama A & B)
│   └── pages/              # 23 layar
└── design-b/               # "Terpandu" — guided, predictable, first-timer
    ├── index.html
    ├── assets/             # styles.css · shell.js (sidebar) · app.js
    └── pages/              # 23 layar
```

---

## Design A — "Tenang"

**Tesis:** ketenangan editorial. Konten adalah antarmukanya.

| Aspek | Pilihan |
|-------|---------|
| Tipografi | **Fraunces** (display) + **Inter** (UI) |
| Kanvas | Kertas hangat `#F8F7F4` |
| Aksen | Pinus tua `#0B5D4E`, emas `#9A6614` untuk pencapaian |
| Shell | Bilah atas ringkas, kanvas lebar, tanpa sidebar |
| Kepadatan | Lapang, garis rambut, bayangan nyaris nihil |
| Untuk | Pengguna yang sudah nyaman dengan produk digital modern |

Prinsip:
- Hirarki dibangun lewat **ruang dan skala tipografi**, bukan garis dan kotak.
- Aksen warna hanya muncul saat berarti (status, tindakan utama).
- Menu "Kelola" hanya tampil bila peran mengizinkan — bilah atas tetap pendek.
- Mode ujian memakai chrome paling minimal agar soal mendominasi.

**Risiko:** pengguna baru bisa ragu di 30 detik pertama karena tidak ada instruksi eksplisit.

---

## Design B — "Terpandu"

**Tesis:** keyakinan lewat struktur. Pengguna tidak pernah perlu menebak.

| Aspek | Pilihan |
|-------|---------|
| Tipografi | **Plus Jakarta Sans** — dirancang untuk identitas kota Jakarta |
| Kanvas | Biru dingin `#F4F6FB` |
| Aksen | Kobalt `#2547D0`, semantik kontras tinggi |
| Shell | Sidebar tetap berlabel + remah roti di setiap halaman |
| Kepadatan | Elevasi jelas, target sentuh 42–50 px |
| Untuk | Pengguna baru, lintas usia, lintas tingkat literasi digital |

Prinsip:
- Setiap halaman menyebut **namanya, fungsinya, dan satu tindakan utama**.
- Alur panjang dipecah jadi **langkah bernomor** (registrasi, checkout, editor kursus).
- Keadaan kosong menjelaskan **sebab, akibat, dan langkah pemulihan** — bukan sekadar "belum ada data".
- Kegagalan validasi muncul di kolom **dan** di panel "Kesiapan terbit" yang menghitung syarat.
- Sidebar menyembunyikan grup yang tidak bisa dipakai peran tersebut — pengguna baru hanya melihat pintu yang bisa dibuka.

**Risiko:** pengguna mahir bisa merasa dituntun berlebihan.

---

## Cakupan layar (identik di A & B)

| # | Layar | Peran | Rute aplikasi terkait |
|---|-------|-------|----------------------|
| 01 | Beranda publik | Publik | `home` |
| 02 | Masuk (+ 2FA) | Publik | `login`, `two-factor-challenge` |
| 03 | Daftar | Publik | `register` |
| 04 | Verifikasi sertifikat | Publik | `certificates.verify` |
| 05 | Dashboard | Learner | `dashboard`, `learner.dashboard` |
| 06 | Jelajahi kursus | Learner | `courses.index` |
| 07 | Detail kursus + enroll | Learner | `courses.show`, `courses.enroll` |
| 08 | Pembayaran | Learner | `courses.payment.create`, `payments.*` |
| 09 | Pembelajaran saya | Learner | `my-learning` |
| 10 | Ruang belajar | Learner | `courses.lessons.show`, `scorm.player.launch` |
| 11 | Kuis | Learner | `assessments.attempt` |
| 12 | Hasil kuis | Learner | `assessments.attempt.complete` |
| 13 | Sertifikat saya | Learner | `certificates.index` |
| 14 | Jalur pembelajaran | Learner | `learner.learning-paths.index/browse` |
| 15 | Progres jalur | Learner | `learner.learning-paths.progress` |
| 16 | Notifikasi (+ undangan) | Learner | `notifications.index`, `invitations.*` |
| 17 | Pengaturan | Learner | `settings.*` (profil, sandi, 2FA, tampilan) + `payments.index` |
| 18 | Kelola kursus | Content Manager | `courses.index` (mode kelola) |
| 19 | Editor kursus | Content Manager | `courses.edit`, `sections.*`, `lessons.*`, `courses.publish` |
| 20 | Bank soal | Content Manager | `question-bank.*`, `assessments.import-questions` |
| 21 | Penilaian | Trainer | `assessments.grade`, `assessments.grade.submit` |
| 22 | Administrasi | LMS Admin | `admin.users`, `admin.categories`, `admin.tags`, `admin.trash` |
| 23 | Laporan kepatuhan | Compliance Officer | `compliance.audit-reports.*` |

---

## Yang berfungsi di mockup

Keduanya adalah **prototipe interaktif**, bukan gambar statis:

- Ganti tema terang/gelap (tersimpan di `localStorage`, berlaku lintas halaman)
- Tab, akordeon, modal, dropdown, filter chip, toast
- Kuis: pilih jawaban, peta soal ikut menandai, hitung mundur waktu berjalan
- Wizard 5 langkah pada editor kursus (Design B)
- Enroll → status berubah tanpa reload
- "Tandai selesai" pada pelajaran → progres kursus naik
- Sidebar dapat diciutkan (Design B), drawer di layar kecil
- Navigasi berbasis peran — ubah `data-role` di `<body>` untuk melihat menu peran lain

Peran yang dapat dicoba: `learner`, `content_manager`, `trainer`, `teaching_assistant`,
`compliance_officer`, `auditor`, `lms_admin`.

---

## Verifikasi yang sudah dijalankan

| Uji | Hasil |
|-----|-------|
| Tautan internal | 46 halaman, semua tautan `.html` resolve |
| Registry vs berkas | Cocok 23/23 di kedua desain |
| Overflow horizontal | Bersih di 320 / 375 / 414 / 768 / 1024 / 1440 px |
| Galat JavaScript | 0 pada 46 halaman |
| Inisialisasi runtime | Shell, progress bar, ring, dan tab aktif di 46 halaman |

---

## Catatan implementasi

Kedua desain menyatakan warnanya sebagai CSS custom property dengan **nama yang sama
dengan token shadcn yang sudah dipakai aplikasi** (`--background`, `--foreground`,
`--primary`, `--muted-foreground`, `--border`, `--card`, …).

Konsekuensinya: pemenang dapat diadopsi dengan menulis ulang variabel di
`resources/css/app.css`, bukan menulis ulang 60+ komponen Vue. Perubahan tata letak
(sidebar vs top nav, page header, stepper) tetap memerlukan pekerjaan komponen.

Mode gelap di mockup memakai `[data-theme="dark"]` agar tombol ganti tema berdiri
sendiri. Di aplikasi, ini memetakan satu-ke-satu ke
`@custom-variant dark (&:is(.dark *))` yang sudah ada.

---

## Keputusan terbuka

**Model visibilitas navigasi per peran** ada di `design-*/assets/shell.js`
(`ROLE_NAV`, dan `ROLE_HIDE_ITEMS` di Design B).

Default saat ini: **sembunyikan yang tidak bisa dipakai**. Alternatifnya adalah
**tampilkan tapi nonaktifkan**, yang membuat pengguna sadar fitur itu ada dan bisa
meminta akses — dengan biaya sidebar yang lebih ramai bagi learner biasa.

Ini keputusan produk, bukan keputusan desain murni. Silakan sesuaikan peta tersebut
bila kebijakan akses EnterLMS menghendaki sebaliknya.

---

Data fiktif. Tidak ada backend. Bahasa UI: **Indonesia**. Konteks: perbankan / OJK.
