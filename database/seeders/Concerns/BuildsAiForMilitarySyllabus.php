<?php

namespace Database\Seeders\Concerns;

/**
 * Syllabus for AI untuk Militer.
 *
 * Running case: TNI AD, with Korem 074/Warastratama and Korem 072/Pamungkas
 * as the staff-system examples. Public sources only. Lesson is not a lab.
 */
trait BuildsAiForMilitarySyllabus
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
            'title' => 'Konteks TNI AD dan mandat pimpinan',
            'description' => 'Satu angkatan, dua Korem sebagai kasus staf. Bukan slide vendor.',
            'lessons' => [
                $this->textLesson(
                    'Untuk siapa kursus ini',
                    'Pimpinan kesatuan yang harus mengambil sikap di rapat, bukan di server.',
                    16,
                    [
                        $this->heading('Untuk siapa kursus ini', 1),
                        $this->paragraph('Kursus ini untuk pimpinan TNI AD yang harus mengambil sikap tentang AI di kesatuan: Danrem, Kasrem, perwira staf, Dandim, dan personel yang akan memakai — atau dilarang memakai — alat di perangkat dinas. Kamu tidak perlu menulis kode. Kamu perlu bahasa yang sama sebelum ada yang membeli tools.'),
                        $this->paragraph('Ini Open Course, gratis, dalam Bahasa Indonesia. Kamu boleh enroll sendiri. Kasus berjalannya satu: Tentara Nasional Indonesia Angkatan Darat. Setiap Lesson memakai TNI AD. Dua kesatuan staf yang sudah punya sistem nyata dipakai sebagai contoh: Korem 074/Warastratama (Surakarta) dengan Sisfomil, dan Korem 072/Pamungkas dengan Sipamungkas. Kesatuan lain boleh membaca, tetapi kami tidak akan mengganti nama di tengah jalan menjadi “kesatuan X”.'),
                        $this->paragraph('Ini kerangka adopsi AI TNI AD yang komprehensif, bukan briefing 90 menit. Kamu akan berjalan dari konteks kesatuan, arti adopsi versus belanja, peta jalan empat tahap, cara menemukan use case yang andal, tiga meja (tugas, komando, personel), data dan hukum, contoh deliverable Korem 074 dan 072, lalu pustaka yang bisa dicek.'),
                        $this->heading('Empat kursi, satu meja'),
                        $this->bullets([
                            'Pimpinan kesatuan (Danrem / Dandim) — mandat, reputasi, dan tanggung jawab jangka panjang.',
                            'Kasrem / Kepala staf — koordinasi staf, beban kerja, apa yang boleh masuk ruang kerja.',
                            'Perwira staf — data, surat, Sisfomil, perintah harian.',
                            'Personel — prajurit dan staf yang memakai perangkat dinas.',
                        ]),
                        $this->diagram(
                            'Empat kursi TNI AD duduk di satu kerangka adopsi, bukan di empat proyek terpisah.',
                            [
                                ['id' => 'danrem', 'label' => 'Danrem', 'hint' => 'mandat'],
                                ['id' => 'kasrem', 'label' => 'Kasrem', 'hint' => 'koordinasi'],
                                ['id' => 'staf', 'label' => 'Staf', 'hint' => 'data'],
                                ['id' => 'personel', 'label' => 'Personel', 'hint' => 'perangkat'],
                            ],
                            [],
                            4,
                        ),
                        $this->paragraph('Kamu boleh enroll sendiri. Menyelesaikan kursus ini tidak membuka Course terbatas dan tidak memberi akses produksi. Kalau yang kamu cari adalah tombol untuk menjalankan agen di jaringan dinas, kamu di Course yang salah. Itu bukan Lesson.'),
                        $this->quote('Peta jalan yang dewasa kelihatan lambat di minggu pertama, dan murah di tahun kedua. NATO menyebutnya use case yang didefinisikan dulu, mesin kemudian.'),
                    ],
                    preview: true,
                ),
                $this->textLesson(
                    'TNI AD sebagai kasus berjalan',
                    'Fakta publik hierarki dan dua Korem staf. Bukan doktrin rahasia yang dikarang.',
                    18,
                    [
                        $this->heading('TNI AD sebagai kasus berjalan', 1),
                        $this->paragraph('Tentara Nasional Indonesia Angkatan Darat adalah salah satu angkatan di bawah TNI. Dasar hukum publik yang dipakai kursus ini: Undang-Undang Nomor 34 Tahun 2004 tentang Tentara Nasional Indonesia, dan Undang-Undang Nomor 3 Tahun 2002 tentang Pertahanan Negara. Hierarki staf yang relevan di sini — yang boleh diajarkan tanpa doktrin rahasia — adalah Kodam, Korem, Kodim, Koramil.'),
                        $this->paragraph('Dua kesatuan yang menjadi contoh staf: Korem 074/Warastratama di Surakarta, dan Korem 072/Pamungkas. Keduanya di wilayah Kodam IV/Diponegoro. Korem 074 sudah memakai Sisfomil sebagai sistem informasi staf/personel. Korem 072 memakai Sipamungkas (https://si.pamungkas.org) sebagai sistem staf kesatuan. Agent AI runtime yang pernah dijalankan di sisi operasional keduanya diajarkan di Lesson batas: itu bukan tombol di academy ini.'),
                        $this->paragraph('Fakta yang dipakai hanya yang terbit di kanal publik atau yang sudah disebut sebagai sistem staf (bukan intel, bukan senjata). Kami tidak mengarang orgas intern, jumlah personel, atau program senjata yang tidak diumumkan.'),
                        $this->table(
                            ['Fakta publik / sistem staf', 'Sumber yang bisa dicek', 'Implikasi untuk AI'],
                            [
                                ['TNI AD: Kodam → Korem → Kodim → Koramil', 'UU 34/2004; situs TNI AD', 'Wewenang tidak sama di setiap eselon; Dandim tidak menandatangani yang milik Danrem'],
                                ['Korem 074/Warastratama, Surakarta', 'Kanal publik Kodam IV / Korem 074', 'Contoh staf: Sisfomil untuk administrasi personel dan kantor'],
                                ['Korem 072/Pamungkas', 'Sipamungkas https://si.pamungkas.org', 'Contoh staf: sistem kesatuan untuk administrasi dan pemantauan kegiatan'],
                                ['Sisfomil = sistem informasi staf/personel, bukan senjata', 'Sistem yang sudah jalan di 074', 'Use case andal dimulai dari data yang sudah ada di staf, bukan dari demo targeting'],
                                ['Sipamungkas = sistem staf 072; Sisfomil tetap di 074', 'Pemisahan sistem antar-Korem', 'Satu kesatuan satu peta data. Jangan campur basis data personel'],
                            ],
                        ),
                        $this->paragraph('Kursus ini tidak mengajarkan intelijen, penembakan, atau targeting. Itu bukan “use case yang belum diajarkan”. Itu batas. NATO Principles of Responsible Use (2021, dikukuhkan ulang 2024) menuntut use case yang eksplisit. Use case staf yang andal untuk Korem adalah administrasi, rapat, surat, pelatihan, dan layanan kesatuan — dengan manusia yang tetap menandatangani.'),
                    ],
                ),
                $this->textLesson(
                    'Bukan kursus men-deploy agen',
                    'Adopsi bukan lab. Lesson bukan konsol. TNI AD bukan studi kelayakan vendor.',
                    12,
                    [
                        $this->heading('Bukan kursus men-deploy agen', 1),
                        $this->paragraph('EnterLMS adalah academy. Tutor di sini menjelaskan. Ia tidak men-deploy agen di jaringan Korem 074 atau 072, tidak membuka desktop runtime, dan tidak menyelesaikan Lesson untukmu.'),
                        $this->paragraph('Pengenalan Agen AI mengajarkan apa itu agen. Kursus membangun model mengajarkan satu kasus teknis. Kursus ini mengajarkan apa yang pimpinan TNI AD putuskan sebelum ada agen, chatbot, atau slide “AI militer” di ruang rapat Danrem.'),
                        $this->bullets([
                            'Tidak ada lab di Lesson.',
                            'Tidak ada akses produksi karena lulus kuis.',
                            'Restricted Course tentang operasi runtime tetap tersembunyi dari katalog ini.',
                            'Tidak ada data intern kesatuan yang dikarang agar terasa “konsultan”.',
                            'Tidak ada doktrin tempur, intel, atau senjata.',
                        ]),
                        $this->paragraph('Kalau suatu Korem suatu hari mengoperasikan agent AI runtime, itu di luar academy ini. Mandat kamu di sini: wewenang, data staf, use case yang andal, dan kapasitas pimpinan. NATO Revised AI Strategy (Juli 2024) memulai dari Principles of Responsible Use dan use case yang didefinisikan — bukan dari konsol.'),
                    ],
                ),
                $this->textLesson(
                    'Empat kursi: Danrem, Kasrem, staf, personel',
                    'Komandan menandatangani yang tidak bisa ditandatangani Kasi. Personel menanggung kebijakan diam.',
                    16,
                    [
                        $this->heading('Empat kursi, satu meja', 1),
                        $this->paragraph('TNI AD bukan startup. UU 34/2004 menempatkan TNI di bawah kepentingan pertahanan negara, dengan rantai komando. Danrem 074 bukan “tamu” di rapat AI. Danrem pemegang mandat kesatuan, reputasi, dan — pada kontrak yang menyentuh data personel dalam skala Korem — tanda tangan yang tidak bisa diwakilkan operator Sisfomil.'),
                        $this->paragraph('Kasrem menyusun koordinasi staf: apa yang berlaku di seksi, apa yang cukup di Kodim. Kasi boleh mengusulkan pilot absensi. Dandim tidak boleh diabaikan hanya karena “Makorem yang ngerti tools”: data personel Kodim adalah data kesatuan, bukan eksperimen IT.'),
                        $this->paragraph('Personel memegang perangkat dinas. Diam tentang shadow AI di HP dinas adalah kebijakan tersembunyi, dan biasanya kebijakan terburuk. Prajurit tidak menandatangani kontrak vendor, tetapi mereka yang menanggung kebocoran.'),
                        $this->diagram(
                            'Wewenang mengalir dari Danrem dan Kasrem ke staf, lalu ke perangkat personel. Jangan dibalik.',
                            [
                                ['id' => 'pimpinan', 'label' => 'Danrem', 'hint' => 'mandat'],
                                ['id' => 'koordinasi', 'label' => 'Kasrem', 'hint' => 'staf'],
                                ['id' => 'seksi', 'label' => 'Seksi', 'hint' => 'pilot'],
                                ['id' => 'dinas', 'label' => 'Perangkat', 'hint' => 'kebijakan'],
                            ],
                            [
                                ['from' => 'pimpinan', 'to' => 'koordinasi'],
                                ['from' => 'koordinasi', 'to' => 'seksi'],
                                ['from' => 'seksi', 'to' => 'dinas'],
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
            'description' => 'Membeli chatbot bukan kebijakan kesatuan. TNI AD mengadopsi wewenang.',
            'lessons' => [
                $this->textLesson(
                    'Apa artinya adopsi AI di kesatuan',
                    'Keputusan berulang: tugas staf, data, vendor, dan wewenang — di TNI AD, bukan di slide.',
                    16,
                    [
                        $this->heading('Apa artinya adopsi AI di kesatuan', 1),
                        $this->paragraph('Adopsi AI di TNI AD bukan satu proyek IT. Itu rangkaian keputusan: pekerjaan staf mana yang boleh dibantu; data siapa yang boleh masuk ke vendor; siapa yang menandatangani; dan kapan berhenti. NATO Principles of Responsible Use for AI in Defence (Oktober 2021; dikukuhkan dalam Revised AI Strategy Juli 2024) menempatkan lawfulness, responsibility and accountability, dan reliability — termasuk use case yang eksplisit — sebagai syarat, bukan hiasan.'),
                        $this->heading('Tiga pertanyaan yang selalu kembali'),
                        $this->bullets([
                            'Untuk siapa? Administrasi personel, rapat staf, pelatihan, atau layanan kesatuan — masing-masing beda risiko.',
                            'Dengan data apa? Daftar personel Sisfomil, surat dinas, notulen, peta kerja unclassified.',
                            'Siapa yang bertanggung jawab jika salah? Kasi, Kasrem, Danrem, atau pimpinan di atasnya.',
                        ]),
                        $this->paragraph('Kalau rapat hanya membahas merek tools, itu belum adopsi. Itu belanja. Kesatuan dengan mandat UU 34/2004 tidak diukur dari jumlah lisensi chatbot, melainkan dari apakah keputusan itu bisa dijelaskan ke rantai komando, ke hukum Indonesia, dan ke Principles of Responsible Use yang dipakai sebagai rujukan — bukan sebagai pengganti hukum TNI.'),
                        $this->paragraph('Adopsi yang jujur juga mencakup menolak. “Tahun ini Korem 074 tidak men-deploy agen, dan staf tidak mengunggah arsip personel ke model generatif publik” adalah keputusan adopsi. Diam bukan netral. Diam adalah izin tersembunyi.'),
                    ],
                ),
                $this->textLesson(
                    'Bukan beli chatbot lalu selesai',
                    'Chatbot, agen, dan perintah adalah tiga benda berbeda. Vendor sering menjualnya sebagai satu.',
                    14,
                    [
                        $this->heading('Bukan beli chatbot lalu selesai', 1),
                        $this->paragraph('Chatbot menjawab percakapan. Agen menerima tujuan, memakai alat, dan bekerja dalam loop. Perintah kesatuan memutuskan mana yang boleh masuk ke kerja staf TNI AD dan ke data personel. Tiga benda ini sering dijual sebagai satu paket “AI militer”.'),
                        $this->diagram(
                            'Chatbot menjawab. Agen bertindak. Perintah kesatuan yang memutuskan mana yang boleh masuk.',
                            [
                                ['id' => 'chat', 'label' => 'Chatbot', 'hint' => 'jawab'],
                                ['id' => 'agen', 'label' => 'Agen', 'hint' => 'loop'],
                                ['id' => 'perintah', 'label' => 'Perintah', 'hint' => 'wewenang'],
                            ],
                            [
                                ['from' => 'chat', 'to' => 'perintah', 'label' => 'boleh?'],
                                ['from' => 'agen', 'to' => 'perintah', 'label' => 'boleh?'],
                            ],
                            3,
                        ),
                        $this->paragraph('Contoh TNI AD yang sah sebagai diskusi rapat, bukan sebagai fakta rahasia: chatbot FAQ cuti di intranet berbeda risikonya dari agen yang menulis balasan otomatis ke surat personel, dan berbeda lagi dari model yang dilatih dengan arsip Sisfomil. Yang pertama mungkin layanan informasi. Yang kedua menyentuh data pribadi. Yang ketiga menyentuh UU 27/2022 sekaligus ketergantungan vendor.'),
                        $this->quote('Yang TNI AD adopsi bukan merek. Yang diadopsi adalah wewenang: siapa boleh memakai alat apa, pada data siapa, sampai kapan.'),
                    ],
                ),
                $this->textLesson(
                    'Tren, proyek IT, dan perintah: tiga benda',
                    'FOMO, pengadaan, dan perintah harian adalah tiga kalender berbeda. Jangan disatukan di satu undangan.',
                    16,
                    [
                        $this->heading('Tren, proyek IT, dan perintah', 1),
                        $this->paragraph('Tren: berita “AI perang”, demo vendor, staf yang sudah memakai model generatif di HP dinas. Itu nyata. Tekanan “supaya tidak ketinggalan NATO” akan masuk rapat. Tren bukan dasar perintah. NATO sendiri, dalam ringkasan Revised AI Strategy 2024, menuntut use case yang mematuhi Principles of Responsible Use — bukan lisensi massal.'),
                        $this->paragraph('Proyek IT: lisensi, integrasi Sisfomil, Sipamungkas, helpdesk. Itu pengadaan. Ada anggaran, ada vendor, ada SLA. Proyek IT yang dimulai sebelum kebijakan data dan peta wewenang adalah belanja yang mencari justifikasi.'),
                        $this->paragraph('Perintah: siapa boleh apa. DoD AI Ethical Principles (memorandum Menteri Pertahanan AS, 21 Februari 2020) memisahkan responsible, equitable, traceable, reliable, governable. TNI AD tidak menyalin DoD. TNI AD boleh membaca lima itu sebagai bahan, lalu menulis perintah sendiri dalam bahasa rantai komando.'),
                        $this->table(
                            ['Benda', 'Pertanyaan TNI AD', 'Pemilik yang wajar'],
                            [
                                ['Tren', 'Apa yang personel sudah lakukan diam-diam di perangkat dinas?', 'Kasrem + Kasi (inventaris)'],
                                ['Proyek IT', 'Alat apa yang masuk Sisfomil atau Sipamungkas, dengan data apa?', 'Danrem + unit infolahta, sepengetahuan staf jika kontrak besar'],
                                ['Perintah', 'Apa yang wajib, boleh dengan deklarasi, atau dilarang di perangkat dinas?', 'Danrem; Kodim boleh lebih ketat'],
                            ],
                        ),
                        $this->paragraph('Undangan rapat yang hanya berisi demo akan menghasilkan pembelian. Undangan yang memisahkan tiga kolom ini akan menghasilkan sikap. Kalau ketiga kolom itu dicampur, staf akan dituduh “menghambat modernisasi” ketika sebenarnya mereka menahan kontrak.'),
                    ],
                ),
                $this->textLesson(
                    'Mengapa kesatuan tidak copy-paste strategi NATO mentah-mentah',
                    'NATO, DoD, atau REAIM boleh dibaca. UU 34/2004 dan data Sisfomil tidak ikut tertyalin.',
                    14,
                    [
                        $this->heading('Mengapa kesatuan tidak copy-paste', 1),
                        $this->paragraph('Banyak negara sudah punya strategi AI pertahanan. NATO AI Strategy (Oktober 2021) dan revisinya (Juli 2024) adalah dokumen publik. DoD Responsible AI Strategy and Implementation Pathway (Juni 2022) juga. REAIM Blueprint for Action (Den Haag 2023, Seoul 2024) adalah inisiatif negara tentang AI di domain militer. Membaca bukan menyalin.'),
                        $this->paragraph('TNI AD terikat hukum Indonesia: UU 34/2004, UU 3/2002, UU 27/2022 PDP, Perpres 95/2018 tentang SPBE, dan Surat Edaran Menkominfo 9/2023 tentang Etika Kecerdasan Artifisial. SE bukan undang-undang; ia tidak menggantikan perintah Danrem, tetapi ia menandai arah negara. NATO PRU tidak menggantikan rantai komando TNI.'),
                        $this->paragraph('NATO Revised AI Strategy 2024 menyebut dampak AI pada tenaga kerja militer dan sipil: pelatihan ulang, keahlian, perubahan peran. Itu relevan untuk staf Korem. Itu tidak berarti Korem 074 harus meniru DIANA atau Innovation Fund. Itu berarti peta jalan TNI AD harus punya tahap kapasitas manusia, bukan hanya invoice GPU.'),
                        $this->paragraph('Baca strategi luar sebagai bahan. Tulis keputusan kesatuan sebagai pemilik. Lesson deliverable menyediakan draf untuk rapat, bukan terjemahan kebijakan orang lain. ICRC dan diskusi senjata otonom ada di pustaka sebagai batas: kursus ini tidak masuk ke sana.'),
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
            'description' => 'Sikap, fondasi, pilot staf, kelembagaan. TNI AD tidak melompat ke agen hidup.',
            'lessons' => [
                $this->textLesson(
                    'Peta jalan empat tahap TNI AD',
                    'Satu gambar untuk rapat Danrem dan Kasrem. Bukan sepuluh proyek paralel.',
                    18,
                    [
                        $this->heading('Peta jalan empat tahap TNI AD', 1),
                        $this->paragraph('Kerangka ini memakai empat tahap. Nama tahap boleh diubah di rapat. Urutannya jangan. Melompat ke “AI kesatuan” sebelum wewenang dan data tertulis adalah cara mahal untuk belajar di tahun kedua. NATO PRU Reliability menuntut use case yang eksplisit sebelum jaminan keamanan. Tanpa tahap, tidak ada use case — hanya demo.'),
                        $this->diagram(
                            'Peta jalan TNI AD: sikap dulu, fondasi, pilot staf, baru kelembagaan. Bukan tools dulu.',
                            [
                                ['id' => 'sikap', 'label' => 'Sikap', 'hint' => 'wewenang'],
                                ['id' => 'fondasi', 'label' => 'Fondasi', 'hint' => 'inventaris'],
                                ['id' => 'pilot', 'label' => 'Pilot', 'hint' => 'staf'],
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
                                ['0–1 Sikap & fondasi', 'Siapa menandatangani? Data apa yang dilarang keluar? Apa kebijakan perangkat dinas?', 'Lembar keputusan pimpinan kesatuan'],
                                ['2 Pilot', 'Satu use case staf di Korem 074 (Sisfomil) atau 072 (Sipamungkas): apa yang diuji 1–2 triwulan?', 'Filter use case + catatan evaluasi'],
                                ['3 Perluasan', 'Apa yang berlaku ke Kodim? Klausul vendor mana yang wajib?', 'Klausul data Sisfomil/Sipamungkas + pelatihan staf'],
                                ['4 Kelembagaan', 'Siapa mengaudit tahunan? Bagaimana henti darurat tanpa academy ini?', 'Peta jalan 24 bulan yang hidup, bukan poster'],
                            ],
                        ),
                        $this->paragraph('DoD RAI Strategy (2022) memecah adopsi menjadi jalur: tata kelola, trust, test/evaluation, tenaga kerja. Peta jalan TNI AD hanya menerjemahkan itu ke kalender Korem: wewenang dulu, data, satu use case staf, lalu kelembagaan. YBWSA di kampus adalah yayasan; di sini pemiliknya Danrem dan rantai ke Kodam.'),
                        $this->paragraph('Danrem tidak perlu menyetujui merek chatbot di tahap 0. Danrem perlu menyetujui bahwa data personel Sisfomil tidak keluar kesatuan tanpa dasar, dan bahwa Korem tidak akan mewajibkan satu merek ke seluruh Kodim dalam tujuh hari.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 0–1: sikap, wewenang, inventaris',
                    'Rapat 45 menit yang menghasilkan pemilik, bukan notulen “akan dikaji”.',
                    16,
                    [
                        $this->heading('Tahap 0–1: sikap, wewenang, inventaris', 1),
                        $this->paragraph('Tahap 0 adalah sikap. TNI AD di tingkat Korem menyatakan: AI akan dipakai sebagai alat bantu manusia di kerja staf, bukan pengganti komandan, dan tidak akan masuk intel, senjata, atau targeting dari academy ini. Sikap tanpa pemilik adalah slogan.'),
                        $this->paragraph('Tahap 1 adalah fondasi. Inventaris tiga daftar, bukan sepuluh satuan tugas. Pertama: di mana personel sudah memakai model generatif (HP dinas, draf surat, ringkasan rapat). Kedua: sistem mana yang menyimpan data kesatuan (Sisfomil di 074, Sipamungkas di 072, email, arsip surat). Ketiga: kontrak vendor yang sudah jalan — termasuk yang “hanya uji coba” di satu seksi.'),
                        $this->numbered([
                            'Tulis lima keputusan di lembar pimpinan: prioritas tahun ini, data terlarang, penandatangan, kebijakan perangkat dinas, apa yang tidak dimulai minggu pertama.',
                            'Tetapkan pemilik: Danrem untuk perintah kesatuan; Kasrem untuk koordinasi; Kasi untuk pilot seksi; Dandim untuk Kodim yang ikut kemudian.',
                            'Larangan sementara yang jujur: tidak mengunggah arsip personel, tidak mewajibkan satu merek, tidak men-deploy agen hidup, tidak menyentuh intel/senjata.',
                        ]),
                        $this->paragraph('Inventaris tidak butuh konsultan. Ia butuh seksi yang menjawab tiga pertanyaan yang sama, plus infolahta yang menyebut sistem. Kalau jawaban “tidak tahu”, itu temuan. Bukan aib yang disembunyikan sampai vendor datang.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 2: pilot staf Korem, bukan senjata',
                    'Sisfomil atau Sipamungkas boleh mulai. Targeting tidak. Satu use case, satu kesatuan.',
                    16,
                    [
                        $this->heading('Tahap 2: pilot staf Korem, bukan senjata', 1),
                        $this->paragraph('Korem 074 sudah punya Sisfomil. Korem 072 sudah punya Sipamungkas. Itu tempat wajar untuk pilot staf: data sudah ada, manusia sudah memakai sistem, use case bisa dibatasi. Jika hanya “tim IT” yang punya kebijakan, seluruh Kodim tetap diam. Diam di data personel lebih berbahaya daripada gagap di demo.'),
                        $this->paragraph('Pilih satu use case staf. Dua kandidat yang masuk akal secara publik: ringkasan rapat staf (notulen dengan manusia yang mengesahkan) atau laporan hitungan personel/absensi dari Sisfomil yang tetap dibaca perwira. Jangan pilih intel. Jangan pilih senjata. Jangan pilih keduanya plus sepuluh yang lain di triwulan yang sama.'),
                        $this->table(
                            ['Pilot', 'Yang diuji', 'Yang tidak diuji'],
                            [
                                ['Korem 074 / Sisfomil', 'Laporan staf: hitungan, absensi, draf ringkasan — manusia menandatangani', 'Melatih model dari basis data personel; intel; targeting'],
                                ['Korem 072 / Sipamungkas', 'Administrasi kesatuan dan pemantauan progres kegiatan unclassified', 'Menyambungkan agent runtime ke data 074; senjata; doktrin'],
                            ],
                        ),
                        $this->paragraph('Pilot punya akhir. Satu atau dua triwulan, lalu evaluasi tertulis: apa yang staf lakukan, apa yang perwira sanggup periksa, apa yang pecah. Tanpa akhir, pilot adalah pengadaan yang malu disebut pengadaan. NATO Reliability: uji di dalam use case yang didefinisikan, sepanjang daur hidup — bukan “nanti kita lihat”.'),
                    ],
                ),
                $this->textLesson(
                    'Tahap 3–4: perluasan Kodim dan kelembagaan',
                    'Dari satu Korem ke Kodim, lalu ke audit tahunan. Bukan poster di makorem.',
                    16,
                    [
                        $this->heading('Tahap 3–4: perluasan Kodim dan kelembagaan', 1),
                        $this->paragraph('Tahap 3 menuliskan apa yang berlaku ke Kodim di bawah Korem. Kebijakan perangkat dinas minimum: data personel tidak ke model publik; draf surat boleh dibantu jika perwira mengesahkan; ujian/pelatihan mengikuti perintah, bukan tren. Klausul vendor wajib: dasar pemrosesan, lokasi data, larangan melatih model publik dari data kesatuan, hak hapus, audit.'),
                        $this->paragraph('Kapasitas staf di tahap 3 adalah melek wewenang, bukan fine-tuning. Seluruh personel tidak perlu jadi insinyur. Seluruh perwira staf perlu tahu kapan AI merusak tanggung jawab komando. NATO 2024 menyebut dampak pada tenaga kerja: pelatihan ulang, bukan hanya tools.'),
                        $this->paragraph('Tahap 4 adalah kelembagaan. Ada pemilik yang membaca laporan tahunan: berapa insiden data, berapa kontrak vendor, apakah data terlarang lolos. DoD prinsip Governable: kemampuan melepas atau mematikan sistem yang berperilaku di luar niat. TNI AD tidak wajib meniru CDAO. TNI AD wajib punya orang yang bisa mematikan sambungan Sisfomil ke vendor pada jam 02.00.'),
                        $this->paragraph('Kelembagaan juga berarti berhenti. Jika pilot Sisfomil menunjukkan data personel bocor ke prompt, kebijakan diperketat, bukan dilonggarkan karena vendor sudah dibayar. Modernisasi yang tidak bisa dihentikan bukan adopsi. Itu ketergantungan.'),
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
            'title' => 'Use case yang andal di kesatuan',
            'description' => 'Filter dulu. Sisfomil dan rapat staf lolos. Intel dan senjata tidak.',
            'lessons' => [
                $this->textLesson(
                    'Cara menemukan use case AI yang andal',
                    'Bukan daftar vendor. Empat saringan yang harus lolos sebelum rapat Danrem.',
                    18,
                    [
                        $this->heading('Cara menemukan use case AI yang andal', 1),
                        $this->paragraph('NATO PRU Reliability: aplikasi AI harus punya use case yang eksplisit dan terdefinisi. Tanpa saringan, setiap demo adalah “use case”. Saringan TNI AD di kursus ini hanya untuk kerja staf unclassified — administrasi, pelatihan, layanan kesatuan. Yang gagal saringan bukan “belum modern”. Yang gagal saringan tidak masuk peta jalan tahun ini.'),
                        $this->diagram(
                            'Empat saringan use case staf: kebutuhan, data, manusia, henti. Yang gagal tidak naik ke Danrem.',
                            [
                                ['id' => 'butuh', 'label' => 'Kebutuhan', 'hint' => 'staf'],
                                ['id' => 'data', 'label' => 'Data', 'hint' => 'unclass'],
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
                            'Kebutuhan staf: pekerjaan yang sudah ada — notulen, absensi, FAQ cuti, draf surat — bukan pekerjaan yang diciptakan vendor.',
                            'Data: unclassified, bisa disebut di rapat, tidak mencampur intel. Jika kamu tidak bisa menulis jenis datanya di lembar keputusan, itu bukan use case andal.',
                            'Manusia: perwira tetap menandatangani. Chatbot yang “langsung kirim ke Kodim” gagal saringan. DoD Responsible: manusia tetap bertanggung jawab.',
                            'Henti: sistem bisa dimatikan tanpa merusak rantai komando. NATO Governability. Jika vendor memegang satu-satunya salinan notulen, itu bukan adopsi.',
                        ]),
                        $this->paragraph('Gunakan saringan pada setiap usulan. “AI untuk intel” gagal di data dan di batas kursus ini. “AI untuk targeting” gagal di semuanya. “AI untuk ringkasan rapat staf Sisfomil, dengan Kasrem mengesahkan” bisa lolos ke tahap 2. Itu yang dimaksud andal: bisa dimulai, bisa diuji, bisa dihentikan.'),
                    ],
                ),
                $this->textLesson(
                    'Use case administrasi personel: Sisfomil',
                    'Sistem yang sudah jalan di Korem 074. Bukan senjata. Bukan intel.',
                    16,
                    [
                        $this->heading('Use case administrasi personel: Sisfomil', 1),
                        $this->paragraph('Sisfomil di Korem 074/Warastratama adalah sistem informasi staf/personel. Ia sudah dipakai untuk kerja kantor kesatuan: personel, absensi, organisasi. Itu keunggulan yang jarang diakui slide vendor: data sudah ada, manusia sudah punya rutinitas, wewenang sudah ada di rantai komando. Adopsi AI yang andal menempel pada Sisfomil sebagai sumber staf — bukan menggantinya diam-diam dengan model publik.'),
                        $this->diagram(
                            'Sisfomil sebagai sumber staf: absensi dan personel masuk laporan. Manusia mengesahkan. Vendor tidak melatih model dari basis ini.',
                            [
                                ['id' => 'absensi', 'label' => 'Absensi', 'hint' => 'apel'],
                                ['id' => 'personel', 'label' => 'Personel', 'hint' => 'data'],
                                ['id' => 'laporan', 'label' => 'Laporan', 'hint' => 'staf'],
                                ['id' => 'sahkan', 'label' => 'Perwira', 'hint' => 'tanda tangan'],
                            ],
                            [
                                ['from' => 'absensi', 'to' => 'laporan'],
                                ['from' => 'personel', 'to' => 'laporan'],
                                ['from' => 'laporan', 'to' => 'sahkan'],
                            ],
                            4,
                        ),
                        $this->table(
                            ['Yang boleh diuji di tahap 2', 'Syarat', 'Yang dilarang'],
                            [
                                ['Hitungan dan ringkasan absensi untuk rapat staf', 'Hanya data yang sudah boleh dilihat pejabat itu; manusia mengesahkan angka', 'Ekspor basis personel ke vendor untuk “melatih AI kesatuan”'],
                                ['Draf FAQ cuti/prosedur unclassified', 'Teks prosedur yang sudah publik internal; bukan rekam medis atau penilaian', 'Chatbot yang menjawab tanpa jejak siapa yang bertanggung jawab'],
                                ['Bantuan cari di arsip surat unclassified', 'Hak akses sama dengan Sisfomil; log siapa yang bertanya', 'Menyambungkan model publik ke seluruh tabel personel'],
                            ],
                        ),
                        $this->paragraph('Sisfomil milik 074. Pack operasional 072 secara sadar tidak membawa basis Sisfomil. Itu pelajaran tata kelola, bukan trivia IT: satu kesatuan satu peta data. Use case 074 tidak menyalin database ke 072 “supaya AI-nya sama”.'),
                    ],
                ),
                $this->textLesson(
                    'Use case rapat, surat, dan pelatihan',
                    'Notulen, draf surat dinas, academy. Manusia tetap pemilik kalimat.',
                    16,
                    [
                        $this->heading('Use case rapat, surat, dan pelatihan', 1),
                        $this->paragraph('Tiga pekerjaan staf yang hampir setiap Korem sudah lakukan tanpa AI: rapat, surat, pelatihan. Itu kandidat andal karena kebutuhannya bukan fiksi vendor.'),
                        $this->table(
                            ['Use case', 'Contoh di 074 / 072', 'Saringan yang harus lolos'],
                            [
                                ['Notulen rapat staf', 'Ringkasan rapat Makorem; Kasrem mengesahkan', 'Tidak merekam yang classified; tidak kirim otomatis ke Kodim'],
                                ['Draf surat dinas', 'Bantuan struktur surat; perwira menulis substansi dan tanda tangan', 'Bukan surat yang “terkirim sendiri”; jejak draf disimpan di kesatuan'],
                                ['Pelatihan / academy', 'Open Course ini: pimpinan belajar wewenang, bukan men-deploy', 'Lesson bukan lab; lulus kuis tidak membuka runtime'],
                                ['Pemantauan kegiatan unclassified', 'Contoh 072: progres kegiatan teritorial seperti TMMD sebagai kerja staf, bukan intel', 'Angka progres dibaca manusia; bukan targeting'],
                            ],
                        ),
                        $this->paragraph('Sipamungkas di Korem 072 adalah sistem staf kesatuan (https://si.pamungkas.org). Ia tempat wajar untuk administrasi dan pemantauan kegiatan yang sudah unclassified. Ia bukan alasan untuk menyambungkan agent runtime ke Sisfomil 074. Dua sistem, dua peta data, dua pemilik.'),
                        $this->paragraph('Pelatihan adalah use case yang sering dilupakan. NATO 2024 menyebut tenaga kerja. DoD RAI menyebut AI workforce. TNI AD yang mengirim perwira ke academy ini sedang mengadopsi kapasitas. TNI AD yang mewajibkan chatbot tanpa Lesson wewenang sedang mengadopsi tren.'),
                    ],
                ),
                $this->textLesson(
                    'Use case yang tidak dimulai: intel, senjata, targeting',
                    'Bukan karena “belum diajarkan”. Karena saringan dan hukum menolaknya di kerangka ini.',
                    14,
                    [
                        $this->heading('Use case yang tidak dimulai', 1),
                        $this->paragraph('Kursus ini ekstensif tentang staf. Ia sengaja pendek tentang tempur. ICRC dan forum senjata otonom ada di pustaka sebagai batas. REAIM membahas AI di domain militer secara luas; academy ini tidak mengeklaim menutupi itu. Yang berikut tidak masuk peta jalan Korem tahun ini, dan tidak masuk kuis sebagai “jawaban benar kalau modern”.'),
                        $this->bullets([
                            'Intelijen: fusi sumber, profil, targeting intel. Data tidak bisa disebut di Open Course. Saringan data gagal.',
                            'Senjata dan targeting: DoD Directive 3000.09 (Autonomy in Weapon Systems) adalah dokumen AS, bukan runbook TNI. Menyebutnya sebagai bahan baca bukan izin.',
                            'Model yang dilatih dari arsip personel atau surat dinas untuk “AI kesatuan”.',
                            'Agent yang mengeksekusi perintah ke Kodim tanpa manusia.',
                            'Menyambungkan Sisfomil 074 ke runtime 072 atau sebaliknya.',
                        ]),
                        $this->paragraph('Menolak ini di minggu pertama bukan ketinggalan zaman. NATO Lawfulness: sesuai hukum nasional dan internasional. Hukum nasional kamu adalah UU TNI dan UU PDP, bukan brosur. Kalau suatu hari TNI AD menulis doktrin AI tempur, itu dokumen lain, Course lain, klasifikasi lain. Bukan Lesson ini.'),
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
            'title' => 'Tiga meja: tugas, komando, personel',
            'description' => 'Staf, komandan, dan prajurit tidak duduk di kursi yang sama.',
            'lessons' => [
                $this->textLesson(
                    'Tiga meja: tugas, komando, personel',
                    'Mencampur tiga meja ini biasanya berarti membeli dulu, menyesal kemudian.',
                    18,
                    [
                        $this->heading('Tiga meja', 1),
                        $this->paragraph('Kesatuan yang mencampur tugas staf, komando, dan personel dalam satu perintah biasanya membeli dulu, menyesal kemudian. Staf diminta “pakai AI” tanpa desain ulang laporan. Danrem diminta tanda tangan tanpa peta data. Personel diminta “jalankan saja” tanpa wewenang menghentikan.'),
                        $this->diagram(
                            'Tiga meja TNI AD. Kebijakan staf bukan tiket helpdesk. Kontrak bukan silabus apel.',
                            [
                                ['id' => 'tugas', 'label' => 'Tugas', 'hint' => 'staf'],
                                ['id' => 'komando', 'label' => 'Komando', 'hint' => 'wewenang'],
                                ['id' => 'personel', 'label' => 'Personel', 'hint' => 'perangkat'],
                            ],
                            [],
                            3,
                        ),
                        $this->paragraph('Tugas: administrasi, korespondensi, ringkasan rapat, Sisfomil, Sipamungkas, pelatihan. Mana yang boleh dibantu, mana yang tetap harus ditinjau manusia. Ini desain kerja, bukan tiket helpdesk.'),
                        $this->paragraph('Komando: anggaran, kontrak vendor, klasifikasi, reputasi kesatuan. Siapa yang boleh menandatangani data personel ke pihak ketiga. Siapa yang boleh menolak vendor yang tidak mau klausul hapus data.'),
                        $this->paragraph('Personel: kebijakan prajurit dan staf memakai AI di perangkat dinas. Bukan pekerjaan kursus ini untuk men-deploy agen, dan bukan tombol di Lesson. Meja ketiga tetap harus disebut, supaya tidak ada yang berpura-pura Lesson ini adalah runbook.'),
                    ],
                ),
                $this->textLesson(
                    'Meja tugas: staf dan administrasi',
                    'Diam di seksi adalah kebijakan. Biasanya kebijakan terburuk.',
                    16,
                    [
                        $this->heading('Meja tugas', 1),
                        $this->paragraph('NATO Reliability menuntut use case yang didefinisikan. Di meja tugas, itu berarti setiap seksi menulis: laporan mana yang boleh dibantu AI, siapa mengesahkan, di sistem mana (Sisfomil, Sipamungkas, arsip surat). Seksi yang diam memaksa personel menebak, dan memaksa Kasrem marah di minggu inspeksi.'),
                        $this->table(
                            ['Konteks staf', 'Sikap minimum yang bisa ditulis Kasi', 'Alasan'],
                            [
                                ['Laporan personel / absensi', 'AI boleh merangkum angka yang sudah sah di Sisfomil; perwira mengesahkan', 'Angka adalah tanggung jawab manusia'],
                                ['Notulen rapat unclassified', 'Boleh draf, wajib dibaca Kasrem sebelum edar', 'Kalimat perintah bukan milik model'],
                                ['Surat dinas', 'Boleh bantuan struktur; substansi dan tanda tangan manusia', 'Wewenang surat adalah komando'],
                                ['FAQ prosedur', 'Boleh jika sumbernya perintah yang sudah berlaku; jejak jawaban', 'Jangan jadi “hukum” baru dari chatbot'],
                            ],
                        ),
                        $this->paragraph('Kasi tidak wajib seragam di semua seksi. Kasi wajib tertulis. Kebijakan kesatuan boleh lebih ketat dari minimum; tidak boleh lebih longgar pada data personel jika Danrem sudah mengunci.'),
                    ],
                ),
                $this->textLesson(
                    'Meja komando: anggaran, vendor, klasifikasi',
                    'Danrem menandatangani yang tidak bisa ditandatangani operator Sisfomil.',
                    16,
                    [
                        $this->heading('Meja komando', 1),
                        $this->paragraph('Tata kelola TNI AD punya rantai yang tidak dimiliki perusahaan: Danrem, Kodam, dan hukum negara. Kontrak lisensi kecil mungkin cukup di infolahta. Kontrak yang menyentuh Sisfomil, data personel seluruh Korem, atau anggaran di atas ambang kesatuan naik ke pimpinan yang disebut di lembar keputusan.'),
                        $this->paragraph('Klasifikasi adalah aset. Aset itu rapuh terhadap dua skandal: kebocoran data personel, dan perintah yang “dibantu AI” tanpa pemilik. Tata kelola yang hanya menghitung hemat staf (karena “AI yang ngetik”) sedang menukar aset jangka panjang dengan efisiensi semu.'),
                        $this->numbered([
                            'Setiap pengadaan AI menulis: tujuan, data yang disentuh, dasar hukum PDP, lokasi pemrosesan, klausul larangan training, pemilik internal.',
                            'Vendor yang menolak klausul data tidak lolos, meski demonya bagus di ruang staf.',
                            'Anggaran pelatihan perwira (wewenang, bukan prompt) adalah belanja adopsi. Anggaran lisensi tanpa pelatihan adalah belanja tren.',
                            'Keputusan “berhenti berlangganan” harus bisa diambil tanpa kehilangan arsip Sisfomil. Ketergantungan vendor adalah risiko yang disebut di Lesson risiko.',
                        ]),
                        $this->paragraph('Perintah yang mencampur “wajib pakai merek X di semua Kodim” dengan “disiplin personel” sedang mencampur meja. Pisahkan: perintah pemakaian di tugas; kontrak di komando; runbook di operasional yang bukan Lesson ini.'),
                    ],
                ),
                $this->textLesson(
                    'Meja personel: perangkat dinas dan shadow AI',
                    'HP dinas yang sudah memakai model publik adalah kebijakan, bukan gosip.',
                    14,
                    [
                        $this->heading('Meja personel', 1),
                        $this->paragraph('Shadow AI: staf sudah memakai tools pribadi di perangkat dinas sebelum ada perintah. Diam adalah kebijakan terburuk. DoD Equitable dan Bias Mitigation NATO relevan di sini dalam bentuk sederhana: model publik tidak mengenal konteks kesatuan, dan bisa mengarang pangkat, nama, atau prosedur.'),
                        $this->paragraph('Kebijakan perangkat dinas yang sanggup ditulis minggu ini: boleh, wajib deklarasi, atau dilarang. Contoh jujur: draf surat unclassified boleh dengan deklarasi; data personel Sisfomil dilarang di model publik; foto apel yang berisi wajah dan lokasi dilarang diunggah ke aplikasi konsumen.'),
                        $this->paragraph('Personel bukan musuh adopsi. Personel adalah orang yang akan menanggung perintah yang tidak tertulis. Pelatihan di academy ini adalah untuk pimpinan; turunan ke personel adalah perintah yang bisa dibaca, bukan memo “pakai AI” di grup.'),
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
            'description' => 'UU 27/2022 PDP, SPBE, dan batas agent runtime — diterjemahkan ke Korem.',
            'lessons' => [
                $this->textLesson(
                    'Data personel dan kesatuan',
                    'Siapa yang punya, siapa yang boleh meminjamkan ke vendor. Bukan keputusan fitur.',
                    16,
                    [
                        $this->heading('Data personel dan kesatuan', 1),
                        $this->paragraph('Setiap “AI untuk militer” yang menyentuh daftar personel Sisfomil, surat dinas, peta kerja, atau rekaman rapat adalah keputusan data. Bukan keputusan fitur. UU 27/2022 tentang Pelindungan Data Pribadi berlaku pada data pribadi. Status prajurit tidak membuat data itu bebas ke vendor asing.'),
                        $this->bullets([
                            'Data personel — identitas, riwayat, penilaian, absensi. Tidak keluar ke vendor tanpa dasar dan pemilik.',
                            'Surat dan perintah — bukan bahan latihan model tanpa persetujuan Danrem.',
                            'Peta kerja dan lokasi — shadow AI di perangkat dinas adalah kebocoran yang sudah terjadi.',
                            'Pemisahan 074 dan 072 — Sisfomil tidak disalin ke Sipamungkas “supaya AI-nya satu”.',
                        ]),
                        $this->paragraph('Komandan yang tidak bisa menyebut data mana yang keluar kesatuan, belum siap adopsi. Ia baru siap demo. NATO Explainability and Traceability: sumber dan prosedur harus bisa ditinjau. “Sudah di cloud vendor” bukan jejak.'),
                    ],
                ),
                $this->textLesson(
                    'UU 27/2022 PDP dan data prajurit',
                    'Pengendali, dasar pemrosesan, transfer. Bukan “karena dinas”.',
                    16,
                    [
                        $this->heading('UU 27/2022 PDP dan data prajurit', 1),
                        $this->paragraph('Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi membedakan pengendali dan prosesor, data umum dan data spesifik, serta transfer ke luar yurisdiksi. Kesatuan yang mengunggah daftar personel ke model publik sedang memproses data pribadi. “Karena dinas” bukan dasar hukum yang tertulis di undang-undang.'),
                        $this->table(
                            ['Pertanyaan PDP', 'Arti di Korem', 'Pemilik yang wajar'],
                            [
                                ['Siapa pengendali?', 'Kesatuan yang menentukan tujuan dan cara — biasanya pimpinan, bukan vendor', 'Danrem / pejabat yang disebut di lembar'],
                                ['Dasar pemrosesan?', 'Perintah dinas yang sah, bukan klik setuju di chatbot', 'Staf hukum + komando'],
                                ['Data spesifik?', 'Kesehatan, biometrik, jika ada — lebih ketat', 'Jangan masuk pilot tahap 2'],
                                ['Transfer?', 'Lokasi server vendor, subprosesor, larangan training', 'Kontrak; vendor yang menolak = tidak lolos'],
                            ],
                        ),
                        $this->paragraph('UU ITE dan Perpres 95/2018 SPBE menuntut tata kelola elektronik yang akuntabel. Sisfomil dan Sipamungkas adalah sistem staf yang lebih dekat ke semangat SPBE daripada “AI militer” di brosur. Jangan tukar sistem yang sudah ada dengan model yang tidak bisa diaudit.'),
                    ],
                ),
                $this->textLesson(
                    'SPBE, BSSN, dan vendor di luar kesatuan',
                    'Pemerintahan berbasis elektronik sudah ada. AI bukan izin melewatinya.',
                    14,
                    [
                        $this->heading('SPBE, BSSN, dan vendor', 1),
                        $this->paragraph('Perpres 95/2018 tentang Sistem Pemerintahan Berbasis Elektronik mengatur tata kelola, manajemen, dan audit TIK. TNI adalah institusi negara. Pengadaan AI yang mengabaikan SPBE “karena khusus militer” sedang mencari celah, bukan mencari ketaatan. BSSN dan keamanan siber adalah meja komando, bukan footnote vendor.'),
                        $this->paragraph('SE Menkominfo 9/2023 tentang Etika Kecerdasan Artifisial menandai arah: etika, akuntabilitas, keandalan. Ia bukan pengganti perintah Danrem. Ia cukup untuk menolak argumen “di Indonesia belum ada aturan jadi bebas”.'),
                        $this->paragraph('Vendor di luar kesatuan: tulis lokasi data, subprosesor, dan apakah prompt personel dipakai melatih model. DoD RAI dan NATO PRU sama-sama menuntut jejak. Jika jawaban vendor “rahasia dagang”, itu bukan jejak. Itu gembok.'),
                    ],
                ),
                $this->textLesson(
                    'Agent AI runtime: batas academy',
                    'Yang sudah pernah jalan di sisi 074 dan 072 diajarkan sebagai batas, bukan sebagai tombol Lesson.',
                    16,
                    [
                        $this->heading('Agent AI runtime: batas academy', 1),
                        $this->paragraph('Korem 074 dan Korem 072 punya sisi operasional di luar academy ini: agent AI runtime yang menyentuh kerja staf (Sisfomil di 074; sistem staf 072). Itu fakta adopsi yang sudah terjadi di lapangan operasional. Itu bukan bukti bahwa Lesson ini adalah konsol. ADR academy: Lesson bukan lab.'),
                        $this->diagram(
                            'Academy menjelaskan wewenang. Runtime operasional bukan tombol di Lesson. Lulus kuis tidak membuka Course terbatas.',
                            [
                                ['id' => 'academy', 'label' => 'Academy', 'hint' => 'Lesson'],
                                ['id' => 'wewenang', 'label' => 'Wewenang', 'hint' => 'rapat'],
                                ['id' => 'sistem', 'label' => 'Sisfomil', 'hint' => 'staf'],
                                ['id' => 'runtime', 'label' => 'Runtime', 'hint' => 'bukan Lesson'],
                            ],
                            [
                                ['from' => 'academy', 'to' => 'wewenang'],
                                ['from' => 'wewenang', 'to' => 'sistem', 'label' => 'perintah'],
                                ['from' => 'sistem', 'to' => 'runtime', 'label' => 'bukan di sini'],
                            ],
                            4,
                        ),
                        $this->paragraph('Yang boleh kamu bawa pulang: Sisfomil dan Sipamungkas adalah sistem staf yang sudah ada; AI boleh menempel jika saringan use case lolos; runtime agen adalah meja operasional yang tidak dikerjakan tutor. Yang tidak boleh: mengira lulus kuis memberi akses produksi, menyalin basis 074 ke 072, atau membuka desktop agen dari halaman Lesson.'),
                        $this->quote('Adopsi yang dewasa memisahkan belajar dan mengoperasikan. Kamu belajar di sini. Mengoperasikan — jika kesatuan memutuskan — di luar sini, dengan pemilik yang sama yang menandatangani lembar keputusan.'),
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
            'title' => 'Contoh dan deliverable Korem 074 dan 072',
            'description' => 'Yang sudah dikerjakan sebagai sistem staf, plus kertas yang bisa dibawa ke rapat.',
            'lessons' => [
                $this->textLesson(
                    'Yang sudah dikerjakan di Korem 074: Sisfomil',
                    'Sistem staf yang sudah hidup. Deliverable: jangan ganti fondasi dengan demo.',
                    16,
                    [
                        $this->heading('Yang sudah dikerjakan di Korem 074: Sisfomil', 1),
                        $this->paragraph('Korem 074/Warastratama sudah punya Sisfomil: sistem informasi staf/personel untuk kerja kantor kesatuan. Itu deliverable yang sudah ada sebelum kursus ini ditulis. Peta jalan TNI AD tidak memulai dari nol di Surakarta. Ia memulai dari sistem yang manusia sudah pakai.'),
                        $this->paragraph('Contoh kerja staf yang sudah tertanam di keluarga sistem itu — tanpa mengarang modul rahasia: organisasi kesatuan, personel, absensi, rapat, layanan. AI yang andal di 074 menempel pada pekerjaan itu. AI yang tidak andal menjanjikan “intel otomatis” di atas fondasi yang belum dikunci wewenangnya.'),
                        $this->table(
                            ['Deliverable 074 yang sudah ada', 'Arti untuk peta jalan', 'Yang bukan deliverable kursus ini'],
                            [
                                ['Sisfomil sebagai sumber data staf', 'Pilot tahap 2 punya tempat: laporan, absensi, FAQ prosedur', 'Akses produksi ke basis data dari Lesson'],
                                ['Rantai komando Danrem–Kasrem–seksi', 'Lembar keputusan punya pemilik nyata', 'Nama pejabat intern yang dikarang'],
                                ['Pemisahan dari 072', 'Sisfomil tidak dibawa ke Korem lain sebagai “satu AI”', 'Menyatukan database antar-Korem'],
                            ],
                        ),
                        $this->paragraph('Bawa tabel ini ke rapat 074. Tanya: use case mana yang sudah lolos saringan, dan siapa yang menandatangani jika angka Sisfomil salah. Itu rapat adopsi. Rapat merek tools bisa menunggu di luar.'),
                    ],
                ),
                $this->textLesson(
                    'Yang sudah dikerjakan di Korem 072: Sipamungkas',
                    'Sistem staf kesatuan yang terpisah dari Sisfomil. Satu Korem, satu peta data.',
                    16,
                    [
                        $this->heading('Yang sudah dikerjakan di Korem 072: Sipamungkas', 1),
                        $this->paragraph('Korem 072/Pamungkas memakai Sipamungkas (https://si.pamungkas.org) sebagai sistem staf kesatuan. Itu sistem, bukan slogan. Administrasi dan pemantauan kegiatan unclassified — termasuk progres kerja teritorial seperti TMMD sebagai pekerjaan staf — adalah contoh yang boleh disebut tanpa masuk intel.'),
                        $this->paragraph('Pelajaran tata kelola yang sudah diputuskan di lapangan operasional: Sisfomil tetap di 074. 072 tidak membawa basis itu. Deliverable untuk pimpinan 072: peta data sendiri, saringan use case sendiri, pemilik sendiri. Menyalin “AI 074” beserta datanya adalah kegagalan saringan, bukan efisiensi.'),
                        $this->table(
                            ['Deliverable 072 yang sudah ada', 'Arti untuk peta jalan', 'Batas'],
                            [
                                ['Sipamungkas sebagai sistem staf', 'Pilot tahap 2: administrasi dan progres kegiatan unclassified', 'Bukan targeting, bukan intel'],
                                ['Pemisahan dari Sisfomil 074', 'Inventaris tahap 1 menulis sistem mana yang menyimpan apa', 'Jangan “satu model untuk dua Korem”'],
                                ['Agent runtime di sisi operasional', 'Disebut di Lesson batas; tidak dibuka di academy', 'Lulus kuis ≠ akses runtime'],
                            ],
                        ),
                        $this->paragraph('Kalau 072 ingin AI untuk notulen atau FAQ, saringannya sama dengan 074. Yang berbeda hanya sistem sumbernya. Itu kerangka yang bisa dibawa ke rapat, bukan SK yang sudah berlaku. Jika Korem kelak menerbitkan perintah resmi, perintah itu yang diikuti; kerangka ini hanya latihan keputusan.'),
                    ],
                ),
                $this->documentLesson(
                    'Lembar keputusan pimpinan kesatuan',
                    'Satu halaman untuk rapat Danrem dan Kasrem. Bukan checklist belanja tools.',
                    12,
                    [
                        $this->heading('Lembar keputusan pimpinan kesatuan', 1),
                        $this->paragraph('Unduh atau baca PDF ini sebelum rapat. Bukan checklist belanja tools. Daftar keputusan yang harus ada pemiliknya. Isi di rapat 45 menit tahap 0–1. Kosong yang tertinggal adalah temuan, bukan “nanti”.'),
                    ],
                    [
                        'filename' => 'lembar-keputusan-ai-militer.pdf',
                        'title' => 'Lembar Keputusan AI untuk TNI AD',
                        'paragraphs' => [
                            'Kursus: AI untuk Militer. Open Course, gratis. Bukan lab, bukan konsol. Kasus: TNI AD, contoh staf Korem 074 (Sisfomil) dan Korem 072 (Sipamungkas).',
                            'Untuk: Danrem, Kasrem, perwira staf, Dandim, personel.',
                            '1. Untuk siapa AI dipakai di kesatuan ini tahun ini? Administrasi, rapat, pelatihan — sebut satu prioritas staf. Bukan intel, bukan senjata.',
                            '2. Data apa yang boleh keluar ke vendor? Personel Sisfomil, surat, peta kerja — sebut yang dilarang. 074 dan 072 tidak saling menyalin basis.',
                            '3. Siapa menandatangani? Danrem, Kasrem, atau pimpinan di atasnya. Operator sistem tidak menandatangani data kesatuan.',
                            '4. Apa kebijakan memakai AI di perangkat dinas? Diam adalah kebijakan terburuk.',
                            '5. Use case tahap 2 yang lolos saringan: kebutuhan staf, data unclassified, manusia mengesahkan, bisa dihentikan. Tulis satu.',
                            '6. Apa yang tidak dimulai minggu pertama? Deploy agen hidup, unggah arsip personel, kontrak tanpa klausul data, intel/targeting.',
                            'Adopsi: keputusan wewenang, bukan pembelian chatbot. Deliverable: lembar ini terisi pemilik, bukan stempel “akan dikaji”.',
                            'EnterLMS: academy. Menyelesaikan kursus ini tidak membuka Course terbatas dan tidak men-deploy apa pun.',
                            'Rujukan: NATO PRU (2021/2024); DoD AI Ethical Principles (2020); UU 34/2004; UU 27/2022; Perpres 95/2018.',
                        ],
                    ],
                ),
                $this->documentLesson(
                    'Peta jalan 24 bulan TNI AD',
                    'Kalender yang bisa ditempel di rapat. Bukan Gantt fiktif dengan nama pejabat intern.',
                    14,
                    [
                        $this->heading('Peta jalan 24 bulan TNI AD', 1),
                        $this->paragraph('Empat tahap sebelumnya butuh kalender. PDF ini memecah 24 bulan menjadi fondasi, pilot staf, perluasan Kodim, dan kelembagaan. Nama unit memakai sebutan publik (Danrem, Kasrem, Korem 074, Korem 072, Kodim). Tidak ada nama pejabat intern yang dikarang.'),
                        $this->diagram(
                            'Dua puluh empat bulan: fondasi, pilot staf Sisfomil/Sipamungkas, perluasan Kodim, lalu audit kelembagaan.',
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
                        $this->paragraph('Geser bulan jika rapat Danrem tidak bisa bulan ini. Jangan geser urutan: lisensi massal sebelum fondasi, atau runtime agen sebelum saringan use case.'),
                    ],
                    [
                        'filename' => 'peta-jalan-24-bulan-tni-ad.pdf',
                        'title' => 'Peta Jalan 24 Bulan Adopsi AI TNI AD',
                        'paragraphs' => [
                            'Sifat: kerangka kalender untuk rapat. Bukan perintah yang sudah diundangkan.',
                            'Bulan 1–3: rapat sikap Danrem+Kasrem; lembar keputusan terisi pemilik; moratorium agen hidup, unggah arsip personel, wajib merek, intel/targeting sebagai “pilot”.',
                            'Bulan 2–6: inventaris shadow AI di perangkat dinas, peta sistem Sisfomil (074) / Sipamungkas (072), daftar kontrak yang sudah ada. Kebijakan perangkat dinas minimum diedarkan ke seksi dan Kodim.',
                            'Bulan 4–6: pelatihan wewenang untuk perwira staf (saringan use case, PDP, PRU sebagai bahan), bukan fine-tuning.',
                            'Bulan 7–18: satu pilot staf — Sisfomil 074 atau Sipamungkas 072, bukan keduanya plus senjata. Evaluasi tertulis tiap akhir triwulan.',
                            'Bulan 13–18: klausul vendor wajib pada perpanjangan sistem staf. Matikan add-on yang menolak larangan training.',
                            'Bulan 13–24: perluasan ke Kodim berdasarkan bukti pilot. Tidak menyalin basis 074 ke 072.',
                            'Bulan 19–24: audit tahunan (insiden data, ketergantungan vendor, shadow AI). Kemampuan henti darurat diuji di atas kertas dan di atas sistem staf — bukan di Lesson.',
                            'Indikator jujur tahun ke-2: satu use case staf tertulis; nol sambungan Sisfomil ke model publik; perwira tahu data mana yang keluar; runtime tetap di luar academy.',
                            'Bukan indikator: jumlah lisensi, jumlah prompt, atau “sudah seperti NATO”.',
                            'Rujukan: NATO Revised AI Strategy 2024; NATO PRU 2021; DoD RAI 2022; UU 34/2004; UU 27/2022; Perpres 95/2018.',
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
                    'Data kesatuan, vendor, shadow AI, perintah tanpa pemilik. Menyembunyikan risiko lebih mahal.',
                    16,
                    [
                        $this->heading('Risiko yang pimpinan harus sebutkan', 1),
                        $this->paragraph('Menyebut risiko bukan menolak AI. Menyembunyikan risiko adalah yang membuat Danrem kaget di tahun kedua. UNESCO Recommendation on the Ethics of AI (2021) menempatkan do no harm di depan manfaat yang masih spekulatif. NATO PRU menempatkan responsibility and accountability pada manusia, bukan pada merek.'),
                        $this->diagram(
                            'Empat risiko kesatuan yang wajib disebut sebelum tanda tangan, plus siapa yang menanggung.',
                            [
                                ['id' => 'data', 'label' => 'Data', 'hint' => 'Sisfomil'],
                                ['id' => 'vendor', 'label' => 'Vendor', 'hint' => 'gembok'],
                                ['id' => 'shadow', 'label' => 'Shadow AI', 'hint' => 'HP dinas'],
                                ['id' => 'perintah', 'label' => 'Perintah', 'hint' => 'tanpa pemilik'],
                            ],
                            [],
                            4,
                        ),
                        $this->bullets([
                            'Data kesatuan — personel Sisfomil, surat, lokasi yang tidak bisa ditarik dari vendor. UU 27/2022.',
                            'Ketergantungan vendor — kesatuan tidak bisa pindah tanpa kehilangan riwayat laporan.',
                            'Shadow AI — staf sudah memakai tools pribadi di perangkat dinas sebelum ada perintah.',
                            'Perintah tanpa kebijakan — “pakai AI” tanpa batas data adalah kebocoran yang dilegalkan.',
                            'Mencampur 074 dan 072 — menyalin Sisfomil ke Sipamungkas merusak peta data dan wewenang.',
                        ]),
                        $this->paragraph('Tiap risiko butuh wewenang. Data: Danrem sebagai pengendali yang disebut di lembar. Vendor: pengadaan plus pimpinan. Shadow AI: Kasrem dan kebijakan perangkat. Perintah: Danrem, bukan memo seksi. Pemisahan kesatuan: masing-masing Korem pemilik sistemnya.'),
                    ],
                ),
                $this->textLesson(
                    'Mulai dari rapat, bukan dari tools',
                    'Agenda 45 menit untuk Danrem dan Kasrem. Lima keputusan, bukan sepuluh demo.',
                    14,
                    [
                        $this->heading('Mulai dari rapat, bukan dari tools', 1),
                        $this->paragraph('Undangan rapat yang hanya berisi demo vendor akan menghasilkan pembelian. Undangan yang berisi lima pertanyaan di lembar keputusan TNI AD akan menghasilkan sikap. Bawa PDF Lesson deliverable, bukan brosur.'),
                        $this->diagram(
                            'Empat puluh lima menit: prioritas staf, data terlarang, use case, pemilik. Tools menunggu di luar.',
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
                            'Sepuluh menit: satu prioritas tahun ini (bukan sepuluh). Contoh jujur: notulen atau laporan Sisfomil, bukan “AI militer”.',
                            'Lima belas menit: data yang dilarang keluar — personel, surat, peta kerja; 074 tidak menyalin ke 072.',
                            'Sepuluh menit: satu use case yang lolos saringan, atau keputusan belum ada yang lolos.',
                            'Sepuluh menit: siapa pemilik keputusan lanjutan (Danrem, Kasrem, Dandim).',
                        ]),
                        $this->paragraph('Kalau rapat berakhir tanpa pemilik, kesatuan belum adopsi. Kesatuan baru menonton slide. Vendor menunggu di luar sampai lima keputusan itu tertulis.'),
                    ],
                ),
                $this->textLesson(
                    'Apa yang tidak dimulai minggu pertama',
                    'Daftar yang menahan FOMO tanpa menolak belajar. TNI AD tetap boleh kuliah di academy ini hari ini.',
                    12,
                    [
                        $this->heading('Apa yang tidak dimulai minggu pertama', 1),
                        $this->bullets([
                            'Menjalankan agen hidup di jaringan dinas “supaya kelihatan modern”.',
                            'Mengunggah arsip personel Sisfomil atau surat dinas ke vendor untuk “melatih AI kesatuan”.',
                            'Mewajibkan seluruh Kodim memakai satu merek dalam tujuh hari.',
                            'Menyambungkan Sisfomil ke model generatif publik, atau menyalinnya ke Sipamungkas.',
                            'Pilot intel, senjata, atau targeting “karena NATO juga AI”.',
                            'Membuka konsol runtime di dalam Lesson atau LMS.',
                        ]),
                        $this->paragraph('Belajar di academy ini boleh dimulai hari ini. Mengoperasikan agen hidup tidak. Tutor di Lesson ini akan menolak membuka konsol, karena Lesson bukan lab.'),
                        $this->quote('Adopsi yang dewasa kelihatan lambat di minggu pertama, dan murah di tahun kedua. TNI AD sudah punya rantai komando tanpa chatbot. Jangan tukar itu dengan invoice.'),
                    ],
                ),
                $this->textLesson(
                    'Pustaka: NATO, DoD, UU TNI, PDP, SPBE',
                    'Sumber bernama, bertanggal, bisa dicek. Bukan blog vendor, bukan doktrin fiktif.',
                    18,
                    [
                        $this->heading('Pustaka', 1),
                        $this->paragraph('Kerangka ini hanya sekuat sumbernya. Yang berikut bisa dibuka di rapat. Yang tidak ada di daftar — orgas intern, jumlah personel, program senjata yang tidak diumumkan — sengaja tidak dipakai.'),
                        $this->heading('TNI AD dan sistem staf (fakta yang boleh disebut)'),
                        $this->bullets([
                            'Undang-Undang Nomor 34 Tahun 2004 tentang Tentara Nasional Indonesia.',
                            'Undang-Undang Nomor 3 Tahun 2002 tentang Pertahanan Negara.',
                            'Hierarki publik: Kodam, Korem, Kodim, Koramil — situs dan publikasi TNI AD.',
                            'Korem 074/Warastratama (Surakarta) — Sisfomil sebagai sistem informasi staf/personel.',
                            'Korem 072/Pamungkas — Sipamungkas, https://si.pamungkas.org, sistem staf kesatuan.',
                            'Pemisahan data: Sisfomil tetap di 074; 072 tidak membawa basis itu.',
                        ]),
                        $this->heading('Internasional (bahan, bukan pengganti hukum TNI)'),
                        $this->bullets([
                            'NATO. Summary of the NATO Artificial Intelligence Strategy. 22 Oktober 2021. Enam Principles of Responsible Use: Lawfulness; Responsibility and Accountability; Explainability and Traceability; Reliability; Governability; Bias Mitigation. https://www.nato.int/',
                            'NATO. Summary of NATO’s revised Artificial Intelligence (AI) strategy. 10 Juli 2024. Use case, tenaga kerja, ancaman adversarial. https://www.nato.int/',
                            'U.S. Department of Defense. AI Ethical Principles. Memorandum Menteri Pertahanan, 21 Februari 2020. Responsible, Equitable, Traceable, Reliable, Governable.',
                            'U.S. Department of Defense / CDAO. Responsible Artificial Intelligence Strategy and Implementation Pathway. Juni 2022.',
                            'REAIM. Blueprint for Action. Responsible AI in the Military Domain. Den Haag 2023; Seoul 2024.',
                            'UK Ministry of Defence. Defence Artificial Intelligence Strategy. 2022.',
                            'UNESCO. Recommendation on the Ethics of Artificial Intelligence. 23 November 2021. (Etika umum; bukan doktrin TNI.)',
                            'OECD AI Principles — rujukan pelengkap tata kelola.',
                            'NIST AI Risk Management Framework (AI RMF 1.0) — rujukan pelengkap risiko staf/IT.',
                            'ICRC — rujukan batas tentang AI dan konflik bersenjata. Bukan silabus tempur kursus ini.',
                        ]),
                        $this->heading('Indonesia'),
                        $this->bullets([
                            'Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.',
                            'Undang-Undang Informasi dan Transaksi Elektronik beserta perubahannya.',
                            'Peraturan Presiden Nomor 95 Tahun 2018 tentang Sistem Pemerintahan Berbasis Elektronik (SPBE).',
                            'Surat Edaran Menteri Komunikasi dan Informatika Nomor 9 Tahun 2023 tentang Etika Kecerdasan Artifisial — arah, bukan pengganti perintah Danrem.',
                        ]),
                        $this->paragraph('Kalau suatu klaim di Lesson tidak tertaut ke salah satu sumber ini, perlakukan sebagai kerangka diskusi, bukan sebagai berita kesatuan. Academy ini menolak mengarang orgas dan menolak menjual merek. Tugasmu setelah lulus kuis: bawa lembar keputusan ke rapat, bukan membawa konsol ke jaringan dinas.'),
                    ],
                ),
            ],
        ];
    }
}
