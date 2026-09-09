<?php

namespace Database\Seeders\Concerns;

/**
 * Syllabus for AI untuk Kampus.
 *
 * Running case: one constructed rapat at Unissula about vendor "Gema Kampus"
 * and three modules. Public campus facts only. Tokoh (Bu Laila, Pak Irfan,
 * dr. Rina, Dimas, Alya) are examples, not real sivitas.
 */
trait BuildsAiForCampusSyllabus
{
    /**
     * @return list<array{title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    protected function sections(): array
    {
        return [
            $this->sectionKonteks(),
            $this->sectionAdopsi(),
            $this->sectionPetaJalan(),
            $this->sectionTigaMeja(),
            $this->sectionData(),
            $this->sectionKapasitas(),
            $this->sectionDeliverable(),
            $this->sectionPustaka(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private function textLesson(string $title, string $description, int $duration, array $blocks, bool $preview = false): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'content_type' => 'text',
            'duration' => $duration,
            'is_free_preview' => $preview,
            'rich_content' => $this->doc($blocks),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array{filename: string, title: string, paragraphs: list<string>}  $pdf
     * @return array<string, mixed>
     */
    private function documentLesson(string $title, string $description, int $duration, array $blocks, array $pdf): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'content_type' => 'document',
            'duration' => $duration,
            'rich_content' => $this->doc($blocks),
            'pdf' => $pdf,
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionKonteks(): array
    {
        return [
            'title' => 'Konteks Unissula dan mandat pimpinan',
            'description' => 'Satu rapat, tiga modul vendor, empat kursi. Bukan slide generik.',
            'lessons' => [
                $this->textLesson(
                    'Untuk siapa kursus ini',
                    'Sivitas yang harus menjawab vendor di Aula GKB, bukan yang men-deploy.',
                    18,
                    [
                        $this->heading('Untuk siapa kursus ini', 1),
                        $this->paragraph('Kursus ini untuk orang yang harus mengambil sikap di rapat Unissula: dekan, dosen, rektorat, dan pimpinan Yayasan Badan Wakaf Sultan Agung (YBWSA). Kamu tidak perlu menulis kode. Kamu perlu keputusan yang sama sebelum ada yang menandatangani tools.'),
                        $this->paragraph('Kasus berjalannya satu, dan tidak akan diganti menjadi “kampus X”: Universitas Islam Sultan Agung (Unissula), Semarang, https://unissula.ac.id/. Open Course, gratis, Bahasa Indonesia. Menyelesaikan kursus ini tidak membuka Course terbatas dan tidak men-deploy apa pun.'),
                        $this->paragraph('Benang merahnya satu rapat contoh di Aula GKB Kampus Kaligawe. Vendor fiktif Gema Kampus menawarkan “AI campus” dalam tiga modul: Chat PMB (chatbot penerimaan), Baca Tugas (add-on LMS yang membaca teks tugas mahasiswa), dan Kenal Mahasiswa (sambungan ke SIA supaya chatbot “kenal” NIM dan status). Tokoh contoh — bukan sivitas nyata — akan kembali: Bu Laila (dosen Informatika FTI), Pak Irfan (dosen Fakultas Hukum), dr. Rina (dosen FK), Dimas (mahasiswa Informatika), Alya (mahasiswa FH). Kursi nyata yang harus menjawab memakai nama jabatan publik: rektorat dan YBWSA. Kami tidak mengarang ucapan Rektor atau Ketua YBWSA tentang AI.'),
                        $this->paragraph('Yang kamu kuasai di akhir: peta jalan empat tahap; tiga meja (pengajaran, tata kelola, operasional); tautan ke UU 27/2022 PDP, Permendikbudristek 39/2021, Panduan GenAI Kemendikbudristek 2024, dan UNESCO; plus deliverable yang sudah terisi untuk rapat Gema Kampus, bukan checklist kosong.'),
                        $this->heading('Empat kursi, vendor menunggu di luar'),
                        $this->bullets([
                            'YBWSA — kontrak di atas ambang, reputasi wakaf, data sivitas skala universitas.',
                            'Rektorat — kebijakan yang berlaku di 13 fakultas, janji akreditasi Unggul.',
                            'Dekan — pilot fakultas; FTI boleh mulai, FK dan FH tidak boleh diabaikan.',
                            'Dosen — silabus dan asesmen. Diam di kelas adalah kebijakan tersembunyi.',
                        ]),
                        $this->diagram(
                            'Empat kursi Unissula memutuskan. Gema Kampus tidak duduk di meja sampai lima keputusan tertulis.',
                            [
                                ['id' => 'ybwsa', 'label' => 'YBWSA', 'hint' => 'kontrak'],
                                ['id' => 'rektor', 'label' => 'Rektorat', 'hint' => 'kebijakan'],
                                ['id' => 'dekan', 'label' => 'Dekan', 'hint' => 'pilot'],
                                ['id' => 'dosen', 'label' => 'Dosen', 'hint' => 'silabus'],
                            ],
                            [],
                            4,
                        ),
                        $this->quote('UNESCO dan Panduan GenAI Kemendikbudristek 2024 sejalan di satu hal: kapasitas manusia dan kebijakan dulu. Peta jalan yang dewasa kelihatan lambat di minggu pertama, dan murah di tahun kedua.'),
                    ],
                    preview: true,
                ),
                $this->textLesson(
                    'Unissula sebagai kasus berjalan',
                    'FTI sudah mengajar kecerdasan buatan. Yang belum ada: kebijakan GenAI untuk 13 fakultas.',
                    20,
                    [
                        $this->heading('Unissula sebagai kasus berjalan', 1),
                        $this->paragraph('Universitas Islam Sultan Agung (UNISSULA) berdiri 20 Mei 1962 di bawah YBWSA (31 Juli 1950). Kampus Kaligawe, Jalan Raya Kaligawe Km 4, Semarang. Moto publik: Bismillah Membangun Generasi Khaira Ummah (QS Ali Imran 110). Budaya Akademik Islami (BudAI) dideklarasikan 18 Agustus 2005: penguatan ruhiyah dan iptek berjalan bersama. Adopsi AI yang menabrak kejujuran BudAI bukan inovasi.'),
                        $this->paragraph('Yang membuat Unissula bukan stiker. Panduan Akademik FTI 2024/2025 menuliskan visi Informatika: unggul dalam pengembangan bidang kecerdasan buatan, berkontribusi internasional pada 2036, dalam kerangka rahmatan lil’alamin. Misi prodi berfokus pada kecerdasan buatan di pendidikan, penelitian, dan pengabdian. Kurikulum publik Informatika sudah memuat mata kuliah Kecerdasan Buatan, Data Mining, Pengolahan Citra Digital, Pengenalan Pola. FTI juga menonjolkan robotik di kanal publik Unissula.'),
                        $this->paragraph('Artinya rapat Gema Kampus bukan “apakah Unissula boleh sentuh AI”. Informatika sudah menyentuh. Pertanyaannya: 12 fakultas lain diam tentang GenAI di tugas, ujian, dan skripsi; data SIA dan rekaman belum punya peta; vendor menawarkan menyambung semuanya. Kampus yang mengajar AI tanpa kebijakan GenAI untuk FH dan FK sedang berjalan timpang.'),
                        $this->table(
                            ['Fakta publik', 'Sumber', 'Yang berubah di rapat Gema Kampus'],
                            [
                                ['Akreditasi institusi Unggul BAN-PT; 13 fakultas, 45 program studi', 'unissula.ac.id, LLDIKTI VI', 'Janji mutu SPMI; kebijakan AI masuk penjaminan mutu, bukan memo IT'],
                                ['PTS di bawah YBWSA, bukan PTN', 'ybw-sa.org', 'Modul Kenal Mahasiswa (SIA) naik ke yayasan, bukan tanda tangan dosen'],
                                ['Informatika: SK Dikti 1421/D/T/2003; Baik Sekali LAM-INFOKOM 026/2024; visi AI 2036', 'Panduan akademik FTI 2024/2025, cs.unissula.ac.id', 'Pilot wajar di FTI karena prodi sudah hidup dengan AI; bukan alasan FTI menandatangani data 13 fakultas'],
                                ['Elektro dan Industri Unggul; FK dan FH Unggul plus ASIC', 'Halaman akreditasi Unissula', 'FK = data spesifik; FH = keaslian argumentasi. Risiko tidak rata'],
                                ['Rektor Prof. Dr. Gunarto; Ketua Umum YBWSA Prof. Dr. Bambang Tri Bawono', 'Berita Unissula/YBWSA 2025–2026', 'Dua kursi yang disebut di lembar keputusan. Bukan kutipan AI yang dikarang'],
                                ['AppliedHE 2026: PTS 1 Jateng, 4 nasional, 29 ASEAN', 'unissula.ac.id, Desember 2025', 'Skandal data atau joki skripsi lebih mahal daripada menolak modul vendor'],
                            ],
                        ),
                        $this->paragraph('Contoh di Lesson ini — kebijakan kelas, klausul, peta 24 bulan — adalah kerangka rapat. Bukan SK yang sudah berlaku. Tokoh Bu Laila dan Dimas adalah alat ajar, seperti petugas Sari di Course kasus tiket. Jika Unissula menerbitkan kebijakan resmi, kebijakan itu yang diikuti.'),
                    ],
                ),
                $this->textLesson(
                    'Bukan kursus men-deploy agen',
                    'Kalau Unissula suatu hari menyambung SIA, runbook-nya bukan Lesson ini. Tetap harus disebut.',
                    14,
                    [
                        $this->heading('Bukan kursus men-deploy agen', 1),
                        $this->paragraph('EnterLMS adalah academy. Tutor menjelaskan. Ia tidak men-deploy agen di jaringan Kaligawe, tidak menyimpan kunci API Gema Kampus, dan tidak menyelesaikan Lesson untukmu.'),
                        $this->paragraph('Bayangkan Unissula kelak bilang ya pada modul Kenal Mahasiswa. Yang terjadi di operasional: akun layanan ke SIA, log akses NIM, on-call jam 02.00 jika tiket aneh, cara memutus sambungan, cadangan, dan tarik data jika kontrak berakhir. Itu runbook. Bukan silabus. Bukan pekerjaan dekan sendirian. Bukan tombol di Lesson.'),
                        $this->paragraph('Kursus ini tetap harus menyebut meja itu supaya rapat tidak pura-pura selesai karena “sudah belajar di academy”. UNESCO Recommendation on the Ethics of AI (2021) menuntut human oversight yang nyata: orang dengan wewenang menghentikan sistem. Kalau rapat Gema Kampus tidak bisa menunjuk orang itu, modul Kenal Mahasiswa ditolak — bukan ditunda dengan harapan IT “nanti atur”.'),
                        $this->bullets([
                            'Tidak ada lab, konsol, atau akses produksi karena lulus kuis.',
                            'Restricted Course operasi runtime tetap tersembunyi dari katalog ini.',
                            'Tidak ada data intern Unissula yang dikarang agar terasa konsultan.',
                        ]),
                    ],
                ),
                $this->textLesson(
                    'Empat kursi, satu meja: YBWSA, rektorat, dekan, dosen',
                    'Bu Laila boleh menulis silabus Informatika. Ia tidak boleh menandatangani SIA.',
                    18,
                    [
                        $this->heading('Empat kursi, satu meja', 1),
                        $this->paragraph('Unissula adalah PTS. UU 12/2012 membedakan otonomi akademik perguruan tinggi dari pendiri. YBWSA bukan tamu di rapat Gema Kampus. Modul Kenal Mahasiswa menyentuh SIA seluruh mahasiswa: itu data pengendali institusi, kontrak, dan reputasi wakaf. Dosen Informatika yang antusias bukan penandatangan.'),
                        $this->paragraph('Rektorat menulis apa yang berlaku di 13 fakultas. Dekan FTI boleh memulai pilot silabus Bu Laila. Dekan FK tidak boleh diabaikan: data praktikum klinis adalah data spesifik UU 27/2022. Dekan FH tidak boleh diabaikan: skripsi hukum adalah Permendikbudristek 39/2021, bukan tiket helpdesk.'),
                        $this->paragraph('Bu Laila memegang kelas Kecerdasan Buatan. Jika silabusnya diam, Dimas menebak. Pak Irfan diam di kelas Hukum Acara: Alya memakai model generatif untuk memo, lalu dihukum di minggu ujian. Mahasiswa dan orang tua tidak menandatangani vendor; mereka menanggung skandal.'),
                        $this->diagram(
                            'Wewenang Gema Kampus: yayasan dan rektorat dulu. Silabus menyusul. Jangan dibalik.',
                            [
                                ['id' => 'yayasan', 'label' => 'YBWSA', 'hint' => 'SIA/kontrak'],
                                ['id' => 'rektorat', 'label' => 'Rektorat', 'hint' => '13 fakultas'],
                                ['id' => 'fakultas', 'label' => 'Dekan', 'hint' => 'pilot'],
                                ['id' => 'kelas', 'label' => 'Dosen', 'hint' => 'silabus'],
                            ],
                            [
                                ['from' => 'yayasan', 'to' => 'rektorat'],
                                ['from' => 'rektorat', 'to' => 'fakultas'],
                                ['from' => 'fakultas', 'to' => 'kelas'],
                            ],
                            4,
                        ),
                        $this->table(
                            ['Keputusan Gema Kampus', 'Siapa yang sah menandatangani', 'Siapa yang tidak sah sendirian'],
                            [
                                ['Silabus: AI boleh di take-home Informatika dengan deklarasi', 'Dosen + dekan FTI, dalam pagar kebijakan universitas', 'Vendor yang “sudah include di paket”'],
                                ['Kebijakan ujian/skripsi 13 fakultas', 'Rektorat / senat', 'Satu dosen FTI'],
                                ['Add-on LMS Baca Tugas untuk seluruh SIA-adjacent data', 'Rektorat; YBWSA jika ambang kontrak/data universitas', 'Admin LMS yang klik setuju di portal vendor'],
                                ['Sambungan SIA Kenal Mahasiswa', 'YBWSA + rektorat sebagai pengendali data', 'Bu Laila, unit TIK, atau “tim AI” tanpa mandat'],
                            ],
                        ),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionAdopsi(): array
    {
        return [
            'title' => 'Adopsi, bukan belanja tools',
            'description' => 'Tiga modul Gema Kampus adalah tiga keputusan. Bukan satu invoice.',
            'lessons' => [
                $this->textLesson(
                    'Apa artinya adopsi AI di kampus',
                    'Informatika sudah mengajar AI. Adopsi adalah wewenang atas Dimas, Alya, dan data SIA.',
                    18,
                    [
                        $this->heading('Apa artinya adopsi AI di kampus', 1),
                        $this->paragraph('Di Unissula, adopsi bukan “mulai menyentuh AI”. Informatika sudah. Adopsi adalah rangkaian keputusan berulang: apa yang Dimas boleh lakukan di take-home; apa yang Alya boleh lakukan di memo hukum; data siapa yang boleh masuk Gema Kampus; siapa menandatangani; kapan berhenti. UNESCO Recommendation on the Ethics of AI (23 November 2021) menempatkan martabat, pengawasan manusia, dan pelindungan data sebagai syarat.'),
                        $this->paragraph('Panduan Penggunaan Generative AI pada Pembelajaran di Perguruan Tinggi (Kemendikbudristek, 2024, Kusumawardani dkk., s.id/PanduanGenAI) bilang jelas: teknologi ini tidak bisa dihindari dan tidak cukup dilarang; ia alat bantu, manusia tetap berpikir kritis. Panduan itu bukan undang-undang dan bukan SK Unissula. Ia bahan. Keputusan Gema Kampus tetap milik empat kursi.'),
                        $this->heading('Tiga pertanyaan yang kembali di Aula GKB'),
                        $this->bullets([
                            'Untuk siapa modul ini? Calon mahasiswa (Chat PMB), mahasiswa terdaftar (Baca Tugas), atau seluruh record SIA (Kenal Mahasiswa)?',
                            'Dengan data apa? Pertanyaan PMB, teks tugas Dimas, NIM+nilai, rekaman kuliah, ringkasan kasus FK?',
                            'Siapa bertanggung jawab jika salah? Bu Laila, dekan, rektorat, atau YBWSA?',
                        ]),
                        $this->paragraph('Kalau rapat hanya membahas harga paket “AI campus”, itu belanja. Kampus Unggul dengan cita world class islamic university diukur dari apakah keputusan itu bisa dijelaskan ke BAN-PT, ke orang tua Alya, dan ke amanah BudAI. “Tahun ini Unissula menolak Kenal Mahasiswa dan menunda Chat PMB” adalah adopsi. Diam di 12 fakultas bukan netral: Dimas sudah memakai model di HP.'),
                    ],
                ),
                $this->textLesson(
                    'Bukan beli chatbot lalu selesai',
                    'Chat PMB, Baca Tugas, dan Kenal Mahasiswa: tiga risiko, jangan satu tanda tangan.',
                    16,
                    [
                        $this->heading('Bukan beli chatbot lalu selesai', 1),
                        $this->paragraph('Gema Kampus menjual tiga benda sebagai satu. Chatbot menjawab percakapan PMB. Add-on Baca Tugas mengirim teks tugas ke server vendor. Sambungan Kenal Mahasiswa memberi model akses identitas dan status. Agen — jika kelak ditawarkan — menerima tujuan dan memakai alat dalam loop. Kebijakan Unissula memutuskan masing-masing.'),
                        $this->diagram(
                            'Satu invoice Gema Kampus. Tiga putusan Unissula. Jangan disatukan.',
                            [
                                ['id' => 'pmb', 'label' => 'Chat PMB', 'hint' => 'tunda'],
                                ['id' => 'tugas', 'label' => 'Baca Tugas', 'hint' => 'tolak dulu'],
                                ['id' => 'sia', 'label' => 'Kenal SIA', 'hint' => 'tolak'],
                            ],
                            [],
                            3,
                        ),
                        $this->table(
                            ['Modul', 'Yang sebenarnya terjadi', 'Putusan contoh rapat ini'],
                            [
                                ['Chat PMB', 'Percakapan calon mahasiswa; data pendaftar; sering server luar negeri', 'Tunda. FAQ PMB bisa teks statis dulu. Tidak ada dasar PDP tertulis'],
                                ['Baca Tugas', 'Isi tugas Dimas dan Alya keluar kampus; vendor bisa latih model jika klausul bolong', 'Tolak sampai klausul larangan training, lokasi, hapus, audit selesai'],
                                ['Kenal Mahasiswa', 'NIM, status, mungkin nilai, ke model. Ini proyek data, bukan fitur chat', 'Tolak di tahap 0–2. Tidak ada orang on-call dan tidak ada DPIA'],
                            ],
                        ),
                        $this->quote('Yang Unissula adopsi bukan merek Gema Kampus. Yang diadopsi adalah wewenang: modul mana, data siapa, sampai kapan, siapa yang boleh menghentikan.'),
                    ],
                ),
                $this->textLesson(
                    'Tren, proyek IT, dan kebijakan: tiga benda',
                    'Ranking AppliedHE, invoice Gema Kampus, dan SK senat punya kalender berbeda.',
                    18,
                    [
                        $this->heading('Tren, proyek IT, dan kebijakan', 1),
                        $this->paragraph('Tren sudah masuk Kaligawe. Dimas memakai model di HP. AppliedHE 2026 menempatkan Unissula PTS 1 Jawa Tengah. Tekanan “supaya tidak ketinggalan” akan dipakai sales Gema Kampus. Tren bukan dasar SK.'),
                        $this->paragraph('Proyek IT: lisensi, integrasi LMS/SIA, SLA, kunci API. Ada anggaran. Proyek yang dimulai sebelum kebijakan kelas dan peta data adalah belanja yang mencari justifikasi. Modul Baca Tugas adalah proyek IT yang menyamar sebagai fitur dosen.'),
                        $this->paragraph('Kebijakan: siapa boleh apa. UNESCO AI and education: Guidance for policy-makers (2021) memisahkan memakai AI untuk mengajar, mempersiapkan manusia hidup bersama AI, dan pemakaian etis. Panduan GenAI 2024 menambah: integritas, keamanan, kesetaraan/transparansi, dampak lingkungan. Tiga kalender itu tidak selesai dengan satu invoice.'),
                        $this->table(
                            ['Benda', 'Di rapat Gema Kampus kelihatan seperti', 'Pemilik yang wajar'],
                            [
                                ['Tren', '“Mahasiswa sudah pakai ChatGPT, kampus ketinggalan”', 'Dosen + senat: inventaris pemakaian, bukan pengadaan'],
                                ['Proyek IT', 'Demo Baca Tugas mengoreksi 40 makalah semalam', 'Unit TIK + rektorat, setelah klausul; YBWSA jika data universitas'],
                                ['Kebijakan', 'Boleh/deklarasi/dilarang di ujian dan skripsi', 'Senat lalu SK rektor; fakultas boleh lebih ketat'],
                            ],
                        ),
                        $this->paragraph('Undangan yang hanya berisi demo menghasilkan pembelian. Undangan yang memisahkan tiga kolom menghasilkan sikap. Kalau FTI menahan kontrak, jangan dituduh menghambat visi AI 2036. Mereka menahan data Alya.'),
                    ],
                ),
                $this->textLesson(
                    'Mengapa Unissula tidak copy-paste kebijakan luar negeri',
                    'NUS dan UPI boleh dibaca. BudAI, YBWSA, dan UU PDP tidak ikut tertyalin.',
                    16,
                    [
                        $this->heading('Mengapa Unissula tidak copy-paste', 1),
                        $this->paragraph('Banyak kampus punya pedoman GenAI. UPI menerbitkan Peraturan Rektor tentang penggunaan AI (2026) — itu contoh proses senat/rektor, bukan teks untuk disalin ke Kaligawe. UNESCO Guidance for generative AI (2023) memperingatkan: lembaga harus menvalidasi alat terhadap kelayakan etis dan pedagogis setempat.'),
                        $this->paragraph('Unissula adalah PTS Islam dengan BudAI. “Human agency” UNESCO di sini bertemu amanah dan kejujuran. Kebijakan yang hanya bilang “deklarasikan ChatGPT” tanpa Permendikbudristek 39/2021 akan terasa aturan IT di senat yang biasa bicara karya ilmiah.'),
                        $this->paragraph('Hukum yang mengikat: UU 12/2012, UU 27/2022 PDP, Permendikbudristek 39/2021, UU ITE beserta perubahan, PP 4/2014. Panduan GenAI Kemendikbudristek 2024 adalah beleidsregel: arah, contoh, etika — bukan pengganti SK. Surat Edaran Menkominfo 9/2023 tentang Etika KA lebih sempit lagi. Gema Kampus yang bilang “sudah sesuai UNESCO” belum tentu sesuai pengendali data Unissula.'),
                        $this->paragraph('Baca luar sebagai bahan. Tulis keputusan Unissula sebagai pemilik. Lesson deliverable mengisi lembar untuk rapat Gema Kampus, bukan menerjemahkan kebijakan orang lain.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionPetaJalan(): array
    {
        return [
            'title' => 'Peta jalan bertahap',
            'description' => 'Gema Kampus masuk di tahap berapa, bukan “sekarang juga”.',
            'lessons' => [
                $this->textLesson(
                    'Peta jalan empat tahap Unissula',
                    'Sikap, fondasi, pilot FTI+FH, kelembagaan. Modul SIA tidak meloncat ke depan.',
                    20,
                    [
                        $this->heading('Peta jalan empat tahap Unissula', 1),
                        $this->paragraph('Urutan jangan dilompati. Gema Kampus ingin tanda tangan minggu ini. Peta jalan menjawab: modul mana yang bahkan tidak boleh dibahas sebelum fondasi.'),
                        $this->diagram(
                            'Peta jalan Unissula. YBWSA mengunci sikap. Pilot di FTI+FH. Lembaga mengaudit. Bukan tools dulu.',
                            [
                                ['id' => 'sikap', 'label' => 'Sikap', 'hint' => 'wewenang'],
                                ['id' => 'fondasi', 'label' => 'Fondasi', 'hint' => 'inventaris'],
                                ['id' => 'pilot', 'label' => 'Pilot', 'hint' => 'FTI+FH'],
                                ['id' => 'lembaga', 'label' => 'Lembaga', 'hint' => 'SPMI'],
                            ],
                            [
                                ['from' => 'sikap', 'to' => 'fondasi'],
                                ['from' => 'fondasi', 'to' => 'pilot'],
                                ['from' => 'pilot', 'to' => 'lembaga'],
                            ],
                            4,
                        ),
                        $this->table(
                            ['Tahap', 'Gema Kampus', 'Deliverable'],
                            [
                                ['0–1 Sikap & fondasi', 'Tiga modul dipisah. Kenal Mahasiswa ditolak. Baca Tugas ditahan. Chat PMB ditunda', 'Lembar keputusan terisi pemilik'],
                                ['2 Pilot', 'Tidak ada add-on LMS universitas. Yang diuji: silabus Bu Laila + satu kelas Pak Irfan, 1–2 semester', 'Kebijakan kelas terisi + catatan apa yang gagal diperiksa'],
                                ['3 Perluasan', 'Klausul vendor wajib sebelum add-on apa pun. Pelatihan Acquire, bukan Create, ke perwakilan 13 fakultas', 'Klausul + bukti pelatihan'],
                                ['4 Kelembagaan', 'Audit tahunan lewat SPMI/senat: insiden integritas, insiden data, ketergantungan vendor', 'Peta 24 bulan yang hidup'],
                            ],
                        ),
                        $this->paragraph('UNESCO Guidance for policy-makers (2021) dan GenAI (2023) plus Panduan Kemendikbudristek 2024: tindakan segera (kelas, privasi), kebijakan jangka panjang, kapasitas manusia. YBWSA tidak perlu menyetujui merek chatbot di tahap 0. YBWSA perlu menyetujui bahwa data SIA tidak keluar, dan Unissula tidak mewajibkan satu merek ke 13 fakultas dalam tujuh hari.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 0–1: sikap, wewenang, inventaris',
                    'Empat puluh lima menit yang menghasilkan pemilik, plus tiga daftar yang FTI tidak bisa isi sendirian.',
                    18,
                    [
                        $this->heading('Tahap 0–1: sikap, wewenang, inventaris', 1),
                        $this->paragraph('Sikap tertulis untuk rapat Gema Kampus: AI adalah alat bantu manusia; tidak mengganti dosen; tidak menabrak BudAI dan Permendikbudristek 39/2021; tiga modul vendor diputus terpisah. Sikap tanpa pemilik adalah slogan.'),
                        $this->paragraph('Fondasi adalah tiga daftar, bukan sepuluh satuan tugas. Pertama, pemakaian: Dimas sudah memakai model di take-home; Alya di memo; berapa dosen diam. Kedua, sistem: SIA, LMS, email, rekaman, data pendidikan kedokteran. Ketiga, kontrak yang sudah jalan — termasuk “uji coba” laboratorium yang mengunggah tugas ke tools publik.'),
                        $this->numbered([
                            'Isi lima baris lembar pimpinan: prioritas tahun ini, data terlarang, penandatangan, kebijakan kelas minimum, apa yang tidak dimulai minggu pertama.',
                            'Pemilik: rektorat untuk 13 fakultas; dekan FTI untuk pilot Informatika; dekan FH untuk pilot non-teknik; YBWSA untuk SIA/kontrak universitas.',
                            'Moratorium: tidak ada agen hidup, tidak unggah arsip tugas, tidak wajib merek, tidak sambung SIA.',
                        ]),
                        $this->paragraph('Inventaris tidak butuh konsultan. Tiga pertanyaan ke 13 dekan plus unit TIK. Jika jawaban “tidak tahu di mana rekaman kuliah disimpan”, itu temuan tahap 1. Bukan aib yang disembunyikan sampai Gema Kampus kembali.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 2: pilot FTI dan satu fakultas non-teknik',
                    'Bu Laila di Informatika, Pak Irfan di FH. Bukan 13 fakultas, bukan FK dulu.',
                    20,
                    [
                        $this->heading('Tahap 2: pilot FTI dan satu fakultas non-teknik', 1),
                        $this->paragraph('FTI adalah tempat wajar untuk pilot teknis: visi AI 2036, mata kuliah Kecerdasan Buatan, mahasiswa seperti Dimas sudah dekat dengan tools. Jika hanya FTI yang punya kebijakan, 12 fakultas tetap diam. Diam di FH adalah risiko integritas. Diam di FK adalah risiko PDP — karena itu FK tidak dipilih sebagai fakultas non-teknik pertama.'),
                        $this->paragraph('Pilih FH, bukan FK, untuk pilot kedua. Karya tulis dan opini dekat dengan Permendikbudristek 39/2021. Data pasien tidak ikut. Pak Irfan menguji: memo dengan deklarasi proses vs ujian tertutup tanpa model. Satu atau dua semester, lalu evaluasi tertulis. Tanpa akhir, pilot adalah pengadaan yang malu disebut pengadaan.'),
                        $this->table(
                            ['Kelas contoh', 'Yang diuji', 'Yang tidak diuji'],
                            [
                                ['Bu Laila, Kecerdasan Buatan / take-home kode', 'Deklarasi proses, wawancara kode 10 menit, ujian lab tanpa model', 'Mengunggah repo kelas ke Gema Kampus; Baca Tugas universitas'],
                                ['Pak Irfan, memo hukum take-home', 'Sumber primer tetap dibaca manusia; AI bukan sitasi; asesmen lisan singkat', 'Chatbot konsultasi hukum; joki berbasis model'],
                                ['dr. Rina, FK — bukan pilot tahap 2', '—', 'Ringkasan kasus klinis ke model publik. Ditahan sampai kebijakan data spesifik'],
                            ],
                        ),
                        $this->paragraph('Gema Kampus akan bilang “pilot tanpa tools kami tidak valid”. Abaikan. Yang diuji tahap 2 adalah asesmen manusia, bukan merek. Kalau Bu Laila tidak sanggup wawancara 40 mahasiswa, itu temuan beban dosen — bukan alasan membeli Baca Tugas yang mengoreksi tanpa memahami.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 3–4: perluasan dan kelembagaan',
                    'SPMI, senat, LPPM, biaya. Bukan poster di lorong GKB.',
                    18,
                    [
                        $this->heading('Tahap 3–4: perluasan dan kelembagaan', 1),
                        $this->paragraph('Tahap 3 menulis minimum universitas dari bukti pilot, bukan dari brochure. Ujian tertutup tanpa AI kecuali disebut sebaliknya. Tugas boleh dengan deklarasi proses — sesuai arah Panduan GenAI 2024 agar dosen meminta mahasiswa menggambarkan pemakaian. Skripsi mengikuti Permendikbudristek 39/2021. Klausul vendor wajib sebelum add-on LMS apa pun.'),
                        $this->paragraph('Kapasitas tahap 3: UNESCO Teachers tingkat Acquire (mindset + ethics) untuk perwakilan tiap fakultas. Bukan Create. Bukan fine-tuning. Anggaran pelatihan adalah belanja adopsi; lisensi tanpa pelatihan adalah belanja tren.'),
                        $this->paragraph('Kelembagaan tahap 4 masuk SPMI — Unissula Unggul sudah punya penjaminan mutu. Kebijakan AI yang hanya hidup di unit TIK akan hilang saat akreditasi berikutnya. Senat memegang karya ilmiah. LPPM memegang publikasi: fabrikasi/falsifikasi dengan model generatif tetap 39/2021. Ethical impact assessment UNESCO 2021 tidak harus formulir Paris; ia orang yang setiap tahun bertanya siapa dirugikan sebelum perpanjang Gema Kampus.'),
                        $this->paragraph('Biaya yang jujur, tanpa mengarang anggaran Unissula: tiga opsi. (1) Lisensi 13 fakultas + add-on SIA: mahal, mengunci vendor, belum tentu mengubah asesmen. (2) Nol lisensi + kebijakan kelas + pelatihan Acquire: lebih murah, lebih dekat ke rumusan kasus. (3) Hybrid: tools berbayar hanya di lab FTI yang sudah mengajar AI, sisanya kebijakan. Ranking AppliedHE tidak menolong jika ijazah Alya diragukan.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionTigaMeja(): array
    {
        return [
            'title' => 'Tiga meja: pengajaran, tata kelola, operasional',
            'description' => 'Tugas Dimas menyentuh ketiga meja. SK yang mencampur ketiganya akan pecah.',
            'lessons' => [
                $this->textLesson(
                    'Tiga meja: pengajaran, tata kelola, operasional',
                    'Koreksi otomatis Baca Tugas bukan silabus. Kunci API bukan kebijakan senat.',
                    20,
                    [
                        $this->heading('Tiga meja', 1),
                        $this->paragraph('Dimas mengumpulkan tugas pemrograman. Tiga meja tersentuh sekaligus. Pengajaran: apakah tugas itu masih mengukur Dimas. Tata kelola: apakah teks tugas boleh ke server Gema Kampus. Operasional: siapa yang mematikan add-on jika bocor. Mencampur ketiganya dalam satu SK “wajib pakai Gema Kampus” menghasilkan pembelian dulu, sesal kemudian.'),
                        $this->diagram(
                            'Tugas Dimas bukan tiket helpdesk. Ia menyentuh silabus, kontrak, dan tombol mati.',
                            [
                                ['id' => 'ajar', 'label' => 'Pengajaran', 'hint' => 'asesmen'],
                                ['id' => 'kelola', 'label' => 'Tata kelola', 'hint' => 'PDP'],
                                ['id' => 'ops', 'label' => 'Operasional', 'hint' => 'henti'],
                            ],
                            [],
                            3,
                        ),
                        $this->table(
                            ['Meja', 'Pertanyaan Gema Kampus', 'Bukan pertanyaannya'],
                            [
                                ['Pengajaran', 'Kalau Baca Tugas mengoreksi, apa yang masih diuji dari Dimas?', 'Berapa persen hemat waktu dosen menurut slide'],
                                ['Tata kelola', 'Dasar UU 27/2022 apa? Siapa pengendali? Klausul training?', 'Apakah demo di FTI mengesankan'],
                                ['Operasional', 'Siapa on-call, di mana log, bagaimana tarik data?', 'Apakah Lesson academy ini sudah “mengajarkan deploy”'],
                            ],
                        ),
                        $this->paragraph('Pengajaran adalah senat dan dosen. Tata kelola adalah rektorat dan YBWSA. Operasional adalah orang yang bisa mematikan sistem — bukan tutor academy. Kalau rapat tidak bisa menunjuk tiga pemilik, jangan tanda tangan.'),
                    ],
                ),
                $this->textLesson(
                    'Meja pengajaran: silabus, tugas, ujian, skripsi',
                    'Redesain asesmen Bu Laila, Pak Irfan, dr. Rina. Diam di kelas adalah kebijakan.',
                    22,
                    [
                        $this->heading('Meja pengajaran', 1),
                        $this->paragraph('Panduan GenAI 2024: dosen meminta mahasiswa menggambarkan cara memakai GenAI, tantangan, dan apa yang dipelajari. UNESCO GenAI 2023: alat yang membantu draf belum tentu sah di ujian. Permendikbudristek 39/2021 mengukur perbuatan, bukan merek model: plagiat, fabrikasi, kepengarangan tidak sah.'),
                        $this->paragraph('Dimas sudah memakai model. Melarang total di take-home Informatika akan dilanggar diam-diam. Membiarkan tanpa redesain membuat nilai mengukur siapa yang berlangganan. Jalan ketiga: ubah tugas.'),
                        $this->diagram(
                            'Asesmen lama mengukur keluaran. Asesmen baru mengukur proses yang manusia masih bisa bela.',
                            [
                                ['id' => 'lama', 'label' => 'Keluaran', 'hint' => 'file akhir'],
                                ['id' => 'proses', 'label' => 'Proses', 'hint' => 'deklarasi'],
                                ['id' => 'lisan', 'label' => 'Lisan', 'hint' => '10 menit'],
                            ],
                            [
                                ['from' => 'lama', 'to' => 'proses'],
                                ['from' => 'proses', 'to' => 'lisan'],
                            ],
                            3,
                        ),
                        $this->table(
                            ['Kelas', 'Tugas lama (rapuh)', 'Tugas yang masih mengukur orangnya'],
                            [
                                ['Bu Laila, kode take-home', 'Kumpul repo. Dimas menempel keluaran model', 'Repo + paragraf deklarasi (alat, bagian mana yang diubah) + wawancara 10 menit pada fungsi acak'],
                                ['Pak Irfan, memo hukum', 'Esai 2.000 kata dari rumah', 'Dossier sumber yang harus dilampirkan; memo pendek; 8 menit lisan “pasal mana yang kamu tolak”'],
                                ['dr. Rina, ringkasan kasus', 'Unggah narasi kasus ke model publik', 'Kasus de-identifikasi di kertas/LMS intern; model publik dilarang; OSCE tanpa alat'],
                                ['Ujian tertutup / uji kompetensi', 'Take-home yang menyamar ujian', 'Tanpa model, kecuali soal secara tertulis menguji pemakaian alat'],
                                ['Skripsi/tesis', 'Bab yang “rapih” tanpa jejak bimbingan', 'Kontribusi harus bisa dijelaskan tanpa alat; data lapangan tidak ke model publik'],
                            ],
                        ),
                        $this->paragraph('Beban dosen: wawancara 10 menit × 40 mahasiswa = hampir 7 jam. Itu BKD, bukan hobi. Dekan yang mewajibkan redesain tanpa mengurangi jumlah tugas atau menambah asisten sedang menyembunyikan kerja. Baca Tugas yang “menghemat” 7 jam dengan mengirim teks ke vendor bukan solusi meja pengajaran — itu pindah ke meja tata kelola.'),
                        $this->paragraph('Alya di HP sewa, tanpa langganan berbayar. Dimas berlangganan. Kebijakan yang mengandaikan semua mahasiswa punya model berbayar gagal kesetaraan Panduan GenAI 2024 dan UNESCO. Tugas harus bisa diselesaikan tanpa alat berbayar. AI boleh; ketergantungan pada merek tidak.'),
                    ],
                ),
                $this->textLesson(
                    'Meja tata kelola: SK, anggaran, vendor, reputasi',
                    'Bagaimana senat mengunci kebijakan, dan mengapa invoice Gema Kampus menunggu di luar.',
                    18,
                    [
                        $this->heading('Meja tata kelola', 1),
                        $this->paragraph('Alur yang wajar, tanpa mengarang tata tertib intern: (1) inventaris dan draft dari rektorat/unit mutu, (2) senat membahas kebijakan akademik (ujian, skripsi, integritas), (3) SK rektor untuk minimum universitas, (4) dekan boleh lebih ketat, (5) kontrak tools ke pengadaan; YBWSA jika ambang data/anggaran universitas. Gema Kampus masuk di langkah 5, bukan langkah 1.'),
                        $this->paragraph('Reputasi Unggul dan AppliedHE rapuh terhadap dua skandal: kebocoran SIA, dan ijazah yang diragukan. Tata kelola yang menghitung “hemat koreksi” menukar aset jangka panjang dengan efisiensi semu. SPMI yang tidak memasukkan kebijakan AI akan ketahuan saat eviden akreditasi: silabus diam, mahasiswa sudah memakai model.'),
                        $this->numbered([
                            'Setiap pengadaan AI menulis tujuan, data, dasar PDP, lokasi, larangan training, pemilik, cara berhenti tanpa kehilangan arsip nilai.',
                            'Vendor yang menolak klausul tidak lolos, meski demo Baca Tugas mengesankan Bu Laila.',
                            'Anggaran Acquire ke 13 fakultas adalah belanja adopsi. Lisensi 13 fakultas tanpa pelatihan adalah belanja tren.',
                            'LPPM: naskah publikasi dengan GenAI tanpa kontribusi substansial tetap 39/2021. Jangan serahkan ke detektor saja.',
                        ]),
                    ],
                ),
                $this->textLesson(
                    'Meja operasional: apa yang academy ini tidak kerjakan',
                    'On-call, log, henti darurat. Jika tidak ada nama, modul Kenal Mahasiswa mati.',
                    14,
                    [
                        $this->heading('Meja operasional', 1),
                        $this->paragraph('Operasional adalah orang yang mematikan sambungan SIA pada jam 02.00. Bukan dekan yang baru baca slide. Bukan tutor. Human oversight UNESCO 2021: wewenang menghentikan, bukan slogan.'),
                        $this->paragraph('Untuk tiga modul Gema Kampus, tanya sebelum demo kedua: siapa akun layanan; di mana log; retensi berapa lama; siapa yang dihubungi mahasiswa jika chatbot PMB salah menjanjikan beasiswa; bagaimana tarik data jika kontrak berakhir; apakah cadangan nilai tetap di Unissula. Jika jawaban “nanti IT”, modul Kenal Mahasiswa ditolak di tahap 0. Unissula belum siap serving. Unissula siap demo.'),
                        $this->paragraph('Yang tidak dikerjakan kursus ini: men-deploy, menyambung SIA, menyimpan kunci API, red-team. Menyebut meja ini bukan inkonsistensi. Menyembunyikannya membuat rapat mengira lulus kuis = siap produksi.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionData(): array
    {
        return [
            'title' => 'Data, privasi, dan hukum',
            'description' => 'Dari pasal ke tiga modul Gema Kampus. Bukan perasaan “data itu penting”.',
            'lessons' => [
                $this->textLesson(
                    'Data mahasiswa dan dosen Unissula',
                    'Peta data sebelum klausul. Apa yang Dimas, Alya, dan rekaman kuliah bawa.',
                    18,
                    [
                        $this->heading('Data mahasiswa dan dosen Unissula', 1),
                        $this->paragraph('Unissula sebagai pengendali UU 27/2022 menentukan tujuan. Gema Kampus yang “hanya prosesor” tetap berisiko jika kontraknya bolong. Peta data tahap 1, versi jujur — sistem spesifik boleh berbeda; kategori ini yang harus disebut.'),
                        $this->table(
                            ['Kategori', 'Contoh di Kaligawe', 'Modul Gema yang mengincar'],
                            [
                                ['Identitas', 'Nama, NIM, email, foto KTM', 'Kenal Mahasiswa'],
                                ['Akademik', 'Nilai, status, KRS, teks tugas, draf skripsi', 'Baca Tugas; Kenal Mahasiswa'],
                                ['Rekaman', 'Kuliah, zoom, wajah dan suara Alya', 'Ringkasan otomatis yang “bukan paket” tapi tetap transfer'],
                                ['Kesehatan / klinis', 'Praktikum FK, data yang menempel kasus', 'Dilarang ke model publik. Bukan modul PMB'],
                                ['Calon mahasiswa', 'Chat PMB, data orang tua, dokumen', 'Chat PMB'],
                                ['Dosen', 'Rekaman mengajar, materi, NIK/NIDN di SIA', 'Baca Tugas jika silabus ikut terunggah'],
                            ],
                        ),
                        $this->paragraph('Pimpinan yang tidak bisa menyebut mana yang keluar, belum siap adopsi. Unit TIK yang bilang “semua sudah di cloud” belum menjawab dasar pemrosesan. Lesson berikutnya menaut pasal.'),
                    ],
                ),
                $this->textLesson(
                    'UU 27/2022 PDP untuk kampus',
                    'Pengendali, prosesor, data spesifik, transfer, hak subjek — pasal ke modul.',
                    22,
                    [
                        $this->heading('UU 27/2022 PDP untuk kampus', 1),
                        $this->paragraph('UU 27/2022 (LN 2022/196) berlaku untuk perguruan tinggi. Unissula memproses nama, NIM, nilai, foto, sering data spesifik (kesehatan, biometrik absensi). Dasar harus ada: persetujuan, kewajiban hukum, atau kepentingan sah yang diuji — bukan “Dimas klik setuju di chatbot Gema Kampus”.'),
                        $this->diagram(
                            'Data lahir di sivitas. Unissula pengendali. Vendor hanya prosesor jika tujuan tetap milik kampus.',
                            [
                                ['id' => 'sivitas', 'label' => 'Sivitas', 'hint' => 'subjek'],
                                ['id' => 'kampus', 'label' => 'Unissula', 'hint' => 'pengendali'],
                                ['id' => 'vendor', 'label' => 'Vendor', 'hint' => 'prosesor'],
                            ],
                            [
                                ['from' => 'sivitas', 'to' => 'kampus', 'label' => 'data'],
                                ['from' => 'kampus', 'to' => 'vendor', 'label' => 'dasar'],
                            ],
                            3,
                        ),
                        $this->table(
                            ['Konsep', 'Pasal yang dipakai di rapat (bahasa pimpinan)', 'Gema Kampus'],
                            [
                                ['Pengendali vs prosesor', 'Siapa menentukan tujuan? Jika vendor menentukan “meningkatkan model kami”, ia bukan semata prosesor', 'Tolak tujuan kabur'],
                                ['Dasar pemrosesan', 'Persetujuan harus bebas, spesifik, informatif. Wajib kuliah ≠ persetujuan bebas untuk latih model publik', 'Baca Tugas gagal jika dasar hanya TOS'],
                                ['Data spesifik', 'Kesehatan, biometrik: ambang lebih tinggi, sering butuh dasar lebih ketat', 'dr. Rina tidak mengunggah kasus'],
                                ['Transfer luar negeri', 'Boleh dengan jaminan; sebut negara; “kebijakan privasi vendor” bukan dasar Unissula', 'Tanya lokasi server Chat PMB'],
                                ['Hak subjek', 'Akses, perbaiki, hapus, tarik. Termasuk mahasiswa dropout dan calon yang chat PMB lalu tidak daftar', 'Klausul tanpa hapus = jangan masuk'],
                                ['Penilaian dampak', 'Pemrosesan berisiko tinggi (profil, data spesifik, transfer) mendorong DPIA', 'Kenal Mahasiswa tanpa DPIA = tolak'],
                            ],
                        ),
                        $this->paragraph('Tidak perlu menunggu “peraturan pelaksana lengkap” untuk menolak unggah arsip skripsi. Prinsip UU PDP cukup untuk berkata tidak. UU ITE tetap relevan untuk sistem elektronik; ia tidak menggantikan PDP. Keduanya masuk klausul.'),
                    ],
                ),
                $this->textLesson(
                    'Transfer ke vendor, rekaman kuliah, SIA',
                    'Tiga saluran yang lolos karena terasa teknis. Termasuk yang “bukan paket Gema”.',
                    18,
                    [
                        $this->heading('Transfer ke vendor, rekaman kuliah, SIA', 1),
                        $this->paragraph('Tiga saluran yang sering lolos dari rapat. Satu: Bu Laila mengunggah rekaman kuliah ke alat ringkasan — wajah Dimas, pertanyaan Alya yang mampir, kadang contoh. Itu transfer, meski bukan modul Gema. Dua: Baca Tugas menyedot teks. Tiga: Kenal Mahasiswa. Yang ketiga adalah proyek data.'),
                        $this->diagram(
                            'Rekaman dan SIA tidak “ikut” chatbot. Masing-masing transfer, masing-masing dasar.',
                            [
                                ['id' => 'rekam', 'label' => 'Rekaman', 'hint' => 'wajah+suara'],
                                ['id' => 'lms', 'label' => 'Tugas LMS', 'hint' => 'teks'],
                                ['id' => 'sia', 'label' => 'SIA', 'hint' => 'NIM+nilai'],
                            ],
                            [],
                            3,
                        ),
                        $this->bullets([
                            'Rekaman: default jangan ke tools publik. Internal: tujuan, retensi, akses. Mahasiswa harus tahu jika wajahnya diproses.',
                            'LMS: matikan add-on yang mengirim isi tugas sampai klausul selesai. Baca Tugas = transfer, bukan fitur dosen.',
                            'SIA: tidak disambung ke model pada tahap 0–2. Identitas plus nilai adalah kombinasi mahal.',
                            'Email: agen pembalas otomatis ke mahasiswa adalah pemrosesan, bukan efisiensi humas.',
                        ]),
                        $this->paragraph('Kalimat rapat yang bisa ditulis hari ini: “Semester ini Unissula tidak menyambungkan SIA ke chatbot, rekaman kuliah tidak ke model publik, dan add-on Baca Tugas tidak dihidupkan.” Tanpa tender. Tanpa Gema Kampus di ruangan.'),
                    ],
                ),
                $this->textLesson(
                    'Integritas akademik: Permendikbudristek 39/2021',
                    'Perbuatan Alya, bukan merek model. Senat, bukan detektor.',
                    20,
                    [
                        $this->heading('Integritas akademik: Permendikbudristek 39/2021', 1),
                        $this->paragraph('Permendikbudristek 39/2021: kejujuran, kepercayaan, keadilan, kehormatan, tanggung jawab, keteguhan hati. Pelanggaran: fabrikasi, falsifikasi, plagiat, kepengarangan tidak sah, konflik kepentingan, pengajuan jamak. Model generatif tidak disebut. Perbuatannya yang diukur.'),
                        $this->paragraph('Alya menyerahkan memo yang hampir seluruhnya keluaran model, tanpa kontribusi substansial: dekat kepengarangan tidak sah atau plagiat, tergantung fakta. Joki berbasis AI tetap pelanggaran. Senat memeriksa; pemimpin menjatuhkan sanksi administratif (nilai, status mahasiswa, hingga ijazah — sesuai pasal 16–17). UU 12/2012 menempatkan kejujuran sebagai asas. BudAI menempatkan akhlak sebagai strategi.'),
                        $this->paragraph('Detektor AI bukan hakim. Salah positif tinggi pada Bahasa Indonesia. Unissula yang menggantung skripsi pada skor GPTZero sedang membeli ketenangan palsu. Panduan GenAI 2024 mengarah ke proses: minta mahasiswa menggambarkan pemakaian. Wawancara Pak Irfan lebih jujur daripada skor.'),
                        $this->paragraph('LPPM: naskah jurnal dengan gambar atau data yang di-generate tanpa pernyataan adalah fabrikasi/falsifikasi. Kebijakan AI kelas yang tidak menaut 39/2021 akan terasa aturan IT. Yang menaut akan terasa urusan senat — tempatnya.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionKapasitas(): array
    {
        return [
            'title' => 'Kapasitas sivitas',
            'description' => 'Bu Laila tidak sama dengan Pak Irfan. Acquire dulu, Create bukan SK.',
            'lessons' => [
                $this->textLesson(
                    'UNESCO untuk dosen: AI Competency Framework for Teachers',
                    'Satu minggu Acquire Pak Irfan: redesain memo, bukan GPU.',
                    20,
                    [
                        $this->heading('UNESCO untuk dosen', 1),
                        $this->paragraph('UNESCO AI Competency Framework for Teachers (2024, Miao & Cukurova): 15 kompetensi, lima dimensi, tingkat Acquire / Deepen / Create. Ia rujukan agar Unissula menyusun yang sendiri, bukan menyalin Paris. Bu Laila sudah dekat Create di substansi informatika. Pak Irfan dan dr. Rina butuh Acquire. Melatih 13 fakultas sampai Create adalah FOMO berseragam.'),
                        $this->diagram(
                            'Acquire untuk semua. Deepen di pilot. Create milik yang memang mengajar komputasi — bukan syarat SK.',
                            [
                                ['id' => 'acq', 'label' => 'Acquire', 'hint' => 'Irfan/Rina'],
                                ['id' => 'deep', 'label' => 'Deepen', 'hint' => 'pilot'],
                                ['id' => 'create', 'label' => 'Create', 'hint' => 'FTI'],
                            ],
                            [
                                ['from' => 'acq', 'to' => 'deep'],
                                ['from' => 'deep', 'to' => 'create'],
                            ],
                            3,
                        ),
                        $this->table(
                            ['Dimensi', 'Acquire untuk Pak Irfan minggu ini', 'Bukan artinya'],
                            [
                                ['Human-centred', 'Memo tetap diuji dari argumentasi Alya, bukan kelancaran bahasa model', 'Menolak semua alat'],
                                ['Ethics of AI', 'Tahu teks memo ke vendor adalah PDP; joki AI = 39/2021', 'Hafal pasal tanpa ubah tugas'],
                                ['Fondasi', 'Chatbot ≠ add-on LMS ≠ sambungan SIA', 'Wajib coding'],
                                ['Pedagogi', 'Ganti esai 2.000 kata menjadi dossier + lisan 8 menit', 'Wajibkan Gema Kampus di kelas'],
                                ['Belajar profesional', 'Rapat sejawat FH: satu tugas diubah bersama', 'Sertifikat 3 jam vendor = Create'],
                            ],
                        ),
                        $this->paragraph('Target tahap 3: seluruh dosen Reach Acquire pada mindset dan ethics. Deepen untuk Bu Laila dan Pak Irfan sebagai pilot. Create tidak wajib universitas. Gema Kampus yang menawarkan “pelatihan 3 jam untuk semua dosen” bukan kerangka UNESCO; itu onboarding produk.'),
                    ],
                ),
                $this->textLesson(
                    'UNESCO untuk mahasiswa: AI Competency Framework for Students',
                    'Dimas yang pintar prompt belum tentu lulus ethics. Alya tanpa langganan tetap harus bisa lulus.',
                    18,
                    [
                        $this->heading('UNESCO untuk mahasiswa', 1),
                        $this->paragraph('UNESCO AI Competency Framework for Students (2024): 12 kompetensi, empat dimensi, Understand / Apply / Create. Mahasiswa sebagai warga yang menilai AI, bukan konsumen prompt. Di Unissula, ethics bertemu BudAI: tidak menipu, tidak menyerahkan amanah karya ke mesin.'),
                        $this->bullets([
                            'Understand (semua prodi): model bisa salah dan bias; keluaran bukan sumber primer. Alya yang menyalin “pasal” dari chatbot tanpa cek putusan sedang gagal Understand, bukan gagal mengetik.',
                            'Apply (sesuai silabus): deklarasi, sitasi, verifikasi manusia. Dimas yang memakai model untuk draf fungsi lalu menulis ulang dan menjelaskan di wawancara sedang Apply.',
                            'Create (terutama FTI): merancang pemakaian dengan pengawasan. Mata kuliah Kecerdasan Buatan sudah ada; Create bukan izin mengunggah data kelas ke vendor.',
                        ]),
                        $this->paragraph('Kesetaraan: Alya di HP sewa. Tugas yang hanya selesai dengan model berbayar menggagalkan Apply untuk yang tidak membayar. “Unissula AI-ready” di brosur PMB tanpa kebijakan kelas adalah klaim. Klaim itu ditagih saat ujian dan wisuda.'),
                    ],
                ),
                $this->textLesson(
                    'Dari FTI ke fakultas kesehatan dan hukum',
                    'Satu minimum universitas, tiga beban. FK tidak ikut pilot hanya karena “FTI yang ngerti”.',
                    18,
                    [
                        $this->heading('Dari FTI ke fakultas kesehatan dan hukum', 1),
                        $this->paragraph('Minimum universitas harus hidup di PGSD dan di Profesi Dokter. Detailnya tidak rata. FTI mendalami tools karena visinya AI. FH menahan keaslian argumentasi. FK menahan data spesifik — dan karena itu bukan pilot kedua.'),
                        $this->diagram(
                            'Satu pagar universitas. Tiga beban. Jangan rata, jangan diam.',
                            [
                                ['id' => 'fti', 'label' => 'FTI', 'hint' => 'tools+repo'],
                                ['id' => 'fh', 'label' => 'Hukum', 'hint' => 'karya'],
                                ['id' => 'fk', 'label' => 'Kedokteran', 'hint' => 'data spesifik'],
                            ],
                            [],
                            3,
                        ),
                        $this->table(
                            ['Klaster (fakta publik)', 'Beban khas', 'Kapasitas dulu'],
                            [
                                ['FTI — Informatika visi AI 2036; Elektro & Industri Unggul', 'Repo, ujian lab, mahasiswa seperti Dimas sudah memakai tools', 'Deepen pedagogi + larangan unggah repo ke vendor'],
                                ['FK / FKG / Keperawatan — banyak Unggul', 'Data klinis, OSCE, etika profesi', 'Acquire ethics + larangan keras model publik untuk kasus'],
                                ['Hukum — Unggul, ASIC', 'Memo, skripsi, joki', 'Acquire + asesmen proses/lisan (pilot tahap 2)'],
                                ['FKIP, FAI, Psikologi, dll.', 'Esai massal, data klien praktikum', 'Acquire + kebijakan praktikum; bukan Create'],
                            ],
                        ),
                        $this->paragraph('Pelatihan yang hanya diisi FTI menghasilkan dua Unissula. Diam di kesehatan adalah PDP. Diam di hukum adalah 39/2021. Anggaran tahap 3 yang tidak menyentuh dua klaster itu salah sasaran, meski lab robotik FTI menang kontes.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionDeliverable(): array
    {
        return [
            'title' => 'Contoh dan deliverable Unissula',
            'description' => 'Lembar terisi untuk rapat Gema Kampus. Bukan SK fiktif, bukan template kosong.',
            'lessons' => [
                $this->documentLesson(
                    'Lembar keputusan pimpinan Unissula',
                    'Hasil rapat contoh Aula GKB: tiga modul, lima keputusan, pemilik nama jabatan.',
                    16,
                    [
                        $this->heading('Lembar keputusan pimpinan Unissula', 1),
                        $this->paragraph('PDF ini sudah terisi sebagai hasil rapat contoh Gema Kampus — kerangka, bukan SK yang diundangkan. Ganti isinya di rapat sungguhan; jangan tanda tangan vendor dengan lembar kosong. Tiap baris punya pemilik: rektorat, dekan, atau YBWSA.'),
                        $this->paragraph('Cara memakai: cetak dua halaman. Isi kolom “diputuskan” hidup di rapat, bukan di taxi. Jika rapat berakhir “akan dikaji”, Unissula belum adopsi. Tiga modul harus tiga baris, bukan satu checklist merek.'),
                    ],
                    [
                        'filename' => 'lembar-keputusan-pimpinan-unissula.pdf',
                        'title' => 'Lembar Keputusan Pimpinan Unissula — hasil rapat contoh Gema Kampus',
                        'paragraphs' => [
                            'Sifat: kerangka rapat contoh. Bukan SK Rektor, bukan notulen resmi Unissula, bukan konsol.',
                            'Kasus: Universitas Islam Sultan Agung (https://unissula.ac.id/), PTS YBWSA, Unggul BAN-PT, BudAI, Khaira Ummah. Vendor fiktif Gema Kampus, tiga modul.',
                            '1. Prioritas tahun ini (DIPUTUSKAN): kebijakan kelas minimum di 13 fakultas + moratorium sambungan SIA. BUKAN lisensi chatbot untuk 13 fakultas. Pemilik: rektorat. Tenggat: 90 hari ke senat.',
                            '2. Data terlarang keluar tanpa dasar UU 27/2022: NIM+identitas SIA, nilai, rekaman kuliah, arsip tugas/skripsi, data praktikum FK. Chat PMB: data calon ditahan sampai dasar tertulis. Pemilik peta data: rektorat + unit TIK, laporan ke YBWSA.',
                            '3. Putusan tiga modul Gema Kampus: Chat PMB = TUNDA (FAQ statis dulu). Baca Tugas = TOLAK sampai klausul larangan training, lokasi, hapus, audit. Kenal Mahasiswa (SIA) = TOLAK tahap 0–2. Pemilik: YBWSA+rektorat untuk SIA; rektorat untuk LMS.',
                            '4. Kebijakan mahasiswa: ujian tertutup dilarang memakai model kecuali soal menguji alat; tugas boleh dengan deklarasi proses (Panduan GenAI 2024); skripsi mengikuti Permendikbudristek 39/2021. Diam = tidak diterima. Pemilik: senat lalu SK rektor; dekan boleh lebih ketat.',
                            '5. Tidak dimulai minggu pertama: agen hidup di Kaligawe, unggah arsip, wajib satu merek, sambung SIA, detektor sebagai hakim skripsi.',
                            'Pilot: FTI Informatika (Bu Laila, contoh) + FH (Pak Irfan, contoh). Bukan FK dulu. Evaluasi 1 semester. Pemilik: dekan terkait.',
                            'Rujukan: UNESCO Ethics AI 2021; UNESCO GenAI 2023; UNESCO competency 2024; Panduan GenAI Kemendikbudristek 2024; UU 27/2022; Permendikbudristek 39/2021; UU 12/2012.',
                            'EnterLMS: academy. Lulus kursus ini tidak men-deploy dan tidak membuka Course terbatas.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Kebijakan kelas: contoh FTI Informatika',
                    'Klausa silabus terisi untuk take-home Bu Laila dan ujian lab Dimas.',
                    18,
                    [
                        $this->heading('Kebijakan kelas: contoh FTI Informatika', 1),
                        $this->paragraph('Informatika FTI: SK Dikti 1421/D/T/2003, Baik Sekali LAM-INFOKOM 2024, visi publik kecerdasan buatan 2036, mata kuliah Kecerdasan Buatan di kurikulum. PDF adalah draf klausa yang Bu Laila bisa tempel di RPS — bukan peraturan fakultas yang sudah sah. FH boleh meniru struktur boleh/deklarasi/dilarang, bukan menyalin izin kode.'),
                        $this->paragraph('Isi yang harus ada di silabus hidup: apa yang diukur, apa yang boleh dengan jejak, apa yang membuat Dimas dipanggil wawancara, apa yang masuk 39/2021. Tanpa itu, visi AI 2036 hanya di banner prodi.'),
                    ],
                    [
                        'filename' => 'kebijakan-kelas-fti-informatika-unissula.pdf',
                        'title' => 'Draf klausa RPS — Informatika FTI Unissula (contoh Bu Laila)',
                        'paragraphs' => [
                            'Sifat: contoh deliverable. Bukan SK Dekan FTI. Tokoh Bu Laila dan Dimas adalah alat ajar.',
                            'Prodi: S1 Informatika, FTI Unissula. Konteks: take-home pemrograman dan mata kuliah Kecerdasan Buatan (kurikulum publik).',
                            'Prinsip: yang dinilai kompetensi mahasiswa. AI asisten. Sejalan BudAI dan Permendikbudristek 39/2021. Tugas harus bisa diselesaikan tanpa langganan berbayar (kesetaraan Panduan GenAI 2024).',
                            'Ujian lab / kuis tertutup / uji kompetensi: model dan agen dilarang, kecuali soal tertulis menguji pemakaian alat. HP di meja depan.',
                            'Take-home kode: BOLEH model untuk draf dan penjelasan. WAJIB paragraf deklarasi: nama alat, ringkasan prompt, file/fungsi mana yang diubah manusia, apa yang gagal dilakukan model. Dosen boleh wawancara 10 menit pada fungsi acak. Tidak bisa menjelaskan = tidak lulus bagian itu, bukan “nilai detektor”.',
                            'Proyek kelompok: satu deklarasi. Dilarang kumpul keluaran model tanpa dijalankan di lingkungan mahasiswa. API key, data kelas, rekaman — dilarang ke layanan asing. Default repo di infrastruktur yang disetujui FTI.',
                            'Skripsi: kontribusi penulis dijelaskan tanpa alat. Dataset pihak ketiga dan data SIA/identitas kampus dilarang sebagai bahan ke vendor. Pelanggaran = integritas akademik 39/2021, bukan “tidak mengikuti tren”.',
                            'Beban: wawancara 10 menit dihitung di RPS (mis. 40 mahasiswa ≈ 7 jam). Jika dekan tidak mengakui beban itu, kurangi jumlah take-home, jangan beli Baca Tugas sebagai ganti.',
                            'Evaluasi pilot: satu semester. Tulis berapa mahasiswa diwawancara, berapa yang gagal menjelaskan, apa yang pecah. Tanpa itu draf ini poster.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Klausul data vendor untuk LMS dan SIA',
                    'Bahasa klausul yang bisa dibawa ke pengadaan, diisi untuk Baca Tugas dan Kenal Mahasiswa.',
                    16,
                    [
                        $this->heading('Klausul data vendor untuk LMS dan SIA', 1),
                        $this->paragraph('Bukan merek. Bahasa yang ditolak Gema Kampus jika mereka menolak. Unissula pengendali; vendor prosesor kecuali mereka menentukan tujuan sendiri — maka seluruh hubungan ditinjau. PDF bisa dilampirkan ke pengadaan. YBWSA melihatnya jika ambang universitas terlampaui.'),
                        $this->paragraph('Cara memakai: untuk Baca Tugas, isi bidang data = teks tugas + identitas akun LMS, tujuan = umpan balik draf, bukan “meningkatkan model vendor”. Untuk Kenal Mahasiswa: jika tidak bisa mengisi DPIA dan orang on-call, jangan buka pasal ini — modulnya sudah ditolak di lembar keputusan.'),
                    ],
                    [
                        'filename' => 'klausul-data-vendor-lms-sia-unissula.pdf',
                        'title' => 'Klausul data — kerangka Unissula untuk add-on LMS/SIA',
                        'paragraphs' => [
                            'Sifat: daftar klausul. Bukan kontrak tertandatangan. Bukan nama vendor hidup.',
                            'Dasar: UU 27/2022; UU ITE beserta perubahan; UNESCO Ethics AI 2021 (privasi, oversight).',
                            'Para pihak: Unissula pengendali. Vendor prosesor. Jika vendor memakai data untuk model klien lain, ia bukan semata prosesor — hentikan.',
                            'Tujuan terbatas (contoh Baca Tugas): “memberi umpan balik draf tugas pada mata kuliah yang diopt-in fakultas.” Bukan “meningkatkan layanan AI”.',
                            'Data minimum: sebut bidang. Tolak paket yang menyedot seluruh SIA “supaya cerdas”. Kenal Mahasiswa yang butuh NIM+status+nilai = bukan data minimum.',
                            'Larangan training: data Unissula tidak untuk model publik atau model milik vendor bagi klien lain, termasuk fine-tune tersembunyi.',
                            'Lokasi: sebut negara dan subprosesor. Transfer keluar Indonesia hanya dengan jaminan tertulis yang disetujui pengendali.',
                            'Hak subjek: akses, koreksi, hapus, portabilitas dalam 14 hari kerja. Termasuk dropout dan calon PMB yang tidak daftar.',
                            'Insiden: kabari Unissula dalam 24 jam sejak diketahui. Unissula yang bicara ke sivitas.',
                            'Keluar: ekspor, hapus salinan, kunci dikembalikan, dalam 30 hari. Jika tidak bisa dijanjikan, jangan masuk.',
                            'Audit: hak pemeriksaan atau SOC/laporan independen tahunan. Tanpa audit, klausul pamflet.',
                            'Henti darurat: Unissula boleh memutus integrasi sepihak jika dugaan pelanggaran PDP, tanpa denda “sisa lisensi”.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Peta jalan 24 bulan Unissula',
                    'Kalender terisi dari rapat Gema Kampus: siapa, kapan, apa yang dihitung sukses.',
                    18,
                    [
                        $this->heading('Peta jalan 24 bulan Unissula', 1),
                        $this->paragraph('Empat tahap butuh tanggal. PDF memecah 24 bulan dari rapat contoh. Geser bulan jika YBWSA tidak bisa minggu ini. Jangan geser urutan: lisensi massal sebelum fondasi, atau Create sebelum Acquire, atau Kenal Mahasiswa sebelum DPIA.'),
                        $this->diagram(
                            'Dua puluh empat bulan dari rapat Gema Kampus: fondasi, pilot FTI+FH, klausul, audit SPMI.',
                            [
                                ['id' => 'y1a', 'label' => 'Bulan 1–6', 'hint' => 'fondasi'],
                                ['id' => 'y1b', 'label' => 'Bulan 7–12', 'hint' => 'pilot'],
                                ['id' => 'y2a', 'label' => 'Bulan 13–18', 'hint' => 'klausul'],
                                ['id' => 'y2b', 'label' => 'Bulan 19–24', 'hint' => 'SPMI'],
                            ],
                            [
                                ['from' => 'y1a', 'to' => 'y1b'],
                                ['from' => 'y1b', 'to' => 'y2a'],
                                ['from' => 'y2a', 'to' => 'y2b'],
                            ],
                            4,
                        ),
                        $this->paragraph('Indikator tahun ke-2 yang jujur tertulis di PDF. Jumlah lisensi dan jumlah prompt bukan indikator.'),
                    ],
                    [
                        'filename' => 'peta-jalan-24-bulan-unissula.pdf',
                        'title' => 'Peta jalan 24 bulan — terisi dari rapat contoh Gema Kampus',
                        'paragraphs' => [
                            'Sifat: kerangka kalender. Bukan keputusan yayasan yang diundangkan.',
                            'Bulan 1–3: rapat sikap rektorat+YBWSA; lembar keputusan terisi; moratorium SIA, arsip, wajib merek, agen hidup. Putusan Gema: PMB tunda, Baca Tugas tolak dulu, Kenal Mahasiswa tolak.',
                            'Bulan 2–6: inventaris 13 dekan (pemakaian, sistem, kontrak gelap). Kebijakan kelas minimum ke senat. Peta data SIA/LMS/rekaman.',
                            'Bulan 4–6: Acquire (UNESCO Teachers: mindset+ethics) 1–2 orang per fakultas, fasilitator bukan vendor. Pak Irfan mengubah satu memo; Bu Laila mengubah satu take-home.',
                            'Bulan 7–18: pilot Informatika + FH. Evaluasi tertulis tiap akhir semester: berapa wawancara, apa yang gagal diperiksa, beban BKD. Tidak menambah FK sebelum evaluasi pertama.',
                            'Bulan 13–18: klausul wajib pada perpanjangan LMS. Add-on yang menolak larangan training dimatikan. Chat PMB hanya jika dasar PDP calon mahasiswa tertulis.',
                            'Bulan 13–24: perluasan kebijakan universitas dari bukti pilot. Deepen hanya di fakultas siap. FK: larangan data klinis ke model publik tetap, meski belum pilot tools.',
                            'Bulan 19–24: audit SPMI/senat/LPPM: insiden 39/2021, insiden PDP, ketergantungan vendor, kesetaraan (tugas tanpa langganan). EIA UNESCO dalam bentuk yang senat sanggup jalan.',
                            'Sukses tahun ke-2: silabus tertulis di dua fakultas pilot; nol sambungan SIA ke model; dosen Acquire terlacak; YBWSA tahu data mana yang keluar. Bukan sukses: jumlah lisensi, prompt, atau brosur AI-ready.',
                            'Rujukan: UNESCO competency 2024; UNESCO GenAI 2023; Panduan GenAI 2024; UU 27/2022; Permendikbudristek 39/2021.',
                        ],
                    ],
                ),
                $this->textLesson(
                    'Risiko yang pimpinan harus sebutkan',
                    'Lima risiko Gema Kampus, masing-masing dengan wewenang. Menyembunyikan lebih mahal.',
                    18,
                    [
                        $this->heading('Risiko yang pimpinan harus sebutkan', 1),
                        $this->paragraph('Menyebut risiko bukan menolak AI. Menyembunyikan membuat YBWSA kaget di tahun kedua. UNESCO Ethics 2021: do no harm dan proporsionalitas di depan manfaat spekulatif. Gema Kampus akan bicara hemat koreksi. Pimpinan bicara lima hal ini sebelum tanda tangan.'),
                        $this->diagram(
                            'Empat risiko yang wajib disebut, plus identitas BudAI di narasi rapat — masing-masing punya pemilik.',
                            [
                                ['id' => 'integritas', 'label' => 'Integritas', 'hint' => '39/2021'],
                                ['id' => 'privasi', 'label' => 'Privasi', 'hint' => 'PDP'],
                                ['id' => 'vendor', 'label' => 'Vendor', 'hint' => 'gembok'],
                                ['id' => 'beban', 'label' => 'Beban', 'hint' => 'BKD'],
                            ],
                            [],
                            4,
                        ),
                        $this->table(
                            ['Risiko', 'Wujud di kasus Gema Kampus', 'Wewenang'],
                            [
                                ['Integritas', 'Alya kumpul memo model; detektor jadi hakim skripsi', 'Senat + dosen. Bukan unit TIK'],
                                ['Privasi', 'Baca Tugas dan Kenal Mahasiswa menarik teks/NIM ke luar negeri', 'Pengendali di rektorat; YBWSA pada kontrak universitas'],
                                ['Vendor lock', 'Nilai umpan balik hanya hidup di Gema; Unissula tidak bisa pindah', 'Pengadaan + pimpinan. Klausul keluar wajib'],
                                ['Beban dosen', 'Redesain tanpa BKD, atau “hemat” lewat vendor yang menyedot data', 'Dekan. Memo turun tanpa jam = kerja tersembunyi'],
                                ['Identitas / kesetaraan', 'Menipu vs BudAI; Alya tanpa langganan kalah dari Dimas', 'Pimpinan yang mengucapkan Khaira Ummah di publik'],
                            ],
                        ),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionPustaka(): array
    {
        return [
            'title' => 'Rapat, batas, dan pustaka',
            'description' => 'Agenda 45 menit untuk Gema Kampus. Sumber yang bisa dibuka di meja.',
            'lessons' => [
                $this->textLesson(
                    'Mulai dari rapat, bukan dari tools',
                    'Empat puluh lima menit, tiga modul, lima keputusan. Vendor di luar sampai pemilik tertulis.',
                    16,
                    [
                        $this->heading('Mulai dari rapat, bukan dari tools', 1),
                        $this->paragraph('Undangan rapat Gema Kampus yang hanya demo menghasilkan pembelian. Undangan yang berisi lembar keputusan terisi menghasilkan sikap. Bawa PDF Lesson ini, bukan brosur. Vendor menunggu di luar sampai empat agenda selesai.'),
                        $this->diagram(
                            'Empat puluh lima menit Aula GKB: prioritas, data, tiga modul, pemilik. Tools menunggu.',
                            [
                                ['id' => 'prioritas', 'label' => 'Prioritas', 'hint' => '10 mnt'],
                                ['id' => 'data', 'label' => 'Data', 'hint' => '15 mnt'],
                                ['id' => 'kelas', 'label' => 'Modul', 'hint' => '10 mnt'],
                                ['id' => 'pemilik', 'label' => 'Pemilik', 'hint' => '10 mnt'],
                            ],
                            [
                                ['from' => 'prioritas', 'to' => 'data'],
                                ['from' => 'data', 'to' => 'kelas'],
                                ['from' => 'kelas', 'to' => 'pemilik'],
                            ],
                            4,
                        ),
                        $this->heading('Agenda yang sudah diisi untuk rapat contoh'),
                        $this->bullets([
                            '10 menit — satu prioritas: kebijakan kelas minimum, bukan “AI campus”.',
                            '15 menit — data terlarang: SIA, rekaman, arsip, data FK. Chat PMB = data calon.',
                            '10 menit — tiga modul terpisah: PMB tunda, Baca Tugas tolak dulu, Kenal Mahasiswa tolak.',
                            '10 menit — pemilik: rektorat, dekan FTI, dekan FH, YBWSA. Tanpa nama jabatan, ulangi rapat.',
                        ]),
                        $this->paragraph('Kalau berakhir tanpa pemilik, Unissula menonton slide. Gema Kampus boleh masuk kembali setelah lima baris lembar terisi.'),
                    ],
                ),
                $this->textLesson(
                    'Apa yang tidak dimulai minggu pertama',
                    'Moratorium yang menahan FOMO tanpa menolak Informatika mengajar AI.',
                    14,
                    [
                        $this->heading('Apa yang tidak dimulai minggu pertama', 1),
                        $this->paragraph('Informatika tetap mengajar Kecerdasan Buatan minggu ini. Moratorium bukan anti-iptek. Ia menahan tiga modul Gema Kampus dan saluran gelap yang menyamar operasional.'),
                        $this->bullets([
                            'Agen hidup di jaringan Kaligawe “supaya modern”.',
                            'Unggah arsip tugas/skripsi “untuk melatih AI Unissula”.',
                            'Wajibkan 13 fakultas memakai satu merek dalam tujuh hari.',
                            'Sambungkan SIA ke model (Kenal Mahasiswa).',
                            'Hidupkan Baca Tugas sebelum klausul.',
                            'Buka konsol runtime di Lesson atau LMS.',
                            'Gantung skripsi pada skor detektor saja.',
                        ]),
                        $this->quote('Unissula sudah Unggul, sudah mengajar AI di FTI, sudah BudAI, tanpa chatbot PMB. Jangan tukar itu dengan invoice. Belajar di academy ini boleh hari ini. Mengoperasikan agen hidup tidak.'),
                    ],
                ),
                $this->textLesson(
                    'Pustaka: UNESCO, UU PDP, Dikti, Unissula',
                    'Sumber bernama untuk dibuka di Aula GKB. Bukan blog vendor.',
                    18,
                    [
                        $this->heading('Pustaka', 1),
                        $this->paragraph('Kerangka ini sekuat sumbernya. Klaim yang tidak tertaut ke daftar ini — jumlah mahasiswa intern, SK Rektor yang tidak terbit, ucapan pimpinan tentang AI yang tidak ada di berita — sengaja tidak dipakai. Tokoh Bu Laila, Pak Irfan, dr. Rina, Dimas, Alya, dan vendor Gema Kampus adalah alat ajar.'),
                        $this->heading('Unissula (fakta publik)'),
                        $this->bullets([
                            'Situs: https://unissula.ac.id/ — profil, berita, akreditasi.',
                            'BudAI, 18 Agustus 2005: https://unissula.ac.id/budaya-akademik-islami-budai/',
                            'YBWSA, 31 Juli 1950: https://ybw-sa.org/',
                            'Informatika: SK Dikti 1421/D/T/2003; LAM-INFOKOM 026/SK/LAM-INFOKOM/Ak/S/III/2024 Baik Sekali; visi AI 2036 pada Panduan Akademik FTI 2024/2025; kurikulum publik cs.unissula.ac.id (Kecerdasan Buatan, Data Mining, Pengolahan Citra, Pengenalan Pola).',
                            'FTI berdiri 23 Juli 1993. Elektro dan Industri Unggul.',
                            'Jabatan publik 2025–2026: Rektor Prof. Dr. Gunarto; Ketua Umum YBWSA Prof. Dr. Bambang Tri Bawono — sebagai kursi, bukan sebagai pendukung kursus ini.',
                            'AppliedHE 2026: unissula.ac.id, Desember 2025.',
                        ]),
                        $this->heading('Indonesia'),
                        $this->bullets([
                            'Kemendikbudristek. Panduan Penggunaan Generative Artificial Intelligence pada Pembelajaran di Perguruan Tinggi. 2024 (Kusumawardani, Wulandari, Pannen, dkk.). s.id/PanduanGenAI — pedoman, bukan UU.',
                            'UU 12/2012 Pendidikan Tinggi.',
                            'UU 27/2022 Pelindungan Data Pribadi.',
                            'UU ITE beserta perubahannya.',
                            'Permendikbudristek 39/2021 Integritas Akademik dalam Menghasilkan Karya Ilmiah.',
                            'PP 4/2014 Penyelenggaraan Pendidikan Tinggi.',
                            'SE Menkominfo 9/2023 Etika Kecerdasan Artifisial — arah, bukan pengganti SK kampus.',
                        ]),
                        $this->heading('Internasional'),
                        $this->bullets([
                            'UNESCO. Recommendation on the Ethics of Artificial Intelligence. 23 November 2021.',
                            'UNESCO. AI and education: Guidance for policy-makers. 2021 (Miao & Holmes).',
                            'UNESCO. Guidance for generative AI in education and research. 2023 (Miao & Holmes).',
                            'UNESCO. AI competency framework for teachers. 2024 (Miao & Cukurova).',
                            'UNESCO. AI competency framework for students. 2024.',
                            'OECD AI Principles; NIST AI RMF 1.0 — pelengkap, bukan pengganti hukum Indonesia.',
                        ]),
                        $this->paragraph('Setelah kuis: bawa lembar keputusan terisi ke rapat, bukan konsol ke jaringan kampus. Kalau Gema Kampus datang lagi, tiga modul tetap tiga keputusan.'),
                    ],
                ),
            ],
        ];
    }
}
