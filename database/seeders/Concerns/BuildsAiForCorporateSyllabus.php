<?php

namespace Database\Seeders\Concerns;

/**
 * Syllabus for AI untuk Korporat.
 *
 * Running case: NEX Energi Masa Depan.
 * Public facts only. Lessons stay teaching text + figures. Not a live lab.
 */
trait BuildsAiForCorporateSyllabus
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
            $this->sectionUseCase(),
            $this->sectionTigaMeja(),
            $this->sectionData(),
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
            'title' => 'Konteks NEX Energi Masa Depan dan mandat pimpinan',
            'description' => 'Satu perusahaan PLTS institusi, empat kursi, satu kerangka. Bukan slide vendor.',
            'lessons' => [
                $this->textLesson(
                    'Untuk siapa kursus ini',
                    'Pimpinan NEX yang harus mengambil sikap di rapat, bukan di server.',
                    16,
                    [
                        $this->heading('Untuk siapa kursus ini', 1),
                        $this->paragraph('Kursus ini untuk pimpinan yang harus mengambil sikap tentang AI di perusahaan: direksi, manajer proyek, kantor, dan pengawas. Kamu tidak perlu menulis kode. Kamu perlu bahasa yang sama sebelum ada yang membeli tools.'),
                        $this->paragraph('Ini Open Course, gratis, dalam Bahasa Indonesia. Kamu boleh enroll sendiri. Kasus berjalannya satu: NEX Energi Masa Depan di https://energimasadepan.com. Setiap Lesson memakai NEX. Perusahaan lain boleh membaca, tetapi kami tidak akan mengganti nama di tengah jalan menjadi “perusahaan X”.'),
                        $this->paragraph('Ini kerangka adopsi AI NEX yang komprehensif, bukan briefing 90 menit. Kamu akan berjalan dari konteks perusahaan, arti adopsi versus belanja, peta jalan empat tahap, cara menemukan use case yang andal, tiga meja (operasional, tata kelola, SDM), data dan hukum, contoh deliverable nex-project, lalu pustaka yang bisa dicek.'),
                        $this->heading('Empat kursi, satu meja'),
                        $this->bullets([
                            'Direksi — prioritas bisnis PLTS institusi, anggaran, dan janji ke pelanggan serta pemegang saham.',
                            'Manajer proyek — survey, kajian teknis, instalasi, komisioning, pantau. Proses kerja lapangan dan kantor.',
                            'Kantor — desk nex-project, pipeline, SDM, dan kebijakan memakai AI di kerja sehari-hari.',
                            'Pengawas — risiko reputasi institusi, kontrak data, dan mandat jangka panjang.',
                        ]),
                        $this->diagram(
                            'Empat kursi NEX duduk di satu kerangka adopsi, bukan di empat proyek terpisah.',
                            [
                                ['id' => 'direksi', 'label' => 'Direksi', 'hint' => 'prioritas'],
                                ['id' => 'proyek', 'label' => 'Proyek', 'hint' => 'lapangan'],
                                ['id' => 'kantor', 'label' => 'Kantor', 'hint' => 'desk'],
                                ['id' => 'awas', 'label' => 'Pengawas', 'hint' => 'risiko'],
                            ],
                            [],
                            4,
                        ),
                        $this->paragraph('Kamu boleh enroll sendiri. Menyelesaikan kursus ini tidak membuka Course terbatas dan tidak memberi akses produksi. Kalau yang kamu cari adalah tombol untuk menjalankan agen di kantor NEX, kamu di Course yang salah. Itu bukan Lesson.'),
                        $this->quote('Peta jalan yang dewasa kelihatan lambat di minggu pertama, dan murah di tahun kedua. UNESCO menyebut martabat manusia dan pengawasan manusia sebagai syarat, bukan hiasan.'),
                    ],
                    preview: true,
                ),
                $this->textLesson(
                    'NEX Energi Masa Depan sebagai kasus berjalan',
                    'Fakta publik yang boleh dipakai di rapat. Bukan organigram intern yang dikarang.',
                    18,
                    [
                        $this->heading('NEX Energi Masa Depan sebagai kasus berjalan', 1),
                        $this->paragraph('NEX mempublikasikan diri sebagai Energi Masa Depan Indonesia: PLTS atap untuk operasional institusi. Situs resmi https://energimasadepan.com. Footer 2026 menyebut PT NEX. Tagline publik: transformasi energi hijau untuk instansi. Itu bisnis yang sudah jalan, bukan slide “AI enterprise”.'),
                        $this->paragraph('Fakta kelembagaan yang dipakai kursus ini hanya yang terbit di kanal publik NEX. Kami tidak mengarang jumlah karyawan intern, SK direksi, atau spesifikasi produk yang tidak diumumkan. nex-project disebut karena host publiknya ada: https://project.energimasadepan.com.'),
                        $this->table(
                            ['Fakta publik', 'Sumber yang bisa dicek', 'Implikasi untuk AI'],
                            [
                                ['Brand NEX; tagline Energi Masa Depan Indonesia; PLTS atap untuk instansi', 'energimasadepan.com', 'Adopsi AI menempel pada bisnis PLTS institusi, bukan chatbot generik'],
                                ['PT NEX di footer 2026', 'energimasadepan.com, footer', 'Badan hukum yang menandatangani kontrak data dan vendor'],
                                ['NUSA Solar by PT IDN Solar Tech; TKDN 10010/SJ-IND.8/TKDN/9/2021; kontak PT Nusantara Energi Khatulistiwa', 'energimasadepan.com/product', 'Rantai pasok lokal; jangan campur entitas di klausul tanpa rapat'],
                                ['Proses: Survey, Kajian teknis, Instalasi, Komisioning, Pantau', 'energimasadepan.com #proses', 'AI menempel di kajian dan pantau, bukan mengganti komisioning manusia'],
                                ['Simulasi ROI publik dan konsultasi “Halo NEX”', 'energimasadepan.com #calculator', 'Decision-support yang sudah ada; bukan agen yang menandatangani kontrak'],
                                ['Armada: stasiun/depo 50+, SPBU 20+, perkantoran 10+, instansi 10+', 'energimasadepan.com #sektor', 'Data lokasi institusi adalah data pelanggan, bukan dataset latihan'],
                                ['nex-project: desk, pipeline, solar', 'project.energimasadepan.com', 'AI infus di produk kantor adalah contoh adopsi, bukan konsol academy'],
                            ],
                        ),
                        $this->paragraph('NEX sudah punya alat bantu keputusan di situs (simulasi ROI, dampak armada) dan produk kantor (nex-project). Itu bukti perusahaan ini tidak anti-teknologi. Itu bukan izin untuk mengunggah arsip pelanggan institusi ke model publik tanpa dasar.'),
                        $this->paragraph('Contoh di Lesson berikutnya — lembar keputusan, klausul vendor, peta jalan 24 bulan — adalah kerangka yang bisa dibawa ke rapat. Bukan SK yang sudah berlaku. Jika NEX kelak menerbitkan kebijakan resmi, kebijakan itu yang diikuti; kerangka ini hanya latihan keputusan.'),
                    ],
                ),
                $this->textLesson(
                    'Bukan kursus men-deploy agen',
                    'Adopsi bukan lab. Lesson bukan konsol. NEX bukan studi kelayakan vendor.',
                    12,
                    [
                        $this->heading('Bukan kursus men-deploy agen', 1),
                        $this->paragraph('EnterLMS adalah academy. Tutor di sini menjelaskan. Ia tidak men-deploy agen di jaringan NEX, tidak membuka desktop runtime, dan tidak menyelesaikan Lesson untukmu.'),
                        $this->paragraph('Pengenalan Agen AI mengajarkan apa itu agen. Kursus ini mengajarkan apa yang pimpinan NEX putuskan sebelum ada chatbot, agen, atau slide “AI enterprise” di ruang rapat direksi. nex-project yang sudah menginfus AI di desk adalah contoh produk — bukan tombol di Lesson ini.'),
                        $this->bullets([
                            'Tidak ada lab di Lesson.',
                            'Tidak ada akses produksi karena lulus kuis.',
                            'Restricted Course tentang operasi runtime tetap tersembunyi dari katalog ini.',
                            'Tidak ada data intern NEX yang dikarang agar terasa “konsultan”.',
                        ]),
                        $this->paragraph('Kalau NEX suatu hari mengoperasikan agen di lapangan PLTS, itu di luar academy ini. Mandat kamu di sini: wewenang, data, use case, dan kapasitas. UNESCO Recommendation on the Ethics of Artificial Intelligence (23 November 2021) menempatkan pengawasan manusia sebagai syarat — bukan konsol.'),
                    ],
                ),
                $this->textLesson(
                    'Empat kursi: direksi, proyek, kantor, pengawas',
                    'PLTS institusi punya direksi. Proyek punya manajer. Desk punya staf. Jangan samakan wewenangnya.',
                    16,
                    [
                        $this->heading('Empat kursi, satu meja', 1),
                        $this->paragraph('NEX menjual PLTS ke instansi. Kontrak itu menyentuh atap, rekening listrik, dan reputasi pelanggan institusi. Direksi bukan “tamu” di rapat AI. Direksi pemegang janji ke instansi, anggaran, dan — pada kontrak yang menyentuh data proyek dalam skala perusahaan — tanda tangan yang tidak bisa diwakilkan operator desk.'),
                        $this->paragraph('Manajer proyek memegang lima langkah publik: survey, kajian teknis, instalasi, komisioning, pantau. Kantor memegang nex-project: pipeline, hunting, solar, hari ini. Pengawas memegang risiko reputasi jika data stasiun kereta, SPBU, atau BUMN bocor ke model publik.'),
                        $this->paragraph('Staf desk memegang kerja sehari-hari. Diam staf tentang AI di pipeline adalah kebijakan tersembunyi, dan biasanya kebijakan terburuk. Pelanggan institusi tidak menandatangani kontrak vendor AI NEX, tetapi mereka yang menanggung skandal data lokasi.'),
                        $this->diagram(
                            'Wewenang mengalir dari direksi dan pengawas ke proyek, lalu ke desk. Jangan dibalik.',
                            [
                                ['id' => 'dir', 'label' => 'Direksi', 'hint' => 'janji'],
                                ['id' => 'awas', 'label' => 'Pengawas', 'hint' => 'risiko'],
                                ['id' => 'proy', 'label' => 'Proyek', 'hint' => 'lapangan'],
                                ['id' => 'desk', 'label' => 'Desk', 'hint' => 'nex-project'],
                            ],
                            [
                                ['from' => 'dir', 'to' => 'proy'],
                                ['from' => 'awas', 'to' => 'dir'],
                                ['from' => 'proy', 'to' => 'desk'],
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
            'description' => 'Membeli chatbot bukan kebijakan perusahaan. NEX mengadopsi wewenang.',
            'lessons' => [
                $this->textLesson(
                    'Apa artinya adopsi AI di perusahaan',
                    'Keputusan berulang: kerja, data, vendor, dan wewenang — di NEX, bukan di slide.',
                    16,
                    [
                        $this->heading('Apa artinya adopsi AI di perusahaan', 1),
                        $this->paragraph('Adopsi AI di NEX bukan satu proyek IT. Itu rangkaian keputusan: proses kerja mana yang boleh dibantu di desk nex-project dan di lapangan PLTS; data siapa yang boleh masuk ke vendor; siapa yang menandatangani; dan kapan berhenti. UNESCO Recommendation on the Ethics of AI (2021) menempatkan martabat manusia, pengawasan manusia, dan pelindungan data sebagai syarat, bukan hiasan.'),
                        $this->heading('Tiga pertanyaan yang selalu kembali'),
                        $this->bullets([
                            'Untuk siapa? Prospek institusi, staf desk, proyek lapangan, atau pimpinan — masing-masing beda risiko di NEX.',
                            'Dengan data apa? Tagihan listrik calon pelanggan, lokasi stasiun/SPBU, pipeline tender, kontrak, penilaian karyawan.',
                            'Siapa yang bertanggung jawab jika salah? Manajer proyek, direksi, kantor, atau pengawas.',
                        ]),
                        $this->paragraph('Kalau rapat hanya membahas merek tools, itu belum adopsi. Itu belanja. Perusahaan yang menjanjikan energi hijau ke instansi tidak diukur dari jumlah lisensi chatbot, melainkan dari apakah keputusan itu bisa dijelaskan ke pelanggan institusi, ke UU 27/2022, dan ke pengawas.'),
                        $this->paragraph('Adopsi yang jujur juga mencakup menolak. “Tahun ini NEX tidak men-deploy agen di jaringan lapangan, dan staf tidak mengunggah data stasiun ke model generatif publik” adalah keputusan adopsi. Diam bukan netral. Diam adalah izin tersembunyi.'),
                    ],
                ),
                $this->textLesson(
                    'Bukan beli chatbot lalu selesai',
                    'Chatbot, agen, dan kebijakan adalah tiga benda berbeda. Vendor sering menjualnya sebagai satu.',
                    14,
                    [
                        $this->heading('Bukan beli chatbot lalu selesai', 1),
                        $this->paragraph('Chatbot menjawab percakapan. Agen menerima tujuan, memakai alat, dan bekerja dalam loop. Kebijakan perusahaan memutuskan mana yang boleh masuk ke desk NEX dan ke data pelanggan institusi. Tiga benda ini sering dijual sebagai satu paket “AI enterprise”.'),
                        $this->diagram(
                            'Chatbot menjawab. Agen bertindak. Kebijakan NEX yang memutuskan mana yang boleh masuk.',
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
                        $this->paragraph('Contoh NEX yang sah sebagai diskusi rapat, bukan sebagai rahasia intern: simulasi ROI di energimasadepan.com berbeda risikonya dari agen kantor di desk nex-project, dan berbeda lagi dari model yang dilatih dengan arsip kontrak institusi. Yang pertama mungkin layanan informasi. Yang kedua menyentuh data kerja. Yang ketiga menyentuh rahasia dagang dan UU PDP sekaligus.'),
                        $this->quote('Yang NEX adopsi bukan merek. Yang diadopsi adalah wewenang: siapa boleh memakai alat apa, pada data siapa, sampai kapan.'),
                    ],
                ),
                $this->textLesson(
                    'Tren, proyek IT, dan kebijakan: tiga benda',
                    'FOMO, pengadaan, dan keputusan pimpinan adalah tiga kalender berbeda. Jangan disatukan di satu undangan.',
                    16,
                    [
                        $this->heading('Tren, proyek IT, dan kebijakan', 1),
                        $this->paragraph('Tren: berita data center AI, demo vendor, staf yang sudah memakai model generatif di HP. Itu nyata. Tekanan “supaya tidak ketinggalan” akan masuk rapat. Tren bukan dasar keputusan direksi.'),
                        $this->paragraph('Proyek IT: lisensi, integrasi nex-project, pantau armada, helpdesk. Itu pengadaan. Ada anggaran, ada vendor, ada SLA. Proyek IT yang dimulai sebelum kebijakan desk dan peta data adalah belanja yang mencari justifikasi.'),
                        $this->paragraph('Kebijakan: siapa boleh apa. OECD AI Principles (2019, diperbarui) memisahkan pertumbuhan yang inklusif, nilai-nilai berpusat manusia, dan transparansi. Tiga itu tidak selesai dengan satu invoice.'),
                        $this->table(
                            ['Benda', 'Pertanyaan NEX', 'Pemilik yang wajar'],
                            [
                                ['Tren', 'Apa yang staf sudah lakukan diam-diam di desk dan di lapangan?', 'Kantor + inventaris shadow AI'],
                                ['Proyek IT', 'Alat apa yang masuk nex-project atau pantau, dengan data apa?', 'Direksi + pemilik sistem, sepengetahuan pengawas jika kontrak besar'],
                                ['Kebijakan', 'Apa yang wajib, boleh dengan deklarasi, atau dilarang di data institusi?', 'Direksi; proyek boleh lebih ketat di lapangan'],
                            ],
                        ),
                        $this->paragraph('Undangan rapat yang hanya berisi demo akan menghasilkan pembelian. Undangan yang memisahkan tiga kolom ini akan menghasilkan sikap. Kalau ketiga kolom itu dicampur, kantor akan dituduh “menghambat inovasi” ketika sebenarnya mereka menahan kontrak.'),
                    ],
                ),
                $this->textLesson(
                    'Mengapa NEX tidak copy-paste strategi Big Tech mentah-mentah',
                    'Google, Microsoft, atau startup Silicon Valley boleh dibaca. UU PDP dan pelanggan institusi Indonesia tidak ikut tertyalin.',
                    14,
                    [
                        $this->heading('Mengapa NEX tidak copy-paste', 1),
                        $this->paragraph('Banyak perusahaan dunia sudah punya pedoman generatif AI. UNESCO Recommendation on the Ethics of AI (2021) justru memperingatkan: konteks setempat, hukum, dan pengawasan manusia tidak bisa diimpor. Menyalin playbook Big Tech tidak menyalin hukum Indonesia, tidak menyalin pelanggan BUMN, dan tidak menyalin reputasi PLTS institusi.'),
                        $this->paragraph('NEX menjual ke stasiun, SPBU, perkantoran, dan instansi. “Human agency” dalam UNESCO 2021 di NEX tidak netral-abstrak: ia bertemu janji ke institusi, keselamatan instalasi, dan data lokasi aset. Kebijakan yang hanya bilang “deklarasikan pemakaian ChatGPT” tanpa menaut ke UU 27/2022 akan terasa asing di rapat pengawas.'),
                        $this->paragraph('Hukum yang mengikat NEX adalah hukum Indonesia: UU 27/2022 PDP, UU ITE beserta perubahannya, dan — terbatas — Surat Edaran Menkominfo 9/2023 tentang Etika Kecerdasan Artifisial. SE bukan undang-undang; ia tidak menggantikan keputusan direksi, tetapi ia menandai arah negara.'),
                        $this->paragraph('Baca kebijakan luar sebagai bahan. Tulis keputusan NEX sebagai pemilik. Lesson deliverable menyediakan draf untuk rapat, bukan terjemahan kebijakan orang lain.'),
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
            'description' => 'Sikap, fondasi, pilot kantor, kelembagaan. NEX tidak melompat ke agen hidup.',
            'lessons' => [
                $this->textLesson(
                    'Peta jalan empat tahap NEX',
                    'Satu gambar untuk rapat direksi dan pengawas. Bukan sepuluh proyek paralel.',
                    18,
                    [
                        $this->heading('Peta jalan empat tahap NEX', 1),
                        $this->paragraph('Kerangka ini memakai empat tahap. Nama tahap boleh diubah di rapat. Urutannya jangan. Melompat ke “AI enterprise” sebelum wewenang dan data tertulis adalah cara mahal untuk belajar di tahun kedua. NIST AI RMF 1.0 (2023) memetakan GOVERN, MAP, MEASURE, MANAGE. Tanpa tahap, tidak ada use case — hanya demo.'),
                        $this->diagram(
                            'Peta jalan NEX: sikap dulu, fondasi, pilot kantor, baru kelembagaan. Bukan tools dulu.',
                            [
                                ['id' => 'sikap', 'label' => 'Sikap', 'hint' => 'wewenang'],
                                ['id' => 'fondasi', 'label' => 'Fondasi', 'hint' => 'inventaris'],
                                ['id' => 'pilot', 'label' => 'Pilot', 'hint' => 'kantor'],
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
                                ['0–1 Sikap & fondasi', 'Siapa menandatangani? Data apa yang dilarang keluar? Apa kebijakan desk minimum?', 'Lembar keputusan pimpinan NEX'],
                                ['2 Pilot', 'Satu use case kantor di nex-project: apa yang diuji 1–2 kuartal?', 'Filter use case + catatan evaluasi'],
                                ['3 Perluasan', 'Apa yang berlaku ke lapangan PLTS? Klausul vendor mana yang wajib?', 'Klausul data proyek + pelatihan staf'],
                                ['4 Kelembagaan', 'Siapa mengaudit tahunan? Bagaimana henti darurat tanpa academy ini?', 'Peta jalan 24 bulan yang hidup, bukan poster'],
                            ],
                        ),
                        $this->paragraph('UNESCO Guidance for policy-makers (2021) dan NIST AI RMF sejalan: tata kelola dulu, peta risiko, ukur, kelola. Peta jalan NEX hanya menerjemahkan itu ke kalender perusahaan PLTS institusi: wewenang dulu, data, satu use case kantor, lalu kelembagaan. nex-project adalah tempat pilot yang sudah ada — bukan alasan melewati tahap.'),
                        $this->paragraph('Direksi tidak perlu menyetujui merek chatbot di tahap 0. Direksi perlu menyetujui bahwa data pelanggan institusi tidak keluar perusahaan tanpa dasar, dan bahwa NEX tidak akan mewajibkan satu merek ke seluruh staf dalam tujuh hari.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 0–1: sikap, wewenang, inventaris',
                    'Rapat 45 menit yang menghasilkan pemilik, bukan notulen “akan dikaji”.',
                    16,
                    [
                        $this->heading('Tahap 0–1: sikap, wewenang, inventaris', 1),
                        $this->paragraph('Tahap 0 adalah sikap. NEX menyatakan: AI akan dipakai sebagai alat bantu manusia di kerja kantor dan kajian, bukan pengganti manajer proyek, dan tidak akan mengunggah data stasiun, SPBU, atau instansi ke model publik. Sikap tanpa pemilik adalah slogan.'),
                        $this->paragraph('Tahap 1 adalah fondasi. Inventaris tiga daftar, bukan sepuluh satuan tugas. Pertama: di mana staf sudah memakai model generatif (HP, draf proposal, ringkasan rapat). Kedua: sistem mana yang menyimpan data perusahaan (nex-project, pantau armada, email, arsip kontrak). Ketiga: kontrak vendor yang sudah jalan — termasuk yang “hanya uji coba” di satu desk.'),
                        $this->numbered([
                            'Tulis lima keputusan di lembar pimpinan: prioritas tahun ini, data terlarang, penandatangan, kebijakan desk minimum, apa yang tidak dimulai minggu pertama.',
                            'Tetapkan pemilik: direksi untuk kebijakan perusahaan; manajer proyek untuk lapangan; kantor untuk desk nex-project; pengawas untuk kontrak yang melewati ambang.',
                            'Larangan sementara yang jujur: tidak mengunggah arsip pelanggan institusi, tidak mewajibkan satu merek, tidak men-deploy agen hidup di jaringan lapangan.',
                        ]),
                        $this->paragraph('Inventaris tidak butuh konsultan. Ia butuh kepala desk dan manajer proyek yang menjawab tiga pertanyaan yang sama. Kalau jawaban “tidak tahu”, itu temuan. Bukan aib yang disembunyikan sampai vendor datang.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 2: pilot kantor, bukan PLTS otomatis',
                    'nex-project boleh mulai. Komisioning otomatis tidak. Satu use case, satu produk.',
                    16,
                    [
                        $this->heading('Tahap 2: pilot kantor, bukan PLTS otomatis', 1),
                        $this->paragraph('nex-project sudah hidup di project.energimasadepan.com: pipeline, hunting, solar, hari ini. Itu tempat wajar untuk pilot kantor: data kerja sudah ada, manusia sudah punya rutinitas, wewenang sudah ada di desk. Jika hanya “tim IT” yang punya kebijakan, seluruh lapangan tetap diam. Diam di data stasiun lebih berbahaya daripada gagap di demo.'),
                        $this->paragraph('Pilih satu use case kantor. Dua kandidat yang masuk akal secara publik: bantuan draf di desk Hari Ini (manusia mengesahkan sebelum kirim ke instansi) atau ringkasan pipeline tender unclassified. Jangan pilih “AI yang menandatangani kajian teknis”. Jangan pilih keduanya plus sepuluh yang lain di kuartal yang sama.'),
                        $this->table(
                            ['Pilot', 'Yang diuji', 'Yang tidak diuji'],
                            [
                                ['nex-project desk / pipeline', 'Draf, pengingat, ringkasan board — manusia menandatangani ke instansi', 'Melatih model dari arsip tender; kirim otomatis ke pelanggan'],
                                ['Situs simulasi ROI', 'Kajian awal dari tagihan yang calon sendiri masukkan', 'Menyambungkan model publik ke data armada terpasang'],
                            ],
                        ),
                        $this->paragraph('Pilot punya akhir. Satu atau dua kuartal, lalu evaluasi tertulis: apa yang staf lakukan, apa yang manajer sanggup periksa, apa yang pecah. Tanpa akhir, pilot adalah pengadaan yang malu disebut pengadaan. NIST MEASURE: uji di dalam use case yang didefinisikan — bukan “nanti kita lihat”.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 3–4: perluasan produk dan kelembagaan',
                    'Dari satu desk ke lapangan, lalu ke audit tahunan. Bukan poster di lobi kantor.',
                    16,
                    [
                        $this->heading('Tahap 3–4: perluasan produk dan kelembagaan', 1),
                        $this->paragraph('Tahap 3 menuliskan apa yang berlaku ke lapangan PLTS. Kebijakan minimum: data lokasi institusi tidak ke model publik; draf kajian boleh dibantu jika manusia mengesahkan; komisioning tetap manusia. Klausul vendor wajib: dasar pemrosesan, lokasi data, larangan melatih model publik dari data NEX, hak hapus, audit.'),
                        $this->paragraph('Kapasitas staf di tahap 3 adalah melek wewenang, bukan fine-tuning. Seluruh teknisi tidak perlu jadi insinyur model. Seluruh kepala desk perlu tahu kapan AI merusak janji ke instansi. OECD menuntut transparansi dan akuntabilitas; NEX menerjemahkannya ke pelatihan yang bisa dihadiri, bukan sertifikat vendor semalam.'),
                        $this->paragraph('Tahap 4 adalah kelembagaan. Ada pemilik yang membaca laporan tahunan: berapa insiden data, berapa kontrak vendor, apakah data terlarang lolos. NIST MANAGE: kemampuan melepas atau mematikan sistem yang berperilaku di luar niat. NEX tidak wajib meniru formulir NIST. NEX wajib punya orang yang bisa mematikan sambungan data armada ke vendor pada jam 02.00.'),
                        $this->paragraph('Kelembagaan juga berarti berhenti. Jika pilot nex-project menunjukkan data tender bocor ke prompt, kebijakan diperketat, bukan dilonggarkan karena vendor sudah dibayar. Instalasi PLTS yang tidak bisa dihentikan bukan adopsi. Itu ketergantungan.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionUseCase(): array
    {
        return [
            'title' => 'Use case yang andal di kantor NEX',
            'description' => 'Filter dulu. nex-project dan simulasi ROI lolos. Data institusi ke model publik tidak.',
            'lessons' => [
                $this->textLesson(
                    'Cara menemukan use case AI yang andal',
                    'Bukan daftar vendor. Empat saringan yang harus lolos sebelum rapat direksi.',
                    18,
                    [
                        $this->heading('Cara menemukan use case AI yang andal', 1),
                        $this->paragraph('NIST MAP menuntut konteks dan use case yang eksplisit. Tanpa saringan, setiap demo adalah “use case”. Saringan NEX di kursus ini hanya untuk kerja kantor dan kajian unclassified — desk, pipeline, simulasi, pelatihan. Yang gagal saringan bukan “belum modern”. Yang gagal saringan tidak masuk peta jalan tahun ini.'),
                        $this->diagram(
                            'Empat saringan use case kantor: kebutuhan, data, manusia, henti. Yang gagal tidak naik ke direksi.',
                            [
                                ['id' => 'butuh', 'label' => 'Kebutuhan', 'hint' => 'kerja'],
                                ['id' => 'data', 'label' => 'Data', 'hint' => 'boleh disebut'],
                                ['id' => 'manusia', 'label' => 'Manusia', 'hint' => 'tanda tangan'],
                                ['id' => 'henti', 'label' => 'Henti', 'hint' => 'bisa padam'],
                            ],
                            [
                                ['from' => 'butuh', 'to' => 'data'],
                                ['from' => 'data', 'to' => 'manusia'],
                                ['from' => 'manusia', 'to' => 'henti'],
                            ],
                            4,
                        ),
                        $this->numbered([
                            'Kebutuhan kerja: pekerjaan yang sudah ada — draf desk, pipeline, simulasi ROI, notulen — bukan pekerjaan yang diciptakan vendor.',
                            'Data: bisa disebut di rapat, tidak mencampur data pelanggan institusi yang belum ada dasar. Jika kamu tidak bisa menulis jenis datanya di lembar keputusan, itu bukan use case andal.',
                            'Manusia: staf atau direksi tetap menandatangani. Chatbot yang “langsung kirim kajian ke instansi” gagal saringan. UNESCO: pengawasan manusia yang nyata.',
                            'Henti: sistem bisa dimatikan tanpa merusak komisioning PLTS. NIST MANAGE. Jika vendor memegang satu-satunya salinan pipeline, itu bukan adopsi.',
                        ]),
                        $this->paragraph('Gunakan saringan pada setiap usulan. “AI untuk menandatangani kontrak institusi” gagal di manusia. “AI dilatih dari arsip stasiun” gagal di data. “AI untuk draf di desk nex-project, dengan manusia mengesahkan” bisa lolos ke tahap 2. Itu yang dimaksud andal: bisa dimulai, bisa diuji, bisa dihentikan.'),
                    ],
                ),
                $this->textLesson(
                    'Use case nex-project: desk, pipeline, solar',
                    'Produk kantor yang sudah jalan. Bukan lab. Bukan konsol academy.',
                    16,
                    [
                        $this->heading('Use case nex-project: desk, pipeline, solar', 1),
                        $this->paragraph('nex-project hidup di https://project.energimasadepan.com. Secara publik ia adalah produk kantor NEX: pipeline peluang dan tender, pelacakan situs solar, desk harian. Itu keunggulan yang jarang diakui slide vendor: kerja sudah ada, manusia sudah punya rutinitas, wewenang sudah ada di desk. Adopsi AI yang andal menempel pada nex-project sebagai sumber kerja kantor — bukan menggantinya diam-diam dengan model publik.'),
                        $this->diagram(
                            'nex-project sebagai sumber kerja: pipeline dan solar masuk desk. Manusia mengesahkan. Vendor tidak melatih model dari board ini.',
                            [
                                ['id' => 'pipe', 'label' => 'Pipeline', 'hint' => 'tender'],
                                ['id' => 'solar', 'label' => 'Solar', 'hint' => 'situs'],
                                ['id' => 'desk', 'label' => 'Desk', 'hint' => 'hari ini'],
                                ['id' => 'sahkan', 'label' => 'Manusia', 'hint' => 'tanda tangan'],
                            ],
                            [
                                ['from' => 'pipe', 'to' => 'desk'],
                                ['from' => 'solar', 'to' => 'desk'],
                                ['from' => 'desk', 'to' => 'sahkan'],
                            ],
                            4,
                        ),
                        $this->table(
                            ['Yang boleh diuji di tahap 2', 'Syarat', 'Yang dilarang'],
                            [
                                ['Bantuan draf di desk Hari Ini', 'Hanya data yang sudah boleh dilihat staf itu; manusia mengesahkan sebelum ke instansi', 'Ekspor board pipeline ke vendor untuk “melatih AI NEX”'],
                                ['Ringkasan pipeline unclassified', 'Status yang sudah ada di board; bukan lampiran tender lengkap ke model publik', 'Chatbot yang mengubah status tanpa jejak siapa yang bertanggung jawab'],
                                ['Pengingat dan aging', 'Jadwal yang sudah milik NEX; log siapa yang bertindak', 'Menyambungkan model publik ke seluruh arsip solar'],
                            ],
                        ),
                        $this->paragraph('nex-project milik NEX. Deliverable-nya adalah pekerjaan kantor, bukan akses produksi dari Lesson. AI yang sudah diinfus di desk adalah contoh adopsi-di-produk: alat bantu manusia di atas peta kerja yang sudah ada. Itu bukan alasan membuka runtime dari academy ini.'),
                    ],
                ),
                $this->textLesson(
                    'Use case situs publik: simulasi ROI dan kajian',
                    'Calon institusi memasukkan tagihannya sendiri. NEX tidak menyerahkan arsip armada.',
                    16,
                    [
                        $this->heading('Use case situs publik: simulasi ROI dan kajian', 1),
                        $this->paragraph('energimasadepan.com sudah menampilkan Simulasi ROI, Dampak armada, dan Konsultasi. Calon mengetik perkiraan tagihan, mendapat estimasi kapasitas dan payback, lalu WhatsApp “Halo NEX”. Itu decision-support yang sudah diinfus ke produk publik. Ia lolos saringan kebutuhan: pekerjaan kajian teknis langkah 02 sudah ada sebelum ada “AI enterprise”.'),
                        $this->table(
                            ['Use case publik', 'Data yang disentuh', 'Saringan yang harus lolos'],
                            [
                                ['Simulasi ROI', 'Tagihan yang calon sendiri masukkan; bukan rekening PLN arsip NEX', 'Hasil adalah estimasi, bukan kontrak; manusia NEX mengkaji ulang'],
                                ['Dampak armada', 'Angka agregat yang NEX pilih tayangkan (kWp, MWh, lokasi sebagai bukti sektor)', 'Jangan buka deret per-inverter ke model publik'],
                                ['Konsultasi / kajian', 'Pesan yang calon tulis; identitas kontak', 'Tidak otomatis jadi surat penawaran yang “terkirim sendiri”'],
                            ],
                        ),
                        $this->paragraph('Langkah publik NEX — Survey, Kajian teknis, Instalasi, Komisioning, Pantau — memberi peta menempel yang jujur. AI andal di kajian (estimasi, draf). AI tidak andal di komisioning (uji kinerja dan serah terima tetap manusia). Pantau boleh dibantu jika angka armada tidak keluar ke vendor yang menolak klausul.'),
                        $this->paragraph('Pelatihan pimpinan di academy ini juga use case. OECD dan UNESCO menyebut kapasitas manusia. NEX yang mengirim direksi ke Course ini sedang mengadopsi kapasitas. NEX yang mewajibkan chatbot tanpa Lesson wewenang sedang mengadopsi tren.'),
                    ],
                ),
                $this->textLesson(
                    'Use case yang tidak dimulai: data pelanggan institusi ke model publik',
                    'Bukan karena “belum diajarkan”. Karena saringan dan hukum menolaknya di kerangka ini.',
                    14,
                    [
                        $this->heading('Use case yang tidak dimulai', 1),
                        $this->paragraph('Kursus ini ekstensif tentang kantor dan kajian. Ia sengaja pendek tentang otomasi lapangan. Yang berikut tidak masuk peta jalan NEX tahun ini, dan tidak masuk kuis sebagai “jawaban benar kalau modern”.'),
                        $this->bullets([
                            'Mengunggah daftar stasiun, SPBU, atau instansi pelanggan ke model publik untuk “melatih AI NEX”.',
                            'Agen yang menandatangani kajian teknis atau kontrak tanpa manusia.',
                            'Menyambungkan pantau armada ke model generatif publik “supaya pintar”.',
                            'Chatbot pelanggan yang mengaku mewakili NEX tanpa jejak staf.',
                            'Menjalankan agen hidup di jaringan lapangan “supaya kelihatan modern”.',
                        ]),
                        $this->paragraph('Menolak ini di minggu pertama bukan ketinggalan zaman. UNESCO do no harm dan UU 27/2022 sudah cukup. Kalau suatu hari NEX menulis kebijakan AI lapangan, itu dokumen lain, Course lain, atau bukan Course sama sekali. Bukan Lesson ini.'),
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
            'title' => 'Tiga meja: operasional, tata kelola, SDM',
            'description' => 'Manajer proyek, direksi, dan HR tidak duduk di kursi yang sama.',
            'lessons' => [
                $this->textLesson(
                    'Tiga meja: operasional, tata kelola, SDM',
                    'Mencampur tiga meja ini biasanya berarti membeli dulu, menyesal kemudian.',
                    18,
                    [
                        $this->heading('Tiga meja', 1),
                        $this->paragraph('Perusahaan yang mencampur operasional, tata kelola, dan SDM dalam satu keputusan biasanya membeli dulu, menyesal kemudian. Staf diminta “pakai AI” tanpa desain ulang desk. Direksi diminta tanda tangan tanpa peta data. Karyawan diminta “jalankan saja” tanpa wewenang menghentikan.'),
                        $this->diagram(
                            'Tiga meja NEX. Kebijakan desk bukan tiket helpdesk. Kontrak bukan silabus instalasi.',
                            [
                                ['id' => 'ops', 'label' => 'Operasional', 'hint' => 'lapangan'],
                                ['id' => 'kelola', 'label' => 'Tata kelola', 'hint' => 'wewenang'],
                                ['id' => 'sdm', 'label' => 'SDM', 'hint' => 'karyawan'],
                            ],
                            [],
                            3,
                        ),
                        $this->paragraph('Operasional: proses mana yang boleh dibantu AI di lapangan PLTS dan di desk nex-project, apa yang tetap harus ditinjau manusia, apa yang dilarang otomatis. Ini desain kerja, bukan tiket helpdesk.'),
                        $this->paragraph('Tata kelola: anggaran, kontrak vendor, privasi, reputasi institusi. Siapa yang boleh menandatangani data pelanggan ke pihak ketiga. Siapa yang boleh menolak vendor yang tidak mau klausul hapus data.'),
                        $this->paragraph('SDM: kebijakan karyawan memakai AI, penilaian kinerja, rekrutmen, dan apa yang terjadi pada peran yang berubah. Bukan pekerjaan kursus ini untuk men-deploy agen, dan bukan tombol di Lesson. Meja ketiga tetap harus disebut, supaya tidak ada yang berpura-pura Lesson ini adalah runbook.'),
                    ],
                ),
                $this->textLesson(
                    'Meja operasional: lapangan PLTS dan proses kantor',
                    'Diam di desk adalah kebijakan. Biasanya kebijakan terburuk.',
                    16,
                    [
                        $this->heading('Meja operasional', 1),
                        $this->paragraph('Lima langkah publik NEX adalah peta operasional: Survey, Kajian teknis, Instalasi, Komisioning, Pantau. NIST MAP: tulis konteks. Di meja ini, itu berarti setiap langkah menulis: apa yang boleh dibantu AI, siapa mengesahkan, di sistem mana (nex-project, situs, pantau).'),
                        $this->table(
                            ['Langkah NEX', 'Sikap minimum yang bisa ditulis manajer', 'Alasan'],
                            [
                                ['Survey / kajian', 'AI boleh merangkum input yang calon atau staf sudah sahkan; manusia menandatangani kajian', 'Janji ke instansi adalah tanggung jawab manusia'],
                                ['Instalasi', 'Jadwal dan logistik boleh dibantu; keselamatan atap tidak diotomatisasi', 'Aset pelanggan tetap menyala — copy situs NEX'],
                                ['Komisioning', 'Uji kinerja dan serah terima manusia', 'Dokumentasi lengkap bukan keluaran model yang tidak ditinjau'],
                                ['Pantau', 'Angka agregat boleh dibantu; deret per-situs tidak ke model publik', 'Data armada adalah data pelanggan'],
                                ['Desk nex-project', 'Draf dan pengingat boleh; status ke instansi wajib jejak manusia', 'Pipeline bukan chat tanpa pemilik'],
                            ],
                        ),
                        $this->paragraph('Kepala desk tidak wajib seragam dengan mandor lapangan. Keduanya wajib tertulis. Kebijakan perusahaan boleh lebih ketat dari minimum; tidak boleh lebih longgar pada data institusi jika direksi sudah mengunci.'),
                    ],
                ),
                $this->textLesson(
                    'Meja tata kelola: kontrak, vendor, reputasi institusi',
                    'Direksi menandatangani yang tidak bisa ditandatangani operator nex-project.',
                    16,
                    [
                        $this->heading('Meja tata kelola', 1),
                        $this->paragraph('Tata kelola NEX punya dua lapis yang tidak boleh disamakan: keputusan operasional desk dan keputusan yang menyentuh pelanggan institusi. Kontrak lisensi kecil mungkin cukup di kantor. Kontrak yang menyentuh data stasiun, SPBU, atau instansi naik ke pimpinan yang disebut di lembar keputusan.'),
                        $this->paragraph('Reputasi “energi hijau untuk instansi” adalah aset. Aset itu rapuh terhadap dua skandal: kebocoran data lokasi pelanggan, dan kajian yang “dibantu AI” tanpa pemilik. Tata kelola yang hanya menghitung hemat staf (karena “AI yang ngetik”) sedang menukar aset jangka panjang dengan efisiensi semu.'),
                        $this->numbered([
                            'Setiap pengadaan AI menulis: tujuan, data yang disentuh, dasar hukum PDP, lokasi pemrosesan, klausul larangan training, pemilik internal.',
                            'Vendor yang menolak klausul data tidak lolos, meski demonya bagus di desk.',
                            'Anggaran pelatihan staf (wewenang, bukan prompt) adalah belanja adopsi. Anggaran lisensi tanpa pelatihan adalah belanja tren.',
                            'Keputusan “berhenti berlangganan” harus bisa diambil tanpa kehilangan riwayat pipeline. Ketergantungan vendor adalah risiko yang disebut di Lesson risiko.',
                        ]),
                        $this->paragraph('Keputusan yang mencampur “wajib pakai merek X di semua proyek” dengan “disiplin lapangan” sedang mencampur meja. Pisahkan: pemakaian di operasional; kontrak di tata kelola; runbook di meja yang bukan Lesson ini.'),
                    ],
                ),
                $this->textLesson(
                    'Meja SDM: karyawan dan shadow AI',
                    'HP staf yang sudah memakai model publik adalah kebijakan, bukan gosip.',
                    12,
                    [
                        $this->heading('Meja SDM', 1),
                        $this->paragraph('Shadow AI: staf sudah memakai tools pribadi di data perusahaan sebelum ada kebijakan. Diam adalah kebijakan terburuk. OECD fairness dan UNESCO do no harm relevan di sini dalam bentuk sederhana: model publik tidak mengenal konteks institusi pelanggan NEX, dan bisa mengarang angka kWp, nama instansi, atau klausul kontrak.'),
                        $this->paragraph('Kebijakan karyawan yang sanggup ditulis minggu ini: boleh, wajib deklarasi, atau dilarang. Contoh jujur: draf unclassified di desk boleh dengan deklarasi; data pelanggan institusi dilarang di model publik; foto instalasi yang berisi lokasi aset dilarang diunggah ke aplikasi konsumen.'),
                        $this->paragraph('Karyawan bukan musuh adopsi. Karyawan adalah orang yang akan menanggung keputusan yang tidak tertulis. Pelatihan di academy ini adalah untuk pimpinan; turunan ke staf adalah kebijakan yang bisa dibaca, bukan memo “pakai AI” di grup.'),
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
            'title' => 'Data, wewenang, dan hukum',
            'description' => 'UU 27/2022 PDP dan batas agent runtime — diterjemahkan ke proyek PLTS NEX.',
            'lessons' => [
                $this->textLesson(
                    'Data pelanggan institusi dan karyawan NEX',
                    'Siapa yang punya, siapa yang boleh meminjamkan ke vendor. Bukan keputusan fitur.',
                    16,
                    [
                        $this->heading('Data pelanggan institusi dan karyawan NEX', 1),
                        $this->paragraph('Setiap “AI untuk perusahaan” yang menyentuh lokasi stasiun, rekening, kontrak, pipeline, atau penilaian karyawan adalah keputusan data. Bukan keputusan fitur. NEX sebagai pengendali — dalam bahasa UU 27/2022 — menentukan tujuan dan kendali pemrosesan. Vendor yang “hanya memproses” tetap membawa risiko jika kontraknya bolong.'),
                        $this->bullets([
                            'Data pelanggan institusi — lokasi, kapasitas, rekening, kontak. Tidak keluar ke vendor tanpa dasar.',
                            'Data karyawan — penilaian, gaji, rekaman rapat. Bukan bahan latihan model tanpa persetujuan.',
                            'Rahasia dagang — proposal, harga, desain. Shadow AI di laptop staf adalah kebocoran yang sudah terjadi.',
                            'Pipeline nex-project — tender dan peluang. Bukan dataset publik.',
                        ]),
                        $this->paragraph('Direksi dan pengawas yang tidak bisa menyebut data mana yang keluar perusahaan, belum siap adopsi. Mereka baru siap demo. NIST MAP: peta data sebelum MAP risiko. “Sudah di cloud vendor” bukan peta.'),
                    ],
                ),
                $this->textLesson(
                    'UU 27/2022 PDP dan data proyek PLTS',
                    'Pengendali, prosesor, data spesifik, transfer, hak subjek — dalam bahasa rapat NEX.',
                    18,
                    [
                        $this->heading('UU 27/2022 PDP dan data proyek PLTS', 1),
                        $this->paragraph('Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (LN 2022/196) berlaku untuk pemrosesan data pribadi, termasuk oleh perusahaan energi. NEX memproses nama PIC instansi, telepon, email, dan sering data yang menunjuk lokasi aset. Dasar pemrosesan harus ada: persetujuan, kontrak, atau kepentingan sah yang diuji — bukan “calon sudah klik setuju di chatbot vendor”.'),
                        $this->diagram(
                            'Data institusi lahir di NEX. Keluar ke vendor hanya dengan dasar, tujuan, dan hak tarik.',
                            [
                                ['id' => 'instansi', 'label' => 'Instansi', 'hint' => 'pelanggan'],
                                ['id' => 'nex', 'label' => 'NEX', 'hint' => 'pengendali'],
                                ['id' => 'vendor', 'label' => 'Vendor', 'hint' => 'prosesor'],
                            ],
                            [
                                ['from' => 'instansi', 'to' => 'nex', 'label' => 'data'],
                                ['from' => 'nex', 'to' => 'vendor', 'label' => 'dasar'],
                            ],
                            3,
                        ),
                        $this->table(
                            ['Konsep UU PDP', 'Arti di NEX', 'Yang ditanyakan di rapat'],
                            [
                                ['Pengendali', 'NEX menentukan tujuan pemrosesan', 'Siapa pejabat pengendali untuk nex-project dan pantau?'],
                                ['Prosesor', 'Vendor AI yang memproses atas instruksi NEX', 'Apakah kontrak melarang pakai data untuk latih model publik?'],
                                ['Data yang menunjuk aset', 'Lokasi stasiun, SPBU, instansi', 'Mengapa tools publik bukan tempatnya?'],
                                ['Transfer', 'Server vendor di luar negeri', 'Di negara mana, dengan jaminan apa?'],
                                ['Hak subjek', 'Akses, perbaiki, hapus, tarik persetujuan', 'Bagaimana PIC instansi menarik data jika proyek batal?'],
                            ],
                        ),
                        $this->paragraph('Pemrosesan berisiko tinggi — profil otomatis, transfer, data lokasi — mendorong penilaian dampak. NEX tidak perlu menunggu “peraturan pelaksana lengkap” untuk menahan unggahan arsip proyek ke model publik. Prinsip UU PDP sudah cukup untuk berkata tidak.'),
                        $this->paragraph('UU ITE (11/2008 beserta perubahannya) tetap relevan untuk sistem elektronik. Ia tidak menggantikan PDP. Dua-duanya disebut di klausul vendor, bukan salah satu.'),
                    ],
                ),
                $this->textLesson(
                    'Transfer ke vendor, rekaman rapat, kontrak',
                    'Tiga saluran yang paling sering “lolos” karena terasa operasional, bukan kebijakan.',
                    16,
                    [
                        $this->heading('Transfer ke vendor, rekaman rapat, kontrak', 1),
                        $this->paragraph('Tiga saluran NEX yang paling sering lolos dari rapat karena terasa teknis. Pertama: staf mengunggah rekaman rapat kajian ke alat ringkasan otomatis. Rekaman berisi angka CAPEX, nama instansi, kadang peta atap. Itu transfer. Kedua: “AI di desk” yang menyedot teks pipeline ke server vendor. Ketiga: ide menyambungkan pantau armada agar chatbot “tahu status situs”. Yang ketiga adalah proyek data, bukan fitur chat.'),
                        $this->paragraph('Lokasi server penting. Banyak model generatif diproses di luar Indonesia. UU 27/2022 mengatur transfer data pribadi ke luar wilayah hukum Indonesia. “Kebijakan privasi vendor” bukan dasar hukum NEX. Dasar NEX adalah keputusan pengendali plus jaminan yang bisa diaudit.'),
                        $this->bullets([
                            'Rekaman: default jangan unggah ke tools publik. Jika dipakai internal, sebut tujuan, retensi, dan siapa yang akses.',
                            'nex-project: matikan fitur yang mengirim isi board ke pihak ketiga, sampai klausul selesai.',
                            'Pantau armada: tidak disambung ke model generatif pada tahap 0–2.',
                            'Kontrak: agen pembalas otomatis ke instansi adalah pemrosesan data, bukan “efisiensi humas”.',
                        ]),
                        $this->paragraph('Contoh rapat yang jujur: “Kuartal ini NEX tidak menyambungkan pantau ke chatbot, dan rekaman kajian tidak boleh diunggah ke model publik.” Itu keputusan data. Bisa ditulis hari ini, tanpa menunggu tender tools.'),
                    ],
                ),
                $this->textLesson(
                    'Agent AI runtime: batas academy',
                    'Yang sudah diinfus di nex-project diajarkan sebagai contoh produk, bukan sebagai tombol Lesson.',
                    16,
                    [
                        $this->heading('Agent AI runtime: batas academy', 1),
                        $this->paragraph('NEX sudah menginfus AI di produk kantor nex-project — termasuk agen kantor di desk Hari Ini. Itu fakta adopsi-di-produk yang boleh disebut sebagai contoh. Itu bukan bukti bahwa Lesson ini adalah konsol. ADR academy: Lesson bukan lab.'),
                        $this->diagram(
                            'Academy menjelaskan wewenang. Runtime nex-project bukan tombol di Lesson. Lulus kuis tidak membuka Course terbatas.',
                            [
                                ['id' => 'academy', 'label' => 'Academy', 'hint' => 'Lesson'],
                                ['id' => 'wewenang', 'label' => 'Wewenang', 'hint' => 'rapat'],
                                ['id' => 'produk', 'label' => 'nex-project', 'hint' => 'desk'],
                                ['id' => 'runtime', 'label' => 'Runtime', 'hint' => 'bukan Lesson'],
                            ],
                            [
                                ['from' => 'academy', 'to' => 'wewenang'],
                                ['from' => 'wewenang', 'to' => 'produk', 'label' => 'keputusan'],
                                ['from' => 'produk', 'to' => 'runtime', 'label' => 'bukan di sini'],
                            ],
                            4,
                        ),
                        $this->paragraph('Yang boleh kamu bawa pulang: nex-project dan simulasi ROI adalah produk yang sudah ada; AI boleh menempel jika saringan use case lolos; runtime agen adalah meja operasional yang tidak dikerjakan tutor. Yang tidak boleh: mengira lulus kuis memberi akses produksi, mengunggah board ke model publik, atau membuka desktop agen dari halaman Lesson.'),
                        $this->quote('Adopsi yang dewasa memisahkan belajar dan mengoperasikan. Kamu belajar di sini. Mengoperasikan — jika NEX memutuskan — di luar sini, dengan pemilik yang sama yang menandatangani lembar keputusan.'),
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
            'title' => 'Contoh dan deliverable NEX',
            'description' => 'Yang sudah dikerjakan sebagai produk, plus kertas yang bisa dibawa ke rapat.',
            'lessons' => [
                $this->textLesson(
                    'Yang sudah dikerjakan di NEX: nex-project',
                    'Produk kantor yang sudah hidup. Deliverable: jangan ganti fondasi dengan demo.',
                    16,
                    [
                        $this->heading('Yang sudah dikerjakan di NEX: nex-project', 1),
                        $this->paragraph('NEX sudah punya nex-project di https://project.energimasadepan.com: desk, pipeline, solar. Itu deliverable yang sudah ada sebelum kursus ini ditulis. Peta jalan NEX tidak memulai dari nol. Ia memulai dari produk yang manusia sudah pakai. AI yang diinfus di desk adalah contoh adopsi-di-produk — manusia tetap pemilik status dan kalimat ke instansi.'),
                        $this->paragraph('Situs publik https://energimasadepan.com sudah menginfus decision-support: simulasi ROI, dampak armada, konsultasi. Langkah Pantau di proses publik adalah tempat pemantauan armada. Dua permukaan itu — kantor dan situs — adalah bukti NEX tidak menunggu vendor “AI enterprise” untuk mulai. Mereka sudah mulai di produk sendiri.'),
                        $this->table(
                            ['Deliverable NEX yang sudah ada', 'Arti untuk peta jalan', 'Yang bukan deliverable kursus ini'],
                            [
                                ['nex-project sebagai desk kantor', 'Pilot tahap 2 punya tempat: draf, pipeline, solar', 'Akses produksi ke board dari Lesson'],
                                ['Simulasi ROI di situs publik', 'Use case kajian yang calon isi sendiri', 'Agen yang menandatangani kontrak'],
                                ['Proses lima langkah + Pantau', 'AI menempel di kajian dan pantau, bukan komisioning', 'Runbook inverter di academy'],
                                ['Rantai direksi–proyek–desk', 'Lembar keputusan punya pemilik nyata', 'Nama pejabat intern yang dikarang'],
                            ],
                        ),
                        $this->paragraph('Bawa tabel ini ke rapat NEX. Tanya: use case mana yang sudah lolos saringan, dan siapa yang menandatangani jika angka kajian salah. Itu rapat adopsi. Rapat merek tools bisa menunggu di luar.'),
                    ],
                ),
                $this->documentLesson(
                    'Lembar keputusan pimpinan NEX',
                    'Satu halaman untuk rapat direksi dan pengawas. Bukan checklist belanja tools.',
                    12,
                    [
                        $this->heading('Lembar keputusan pimpinan NEX', 1),
                        $this->paragraph('Unduh PDF ini sebelum rapat. Ini kerangka keputusan, bukan SK yang sudah berlaku, dan bukan organigram intern yang dikarang. Tiap baris butuh pemilik: direksi, manajer proyek, kantor, atau pengawas. Kalau rapat berakhir tanpa pemilik, NEX belum adopsi. NEX baru menonton slide.'),
                        $this->paragraph('Lembar ini merangkum tahap 0–1 peta jalan: satu prioritas tahun ini, data yang dilarang keluar, penandatangan, kebijakan desk minimum, dan apa yang tidak dimulai minggu pertama. Isi contohnya memakai nama publik NEX agar rapat tidak abstrak. Ganti isinya di rapat sungguhan.'),
                    ],
                    [
                        'filename' => 'lembar-keputusan-pimpinan-nex.pdf',
                        'title' => 'Lembar Keputusan Pimpinan NEX — Adopsi AI',
                        'paragraphs' => [
                            'Kursus: AI untuk Korporat. Open Course. Bukan lab, bukan konsol, bukan SK resmi NEX.',
                            'Kasus: NEX Energi Masa Depan (https://energimasadepan.com), PLTS atap untuk operasional institusi; nex-project (https://project.energimasadepan.com) sebagai produk kantor.',
                            'Sifat dokumen: kerangka untuk rapat pimpinan. Bukan kebijakan yang sudah diundangkan.',
                            '1. Prioritas tahun ini (sebut satu): misalnya kebijakan desk minimum di nex-project, BUKAN lisensi chatbot untuk seluruh lapangan.',
                            '2. Data yang dilarang keluar ke vendor tanpa dasar UU 27/2022 PDP: lokasi stasiun/SPBU/instansi, rekening, arsip kontrak, board pipeline, penilaian karyawan. Sebut yang boleh dengan klausul (jika ada).',
                            '3. Siapa menandatangani: kebijakan perusahaan = direksi; kontrak di atas ambang = pengawas; pilot desk = kepala kantor / pemilik nex-project.',
                            '4. Kebijakan karyawan memakai AI: data institusi dilarang di model publik; draf unclassified boleh dengan deklarasi; diam = tidak diterima.',
                            '5. Apa yang tidak dimulai minggu pertama: agen hidup di jaringan lapangan, unggah arsip pelanggan institusi, wajib satu merek ke seluruh staf, sambung pantau ke model generatif.',
                            'Rujukan rapat: UNESCO Recommendation on the Ethics of AI (2021); OECD AI Principles; NIST AI RMF 1.0; UU 27/2022 PDP; UU ITE; SE Menkominfo 9/2023.',
                            'Adopsi: keputusan wewenang, bukan pembelian chatbot. EnterLMS: academy. Lulus kursus ini tidak men-deploy apa pun dan tidak membuka Course terbatas.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Klausul data vendor untuk proyek PLTS',
                    'Yang wajib ada di kontrak sebelum lisensi. Bukan nama vendor yang dikarang.',
                    12,
                    [
                        $this->heading('Klausul data vendor untuk proyek PLTS', 1),
                        $this->paragraph('Lesson ini tidak menunjuk merek. Ia menunjuk klausul. NEX yang sudah punya situs, nex-project, dan pantau akan ditawari “AI add-on”. Add-on itu adalah pemrosesan data. UU 27/2022 menuntut tujuan, dasar, dan kendali. PDF berisi daftar klausul yang dibawa ke pengadaan dan ke pengawas jika ambang kontrak terlampaui.'),
                        $this->paragraph('Vendor yang menolak klausul larangan training, lokasi data, dan hak hapus tidak lolos — meski demonya menolong desk merangkum pipeline. Ringkasan semu lebih murah daripada insiden PDP di pelanggan institusi.'),
                    ],
                    [
                        'filename' => 'klausul-data-vendor-proyek-plts-nex.pdf',
                        'title' => 'Klausul Data Vendor Proyek PLTS — Kerangka NEX',
                        'paragraphs' => [
                            'Sifat: daftar periksa kontrak, bukan nama vendor, bukan kontrak yang sudah ditandatangani NEX.',
                            'Dasar: UU 27/2022 PDP; UU ITE beserta perubahannya; UNESCO Recommendation on the Ethics of AI (2021) soal privasi dan pengawasan manusia; NIST AI RMF 1.0 untuk MAP/MEASURE.',
                            'Para pihak: NEX (PT NEX sebagaimana footer energimasadepan.com) sebagai pengendali; vendor sebagai prosesor kecuali terbukti menentukan tujuan sendiri (maka tinjau ulang seluruh hubungan).',
                            'Tujuan terbatas: sebut fungsi (misalnya umpan balik draf desk), bukan “meningkatkan layanan AI vendor”.',
                            'Data minimum: sebut bidang. Tolak paket yang menyedot seluruh board nex-project atau pantau armada “supaya cerdas”.',
                            'Larangan training: data NEX dan data pelanggan institusi tidak boleh dipakai melatih model publik atau model milik vendor untuk klien lain.',
                            'Lokasi pemrosesan: sebut negara. Transfer keluar Indonesia hanya dengan jaminan yang disetujui pengendali.',
                            'Subprosesor: daftar, kabari jika berubah, hak NEX menolak.',
                            'Hak subjek: mekanisme akses, koreksi, hapus, dan portabilitas dalam batas waktu yang tertulis. Termasuk PIC instansi jika proyek batal.',
                            'Insiden: kabari NEX dalam waktu yang tertulis; NEX yang bicara ke pelanggan institusi, bukan vendor yang bicara ke media.',
                            'Keluar kontrak: ekspor data, hapus salinan vendor, kunci enkripsi dikembalikan. Jika tidak bisa, jangan masuk.',
                            'Audit: hak pemeriksaan atau laporan independen. Tanpa audit, klausul hanya pamflet.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Peta jalan 24 bulan NEX',
                    'Kalender yang bisa ditempel di rapat. Bukan Gantt fiktif dengan nama pejabat intern.',
                    14,
                    [
                        $this->heading('Peta jalan 24 bulan NEX', 1),
                        $this->paragraph('Empat tahap sebelumnya butuh kalender. PDF ini memecah 24 bulan menjadi fondasi, dua kuartal pilot, perluasan, dan kelembagaan. Nama unit memakai sebutan publik (direksi, proyek, kantor, pengawas, nex-project). Tidak ada nama pejabat intern yang dikarang.'),
                        $this->diagram(
                            'Dua puluh empat bulan: fondasi, pilot nex-project, perluasan lapangan, lalu audit kelembagaan.',
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
                        $this->paragraph('Geser bulan jika rapat direksi tidak bisa bulan ini. Jangan geser urutan: lisensi massal sebelum fondasi, atau runtime agen sebelum saringan use case.'),
                    ],
                    [
                        'filename' => 'peta-jalan-24-bulan-nex.pdf',
                        'title' => 'Peta Jalan 24 Bulan Adopsi AI NEX',
                        'paragraphs' => [
                            'Sifat: kerangka kalender untuk rapat. Bukan keputusan direksi yang sudah diundangkan.',
                            'Bulan 1–3: rapat sikap direksi+pengawas; lembar keputusan terisi pemilik; moratorium agen hidup di lapangan, unggah arsip institusi, wajib merek.',
                            'Bulan 2–6: inventaris shadow AI di desk dan HP staf, peta sistem nex-project / pantau / email, daftar kontrak yang sudah ada. Kebijakan desk minimum diedarkan ke kantor dan proyek.',
                            'Bulan 4–6: pelatihan wewenang untuk kepala desk dan manajer proyek (saringan use case, PDP, UNESCO sebagai bahan), bukan fine-tuning.',
                            'Bulan 7–18: satu pilot kantor — desk nex-project atau simulasi ROI, bukan keduanya plus otomasi komisioning. Evaluasi tertulis tiap akhir kuartal.',
                            'Bulan 13–18: klausul vendor wajib pada perpanjangan tools. Matikan add-on yang menolak larangan training.',
                            'Bulan 13–24: perluasan ke lapangan berdasarkan bukti pilot. Tidak menyambungkan pantau ke model publik sebelum saringan lolos.',
                            'Bulan 19–24: audit tahunan (insiden data, ketergantungan vendor, shadow AI). Ethical impact assessment ala UNESCO 2021 dalam bentuk yang sanggup dijalankan direksi, bukan formulir impor.',
                            'Indikator jujur tahun ke-2: satu use case kantor tertulis; nol sambungan data institusi ke model publik; staf tahu data mana yang keluar; runtime tetap di luar academy.',
                            'Bukan indikator: jumlah lisensi, jumlah prompt, atau “sudah seperti Big Tech”.',
                            'Rujukan: UNESCO Ethics of AI 2021; OECD AI Principles; NIST AI RMF 1.0; UU 27/2022; UU ITE; SE Menkominfo 9/2023.',
                        ],
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
                    'Risiko yang pimpinan harus sebutkan',
                    'Data institusi, tenaga kerja, vendor, shadow AI. Menyembunyikan risiko lebih mahal.',
                    16,
                    [
                        $this->heading('Risiko yang pimpinan harus sebutkan', 1),
                        $this->paragraph('Menyebut risiko bukan menolak AI. Menyembunyikan risiko adalah yang membuat pengawas kaget di tahun kedua. UNESCO Recommendation on the Ethics of AI (2021) menempatkan do no harm dan proporsionalitas di depan manfaat yang masih spekulatif.'),
                        $this->diagram(
                            'Empat risiko NEX yang wajib disebut sebelum tanda tangan, plus siapa yang menanggung.',
                            [
                                ['id' => 'data', 'label' => 'Data', 'hint' => 'institusi'],
                                ['id' => 'vendor', 'label' => 'Vendor', 'hint' => 'gembok'],
                                ['id' => 'shadow', 'label' => 'Shadow AI', 'hint' => 'HP staf'],
                                ['id' => 'beban', 'label' => 'Beban staf', 'hint' => 'waktu'],
                            ],
                            [],
                            4,
                        ),
                        $this->bullets([
                            'Data pelanggan institusi — lokasi stasiun, SPBU, instansi yang tidak bisa ditarik dari vendor. UU 27/2022.',
                            'Ketergantungan vendor — NEX tidak bisa pindah tanpa kehilangan riwayat pipeline atau kajian.',
                            'Shadow AI — staf sudah memakai tools pribadi di data perusahaan sebelum ada kebijakan.',
                            'Tenaga kerja — “pakai AI” tanpa kebijakan SDM adalah PHK tersembunyi atau kerja tambahan tanpa nama.',
                            'Reputasi — kajian yang salah angka ke instansi merusak janji Energi Masa Depan lebih cepat daripada hemat ketik.',
                        ]),
                        $this->paragraph('Tiap risiko butuh wewenang. Data: pengendali di direksi, dengan pengawas pada kontrak besar. Vendor: pengadaan plus pimpinan. Shadow AI: kantor dan kebijakan perangkat. Beban staf: manajer, bukan memo yang hanya turun. Reputasi: direksi yang mengucapkan NEX di publik — jangan serahkan ke vendor.'),
                    ],
                ),
                $this->textLesson(
                    'Mulai dari rapat, bukan dari tools',
                    'Agenda 45 menit untuk direksi dan pengawas. Lima keputusan, bukan sepuluh demo.',
                    14,
                    [
                        $this->heading('Mulai dari rapat, bukan dari tools', 1),
                        $this->paragraph('Undangan rapat yang hanya berisi demo vendor akan menghasilkan pembelian. Undangan yang berisi lima pertanyaan di lembar keputusan NEX akan menghasilkan sikap. Bawa PDF Lesson deliverable, bukan brosur.'),
                        $this->diagram(
                            'Empat puluh lima menit: prioritas, data terlarang, use case, pemilik. Tools menunggu di luar.',
                            [
                                ['id' => 'prioritas', 'label' => 'Prioritas', 'hint' => '10 mnt'],
                                ['id' => 'data', 'label' => 'Data', 'hint' => '15 mnt'],
                                ['id' => 'usecase', 'label' => 'Use case', 'hint' => '10 mnt'],
                                ['id' => 'pemilik', 'label' => 'Pemilik', 'hint' => '10 mnt'],
                            ],
                            [
                                ['from' => 'prioritas', 'to' => 'data'],
                                ['from' => 'data', 'to' => 'usecase'],
                                ['from' => 'usecase', 'to' => 'pemilik'],
                            ],
                            4,
                        ),
                        $this->heading('Agenda singkat'),
                        $this->bullets([
                            'Sepuluh menit: satu prioritas tahun ini (bukan sepuluh). Contoh jujur: kebijakan desk nex-project, bukan “AI enterprise”.',
                            'Lima belas menit: data yang dilarang keluar — lokasi institusi, rekening, board pipeline, rekaman kajian.',
                            'Sepuluh menit: satu use case yang lolos saringan, atau keputusan belum ada yang lolos.',
                            'Sepuluh menit: siapa pemilik keputusan lanjutan (direksi, proyek, kantor, pengawas).',
                        ]),
                        $this->paragraph('Kalau rapat berakhir tanpa pemilik, NEX belum adopsi. NEX baru menonton slide. Vendor menunggu di luar sampai lima keputusan itu tertulis.'),
                    ],
                ),
                $this->textLesson(
                    'Apa yang tidak dimulai minggu pertama',
                    'Daftar yang menahan FOMO tanpa menolak belajar. NEX tetap boleh kuliah di academy ini hari ini.',
                    12,
                    [
                        $this->heading('Apa yang tidak dimulai minggu pertama', 1),
                        $this->bullets([
                            'Menjalankan agen hidup di jaringan lapangan PLTS “supaya kelihatan modern”.',
                            'Mengunggah arsip pelanggan institusi atau board nex-project ke vendor untuk “melatih AI NEX”.',
                            'Mewajibkan seluruh staf memakai satu merek dalam tujuh hari.',
                            'Menyambungkan pantau armada ke model generatif publik.',
                            'Membuka konsol runtime di dalam Lesson atau LMS.',
                            'Membiarkan chatbot menandatangani kajian ke instansi.',
                        ]),
                        $this->paragraph('Belajar di academy ini boleh dimulai hari ini. Mengoperasikan agen hidup tidak. Tutor di Lesson ini akan menolak membuka konsol, karena Lesson bukan lab.'),
                        $this->quote('Adopsi yang dewasa kelihatan lambat di minggu pertama, dan murah di tahun kedua. NEX sudah memasang PLTS tanpa chatbot. Jangan tukar itu dengan invoice.'),
                    ],
                ),
                $this->textLesson(
                    'Pustaka: UNESCO, OECD, NIST, UU PDP, NEX',
                    'Sumber bernama, bertanggal, bisa dicek. Bukan blog vendor, bukan SK fiktif.',
                    16,
                    [
                        $this->heading('Pustaka', 1),
                        $this->paragraph('Kerangka ini hanya sekuat sumbernya. Yang berikut bisa dibuka di rapat. Yang tidak ada di daftar — jumlah karyawan intern, SK direksi yang tidak terbit, spesifikasi produk yang tidak diumumkan — sengaja tidak dipakai.'),
                        $this->heading('NEX Energi Masa Depan (fakta publik)'),
                        $this->bullets([
                            'Situs resmi: https://energimasadepan.com — PLTS untuk operasional institusi; tagline Energi Masa Depan Indonesia; footer © 2026 PT NEX.',
                            'Produk panel: https://energimasadepan.com/product — NUSA Solar by PT IDN Solar Tech; TKDN 10010/SJ-IND.8/TKDN/9/2021; kontak PT Nusantara Energi Khatulistiwa; telepon 021 59577680.',
                            'Proses publik: Survey, Kajian teknis, Instalasi, Komisioning, Pantau.',
                            'Simulasi ROI, dampak armada, konsultasi: bagian kalkulator dan dampak di situs yang sama.',
                            'Sektor bukti: stasiun & depo, SPBU & energi, perkantoran, instansi (BUMN dan fasilitas publik) — angka lokasi di halaman situs.',
                            'nex-project: https://project.energimasadepan.com — produk kantor (pipeline, solar, desk). Contoh AI infus di produk, bukan konsol academy.',
                        ]),
                        $this->heading('Internasional'),
                        $this->bullets([
                            'UNESCO. Recommendation on the Ethics of Artificial Intelligence. 23 November 2021.',
                            'UNESCO. AI and education: Guidance for policy-makers. 2021 — kapasitas manusia, relevan untuk pelatihan staf.',
                            'OECD. OECD AI Principles. 2019, diperbarui 2024. Pertumbuhan inklusif, hak asasi, transparansi, ketahanan, akuntabilitas.',
                            'NIST. Artificial Intelligence Risk Management Framework (AI RMF 1.0). Januari 2023. GOVERN, MAP, MEASURE, MANAGE.',
                            'ISO/IEC 42001:2023 — sistem manajemen AI, rujukan pelengkap kelembagaan, bukan pengganti UU PDP.',
                        ]),
                        $this->heading('Indonesia'),
                        $this->bullets([
                            'Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.',
                            'Undang-Undang Informasi dan Transaksi Elektronik beserta perubahannya.',
                            'Surat Edaran Menteri Komunikasi dan Informatika Nomor 9 Tahun 2023 tentang Etika Kecerdasan Artifisial — arah, bukan pengganti keputusan direksi.',
                        ]),
                        $this->paragraph('Kalau suatu klaim di Lesson tidak tertaut ke salah satu sumber ini, perlakukan sebagai kerangka diskusi, bukan sebagai berita NEX. Academy ini menolak mengarang organigram dan menolak menjual merek. Tugasmu setelah lulus kuis: bawa lembar keputusan ke rapat, bukan membawa konsol ke jaringan kantor.'),
                    ],
                ),
            ],
        ];
    }
}
