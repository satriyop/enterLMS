<?php

namespace Database\Seeders\Concerns;

/**
 * Three-day syllabus for Insinyur Kecerdasan Artificial (AI Engineer).
 *
 * Occupation TIK.DSC0704, scheme SKEMA-45. Running case: complaint routing
 * at Dinas Pelayanan Publik Kota Arunika. Lessons are text, figures, and
 * Python the learner runs locally. The LMS does not execute that code.
 * Not a BNSP certificate.
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
     * @return array<string, mixed>
     */
    private function fixtureCode(string $filename, string $language = 'python'): array
    {
        $path = dirname(__DIR__).'/fixtures/ai-engineer/'.$filename;
        $source = file_get_contents($path);

        if (! is_string($source) || trim($source) === '') {
            throw new \RuntimeException('Berkas latihan kosong: '.$filename);
        }

        return $this->code($source, $language);
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionHariSatu(): array
    {
        return [
            'title' => 'Hari 1. Sasaran teknis dan rencana proyek',
            'description' => 'Tujuh jam dengan data pengaduan September Dinas Arunika. Kamu menghitung ambang dari data itu, lalu menulis sasaran teknis dan rencana proyek.',
            'lessons' => [
                $this->textLesson(
                    'Untuk siapa kursus ini',
                    'Tiga hari untuk orang yang akan memutuskan apakah model boleh mengubah antrian pengaduan.',
                    20,
                    [
                        $this->heading('Untuk siapa kursus ini', 1),
                        $this->paragraph('Kursus ini untuk kamu yang sudah menulis Python dan pernah memakai model klasifikasi. Okupasinya Insinyur Kecerdasan Artificial, kode TIK.DSC0704, jenjang kualifikasi 7. Tiga hari ini pelatihan untuk skema SKEMA-45. Ini bukan kuliah machine learning, dan bukan tempat uji kompetensi.'),
                        $this->paragraph('Ini Open Course, gratis, dalam Bahasa Indonesia. Kamu boleh enroll sendiri. Selesai di sini tidak membuka Course terbatas, dan tidak menerbitkan sertifikat BNSP. Uji kompetensi tetap di lembaga sertifikasi. Biaya pada skema itu milik lembaga tersebut, bukan harga Course ini.'),
                        $this->paragraph('Kasusnya satu dinas fiktif, Dinas Pelayanan Publik Kota Arunika. Ilmuwan data sudah menyerahkan model yang membaca pengaduan dan mengusulkan meja tujuan. Pekerjaanmu hari ini bukan melatih model itu lagi. Pekerjaanmu menulis sasaran yang bisa ditandatangani, lalu rencana yang memakai sasaran itu.'),
                        $this->paragraph('Bahan hari ini adalah lembar September: jumlah pengaduan per meja, dua belas teks pengaduan, satu baris log, dan satu halaman rencana dari vendor. Kamu akan menghitung ambang dari lembar itu. Angka yang sudah ditandatangani baru muncul setelah hitungannya kelihatan.'),
                        $this->heading('Yang akan kamu hasilkan hari ini'),
                        $this->bullets([
                            'Satu halaman sasaran teknis. Isinya keputusan, ambang, lingkungan, dan syarat berhenti.',
                            'Satu sampai dua halaman rencana. Isinya lingkup, urutan, nama orang, jadwal enam minggu, dan risiko.',
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
                            'Tiga hari pada kasus yang sama. Hari berikutnya memakai dokumen hari sebelumnya.',
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
                        $this->paragraph('Pada pelajaran yang memakai kode, tombol Unduh berkas ada di atas teks. Berkasnya atur_meja.py, lembar_september.py, redaksi.py, evaluasi_latihan.py, celah_proyek.py, jejak_ambang.py, dan layanan_bayangan.py. Simpan berkas itu, lalu jalankan dengan Python 3 di komputermu, tanpa pustaka tambahan. LMS ini tidak menjalankan kode itu. Di akhir hari ada tugas tertulis. Kuis pengetahuan dinilai otomatis. Tugas portofolio dinilai manusia.'),
                        $this->paragraph('Kursus tetangga, Membangun Model AI untuk Kasus Tertentu, mengerjakan klasifikasi dari sisi model. Di sini model itu sudah ada. Kamu yang memutuskan versi mana yang boleh mengubah meja tujuan warga.'),
                    ],
                    preview: true,
                ),
                $this->textLesson(
                    'Kasus berjalan: pengaduan Kota Arunika',
                    'Delapan ribu pengaduan September, lima meja, dan dua belas teks yang kamu arahkan sendiri.',
                    70,
                    [
                        $this->heading('Apa yang dikerjakan dinas ini', 1),
                        $this->paragraph('Dinas Pelayanan Publik Kota Arunika menerima sekitar 400 pengaduan pada hari kerja. Ada tiga saluran: SP4N-LAPOR, loket, dan WhatsApp resmi dinas. Petugas meja depan, Dina, membaca teks lalu mengirim ke satu dari lima meja: kependudukan, perizinan, infrastruktur, kebersihan, atau sosial.'),
                        $this->paragraph('Salah meja membuat warga menunggu lebih lama. Salah meja infrastruktur juga menunda bahaya fisik. Pengaduan “jalan di gang melati ambles setelah hujan” yang masuk ke kebersihan bisa menunggu sampai sore. Itu pekerjaan yang ingin dibantu. Model hanya mengusulkan meja. Model tidak membalas warga.'),
                        $this->heading('Lembar September'),
                        $this->paragraph('Dua puluh hari kerja dikali 400 pengaduan sama dengan 8.000 pengaduan. Rekap Dina untuk September ada di tabel ini. Porsi adalah bagian dari 8.000. Kamu akan memakai angka ini saat menghitung ambang, jadi jangan melewatinya.'),
                        $this->table(
                            ['Meja', 'Jumlah September', 'Porsi', 'Jika salah meja'],
                            [
                                ['Kependudukan', '3.360', '42 persen', 'Warga balik lagi. Teks sering membawa NIK.'],
                                ['Perizinan', '1.920', '24 persen', 'Izin tertahan di meja yang salah.'],
                                ['Kebersihan', '1.440', '18 persen', 'Sampah terlambat. Tidak berbahaya pada hari yang sama.'],
                                ['Infrastruktur', '800', '10 persen', 'Jalan ambles, lampu mati, atau genangan. Bahaya fisik.'],
                                ['Sosial', '480', '6 persen', 'Bantuan tidak cair. Isinya keadaan keluarga.'],
                            ],
                        ),
                        $this->paragraph('Kependudukan adalah meja terbesar. Model yang selalu memilih kependudukan bisa terlihat “akurat” padahal infrastruktur dan sosial sering salah. Karena itu sasaran nanti tidak memakai akurasi tunggal.'),
                        $this->heading('Dua belas pengaduan untuk kamu arahkan'),
                        $this->paragraph('Siapkan kertas. Untuk tiap teks, tulis satu meja, atau tulis ragu jika Dina yang harus memutuskan. Perkiraan waktu 40 menit. Tabel Dina ada di bawah teks ini. Isi dulu, baru bandingkan.'),
                        $this->numbered([
                            '“ktp saya hilang, mohon diterbitkan ulang”.',
                            '“ktp saya hilang nik 3201013101900001 mohon diterbitkan ulang”. Nomor ini karangan untuk latihan. Polanya sama dengan NIK, yaitu 16 digit.',
                            '“imb rumah ditolak tanpa alasan”.',
                            '“jalan di gang melati ambles setelah hujan”.',
                            '“sampah tiga hari tidak diangkut di rt 04”.',
                            '“bantuan sosial ibu saya tidak cair”.',
                            '“lampu jalan mati dan sampah menumpuk”.',
                            '“air rob masuk rumah dan lampu mati”.',
                            '“akta lahir anak saya belum jadi sudah 3 bulan”.',
                            '“izin usaha warung ditolak”.',
                            '“selokan mampet depan rumah, baunya sampai ke dalam”.',
                            '“jam buka loket hari Sabtu sampai jam berapa?”.',
                        ]),
                        $this->heading('Meja yang dipilih Dina'),
                        $this->table(
                            ['No.', 'Meja Dina', 'Alasan singkat'],
                            [
                                ['1', 'Kependudukan', 'Minta KTP terbit ulang.'],
                                ['2', 'Kependudukan', 'Sama dengan nomor 1. NIK tidak mengubah meja, tetapi tidak boleh ikut ke model.'],
                                ['3', 'Perizinan', 'Keberatan atas IMB.'],
                                ['4', 'Infrastruktur', 'Jalan ambles. Bahaya fisik.'],
                                ['5', 'Kebersihan', 'Sampah tidak diangkut. Tidak ada bahaya fisik pada hari itu.'],
                                ['6', 'Sosial', 'Bantuan tidak cair.'],
                                ['7', 'Infrastruktur', 'Ada dua urusan. Lampu mati adalah bahaya fisik, jadi infrastruktur menang.'],
                                ['8', 'Infrastruktur', 'Rob dan lampu mati. Keduanya bahaya fisik.'],
                                ['9', 'Kependudukan', 'Akta lahir belum selesai.'],
                                ['10', 'Perizinan', 'Izin usaha ditolak.'],
                                ['11', 'Kebersihan', 'Yang dikeluhkan bau. Tidak ada genangan atau jalan tertutup.'],
                                ['12', 'Ragu, Dina jawab sendiri', 'Ini pertanyaan jam layanan, bukan pengaduan yang perlu meja.'],
                            ],
                        ),
                        $this->paragraph('Dua aturan dipakai Dina, dan kamu akan menulisnya ke sasaran. Pertama, jika teks menyebut bahaya fisik (jalan ambles, lampu mati, rob, genangan, pohon tumbang) bersama urusan meja lain, pilih infrastruktur. Kedua, jika teks hanya bertanya dan tidak melaporkan urusan, jangan paksakan meja. Itu kelas ragu.'),
                        $this->paragraph('Nomor 11 mudah tertukar dengan infrastruktur. “Selokan mampet” bisa berarti banjir. Dina memilih kebersihan karena keluhannya bau, bukan air yang menutup jalan. Kalau teksnya “selokan mampet sampai air masuk rumah”, meja yang dipilih adalah infrastruktur.'),
                        $this->heading('Aturan itu sebagai berkas yang bisa dijalankan'),
                        $this->paragraph('Tabel di atas adalah spesifikasinya. atur_meja.py adalah spesifikasi yang sama, dalam urutan yang sama: bahaya fisik dulu, pertanyaan jam atau syarat tidak dipaksa ke meja, NIK tidak mengubah meja. Unduh berkas di atas halaman ini, lalu jalankan python3 atur_meja.py. Dua belas baris pertama harus sama dengan tabel Dina. Empat baris latihan harus sama dengan kunci di lesson artefak. Kalau mejanya beda, aturannya yang salah.'),
                        $this->fixtureCode('atur_meja.py'),
                        $this->paragraph('Keluaran yang sesuai:'),
                        $this->fixtureCode('atur_meja.out.txt', 'text'),
                        $this->diagram(
                            'Teks masuk, identitas dilepas, lalu model memilih meja atau mengembalikan ke Dina.',
                            [
                                ['id' => 'adu', 'label' => 'Pengaduan', 'hint' => 'teks masuk'],
                                ['id' => 'potong', 'label' => 'Potong', 'hint' => 'NIK dan HP'],
                                ['id' => 'meja', 'label' => 'Meja', 'hint' => 'satu dari lima'],
                                ['id' => 'ragu', 'label' => 'Ragu', 'hint' => 'kembali ke Dina'],
                            ],
                            [
                                ['from' => 'adu', 'to' => 'potong'],
                                ['from' => 'potong', 'to' => 'meja', 'label' => 'yakin'],
                                ['from' => 'potong', 'to' => 'ragu', 'label' => 'tidak yakin'],
                            ],
                            4,
                        ),
                        $this->heading('Siapa mengerjakan apa'),
                        $this->table(
                            ['Orang', 'Pekerjaan pada kasus ini'],
                            [
                                ['Ibu Lestari', 'Kepala dinas. Menandatangani waktu tunggu warga dan ambang yang boleh dipakai.'],
                                ['Dina', 'Meja depan. Hari ini dia yang mengarahkan semua pengaduan. Pengaduan ragu kembali kepadanya.'],
                                ['Bima', 'Ilmuwan data. Menyerahkan model, metrik, dan catatan label. Dia tidak memasang model ke antrian.'],
                                ['Raka', 'Insinyur data. Menyiapkan antrean dari tiga saluran. Layanan prediksi belum ada.'],
                                ['Kamu', 'Insinyur AI. Menulis sasaran dan rencana, lalu nanti meninjau apakah model boleh mengubah antrian.'],
                            ],
                        ),
                        $this->paragraph('Bima sudah menyerahkan dua kandidat yang belum terpasang: aturan kata kunci ditambah regresi logistik, dan sebuah encoder bahasa Indonesia. Lingkungan dinas tidak mengirim teks mentah warga ke layanan model di luar jaringan dinas.'),
                    ],
                ),
                $this->textLesson(
                    'Posisi insinyur AI di antara ilmuwan data dan insinyur data',
                    'Tiga pekerjaan pada Senin pagi yang sama. Kamu yang menandatangani boleh atau tidaknya antrian berubah.',
                    25,
                    [
                        $this->heading('Tiga pertanyaan yang berbeda', 1),
                        $this->paragraph('Pekerjaan okupasi ini adalah merancang dan memasang solusi AI ke sistem yang sudah berjalan, supaya tujuan organisasi tercapai. Di Arunika, tujuan itu sempit: pengaduan sampai ke meja yang benar, dan data warga tidak ikut keluar.'),
                        $this->paragraph('Pada Senin pagi, Bima membuka laporan dan bertanya: model mana yang lebih baik pada data uji. Raka membuka antrean dan bertanya: apakah teks dari tiga saluran sudah sampai ke gudang tanpa tercecer. Kamu bertanya sesuatu yang lain: versi mana yang boleh mengubah meja tujuan warga, dengan syarat apa, dan bagaimana antrian dikembalikan ke Dina jika hasilnya merugikan.'),
                        $this->heading('Contoh serahan yang belum keputusan'),
                        $this->paragraph('Bima berkata, “Encoder unggul pada rata-rata.” Raka berkata, “Antrean dari LAPOR, loket, dan WhatsApp sudah masuk gudang. Layanan prediksi belum saya buat.” Ibu Lestari bertanya, “Jadi minggu ini warga sudah diarahkan mesin?” Jawaban yang jujur hari itu adalah belum. Belum ada ambang yang ditandatangani, belum ada tombol yang mengembalikan trafik ke Dina, dan angka Bima belum dicek terhadap meja yang berbahaya.'),
                        $this->heading('Yang kamu pegang'),
                        $this->bullets([
                            'Keputusan layak pasang, tunda, atau tolak. Ibu Lestari tidak membaca laporan metrik baris demi baris. Bima tidak menandatangani risiko pada antrian warga.',
                            'Urutan kerja. Label yang masih berisi nama petugas harus beres sebelum layanan prediksi dipakai. Persetujuan tujuan pemrosesan harus ada sebelum teks warga masuk ke layanan itu.',
                            'Syarat naik. Model hanya boleh mengubah antrian jika ambang meja berbahaya dan uji kebocoran lulus.',
                            'Cara mundur. Jika routing salah atau NIK muncul di log, antrian kembali ke Dina.',
                        ]),
                        $this->table(
                            ['Dokumen', 'Penulis pertama', 'Yang menandatangani'],
                            [
                                ['Laporan metrik uji', 'Bima', 'Kamu, terhadap sasaran teknis'],
                                ['Kontrak antrean data', 'Raka', 'Kamu, terhadap bidang yang boleh masuk model'],
                                ['Sasaran teknis dan rencana', 'Kamu', 'Ibu Lestari, untuk waktu tunggu warga'],
                                ['Program kerja data pribadi', 'Kamu bersama petugas yang ditunjuk dinas', 'Pemilik tujuan pemrosesan'],
                            ],
                        ),
                        $this->paragraph('Kalau tiga pekerjaan itu dilebur menjadi “tim AI”, tidak ada yang menjaga data uji tetap jujur, dan tidak ada yang menjaga layanan tetap bisa dimatikan. Course ini memisahkan pekerjaan itu supaya masing-masing punya dokumen.'),
                    ],
                ),
                $this->textLesson(
                    'Menentukan sasaran teknis solusi AI',
                    'Unit J.62AIN00.002.1. Satu ambang dihitung sampai tuntas. Lima ambang lain memakai langkah yang sama.',
                    80,
                    [
                        $this->heading('Sasaran adalah kontrak sebelum skor Bima', 1),
                        $this->paragraph('Unit J.62AIN00.002.1 meminta kamu menentukan sasaran teknis solusi AI. Kalimat “akurasi tinggi” belum sasaran. Sasaran menyebut apa yang masuk, kapan keputusan diambil, apa yang keluar, batas gagal, dan di jaringan mana layanan itu hidup.'),
                        $this->paragraph('Untuk Arunika, keputusan diambil pada saat pengaduan masuk, sebelum Dina memindahkan tiket. Masukan yang boleh dilihat model adalah teks pengaduan setelah NIK dan nomor HP dilepas. Keluaran adalah satu dari lima meja, atau ragu. Layanan hidup di jaringan dinas. Teks mentah tidak dikirim ke penyedia model di luar jaringan itu.'),
                        $this->heading('Enam langkah lembar kerja'),
                        $this->numbered([
                            'Tulis keputusan dalam satu kalimat. Di sini: pilih satu meja, atau kembalikan ke Dina.',
                            'Tulis masukan pada saat keputusan. Teks setelah identitas dilepas. Bukan catatan petugas setelah pengaduan ditangani.',
                            'Tulis keluaran. Lima nama meja, plus ragu.',
                            'Pilih kesalahan yang mahal. Ubah menjadi angka dengan cara: jumlah bulan itu, berapa yang boleh terlewat, lalu bagi.',
                            'Tulis lingkungan. Jaringan dinas, dan bidang yang berhenti di pintu model.',
                            'Tulis syarat berhenti. Ukuran apa, pada data yang mana, yang membuat pemasangan tidak jadi.',
                        ]),
                        $this->heading('Contoh tuntas: recall infrastruktur'),
                        $this->paragraph('Infrastruktur adalah kesalahan yang mahal karena menunda bahaya fisik. Pada September ada 800 pengaduan infrastruktur. Ibu Lestari menerima paling banyak 120 yang terlewat dalam sebulan. Sisanya harus sampai ke meja infrastruktur.'),
                        $this->paragraph('Hitungannya: 120 dibagi 800 sama dengan 0,15. Jadi paling banyak 15 persen yang boleh terlewat. Recall adalah bagian yang tertangkap, yaitu 1 dikurangi 0,15, sama dengan 0,85. Ambang yang ditandatangani: recall infrastruktur minimal 0,85.'),
                        $this->table(
                            ['Langkah', 'Angka September', 'Hasil'],
                            [
                                ['Jumlah infrastruktur', '800', '10 persen dari 8.000'],
                                ['Yang boleh terlewat', '120', 'Dipilih Ibu Lestari, sekitar empat gagal per hari kerja'],
                                ['Bagian yang terlewat', '120 / 800', '0,15'],
                                ['Recall minimum', '1 − 0,15', '0,85'],
                            ],
                        ),
                        $this->paragraph('Empat gagal per hari kerja berasal dari 120 dibagi 20 hari. Itu masih banyak untuk jalan ambles, dan itu sudah batas atas. Angka yang lebih longgar dari 0,85 berarti Ibu Lestari menerima lebih dari 120 salah meja berbahaya. Kamu tidak menaikkan ambang ini hanya karena suatu model tidak mencapainya.'),
                        $this->heading('Langkah yang sama untuk baris lain'),
                        $this->paragraph('Macro-F1 dipakai karena mejanya timpang. Kependudukan 3.360 dan infrastruktur 800. Akurasi tunggal bisa tinggi hanya karena meja besar sering benar. Macro-F1 menghitung tiap meja lalu merata-ratakannya, sehingga infrastruktur tidak tenggelam. Sasarannya: macro-F1 harus lebih tinggi daripada aturan kata kunci yang dipakai Dina hari ini. Aturan itu adalah pembanding, bukan model yang baru.'),
                        $this->paragraph('Porsi ragu dihitung dari waktu Dina. Tiap pengaduan yang dikembalikan memakan sekitar 2 menit. Pada 400 pengaduan per hari, 20 persen berarti 80 pengaduan, yaitu 160 menit, sekitar dua setengah jam dari shiftnya. Di atas itu Dina kembali mengerjakan hampir semuanya, dan model tidak mengurangi beban. Ambang yang ditandatangani: porsi ragu maksimal 20 persen. Di tugas kamu menunjukkan perkalian ini, bukan hanya menyalin 20 persen.'),
                        $this->paragraph('Latensi dihitung dari loket. Petugas melihat saran meja sebelum berbicara ke warga. Jika saran belum muncul dalam sekali pandang, petugas mengabaikannya. Sekali pandang di loket Arunika dihitung 300 milidetik. p95 artinya 95 dari 100 permintaan selesai di bawah angka itu. Lima permintaan paling lambat boleh lebih lambat. Ambang: p95 maksimal 300 ms pada jam sibuk, diukur pada layanan di jaringan dinas, bukan pada notebook.'),
                        $this->paragraph('Identitas di log dihitung sebagai cacah. Pola yang dicari adalah 16 digit berurutan dan nomor HP. Cacah yang boleh ada di log prediksi adalah nol. Arsip dinas tetap menyimpan pengaduan asli. Log prediksi bukan salinan arsip itu.'),
                        $this->paragraph('Rollback dihitung dari lobby. Jika pada pukul 08.15 model mulai mengirim banyak pengaduan ke meja yang salah, petugas harus bisa mengembalikan seluruh antrian ke Dina sebelum lobby penuh. Ambang: pengembalian itu selesai dalam waktu di bawah 15 menit, dan ada orang yang memegang tombolnya.'),
                        $this->diagram(
                            'Ambang bergerak dari jumlah pada lembar September ke halaman yang ditandatangani.',
                            [
                                ['id' => 'jumlah', 'label' => 'Jumlah', 'hint' => 'lembar September'],
                                ['id' => 'batas', 'label' => 'Batas salah', 'hint' => 'berapa yang boleh'],
                                ['id' => 'ambang', 'label' => 'Ambang', 'hint' => 'hasil bagi'],
                                ['id' => 'halaman', 'label' => 'Halaman', 'hint' => 'ditandatangani'],
                            ],
                            [
                                ['from' => 'jumlah', 'to' => 'batas'],
                                ['from' => 'batas', 'to' => 'ambang'],
                                ['from' => 'ambang', 'to' => 'halaman'],
                            ],
                            4,
                        ),
                        $this->heading('Halaman yang ditandatangani'),
                        $this->paragraph('Ini kontrak yang akan kamu pakai lagi pada hari 2. Tiap baris punya asal. Baris infrastruktur sudah dihitung di atas. Di tugas, kamu mengulang hitungan itu dengan tulisanmu sendiri, lalu mengerjakan baris sosial yang belum diisi di tabel ini.'),
                        $this->table(
                            ['Sasaran', 'Ambang', 'Asal angka'],
                            [
                                ['Macro-F1', 'Lebih tinggi dari aturan kata kunci Dina', 'Meja timpang: 3.360 lawan 800'],
                                ['Recall infrastruktur', 'Minimal 0,85', '120 terlewat dari 800'],
                                ['Porsi ragu', 'Maksimal 20 persen', '80 pengaduan, sekitar 160 menit Dina'],
                                ['Latensi p95', 'Maksimal 300 ms pada jam sibuk', 'Sekali pandang di loket'],
                                ['Identitas di log', 'Nol NIK dan nol nomor HP', 'Log prediksi bukan arsip'],
                                ['Rollback', 'Di bawah 15 menit', 'Antrian kembali ke Dina sebelum lobby penuh'],
                            ],
                        ),
                        $this->paragraph('Ibu Lestari tidak menandatangani macro-F1 sebagai angka lomba. Ia menandatangani waktu warga sampai meja yang benar. Tiap salah meja harus bisa diceritakan sebagai menit tambahan atau bahaya yang tertunda. Untuk infrastruktur, 120 salah meja adalah cerita itu.'),
                        $this->paragraph('Syarat berhenti juga masuk ke halaman yang sama. Kalimatnya: jika recall infrastruktur pada data yang lebih baru daripada data latih jatuh di bawah 0,85, solusi tidak naik dari mode bayangan. Mode bayangan berarti model menghitung meja, tetapi antrian warga tidak berubah. Dina tetap yang mengirim. Tanpa kalimat berhenti, tidak ada yang berhak menahan pemasangan ketika angka berikutnya jelek.'),
                        $this->heading('Hitungan yang sama, dijalankan'),
                        $this->paragraph('Unduh berkas di atas halaman ini. lembar_september.py menghitung porsi, recall, menit ragu, dan p95. Python mencetak titik desimal. Di halaman yang ditandatangani kamu menulis koma: keluaran 0.85 adalah ambang 0,85. p95 pada daftar notebook Bima tercetak 21 ms. p95 pada daftar layanan dinas tercetak 340 ms. Keduanya memakai fungsi yang sama. Yang lulus ambang 300 ms hanya jika sampelnya diambil di layanan, bukan di notebook. Baris sosial tidak diisi berkas ini. Kamu yang memanggil recall dengan pembagimu sendiri.'),
                        $this->fixtureCode('lembar_september.py'),
                        $this->paragraph('Keluaran yang sesuai:'),
                        $this->fixtureCode('lembar_september.out.txt', 'text'),
                        $this->heading('Baris yang kamu hitung sendiri'),
                        $this->paragraph('Sosial belum punya ambang recall pada halaman di atas. September mencatat 480 pengaduan sosial. Salah meja di sini tidak menunda jalan ambles. Salah meja di sini menaruh keadaan bantuan seseorang di meja yang tidak berwenang membacanya. Di tugas kamu memilih berapa yang boleh terlewat, membagi angka itu dengan 480, lalu menulis recall minimum. Sertakan satu kalimat tentang apa yang terjadi pada warga jika teks bansos dibaca meja lain.'),
                    ],
                ),
                $this->textLesson(
                    'Kendala, lingkungan, dan kriteria belum siap',
                    'Empat benda yang bisa kamu periksa: saluran, log, baris latih, dan aturan pengaduan campur.',
                    55,
                    [
                        $this->heading('Apa yang boleh melewati pintu model', 1),
                        $this->paragraph('Tiga saluran tidak membawa data yang sama. Sasaran harus menyebut bidang mana yang berhenti sebelum layanan prediksi. Arsip dinas tetap menyimpan pengaduan lengkap. Yang dipotong adalah salinan yang masuk ke model dan ke log prediksi.'),
                        $this->table(
                            ['Saluran', 'Yang ikut pada teks', 'Yang harus berhenti di pintu'],
                            [
                                ['SP4N-LAPOR', 'Nomor aduan dan isi cerita. Kategori dari warga sering salah.', 'Nama dan nomor HP jika ada di isi. Korpus LAPOR tidak disalin ke notebook pribadi.'],
                                ['WhatsApp resmi', 'Isi pesan. Nomor pengirim ada di luar teks.', 'Nomor pengirim, dan NIK jika warga mengetiknya di pesan.'],
                                ['Loket', 'Cerita yang diketik petugas.', 'Kolom NIK, nama, dan HP pada formulir. Kolom itu tidak digabung ke teks model.'],
                            ],
                        ),
                        $this->heading('Satu baris log yang tidak boleh lolos'),
                        $this->paragraph('Ini cuplikan latihan, bukan log sungguhan. Nomornya karangan.'),
                        $this->quote('2026-09-08 08:14:02 prediksi=kebersihan skor=0,62 teks="ktp saya hilang nik 3201013101900001 mohon diterbitkan ulang"'),
                        $this->paragraph('Ada dua masalah pada satu baris. Pertama, meja yang dipilih salah. Teks ini minta KTP, jadi kependudukan, sama seperti nomor 2 pada daftar Dina. Kedua, 16 digit ikut tersimpan di log prediksi. Raka harus memotong pola 16 digit dan nomor HP sebelum layanan prediksi menulis log. Kamu yang menolak layanan yang belum bisa memotong itu. Cacah NIK yang diterima di log adalah nol, bukan “sedikit”.'),
                        $this->heading('Pemotong yang bisa diuji'),
                        $this->paragraph('redaksi.py memakai dua pola. NIK adalah 16 digit yang tidak menempel pada digit lain. HP adalah nomor seluler Indonesia, lebih pendek dari 16 digit, jadi pemotong yang hanya mencari NIK membiarkannya utuh. Unduh berkas di atas halaman ini, lalu jalankan python3 redaksi.py. Log 8 September gagal sebelum dipotong dan lulus sesudahnya. Teks bansos dengan 081234567890 masih gagal pada redaksi_nik_saja(). Kalimat “sudah diteruskan ke bidang PU” terdeteksi, dan kalimat loket sebelum tanda titik tidak.'),
                        $this->fixtureCode('redaksi.py'),
                        $this->paragraph('Keluaran yang sesuai:'),
                        $this->fixtureCode('redaksi.out.txt', 'text'),
                        $this->heading('Satu baris latih yang membuat skor berbohong'),
                        $this->paragraph('Bima menunjukkan satu baris dari berkas latih.'),
                        $this->quote('Teks: "jalan di gang melati ambles setelah hujan. sudah diteruskan ke bidang PU". Label: infrastruktur.'),
                        $this->paragraph('Kalimat kedua ditulis petugas setelah pengaduan ditangani. Pada saat Dina memilih meja, kalimat itu belum ada. Jika model belajar dari kalimat itu, skor uji naik karena model melihat jawaban yang belum tersedia di loket. Sebelum kamu percaya pada angka Bima, kalimat semacam itu harus dibuang dari data yang dipakai menghitung metrik. Tandanya mudah dicari: “sudah diteruskan”, “telah diselesaikan”, atau nama bidang yang baru muncul setelah penanganan.'),
                        $this->heading('Pengaduan campur, ditulis sebagai aturan'),
                        $this->paragraph('“Lampu jalan mati dan sampah menumpuk” berisi dua meja. Aturan yang dipakai, dan yang kamu tulis di sasaran: jika ada bahaya fisik, pilih infrastruktur. Jika dua skor sama-sama rendah dan bahaya fisik tidak jelas, kembalikan ke Dina. Aturan ini ditulis di dokumen, tidak disembunyikan di dalam bobot model.'),
                        $this->paragraph('“Selokan mampet depan rumah, baunya sampai ke dalam” tidak memakai aturan bahaya fisik. Tidak ada air yang masuk rumah dan tidak ada jalan yang tertutup, jadi kebersihan. Kalimat yang hampir sama, “selokan mampet sampai air masuk rumah”, memakai aturan bahaya fisik, jadi infrastruktur.'),
                        $this->heading('Lingkungan yang sudah diketahui'),
                        $this->bullets([
                            'Dinas tidak punya GPU bersama. Model yang hanya jalan di GPU cloud gagal pada lingkungan, sebelum gagal pada metrik.',
                            'Teks pengaduan mentah tidak dikirim ke layanan model di luar jaringan dinas.',
                            'Sebagian label lama berisi nama petugas, misalnya “ke Dina”, bukan nama meja. Label itu mengajarkan giliran jaga, bukan jenis layanan.',
                            'Pimpinan pernah meminta balasan otomatis ke warga. Yang bisa dipertanggungjawabkan saat ini baru pilihan meja. Balasan ke warga ada di luar lingkup.',
                        ]),
                        $this->paragraph('Kriteria belum siap adalah bagian dari sasaran. Pada banyak dinas, hasil jujur di akhir hari pertama adalah tunda. Tunda tetap hasil kerja unit ini, selama alasan tundanya memakai angka dari lembar September, bukan kesan bahwa datanya belum sempurna.'),
                    ],
                ),
                $this->textLesson(
                    'Membuat rencana proyek solusi AI',
                    'Unit J.62AIN00.004.1. Satu halaman vendor diperbaiki menjadi urutan yang punya nama.',
                    50,
                    [
                        $this->heading('Halaman yang ditolak', 1),
                        $this->paragraph('Seorang vendor mengirim satu halaman ke Ibu Lestari. Kamu akan memakai halaman ini di tugas. Baca dulu apa yang salah pada tiap kalimat, baru tulis penggantinya.'),
                        $this->quote('Solusi AI pelayanan publik. Akurasi 95 persen. Chatbot menjawab warga di WhatsApp. Go-live Jumat. GPU cloud. Tim data menyiapkan semuanya.'),
                        $this->paragraph('“Akurasi 95 persen” tidak menyebut meja mana. Pada data September, meja besar saja sudah 42 persen dari volume. “Chatbot menjawab warga” mengerjakan pekerjaan yang belum masuk lingkup, yaitu membalas warga, bukan memilih meja. “Go-live Jumat” adalah tanggal, bukan hasil pemeriksaan ambang. “GPU cloud” meminta mesin yang tidak ada di dinas dan mengirim teks ke luar jaringan. “Tim data” bukan nama orang, jadi tidak ada yang bisa ditagih pada hari Jumat itu.'),
                        $this->heading('Lingkup yang menggantikan halaman itu'),
                        $this->paragraph('Di dalam lingkup: routing ke lima meja, kelas ragu yang kembali ke Dina, mode bayangan, pemeriksaan ambang, pemotongan NIK dan nomor HP di pintu model, dan cara mengembalikan antrian ke Dina. Di luar lingkup: membalas warga, mengubah aplikasi SP4N-LAPOR, melatih model bahasa baru, dan mengganti Dina.'),
                        $this->heading('Urutan yang tidak dibalik'),
                        $this->numbered([
                            'Ibu Lestari menandatangani sasaran, termasuk recall infrastruktur 0,85 dan larangan NIK di log.',
                            'Bima membuang kalimat yang baru ada setelah penanganan, memperbaiki label nama petugas, lalu menyerahkan ulang metrik pada data yang lebih baru daripada data latih.',
                            'Raka menyiapkan layanan prediksi di jaringan dinas. Layanan itu memotong 16 digit dan nomor HP, dan punya tombol yang mengembalikan trafik ke Dina.',
                            'Mode bayangan berjalan tanpa mengubah antrian warga. Dina tetap mengirim. Kamu membandingkan usulan model dengan pilihan Dina.',
                            'Kamu menulis naik, tunda, atau tolak. Laporan akhir menyusul keputusan itu, tidak mendahuluinya.',
                        ]),
                        $this->paragraph('Tiap langkah selesai ketika bendanya ada, bukan ketika rapatnya selesai. Langkah 2 selesai ketika kalimat_setelah_ditangani() pada berkas latih mengembalikan False. Langkah 3 selesai ketika uji_log() pada satu permintaan percobaan mengembalikan lulus, termasuk untuk nomor HP, dan ketika rollback pernah dicatat di bawah 15 menit. Langkah 4 selesai setelah sepuluh hari kerja mode bayangan, bukan setelah satu sore. Fungsi uji_log() ada di redaksi.py. Log 8 September adalah permintaan yang harus gagal sebelum dipotong.'),
                        $this->diagram(
                            'Rencana adalah urutan. Langsung memasang membuat review tidak terpakai.',
                            [
                                ['id' => 'lingkup', 'label' => 'Lingkup', 'hint' => 'dan di luarnya'],
                                ['id' => 'urut', 'label' => 'Urutan', 'hint' => 'lima langkah'],
                                ['id' => 'orang', 'label' => 'Nama', 'hint' => 'yang ditagih'],
                                ['id' => 'pintu', 'label' => 'Keputusan', 'hint' => 'naik atau tunda'],
                            ],
                            [
                                ['from' => 'lingkup', 'to' => 'urut'],
                                ['from' => 'urut', 'to' => 'orang'],
                                ['from' => 'orang', 'to' => 'pintu'],
                            ],
                            4,
                        ),
                        $this->paragraph('Ketergantungan ditulis sebagai nama dan barang. “Bima menyerahkan data uji tanpa kalimat resolusi, paling lambat Jumat pekan pertama” adalah ketergantungan. “Raka menyediakan tombol rollback yang kamu yang memegang” juga. “Tim data menyiapkan semuanya” tidak bisa ditagih.'),
                    ],
                ),
                $this->textLesson(
                    'Jadwal, dependensi, dan risiko',
                    'Enam minggu setelah sasaran ditandatangani. Tiga hari kursus tidak menggantikan enam minggu itu.',
                    40,
                    [
                        $this->heading('Jadwal setelah Senin tanda tangan', 1),
                        $this->paragraph('Tiga hari kursus menghasilkan dokumen. Pekerjaan dinas lebih panjang. Contoh di bawah memakai Senin 5 Oktober sebagai hari Ibu Lestari menandatangani sasaran. Jika tanggal tanda tanganmu berbeda, geser minggu-minggunya. Jangan memampatkan mode bayangan menjadi satu hari supaya tanggal go-live di slide vendor tetap hidup.'),
                        $this->table(
                            ['Minggu', 'Tanggal contoh', 'Selesai ketika'],
                            [
                                ['1. Data', '5–9 Oktober', 'Bima menyerahkan ulang metrik. Kalimat “sudah diteruskan” sudah dibuang. Label nama petugas sudah diganti nama meja.'],
                                ['2. Layanan', '12–16 Oktober', 'Raka menjalankan layanan di jaringan dinas. Satu log percobaan tanpa NIK. Tombol kembali ke Dina pernah dicoba, waktunya di bawah 15 menit.'],
                                ['3 dan 4. Mode bayangan', '19 Oktober–30 Oktober', 'Sepuluh hari kerja. Model menghitung meja. Antrian warga tidak berubah. Kamu mencatat beda antara model dan Dina, per meja.'],
                                ['5. Keputusan', '2–6 November', 'Kamu menulis naik, tunda, atau tolak terhadap ambang yang sudah ditandatangani.'],
                                ['6. Tutup', '9–13 November', 'Laporan akhir dan catatan data pribadi untuk alur yang benar-benar berjalan, bukan untuk alur yang masih dijanjikan.'],
                            ],
                        ),
                        $this->paragraph('Oktober masih musim hujan di banyak kota. Pengaduan infrastruktur dan kebersihan pada mode bayangan bisa lebih banyak daripada September. Itu bukan gangguan jadwal. Itu data yang lebih keras daripada data uji musim kemarau. Rencana menyebutkannya, dan tidak memindahkan mode bayangan hanya supaya angkanya lebih mudah.'),
                        $this->paragraph('Angka p95 untuk minggu layanan diambil dari lembar_september.py. Sampel notebook mencetak 21 ms. Sampel layanan mencetak 340 ms dan gagal ambang 300 ms. Rencana yang menulis “latensi aman” karena notebook Bima cepat belum mengukur layanan.'),
                        $this->heading('Empat risiko yang sudah punya benda'),
                        $this->table(
                            ['Risiko', 'Tanda yang bisa diperiksa', 'Mitigasi', 'Pemilik'],
                            [
                                ['Skor uji berbohong', 'Baris latih masih berisi “sudah diteruskan ke bidang PU”', 'Buang kalimat yang baru ada setelah penanganan, lalu hitung ulang', 'Bima'],
                                ['NIK di log', 'Baris seperti cuplikan 8 September, ada 16 digit', 'Potong 16 digit dan nomor HP sebelum layanan menulis log', 'Raka dan kamu'],
                                ['Rata-rata naik, meja berbahaya turun', 'Macro-F1 naik, recall infrastruktur di bawah 0,85', 'Pakai ambang infrastruktur, jangan memakai rata-rata saja', 'Kamu'],
                                ['Korpus saluran nasional diminta keluar', 'Ada permintaan mengekspor isi LAPOR ke notebook atau ke vendor', 'Tolak. Latih dari arsip yang memang wewenang dinas', 'Kamu'],
                            ],
                        ),
                        $this->paragraph('Risiko tanpa pemilik akan jatuh ke Dina pada hari pertama antrian berubah. Dina tidak ikut menulis rencana. Nama pada kolom pemilik harus orang yang memang mengerjakan mitigasinya.'),
                    ],
                ),
                $this->textLesson(
                    'Artefak hari 1: sasaran dan rencana',
                    'Delapan puluh menit untuk menghitung dan menulis. Kunci latihan ada di lesson. Dokumen tugas tidak disalin dari tabel.',
                    80,
                    [
                        $this->heading('Cara memakai waktu ini', 1),
                        $this->paragraph('Hari 1 ditutup oleh dua dokumen pada tugas, untuk unit J.62AIN00.002.1 dan unit J.62AIN00.004.1. Lesson ini adalah latihan sebelum tugas. Kuis tidak menggantikan dua dokumen itu. Jika ambang di dokumenmu tidak punya hitungan, review hari 2 hanya akan menyalin opini.'),
                        $this->heading('Dua puluh menit: empat pengaduan latihan'),
                        $this->paragraph('Arahkan empat teks ini dengan aturan yang sudah kamu pakai pada dua belas pengaduan September. Tulis mejanya dulu, lalu bandingkan dengan kunci.'),
                        $this->numbered([
                            '“genteng tetangga jatuh ke jalan gang melati”.',
                            '“minta info syarat imb, saya belum mengajukan”.',
                            '“sampah pasar tidak diangkut dan lampu di gang yang sama mati”.',
                            '“bantuan lansia ibu saya tidak cair sejak Agustus”.',
                        ]),
                        $this->table(
                            ['No.', 'Meja', 'Aturan yang dipakai'],
                            [
                                ['1', 'Infrastruktur', 'Genteng di jalan adalah bahaya fisik.'],
                                ['2', 'Ragu', 'Baru bertanya syarat. Belum ada pengaduan izin yang ditolak.'],
                                ['3', 'Infrastruktur', 'Ada sampah dan lampu mati. Bahaya fisik menang.'],
                                ['4', 'Sosial', 'Bantuan tidak cair. Tidak ada bahaya fisik.'],
                            ],
                        ),
                        $this->heading('Tiga puluh menit: dua hitungan'),
                        $this->paragraph('Unduh lembar_september.py pada lesson sasaran, lalu jalankan python3 lembar_september.py. Tempel keluaran recall = 0.85 dan menit Dina = 160 ke kertas, lalu tulis ulang pembagiannya dengan koma desimal: 120 / 800 = 0,15, recall 0,85, dan 80 tiket kali 2 menit = 160 menit. Jika hasilmu berbeda dari keluaran berkas, kamu salah pada perkalian, bukan pada selera.'),
                        $this->paragraph('Lalu hitung baris yang belum ada kuncinya. Sosial pada September berjumlah 480. Pilih berapa yang boleh terlewat. Recall minimum sama dengan 1 dikurangi (jumlah terlewat dibagi 480). Tulis satu kalimat: apa yang terjadi jika teks bansos dibaca meja kependudukan atau meja kebersihan. Angka sosial ini masuk ke tugas. Jangan menyalin ambang infrastruktur lalu mengganti namanya menjadi sosial.'),
                        $this->heading('Tiga puluh menit: mulai rencana dari halaman vendor'),
                        $this->paragraph('Salin halaman vendor ke kertas: “Akurasi 95 persen. Chatbot menjawab warga. Go-live Jumat. GPU cloud. Tim data menyiapkan semuanya.” Coret tiap kalimat dan ganti. Penggantinya memakai lingkup, lima langkah, nama Bima, Raka, Ibu Lestari, dan kamu, plus jadwal enam minggu yang berisi sepuluh hari kerja mode bayangan. Bawa coretan ini ke tugas. Tugas meminta dokumen utuh, bukan coretan.'),
                        $this->heading('Yang dikumpulkan pada tugas'),
                        $this->bullets([
                            'Sasaran teknis: keputusan, masukan, keluaran, lingkungan, enam ambang dengan asal angkanya, baris sosial hasil hitunganmu, dan satu kalimat syarat berhenti.',
                            'Rencana: di dalam lingkup, di luar lingkup, lima langkah dengan nama, jadwal enam minggu, dan empat risiko. Tiap risiko punya tanda yang bisa diperiksa, mitigasi, dan pemilik.',
                        ]),
                        $this->paragraph('Dokumen yang lemah berbunyi “model akan dipasang minggu depan dengan akurasi maksimal”. Dokumen yang cukup membuat Ibu Lestari bisa menolak hari Jumat di halaman vendor tanpa harus membaca laporan pelatihan model. Ia melihat 800, 120, dan 0,85, lalu melihat bahwa chatbot dan GPU cloud ada di luar lingkup.'),
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
                        $this->paragraph('Pagi hari 2, Bima meletakkan paket: catatan label lima meja, notebook evaluasi, dua artefak model, dan satu lembar metrik. Baseline adalah aturan kata kunci ditambah regresi logistik. Kandidat kedua adalah encoder bahasa Indonesia. Kamu tidak melatih model baru di lesson ini.'),
                        $this->paragraph('Yang kamu jalankan di sini adalah evaluasi_latihan.py. Bobotnya kecil supaya satu baris bisa dihitung tangan, datanya 22 pengaduan latihan, dan angkanya bukan paket siang. Lembar yang masuk berita acara ada di Tugas Hari 2. Angka kuis, angka berkas latihan, dan angka paket siang adalah tiga lembar yang berbeda. Jangan menyalin macro-F1 atau recall dari berkas latihan ke berita acara.'),
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
                        $this->heading('Notebook latihan, bukan paket siang'),
                        $this->paragraph('Unduh berkas di atas halaman ini, lalu jalankan python3 evaluasi_latihan.py. Modelnya jumlah bobot kata, ditambah bias meja besar. Jika selisih dua skor teratas di bawah 0.5, fungsi mengembalikan ragu. Pada “lampu jalan mati dan sampah menumpuk” kebersihan mencetak 2.3 dan infrastruktur 2.0. Selisihnya 0.3, jadi ragu. Label emasnya infrastruktur, karena lampu mati adalah bahaya fisik. Aturan dinas itu tidak ada di bobot. “jalan rusak di rt 04” hanya menyalakan fitur jalan dengan skor 1.0, terlalu dekat dengan bias kependudukan 0.6, dan juga jatuh ke ragu. “kk saya tertinggal di kampung” tidak menyalakan fitur apa pun: berkas ini tidak punya fitur kk, jadi prediksinya ragu padahal labelnya kependudukan.'),
                        $this->paragraph('Pada 22 baris itu, tanpa kalimat setelah penanganan, berkas mencetak akurasi 0.86, macro-F1 0.85, dan recall infrastruktur 0.50. lulus ambang 0.85 pada data jujur = False. Macro-F1 yang tercetak 0.85 bukan recall infrastruktur. Ambang yang ditandatangani adalah recall, dan recall-nya 0.50. Setelah kalimat “sudah diteruskan ke bidang PU” ditempel hanya pada baris infrastruktur, recall kelas itu naik menjadi 1.00 dan macro-F1 menjadi 0.94. Kenaikan itu tidak sah: kalimatnya belum ada di loket. Uji 2026-10-06 lebih baru daripada latih 2026-09-30. Uji 2026-09-18 tidak.'),
                        $this->fixtureCode('evaluasi_latihan.py'),
                        $this->paragraph('Keluaran yang sesuai:'),
                        $this->fixtureCode('evaluasi_latihan.out.txt', 'text'),
                        $this->paragraph('Bentuk temuan yang sah pada paket siang memakai cara yang sama, dengan kutipan dari paket itu, bukan dari 22 baris ini. Sebuah baris latih memuat kalimat yang baru ada setelah petugas menangani pengaduan, misalnya “sudah diteruskan ke bidang PU”. Itu kebocoran. Label lama yang berisi “ke Dina” atau nama staf, bukan nama meja, mengajarkan giliran jaga. Kamu baru boleh menulis temuan itu di berita acara jika kamu mengutip barisnya dari paket siang.'),
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
                            '“lampu jalan mati dan sampah menumpuk” jatuh ke ragu karena selisih skor 0.3. Bahaya fisik tidak menang. Warga yang mengadu dua hal sekaligus tidak terlayani mesin, dan Dina tetap mengerjakan tiket berbahaya itu.',
                            '“jalan rusak di rt 04” jatuh ke ragu karena fitur jalan hanya bernilai 1.0, kalah tipis dari bias kependudukan. Kata “ambles” tidak ada, padahal kerusakannya infrastruktur.',
                            '“ambles gan” pada berkas latihan justru kena, karena bobot ambles adalah 2.0. Teks pendek tidak otomatis salah. Yang rapuh adalah teks yang tidak menyalakan kata yang kebetulan ada di daftar fitur.',
                            '“kk saya tertinggal di kampung” jatuh ke ragu karena fitur kk tidak ada. Meja terbesar pun salah jika kata yang dipakai warga tidak masuk vektor.',
                        ]),
                        $this->paragraph('Bias di sini terlihat di bobot. Kependudukan membawa bias 0.6 sebelum ada kata yang menyala. Infrastruktur membawa bias 0.0. Pada data yang timpang, meja besar menang saat buktinya lemah. Itu alasan gerbang recall, dan angkanya ada di keluaran evaluasi_latihan.py: akurasi 0.86 menyembunyikan recall infrastruktur 0.50.'),
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
                        $this->heading('Celah sebagai fungsi'),
                        $this->paragraph('celah_proyek.py memeriksa satu serahan latihan. Ia bukan paket siang. Recall infrastruktur kosong, layanan kosong, rollback belum diuji, tidak ada pemilik metrik, GPU cloud menyala, dan go-live dijanjikan lima hari lagi. Unduh berkas di atas halaman ini, lalu jalankan python3 celah_proyek.py. Enam celah harus tercetak. Pada tugas, kamu mengisi argumen fungsi itu dari paket siang, bukan menyalin kamus di berkas.'),
                        $this->fixtureCode('celah_proyek.py'),
                        $this->paragraph('Keluaran yang sesuai:'),
                        $this->fixtureCode('celah_proyek.out.txt', 'text'),
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
                        $this->paragraph('Status belum sah berbeda dari gagal ambang. Angka yang kelihatan memenuhi ambang pada data bocor masuk sebagai angka yang harus diulang, bukan sebagai kelulusan. jejak_ambang.py memisahkan dua pertanyaan itu. status_bukti() hanya menjawab apakah angkanya boleh dipakai. lulus_ambang() membandingkan recall dengan 0.85, dan mengembalikan False jika datanya tidak jujur walaupun recall-nya 1.00.'),
                        $this->paragraph('Tiga baris di berkas memakai hasil evaluasi_latihan.py, bukan paket siang. Latihan jujur: bukti lolos, recall 0.50, lulus ambang False. Kalimat setelah ditangani: bukti belum sah, recall 1.00, lulus ambang False. Tanpa bukti latensi: celah. Unduh berkas di atas halaman ini, lalu jalankan python3 jejak_ambang.py. Pada paket siang kamu menambah satu baris untuk tiap ambang hari 1, dengan angka dari tugas.'),
                        $this->fixtureCode('jejak_ambang.py'),
                        $this->paragraph('Keluaran yang sesuai:'),
                        $this->fixtureCode('jejak_ambang.out.txt', 'text'),
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
                        $this->paragraph('Unit J.62AIN00.015.1 meminta pemasangan solusi AI. Yang boleh kamu rancang mengikuti putusan berita acara hari 2. Putusan tunda berarti layanan siap untuk mode bayangan, dan syarat yang belum terpenuhi ditulis sebagai gerbang. Putusan tolak berarti kamu menulis cara jalur itu tetap mati, bukan jadwal promosi. Putusan layak tetap melewati gerbang di bawah sebelum antrian warga berubah.'),
                        $this->paragraph('Kontraknya sempit, dan ada berkasnya. layanan_bayangan.py memotong NIK dan HP, memilih meja, lalu menulis log. MODE_BAYANGAN benar, jadi meja_antrian tercetak None: antrian warga tidak berubah, saran meja tetap ada untuk dibandingkan dengan Dina. Jika identitas masih ada di baris log, tulis_log() melempar kesalahan dan proses berhenti. LMS ini tidak memasang layanan ke dinas. Unduh berkas di atas halaman ini, lalu jalankan python3 layanan_bayangan.py di komputermu. Raka yang nanti menjalankan kontrak yang sama di jaringan dinas.'),
                        $this->fixtureCode('layanan_bayangan.py'),
                        $this->paragraph('Keluaran yang sesuai. Lima baris log tidak memuat 16 digit dan tidak memuat 081234567890. meja_antrian=None pada kelimanya.'),
                        $this->fixtureCode('layanan_bayangan.out.txt', 'text'),
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
                        $this->paragraph('Rollback adalah bendera trafik, bukan pembangunan ulang. Di layanan_bayangan.py, rollback() mengembalikan MODE_BAYANGAN ke benar dan mencatat antrian Dina. uji_rollback(6) lulus. uji_rollback(22) gagal karena melewati 15 menit. Raka mengalihkan antrean ke jalur manual. Kamu mencatat menitnya. Jika caranya “telepon Raka lalu edit konfigurasi yang tidak disimpan”, kamu belum memasang solusi.'),
                        $this->paragraph('health() pada lima permintaan latihan mencetak identitas_di_log = 0 dan porsi_ragu = 0.20. Nol itu belum membuktikan pemotong hidup di produksi. Berkas ini memanggil redaksi() sebelum tulis_log(), jadi identitas sudah hilang sebelum health() menghitung. Uji yang berlaku adalah baris log memuat [NIK] atau [HP] ketika masukan masih membawa nomor, bukan sekadar cacah nol. Lima permintaan juga terlalu sedikit untuk menagih ambang ragu 20 persen.'),
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
                        $this->paragraph('Pemicu di runbook harus angka, dan angkanya diturunkan dari sasaran hari 1, bukan dari berita rilis model. picu_latih_ulang() di layanan_bayangan.py membandingkan recall minggu ini dengan 0.85. Keluaran untuk 0.81 adalah “tahan, panggil Bima, jangan promosikan”. Keluaran untuk 0.92 adalah “lanjut pantau”. Tiga bentuk yang sah pada runbook dinas: macro-F1 mingguan turun sekian dari titik mode bayangan yang diterima, recall kelas mahal jatuh di bawah ambang hari 1, atau saluran baru belum punya sampel di data latih. Salah satu saja sudah cukup untuk menghentikan promosi dan memanggil Bima. Kamu yang memiliki pemicu. Bima yang mengulang pelatihan. Kamu yang meninjau ulang sebelum versi baru naik. Angka pemicu untuk paket siang kamu tulis di tugas, tidak disalin dari 0.81.'),
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
                        $this->paragraph('Contoh yang kamu tulis di latihan ada di keluaran redaksi.py. redaksi_nik_saja() pada “ibu saya nomor 081234567890 bansos belum cair” membiarkan nomor itu utuh, dan ada_identitas() tetap True. redaksi() menggantinya menjadi [HP]. Itu insiden pada rancangan, walaupun belum keluar dari jaringan dinas. Catatan insiden menyebut apa yang bocor, berapa lama, siapa yang diakses lognya, apa yang dipotong hari itu, dan uji yang berubah besoknya: uji_log() harus gagal pada HP, bukan hanya pada 16 digit. “Sudah kami hapus” tanpa uji itu adalah tutup buku, bukan perawatan.'),
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
                        $this->paragraph('Portofolio yang paling dekat dengan bukti skema adalah tiga tugas ini, setelah kamu rapikan. Sasaran dan rencana untuk J.62AIN00.002.1 dan J.62AIN00.004.1. Dua berita acara untuk J.62DMI00.015.1 dan J.62DMI00.020.1. Laporan akhir yang memuat J.62AIN00.015.1, J.62AIN00.017.1, J.62PDP00.011.1, dan J.62DMI00.021.1. Tujuh berkas Python adalah latihan supaya artefak itu punya hitungan dan uji. Menyalin keluaran berkas latihan ke paket siang tidak memadai, karena angkanya berbeda. Course ini bukan formulir APL 01 atau APL 02.'),
                        $this->paragraph('Kalau putusanmu tunda dengan syarat, kamu tidak gagal sebagai insinyur. Kamu menolak memasang model yang belum layak ke antrian warga. Itu salah satu pekerjaan yang diminta okupasi ini.'),
                    ],
                ),
            ],
        ];
    }
}
