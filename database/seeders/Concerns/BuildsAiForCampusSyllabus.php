<?php

namespace Database\Seeders\Concerns;

/**
 * Syllabus for AI untuk Kampus.
 *
 * Running case: Universitas Islam Sultan Agung (Unissula).
 * Public facts only. Lessons stay teaching text + figures. Not a live lab.
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
            'description' => 'Satu kampus, empat kursi, satu kerangka. Bukan slide vendor.',
            'lessons' => [
                $this->textLesson(
                    'Untuk siapa kursus ini',
                    'Sivitas Unissula yang harus mengambil sikap di rapat, bukan di server.',
                    16,
                    [
                        $this->heading('Untuk siapa kursus ini', 1),
                        $this->paragraph('Kursus ini untuk sivitas yang harus mengambil sikap tentang AI di kampus: dekan, dosen, pemangku kepentingan, rektorat, dan pimpinan Yayasan Badan Wakaf Sultan Agung (YBWSA). Kamu tidak perlu menulis kode. Kamu perlu bahasa yang sama sebelum ada yang membeli tools.'),
                        $this->paragraph('Ini Open Course, gratis, dalam Bahasa Indonesia. Kamu boleh enroll sendiri. Kasus berjalannya satu: Universitas Islam Sultan Agung (Unissula) di Semarang, https://unissula.ac.id/. Setiap Lesson memakai Unissula. Kampus lain boleh membaca, tetapi kami tidak akan mengganti nama di tengah jalan menjadi “kampus X”.'),
                        $this->paragraph('Ini kerangka adopsi AI Unissula yang komprehensif, bukan briefing. Kamu akan berjalan dari konteks kampus, arti adopsi versus belanja, peta jalan empat tahap, tiga meja (pengajaran, tata kelola, operasional), data dan UU PDP, kapasitas sivitas menurut UNESCO, lalu deliverable yang bisa dibawa ke rapat.'),
                        $this->heading('Empat kursi, satu meja'),
                        $this->bullets([
                            'YBWSA — risiko reputasi, anggaran jangka panjang, dan amanah wakaf.',
                            'Rektorat — kebijakan universitas, tridharma, janji akreditasi Unggul.',
                            'Dekan / pimpinan fakultas — prioritas program, beban dosen, janji ke mahasiswa.',
                            'Dosen — ruang kelas, integritas tugas, waktu yang tersisa untuk mengajar.',
                        ]),
                        $this->diagram(
                            'Empat kursi Unissula duduk di satu kerangka adopsi, bukan di empat proyek terpisah.',
                            [
                                ['id' => 'ybwsa', 'label' => 'YBWSA', 'hint' => 'amanah'],
                                ['id' => 'rektor', 'label' => 'Rektorat', 'hint' => 'kebijakan'],
                                ['id' => 'dekan', 'label' => 'Dekan', 'hint' => 'fakultas'],
                                ['id' => 'dosen', 'label' => 'Dosen', 'hint' => 'kelas'],
                            ],
                            [],
                            4,
                        ),
                        $this->paragraph('Kamu boleh enroll sendiri. Menyelesaikan kursus ini tidak membuka Course terbatas dan tidak memberi akses produksi. Kalau yang kamu cari adalah tombol untuk menjalankan agen di Kampus Kaligawe, kamu di Course yang salah. Itu bukan Lesson.'),
                        $this->quote('Peta jalan yang dewasa kelihatan lambat di minggu pertama, dan murah di tahun kedua. UNESCO menyebutnya kapasitas manusia dulu, mesin kemudian.'),
                    ],
                    preview: true,
                ),
                $this->textLesson(
                    'Unissula sebagai kasus berjalan',
                    'Fakta publik yang boleh dipakai di rapat. Bukan organigram intern yang dikarang.',
                    18,
                    [
                        $this->heading('Unissula sebagai kasus berjalan', 1),
                        $this->paragraph('Universitas Islam Sultan Agung (UNISSULA) berdiri 20 Mei 1962 di bawah Yayasan Badan Wakaf Sultan Agung (YBWSA, berdiri 31 Juli 1950). Kampus utamanya di Jalan Raya Kaligawe Km 4, Semarang. Moto publiknya: Bismillah Membangun Generasi Khaira Ummah — generasi terbaik, merujuk QS Ali Imran 110.'),
                        $this->paragraph('Budaya Akademik Islami (BudAI) dideklarasikan 18 Agustus 2005 sebagai strategi pendidikan: penguatan ruhiyah (akidah, ibadah, akhlak) dan penguatan iptek berjalan bersama. Gerakan publiknya meliputi shalat berjamaah, busana Islami, thaharah, keteladanan, keramahan, dan kualitas hidup. Adopsi AI yang menabrak BudAI bukan inovasi. Itu inkonsistensi identitas.'),
                        $this->paragraph('Fakta kelembagaan yang dipakai kursus ini hanya yang terbit di kanal publik Unissula, YBWSA, BAN-PT/LAM, atau LLDIKTI VI. Kami tidak mengarang jumlah mahasiswa, SK Rektor intern, atau program AI yang tidak diumumkan.'),
                        $this->table(
                            ['Fakta publik', 'Sumber yang bisa dicek', 'Implikasi untuk AI'],
                            [
                                ['Akreditasi institusi Unggul BAN-PT; 13 fakultas, 45 program studi', 'unissula.ac.id, dashboard LLDIKTI VI', 'Janji mutu tidak boleh dikorbankan demi demo tools'],
                                ['PTS di bawah YBWSA, bukan PTN', 'ybw-sa.org, sejarah Unissula', 'Kontrak data dan anggaran naik ke yayasan, bukan hanya dekan'],
                                ['FTI berdiri 1993; Informatika SK Dikti 1421/D/T/2003, akreditasi Baik Sekali LAM-INFOKOM (2024)', 'Panduan akademik FTI, unissula.ac.id/akreditasi-program-studi', 'Pilot wajar di FTI, bukan satu-satunya fakultas yang kena kebijakan'],
                                ['Teknik Elektro dan Industri Unggul; FK dan FH Unggul plus ASIC', 'Halaman akreditasi Unissula', 'Fakultas kesehatan dan hukum membawa data dan integritas yang lebih berat'],
                                ['Rektor Prof. Dr. Gunarto; Ketua Umum YBWSA Prof. Dr. Bambang Tri Bawono', 'Berita Unissula / YBWSA 2025–2026', 'Dua meja pimpinan yang harus disebut di lembar keputusan'],
                                ['AppliedHE 2026: PTS 1 Jawa Tengah, 4 nasional, 29 ASEAN', 'unissula.ac.id, Desember 2025', 'Reputasi internasional rentan jika skandal data atau joki AI meledak'],
                            ],
                        ),
                        $this->paragraph('FTI publik juga menonjolkan robotik dan Magister Teknik Elektro yang menyebut kecerdasan artifisial sebagai salah satu kompetensi pengajar. Itu bukti kampus ini tidak anti-teknologi. Itu bukan izin untuk mengunggah arsip tugas ke vendor asing tanpa dasar.'),
                        $this->paragraph('Contoh di Lesson berikutnya — kebijakan kelas Informatika, klausul vendor, peta jalan 24 bulan — adalah kerangka yang bisa dibawa ke rapat. Bukan SK yang sudah berlaku. Jika Unissula kelak menerbitkan kebijakan resmi, kebijakan itu yang diikuti; kerangka ini hanya latihan keputusan.'),
                    ],
                ),
                $this->textLesson(
                    'Bukan kursus men-deploy agen',
                    'Adopsi bukan lab. Lesson bukan konsol. Unissula bukan studi kelayakan vendor.',
                    12,
                    [
                        $this->heading('Bukan kursus men-deploy agen', 1),
                        $this->paragraph('EnterLMS adalah academy. Tutor di sini menjelaskan. Ia tidak men-deploy agen di jaringan Unissula, tidak membuka desktop runtime, dan tidak menyelesaikan Lesson untukmu.'),
                        $this->paragraph('Pengenalan Agen AI mengajarkan apa itu agen. Kursus membangun model mengajarkan satu kasus teknis. Kursus ini mengajarkan apa yang pimpinan Unissula putuskan sebelum ada agen, chatbot, atau slide “AI campus” di ruang rapat YBWSA.'),
                        $this->bullets([
                            'Tidak ada lab di Lesson.',
                            'Tidak ada akses produksi karena lulus kuis.',
                            'Restricted Course tentang operasi runtime tetap tersembunyi dari katalog ini.',
                            'Tidak ada data intern Unissula yang dikarang agar terasa “konsultan”.',
                        ]),
                        $this->paragraph('Kalau FTI suatu hari mengoperasikan agen, itu di luar academy ini. Mandat kamu di sini: wewenang, data, pengajaran, dan kapasitas. UNESCO Guidance for generative AI in education and research (2023) memulai dari tindakan segera, kebijakan jangka panjang, dan kapasitas manusia — bukan dari konsol.'),
                    ],
                ),
                $this->textLesson(
                    'Empat kursi, satu meja: YBWSA, rektorat, dekan, dosen',
                    'PTS punya yayasan. Fakultas punya dekan. Kelas punya dosen. Jangan samakan wewenangnya.',
                    16,
                    [
                        $this->heading('Empat kursi, satu meja', 1),
                        $this->paragraph('Unissula adalah PTS. UU 12/2012 tentang Pendidikan Tinggi membedakan otonomi akademik perguruan tinggi dari pendiri/yayasan. YBWSA bukan “tamu” di rapat AI. YBWSA pemegang amanah wakaf, reputasi jangka panjang, dan — pada kontrak yang menyentuh data sivitas dalam skala universitas — tanda tangan yang tidak bisa diwakilkan dosen.'),
                        $this->paragraph('Rektorat menyusun kebijakan universitas: apa yang berlaku di 13 fakultas, apa yang cukup diatur fakultas. Dekan FTI boleh memulai pilot Informatika. Dekan Fakultas Kedokteran tidak boleh diabaikan hanya karena “FTI yang ngerti AI”: rekam medis mahasiswa dan pasien adalah data spesifik menurut UU 27/2022.'),
                        $this->paragraph('Dosen memegang kelas. Diam dosen tentang AI di tugas adalah kebijakan tersembunyi, dan biasanya kebijakan terburuk. Mahasiswa dan orang tua adalah pemangku kepentingan; mereka tidak menandatangani kontrak vendor, tetapi mereka yang menanggung skandal.'),
                        $this->diagram(
                            'Wewenang mengalir dari yayasan dan rektorat ke fakultas, lalu ke kelas. Jangan dibalik.',
                            [
                                ['id' => 'yayasan', 'label' => 'YBWSA', 'hint' => 'amanah'],
                                ['id' => 'rektorat', 'label' => 'Rektorat', 'hint' => 'kebijakan'],
                                ['id' => 'fakultas', 'label' => 'Fakultas', 'hint' => 'pilot'],
                                ['id' => 'kelas', 'label' => 'Kelas', 'hint' => 'silabus'],
                            ],
                            [
                                ['from' => 'yayasan', 'to' => 'rektorat'],
                                ['from' => 'rektorat', 'to' => 'fakultas'],
                                ['from' => 'fakultas', 'to' => 'kelas'],
                            ],
                            4,
                        ),
                        $this->paragraph('Rapat yang hanya mengundang “tim IT” akan menghasilkan pengadaan. Rapat yang mengundang empat kursi ini — meski 45 menit — akan menghasilkan sikap. Lesson lembar keputusan akan menuliskan pemilik tiap keputusan, bukan jabatan generik.'),
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
            'description' => 'Membeli chatbot bukan kebijakan kampus. Unissula mengadopsi wewenang.',
            'lessons' => [
                $this->textLesson(
                    'Apa artinya adopsi AI di kampus',
                    'Keputusan berulang: pengajaran, data, vendor, dan wewenang — di Unissula, bukan di slide.',
                    16,
                    [
                        $this->heading('Apa artinya adopsi AI di kampus', 1),
                        $this->paragraph('Adopsi AI di Unissula bukan satu proyek IT. Itu rangkaian keputusan: apa yang boleh dipakai di kelas Informatika dan di kelas Hukum; data siapa yang boleh masuk ke vendor; siapa yang menandatangani; dan kapan berhenti. UNESCO Recommendation on the Ethics of Artificial Intelligence (23 November 2021) menempatkan martabat manusia, pengawasan manusia, dan pelindungan data sebagai syarat, bukan hiasan.'),
                        $this->heading('Tiga pertanyaan yang selalu kembali'),
                        $this->bullets([
                            'Untuk siapa? Mahasiswa, dosen, administrasi, atau riset — masing-masing beda risiko di Unissula.',
                            'Dengan data apa? Tugas, transkrip, identitas, rekaman kuliah, rekam kesehatan di FK.',
                            'Siapa yang bertanggung jawab jika salah? Dosen, dekan, rektorat, atau YBWSA.',
                        ]),
                        $this->paragraph('Kalau rapat hanya membahas merek tools, itu belum adopsi. Itu belanja. Kampus dengan akreditasi Unggul dan cita-cita world class islamic university tidak diukur dari jumlah lisensi chatbot, melainkan dari apakah keputusan itu bisa dijelaskan ke BAN-PT, ke orang tua, dan ke amanah BudAI.'),
                        $this->paragraph('Adopsi yang jujur juga mencakup menolak. “Tahun ini Unissula tidak men-deploy agen, dan dosen FK tidak mengunggah data klinis ke model generatif publik” adalah keputusan adopsi. Diam bukan netral. Diam adalah izin tersembunyi.'),
                    ],
                ),
                $this->textLesson(
                    'Bukan beli chatbot lalu selesai',
                    'Chatbot, agen, dan kebijakan adalah tiga benda berbeda. Vendor sering menjualnya sebagai satu.',
                    14,
                    [
                        $this->heading('Bukan beli chatbot lalu selesai', 1),
                        $this->paragraph('Chatbot menjawab percakapan. Agen menerima tujuan, memakai alat, dan bekerja dalam loop. Kebijakan kampus memutuskan mana yang boleh masuk ke kelas Unissula dan ke data mahasiswa. Tiga benda ini sering dijual sebagai satu paket “AI kampus”.'),
                        $this->diagram(
                            'Chatbot menjawab. Agen bertindak. Kebijakan Unissula yang memutuskan mana yang boleh masuk.',
                            [
                                ['id' => 'chat', 'label' => 'Chatbot', 'hint' => 'jawab'],
                                ['id' => 'agen', 'label' => 'Agen', 'hint' => 'loop'],
                                ['id' => 'kebijakan', 'label' => 'Kebijakan', 'hint' => 'wewenang'],
                            ],
                            [
                                ['from' => 'chat', 'to' => 'kebijakan', 'label' => 'boleh?'],
                                ['from' => 'agen', 'to' => 'kebijakan', 'label' => 'boleh?'],
                            ],
                            3,
                        ),
                        $this->paragraph('Contoh Unissula yang sah sebagai diskusi rapat, bukan sebagai fakta intern: chatbot FAQ PMB di situs unissula.ac.id berbeda risikonya dari agen yang menulis balasan otomatis ke email mahasiswa, dan berbeda lagi dari model yang dilatih dengan arsip skripsi. Yang pertama mungkin layanan informasi. Yang kedua menyentuh data pribadi. Yang ketiga menyentuh hak cipta dan UU PDP sekaligus.'),
                        $this->quote('Yang Unissula adopsi bukan merek. Yang diadopsi adalah wewenang: siapa boleh memakai alat apa, pada data siapa, sampai kapan.'),
                    ],
                ),
                $this->textLesson(
                    'Tren, proyek IT, dan kebijakan: tiga benda',
                    'FOMO, pengadaan, dan SK adalah tiga kalender berbeda. Jangan disatukan di satu undangan.',
                    16,
                    [
                        $this->heading('Tren, proyek IT, dan kebijakan', 1),
                        $this->paragraph('Tren: berita ranking, demo vendor, mahasiswa yang sudah memakai model generatif di HP. Itu nyata. AppliedHE 2026 menempatkan Unissula sebagai PTS 1 Jawa Tengah. Tekanan “supaya tidak ketinggalan” akan masuk rapat. Tren bukan dasar SK.'),
                        $this->paragraph('Proyek IT: lisensi, integrasi SIA, LMS, helpdesk. Itu pengadaan. Ada anggaran, ada vendor, ada SLA. Proyek IT yang dimulai sebelum kebijakan kelas dan peta data adalah belanja yang mencari justifikasi.'),
                        $this->paragraph('Kebijakan: siapa boleh apa. UNESCO AI and education: Guidance for policy-makers (2021) memisahkan memakai AI untuk mengajar, mempersiapkan manusia hidup bersama AI, dan memastikan pemakaian etis. Tiga itu tidak selesai dengan satu invoice.'),
                        $this->table(
                            ['Benda', 'Pertanyaan Unissula', 'Pemilik yang wajar'],
                            [
                                ['Tren', 'Apa yang mahasiswa sudah lakukan diam-diam di kelas?', 'Dosen + senat akademik (inventaris)'],
                                ['Proyek IT', 'Alat apa yang masuk SIA atau LMS, dengan data apa?', 'Rektorat + unit TIK, sepengetahuan YBWSA jika kontrak besar'],
                                ['Kebijakan', 'Apa yang wajib, boleh dengan deklarasi, atau dilarang di ujian dan skripsi?', 'Rektorat / senat; fakultas boleh lebih ketat'],
                            ],
                        ),
                        $this->paragraph('Undangan rapat yang hanya berisi demo akan menghasilkan pembelian. Undangan yang memisahkan tiga kolom ini akan menghasilkan sikap. Kalau ketiga kolom itu dicampur, FTI akan dituduh “menghambat inovasi” ketika sebenarnya mereka menahan kontrak.'),
                    ],
                ),
                $this->textLesson(
                    'Mengapa Unissula tidak copy-paste kebijakan luar negeri',
                    'Oxford, NUS, atau UI boleh dibaca. YBWSA, BudAI, dan UU PDP tidak ikut tertyalin.',
                    14,
                    [
                        $this->heading('Mengapa Unissula tidak copy-paste', 1),
                        $this->paragraph('Banyak kampus dunia sudah punya pedoman generatif AI. UNESCO Guidance for generative AI in education and research (2023) justru memperingatkan: lembaga harus menvalidasi alat terhadap kelayakan etis dan pedagogis setempat. Menyalin silabus NUS tidak menyalin hukum Indonesia, tidak menyalin BudAI, dan tidak menyalin struktur PTS–yayasan.'),
                        $this->paragraph('Unissula adalah kampus Islam dengan strategi BudAI. “Human agency” dalam UNESCO Recommendation on the Ethics of AI (2021) di Unissula tidak netral-sekuler: ia bertemu amanah, kejujuran, dan khaira ummah. Kebijakan yang hanya bilang “deklarasikan pemakaian ChatGPT” tanpa menaut ke Permendikbudristek 39/2021 tentang Integritas Akademik akan terasa asing di senat.'),
                        $this->paragraph('Hukum yang mengikat Unissula adalah hukum Indonesia: UU 12/2012 Pendidikan Tinggi, UU 27/2022 PDP, Permendikbudristek 39/2021, dan — terbatas — Surat Edaran Menkominfo 9/2023 tentang Etika Kecerdasan Artifisial. SE bukan undang-undang; ia tidak menggantikan SK kampus, tetapi ia menandai arah negara.'),
                        $this->paragraph('Baca kebijakan luar sebagai bahan. Tulis keputusan Unissula sebagai pemilik. Lesson deliverable menyediakan draf untuk rapat, bukan terjemahan kebijakan orang lain.'),
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
            'description' => 'Sikap, fondasi, pilot, kelembagaan. Unissula tidak melompat ke agen hidup.',
            'lessons' => [
                $this->textLesson(
                    'Peta jalan empat tahap Unissula',
                    'Satu gambar untuk rapat YBWSA dan rektorat. Bukan sepuluh proyek paralel.',
                    18,
                    [
                        $this->heading('Peta jalan empat tahap Unissula', 1),
                        $this->paragraph('Kerangka ini memakai empat tahap. Nama tahap boleh diubah di rapat. Urutannya jangan. Melompat ke “AI campus” sebelum wewenang dan data tertulis adalah cara mahal untuk belajar di tahun kedua.'),
                        $this->diagram(
                            'Peta jalan Unissula: sikap dulu, fondasi, pilot, baru kelembagaan. Bukan tools dulu.',
                            [
                                ['id' => 'sikap', 'label' => 'Sikap', 'hint' => 'wewenang'],
                                ['id' => 'fondasi', 'label' => 'Fondasi', 'hint' => 'inventaris'],
                                ['id' => 'pilot', 'label' => 'Pilot', 'hint' => 'FTI+1'],
                                ['id' => 'lembaga', 'label' => 'Lembaga', 'hint' => 'audit'],
                            ],
                            [
                                ['from' => 'sikap', 'to' => 'fondasi'],
                                ['from' => 'fondasi', 'to' => 'pilot'],
                                ['from' => 'pilot', 'to' => 'lembaga'],
                            ],
                            4,
                        ),
                        $this->table(
                            ['Tahap', 'Pertanyaan yang harus selesai', 'Deliverable rapat'],
                            [
                                ['0–1 Sikap & fondasi', 'Siapa menandatangani? Data apa yang dilarang keluar? Apa kebijakan minimum kelas?', 'Lembar keputusan pimpinan Unissula'],
                                ['2 Pilot', 'FTI Informatika plus satu fakultas non-teknik: apa yang diuji 1–2 semester?', 'Kebijakan kelas contoh + catatan evaluasi'],
                                ['3 Perluasan', 'Apa yang berlaku universitas? Klausul vendor mana yang wajib?', 'Klausul data LMS/SIA + pelatihan dosen tahap Acquire UNESCO'],
                                ['4 Kelembagaan', 'Siapa mengaudit tahunan? Bagaimana kapasitas Create tidak hanya di FTI?', 'Peta jalan 24 bulan yang hidup, bukan poster'],
                            ],
                        ),
                        $this->paragraph('UNESCO Guidance for policy-makers (2021) dan Guidance for generative AI (2023) sejalan: tindakan segera (kebijakan kelas, privasi), kebijakan jangka panjang (tata kelola, pengadaan), dan kapasitas manusia (dosen dan mahasiswa). Peta jalan Unissula hanya menerjemahkan tiga itu ke kalender PTS di Kaligawe.'),
                        $this->paragraph('YBWSA tidak perlu menyetujui merek chatbot di tahap 0. YBWSA perlu menyetujui bahwa data mahasiswa tidak keluar kampus tanpa dasar, dan bahwa Unissula tidak akan mewajibkan satu merek ke 13 fakultas dalam tujuh hari.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 0–1: sikap, wewenang, inventaris',
                    'Rapat 45 menit yang menghasilkan pemilik, bukan notulen “akan dikaji”.',
                    16,
                    [
                        $this->heading('Tahap 0–1: sikap, wewenang, inventaris', 1),
                        $this->paragraph('Tahap 0 adalah sikap. Unissula menyatakan: AI akan dipakai sebagai alat bantu manusia, bukan pengganti dosen, dan tidak akan menabrak BudAI serta integritas akademik. Sikap tanpa pemilik adalah slogan.'),
                        $this->paragraph('Tahap 1 adalah fondasi. Inventaris tiga daftar, bukan sepuluh satuan tugas. Pertama: di mana mahasiswa sudah memakai model generatif (tugas, skripsi, ujian, organisasi). Kedua: sistem mana yang menyimpan data sivitas (SIA, LMS, email, rekaman kuliah, rekam FK). Ketiga: kontrak vendor yang sudah jalan — termasuk yang “hanya uji coba” di satu laboratorium FTI.'),
                        $this->numbered([
                            'Tulis lima keputusan di lembar pimpinan: prioritas tahun ini, data terlarang, penandatangan, kebijakan kelas minimum, apa yang tidak dimulai minggu pertama.',
                            'Tetapkan pemilik: rektorat untuk kebijakan universitas; dekan untuk pilot fakultas; dosen untuk silabus; YBWSA untuk kontrak yang melewati ambang yayasan.',
                            'Larangan sementara yang jujur: tidak mengunggah arsip tugas, tidak mewajibkan satu merek, tidak men-deploy agen hidup.',
                        ]),
                        $this->paragraph('Inventaris tidak butuh konsultan. Ia butuh 13 dekan yang menjawab tiga pertanyaan yang sama, plus unit TIK yang menyebut sistem. Kalau jawaban “tidak tahu”, itu temuan. Bukan aib yang disembunyikan sampai vendor datang.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 2: pilot FTI dan satu fakultas non-teknik',
                    'Informatika boleh mulai. Hukum atau kedokteran harus ikut, supaya kebijakan tidak hanya milik insinyur.',
                    16,
                    [
                        $this->heading('Tahap 2: pilot FTI dan satu fakultas non-teknik', 1),
                        $this->paragraph('FTI Unissula — Elektro Unggul, Industri Unggul, Informatika Baik Sekali — adalah tempat wajar untuk pilot teknis. Dosen Informatika sudah dekat dengan tools. Jika hanya FTI yang punya kebijakan kelas, 12 fakultas lain tetap diam. Diam di FK dan FH lebih berbahaya daripada gagap di FTI.'),
                        $this->paragraph('Pilih satu fakultas non-teknik. Dua kandidat yang masuk akal secara publik: Fakultas Hukum (karya ilmiah, opini, dan Permendikbudristek 39/2021 sangat dekat) atau Fakultas Kedokteran (data kesehatan adalah data spesifik UU PDP). Jangan pilih keduanya di semester yang sama. Satu non-teknik cukup untuk menguji apakah kebijakan “deklarasi vs larangan ujian” hidup di luar laboratorium komputer.'),
                        $this->table(
                            ['Pilot', 'Yang diuji', 'Yang tidak diuji'],
                            [
                                ['FTI Informatika', 'Silabus: AI boleh untuk draf kode dengan deklarasi; ujian lab tanpa model', 'Melatih model dari repositori tugas mahasiswa'],
                                ['FH atau FK', 'Sitasi dan keaslian karya; data pasien/berkas tidak masuk tools publik', 'Chatbot layanan klinik atau konsultasi hukum otomatis'],
                            ],
                        ),
                        $this->paragraph('Pilot punya akhir. Satu atau dua semester, lalu evaluasi tertulis: apa yang mahasiswa lakukan, apa yang dosen sanggup periksa, apa yang pecah. Tanpa akhir, pilot adalah pengadaan yang malu disebut pengadaan.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 3–4: perluasan dan kelembagaan',
                    'Dari dua fakultas ke 13, lalu ke audit tahunan. Bukan poster di lorong GKB.',
                    16,
                    [
                        $this->heading('Tahap 3–4: perluasan dan kelembagaan', 1),
                        $this->paragraph('Tahap 3 menuliskan apa yang berlaku universitas. Kebijakan kelas minimum: ujian tertutup tanpa AI kecuali disebut sebaliknya; tugas boleh memakai AI jika prosesnya dinyatakan; skripsi mengikuti Permendikbudristek 39/2021 plus aturan fakultas. Klausul vendor wajib: dasar pemrosesan, lokasi data, larangan melatih model publik dari data Unissula, hak hapus, audit.'),
                        $this->paragraph('Kapasitas dosen di tahap 3 memakai UNESCO AI Competency Framework for Teachers (2024) pada tingkat Acquire: human-centred mindset dan ethics of AI, bukan fine-tuning. Seluruh dosen tidak perlu jadi informatika. Seluruh dosen perlu tahu kapan AI merusak asesmen.'),
                        $this->paragraph('Tahap 4 adalah kelembagaan. Ada pemilik yang membaca laporan tahunan: berapa insiden integritas, berapa kontrak vendor, apakah data terlarang lolos. UNESCO Recommendation on the Ethics of AI (2021) menyebut ethical impact assessment. Unissula tidak wajib meniru formulir Paris. Unissula wajib punya orang yang bertanya “siapa dirugikan” sebelum perpanjang kontrak.'),
                        $this->paragraph('Kelembagaan juga berarti berhenti. Jika pilot FH menunjukkan plagiarisme naik dan dosen tidak sanggup memeriksa, kebijakan bisa diperketat, bukan dilonggarkan karena vendor sudah dibayar. Ranking AppliedHE tidak menolong jika ijazah dipertanyakan.'),
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
            'description' => 'Dosen, pimpinan, dan operator tidak duduk di kursi yang sama.',
            'lessons' => [
                $this->textLesson(
                    'Tiga meja: pengajaran, tata kelola, operasional',
                    'Mencampur tiga meja ini biasanya berarti membeli dulu, menyesal kemudian.',
                    18,
                    [
                        $this->heading('Tiga meja', 1),
                        $this->paragraph('Unissula yang mencampur pengajaran, tata kelola, dan operasional dalam satu SK biasanya membeli dulu, menyesal kemudian. Dosen diminta “pakai AI” tanpa desain ulang asesmen. Yayasan diminta tanda tangan tanpa peta data. Operator diminta “jalankan saja” tanpa wewenang menghentikan.'),
                        $this->diagram(
                            'Tiga meja Unissula. Kebijakan kelas bukan tiket helpdesk. Kontrak bukan silabus.',
                            [
                                ['id' => 'ajar', 'label' => 'Pengajaran', 'hint' => 'silabus'],
                                ['id' => 'kelola', 'label' => 'Tata kelola', 'hint' => 'wewenang'],
                                ['id' => 'ops', 'label' => 'Operasional', 'hint' => 'bukan Lesson'],
                            ],
                            [],
                            3,
                        ),
                        $this->paragraph('Pengajaran: bolehkah mahasiswa memakai AI untuk tugas, ujian, skripsi? Apa yang harus dinyatakan di silabus Informatika, dan apa yang dilarang di ujian OSCE FK? Ini kebijakan akademik, bukan tiket helpdesk.'),
                        $this->paragraph('Tata kelola: anggaran, kontrak vendor, privasi, reputasi Unggul, amanah YBWSA. Siapa yang boleh menandatangani data mahasiswa ke pihak ketiga. Siapa yang boleh menolak vendor yang tidak mau klausul hapus data.'),
                        $this->paragraph('Operasional: menjalankan sistem — log, akses, henti darurat. Bukan pekerjaan kursus ini dan bukan tombol di Lesson. Kalau Unissula kelak mengoperasikan agen, itu di luar academy ini. Meja ketiga tetap harus disebut, supaya tidak ada yang berpura-pura Lesson ini adalah runbook.'),
                    ],
                ),
                $this->textLesson(
                    'Meja pengajaran: silabus, tugas, ujian, skripsi',
                    'Diam di kelas adalah kebijakan. Biasanya kebijakan terburuk.',
                    16,
                    [
                        $this->heading('Meja pengajaran', 1),
                        $this->paragraph('UNESCO Guidance for generative AI in education and research (2023) menekankan validasi pedagogis: alat yang membantu draf belum tentu sah di ujian. Permendikbudristek 39/2021 tentang Integritas Akademik dalam Menghasilkan Karya Ilmiah menyebut kejujuran, plagiat, fabrikasi, falsifikasi, dan kepengarangan tidak sah. Model generatif yang menulis skripsi tanpa kontribusi mahasiswa adalah masalah integritas, bukan masalah “ketinggalan zaman”.'),
                        $this->table(
                            ['Konteks Unissula', 'Sikap minimum yang bisa ditulis dosen', 'Alasan'],
                            [
                                ['Ujian tertutup / OSCE / uji kompetensi', 'AI dilarang, kecuali soal secara eksplisit menguji pemakaian alat', 'Yang diukur adalah kemampuan orangnya'],
                                ['Tugas take-home Informatika', 'Boleh sebagai asisten draf, wajib deklarasi proses, dosen boleh minta wawancara kode', 'Proses terlihat; hasil saja tidak'],
                                ['Esai FH / makalah FKIP', 'Sumber primer tetap dibaca manusia; AI bukan sitasi; parafrase tanpa paham = plagiat', 'Permendikbudristek 39/2021'],
                                ['Skripsi / tesis / disertasi', 'Kontribusi penulis harus bisa dijelaskan tanpa alat; data lapangan tidak diunggah ke model publik', 'Hak cipta, PDP, integritas'],
                            ],
                        ),
                        $this->paragraph('Dosen Unissula tidak wajib seragam di semua mata kuliah. Dosen wajib tertulis. Silabus yang diam memaksa mahasiswa menebak, dan memaksa dosen marah di minggu ujian. Kebijakan fakultas boleh lebih ketat dari minimum universitas; tidak boleh lebih longgar pada ujian dan karya ilmiah jika senat sudah mengunci.'),
                    ],
                ),
                $this->textLesson(
                    'Meja tata kelola: SK, anggaran, vendor, reputasi',
                    'YBWSA dan rektorat menandatangani yang tidak bisa ditandatangani dosen.',
                    16,
                    [
                        $this->heading('Meja tata kelola', 1),
                        $this->paragraph('Tata kelola Unissula punya dua lapis yang tidak dimiliki PTN biasa: rektorat dan YBWSA. Kontrak lisensi kecil mungkin cukup di unit TIK. Kontrak yang menyentuh SIA, data mahasiswa seluruh universitas, atau anggaran di atas ambang yayasan naik ke pimpinan yang disebut di lembar keputusan.'),
                        $this->paragraph('Reputasi Unggul BAN-PT dan ranking AppliedHE adalah aset. Aset itu rapuh terhadap dua skandal: kebocoran data sivitas, dan ijazah yang diragukan karena joki AI. Tata kelola yang hanya menghitung hemat dosen (karena “AI yang koreksi”) sedang menukar aset jangka panjang dengan efisiensi semu.'),
                        $this->numbered([
                            'Setiap pengadaan AI menulis: tujuan, data yang disentuh, dasar hukum PDP, lokasi pemrosesan, klausul larangan training, pemilik internal.',
                            'Vendor yang menolak klausul data tidak lolos, meski demonya bagus di FTI.',
                            'Anggaran pelatihan dosen (UNESCO Acquire) adalah belanja adopsi. Anggaran lisensi tanpa pelatihan adalah belanja tren.',
                            'Keputusan “berhenti berlangganan” harus bisa diambil tanpa kehilangan arsip nilai. Ketergantungan vendor adalah risiko yang disebut di Lesson risiko.',
                        ]),
                        $this->paragraph('SK yang mencampur “wajib pakai merek X di semua kelas” dengan “integritas akademik” sedang mencampur meja. Pisahkan: SK kebijakan pemakaian di pengajaran; kontrak di tata kelola; runbook di operasional.'),
                    ],
                ),
                $this->textLesson(
                    'Meja operasional: apa yang academy ini tidak kerjakan',
                    'Log, akses, henti darurat. Bukan tombol di Lesson, dan bukan pekerjaan dekan sendirian.',
                    12,
                    [
                        $this->heading('Meja operasional', 1),
                        $this->paragraph('Operasional adalah orang yang bisa mematikan sistem pada jam 02.00 ketika tiket SIA aneh. Bukan dekan yang baru baca slide. Bukan tutor academy. UNESCO Recommendation on the Ethics of AI (2021) menuntut human oversight — pengawasan manusia yang nyata, dengan wewenang menghentikan.'),
                        $this->paragraph('Yang tidak dikerjakan kursus ini, dan tidak boleh dikira sudah “selesai karena lulus kuis”: men-deploy agen di jaringan Kampus Kaligawe; menyambungkan model ke SIA; menyimpan kunci API di LMS; menjalankan lab red-team. Itu meja lain, Course lain, atau bukan Course sama sekali.'),
                        $this->paragraph('Yang harus disebut di rapat supaya meja ini tidak hilang: siapa on-call jika vendor bocor; di mana log akses data mahasiswa; berapa lama cadangan; bagaimana tarik data jika kontrak berakhir. Jika jawaban “nanti ditanya IT”, Unissula belum siap tahap 3. Unissula baru siap demo.'),
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
            'description' => 'UU 27/2022 PDP dan Permendikbudristek 39/2021, diterjemahkan ke Kaligawe.',
            'lessons' => [
                $this->textLesson(
                    'Data mahasiswa dan dosen Unissula',
                    'Siapa yang punya, siapa yang boleh meminjamkan ke vendor. Bukan keputusan fitur.',
                    16,
                    [
                        $this->heading('Data mahasiswa dan dosen Unissula', 1),
                        $this->paragraph('Setiap “AI untuk kampus” yang menyentuh tugas, nilai, identitas, rekaman kuliah, atau rekam FK adalah keputusan data. Bukan keputusan fitur. Unissula sebagai pengendali data — dalam bahasa UU 27/2022 — menentukan tujuan dan kendali pemrosesan. Vendor yang “hanya memproses” tetap membawa risiko jika kontraknya bolong.'),
                        $this->bullets([
                            'Tugas dan ujian — integritas akademik dan hak cipta mahasiswa.',
                            'Identitas, NIM, email — tidak masuk ke vendor asing tanpa dasar pemrosesan.',
                            'Rekaman dosen — bukan bahan latihan model tanpa persetujuan dan dasar.',
                            'Data kesehatan di lingkungan FK — data spesifik; ambang jauh lebih tinggi.',
                            'Log SIA dan LMS — sering berisi perilaku, nilai, dan identitas sekaligus.',
                        ]),
                        $this->paragraph('Pimpinan YBWSA dan rektorat yang tidak bisa menyebut data mana yang keluar kampus, belum siap adopsi. Mereka baru siap demo. Lesson berikutnya menautkan daftar ini ke pasal-pasal yang bisa disebut di rapat, bukan ke perasaan “data itu penting”.'),
                    ],
                ),
                $this->textLesson(
                    'UU 27/2022 PDP untuk kampus',
                    'Pengendali, prosesor, data spesifik, transfer, hak subjek — dalam bahasa rapat Unissula.',
                    18,
                    [
                        $this->heading('UU 27/2022 PDP untuk kampus', 1),
                        $this->paragraph('Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (LN 2022/196) berlaku untuk pemrosesan data pribadi, termasuk oleh perguruan tinggi. Unissula memproses nama, NIM, alamat, foto, nilai, dan sering data spesifik (kesehatan, biometrik absensi). Dasar pemrosesan harus ada: persetujuan, kewajiban hukum, atau kepentingan sah yang diuji — bukan “mahasiswa sudah klik setuju di chatbot vendor”.'),
                        $this->diagram(
                            'Data sivitas lahir di Unissula. Keluar ke vendor hanya dengan dasar, tujuan, dan hak tarik.',
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
                            ['Konsep UU PDP', 'Arti di Unissula', 'Yang ditanyakan di rapat'],
                            [
                                ['Pengendali', 'Unissula menentukan tujuan pemrosesan', 'Siapa pejabat pengendali untuk SIA dan LMS?'],
                                ['Prosesor', 'Vendor AI yang memproses atas instruksi kampus', 'Apakah kontrak melarang pakai data untuk latih model publik?'],
                                ['Data spesifik', 'Kesehatan FK, biometrik, data anak jika ada program sekolah lab', 'Mengapa tools publik bukan tempatnya?'],
                                ['Transfer', 'Server vendor di luar negeri', 'Di negara mana, dengan jaminan apa?'],
                                ['Hak subjek', 'Akses, perbaiki, hapus, tarik persetujuan', 'Bagaimana mahasiswa menarik data dari vendor jika dropout?'],
                            ],
                        ),
                        $this->paragraph('Pemrosesan berisiko tinggi — profil otomatis, data spesifik, transfer — mendorong penilaian dampak pelindungan data. Unissula tidak perlu menunggu “peraturan pelaksana lengkap” untuk menahan unggahan arsip skripsi ke model publik. Prinsip UU PDP sudah cukup untuk berkata tidak.'),
                        $this->paragraph('UU ITE (11/2008 beserta perubahannya) tetap relevan untuk sistem elektronik. Ia tidak menggantikan PDP. Dua-duanya disebut di klausul vendor, bukan salah satu.'),
                    ],
                ),
                $this->textLesson(
                    'Transfer ke vendor, rekaman kuliah, SIA',
                    'Tiga saluran yang paling sering “lolos” karena terasa operasional, bukan kebijakan.',
                    16,
                    [
                        $this->heading('Transfer ke vendor, rekaman kuliah, SIA', 1),
                        $this->paragraph('Tiga saluran Unissula yang paling sering lolos dari rapat karena terasa teknis. Pertama: dosen mengunggah rekaman kuliah ke alat ringkasan otomatis. Rekaman berisi wajah, suara, pertanyaan mahasiswa, kadang data kasus. Itu transfer. Kedua: “AI di LMS” yang menyedot teks tugas ke server vendor. Ketiga: ide menyambungkan SIA agar chatbot “tahu status mahasiswa”. Yang ketiga adalah proyek data, bukan fitur chat.'),
                        $this->paragraph('Lokasi server penting. Banyak model generatif diproses di luar Indonesia. UU 27/2022 mengatur transfer data pribadi ke luar wilayah hukum Indonesia. “Kebijakan privasi vendor” bukan dasar hukum Unissula. Dasar Unissula adalah keputusan pengendali plus jaminan yang bisa diaudit.'),
                        $this->bullets([
                            'Rekaman: default jangan unggah ke tools publik. Jika dipakai internal, sebut tujuan, retensi, dan siapa yang akses.',
                            'LMS: matikan fitur yang mengirim isi tugas ke pihak ketiga, sampai klausul selesai.',
                            'SIA: tidak disambung ke model generatif pada tahap 0–2. Identitas plus nilai adalah kombinasi mahal jika bocor.',
                            'Email mahasiswa: agen pembalas otomatis adalah pemrosesan data pribadi, bukan “efisiensi humas”.',
                        ]),
                        $this->paragraph('Contoh rapat yang jujur: “Semester ini Unissula tidak menyambungkan SIA ke chatbot, dan rekaman kuliah tidak boleh diunggah ke model publik.” Itu keputusan data. Bisa ditulis hari ini, tanpa menunggu tender.'),
                    ],
                ),
                $this->textLesson(
                    'Integritas akademik: Permendikbudristek 39/2021',
                    'Plagiat, fabrikasi, kepengarangan tidak sah — AI tidak membuat pasal ini usang.',
                    16,
                    [
                        $this->heading('Integritas akademik: Permendikbudristek 39/2021', 1),
                        $this->paragraph('Peraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Nomor 39 Tahun 2021 tentang Integritas Akademik dalam Menghasilkan Karya Ilmiah berlaku di Unissula. Nilai yang disebut: kejujuran, kepercayaan, keadilan, kehormatan, tanggung jawab, keteguhan hati. Pelanggaran meliputi fabrikasi, falsifikasi, plagiat, kepengarangan tidak sah, konflik kepentingan, dan pengajuan jamak.'),
                        $this->paragraph('Model generatif tidak disebut namanya di peraturan 2021. Perbuatannya yang diukur. Menyerahkan keluaran model sebagai karya sendiri tanpa kontribusi substansial dekat dengan kepengarangan tidak sah atau plagiat, tergantung fakta. Menyuruh orang lain — termasuk “joki” berbasis AI — membuat skripsi tetap pelanggaran. Senat perguruan tinggi yang memeriksa, pemimpin yang menjatuhkan sanksi administratif.'),
                        $this->paragraph('UU 12/2012 tentang Pendidikan Tinggi menempatkan kejujuran sebagai asas. BudAI menempatkan akhlak sebagai strategi, bukan poster. Kebijakan AI kelas yang tidak menaut ke Permendikbudristek 39/2021 akan terasa seperti aturan IT. Kebijakan yang menaut akan terasa seperti urusan senat — dan itu tempatnya.'),
                        $this->paragraph('Deteksi AI otomatis bukan hakim. Alat deteksi salah positif, terutama pada Bahasa Indonesia. Unissula yang menggantungkan integritas pada skor detektor sedang membeli ketenangan palsu. Wawancara, draf berproses, dan penilaian lisan lebih jujur — dan lebih sesuai meja pengajaran.'),
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
            'description' => 'UNESCO untuk dosen dan mahasiswa, diterjemahkan dari FTI ke fakultas lain.',
            'lessons' => [
                $this->textLesson(
                    'UNESCO untuk dosen: AI Competency Framework for Teachers',
                    'Lima dimensi, tiga tingkat. Unissula tidak mewajibkan seluruh dosen jadi informatika.',
                    16,
                    [
                        $this->heading('UNESCO untuk dosen', 1),
                        $this->paragraph('UNESCO AI Competency Framework for Teachers (2024, Miao & Cukurova) adalah rujukan global: 15 kompetensi pada lima dimensi — human-centred mindset, ethics of AI, AI foundations and applications, AI pedagogy, AI for professional learning — dengan tingkat Acquire, Deepen, Create. Ia dirancang agar negara dan lembaga menyusun kerangka sendiri, bukan agar Unissula menyalin silabus Paris.'),
                        $this->diagram(
                            'Dosen Unissula naik tingkat: Acquire dulu di semua fakultas. Create bukan syarat SK.',
                            [
                                ['id' => 'acq', 'label' => 'Acquire', 'hint' => 'semua'],
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
                            ['Dimensi UNESCO', 'Arti Acquire di Unissula', 'Bukan artinya'],
                            [
                                ['Human-centred mindset', 'Dosen tetap penentu asesmen; AI tidak menggantikan pengajaran BudAI', 'Menolak semua alat'],
                                ['Ethics of AI', 'Tahu PDP, bias, dan kapan data mahasiswa dilarang diunggah', 'Hafal pasal tanpa praktik kelas'],
                                ['Fondasi & aplikasi', 'Paham chatbot ≠ agen ≠ model yang dilatih kampus', 'Wajib coding'],
                                ['AI pedagogy', 'Mendesain tugas yang tetap mengukur manusia', 'Mewajibkan mahasiswa memakai satu merek'],
                                ['Professional learning', 'Belajar bersama sejawat, bukan tutorial vendor semalam', 'Sertifikat 3 jam sebagai bukti kompetensi Create'],
                            ],
                        ),
                        $this->paragraph('Target jujur tahap 3: seluruh dosen Unissula Reach Acquire pada mindset dan ethics. Deepen untuk dosen pilot FTI dan fakultas non-teknik. Create — merancang pemakaian yang baru — tidak wajib universitas; ia milik orang yang memang mengajar komputasi atau riset AI. Melatih 13 fakultas sampai Create adalah FOMO berseragam.'),
                    ],
                ),
                $this->textLesson(
                    'UNESCO untuk mahasiswa: AI Competency Framework for Students',
                    'Dua belas kompetensi, empat dimensi. Mahasiswa Unissula bukan hanya “pengguna ChatGPT”.',
                    16,
                    [
                        $this->heading('UNESCO untuk mahasiswa', 1),
                        $this->paragraph('UNESCO AI Competency Framework for Students (2024) memuat 12 kompetensi pada empat dimensi: human-centred mindset, ethics of AI, AI techniques and applications, AI system design — dengan tingkat Understand, Apply, Create. Visi UNESCO: mahasiswa sebagai warga yang menilai AI, bukan hanya konsumen prompt.'),
                        $this->paragraph('Di Unissula, dimensi etika bertemu BudAI: tidak menipu, tidak menyalin, tidak menyerahkan amanah karya kepada mesin. Mahasiswa FK yang mengunggah data kasus ke model publik gagal ethics, bukan gagal “melek teknologi”. Mahasiswa Informatika yang hanya pintar prompt tanpa paham batasan model gagal techniques, meski nilainya tinggi.'),
                        $this->bullets([
                            'Understand (semua program studi): AI bisa salah, bisa bias, dan keluaran bukan sumber primer.',
                            'Apply (sesuai silabus): memakai alat dengan deklarasi, sitasi, dan verifikasi manusia.',
                            'Create (terutama FTI): merancang pemakaian atau sistem dengan pengawasan, bukan demo tanpa pemilik.',
                        ]),
                        $this->paragraph('Jangan gabungkan kompetensi mahasiswa dengan pemasaran PMB. “Unissula AI-ready” di brosur tanpa kebijakan kelas adalah klaim. Klaim itu akan ditagih orang tua saat ujian dan saat wisuda.'),
                    ],
                ),
                $this->textLesson(
                    'Dari FTI ke fakultas kesehatan dan hukum',
                    'Satu kebijakan universitas, tiga beban risiko. Jangan rata.',
                    16,
                    [
                        $this->heading('Dari FTI ke fakultas kesehatan dan hukum', 1),
                        $this->paragraph('Kebijakan minimum universitas harus bisa dijalankan di Pendidikan Guru Sekolah Dasar dan di Profesi Dokter. Detailnya tidak rata. FTI boleh mendalami tools. FK menahan data spesifik. FH menahan keaslian argumentasi hukum.'),
                        $this->table(
                            ['Fakultas (fakta publik)', 'Beban khas', 'Kapasitas yang wajar dulu'],
                            [
                                ['FTI — Informatika Baik Sekali; Elektro & Industri Unggul', 'Mahasiswa sudah memakai tools; risiko: arsip kode ke vendor, ujian lab bocor', 'Deepen pedagogi + klausul repo'],
                                ['Kedokteran / FKG / Keperawatan — banyak program Unggul', 'Data kesehatan, OSCE, etika profesi', 'Acquire ethics + larangan keras data pasien'],
                                ['Hukum — Unggul, ASIC', 'Karya tulis, opini, plagiat, joki', 'Acquire + asesmen lisan / proses draf'],
                                ['FKIP, FAI, Psikologi, dll.', 'Tugas esai massal, calon guru, data klien praktikum', 'Acquire + kebijakan praktikum'],
                            ],
                        ),
                        $this->paragraph('Pelatihan yang hanya diisi FTI akan menghasilkan dua Unissula: yang ngerti dan yang diam. Diam di fakultas kesehatan adalah risiko PDP. Diam di hukum adalah risiko integritas. Anggaran tahap 3 yang tidak menyentuh dua klaster itu salah sasaran, meski demonya bagus di lab robotik.'),
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
            'description' => 'Lembar yang bisa dibawa ke rapat. Bukan SK fiktif, bukan data intern.',
            'lessons' => [
                $this->documentLesson(
                    'Lembar keputusan pimpinan Unissula',
                    'Satu halaman untuk rapat rektorat dan YBWSA. Bukan checklist belanja tools.',
                    12,
                    [
                        $this->heading('Lembar keputusan pimpinan Unissula', 1),
                        $this->paragraph('Unduh PDF ini sebelum rapat. Ini kerangka keputusan, bukan SK yang sudah berlaku, dan bukan organigram intern yang dikarang. Tiap baris butuh pemilik: rektorat, dekan, atau YBWSA. Kalau rapat berakhir tanpa pemilik, Unissula belum adopsi. Unissula baru menonton slide.'),
                        $this->paragraph('Lembar ini merangkum tahap 0–1 peta jalan: satu prioritas tahun ini, data yang dilarang keluar, penandatangan, kebijakan kelas minimum, dan apa yang tidak dimulai minggu pertama. Isi contohnya memakai nama publik Unissula agar rapat tidak abstrak. Ganti isinya di rapat sungguhan.'),
                    ],
                    [
                        'filename' => 'lembar-keputusan-pimpinan-unissula.pdf',
                        'title' => 'Lembar Keputusan Pimpinan Unissula — Adopsi AI',
                        'paragraphs' => [
                            'Kursus: AI untuk Kampus. Open Course. Bukan lab, bukan konsol, bukan SK resmi Unissula.',
                            'Kasus: Universitas Islam Sultan Agung (https://unissula.ac.id/), PTS di bawah Yayasan Badan Wakaf Sultan Agung, akreditasi institusi Unggul BAN-PT, strategi BudAI, motto Khaira Ummah.',
                            'Sifat dokumen: kerangka untuk rapat pimpinan. Bukan kebijakan yang sudah diundangkan.',
                            '1. Prioritas tahun ini (sebut satu): misalnya kebijakan kelas minimum di seluruh fakultas, BUKAN lisensi chatbot untuk 13 fakultas.',
                            '2. Data yang dilarang keluar ke vendor tanpa dasar UU 27/2022 PDP: identitas+NIM, nilai SIA, rekaman kuliah, arsip tugas/skripsi, data kesehatan FK. Sebut yang boleh dengan klausul (jika ada).',
                            '3. Siapa menandatangani: kebijakan universitas = rektorat; kontrak di atas ambang = YBWSA; pilot fakultas = dekan terkait (contoh: Dekan FTI untuk Informatika).',
                            '4. Kebijakan mahasiswa memakai AI: ujian tertutup dilarang; tugas boleh dengan deklarasi; skripsi mengikuti Permendikbudristek 39/2021. Diam = tidak diterima.',
                            '5. Apa yang tidak dimulai minggu pertama: agen hidup di jaringan Kaligawe, unggah arsip tugas, wajib satu merek ke seluruh dosen, sambung SIA ke model generatif.',
                            'Rujukan rapat: UNESCO Recommendation on the Ethics of AI (2021); UNESCO Guidance for generative AI in education and research (2023); UU 27/2022 PDP; Permendikbudristek 39/2021; UU 12/2012.',
                            'Adopsi: keputusan wewenang, bukan pembelian chatbot. EnterLMS: academy. Lulus kursus ini tidak men-deploy apa pun dan tidak membuka Course terbatas.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Kebijakan kelas: contoh FTI Informatika',
                    'Draf yang bisa dibawa dekan FTI. Bukan silabus yang mengaku sudah sah.',
                    14,
                    [
                        $this->heading('Kebijakan kelas: contoh FTI Informatika', 1),
                        $this->paragraph('Program Studi Teknik Informatika Unissula berdiri 2003 (SK Dirjen Dikti 1421/D/T/2003) dan terakreditasi Baik Sekali LAM-INFOKOM (2024). Itu alasan publik mengapa contoh kelas dimulai di sini: mahasiswa sudah dekat dengan tools, ujian lab rentan, repositori kode mudah bocor ke vendor.'),
                        $this->paragraph('PDF takeaway adalah draf rapat dekan FTI dan dosen pengampu, bukan peraturan fakultas yang berlaku. Fakultas lain boleh meniru struktur (boleh / deklarasi / dilarang), bukan menyalin izin kode.'),
                    ],
                    [
                        'filename' => 'kebijakan-kelas-fti-informatika-unissula.pdf',
                        'title' => 'Draf Kebijakan Kelas AI — FTI Informatika Unissula',
                        'paragraphs' => [
                            'Sifat: contoh deliverable kursus AI untuk Kampus. Bukan SK Dekan FTI yang sudah diundangkan.',
                            'Program: S1 Teknik Informatika, Fakultas Teknologi Industri, Universitas Islam Sultan Agung.',
                            'Prinsip: AI adalah asisten; yang dinilai adalah kompetensi mahasiswa. Sejalan BudAI (kejujuran) dan Permendikbudristek 39/2021.',
                            'Ujian lab, kuis tertutup, uji kompetensi: model generatif dan agen dilarang, kecuali soal secara tertulis menguji pemakaian alat.',
                            'Tugas pemrograman take-home: boleh memakai AI untuk draf dan penjelasan, wajib paragraf deklarasi (alat, prompt ringkas, bagian mana yang diubah manusia). Dosen boleh memanggil wawancara kode 10 menit.',
                            'Proyek kelompok: satu deklarasi per kelompok. Tidak boleh menyerahkan keluaran model tanpa pengujian di lingkungan mahasiswa.',
                            'Skripsi: kontribusi penulis harus bisa dijelaskan tanpa alat. Dataset atau kode milik pihak ketiga tidak diunggah ke model publik. Data kampus (nilai, log SIA, identitas) dilarang sebagai bahan tugas ke vendor.',
                            'Repositori: jangan tempel API key, data mahasiswa, atau rekaman kelas ke layanan asing. Default: kode tugas tetap di infrastruktur yang disetujui FTI.',
                            'Pelanggaran: diproses sebagai isu integritas akademik (bukan sekadar “tidak mengikuti tren”), merujuk Permendikbudristek 39/2021.',
                            'Evaluasi pilot: satu semester, lalu tulis apa yang gagal diperiksa dosen. Tanpa evaluasi, draf ini hanya poster.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Klausul data vendor untuk LMS dan SIA',
                    'Yang wajib ada di kontrak sebelum lisensi. Bukan nama vendor yang dikarang.',
                    12,
                    [
                        $this->heading('Klausul data vendor untuk LMS dan SIA', 1),
                        $this->paragraph('Lesson ini tidak menunjuk merek. Ia menunjuk klausul. Unissula yang sudah punya LMS atau SIA — seperti hampir semua PTS — akan ditawari “AI add-on”. Add-on itu adalah pemrosesan data. UU 27/2022 menuntut tujuan, dasar, dan kendali. PDF berisi daftar klausul yang dibawa ke bagian pengadaan dan ke YBWSA jika ambang kontrak terlampaui.'),
                        $this->paragraph('Vendor yang menolak klausul larangan training, lokasi data, dan hak hapus tidak lolos — meski demonya menolong dosen mengoreksi. Koreksi semu lebih murah daripada insiden PDP.'),
                    ],
                    [
                        'filename' => 'klausul-data-vendor-lms-sia-unissula.pdf',
                        'title' => 'Klausul Data Vendor LMS/SIA — Kerangka Unissula',
                        'paragraphs' => [
                            'Sifat: daftar periksa kontrak, bukan nama vendor, bukan kontrak yang sudah ditandatangani Unissula.',
                            'Dasar: UU 27/2022 PDP; UU ITE beserta perubahannya; UNESCO Recommendation on the Ethics of AI (2021) soal privasi dan pengawasan manusia.',
                            'Para pihak: Unissula sebagai pengendali; vendor sebagai prosesor kecuali terbukti menentukan tujuan sendiri (maka tinjau ulang seluruh hubungan).',
                            'Tujuan terbatas: sebut fungsi (misalnya umpan balik draf tugas), bukan “meningkatkan layanan AI vendor”.',
                            'Data minimum: sebut bidang. Tolak paket yang menyedot seluruh SIA “supaya cerdas”.',
                            'Larangan training: data Unissula tidak boleh dipakai melatih model publik atau model milik vendor untuk klien lain.',
                            'Lokasi pemrosesan: sebut negara. Transfer keluar Indonesia hanya dengan jaminan yang disetujui pengendali.',
                            'Subprosesor: daftar, kabari jika berubah, hak Unissula menolak.',
                            'Hak subjek: mekanisme akses, koreksi, hapus, dan portabilitas dalam batas waktu yang tertulis. Termasuk mahasiswa yang dropout.',
                            'Insiden: kabari Unissula dalam waktu yang tertulis; Unissula yang bicara ke sivitas, bukan vendor yang bicara ke media.',
                            'Keluar kontrak: ekspor data, hapus salinan vendor, kunci enkripsi dikembalikan. Jika tidak bisa, jangan masuk.',
                            'Audit: hak pemeriksaan atau laporan independen. Tanpa audit, klausul hanya pamflet.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Peta jalan 24 bulan Unissula',
                    'Kalender yang bisa ditempel di rapat. Bukan Gantt fiktif dengan nama pejabat intern.',
                    14,
                    [
                        $this->heading('Peta jalan 24 bulan Unissula', 1),
                        $this->paragraph('Empat tahap sebelumnya butuh kalender. PDF ini memecah 24 bulan menjadi fondasi, dua semester pilot, perluasan, dan kelembagaan. Nama unit memakai sebutan publik (rektorat, YBWSA, FTI, senat). Tidak ada nama pejabat intern yang dikarang.'),
                        $this->diagram(
                            'Dua puluh empat bulan: fondasi, pilot FTI+1, perluasan, lalu audit kelembagaan.',
                            [
                                ['id' => 'y1a', 'label' => 'Bulan 1–6', 'hint' => 'fondasi'],
                                ['id' => 'y1b', 'label' => 'Bulan 7–12', 'hint' => 'pilot'],
                                ['id' => 'y2a', 'label' => 'Bulan 13–18', 'hint' => 'perluasan'],
                                ['id' => 'y2b', 'label' => 'Bulan 19–24', 'hint' => 'audit'],
                            ],
                            [
                                ['from' => 'y1a', 'to' => 'y1b'],
                                ['from' => 'y1b', 'to' => 'y2a'],
                                ['from' => 'y2a', 'to' => 'y2b'],
                            ],
                            4,
                        ),
                        $this->paragraph('Geser bulan jika rapat YBWSA tidak bisa bulan ini. Jangan geser urutan: lisensi massal sebelum fondasi, atau Create UNESCO sebelum Acquire.'),
                    ],
                    [
                        'filename' => 'peta-jalan-24-bulan-unissula.pdf',
                        'title' => 'Peta Jalan 24 Bulan Adopsi AI Unissula',
                        'paragraphs' => [
                            'Sifat: kerangka kalender untuk rapat. Bukan keputusan yayasan yang sudah diundangkan.',
                            'Bulan 1–3: rapat sikap rektorat+YBWSA; lembar keputusan terisi pemilik; moratorium agen hidup, unggah arsip, wajib merek.',
                            'Bulan 2–6: inventaris pemakaian mahasiswa, peta sistem SIA/LMS/rekaman, daftar kontrak yang sudah ada. Kebijakan kelas minimum universitas diedarkan ke 13 dekan.',
                            'Bulan 4–6: pelatihan Acquire (UNESCO Teachers: mindset + ethics) untuk perwakilan tiap fakultas, bukan hanya FTI.',
                            'Bulan 7–18: pilot FTI Informatika + satu fakultas non-teknik (FH atau FK). Evaluasi tertulis tiap akhir semester. Tidak menambah fakultas ketiga sebelum evaluasi pertama.',
                            'Bulan 13–18: klausul vendor wajib pada perpanjangan LMS/SIA. Matikan add-on yang menolak larangan training.',
                            'Bulan 13–24: perluasan kebijakan universitas berdasarkan bukti pilot. Deepen hanya di fakultas yang siap, bukan seragam.',
                            'Bulan 19–24: audit tahunan (insiden integritas, insiden data, ketergantungan vendor). Ethical impact assessment ala UNESCO 2021 dalam bentuk yang sanggup dijalankan senat, bukan formulir impor.',
                            'Indikator jujur tahun ke-2: silabus tertulis di fakultas pilot; nol sambungan SIA ke model publik; dosen Acquire terlacak; YBWSA tahu data mana yang keluar.',
                            'Bukan indikator: jumlah lisensi, jumlah prompt, atau ranking karena brosur AI.',
                            'Rujukan: UNESCO AI competency teachers/students (2024); UNESCO GenAI guidance (2023); UU 27/2022; Permendikbudristek 39/2021.',
                        ],
                    ],
                ),
                $this->textLesson(
                    'Risiko yang pimpinan harus sebutkan',
                    'Integritas, privasi, vendor, beban dosen, identitas BudAI. Menyembunyikan risiko lebih mahal.',
                    16,
                    [
                        $this->heading('Risiko yang pimpinan harus sebutkan', 1),
                        $this->paragraph('Menyebut risiko bukan menolak AI. Menyembunyikan risiko adalah yang membuat YBWSA kaget di tahun kedua. UNESCO Recommendation on the Ethics of AI (2021) menempatkan do no harm dan proporsionalitas di depan manfaat yang masih spekulatif.'),
                        $this->diagram(
                            'Empat risiko Unissula yang wajib disebut sebelum tanda tangan, plus siapa yang menanggung.',
                            [
                                ['id' => 'integritas', 'label' => 'Integritas', 'hint' => 'karya'],
                                ['id' => 'privasi', 'label' => 'Privasi', 'hint' => 'PDP'],
                                ['id' => 'vendor', 'label' => 'Vendor', 'hint' => 'gembok'],
                                ['id' => 'beban', 'label' => 'Beban dosen', 'hint' => 'waktu'],
                            ],
                            [],
                            4,
                        ),
                        $this->bullets([
                            'Integritas akademik — tugas, ujian, skripsi kehilangan makna jika kebijakan diam. Permendikbudristek 39/2021 sudah ada; AI hanya mempercepat pelanggarannya.',
                            'Privasi — data mahasiswa dan dosen yang tidak bisa ditarik dari vendor. UU 27/2022. Data FK lebih berat.',
                            'Ketergantungan vendor — Unissula tidak bisa pindah tanpa kehilangan riwayat umpan balik atau bank soal.',
                            'Beban dosen — “pakai AI” tanpa waktu desain ulang kuliah adalah kerja tambahan tersembunyi, bertentangan dengan niat “membantu dosen”.',
                            'Identitas — adopsi yang menabrak BudAI dan khaira ummah (menipu, mempercepat tanpa amanah) adalah risiko reputasi Islami, bukan hanya risiko IT.',
                        ]),
                        $this->paragraph('Tiap risiko butuh wewenang. Integritas: senat dan dosen. Privasi: pengendali data di rektorat, dengan YBWSA pada kontrak besar. Vendor: pengadaan plus pimpinan. Beban dosen: dekan, bukan memo yang hanya turun. Identitas: pimpinan yang mengucapkan BudAI di publik — jangan serahkan ke vendor.'),
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
            'description' => 'Mulai dari undangan yang berisi keputusan. Tutup dengan sumber yang bisa dicek.',
            'lessons' => [
                $this->textLesson(
                    'Mulai dari rapat, bukan dari tools',
                    'Agenda 45 menit untuk rektorat dan YBWSA. Lima keputusan, bukan sepuluh demo.',
                    14,
                    [
                        $this->heading('Mulai dari rapat, bukan dari tools', 1),
                        $this->paragraph('Undangan rapat yang hanya berisi demo vendor akan menghasilkan pembelian. Undangan yang berisi lima pertanyaan di lembar keputusan Unissula akan menghasilkan sikap. Bawa PDF Lesson deliverable, bukan brosur.'),
                        $this->diagram(
                            'Empat puluh lima menit: prioritas, data terlarang, kebijakan kelas, pemilik. Tools menunggu di luar.',
                            [
                                ['id' => 'prioritas', 'label' => 'Prioritas', 'hint' => '10 mnt'],
                                ['id' => 'data', 'label' => 'Data', 'hint' => '15 mnt'],
                                ['id' => 'kelas', 'label' => 'Kelas', 'hint' => '10 mnt'],
                                ['id' => 'pemilik', 'label' => 'Pemilik', 'hint' => '10 mnt'],
                            ],
                            [
                                ['from' => 'prioritas', 'to' => 'data'],
                                ['from' => 'data', 'to' => 'kelas'],
                                ['from' => 'kelas', 'to' => 'pemilik'],
                            ],
                            4,
                        ),
                        $this->heading('Agenda singkat'),
                        $this->bullets([
                            'Sepuluh menit: satu prioritas tahun ini (bukan sepuluh). Contoh jujur: kebijakan kelas minimum, bukan “AI campus”.',
                            'Lima belas menit: data yang dilarang keluar — SIA, rekaman, arsip tugas, data FK.',
                            'Sepuluh menit: kebijakan kelas — boleh, wajib deklarasi, atau dilarang di ujian.',
                            'Sepuluh menit: siapa pemilik keputusan lanjutan (rektorat, dekan FTI, YBWSA).',
                        ]),
                        $this->paragraph('Kalau rapat berakhir tanpa pemilik, Unissula belum adopsi. Unissula baru menonton slide. Vendor menunggu di luar sampai lima keputusan itu tertulis.'),
                    ],
                ),
                $this->textLesson(
                    'Apa yang tidak dimulai minggu pertama',
                    'Daftar yang menahan FOMO tanpa menolak belajar. Unissula tetap boleh kuliah di academy ini hari ini.',
                    12,
                    [
                        $this->heading('Apa yang tidak dimulai minggu pertama', 1),
                        $this->bullets([
                            'Menjalankan agen hidup di jaringan Kampus Kaligawe “supaya kelihatan modern”.',
                            'Mengunggah arsip tugas atau skripsi mahasiswa ke vendor untuk “melatih AI Unissula”.',
                            'Mewajibkan seluruh dosen 13 fakultas memakai satu merek dalam tujuh hari.',
                            'Menyambungkan SIA ke model generatif.',
                            'Membuka konsol runtime di dalam Lesson atau LMS.',
                            'Menggantungkan integritas skripsi pada skor detektor AI saja.',
                        ]),
                        $this->paragraph('Belajar di academy ini boleh dimulai hari ini. Mengoperasikan agen hidup tidak. Tutor di Lesson ini akan menolak membuka konsol, karena Lesson bukan lab.'),
                        $this->quote('Adopsi yang dewasa kelihatan lambat di minggu pertama, dan murah di tahun kedua. Unissula sudah Unggul tanpa chatbot. Jangan tukar itu dengan invoice.'),
                    ],
                ),
                $this->textLesson(
                    'Pustaka: UNESCO, UU PDP, Dikti, Unissula',
                    'Sumber bernama, bertanggal, bisa dicek. Bukan blog vendor, bukan SK fiktif.',
                    16,
                    [
                        $this->heading('Pustaka', 1),
                        $this->paragraph('Kerangka ini hanya sekuat sumbernya. Yang berikut bisa dibuka di rapat. Yang tidak ada di daftar — jumlah mahasiswa intern, SK Rektor yang tidak terbit, program AI yang tidak diumumkan — sengaja tidak dipakai.'),
                        $this->heading('Unissula (fakta publik)'),
                        $this->bullets([
                            'Situs resmi: https://unissula.ac.id/ — profil, berita, akreditasi program studi.',
                            'Budaya Akademik Islami (BudAI), deklarasi 18 Agustus 2005: https://unissula.ac.id/budaya-akademik-islami-budai/',
                            'Yayasan Badan Wakaf Sultan Agung: https://ybw-sa.org/ — pendirian 31 Juli 1950, naungan Unissula.',
                            'Akreditasi program studi (FTI, FK, FH, dll.): halaman akreditasi Unissula; LAM-INFOKOM untuk Informatika (Baik Sekali, 2024).',
                            'FTI: berdiri 23 Juli 1993; Informatika SK Dirjen Dikti 1421/D/T/2003; panduan akademik FTI.',
                            'Berita ranking AppliedHE 2026 dan pelantikan pejabat: kanal Unissula / YBWSA (2025–2026). Rektor Prof. Dr. Gunarto; Ketua Umum YBWSA Prof. Dr. Bambang Tri Bawono — disebut sebagai fakta publik jabatan, bukan sebagai pendukung kursus ini.',
                        ]),
                        $this->heading('Internasional'),
                        $this->bullets([
                            'UNESCO. Recommendation on the Ethics of Artificial Intelligence. 23 November 2021.',
                            'UNESCO. AI and education: Guidance for policy-makers. 2021 (Miao & Holmes).',
                            'UNESCO. Guidance for generative AI in education and research. 2023 (Miao & Holmes).',
                            'UNESCO. AI competency framework for teachers. 2024 (Miao & Cukurova). 15 kompetensi, lima dimensi, Acquire–Deepen–Create.',
                            'UNESCO. AI competency framework for students. 2024. 12 kompetensi, empat dimensi, Understand–Apply–Create.',
                            'OECD AI Principles — rujukan pelengkap tata kelola, bukan pengganti hukum Indonesia.',
                            'NIST AI Risk Management Framework (AI RMF 1.0) — rujukan pelengkap risiko, terutama untuk meja tata kelola teknis kelak.',
                        ]),
                        $this->heading('Indonesia'),
                        $this->bullets([
                            'Undang-Undang Nomor 12 Tahun 2012 tentang Pendidikan Tinggi.',
                            'Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.',
                            'Undang-Undang Informasi dan Transaksi Elektronik beserta perubahannya.',
                            'Permendikbudristek Nomor 39 Tahun 2021 tentang Integritas Akademik dalam Menghasilkan Karya Ilmiah.',
                            'Peraturan Pemerintah Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan Tinggi dan Pengelolaan Perguruan Tinggi.',
                            'Surat Edaran Menteri Komunikasi dan Informatika Nomor 9 Tahun 2023 tentang Etika Kecerdasan Artifisial — arah, bukan pengganti SK kampus.',
                        ]),
                        $this->paragraph('Kalau suatu klaim di Lesson tidak tertaut ke salah satu sumber ini, perlakukan sebagai kerangka diskusi, bukan sebagai berita Unissula. Academy ini menolak mengarang organigram dan menolak menjual merek. Tugasmu setelah lulus kuis: bawa lembar keputusan ke rapat, bukan membawa konsol ke jaringan kampus.'),
                    ],
                ),
            ],
        ];
    }
}
