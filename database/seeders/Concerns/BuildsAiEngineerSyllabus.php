<?php

namespace Database\Seeders\Concerns;

/**
 * Three-day syllabus for Insinyur Kecerdasan Artificial (AI Engineer).
 *
 * Occupation TIK.DSC0704, scheme SKEMA-45. Running case: complaint routing
 * at Dinas Pelayanan Publik Kota Arunika. Lessons are text and figures.
 * Not a live training console, and not a BNSP certificate.
 */
trait BuildsAiEngineerSyllabus
{
    /**
     * @return list<array{title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    protected function sections(): array
    {
        return [
            $this->sectionHariSatu(),
            $this->sectionHariDua(),
            $this->sectionHariTiga(),
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
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionHariSatu(): array
    {
        return [
            'title' => 'Hari 1. Sasaran teknis dan rencana proyek',
            'description' => 'Tujuh jam. Tetapkan sasaran teknis solusi AI dan rencana proyek pada pengaduan Kota Arunika, sebelum ada yang memasang model.',
            'lessons' => [
                $this->textLesson(
                    'Untuk siapa kursus ini',
                    'Tiga hari untuk praktisi yang akan meninjau, memasang, dan merawat solusi AI — bukan kursus melatih model dari nol.',
                    25,
                    [
                        $this->heading('Untuk siapa kursus ini', 1),
                        $this->paragraph('Kursus ini untuk kamu yang sudah menulis Python dan pernah melatih atau memakai model. Okupasinya Insinyur Kecerdasan Artificial, kode TIK.DSC0704, jenjang kualifikasi 7. Tiga hari ini adalah pelatihan berbasis kompetensi untuk skema SKEMA-45, bukan kuliah pengantar machine learning.'),
                        $this->paragraph('Ini Open Course, gratis, dalam Bahasa Indonesia. Kamu boleh enroll sendiri. Menyelesaikannya tidak membuka Course terbatas, dan tidak menerbitkan sertifikat BNSP. Uji kompetensi tetap di lembaga sertifikasi profesi. Biaya sertifikasi pada skema itu milik lembaga tersebut, bukan harga Course ini.'),
                        $this->paragraph('Benang merahnya satu dinas fiktif: Dinas Pelayanan Publik Kota Arunika. Ilmuwan data sudah menyerahkan model klasifikasi pengaduan. Tugasmu bukan melatih ulang dari nol. Tugasmu menentukan sasaran, merencanakan, meninjau, memasang, merawat, melindungi data pribadi, dan menutup dengan laporan.'),
                        $this->heading('Yang akan kamu kerjakan'),
                        $this->bullets([
                            'Menulis sasaran teknis dan rencana proyek pada hari pertama.',
                            'Mengadili paket model yang sudah ada, lalu menutupnya dengan berita acara.',
                            'Merancang pemasangan, perawatan, dan program kerja data pribadi tanpa konsol.',
                            'Menutup dengan laporan akhir. Bukan dengan sertifikat dari halaman ini.',
                        ]),
                        $this->heading('Delapan unit, tiga hari'),
                        $this->table(
                            ['Kode', 'Unit', 'Hari'],
                            [
                                ['J.62AIN00.002.1', 'Menentukan sasaran teknis solusi AI', '1'],
                                ['J.62AIN00.004.1', 'Membuat rencana proyek solusi AI', '1'],
                                ['J.62DMI00.015.1', 'Melakukan proses review pemodelan', '2'],
                                ['J.62DMI00.020.1', 'Melakukan review proyek data science', '2'],
                                ['J.62AIN00.015.1', 'Memasang solusi AI', '3'],
                                ['J.62AIN00.017.1', 'Merawat solusi AI', '3'],
                                ['J.62PDP00.011.1', 'Menerapkan program kerja pelindungan data pribadi', '3'],
                                ['J.62DMI00.021.1', 'Melakukan laporan akhir proyek data science', '3'],
                            ],
                        ),
                        $this->diagram(
                            'Tiga hari pada kasus yang sama. Hari berikutnya memakai artefak hari sebelumnya.',
                            [
                                ['id' => 'h1', 'label' => 'Hari 1', 'hint' => 'sasaran'],
                                ['id' => 'h2', 'label' => 'Hari 2', 'hint' => 'review'],
                                ['id' => 'h3', 'label' => 'Hari 3', 'hint' => 'pasang'],
                            ],
                            [
                                ['from' => 'h1', 'to' => 'h2'],
                                ['from' => 'h2', 'to' => 'h3'],
                            ],
                            3,
                        ),
                        $this->paragraph('Lesson adalah teks, tabel, dan diagram. Bukan notebook dan bukan konsol runtime. Di akhir tiap hari ada tugas tertulis. Kuis pengetahuan dinilai otomatis. Tugas portofolio dinilai manusia.'),
                        $this->paragraph('Kursus tetangga, Membangun Model AI untuk Kasus Tertentu, mengerjakan klasifikasi tiket dari sisi model. Di sini model itu sudah ada. Kamu yang memutuskan ia layak masuk layanan publik atau belum.'),
                    ],
                    preview: true,
                ),
                $this->textLesson(
                    'Kasus berjalan: pengaduan Kota Arunika',
                    'Lima meja, tiga saluran, satu keputusan: ke mana pengaduan ini pergi.',
                    40,
                    [
                        $this->heading('Kasus berjalan', 1),
                        $this->paragraph('Dinas Pelayanan Publik Kota Arunika menerima sekitar 400 pengaduan pada hari kerja. Salurannya tiga: SP4N-LAPOR, loket, dan WhatsApp resmi dinas. Petugas meja depan, Dina, membaca lalu mengirim ke satu dari lima meja: kependudukan, perizinan, infrastruktur, kebersihan, atau sosial.'),
                        $this->paragraph('Salah meja memperpanjang waktu warga. Meja infrastruktur yang terlambat juga berbahaya: jalan ambles tidak boleh mengantre di kebersihan sampai sore. Itu kasusnya. Bukan chatbot yang membalas warga. Bukan “AI untuk pelayanan publik”.'),
                        $this->heading('Lima pengaduan yang akan kembali'),
                        $this->bullets([
                            '“ktp saya hilang, mohon diterbitkan ulang” → kependudukan. Teks ini sering membawa NIK.',
                            '“imb rumah ditolak tanpa alasan” → perizinan.',
                            '“jalan di gang melati ambles setelah hujan” → infrastruktur.',
                            '“sampah tiga hari tidak diangkut di rt 04” → kebersihan.',
                            '“bantuan sosial ibu saya tidak cair” → sosial, dan isinya sensitif.',
                        ]),
                        $this->paragraph('Satu pengaduan campur akan menguji aturanmu: “lampu jalan mati dan sampah menumpuk”. Dua meja sama-sama masuk akal. Tanpa aturan prioritas, model dipaksa memilih dan Dina yang menanggung salahnya.'),
                        $this->diagram(
                            'Teks pengaduan masuk. Model hanya boleh mengirim ke meja, atau mengaku ragu.',
                            [
                                ['id' => 'adu', 'label' => 'Pengaduan', 'hint' => 'teks masuk'],
                                ['id' => 'model', 'label' => 'Model', 'hint' => '5 meja'],
                                ['id' => 'meja', 'label' => 'Meja', 'hint' => 'antrian'],
                                ['id' => 'ragu', 'label' => 'Ragu', 'hint' => 'ke Dina'],
                            ],
                            [
                                ['from' => 'adu', 'to' => 'model'],
                                ['from' => 'model', 'to' => 'meja', 'label' => 'yakin'],
                                ['from' => 'model', 'to' => 'ragu', 'label' => 'ragu'],
                            ],
                            4,
                        ),
                        $this->heading('Siapa di ruangan'),
                        $this->table(
                            ['Orang', 'Peran pada kasus ini'],
                            [
                                ['Ibu Lestari', 'Kepala dinas. Pemilik metrik bisnis: waktu sampai meja yang benar.'],
                                ['Dina', 'Meja depan. Rujukan hari ini, dan penerima pengaduan yang modelnya ragu.'],
                                ['Bima', 'Ilmuwan data. Menyerahkan model, metrik, dan catatan label. Tidak memasang ke produksi.'],
                                ['Raka', 'Insinyur data. Menyiapkan antrean dari saluran ke gudang. Belum ke layanan prediksi.'],
                                ['Kamu', 'Insinyur AI. Meninjau, memasang, merawat, dan menutup risiko operasional.'],
                            ],
                        ),
                        $this->paragraph('Bima sudah menyerahkan dua kandidat: aturan kata kunci plus regresi logistik sebagai baseline, dan encoder bahasa Indonesia. Keduanya belum terpasang. Lingkungan dinas tidak memakai layanan model publik untuk teks mentah warga.'),
                    ],
                ),
                $this->textLesson(
                    'Posisi insinyur AI di antara ilmuwan data dan insinyur data',
                    'Kamu tidak menggantikan Bima atau Raka. Kamu yang memegang keputusan layak pasang.',
                    30,
                    [
                        $this->heading('Tiga pekerjaan, satu solusi', 1),
                        $this->paragraph('Definisi okupasi ini sempit: merancang, mengembangkan, dan mengintegrasikan solusi AI ke sistem operasional supaya tujuan organisasi tercapai. Di Arunika, tujuan itu adalah pengaduan sampai ke meja yang benar, dengan data warga tetap terlindungi.'),
                        $this->paragraph('Bima menjawab “model mana yang lebih baik di data uji”. Raka menjawab “teks dari tiga saluran sampai ke gudang tanpa tercecer”. Kamu menjawab “versi mana yang boleh memengaruhi antrian warga, dengan syarat apa, dan bagaimana ia dimatikan jika merugikan”.'),
                        $this->heading('Yang kamu pegang, meski timnya hanya tiga orang'),
                        $this->bullets([
                            'Keputusan layak pasang, tunda, atau tolak. Bukan Ibu Lestari yang membaca confusion matrix, dan bukan Bima yang menandatangani risiko produksi.',
                            'Jadwal yang mengikat ketergantungan: label bersih, antrean Raka, persetujuan tujuan pemrosesan, jendela mode bayangan.',
                            'Gerbang rilis: artefak model hanya naik jika metrik kelas mahal dan uji kebocoran lulus.',
                            'Mitigasi risiko produksi: salah routing, data pribadi di log, dan model yang diam-diam sudah usang.',
                        ]),
                        $this->paragraph('“Memimpin tim insinyur AI” pada kasus ini bukan bagan organisasi. Artinya kamu yang menolak encoder hanya karena macro-F1-nya naik 0,04, jika recall infrastruktur justru jatuh. Kepemimpinan di okupasi ini adalah keputusan yang bisa dijelaskan, bukan gelar.'),
                        $this->table(
                            ['Artefak', 'Penulis pertama', 'Yang menandatangani layak pakai'],
                            [
                                ['Laporan metrik uji', 'Bima', 'Kamu, terhadap sasaran teknis'],
                                ['Kontrak antrean data', 'Raka', 'Kamu, terhadap bidang yang boleh masuk model'],
                                ['Sasaran teknis dan rencana', 'Kamu', 'Ibu Lestari, untuk metrik bisnis'],
                                ['Program kerja data pribadi', 'Kamu, bersama petugas yang ditunjuk dinas', 'Pemilik tujuan pemrosesan'],
                            ],
                        ),
                        $this->paragraph('Kalau ketiga peran dilebur jadi “tim AI”, tidak ada yang menjaga data uji tetap jujur dan tidak ada yang menjaga layanan tetap bisa dimatikan. Course ini memisahkan pekerjaan itu dengan sengaja.'),
                    ],
                ),
                $this->textLesson(
                    'Menentukan sasaran teknis solusi AI',
                    'Unit J.62AIN00.002.1. Sasaran teknis adalah kontrak angka, bukan slogan akurasi.',
                    55,
                    [
                        $this->heading('Sasaran teknis, bukan keinginan', 1),
                        $this->paragraph('Unit J.62AIN00.002.1 meminta kamu menentukan sasaran teknis solusi AI. Di Arunika, kalimat “akurasi tinggi” belum sasaran. Sasaran menyebut masukan, saat keputusan, keluaran, batas gagal, dan lingkungan tempat ia hidup.'),
                        $this->paragraph('Masukan: teks pengaduan pada saat masuk, setelah identitas yang tidak diperlukan untuk routing dilepas. Keluaran: satu dari lima meja, atau ragu. Saat itu, Dina belum memindahkan tiket. Lingkungan: layanan di jaringan dinas, bukan model yang dikirim ke penyedia publik bersama NIK.'),
                        $this->heading('Angka yang layak ditulis sebelum melihat skor Bima'),
                        $this->table(
                            ['Sasaran', 'Ambang Arunika', 'Mengapa'],
                            [
                                ['Macro-F1', 'Mengalahkan aturan kata kunci Dina', 'Kelas timpang. Akurasi global menyembunyikan meja yang jarang.'],
                                ['Recall infrastruktur', 'Minimal 0,85', 'Jalan ambles yang terlewat adalah salah yang mahal.'],
                                ['Porsi ragu', 'Maksimal 20 persen', 'Di atas itu Dina tetap mengerjakan hampir semuanya.'],
                                ['Latensi p95', 'Maksimal 300 ms pada jam sibuk', 'Loket tidak boleh menunggu model.'],
                                ['Identitas di log', 'Nol NIK dan nomor HP', 'Log prediksi bukan arsip kependudukan.'],
                                ['Rollback', 'Di bawah 15 menit', 'Antrian warga harus bisa kembali ke Dina.'],
                            ],
                        ),
                        $this->diagram(
                            'Sasaran bergerak dari kebutuhan dinas ke batas yang membuat solusi belum siap.',
                            [
                                ['id' => 'bisnis', 'label' => 'Bisnis', 'hint' => 'meja benar'],
                                ['id' => 'teknis', 'label' => 'Teknis', 'hint' => 'angka'],
                                ['id' => 'batas', 'label' => 'Batas', 'hint' => 'lingkungan'],
                                ['id' => 'siap', 'label' => 'Siap', 'hint' => 'atau belum'],
                            ],
                            [
                                ['from' => 'bisnis', 'to' => 'teknis'],
                                ['from' => 'teknis', 'to' => 'batas'],
                                ['from' => 'batas', 'to' => 'siap'],
                            ],
                            4,
                        ),
                        $this->paragraph('Ibu Lestari tidak menandatangani macro-F1. Ia menandatangani waktu warga sampai meja yang benar dan jumlah hop antar-meja. Tugasmu menjembatani: setiap salah kelas harus bisa diceritakan sebagai hop ekstra atau bahaya yang tertunda. Tanpa jembatan itu, sasaran teknis hanya angka lomba.'),
                        $this->paragraph('Tulis juga syarat belum siap. Contoh: jika recall infrastruktur di bawah 0,85 pada data yang lebih baru daripada data latih, solusi tidak naik dari mode bayangan. Sasaran tanpa syarat berhenti adalah demo.'),
                        $this->quote('Artefak hari 1 bagian pertama: satu halaman sasaran teknis. Tiap baris punya ambang, pemilik, dan apa yang terjadi jika ambang pecah.'),
                    ],
                ),
                $this->textLesson(
                    'Kendala, lingkungan, dan kriteria belum siap',
                    'Kendala yang tidak ditulis akan muncul sebagai kejutan pada hari pasang.',
                    45,
                    [
                        $this->heading('Lingkungan yang nyata', 1),
                        $this->paragraph('Dinas Arunika tidak punya GPU bersama, dan tidak boleh mengirim teks pengaduan mentah ke layanan model di luar jaringan. Raka bisa menyediakan antrean dan satu layanan prediksi kecil. Encoder yang “hanya butuh satu kartu” sudah gagal di sasaran lingkungan, sebelum gagal di metrik.'),
                        $this->paragraph('SP4N-LAPOR adalah saluran nasional. Korpusnya bukan bahan yang boleh kamu salin ke notebook pribadi atau ke penyedia model publik. WhatsApp resmi membawa nomor pengirim. Loket membawa identitas di kolom terpisah. Sasaran teknis harus menyebut bidang mana yang berhenti di pintu model.'),
                        $this->heading('Daftar belum siap yang sehat'),
                        $this->bullets([
                            'Lima meja belum disepakati. Sebagian label historis berisi nama petugas, bukan jenis layanan.',
                            'Teks latih memuat kalimat resolusi yang tidak ada saat pengaduan masuk.',
                            'Tidak ada pemilik metrik setelah Dina cuti. Model tanpa pemilik adalah risiko, bukan solusi.',
                            'Pimpinan meminta balasan otomatis ke warga, padahal yang bisa dipertanggungjawabkan baru routing.',
                            'Satu-satunya cara memenuhi latensi adalah mengirim teks ke luar jaringan dinas.',
                        ]),
                        $this->paragraph('Pengaduan campur butuh aturan sebelum butuh model. “Lampu jalan mati dan sampah menumpuk”: bahaya fisik menang, jadi infrastruktur, kecuali skor dua kelas sama-sama rendah — maka ragu ke Dina. Aturan ini ditulis di sasaran, bukan disembunyikan di dalam bobot.'),
                        $this->paragraph('Kriteria belum siap adalah bagian dari sasaran, bukan catatan malu. Pada banyak dinas, hasil jujur hari pertama adalah tunda. Itu tetap unjuk kerja unit ini, selama alasan tunda terukur.'),
                    ],
                ),
                $this->textLesson(
                    'Membuat rencana proyek solusi AI',
                    'Unit J.62AIN00.004.1. Rencana mengikat orang, urutan, dan hal yang sengaja tidak dikerjakan.',
                    55,
                    [
                        $this->heading('Rencana yang bisa ditolak', 1),
                        $this->paragraph('Unit J.62AIN00.004.1 meminta rencana proyek solusi AI. Rencana Arunika bukan daftar harapan. Ia punya lingkup, di luar lingkup, ketergantungan, dan urutan yang tidak boleh dibalik.'),
                        $this->paragraph('Di dalam lingkup: routing lima meja, mode bayangan, gerbang metrik, program kerja data pribadi untuk alur ini, dan cara kembali ke antrian manual. Di luar lingkup: membalas warga, mengubah aplikasi SP4N-LAPOR, melatih model bahasa baru, dan mengganti Dina.'),
                        $this->heading('Urutan yang tidak boleh dibalik'),
                        $this->numbered([
                            'Sasaran teknis ditandatangani Ibu Lestari, termasuk ambang infrastruktur dan larangan identitas di log.',
                            'Bima membersihkan kebocoran dan label nama petugas, lalu menyerahkan ulang metrik pada data yang lebih baru.',
                            'Raka menyiapkan layanan prediksi di jaringan dinas, dengan tombol yang mengembalikan trafik ke Dina.',
                            'Mode bayangan berjalan tanpa mengubah antrian warga.',
                            'Kamu menulis keputusan naik, tunda, atau tolak. Laporan akhir baru menyusul keputusan itu.',
                        ]),
                        $this->diagram(
                            'Rencana adalah urutan. Melompat ke pasang membuat review jadi hiasan.',
                            [
                                ['id' => 'lingkup', 'label' => 'Lingkup', 'hint' => 'dan di luar'],
                                ['id' => 'urut', 'label' => 'Urutan', 'hint' => 'ketergantungan'],
                                ['id' => 'orang', 'label' => 'Orang', 'hint' => 'pemilik'],
                                ['id' => 'pintu', 'label' => 'Gerbang', 'hint' => 'naik atau tidak'],
                            ],
                            [
                                ['from' => 'lingkup', 'to' => 'urut'],
                                ['from' => 'urut', 'to' => 'orang'],
                                ['from' => 'orang', 'to' => 'pintu'],
                            ],
                            4,
                        ),
                        $this->paragraph('Ketergantungan ditulis sebagai nama. “Tim data” bukan ketergantungan. “Bima menyerahkan data uji tanpa kalimat resolusi, paling lambat Jumat” adalah ketergantungan. “Raka menyediakan bendera rollback yang kamu yang memegang” juga.'),
                        $this->paragraph('Rencana yang hanya bertanggal “minggu depan go-live” mengabaikan unit ini. Go-live adalah hasil gerbang, bukan tanggal yang dipilih di slide.'),
                        $this->quote('Artefak hari 1 bagian kedua: rencana satu sampai dua halaman. Lingkup, di luar lingkup, lima langkah urut, dan nama pada tiap ketergantungan.'),
                    ],
                ),
                $this->textLesson(
                    'Jadwal, dependensi, dan risiko',
                    'Tiga hari kursus menghasilkan rencana. Pengerjaan dinas lebih panjang daripada tiga hari.',
                    40,
                    [
                        $this->heading('Enam minggu setelah sasaran disepakati', 1),
                        $this->paragraph('Jangan memampatkan pekerjaan dinas ke dalam tiga hari Lesson. Tiga hari ini untuk menulis rencana yang jujur. Contoh irama Arunika setelahnya: minggu 1 bersihkan data dan ulangi metrik; minggu 2 layanan prediksi dan uji rollback; minggu 3–4 mode bayangan sepuluh hari kerja; minggu 5 keputusan naik atau tunda; minggu 6 laporan akhir dan program kerja data pribadi yang sudah dijalankan, bukan yang baru dijanjikan.'),
                        $this->paragraph('Musim hujan tidak menunggu jadwalmu. Jika mode bayangan jatuh di puncak hujan, distribusi infrastruktur dan kebersihan bergeser. Itu bukan gangguan. Itu data yang lebih jujur daripada data uji musim kemarau. Rencana menyebutnya, bukan menghindarinya.'),
                        $this->heading('Risiko yang sudah kelihatan'),
                        $this->table(
                            ['Risiko', 'Tanda', 'Mitigasi', 'Pemilik'],
                            [
                                ['Kebocoran teks resolusi', 'Skor uji jauh di atas mode bayangan', 'Buang kalimat yang baru ada setelah ditangani', 'Bima'],
                                ['NIK di log', 'Contoh log memuat 16 digit', 'Potong identitas sebelum layanan prediksi', 'Raka dan kamu'],
                                ['Encoder menggoda', 'Macro-F1 naik, recall infrastruktur turun', 'Gerbang kelas mahal, bukan gerbang rata-rata', 'Kamu'],
                                ['Saluran nasional', 'Ada yang meminta ekspor korpus LAPOR', 'Tolak. Latih dari arsip yang memang wewenang dinas', 'Kamu'],
                            ],
                        ),
                        $this->paragraph('Risiko tanpa pemilik akan jatuh ke Dina pada hari pertama produksi. Itu bukan mitigasi. Itu pemindahan beban ke petugas yang tidak ikut menulis rencana.'),
                    ],
                ),
                $this->textLesson(
                    'Artefak hari 1: sasaran dan rencana',
                    'Dua dokumen, dua unit. Sisa waktu lesson ini untuk menulis, bukan untuk membaca ulang.',
                    130,
                    [
                        $this->heading('Apa yang kamu kumpulkan', 1),
                        $this->paragraph('Hari 1 ditutup oleh dua artefak pada kasus Arunika, bukan ringkasan Lesson. Keduanya menjadi lampiran tugas. Kuis tidak menggantikan keduanya. Sisa waktu lesson ini dipakai di tugas.'),
                        $this->heading('Sasaran teknis, unit J.62AIN00.002.1'),
                        $this->bullets([
                            'Keputusan, masukan, saat, dan keluaran, termasuk kelas ragu.',
                            'Enam ambang: macro-F1 terhadap aturan, recall infrastruktur, porsi ragu, latensi, identitas di log, waktu rollback.',
                            'Lingkungan: jaringan dinas, bidang yang tidak boleh masuk model.',
                            'Satu kalimat belum siap, yang jika terpenuhi membuat pemasangan berhenti.',
                        ]),
                        $this->heading('Rencana proyek, unit J.62AIN00.004.1'),
                        $this->bullets([
                            'Di dalam lingkup dan di luar lingkup. Balasan otomatis ke warga ada di luar.',
                            'Urutan lima langkah dengan nama ketergantungan.',
                            'Jadwal enam minggu yang tidak memampatkan mode bayangan menjadi satu hari.',
                            'Empat risiko dengan tanda, mitigasi, dan pemilik.',
                        ]),
                        $this->paragraph('Artefak yang lemah berbunyi “model akan dipasang minggu depan dengan akurasi maksimal”. Artefak yang cukup membuat Ibu Lestari bisa menolak tanggal itu tanpa harus memahami training loss. Hari 2 memakai ambang di halaman ini untuk mengadili model Bima. Kalau angkanya belum ada, review besok hanya opini.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionHariDua(): array
    {
        return [
            'title' => 'Hari 2. Meninjau model dan proyek',
            'description' => 'Tujuh jam. Tinjau pemodelan dan proyek data science terhadap sasaran hari 1. Hasilnya berita acara, bukan perasaan bahwa modelnya bagus.',
            'lessons' => [
                $this->textLesson(
                    'Paket model yang kamu terima',
                    'Bima menyerahkan kandidat, bukan izin produksi. Angka yang dinilai ada di tugas, bukan di lesson ini.',
                    25,
                    [
                        $this->heading('Isi paket, dan yang tidak ada', 1),
                        $this->paragraph('Pagi hari 2, Bima meletakkan paket: catatan label lima meja, notebook evaluasi, dua artefak model, dan satu lembar metrik. Baseline adalah aturan kata kunci ditambah regresi logistik. Kandidat kedua adalah encoder bahasa Indonesia. Kamu tidak membuka pelatihan baru di Lesson ini.'),
                        $this->paragraph('Lembar metrik yang kamu adili tidak ada di Lesson ini. Ia ada di Tugas Hari 2, dengan nama paket siang. Di sini kamu belajar cara membaca lembar itu: macro-F1, recall kelas mahal, porsi ragu, dan apakah angkanya diukur pada data yang jujur. Angka di kuis adalah latihan aturan. Angka paket siang adalah yang masuk berita acara. Kedua angka itu tidak sama.'),
                        $this->heading('Yang belum ada di paket'),
                        $this->bullets([
                            'Daftar bidang yang dipakai saat prediksi, dibedakan dari bidang yang hanya ada di arsip setelah ditangani.',
                            'Contoh salah pada kelas infrastruktur, bukan hanya rata-rata.',
                            'Kontrak layanan: masukan, keluaran, versi, dan batas waktu.',
                            'Pernyataan bahwa NIK tidak masuk fitur dan tidak masuk log.',
                        ]),
                        $this->paragraph('Ilmuwan data yang baik menyerahkan ketidakpastian bersama model. Tugas review adalah membuat ketidakpastian itu konkret, lalu membandingkannya dengan ambang yang sudah ditandatangani. Jangan menambah model ketiga hanya karena dua angka ini belum nyaman.'),
                    ],
                ),
                $this->textLesson(
                    'Review pemodelan: data, fitur, dan metrik',
                    'Unit J.62DMI00.015.1. Review pemodelan mengadili data dan metrik, bukan selera arsitektur.',
                    55,
                    [
                        $this->heading('Empat pertanyaan review', 1),
                        $this->paragraph('Unit J.62DMI00.015.1 meminta proses review pemodelan. Pada Arunika, empat pertanyaan cukup, dan semuanya harus dijawab dengan bukti dari paket Bima, bukan dengan nama algoritma.'),
                        $this->numbered([
                            'Apakah labelnya jenis layanan, atau nama petugas yang kebetulan memegang meja itu bulan lalu?',
                            'Apakah fitur akan ada pada saat pengaduan masuk? Kalimat “sudah diteruskan ke bidang PU” baru ada setelah ditangani. Itu kebocoran.',
                            'Apakah metrik yang dilaporkan adalah metrik sasaran? Macro-F1 tanpa recall infrastruktur belum menjawab Ibu Lestari.',
                            'Apakah data uji lebih baru daripada data latih, atau diacak dari minggu yang sama?',
                        ]),
                        $this->paragraph('Bentuk temuan yang sah kira-kira begini. Sebuah baris latih memuat kalimat yang baru ada setelah petugas menangani pengaduan, misalnya “sudah diteruskan ke bidang PU”. Itu kebocoran: fitur itu tidak ada pada saat keputusan. Label lama yang berisi “ke Dina” atau nama staf, bukan nama meja, mengajarkan model pada giliran jaga, bukan pada jenis layanan. Kamu baru boleh menulis temuan itu di berita acara jika kamu mengutip barisnya dari paket siang. Lesson ini tidak memberi kutipan paket itu.'),
                        $this->diagram(
                            'Review berjalan dari data ke putusan. Putusan belum tentu layak pasang.',
                            [
                                ['id' => 'data', 'label' => 'Data', 'hint' => 'label dan bocor'],
                                ['id' => 'metrik', 'label' => 'Metrik', 'hint' => 'kelas mahal'],
                                ['id' => 'batas', 'label' => 'Batas', 'hint' => 'sasaran hari 1'],
                                ['id' => 'putusan', 'label' => 'Putusan', 'hint' => 'layak atau tidak'],
                            ],
                            [
                                ['from' => 'data', 'to' => 'metrik'],
                                ['from' => 'metrik', 'to' => 'batas'],
                                ['from' => 'batas', 'to' => 'putusan'],
                            ],
                            4,
                        ),
                        $this->paragraph('Split acak pada pengaduan yang temanya musiman akan membocorkan bahasa musim hujan ke data uji. Review yang hanya menulis “akurasi 75 persen” belum melakukan unit ini. Review yang menunjukkan satu baris bocor, satu label salah jenis, dan satu metrik yang tidak ada di sasaran, sudah mulai cukup.'),
                        $this->quote('Tuliskan kutipan baris bocornya. Tanpa kutipan, temuan kebocoran hanya tuduhan.'),
                    ],
                ),
                $this->textLesson(
                    'Bias, keterbatasan, dan keputusan layak pasang',
                    'Kenaikan rata-rata yang mengorbankan kelas mahal adalah alasan menolak, bukan alasan merayakan.',
                    40,
                    [
                        $this->heading('Cara memutuskan dua kandidat', 1),
                        $this->paragraph('Urutannya tetap. Pertama, bandingkan recall kelas mahal dengan ambang hari 1. Kenaikan macro-F1 tidak menebus kelas mahal yang jatuh di bawah ambang. Kedua, tandai angka yang belum sah: data bocor, label salah jenis, atau uji yang diacak dari minggu yang sama dengan latih. Angka yang belum sah tidak boleh ditulis sebagai kelulusan, walaupun ia kelihatan melewati ambang. Ketiga, porsi ragu yang melewati batas berarti Dina tetap mengerjakan hampir semuanya, dan itu juga gagal sasaran.'),
                        $this->paragraph('Tiga hasil itu tidak sama. Kandidat yang gagal kelas mahal pada data yang sudah jujur ditolak untuk dinaikkan. Kandidat yang angkanya belum sah ditunda, bukan diluluskan dan bukan serta-merta ditolak selamanya. Kandidat yang lolos angka pada data yang jujur masih harus menunggu gerbang pemasangan di hari 3. Putusan paket siang kamu tulis sendiri di tugas. Lesson ini tidak menyimpannya.'),
                        $this->heading('Salah yang tidak rata'),
                        $this->bullets([
                            '“jalan di gang melati ambles” yang masuk kebersihan menunda bahaya. Ini salah mahal.',
                            'Pengaduan kebersihan yang masuk infrastruktur menyita waktu bidang yang sedang siaga. Mengganggu, tidak sebahaya.',
                            'Teks pendek dari WhatsApp (“ambles gan”) lebih rapuh daripada kalimat loket yang lengkap. Laporkan metrik per saluran, bukan satu angka.',
                            'Pengaduan campur tidak boleh dipaksa. Ragu adalah keluaran yang sah.',
                        ]),
                        $this->paragraph('Bias di sini bukan kuliah moral yang terpisah dari antrian. Warga yang menulis singkat, atau yang mengadu dalam bahasa campur, lebih sering jatuh ke kelas mayoritas. Jika kelas mayoritasnya kependudukan, pengaduan infrastruktur yang pendek tertelan. Itu alasan gerbang recall, bukan paragraf etika yang menempel di lampiran.'),
                        $this->paragraph('Keterbatasan ditulis untuk pembaca yang tidak melihat notebook: model membaca teks, bukan foto jalan; model tidak tahu giliran jaga petugas; model tidak boleh melihat NIK. Keterbatasan yang tidak ditulis akan diperlakukan sebagai kemampuan oleh orang yang memasang slide.'),
                    ],
                ),
                $this->textLesson(
                    'Review proyek data science',
                    'Unit J.62DMI00.020.1. Meninjau proyek bukan mengulang review model.',
                    50,
                    [
                        $this->heading('Proyeknya yang ditinjau', 1),
                        $this->paragraph('Unit J.62DMI00.020.1 meminta review proyek data science. Model bisa rapi sementara proyeknya belum layak. Review model bertanya “apakah kandidat ini jujur terhadap data”. Review proyek bertanya “apakah pekerjaan ini terikat pada keputusan dinas, orangnya nyata, dan hasilnya bisa dioperasikan tanpa penulisnya duduk di sebelah”.'),
                        $this->heading('Celah yang kamu cari'),
                        $this->table(
                            ['Yang dijanjikan paket', 'Yang diminta sasaran dan rencana', 'Yang kamu tulis jika tidak ketemu'],
                            [
                                ['Metrik di lembar atau di slide', 'Ambang hari 1, termasuk kelas mahal', 'Celah: metrik yang dilaporkan bukan metrik yang ditandatangani'],
                                ['Bentuk serahan', 'Layanan yang bisa dimatikan', 'Celah: serahan tidak bisa dipasang di loket'],
                                ['Asal data', 'Wewenang dan tujuan pemrosesan', 'Celah: korpus yang belum disebut pemiliknya'],
                                ['Tanggal naik', 'Urutan di rencana, termasuk mode bayangan', 'Celah: jadwal melompati bukti'],
                            ],
                        ),
                        $this->diagram(
                            'Review proyek menelusuri janji sampai ke celah, lalu ke berita acara.',
                            [
                                ['id' => 'janji', 'label' => 'Janji', 'hint' => 'piagam dan jadwal'],
                                ['id' => 'bukti', 'label' => 'Bukti', 'hint' => 'artefak nyata'],
                                ['id' => 'celah', 'label' => 'Celah', 'hint' => 'terhadap sasaran'],
                                ['id' => 'acara', 'label' => 'Acara', 'hint' => 'keputusan'],
                            ],
                            [
                                ['from' => 'janji', 'to' => 'bukti'],
                                ['from' => 'bukti', 'to' => 'celah'],
                                ['from' => 'celah', 'to' => 'acara'],
                            ],
                            4,
                        ),
                        $this->paragraph('Jangan menolak proyek hanya karena encoder kalah. Jangan meluluskan proyek hanya karena Bima bekerja keras. Unjuk kerja unit ini adalah celah yang ditunjuk ke dokumen, plus tindakan yang punya nama dan tanggal.'),
                    ],
                ),
                $this->textLesson(
                    'Menyelaraskan artefak ke sasaran hari 1',
                    'Setiap ambang hari 1 harus ketemu bukti, atau ketemu kalimat celah.',
                    35,
                    [
                        $this->heading('Jejak dari ambang ke bukti', 1),
                        $this->paragraph('Sasaran yang tidak dipakai untuk mengadili paket hanyalah arsip hari 1. Buat jejak. Satu baris untuk tiap ambang. Tiga status yang sah: lolos, celah, dan belum sah. Lolos berarti bukti ada dan datanya jujur. Celah berarti bukti tidak ada. Belum sah berarti angka ada, tetapi datanya bocor, labelnya salah jenis, atau ujinya tidak terpisah dari latih.'),
                        $this->table(
                            ['Ambang hari 1', 'Bukti di paket siang', 'Status'],
                            [
                                ['Tulis ambangnya', 'Kutip angka, atau tulis bahwa bukti tidak ada', 'lolos, celah, atau belum sah'],
                                ['Satu baris tiap ambang', 'Jangan merata-rata menjadi satu kesan', 'Jangan menyalin status dari Lesson lain'],
                            ],
                        ),
                        $this->paragraph('Status belum sah berbeda dari gagal. Angka yang kelihatan memenuhi ambang pada data bocor masuk sebagai angka yang harus diulang, bukan sebagai kelulusan. Jejak ini yang membuat review bisa dibaca Ibu Lestari. Ia tidak perlu membuka notebook untuk melihat ambang mana yang belum punya bukti sah.'),
                    ],
                ),
                $this->textLesson(
                    'Berita acara: layak, tunda, atau tolak',
                    'Tiga putusan. Paket siang yang menentukan layak, tunda, atau tolak.',
                    45,
                    [
                        $this->heading('Tiga putusan yang sah', 1),
                        $this->paragraph('Berita acara review hanya memakai tiga putusan. Layak pasang: semua ambang punya bukti sah dan rollback sudah diuji. Tunda: arahnya masih benar, tetapi bukti belum sah atau lingkungan belum ada. Tolak: sasaran salah, atau kandidat gagal ambang kelas mahal pada data yang sudah jujur, atau solusi yang diminta bukan routing yang bisa dipertanggungjawabkan.'),
                        $this->paragraph('Putusan paket siang tidak ditulis di Lesson ini. Kamu yang menentukannya dari angka di tugas. Mode bayangan hanya boleh dijadwalkan jika berita acara mengizinkannya, dengan syarat yang jika gagal mengubah putusan. Itu bukan “kurang lebih setuju”.'),
                        $this->heading('Isi berita acara'),
                        $this->numbered([
                            'Identitas paket: versi, tanggal serah, nama Bima, data sampai tanggal berapa.',
                            'Temuan, masing-masing dengan kutipan atau angka. Satu temuan, satu bukti.',
                            'Jejak ambang hari 1. Jangan merata-rata menjadi “cukup baik”.',
                            'Putusan tunda, plus syarat yang jika gagal mengubah putusan menjadi tolak.',
                            'Yang tidak diputuskan: tidak ada izin membalas warga, tidak ada izin ekspor korpus.',
                        ]),
                        $this->paragraph('Berita acara yang lemah: “model cukup menjanjikan, disarankan dilanjutkan”. Berita acara yang cukup bisa dibaca asesor nanti sebagai bukti bahwa kamu meninjau, bukan bahwa kamu percaya. Bukti itu harus valid, asli, terkini, dan memadai. Angka dari notebook orang lain tanpa kamu periksa sumbernya belum memadai.'),
                        $this->paragraph('Kamu menulis dua berita acara, bukan satu. Pertama review pemodelan. Kedua review proyek. Putusannya boleh sama-sama tunda, alasannya tidak boleh disalin mentah. Yang pertama tentang data dan metrik. Yang kedua tentang jadwal, serahan, dan pemilik.'),
                    ],
                ),
                $this->textLesson(
                    'Artefak hari 2: dua berita acara',
                    'Dua dokumen pendek. Putusan di kalimat pertama, bukti di bawahnya. Sisa waktu lesson ini untuk menulis.',
                    170,
                    [
                        $this->heading('Bentuk yang bisa dinilai', 1),
                        $this->paragraph('Tugas hari 2 adalah dua berita acara pada paket siang. Angkanya ada di pertanyaan tugas, tidak di Lesson ini, dan tidak sama dengan latihan di kuis. Jangan mengganti kasus. Jangan menambah kandidat model ketiga. Tandai angka yang belum sah. Sisa waktu lesson ini dipakai untuk menulis kedua dokumen itu.'),
                        $this->heading('Review pemodelan, unit J.62DMI00.015.1'),
                        $this->bullets([
                            'Kalimat pertama: putusan terhadap tiap kandidat di paket siang.',
                            'Kutipan hanya jika paketnya memuat baris itu. Jangan mengarang kutipan dari contoh Lesson.',
                            'Recall infrastruktur dan macro-F1, dibaca terhadap ambang hari 1, dengan status sah atau belum sah.',
                        ]),
                        $this->heading('Review proyek, unit J.62DMI00.020.1'),
                        $this->bullets([
                            'Celah antara yang dijanjikan paket siang dan yang diminta sasaran serta rencana.',
                            'Nama pemilik tiap tindakan perbaikan.',
                            'Syarat yang mengubah tunda menjadi tolak. Contoh bentuknya: identitas tetap masuk log setelah diperbaiki sekali.',
                        ]),
                        $this->paragraph('Pembuka yang cukup menyebut putusan untuk tiap kandidat, ambang yang dipakai, dan apakah angkanya sah. Pembuka yang gagal berbunyi “kedua model memiliki kelebihan dan kekurangan”, atau menyalin putusan dari soal kuis yang angkanya berbeda. Hari 3 hanya merancang pemasangan untuk jalur yang berita acara ini izinkan.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionHariTiga(): array
    {
        return [
            'title' => 'Hari 3. Memasang, merawat, dan menutup',
            'description' => 'Tujuh jam. Rancang pemasangan dan perawatan, program kerja pelindungan data pribadi, lalu tutup dengan laporan akhir.',
            'lessons' => [
                $this->textLesson(
                    'Memasang solusi AI ke layanan operasional',
                    'Unit J.62AIN00.015.1. Memasang adalah mengubah artefak menjadi layanan yang bisa dimatikan.',
                    50,
                    [
                        $this->heading('Yang boleh dipasang hari ini', 1),
                        $this->paragraph('Unit J.62AIN00.015.1 meminta pemasangan solusi AI. Yang boleh kamu rancang mengikuti putusan berita acara hari 2. Putusan tunda berarti layanan siap untuk mode bayangan, dan syarat yang belum terpenuhi ditulis sebagai gerbang. Putusan tolak berarti kamu menulis cara jalur itu tetap mati, bukan jadwal promosi. Putusan layak tetap melewati gerbang di bawah sebelum antrian warga berubah. Lesson ini tidak membuka konsol dan tidak meminta kamu men-deploy.'),
                        $this->paragraph('Kontraknya sempit. Masukan: teks pengaduan yang sudah dipotong dari NIK, nomor HP, dan nama. Keluaran: kode meja, skor, versi model, dan tanda ragu. Jika identitas masih menempel, layanan menolak permintaan. Itu bagian dari pemasangan, bukan urusan nanti.'),
                        $this->heading('Gerbang sebelum artefak boleh naik'),
                        $this->numbered([
                            'Uji kebocoran kosong: tidak ada kalimat resolusi di data yang dipakai menghitung metrik rilis.',
                            'Recall infrastruktur pada data yang lebih baru masih memenuhi ambang, atau rilis dibatalkan.',
                            'Uji kontrak: permintaan dengan NIK ditolak; permintaan bersih mengembalikan satu meja atau ragu.',
                            'Latihan rollback sudah dicatat waktunya, di bawah 15 menit.',
                        ]),
                        $this->diagram(
                            'Artefak hanya naik jika gerbang lulus. Produksi hanya dibuka jika berita acara mengizinkan.',
                            [
                                ['id' => 'artefak', 'label' => 'Artefak', 'hint' => 'versi'],
                                ['id' => 'gerbang', 'label' => 'Gerbang', 'hint' => 'uji metrik'],
                                ['id' => 'bayang', 'label' => 'Bayangan', 'hint' => 'tanpa ubah antrian'],
                                ['id' => 'produksi', 'label' => 'Produksi', 'hint' => 'sesuai acara'],
                            ],
                            [
                                ['from' => 'artefak', 'to' => 'gerbang'],
                                ['from' => 'gerbang', 'to' => 'bayang'],
                                ['from' => 'bayang', 'to' => 'produksi'],
                            ],
                            4,
                        ),
                        $this->paragraph('Praktik rilis yang diminta okupasi ini adalah pipeline pendek: uji, bungkus artefak, naik ke bayangan, baru promosi. Bukan platform yang namanya panjang. Raka yang mengoperasikan antrean. Kamu yang memegang syarat naik dan catatan versi. Bima tidak mendorong file model ke server loket lewat pesan singkat.'),
                        $this->paragraph('Pengaduan campur tetap menghasilkan ragu jika dua skor tertinggi berdekatan. Aturan “bahaya fisik menang” hidup di layanan sebagai aturan yang bisa dibaca, bukan sebagai kejutan di dalam bobot. Petugas yang mengaudit harus bisa menunjukkan aturannya tanpa membuka notebook.'),
                    ],
                ),
                $this->textLesson(
                    'Health check, mode bayangan, dan rollback',
                    'Mode bayangan mengukur akibat tanpa menagih warga sebagai kelinci percobaan.',
                    40,
                    [
                        $this->heading('Sepuluh hari kerja berdampingan', 1),
                        $this->paragraph('Mode bayangan berarti layanan memprediksi pada pengaduan yang sama dengan yang Dina arahkan, tanpa mengubah antrian. Sepuluh hari kerja, termasuk jika hujan. Setiap pagi kamu membaca empat angka: versi yang hidup, p95 latensi, kadar galat layanan, dan persen ragu. Setiap sore kamu membandingkan meja model dengan meja Dina pada kelas infrastruktur.'),
                        $this->paragraph('Promosi ke produksi hanya jika selama jendela itu recall infrastruktur pada kesepakatan dengan Dina tetap di atas ambang, identitas tidak muncul di log, dan rollback sudah diuji sekali pada jam sibuk. Satu hari yang bagus tidak cukup. Musim tidak bergerak dalam satu hari.'),
                        $this->heading('Health yang berguna'),
                        $this->bullets([
                            'Versi model dan tanggal artefak. Prediksi tanpa versi tidak bisa diulang saat ada keluhan.',
                            'Jumlah permintaan ditolak karena identitas. Nol terus-menerus patut dicurigai: bisa jadi pemotongnya tidak jalan.',
                            'Persen ragu per saluran. WhatsApp yang tiba-tiba nol ragu biasanya tanda salah.',
                            'Waktu terakhir rollback dicoba, dan berapa lama trafik kembali ke Dina.',
                        ]),
                        $this->paragraph('Rollback adalah bendera trafik, bukan pembangunan ulang. Raka mengalihkan antrean ke jalur manual. Kamu mencatat jam mulai dan jam antrian warga kembali normal. Jika caranya “telepon Raka lalu edit konfigurasi yang tidak disimpan”, kamu belum memasang solusi. Kamu memasang ketergantungan pada ingatan satu orang.'),
                    ],
                ),
                $this->textLesson(
                    'Merawat solusi AI',
                    'Unit J.62AIN00.017.1. Perawatan adalah jadwal dan pemilik, bukan harapan bahwa model tetap pintar.',
                    40,
                    [
                        $this->heading('Jadwal setelah versi pertama hidup', 1),
                        $this->paragraph('Unit J.62AIN00.017.1 meminta perawatan solusi AI. Di Arunika, perawatan dimulai bahkan saat solusi masih di mode bayangan. Tidak ada masa “nanti setelah go-live baru kami pantau”.'),
                        $this->table(
                            ['Irama', 'Yang dilihat', 'Siapa'],
                            [
                                ['Harian', 'Latensi, galat, persen ragu, versi', 'Kamu'],
                                ['Mingguan', 'Macro-F1 dan recall infrastruktur pada sampel yang Dina koreksi', 'Kamu, data dari Dina'],
                                ['Bulanan', 'Audit label baru: masih jenis layanan, atau sudah kembali jadi nama petugas', 'Bima'],
                                ['Tiap musim hujan', 'Pergeseran infrastruktur dan kebersihan, sebelum ambang pecah', 'Kamu'],
                                ['Triwulanan', 'Program kerja data pribadi masih sesuai praktik log', 'Kamu dan petugas yang ditunjuk'],
                            ],
                        ),
                        $this->diagram(
                            'Perawatan mengikat pantauan ke tindakan. Angka tanpa tindakan hanya dasbor.',
                            [
                                ['id' => 'pantau', 'label' => 'Pantau', 'hint' => 'angka harian'],
                                ['id' => 'geser', 'label' => 'Geser', 'hint' => 'musim dan saluran'],
                                ['id' => 'insiden', 'label' => 'Insiden', 'hint' => 'salah meja'],
                                ['id' => 'ulang', 'label' => 'Ulang', 'hint' => 'syarat latih'],
                            ],
                            [
                                ['from' => 'pantau', 'to' => 'geser'],
                                ['from' => 'geser', 'to' => 'insiden'],
                                ['from' => 'insiden', 'to' => 'ulang'],
                            ],
                            4,
                        ),
                        $this->paragraph('Insiden contoh: “jalan di gang melati ambles” masuk kebersihan pada mode bayangan. Karena masih bayangan, warga tidak dirugikan, tetapi insiden tetap dicatat. Tanpa catatan, pola yang sama akan lolos pada hari produksi. Perawatan yang hanya membanggakan uptime layanan belum merawat solusi AI. Layanan bisa hidup sementara keputusannya salah.'),
                    ],
                ),
                $this->textLesson(
                    'Drift pengaduan dan pemicu latih ulang',
                    'Latih ulang adalah keputusan, bukan kebiasaan tiap Jumat.',
                    35,
                    [
                        $this->heading('Apa yang bergeser di dinas', 1),
                        $this->paragraph('Hujan menaikkan infrastruktur dan kebersihan. Tahun dengan layanan kependudukan massal menaikkan kalimat KTP dan pindah. Saluran baru, misalnya formulir yang berubah di loket, mengubah bahasa pengaduan tanpa mengubah lima meja. Itu drift. Model tidak “rusak”. Distribusinya yang pergi.'),
                        $this->paragraph('Pemicu di runbook harus angka, dan angkanya diturunkan dari sasaran hari 1, bukan dari berita rilis model. Tiga bentuk yang sah: macro-F1 mingguan turun sekian dari titik mode bayangan yang diterima, recall kelas mahal jatuh di bawah ambang hari 1, atau saluran baru belum punya sampel di data latih. Salah satu saja sudah cukup untuk menghentikan promosi dan memanggil Bima. Kamu yang memiliki pemicu. Bima yang mengulang pelatihan. Kamu yang meninjau ulang sebelum versi baru naik. Angka pemicu untuk kasus ini kamu tulis di tugas.'),
                        $this->heading('Yang bukan pemicu'),
                        $this->bullets([
                            'Rilis model umum di berita. Arunika tidak ganti encoder karena ada yang baru.',
                            'Satu pengaduan salah yang Dina bisa jelaskan sebagai teks campur dan sudah jatuh ke ragu.',
                            'Permintaan pimpinan agar “sekarang bisa membalas warga”. Itu perubahan lingkup, bukan drift.',
                        ]),
                        $this->paragraph('Latih ulang tanpa review pemodelan mengulang kesalahan kemarin dengan data yang lebih banyak. Runbook menulis urutannya: pemicu, perbaikan data, metrik pada jendela yang lebih baru, berita acara singkat, baru promosi. Musim hujan adalah saat terburuk untuk melewatkan urutan itu, dan saat terbaik untuk mengujinya.'),
                    ],
                ),
                $this->textLesson(
                    'Program kerja pelindungan data pribadi',
                    'Unit J.62PDP00.011.1. Routing pengaduan adalah pemrosesan data pribadi, bukan sampingan teknis.',
                    75,
                    [
                        $this->heading('Tujuan yang sempit', 1),
                        $this->paragraph('Unit J.62PDP00.011.1 meminta kamu menerapkan program kerja pelindungan data pribadi yang sudah ada, bukan menyusun visi dinas dari nol. Pengaduan Arunika memuat nama, alamat, nomor, kadang NIK, dan kadang keadaan yang sensitif seperti bantuan sosial. Dinas adalah pengendali untuk pelayanan itu. Kamu tidak menjadi pengendali baru hanya karena menaruh model di jaringannya, tetapi rancanganmu bisa memperluas tujuan tanpa disadari.'),
                        $this->paragraph('Dasar yang paling pas untuk routing ini adalah pelaksanaan tugas dinas dalam pelayanan publik, bukan persetujuan yang disembunyikan di kaki halaman. UU 27/2022 Pasal 20 ayat (2) huruf e adalah rujukan di piagam. Persetujuan tetap bukan pengganti tujuan yang melebar. “Sudah klik setuju” tidak mengizinkan teks bantuan sosial dipakai melatih model publik.'),
                        $this->heading('Piagam yang kamu terapkan'),
                        $this->paragraph('Kepala dinas sudah menandatangani piagam sempit ini. Tugasmu memakai piagam itu pada alur routing, bukan menulis piagam baru.'),
                        $this->table(
                            ['Butir', 'Isi piagam Arunika'],
                            [
                                ['Pengendali', 'Dinas Pelayanan Publik Kota Arunika'],
                                ['Tujuan', 'Mengarahkan pengaduan ke satu dari lima meja'],
                                ['Dasar', 'Tugas pelayanan publik, UU 27/2022 Pasal 20 ayat (2) huruf e'],
                                ['Dilarang', 'Memprofil warga, mengekspor korpus, mengirim teks mentah ke luar jaringan dinas'],
                                ['Penerima', 'Dina, Bima, Raka, dan kamu. Tidak ada penyedia model di luar dinas'],
                                ['Pemilik', 'Petugas yang ditunjuk Kepala Dinas. Kamu menjaga log prediksi'],
                            ],
                        ),
                        $this->heading('Catatan kegiatan pemrosesan'),
                        $this->paragraph('Asesor mengenal catatan ini sebagai RoPA. Satu baris untuk tiap kegiatan. Kolomnya aktivitas, data, tujuan, dasar, penerima, dan retensi. Lesson ini tidak mengisi barisnya. Di tugas kamu mengisi lima baris untuk alur routing, lalu menerapkan piagam pada dua teks: “ktp saya hilang, mohon diterbitkan ulang” dan “bantuan sosial ibu saya tidak cair”. Untuk tiap teks, tulis apa yang dipotong sebelum prediksi, apa yang tetap di arsip dinas, dan apa yang tidak boleh keluar dari dinas.'),
                        $this->diagram(
                            'Program kerja mengikat tujuan sampai hak. Melewatkan satu kotak memperlebar pemrosesan.',
                            [
                                ['id' => 'tujuan', 'label' => 'Tujuan', 'hint' => 'routing saja'],
                                ['id' => 'dasar', 'label' => 'Dasar', 'hint' => 'tugas dinas'],
                                ['id' => 'retensi', 'label' => 'Retensi', 'hint' => 'tanpa salinan liar'],
                                ['id' => 'hak', 'label' => 'Hak', 'hint' => 'jalur yang ada'],
                            ],
                            [
                                ['from' => 'tujuan', 'to' => 'dasar'],
                                ['from' => 'dasar', 'to' => 'retensi'],
                                ['from' => 'retensi', 'to' => 'hak'],
                            ],
                            4,
                        ),
                        $this->paragraph('SP4N-LAPOR tidak menjadi alasan mengekspor korpus. Program kerja menulis tolak untuk permintaan “kirimkan CSV ke vendor agar modelnya lebih pintar”. Piagam sudah menunjuk pemilik. Jika praktik log menyimpang dari piagam, itu celah, dan celah itu tidak mengizinkan ekspor.'),
                    ],
                ),
                $this->textLesson(
                    'Retensi, hak subjek, dan insiden data',
                    'Salinan kedua di bucket model adalah pemrosesan baru, walaupun arsip dinasnya sah.',
                    30,
                    [
                        $this->heading('Jangan membuat umur data yang tidak tertulis', 1),
                        $this->paragraph('Arsip pengaduan mengikuti jadwal retensi dinas. Kamu tidak mengarang angka tahun di Lesson ini. Yang kamu pastikan: layanan prediksi tidak menyimpan salinan teks mentah yang umurnya tidak ada di jadwal itu. Fitur dan log yang masih bisa dipakai menunjuk ke seseorang dihapus atau dipotong sesuai program kerja, lebih cepat daripada arsip resmi jika tidak diperlukan untuk audit routing.'),
                        $this->paragraph('Hak subjek tidak diciptakan ulang di aplikasi model. Dinas sudah punya jalur melalui pelayanan dan melalui saluran pengaduan. Program kerja menambahkan satu hal: jika warga meminta akses atau penghapusan, kamu bisa menemukan apakah teks itu pernah masuk log prediksi, versi mana yang memprosesnya, dan bagaimana salinan teknis dihapus tanpa merusak arsip yang memang wajib disimpan.'),
                        $this->heading('Insiden yang sudah hampir terjadi'),
                        $this->paragraph('Contoh yang kamu tulis di latihan: satu baris log menyimpan nomor HP karena pemotong hanya mencari pola NIK. Itu insiden pada rancangan, walaupun belum keluar dari jaringan dinas. Catatan insiden menyebut apa yang bocor, berapa lama, siapa yang diakses lognya, apa yang dipotong hari itu, dan apa yang berubah di uji kontrak besoknya. “Sudah kami hapus” tanpa uji yang mencegah nomor HP berikutnya adalah tutup buku, bukan perawatan.'),
                        $this->paragraph('Bantuan sosial yang tidak cair tetap kelas meja sosial. Isinya tidak boleh muncul di contoh Lesson publik, di slide vendor, atau di notebook yang disimpan di luar dinas. Gunakan pengaduan latihan yang sudah dianonimkan, seperti yang dipakai Course ini. Penerapan dua teks itu pada piagam ditulis di tugas, bukan di sini.'),
                    ],
                ),
                $this->textLesson(
                    'Laporan akhir proyek data science',
                    'Unit J.62DMI00.021.1. Laporan mengikat putusan hari 2, sisa risiko, dan sembilan puluh hari ke depan. Waktu lesson ini untuk menulis tugas.',
                    120,
                    [
                        $this->heading('Bukan ringkasan untuk arsip mati', 1),
                        $this->paragraph('Unit J.62DMI00.021.1 meminta laporan akhir proyek data science. Pada Arunika, laporan akhir bukan tempat menaruh semua notebook. Ia dokumen yang Ibu Lestari bisa pakai untuk memutuskan lanjut atau berhenti, dan yang nanti bisa kamu jadikan bukti portofolio — setelah fakta yang sama kamu rapikan untuk asesor, bukan dengan menyalin Lesson ini. Waktu lesson ini dipakai untuk menulis laporan di tugas.'),
                        $this->heading('Urutan yang memaksa jujur'),
                        $this->numbered([
                            'Keputusan: putusan dari berita acara hari 2, apa adanya. Jangan menggantinya agar laporan terdengar lebih halus.',
                            'Bukti: jejak ambang, dan status sah atau belum sah.',
                            'Pemasangan, unit J.62AIN00.015.1: gerbang, mode bayangan, dan rollback, hanya sepanjang berita acara mengizinkan.',
                            'Perawatan, unit J.62AIN00.017.1: irama pantau dan pemicu latih ulang yang angkanya kamu turunkan dari sasaran hari 1.',
                            'Data pribadi, unit J.62PDP00.011.1: RoPA lima baris dan penerapan piagam pada dua teks.',
                            'Sembilan puluh hari, unit J.62DMI00.021.1: siapa melakukan apa, dan pemicu yang akan menghentikan pekerjaan.',
                            'Yang tidak dikerjakan: balasan otomatis, ekspor korpus, sertifikat dari Course ini.',
                        ]),
                        $this->diagram(
                            'Laporan akhir berjalan dari putusan hari 2 ke sisa risiko, lalu ke jendela sembilan puluh hari.',
                            [
                                ['id' => 'putusan', 'label' => 'Putusan', 'hint' => 'dari acara'],
                                ['id' => 'bukti', 'label' => 'Bukti', 'hint' => 'ambang'],
                                ['id' => 'sisa', 'label' => 'Sisa', 'hint' => 'risiko'],
                                ['id' => 'depan', 'label' => '90 hari', 'hint' => 'pemilik'],
                            ],
                            [
                                ['from' => 'putusan', 'to' => 'bukti'],
                                ['from' => 'bukti', 'to' => 'sisa'],
                                ['from' => 'sisa', 'to' => 'depan'],
                            ],
                            4,
                        ),
                        $this->paragraph('Laporan yang membuka dengan arsitektur encoder sudah salah urutan. Pembaca dinas membutuhkan putusan di paragraf pertama. Detail model hidup di lampiran yang ditunjuk, bukan di kalimat pembuka. Jika sembilan puluh hari tidak punya nama, laporan itu hanya berita acara yang dipanjangkan.'),
                    ],
                ),
                $this->textLesson(
                    'Menutup tiga hari: bukti, rubrik, dan batas sertifikat',
                    'Kuis mengukur bacaaan. Tugas mengukur artefak. Sertifikat okupasi tidak keluar dari sini.',
                    30,
                    [
                        $this->heading('Apa yang selesai, dan apa yang tidak', 1),
                        $this->paragraph('Tiga hari ini menutup pelatihan, bukan uji kompetensi. Kuis wajib menilai apakah kamu bisa memilih keputusan yang selaras dengan sasaran, review, pemasangan, perawatan, dan data pribadi. Tiga tugas menilai artefak: sasaran plus rencana, dua berita acara, dan laporan akhir yang memuat pemasangan, runbook, serta program kerja data pribadi.'),
                        $this->paragraph('Bukti yang nanti berguna di asesmen adalah bukti yang valid, asli, terkini, dan memadai. Menyalin tabel Lesson ke tugas, atau menyalin putusan kuis ke paket siang, tidak memadai. Course ini bukan formulir pemohonan sertifikasi dan bukan tempat uji.'),
                        $this->heading('Batas yang jangan dikaburkan'),
                        $this->bullets([
                            'Skema SKEMA-45 menilai okupasi ini di tempat uji yang ditetapkan lembaganya. Lulus Course tidak sama dengan kompeten.',
                            'Profil okupasi di peta okupasi meminta pengalaman dan kualifikasi yang lebih tinggi daripada syarat daftar pemohon pada skema. Pelatihan tidak menggantikan pengalaman itu.',
                            'Sertifikat lembaga, jika kelak terbit, berlaku menurut skema itu, bukan menurut halaman ini.',
                            'Menyelesaikan Course ini tidak membuka Administrasi Agen OpenClaw dan tidak menjadikan Lesson sebagai konsol agen.',
                        ]),
                        $this->heading('Kalau kamu mau lanjut ke lembaga sertifikasi'),
                        $this->paragraph('Skema SKEMA-45 milik LSP Telekomunikasi Digital Indonesia. Pemohon yang diakui skema itu ada tiga jalur: mahasiswa D3 semester akhir atau semester 6 yang telah mengikuti pelatihan berbasis kompetensi ini, atau pemilik ijazah minimal SMA atau SMK yang telah mengikuti pelatihan yang sama, atau tenaga kerja dengan pengalaman minimal 1 tahun di jabatan ini.'),
                        $this->paragraph('Biaya sertifikasi yang tertulis pada skema adalah Rp2.500.000. Sertifikat kompetensi, jika kelak terbit, berlaku 3 tahun. Surveilan pemegang sertifikat dilakukan minimal sekali dalam satu tahun. Peta okupasi TIK.DSC0704 mensyaratkan sertifikat okupasi setara KKNI level 6 dan pengalaman minimal 3 tahun. Pelatihan tiga hari ini tidak menggantikan syarat peta itu, dan tidak menggantikan asesmen.'),
                        $this->paragraph('Portofolio yang paling dekat dengan bukti skema adalah tiga tugas ini, setelah kamu rapikan. Sasaran dan rencana untuk J.62AIN00.002.1 dan J.62AIN00.004.1. Dua berita acara untuk J.62DMI00.015.1 dan J.62DMI00.020.1. Laporan akhir yang memuat J.62AIN00.015.1, J.62AIN00.017.1, J.62PDP00.011.1, dan J.62DMI00.021.1. Course ini bukan formulir APL 01 atau APL 02.'),
                        $this->paragraph('Kalau putusanmu tunda dengan syarat, kamu tidak gagal sebagai insinyur. Kamu menolak memasang model yang belum layak ke antrian warga. Itu salah satu pekerjaan yang diminta okupasi ini.'),
                    ],
                ),
            ],
        ];
    }
}
