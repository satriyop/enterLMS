<?php

namespace Database\Seeders\Concerns;

/**
 * Syllabus for Membangun Model AI untuk Kasus Tertentu.
 *
 * Running case: Biro Layanan at a kampus — five ticket queues.
 * Lessons stay teaching text + figures. Not a live training lab.
 */
trait BuildsAiUseCaseSyllabus
{
    /**
     * @return list<array{title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    protected function sections(): array
    {
        return [
            $this->sectionFraming(),
            $this->sectionData(),
            $this->sectionModel(),
            $this->sectionTrain(),
            $this->sectionEval(),
            $this->sectionServe(),
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
    private function sectionFraming(): array
    {
        return [
            'title' => 'Merangka kasus dan metrik sukses',
            'description' => 'Putuskan keputusan apa yang diambil model, dan bagaimana kamu tahu ia berhasil untuk kasus itu.',
            'lessons' => [
                $this->textLesson(
                    'Untuk siapa kursus ini',
                    'Kursus mendalam untuk orang yang harus membangun atau meninjau satu model AI untuk kasus nyata — bukan survei generik.',
                    16,
                    [
                        $this->heading('Untuk siapa kursus ini', 1),
                        $this->paragraph('Kursus ini untuk kamu yang harus membangun, meninjau, atau menolak sebuah model AI untuk satu kasus nyata. Staf data, pengembang, arsitek, atau pimpinan yang harus bertanya dengan tepat sebelum ada yang belanja tools.'),
                        $this->paragraph('Ini Open Course, gratis, dalam Bahasa Indonesia. Kamu boleh enroll sendiri.'),
                        $this->paragraph('Ini bukan pengantar “apa itu AI”. Benang merahnya satu kasus: klasifikasi tiket layanan di Biro Layanan sebuah kampus. Setiap Lesson memakai tiket yang sama. Teknik yang tidak masuk akal untuk tiket itu, kita buang.'),
                        $this->paragraph('Lesson adalah teks dan diagram. Bukan notebook, bukan GPU, dan bukan konsol runtime. Menyelesaikan kursus ini tidak membuka Course terbatas Administrasi Agen OpenClaw.'),
                        $this->heading('Yang akan kamu kuasai'),
                        $this->bullets([
                            'Menulis keputusan yang akan diambil model, lengkap dengan biaya salah.',
                            'Menyusun data, label, split temporal, dan daftar kebocoran.',
                            'Memilih baseline sebelum encoder, dan menolak model generatif jika tugasnya klasifikasi.',
                            'Mengevaluasi dengan metrik kasus: macro-F1, recall kelas mahal, ambang “tidak yakin”.',
                            'Merancang serving, mode bayangan, umpan balik petugas, privasi, dan putaran ulang.',
                        ]),
                        $this->diagram(
                            'Enam langkah yang sama diulang untuk satu kasus: tiket layanan Biro Layanan.',
                            [
                                ['id' => 'kasus', 'label' => 'Kasus', 'hint' => 'metrik'],
                                ['id' => 'data', 'label' => 'Data', 'hint' => 'split'],
                                ['id' => 'model', 'label' => 'Model', 'hint' => 'baseline'],
                                ['id' => 'latih', 'label' => 'Latih', 'hint' => 'loss'],
                                ['id' => 'eval', 'label' => 'Evaluasi', 'hint' => 'macro-F1'],
                                ['id' => 'serve', 'label' => 'Serving', 'hint' => 'iterasi'],
                            ],
                            [],
                            3,
                        ),
                        $this->paragraph('Kalau yang kamu cari adalah tombol “latih di Lesson ini”, kamu di Course yang salah. Yang kamu bangun di sini adalah penilaian: kapan model layak masuk antrian, dan kapan ia masih tebakan mahal.'),
                    ],
                    preview: true,
                ),
                $this->textLesson(
                    'Kasus yang kita bangun: klasifikasi tiket layanan',
                    'Satu biro, lima antrian, satu keputusan: ke meja mana tiket ini pergi.',
                    18,
                    [
                        $this->heading('Kasus yang kita bangun', 1),
                        $this->paragraph('Biro Layanan menerima ratusan tiket sehari. Petugas Sari membaca, lalu mengirim tiap tiket ke satu dari lima antrian: akses, jaringan, aplikasi, perangkat, atau permintaan.'),
                        $this->paragraph('Salah antrian memperpanjang SLA. Antrian yang benar tapi terlambat juga gagal. Itu kasusnya. Bukan “AI untuk helpdesk”. Bukan chatbot yang membalas mahasiswa.'),
                        $this->heading('Lima tiket yang akan kembali'),
                        $this->bullets([
                            '“gak bisa login SSO, muncul 401” → akses.',
                            '“wifi gedung B lt 3 mati dari kmrn” → jaringan.',
                            '“absensi error 500 pas absen pagi” → aplikasi.',
                            '“monitor laptop berkedip” → perangkat.',
                            '“mohon akses folder bersama fakultas” → permintaan.',
                        ]),
                        $this->paragraph('Keputusan model sangat sempit. Masukan: judul + isi tiket, pada saat tiket dibuat. Keluaran: satu dari lima kelas, atau “tidak yakin” supaya Sari yang memutuskan.'),
                        $this->diagram(
                            'Teks tiket masuk. Model hanya boleh mengirim ke antrian, atau mengaku ragu.',
                            [
                                ['id' => 'tiket', 'label' => 'Teks tiket', 'hint' => 'judul + isi'],
                                ['id' => 'model', 'label' => 'Model', 'hint' => '5 kelas'],
                                ['id' => 'label', 'label' => 'Label', 'hint' => 'antrian'],
                                ['id' => 'ragu', 'label' => 'Tidak yakin', 'hint' => 'ke manusia'],
                            ],
                            [
                                ['from' => 'tiket', 'to' => 'model'],
                                ['from' => 'model', 'to' => 'label', 'label' => 'yakin'],
                                ['from' => 'model', 'to' => 'ragu', 'label' => 'ragu'],
                            ],
                            4,
                        ),
                        $this->paragraph('Kita tidak ganti kasus di tengah kursus. Kalau suatu teknik tidak membantu Sari mengantar tiket, itu bukan bagian dari kasus ini.'),
                    ],
                ),
                $this->textLesson(
                    'Masalah bisnis bukan masalah model',
                    'Jangan mulai dari arsitektur. Mulai dari keputusan, pemilik, dan apa yang terjadi jika salah.',
                    16,
                    [
                        $this->heading('Masalah bisnis bukan masalah model', 1),
                        $this->paragraph('Kalimat “kita butuh model klasifikasi teks” belum rumusan kasus. Rumusan kasus menjawab empat hal: siapa yang memutuskan hari ini, apa yang ia putuskan, berapa sering ia salah, dan apa biayanya.'),
                        $this->paragraph('Hari ini Sari yang memutuskan. Ia membaca antrian masuk, memilih satu dari lima meja teknis. SLA dihitung dari tiket dibuat sampai antrian yang benar menyentuhnya.'),
                        $this->paragraph('Kalau aturan kata kunci Sari sudah benar 92%, model harus mengalahkan itu pada kelas yang mahal — bukan hanya rata-rata. Kalau volume hanya 20 tiket sehari, model mungkin tidak layak: anotasi dan pemeliharaan lebih mahal daripada Sari yang sudah hafal pola.'),
                        $this->heading('Tiga pertanyaan sebelum data'),
                        $this->numbered([
                            'Keputusan apa, dengan input apa, pada saat apa? Teks tiket, saat dibuat, sebelum manusia membaca.',
                            'Apa yang terjadi jika salah? Akses ke perangkat: SLA terbakar. Permintaan ke jaringan: teknisi kesal, pengguna menunggu.',
                            'Siapa pemilik metrik setelah model jalan? Bukan “tim AI”. Kepala Biro Layanan.',
                        ]),
                        $this->paragraph('Baru setelah tiga jawaban itu tertulis, kamu boleh bicara TF-IDF atau encoder. Orang yang memulai dari “IndoBERT vs model generatif” tanpa pemilik metrik sedang belanja arsitektur.'),
                        $this->table(
                            ['Salah rumusan', 'Rumusan yang bisa dibangun'],
                            [
                                ['“AI untuk helpdesk”', 'Klasifikasi teks tiket ke 5 antrian, atau eskalasi, dalam 2 detik'],
                                ['“Akurasi tinggi”', 'Macro-F1 ≥ baseline aturan, recall perangkat ≥ 0,80'],
                                ['“Otomatisasi penuh”', 'Model boleh menolak; Sari tetap pemilik tiket ragu'],
                            ],
                        ),
                    ],
                ),
                $this->textLesson(
                    'Metrik sukses yang selaras dengan kasus',
                    'Akurasi global menyembunyikan kelas mahal. Tulis metrik seperti SLA, bukan seperti kompetisi.',
                    18,
                    [
                        $this->heading('Metrik sukses yang selaras dengan kasus', 1),
                        $this->paragraph('Akurasi global adalah jebakan. Jika 55% tiket adalah “akses”, model yang selalu menjawab “akses” sudah berakurasi 55% — dan gagal total sebagai router.'),
                        $this->paragraph('Pimpinan yang hanya mendengar “akurasi 91%” tidak tahu bahwa kelas perangkat — jarang, mahal, sering hardware kritis — recall-nya 0,40. Monitor berkedip mengantri di tim aplikasi sampai sore.'),
                        $this->heading('Dua lapisan metrik'),
                        $this->bullets([
                            'Metrik model: macro-F1 (rata-rata F1 lima kelas, tidak tertarik volume), recall perangkat dan jaringan, persentase tiket ke manusia.',
                            'Metrik bisnis: persen tiket di antrian benar dalam SLA, rata-rata hop antar-antrian, beban Sari untuk tiket ragu.',
                            'Jembatannya: setiap galat harus bisa diceritakan sebagai hop ekstra atau SLA pecah.',
                        ]),
                        $this->paragraph('Tiket ke manusia karena skor di bawah ambang bukan kegagalan model. Itu desain. Sari masih lebih murah daripada hop yang salah ke perangkat.'),
                        $this->diagram(
                            'Prediksi diukur per kelas. Bisnis diukur di antrian dan SLA. Keduanya harus selaras.',
                            [
                                ['id' => 'pred', 'label' => 'Prediksi', 'hint' => 'label + skor'],
                                ['id' => 'f1', 'label' => 'Macro-F1', 'hint' => '5 kelas'],
                                ['id' => 'rec', 'label' => 'Recall mahal', 'hint' => 'perangkat'],
                                ['id' => 'sla', 'label' => 'SLA antrian', 'hint' => 'hop & waktu'],
                            ],
                            [
                                ['from' => 'pred', 'to' => 'f1'],
                                ['from' => 'pred', 'to' => 'rec'],
                                ['from' => 'f1', 'to' => 'sla'],
                                ['from' => 'rec', 'to' => 'sla'],
                            ],
                            4,
                        ),
                        $this->paragraph('Tulis target sebelum melihat angka uji. Untuk Biro Layanan, contoh yang layak: mengalahkan baseline aturan pada macro-F1; recall perangkat minimal 0,80; maksimal 25% tiket ke Sari; p95 latency 200 ms.'),
                        $this->paragraph('Tanpa ambang “kita belum siap serving”, kamu belum punya kasus. Kamu punya demo.'),
                    ],
                ),
                $this->textLesson(
                    'Kapan kasus ini tidak butuh model',
                    'Aturan, antrian manusia, dan “belum cukup data” adalah jawaban yang sah — dan sering lebih murah.',
                    14,
                    [
                        $this->heading('Kapan kasus ini tidak butuh model', 1),
                        $this->paragraph('Membangun model mahal: anotasi, pemeliharaan, drift, audit. Beberapa pola Biro Layanan selesai tanpa model.'),
                        $this->paragraph('“Reset password” dengan frasa tetap? Aturan. Tiket yang selalu berisi kode gedung di judul, lalu peta ruangan ke antrian jaringan? Tabel lookup. Volume 15 tiket/hari dengan tiga petugas yang sudah hafal? Antrian manusia.'),
                        $this->heading('Daftar penolakan yang sehat'),
                        $this->bullets([
                            'Kelas belum disepakati: lima orang, lima ontologi. Model akan belajar kekacauan.',
                            'Label historis adalah nama petugas (“ke Sari”), bukan jenis masalah. Itu bukan target.',
                            'Tiket berisi catatan resolusi di akhir — data yang tidak ada saat prediksi.',
                            'Pimpinan ingin chatbot yang menyelesaikan tiket, padahal yang bisa diotomasi hanya routing.',
                            'Tidak ada pemilik yang akan membaca confusion matrix setiap bulan.',
                        ]),
                        $this->paragraph('Kasus Biro Layanan lolos daftar itu: lima kelas bisa ditulis, volume cukup, keputusan sempit, Sari bisa mengoreksi. Kamu harus bisa menjelaskan mengapa kasusmu lolos, bukan mengapa modelmu keren.'),
                        $this->paragraph('Jika setelah Lesson ini kamu memutuskan organisasimu belum siap model, itu kelulusan. Model yang tidak dibangun karena kasusnya belum jernih lebih dewasa daripada model yang diserving untuk slide.'),
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
            'title' => 'Data untuk kasus itu',
            'description' => 'Mengumpulkan, melabeli, membagi, dan mencegah kebocoran pada tiket layanan.',
            'lessons' => [
                $this->textLesson(
                    'Sumber tiket dan apa yang boleh dipakai',
                    'Portal, email, chat, dan log — masing-masing membawa bidang yang berbeda, dan tidak semua boleh masuk model.',
                    16,
                    [
                        $this->heading('Sumber tiket dan apa yang boleh dipakai', 1),
                        $this->paragraph('Tiket Biro Layanan jarang lahir di satu tempat. Portal punya judul, isi, kategori yang diklik mahasiswa (sering salah), dan identitas. Email punya subject, thread, dan signature. Chat pendek dan penuh slang. Log kadang menempel kode error.'),
                        $this->paragraph('Pada v1, model hanya boleh melihat apa yang akan ada saat tiket baru dibuat: judul dan isi. Bukan catatan resolusi. Bukan nama Sari yang menutup tiket. Bukan antrian terakhir setelah tiga hop.'),
                        $this->paragraph('Kategori yang diklik pengguna adalah sinyal lemah, bukan emas. Mahasiswa sering memilih “umum” atau “aplikasi” karena itu tombol pertama. Kalau itu yang kamu pakai sebagai target, model belajar kebiasaan UI, bukan masalahnya.'),
                        $this->heading('Bidang yang masuk dan yang dilarang di v1'),
                        $this->table(
                            ['Bidang', 'Pakai di v1?', 'Alasan'],
                            [
                                ['Judul + isi tiket', 'Ya', 'Tersedia saat prediksi'],
                                ['Bahasa campur, typo, kode error', 'Ya', 'Itu teks produksi'],
                                ['Kategori klik pengguna', 'Sinyal lemah', 'Bukan emas; boleh jadi fitur, bukan target'],
                                ['Nama petugas, nomor tiket', 'Tidak', 'Bocor; tidak tersedia / tidak stabil'],
                                ['Catatan resolusi', 'Tidak', 'Belum ada saat routing awal'],
                                ['Identitas lengkap pengguna', 'Tidak', 'Privasi; jarang membantu kelas'],
                            ],
                        ),
                        $this->paragraph('Tulis kontrak input seperti API: string judul, string isi, opsional sumber kanal. Segala sesuatu di luar itu adalah godaan overfitting. Lesson kebocoran akan kembali ke tabel ini dengan contoh yang merusak metrik.'),
                    ],
                ),
                $this->textLesson(
                    'Ontologi label dan panduan anotasi',
                    'Lima kelas harus bisa dibedakan manusia. Jika anotator tidak setuju, model tidak akan ajaib.',
                    18,
                    [
                        $this->heading('Ontologi label dan panduan anotasi', 1),
                        $this->paragraph('Lima kelas Biro Layanan harus bisa dibedakan Sari dan rekannya, tanpa “terserah”. Jika dua manusia tidak setuju, model tidak akan ajaib. Ia hanya menghafal kekacauan itu.'),
                        $this->bullets([
                            'Akses: identitas, login, hak, SSO, akun VPN.',
                            'Jaringan: koneksi, Wi-Fi, DNS, terowongan VPN, putus gedung.',
                            'Aplikasi: bug, error 5xx, fitur tidak jalan.',
                            'Perangkat: laptop, monitor, printer, periferal.',
                            'Permintaan: folder baru, lisensi, perubahan yang bukan insiden.',
                        ]),
                        $this->heading('Batas yang harus diputuskan tertulis'),
                        $this->paragraph('“Tidak bisa VPN.” Akun dinonaktifkan? Itu akses. Terowongan putus? Itu jaringan. Panduan harus memilih, lengkap dengan alasan.'),
                        $this->paragraph('“Absensi error.” Bug di server absensi? Aplikasi. Mesin wajah di pintu tidak baca? Perangkat. Diam adalah kebijakan terburuk.'),
                        $this->heading('Isi panduan yang wajib ada'),
                        $this->numbered([
                            'Definisi satu kalimat per kelas, plus lima contoh kanonik.',
                            'Lima contoh batas dengan keputusan tertulis.',
                            'Tiket campur: pilih kelas yang menghalangi pengguna sekarang, atau eskalasi. Jangan berharap model membelah tiket.',
                            'Label tetap Bahasa Indonesia. Teks sumber boleh campur.',
                            'Tiket kosong, satu kata, atau hanya screenshot: v1 “tidak yakin”.',
                        ]),
                        $this->paragraph('Ukur kesepakatan di 200 tiket yang dilabel dua orang. Jika kelas perangkat tidak disepakati, jangan latih. Perbaiki panduan. Model tidak menyembuhkan ontologi yang pecah.'),
                    ],
                ),
                $this->textLesson(
                    'Mengumpulkan, menyeimbangkan, dan membersihkan',
                    'Volume, kelas minoritas, duplikat, template, dan PII — pekerjaan data sebelum split.',
                    16,
                    [
                        $this->heading('Mengumpulkan, menyeimbangkan, dan membersihkan', 1),
                        $this->paragraph('Kumpulkan tiket yang sudah sampai antrian benar — bukan yang masih melayang. Target emas: antrian akhir yang disepakati petugas, setelah hop salah dibuang, dan antrian itu memetakan 1:1 ke lima kelas.'),
                        $this->paragraph('Jika historis memakai 30 kategori lama, petakan dulu. Buang yang tidak masuk lima kelas. Hitung sisa per kelas. Kelas perangkat akan miskin. Itu normal.'),
                        $this->paragraph('Jangan “menyeimbangkan” dengan menyalin tiket yang sama 20 kali ke data uji. Oversampling dan bobot kelas hanya di data latih. Data uji harus mengikuti hidup, termasuk ketimpangan, karena SLA hidup juga timpang.'),
                        $this->heading('Pembersihan yang tidak merusak sinyal'),
                        $this->bullets([
                            'Buang duplikat exact dan near-duplicate di latih (mahasiswa yang kirim ulang).',
                            'Hapus signature email dan disclaimer. Itu bukan masalahnya.',
                            'Ganti NIP, email, nomor HP dengan placeholder. Jangan hapus seluruh kalimat jika sisanya masih bermakna.',
                            'Jangan menormalisasi sampai hilang: “gak bisa login” vs “tidak dapat autentikasi” adalah variasi produksi.',
                            'Simpan kode error (401, 500, ERR_CONNECTION_RESET). Itu fitur, bukan sampah.',
                        ]),
                        $this->paragraph('Catat berapa tiket yang dibuang dan mengapa. Jika 40% historis tidak terpetakan, ontologi operasional belum sama dengan ontologi model. Kembali ke Lesson panduan, bukan memaksa latih.'),
                    ],
                ),
                $this->textLesson(
                    'Split temporal: latih, validasi, uji',
                    'Tiket adalah deret waktu. Acak 80/20 membocorkan masa depan ke masa lalu.',
                    18,
                    [
                        $this->heading('Split temporal: latih, validasi, uji', 1),
                        $this->paragraph('Tiket Biro Layanan punya musim: awal semester, rilis absensi, pindah SSO, pengadaan laptop. Pola “minggu depan” tidak boleh bocor ke data latih.'),
                        $this->paragraph('Kalau kamu mengacak semua tiket lalu 80/20, model melihat rilis SSO yang di dunia nyata belum terjadi. Angka uji jadi optimis. Serving di bulan berikutnya jatuh. Itu bukan nasib. Itu split yang salah.'),
                        $this->paragraph('Urutkan menurut waktu dibuat. Latih di periode paling lama. Validasi di jendela berikutnya. Uji di jendela paling baru yang belum kamu sentuh saat memilih model.'),
                        $this->diagram(
                            'Masa lalu untuk latih, tengah untuk validasi, yang terbaru untuk uji. Hidup belum terjadi.',
                            [
                                ['id' => 'latih', 'label' => 'Latih', 'hint' => 'Jan–Jun'],
                                ['id' => 'val', 'label' => 'Validasi', 'hint' => 'Jul–Agu'],
                                ['id' => 'uji', 'label' => 'Uji', 'hint' => 'Sep–Okt'],
                                ['id' => 'hidup', 'label' => 'Hidup', 'hint' => 'belum terjadi'],
                            ],
                            [],
                            4,
                        ),
                        $this->heading('Aturan yang tidak boleh dilanggar'),
                        $this->bullets([
                            'Tidak ada tiket uji yang lebih tua dari tiket latih di unit yang sama.',
                            'Thread yang sama tidak boleh terbelah ke latih dan uji.',
                            'Validasi boleh dipakai berulang untuk pilih model dan ambang. Uji tidak.',
                            'Setelah serving, data baru tidak dicampur ke latih sampai putaran ulang yang terencana.',
                        ]),
                        $this->paragraph('Kalau datanya sedikit, orang tergoda validasi silang acak. Untuk teks tiket, itu hampir selalu salah. Lebih jujur: latih kecil, uji temporal kecil, dan akui ketidakpastian.'),
                    ],
                ),
                $this->textLesson(
                    'Kebocoran data yang merusak metrik',
                    'Nomor tiket, nama petugas, template, dan target yang menyelinap ke fitur.',
                    18,
                    [
                        $this->heading('Kebocoran data yang merusak metrik', 1),
                        $this->paragraph('Kebocoran adalah informasi yang tidak akan kamu miliki saat prediksi hidup, tetapi ada di data latih sehingga metrik tampak bagus.'),
                        $this->paragraph('Contoh di Biro Layanan. Nomor tiket “NET-2024-881” sudah mengandung kode antrian jaringan. Nama Sari di CC hanya muncul setelah routing manusia. Auto-reply “tiket perangkat Anda sedang…” ditulis sistem setelah kelas dipilih.'),
                        $this->paragraph('Jika model melihat itu, ia mencontek label. Ia tidak membaca “monitor berkedip”.'),
                        $this->heading('Katalog kebocoran kasus ini'),
                        $this->table(
                            ['Sumber bocor', 'Mengapa merusak', 'Pencegahan'],
                            [
                                ['Prefiks nomor tiket', 'Kode antrian lama = label', 'Hapus ID; jangan jadi fitur'],
                                ['Nama petugas / antrian di teks', 'Hanya ada setelah hop', 'Strip header internal'],
                                ['Template resolusi', 'Ditulis setelah kelas diketahui', 'Hanya pakai teks pengguna awal'],
                                ['Waktu tutup tiket', 'Tidak ada saat routing', 'Jangan masuk vektor'],
                                ['Duplikat lintas split', 'Uji bukan tiket baru', 'Dedup sebelum split'],
                            ],
                        ),
                        $this->diagram(
                            'Sinyal yang hanya ada setelah keputusan tidak boleh masuk ke model.',
                            [
                                ['id' => 'user', 'label' => 'Teks pengguna', 'hint' => 'boleh'],
                                ['id' => 'route', 'label' => 'Routing', 'hint' => 'target'],
                                ['id' => 'tmpl', 'label' => 'Template', 'hint' => 'bocor'],
                                ['id' => 'model', 'label' => 'Model nakal', 'hint' => 'mencontek'],
                            ],
                            [
                                ['from' => 'user', 'to' => 'route'],
                                ['from' => 'route', 'to' => 'tmpl'],
                                ['from' => 'tmpl', 'to' => 'model', 'label' => 'bocor'],
                                ['from' => 'user', 'to' => 'model', 'label' => 'sah'],
                            ],
                            4,
                        ),
                        $this->paragraph('Uji kasar: latih model linear hanya pada ID, nama, dan template. Jika F1-nya tinggi, kamu belum punya model bahasa. Kamu punya kebocoran yang terukur. Buang fitur itu, ulangi split, baru lanjut.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionModel(): array
    {
        return [
            'title' => 'Memilih model untuk kasus itu',
            'description' => 'Baseline, tangga kompleksitas, jenis tugas, bahasa, biaya, dan latensi — untuk lima kelas tiket, bukan untuk tren.',
            'lessons' => [
                $this->textLesson(
                    'Baseline dulu: aturan dan model linear',
                    'Kata kunci, TF-IDF, regresi logistik. Tanpa ini, encoder tidak punya lawan yang jujur.',
                    18,
                    [
                        $this->heading('Baseline dulu: aturan dan model linear', 1),
                        $this->paragraph('Sebelum encoder, tulis aturan. Daftar frasa yang hampir pasti. “Reset password”, “lupa OTP”, “tidak bisa SSO” → akses. “Wi-Fi gedung”, “DNS”, “ping timeout” → jaringan. “Printer”, “monitor berkedip” → perangkat.'),
                        $this->paragraph('Aturan akan salah di tiket campur dan slang. Tapi ia murah, bisa diaudit, dan sering sudah menutup 40–60% volume. Itu lantai. Model yang kalah dari aturan di kelas yang sama tidak layak serving.'),
                        $this->paragraph('Baseline statistik pertama: hitung seberapa sering kata dan pasangan kata muncul di judul+isi (TF-IDF), lalu regresi logistik ke lima kelas, dengan bobot supaya kelas miskin tidak diabaikan. Latih hanya di split latih. Pilih pengaturan di validasi. Laporkan macro-F1 di uji sekali.'),
                        $this->paragraph('Ini cepat. Ini menandai kebocoran: kalau F1-nya ajaib, cek ID tiket. Ini juga bisa dibaca: kata mana yang mendorong “perangkat”.'),
                        $this->diagram(
                            'Lantai yang wajib ada: aturan dulu, lalu model linear, baru naik tangga.',
                            [
                                ['id' => 'aturan', 'label' => 'Aturan', 'hint' => 'auditabel'],
                                ['id' => 'tfidf', 'label' => 'TF-IDF', 'hint' => 'baseline'],
                                ['id' => 'enc', 'label' => 'Encoder', 'hint' => 'jika perlu'],
                                ['id' => 'gen', 'label' => 'Generatif', 'hint' => 'jarang'],
                            ],
                            [],
                            4,
                        ),
                        $this->paragraph('Simpan prediksi baseline. Confusion-nya adalah peta: aplikasi vs permintaan akan bertabrakan; perangkat akan sering terlewat. Encoder hanya dibenarkan jika ia memperbaiki sel yang mahal, bukan jika ia menaikkan akurasi global satu poin dengan merusak perangkat.'),
                    ],
                ),
                $this->textLesson(
                    'Tangga model: dari TF-IDF ke encoder',
                    'Naik satu anak tangga hanya jika anak sebelumnya gagal di metrik kasus.',
                    16,
                    [
                        $this->heading('Tangga model: dari TF-IDF ke encoder', 1),
                        $this->paragraph('Naik tangga hanya jika target di validasi belum lulus. Bukan karena paper baru keluar.'),
                        $this->paragraph('Setelah regresi logistik: model linear lain pada vektor yang sama, jika kamu butuh batas yang lebih keras di kelas minoritas. Lalu encoder yang mengenal Bahasa Indonesia dan Inggris. Encoder membantu parafrase: “gak bisa masuk” ≈ “autentikasi gagal”. Ia tidak otomatis lebih baik di tiket yang sudah berisi kata “printer”.'),
                        $this->paragraph('Jangan meloncat ke model generatif berukuran besar untuk lima kelas. Klasifikasi adalah pemetaan ke himpunan tertutup. Model generatif bisa mengeluarkan label yang tidak ada, lebih mahal per tiket, lebih lambat, dan lebih sulit diaudit.'),
                        $this->diagram(
                            'Naik tangga hanya jika metrik kasus di validasi tidak lulus target.',
                            [
                                ['id' => 'b0', 'label' => 'Aturan', 'hint' => 'lantai'],
                                ['id' => 'b1', 'label' => 'Linear', 'hint' => 'TF-IDF'],
                                ['id' => 'b2', 'label' => 'Encoder', 'hint' => 'konteks'],
                                ['id' => 'stop', 'label' => 'Berhenti', 'hint' => 'target lulus'],
                            ],
                            [
                                ['from' => 'b0', 'to' => 'b1', 'label' => 'gagal'],
                                ['from' => 'b1', 'to' => 'b2', 'label' => 'gagal'],
                                ['from' => 'b2', 'to' => 'stop', 'label' => 'lulus'],
                            ],
                            4,
                        ),
                        $this->paragraph('Tulis kriteria: encoder hanya jika macro-F1 validasi naik cukup dan recall perangkat tidak turun. Tanpa itu, setiap demo baru akan mereset pekerjaan Sari.'),
                    ],
                ),
                $this->textLesson(
                    'Klasifikasi, ekstraksi, atau generasi',
                    'Pilih jenis tugas yang benar. Salah pilih tugas tidak bisa diselamatkan oleh model yang lebih besar.',
                    16,
                    [
                        $this->heading('Klasifikasi, ekstraksi, atau generasi', 1),
                        $this->paragraph('Kasus Biro Layanan adalah klasifikasi: satu label dari lima, atau tolak. Bukan ekstraksi (mengambil nomor aset atau nama gedung sebagai field). Bukan generasi (menulis balasan ke mahasiswa).'),
                        $this->paragraph('Mencampur tiga tugas di satu model membuat evaluasi tidak bisa ditafsirkan. F1 label bisa bagus, sementara balasan mengarang SOP yang tidak ada.'),
                        $this->paragraph('Kalau kampus juga ingin mengisi “gedung” dan “nomor aset”, itu pipa kedua, dengan evaluasi sendiri. Kalau ingin draf balasan, itu pipa ketiga, dan manusia wajib baca sebelum kirim. Jangan memasukkan keduanya ke v1 hanya karena vendor menjual “AI helpdesk all-in-one”.'),
                        $this->table(
                            ['Tugas', 'Keluaran', 'Evaluasi'],
                            [
                                ['Klasifikasi (kasus ini)', 'Satu kelas atau ragu', 'Macro-F1, recall mahal'],
                                ['Ekstraksi', 'Field terstruktur', 'Akurasi field, missing rate'],
                                ['Generasi balasan', 'Teks ke pengguna', 'Review manusia, bukan BLEU'],
                            ],
                        ),
                        $this->paragraph('Untuk lima kelas ini, encoder klasifikasi cukup: satu kepala kecil di atas ringkasan teks. Memaksa model generatif “jawab hanya dengan satu kata dari daftar” adalah klasifikasi yang mahal dan rapuh terhadap prompt.'),
                    ],
                ),
                $this->textLesson(
                    'Bahasa Indonesia, slang, dan kode tiket',
                    'Teks produksi bukan korpus berita. Tokenisasi, campur kode, dan istilah lokal menentukan pilihan model.',
                    16,
                    [
                        $this->heading('Bahasa Indonesia, slang, dan kode tiket', 1),
                        $this->paragraph('Tiket Biro Layanan jarang berupa paragraf baku. Contoh nyata: “pak wifi lt 3 ged B mati dari kmrn, sso juga kadang 401.” Ada singkatan, kode HTTP, nama gedung lokal, campur Inggris.'),
                        $this->paragraph('Model yang hanya nyaman di berita akan memotong slang menjadi potongan aneh. Tetap biasanya lebih tahan daripada TF-IDF yang tidak pernah melihat “gak” di kamus latih.'),
                        $this->paragraph('Pilihan praktis: encoder yang kuat di Indonesian dan English, lalu disesuaikan pada tiketmu. Cek 50 tiket nyata: apakah “SSO”, “Wi-Fi”, “ERR_CONNECTION_RESET”, dan nama absensi kampus utuh atau hancur? Jika nama aplikasi selalu dipecah dan itu sinyal kelas “aplikasi”, normalisasi nama produk ke token khusus — di latih dan di hidup, sama.'),
                        $this->heading('Yang tidak perlu kamu lakukan di v1'),
                        $this->bullets([
                            'Stemming agresif yang merusak “jaringan” vs “jalan”.',
                            'Menerjemahkan semua tiket ke Inggris “supaya model internasional lebih pintar”.',
                            'Membuang kode error karena “bukan bahasa alami”.',
                            'Lowercase buta jika kode produk peka huruf besar. Uji di validasi, jangan dogma.',
                        ]),
                        $this->paragraph('Model yang menang di benchmark sentimen Indonesia tidak otomatis menang di tiket Sari. Satu-satunya angka yang dihitung adalah split temporal Biro Layanan.'),
                    ],
                ),
                $this->textLesson(
                    'Biaya, latensi, dan interpretabilitas',
                    'p95 200 ms, biaya per tiket, dan kemampuan menjelaskan ke petugas — itu kendala kasus, bukan afterthought.',
                    15,
                    [
                        $this->heading('Biaya, latensi, dan interpretabilitas', 1),
                        $this->paragraph('Routing terjadi di jalur masuk. Mahasiswa tidak menunggu delapan detik. Sari tidak mau kotak hitam yang tidak bisa dibantah.'),
                        $this->paragraph('Tulis kendala untuk organisasimu. Contoh Biro Layanan: latency p95 paling 200 ms di jaringan internal. Biaya inferensi jauh di bawah biaya satu hop salah. Setiap prediksi menampilkan tiga kata atau token yang mendorong kelas, supaya Sari bisa menolak.'),
                        $this->paragraph('Encoder kecil yang lulus metrik mengalahkan encoder raksasa yang p95-nya dua detik. Model generatif yang “bisa menjelaskan” sering mengarang penjelasan. Koefisien regresi yang jujur lebih berguna di audit daripada paragraf fasih yang tidak terikat bobot.'),
                        $this->paragraph('Hitung kasar: 2.000 tiket/hari × 30 hari. Kalau encoder kecil hemat dibanding hop salah 5% volume, angka itu yang dibawa ke kepala biro — bukan jumlah parameter.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionTrain(): array
    {
        return [
            'title' => 'Melatih dan mengadaptasi',
            'description' => 'Representasi, menyesuaikan encoder, loop loss, kelas timpang, dan reproduksibilitas — tanpa menjadikan Lesson sebagai lab.',
            'lessons' => [
                $this->textLesson(
                    'Representasi teks tiket',
                    'Judul vs isi, truncasi, lowercase, dan token khusus produk — keputusan yang harus konsisten antara latih dan hidup.',
                    16,
                    [
                        $this->heading('Representasi teks tiket', 1),
                        $this->paragraph('Gabungkan judul dan isi dengan pemisah yang sama di latih dan di hidup, misalnya “JUDUL: … ISI: …”. Judul sering padat (“VPN down”). Isi berisi narasi. Jangan memotong judul.'),
                        $this->paragraph('Kalau encoder punya batas panjang, utamakan judul plus awal isi. Tiket yang menempel log panjang boleh dipotong setelah N karakter — dengan aturan yang sama di serving.'),
                        $this->paragraph('Untuk TF-IDF: jangan membuang angka. 401 dan 500 adalah sinyal. Untuk encoder: pakai tokenizer resmi model itu, jangan tokenizer lain. Jika kamu menormalisasi “absensi kampus” ke token APLIKASI_ABSENSI, normalisasi itu harus hidup di layanan yang sama, bukan hanya di skrip latih.'),
                        $this->paragraph('Simpan lima tiket mentah dan lima string yang benar-benar masuk model. Enam bulan kemudian, orang akan mereplikasi praproses dengan salah lalu menyalahkan “drift”.'),
                    ],
                ),
                $this->textLesson(
                    'Menyesuaikan encoder, bukan memaksa model generatif',
                    'Kepala klasifikasi di atas encoder, learning rate kecil, dan mengapa prompt bukan pengganti label.',
                    18,
                    [
                        $this->heading('Menyesuaikan encoder, bukan memaksa model generatif', 1),
                        $this->paragraph('Untuk lima kelas, pola yang sesuai: encoder yang sudah belajar bahasa, lalu kepala kecil ke lima skor kelas. Kamu menyesuaikan encoder pada tiket berlabel Biro Layanan. Itu fine-tune. Bukan prompt.'),
                        $this->paragraph('Jika data kecil, latih kepala dulu, baru lapisan atas. Laju belajar encoder lebih kecil daripada kepala. Itu pola standar, dan untuk kasus ini hampir selalu lebih tepat daripada menyuruh model generatif “pilih salah satu: akses, jaringan, …”.'),
                        $this->paragraph('Prompt tanpa menyesuaikan bobot adalah baseline yang sah untuk diukur. Lalu biasanya kalah di latency, biaya, dan label di luar daftar. Contoh di prompt juga bisa bocor: tiket hidup tidak boleh membawa lima contoh yang kamu tempel di instruksi.'),
                        $this->paragraph('Mencari tiket lama yang mirip bisa membantu Sari. Itu bukan klasifikasi. Jangan mencampur ke v1. Simpan sebagai percobaan terpisah setelah classifier lulus target.'),
                    ],
                ),
                $this->textLesson(
                    'Loop pelatihan: loss, batch, overfitting',
                    'Satu putaran: batch → loss → update → validasi. Overfitting terlihat di selisih latih vs validasi, bukan di akurasi latih.',
                    18,
                    [
                        $this->heading('Loop pelatihan: loss, batch, overfitting', 1),
                        $this->paragraph('Ambil sekelompok tiket latih (batch). Model mengeluarkan lima skor. Loss mengukur seberapa jauh skor itu dari label yang benar. Parameter diupdate. Itu satu langkah.'),
                        $this->paragraph('Setelah satu putaran penuh di data latih (epoch), ukur macro-F1 di validasi, bukan di latih. Ulangi. Berhenti menurut aturan, bukan menurut perasaan.'),
                        $this->paragraph('Overfitting kelihatan seperti ini: F1 latih 0,97, F1 validasi 0,71, dan kelas perangkat di validasi runtuh. Penyebab umum: duplikat, kebocoran, encoder terlalu bebas pada data kecil, atau epoch kebanyakan.'),
                        $this->diagram(
                            'Update hanya dari latih. Keputusan berhenti hanya dari validasi.',
                            [
                                ['id' => 'batch', 'label' => 'Batch latih', 'hint' => 'tiket + label'],
                                ['id' => 'loss', 'label' => 'Loss', 'hint' => 'jarak ke label'],
                                ['id' => 'upd', 'label' => 'Update', 'hint' => 'parameter'],
                                ['id' => 'val', 'label' => 'Validasi', 'hint' => 'macro-F1'],
                            ],
                            [
                                ['from' => 'batch', 'to' => 'loss'],
                                ['from' => 'loss', 'to' => 'upd'],
                                ['from' => 'upd', 'to' => 'val'],
                                ['from' => 'val', 'to' => 'batch', 'label' => 'epoch'],
                            ],
                            4,
                        ),
                        $this->paragraph('Ukuran batch dan laju belajar dipilih di validasi. Jangan menyentuh uji. Catat seed, versi tokenizer, hash data latih. Lesson berikutnya menuntut itu sebagai syarat, bukan hiasan.'),
                    ],
                ),
                $this->textLesson(
                    'Kelas timpang dan biaya salah routing',
                    'Bobot kelas, focal loss, dan mengapa mengoptimalkan akurasi adalah mengkhianati SLA perangkat.',
                    16,
                    [
                        $this->heading('Kelas timpang dan biaya salah routing', 1),
                        $this->paragraph('Distribusi hidup Biro Layanan mungkin: akses 40%, aplikasi 30%, permintaan 15%, jaringan 10%, perangkat 5%. Loss tanpa bobot akan hampir mengabaikan perangkat.'),
                        $this->paragraph('Salah routing perangkat ke aplikasi berarti hardware kritis mengantri di tim yang salah selama berjam-jam. Mengoptimalkan akurasi adalah mengkhianati SLA itu.'),
                        $this->paragraph('Teknik yang diuji di validasi, bukan ditumpuk semua: bobot kelas berbanding terbalik dengan frekuensi; biaya salah perangkat lebih mahal di matriks biaya; oversampling hanya di latih; ambang per kelas di Lesson evaluasi. Jangan memakai akurasi sebagai pengganti loss.'),
                        $this->paragraph('Tulis matriks biaya bersama kepala biro. “Perangkat → aplikasi” lebih mahal daripada “permintaan → akses”. Matriks itu yang memilih model di validasi. Tanpa itu, macro-F1 masih bisa menyembunyikan satu sel yang secara bisnis tidak bisa diterima.'),
                    ],
                ),
                $this->textLesson(
                    'Reproduksibilitas dan early stopping',
                    'Seed, hash data, checkpoint, dan berhenti saat validasi tidak membaik — supaya angka bisa ditagih ulang.',
                    14,
                    [
                        $this->heading('Reproduksibilitas dan early stopping', 1),
                        $this->paragraph('Angka yang tidak bisa diulang bukan hasil. Itu anekdot. Dua orang di Biro Layanan harus bisa mengulang F1 validasi dalam toleransi kecil. Kalau tidak, kamu belum siap serving.'),
                        $this->paragraph('Catat per percobaan: hash berkas latih/validasi/uji, versi tokenizer dan encoder, seed, pengaturan, epoch terbaik menurut macro-F1 validasi, dan confusion validasi. Checkpoint epoch terbaik itulah yang diukur sekali di uji.'),
                        $this->paragraph('Early stopping: berhenti jika validasi tidak membaik selama N epoch. N dipilih di validasi, lalu dikunci. Jangan berhenti berdasarkan uji. Jangan pilih epoch terbaik berdasarkan uji. Itu kebocoran yang kelihatan seperti ketelitian.'),
                        $this->paragraph('Model yang “hanya jalan di laptop satu orang” bukan model kasus. Itu demo rapuh.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionEval(): array
    {
        return [
            'title' => 'Mengevaluasi terhadap metrik kasus',
            'description' => 'Confusion matrix, macro-F1, ambang eskalasi, analisis galat, dan uji temporal — bukan akurasi panggung.',
            'lessons' => [
                $this->textLesson(
                    'Confusion matrix lima kelas tiket',
                    'Setiap sel adalah hop salah yang bisa diceritakan ke petugas. Baca matriks, jangan hanya F1.',
                    18,
                    [
                        $this->heading('Confusion matrix lima kelas tiket', 1),
                        $this->paragraph('Confusion matrix adalah tabel 5×5. Baris: kelas yang benar. Kolom: prediksi model. Diagonal: Sari tidak perlu memindahkan tiket. Di luar diagonal: hop yang akan terjadi di produksi jika model dipercaya buta.'),
                        $this->paragraph('Ceritakan sel mahal dalam bahasa antrian. “Aplikasi yang diprediksi permintaan” berarti bug masuk antrian pengadaan. “Perangkat yang diprediksi aplikasi” berarti monitor berkedip mengantri di tim software.'),
                        $this->paragraph('Dua tabrakan klasik di Biro Layanan: aplikasi vs permintaan (“mohon diperbaiki absensi” — bug atau request?), dan akses vs jaringan (“VPN tidak jalan”). Encoder hanya berhasil jika sel mahal mengecil tanpa mengorbankan diagonal perangkat.'),
                        $this->diagram(
                            'Prediksi salah = hop ke meja yang salah. Baca sel, bukan hanya warna.',
                            [
                                ['id' => 'true', 'label' => 'Kelas benar', 'hint' => 'seharusnya'],
                                ['id' => 'pred', 'label' => 'Prediksi', 'hint' => 'model'],
                                ['id' => 'ok', 'label' => 'Diagonal', 'hint' => 'hop 0'],
                                ['id' => 'hop', 'label' => 'Salah antrian', 'hint' => 'SLA pecah'],
                            ],
                            [
                                ['from' => 'true', 'to' => 'pred'],
                                ['from' => 'pred', 'to' => 'ok', 'label' => 'cocok'],
                                ['from' => 'pred', 'to' => 'hop', 'label' => 'salah'],
                            ],
                            4,
                        ),
                        $this->paragraph('Lampirkan jumlah per sel, bukan hanya heatmap. Kelas perangkat mungkin hanya 40 tiket uji. Satu sel bisa menipu mata.'),
                    ],
                ),
                $this->textLesson(
                    'Macro-F1, bukan akurasi global',
                    'F1 per kelas, rata-rata makro, dan mengapa mikro/akurasi memihak akses.',
                    16,
                    [
                        $this->heading('Macro-F1, bukan akurasi global', 1),
                        $this->paragraph('Precision kelas perangkat: dari yang diprediksi perangkat, berapa yang benar. Recall kelas perangkat: dari tiket yang memang perangkat, berapa yang ketemu. F1 adalah ringkasan keduanya. Kalau salah satu jelek, F1 jelek.'),
                        $this->paragraph('Macro-F1 merata-ratakan F1 lima kelas tanpa peduli volume. Itu mendekati keadilan antar-antrian. Akurasi didominasi akses dan aplikasi. Untuk kasus timpang, paket minimum: macro-F1 plus recall kelas mahal.'),
                        $this->paragraph('Laporkan F1 per kelas, bukan satu angka. Model dengan macro-F1 0,84 tetapi recall perangkat 0,45 gagal kasus, meski slide-nya bagus. Model dengan macro-F1 0,79 dan recall perangkat 0,83 mungkin lebih layak jika matriks biaya bilang begitu.'),
                        $this->paragraph('Dengan uji kecil, beda 0,02 macro-F1 bisa noise. Jangan naik tangga model demi 0,01. Kepala biro butuh “lulus / tidak lulus target”, bukan perlombaan desimal.'),
                    ],
                ),
                $this->textLesson(
                    'Ambang tidak yakin dan eskalasi manusia',
                    'Keyakinan kalibrasi, ambang per kelas, dan kuota tiket yang boleh ke manusia.',
                    18,
                    [
                        $this->heading('Ambang tidak yakin dan eskalasi manusia', 1),
                        $this->paragraph('Model mengeluarkan lima skor yang jumlahnya satu (softmax). Skor 0,70 tidak otomatis berarti “benar 70%”. Itu belum kalibrasi. Jangan memperlakukan skor sebagai janji.'),
                        $this->paragraph('Kebijakan Biro Layanan: jika skor juara di bawah ambang, atau selisih juara vs runner-up terlalu rapat, kirim ke Sari. Ambang dipilih di validasi supaya macro-F1 pada tiket yang di-auto-route lulus target, dan tiket ke manusia tidak melebihi kapasitas — contoh: paling 25%.'),
                        $this->paragraph('Ambang boleh per kelas. Perangkat dengan skor 0,55 mungkin harus ke Sari. Akses dengan 0,55 mungkin sudah aman. Kebijakan yang sama dipakai di uji dan di serving. Mengubah ambang setelah melihat uji adalah menyentuh uji.'),
                        $this->diagram(
                            'Skor rendah tidak dipaksa menjadi label. Sari tetap di loop.',
                            [
                                ['id' => 'in', 'label' => 'Tiket baru', 'hint' => 'judul+isi'],
                                ['id' => 'sc', 'label' => 'Skor 5 kelas', 'hint' => 'softmax'],
                                ['id' => 'auto', 'label' => 'Auto-route', 'hint' => 'di atas ambang'],
                                ['id' => 'man', 'label' => 'Manusia', 'hint' => 'di bawah ambang'],
                            ],
                            [
                                ['from' => 'in', 'to' => 'sc'],
                                ['from' => 'sc', 'to' => 'auto', 'label' => 'yakin'],
                                ['from' => 'sc', 'to' => 'man', 'label' => 'ragu'],
                            ],
                            4,
                        ),
                        $this->paragraph('Eskalasi bukan aib. Itu cara kasus tetap aman saat teks di luar distribusi: tiket campur, produk baru, bahasa baru. Lesson berikutnya memakai tumpukan itu sebagai sumber belajar, bukan tempat menyembunyikan kegagalan.'),
                    ],
                ),
                $this->textLesson(
                    'Analisis galat: tiket campur dan bahasa campur',
                    'Baca 100 galat. Kelompokkan. Jangan langsung naikkan epoch.',
                    16,
                    [
                        $this->heading('Analisis galat: tiket campur dan bahasa campur', 1),
                        $this->paragraph('Ambil 100 tiket validasi yang salah atau yang eskalasi. Baca. Kelompokkan dengan tangan. Jangan langsung menambah epoch.'),
                        $this->numbered([
                            'Ontologi memang ambigu — perbaiki panduan, relabel.',
                            'Tiket campur dua masalah — kebijakan “penghalang utama” atau eskalasi wajib.',
                            'Slang atau typo ekstrem — biasanya encoder tahan; cek dulu.',
                            'Nama produk baru — kamus normalisasi, bukan arsitektur baru.',
                            'Kebocoran yang tersisa — buang fitur, ulangi split.',
                            'Label historis salah — bersihkan emas, jangan hukum model.',
                        ]),
                        $this->paragraph('Bahasa campur (“wifi down dari kemarin, SSO 401”) biasanya encoder sudah tahan. Yang merusak adalah campur tugas: “minta laptop baru karena absensi error” — perangkat atau aplikasi? Panduan harus memilih. Model tidak boleh “kreatif”.'),
                        $this->paragraph('Jika 60% galat adalah label historis salah, naik tangga ke encoder adalah hiburan. Relabel dulu.'),
                    ],
                ),
                $this->textLesson(
                    'Uji temporal di data yang lebih baru',
                    'Satu kali di uji terkunci. Lalu, setelah waktu berjalan, uji lagi di bulan yang tidak ikut memilih model.',
                    15,
                    [
                        $this->heading('Uji temporal di data yang lebih baru', 1),
                        $this->paragraph('Setelah model dan ambang dikunci dari validasi, ukur sekali di uji — misalnya Sep–Okt. Simpan angka itu. Serving boleh dimulai hanya jika target lulus, atau kepala biro menerima pengecualian tertulis (recall perangkat 0,77, dengan mode bayangan).'),
                        $this->paragraph('Dua bulan kemudian, tiket November–Desember adalah uji baru. Jangan mengutak-atik model untuk mengalahkan uji baru itu sebelum kamu mencatat angka beku. Itu pengukuran drift.'),
                        $this->paragraph('Jika macro-F1 jatuh karena rilis SSO baru, itu sinyal putaran ulang: data baru, mungkin label baru. Bukan sinyal “tambah epoch di data lama”.'),
                        $this->paragraph('Laporkan tiga kolom selalu: validasi (untuk pilih), uji terkunci (untuk go/no-go), uji kemudian (untuk hidup). Mencampur tiga kolom itu adalah cara organisasi berbohong pada dirinya sendiri dengan itikad baik.'),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function sectionServe(): array
    {
        return [
            'title' => 'Melayani dan mengiterasi',
            'description' => 'Kontrak API, mode bayangan, umpan balik petugas, privasi, dan putaran ulang — model yang hidup di antrian, bukan di slide.',
            'lessons' => [
                $this->textLesson(
                    'Kontrak serving: dari teks ke label',
                    'Input, output, timeout, idempotensi, dan apa yang tidak boleh dilakukan layanan.',
                    18,
                    [
                        $this->heading('Kontrak serving: dari teks ke label', 1),
                        $this->paragraph('Layanan v1 menerima judul, isi, id tiket (untuk log, bukan untuk fitur), dan id permintaan. Ia mengembalikan label atau “tidak_yakin”, lima skor, versi model, dan waktu inferensi.'),
                        $this->paragraph('Timeout di bawah anggaran latency. Contoh Biro Layanan: p95 200 ms, timeout 300 ms. Jika timeout, eskalasi ke Sari — bukan menebak.'),
                        $this->paragraph('Jangan memblokir pembuatan tiket karena model mati. Antrian lama adalah fallback. Praproses di layanan harus sama dengan latih. Mengganti regex diam-diam adalah merilis model baru tanpa nama.'),
                        $this->diagram(
                            'Tiket masuk. Prediksi atau fallback manusia. Antrian tidak menunggu model yang mati.',
                            [
                                ['id' => 'api', 'label' => 'API tiket', 'hint' => 'judul+isi'],
                                ['id' => 'mdl', 'label' => 'Model vN', 'hint' => '≤200 ms'],
                                ['id' => 'q', 'label' => 'Antrian', 'hint' => 'label'],
                                ['id' => 'fb', 'label' => 'Fallback', 'hint' => 'timeout'],
                            ],
                            [
                                ['from' => 'api', 'to' => 'mdl'],
                                ['from' => 'mdl', 'to' => 'q', 'label' => 'yakin'],
                                ['from' => 'mdl', 'to' => 'fb', 'label' => 'ragu'],
                                ['from' => 'fb', 'to' => 'q'],
                            ],
                            4,
                        ),
                        $this->paragraph('Log: id tiket, versi model, skor, label, apakah Sari meng-override. Isi lengkap mengikuti kebijakan privasi di Lesson berikutnya. Tanpa log override, kamu tidak punya data putaran ulang.'),
                    ],
                ),
                $this->textLesson(
                    'Mode bayangan dan peluncuran bertahap',
                    'Prediksi paralel dulu. Lalu persentase kecil. Baru default. Jangan cutover big-bang.',
                    16,
                    [
                        $this->heading('Mode bayangan dan peluncuran bertahap', 1),
                        $this->paragraph('Mode bayangan: routing lama tetap berkuasa. Model memprediksi di paralel. Hasilnya dicatat. Antrian mahasiswa tidak berubah. Bandingkan prediksi vs antrian akhir Sari selama dua minggu.'),
                        $this->paragraph('Hitung macro-F1 hidup, persentase ragu, latency, dan sel mahal. Jika angka bayangan jauh di bawah uji terkunci, ada kebocoran praproses atau drift. Jangan naik persentase.'),
                        $this->paragraph('Jika bayangan lulus, peluncuran bertahap: 10% tiket di atas ambang di-auto-route, sisanya Sari. Naikkan hanya pada jadwal. Kepala biro harus bisa menarik tuas dalam satu konfigurasi. “Besok semua ke model” mengubah galat 8% menjadi insiden kampus.'),
                        $this->paragraph('Tuas henti: p95 latency tembus, override Sari meledak, atau recall satu kelas jatuh — kembali ke routing lama. Tuas itu milik operasional, di luar academy. Yang kamu pahami di sini: tuas harus ada sebelum serving, bukan setelah keluhan.'),
                    ],
                ),
                $this->textLesson(
                    'Umpan balik petugas dan putaran ulang',
                    'Override adalah emas. Jadwal relabel dan latih ulang, bukan “model sekali seumur hidup”.',
                    18,
                    [
                        $this->heading('Umpan balik petugas dan putaran ulang', 1),
                        $this->paragraph('Setiap kali Sari memindahkan tiket dari antrian prediksi ke antrian lain, itu pasangan (teks, label baru) — setelah dicek bahwa pindahnya memang koreksi kelas, bukan tiket campur yang berubah di tengah jalan.'),
                        $this->paragraph('Jangan latih ulang setiap malam. Itu merusak reproduksibilitas. Jadwalkan: setiap bulan, atau setelah N override di kelas mahal, atau setelah rilis produk besar.'),
                        $this->paragraph('Putaran ulang memakai split temporal baru. Uji lama menjadi arsip. Data terbaru menjadi uji. Latih memasukkan data yang sebelumnya uji hanya setelah dikunci sebagai sejarah. Jangan diam-diam menambah kelas keenam di serving tanpa menulis ontologi baru.'),
                        $this->diagram(
                            'Hidup menghasilkan override. Override menjadi data. Data menjadi model berikutnya, setelah bayangan lagi.',
                            [
                                ['id' => 'hidup', 'label' => 'Serving vN', 'hint' => 'prediksi'],
                                ['id' => 'ov', 'label' => 'Override', 'hint' => 'petugas'],
                                ['id' => 'data', 'label' => 'Data baru', 'hint' => 'split baru'],
                                ['id' => 'vnext', 'label' => 'Model vN+1', 'hint' => 'bayangan'],
                            ],
                            [
                                ['from' => 'hidup', 'to' => 'ov'],
                                ['from' => 'ov', 'to' => 'data'],
                                ['from' => 'data', 'to' => 'vnext'],
                                ['from' => 'vnext', 'to' => 'hidup', 'label' => 'ulang'],
                            ],
                            4,
                        ),
                        $this->paragraph('Dua gagal simetris: tidak pernah latih ulang (drift), atau latih ulang chaos tanpa uji terkunci (regresi). Keduanya berarti kasus tidak dipegang.'),
                    ],
                ),
                $this->textLesson(
                    'Privasi tiket, bias, dan audit',
                    'PII, hak akses log, bias unit/gedung, dan jejak versi yang bisa diaudit.',
                    16,
                    [
                        $this->heading('Privasi tiket, bias, dan audit', 1),
                        $this->paragraph('Tiket berisi nama, NIP, keluhan kesehatan, konflik atasan, screenshot layar email. v1 tidak butuh identitas untuk lima kelas. Ganti identitas dengan placeholder di praproses.'),
                        $this->paragraph('Log produksi: id tiket plus prediksi. Isi lengkap hanya di sistem tiket yang sudah punya hak akses, bukan di penyimpanan model. Jangan mengirim teks tiket ke vendor luar tanpa dasar dan tanpa filter. Kursus ini tidak men-deploy apa pun, tetapi kontrak privasi harus tertulis sebelum serving di kampusmu.'),
                        $this->paragraph('Bias: model bisa lebih sering salah pada tiket dari unit tertentu, atau pada tiket satu kalimat dari pengguna non-teknis. Ukur F1 per kanal (portal vs email) dan per panjang teks di validasi. Tiket sangat pendek mungkin wajib ke Sari — bukan alasan menambah model generatif.'),
                        $this->paragraph('Audit: setiap prediksi yang mengubah antrian menunjuk versi model. Setiap versi menunjuk hash data dan panduan label. Ketika ada keluhan “mengapa tiket ini ke perangkat?”, kamu menjawab dengan versi, skor, dan kebijakan ambang — bukan “AI-nya begitu”.'),
                    ],
                ),
                $this->textLesson(
                    'Ringkasan: dari kasus ke model yang hidup',
                    'Enam bagian, satu kasus, satu tes: apakah keputusan routing tiket lebih baik, lebih aman, dan bisa diulang.',
                    16,
                    [
                        $this->heading('Ringkasan: dari kasus ke model yang hidup', 1),
                        $this->paragraph('Kamu sudah menelusuri satu kasus sampai ujung: Biro Layanan, lima antrian, petugas Sari.'),
                        $this->bullets([
                            'Merangka: keputusan routing, metrik SLA dan macro-F1, kapan tidak perlu model.',
                            'Data: sumber yang sah, ontologi, split temporal, kebocoran.',
                            'Model: aturan dan linear dulu, encoder jika perlu, bukan generasi.',
                            'Melatih: representasi konsisten, kepala klasifikasi, loop loss, kelas timpang, seed dan early stopping.',
                            'Evaluasi: confusion sebagai hop, macro-F1, ambang manusia, galat yang dikelompokkan, uji terkunci lalu uji kemudian.',
                            'Serving: kontrak API, bayangan, override, privasi, putaran ulang.',
                        ]),
                        $this->heading('Tes kedewasaan'),
                        $this->paragraph('Bukan “apakah kita punya encoder”. Melainkan: ada pemilik metrik? Uji temporal lulus target tertulis? Manusia punya jalur ragu? Versi bisa diaudit? Lesson ini tidak pernah menjadi lab? Jika salah satu tidak ada, model belum hidup. Ia masih slide.'),
                        $this->heading('Yang tidak kamu bawa pulang'),
                        $this->bullets([
                            'Akses Restricted Course atau runtime agen. Kursus ini tidak memberikannya.',
                            'Notebook di dalam Lesson. Pelatihan nyata terjadi di pipa organisasimu, di luar academy.',
                            'Resep universal untuk semua kasus. Deteksi dokumen dan generasi punya metrik lain. Disiplinnya sama: kasus dulu.',
                        ]),
                        $this->paragraph('Kerjakan kuis wajib. Jawaban ada di Lesson, bukan di tren. Kalau suatu soal terasa seperti “pilih arsitektur terbaru”, kembali ke tiket Sari.'),
                    ],
                ),
            ],
        ];
    }
}
