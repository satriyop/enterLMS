<?php

namespace Database\Seeders\Concerns;

/**
 * Syllabus for Membangun Model AI untuk Kasus Tertentu.
 *
 * One running case throughout: klasifikasi tiket layanan (lima kelas).
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
                        $this->paragraph('Kursus ini untuk kamu yang harus membangun, meninjau, atau menolak sebuah model AI untuk satu kasus nyata di organisasi Indonesia: staf data, pengembang, arsitek, atau pimpinan yang harus bertanya dengan tepat. Ini Open Course, gratis, dalam Bahasa Indonesia. Kamu boleh enroll sendiri.'),
                        $this->paragraph('Ini bukan pengantar “apa itu AI”. Pengenalan Agen AI, AI untuk Kampus, Korporat, dan Militer mengajarkan adopsi dan apa itu agen. Kursus ini masuk ke dalam loop membangun model: merangka kasus, data, pilihan model, pelatihan, evaluasi, serving, dan iterasi. Benang merahnya satu: klasifikasi tiket layanan.'),
                        $this->paragraph('Lesson adalah teks dan diagram. Tutor bisa menjelaskan Lesson. Lesson bukan notebook, bukan GPU, dan bukan konsol runtime. Menyelesaikan kursus ini tidak membuka Course terbatas Administrasi Agen OpenClaw.'),
                        $this->heading('Yang akan kamu kuasai'),
                        $this->bullets([
                            'Menulis keputusan yang akan diambil model, lengkap dengan biaya salah.',
                            'Menyusun data, label, split temporal, dan daftar kebocoran.',
                            'Memilih baseline sebelum encoder, dan menolak model generatif jika tugasnya klasifikasi.',
                            'Mengevaluasi dengan metrik kasus (macro-F1, recall kelas mahal, ambang “tidak yakin”).',
                            'Merancang serving, mode bayangan, umpan balik petugas, privasi, dan putaran ulang.',
                        ]),
                        $this->diagram(
                            'Pipeline yang akan kamu pelajari: dari kasus ke iterasi, selalu untuk tiket layanan.',
                            [
                                ['id' => 'kasus', 'label' => 'Kasus + metrik', 'hint' => 'keputusan routing'],
                                ['id' => 'data', 'label' => 'Data', 'hint' => 'label, split, bocor'],
                                ['id' => 'model', 'label' => 'Model', 'hint' => 'baseline dulu'],
                                ['id' => 'latih', 'label' => 'Latih', 'hint' => 'loss, imbalance'],
                                ['id' => 'eval', 'label' => 'Evaluasi', 'hint' => 'macro-F1'],
                                ['id' => 'serve', 'label' => 'Serving', 'hint' => 'iterasi'],
                            ],
                            [],
                            3,
                        ),
                        $this->quote('Kalau kamu mencari tombol “latih di Lesson ini”, kamu di Course yang salah. Yang kamu bangun di sini adalah penilaian: kapan model layak, dan kapan ia masih tebakan mahal.'),
                    ],
                    preview: true,
                ),
                $this->textLesson(
                    'Kasus yang kita bangun: klasifikasi tiket layanan',
                    'Satu organisasi, lima kelas tiket, satu keputusan: ke antrian mana tiket ini pergi.',
                    18,
                    [
                        $this->heading('Kasus yang kita bangun', 1),
                        $this->paragraph('Bayangkan meja layanan sebuah organisasi Indonesia — kampus, perusahaan, atau instansi. Setiap hari masuk ratusan tiket: “tidak bisa login SSO”, “Wi-Fi gedung B putus”, “aplikasi absensi error 500”, “monitor berkedip”, “mohon akses folder bersama”. Manusia di meja layanan membaca, lalu mengirim tiket ke antrian yang benar. Salah antrian memperpanjang SLA. Antrian yang benar tapi terlambat juga gagal. Itu kasusnya.'),
                        $this->paragraph('Keputusan yang akan diambil model sangat sempit: diberikan teks tiket (judul + isi, kadang lampiran diabaikan dulu), keluarkan satu label dari lima kelas, atau katakan “tidak yakin”. Lima kelas yang kita pakai sepanjang kursus: akses, jaringan, aplikasi, perangkat, dan permintaan. Bukan sentimen. Bukan meringkas. Bukan chatbot yang membalas pengguna.'),
                        $this->heading('Mengapa kasus ini bagus untuk belajar'),
                        $this->bullets([
                            'Teksnya kotor: campur Bahasa Indonesia, Inggris, slang, typo, dan kode error.',
                            'Kelasnya timpang: akses dan aplikasi sering; perangkat jarang tapi mahal jika salah.',
                            'Ada manusia di loop: petugas layanan bisa mengoreksi, dan koreksi itu data putaran berikutnya.',
                            'Metrik bisnisnya jernih: persentase tiket yang sampai antrian benar dalam SLA, plus beban eskalasi.',
                        ]),
                        $this->diagram(
                            'Keputusan model pada kasus ini: teks tiket masuk, label atau “tidak yakin” keluar.',
                            [
                                ['id' => 'tiket', 'label' => 'Teks tiket', 'hint' => 'judul + isi'],
                                ['id' => 'model', 'label' => 'Model', 'hint' => 'lima kelas'],
                                ['id' => 'label', 'label' => 'Label', 'hint' => 'akses…permintaan'],
                                ['id' => 'ragu', 'label' => 'Tidak yakin', 'hint' => 'ke manusia'],
                            ],
                            [
                                ['from' => 'tiket', 'to' => 'model'],
                                ['from' => 'model', 'to' => 'label', 'label' => 'yakin'],
                                ['from' => 'model', 'to' => 'ragu', 'label' => 'ragu'],
                            ],
                            4,
                        ),
                        $this->paragraph('Kita tidak akan ganti kasus di tengah kursus. Kalau suatu teknik tidak masuk akal untuk tiket layanan, kita buang. Itu disiplin “kasus tertentu”: model ada untuk keputusan ini, bukan sebaliknya.'),
                        $this->quote('Kasus yang ditulis dengan malas (“AI untuk helpdesk”) akan menghasilkan model yang tidak bisa dievaluasi. Kasus yang ditulis sebagai keputusan + kelas + biaya salah bisa dibangun.'),
                    ],
                ),
                $this->textLesson(
                    'Masalah bisnis bukan masalah model',
                    'Jangan mulai dari arsitektur. Mulai dari keputusan, pemilik, dan apa yang terjadi jika salah.',
                    16,
                    [
                        $this->heading('Masalah bisnis bukan masalah model', 1),
                        $this->paragraph('Kalimat “kita butuh model klasifikasi teks” bukan rumusan kasus. Rumusan kasus menjawab: siapa yang memutuskan hari ini, apa yang ia putuskan, berapa lama, berapa sering ia salah, dan apa biayanya. Untuk tiket layanan: petugas membaca antrian masuk, memilih satu dari lima antrian teknis, dan SLA dihitung dari waktu tiket dibuat sampai tiket disentuh antrian yang benar.'),
                        $this->paragraph('Kalau petugas sudah benar 92% dengan aturan kata kunci dan sisanya dikirim ke antrian umum, model harus mengalahkan itu pada kelas yang mahal — bukan hanya rata-rata. Kalau volume hanya dua puluh tiket sehari, model mungkin tidak layak: biaya anotasi dan pemeliharaan lebih mahal daripada satu manusia yang sudah hafal pola.'),
                        $this->heading('Tiga pertanyaan yang harus tertulis sebelum data'),
                        $this->numbered([
                            'Keputusan apa yang diambil, dengan input apa, pada saat apa? (Teks tiket, saat tiket dibuat, sebelum manusia membaca.)',
                            'Apa yang terjadi jika salah? (Akses dikirim ke perangkat: SLA terbakar. Permintaan dikirim ke jaringan: teknisi kesal, pengguna menunggu.)',
                            'Siapa pemilik metrik setelah model jalan? (Bukan “tim AI”. Pemilik antrian layanan.)',
                        ]),
                        $this->paragraph('Baru setelah tiga jawaban itu ada, kamu boleh bicara TF-IDF atau encoder. Orang yang memulai dari “IndoBERT vs model generatif” tanpa pemilik metrik sedang belanja arsitektur, bukan membangun untuk kasus.'),
                        $this->table(
                            ['Salah rumusan', 'Rumusan yang bisa dibangun'],
                            [
                                ['“AI untuk helpdesk”', 'Klasifikasi teks tiket ke 5 antrian, atau eskalasi, dalam 2 detik'],
                                ['“Akurasi tinggi”', 'Macro-F1 ≥ baseline aturan, recall perangkat ≥ 0,80'],
                                ['“Otomatisasi penuh”', 'Model boleh menolak; manusia tetap pemilik tiket ragu'],
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
                        $this->paragraph('Akurasi global adalah jebakan pada tiket layanan. Jika 55% tiket adalah “akses”, model yang selalu menjawab “akses” sudah berakurasi 55% dan gagal total sebagai router. Pimpinan yang hanya mendengar “akurasi 91%” tidak tahu bahwa kelas perangkat — jarang, mahal, sering berisi hardware kritis — recall-nya 0,40.'),
                        $this->paragraph('Metrik kasus harus mencerminkan biaya salah. Untuk lima kelas kita, paket minimum yang jujur: macro-F1 (rata-rata F1 per kelas, tidak tertarik volume), recall kelas perangkat dan jaringan (mahal jika terlewat), plus persentase tiket yang dikirim ke manusia karena di bawah ambang. Yang terakhir bukan kegagalan model; itu desain.'),
                        $this->heading('Metrik bisnis vs metrik model'),
                        $this->bullets([
                            'Metrik model: macro-F1, recall per kelas, kalibrasi keyakinan, latency p95.',
                            'Metrik bisnis: % tiket di antrian benar dalam SLA, rata-rata hop antar-antrian, beban petugas untuk tiket “tidak yakin”.',
                            'Jembatannya: setiap galat model harus bisa diceritakan sebagai hop ekstra atau SLA pecah.',
                        ]),
                        $this->diagram(
                            'Metrik yang selaras: model diukur per kelas; bisnis diukur di antrian dan SLA.',
                            [
                                ['id' => 'pred', 'label' => 'Prediksi', 'hint' => 'label + skor'],
                                ['id' => 'f1', 'label' => 'Macro-F1', 'hint' => 'lima kelas'],
                                ['id' => 'rec', 'label' => 'Recall mahal', 'hint' => 'perangkat, jaringan'],
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
                        $this->paragraph('Tulis target sebelum melihat angka uji. Contoh yang layak untuk kasus ini: “mengalahkan baseline aturan pada macro-F1; recall perangkat minimal 0,80; maksimal 25% tiket ke manusia; p95 latency 200 ms.” Tanpa target tertulis, setiap angka bisa diklaim sukses.'),
                        $this->quote('Metrik yang tidak bisa ditolak adalah metrik yang tidak berguna. Kalau tidak ada ambang “kita belum siap serving”, kamu belum punya kasus. Kamu punya demo.'),
                    ],
                ),
                $this->textLesson(
                    'Kapan kasus ini tidak butuh model',
                    'Aturan, antrian manusia, dan “belum cukup data” adalah jawaban yang sah — dan sering lebih murah.',
                    14,
                    [
                        $this->heading('Kapan kasus ini tidak butuh model', 1),
                        $this->paragraph('Membangun model adalah keputusan mahal: anotasi, pemeliharaan, drift, audit. Beberapa pola tiket layanan selesai tanpa model. “Reset password” dengan frasa tetap? Aturan. Tiket yang selalu berisi kode gedung di judul dan peta ruangan ke antrian jaringan? Tabel lookup. Volume 15 tiket/hari dengan 3 petugas yang sudah hafal? Antrian manusia.'),
                        $this->heading('Daftar penolakan yang sehat'),
                        $this->bullets([
                            'Kelas belum disepakati: lima orang, lima ontologi. Model akan belajar kekacauan.',
                            'Label historis adalah nama petugas, bukan jenis masalah. Itu bukan target.',
                            'Tiket berisi data yang tidak akan tersedia saat prediksi (catatan resolusi di akhir).',
                            'Pimpinan ingin “chatbot yang menyelesaikan tiket”, padahal keputusan yang bisa diotomasi hanya routing.',
                            'Tidak ada pemilik yang akan membaca confusion matrix setiap bulan.',
                        ]),
                        $this->paragraph('Kursus ini tetap memakai kasus tiket karena kasus itu lolos daftar di atas: ontologi lima kelas bisa ditulis, volume cukup, keputusan sempit, manusia bisa mengoreksi. Kamu harus bisa menjelaskan mengapa kasusmu lolos, bukan mengapa modelmu keren.'),
                        $this->paragraph('Jika setelah Lesson ini kamu memutuskan organisasimu belum siap model, itu kelulusan, bukan gagal. Model yang tidak dibangun karena kasusnya belum jernih lebih dewasa daripada model yang diserving untuk slide.'),
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
                        $this->paragraph('Tiket layanan jarang lahir di satu tempat. Portal internal punya judul, isi, kategori yang dipilih pengguna (sering salah), lampiran, dan identitas. Email punya subject, thread, signature, dan auto-reply. Chat punya potongan pendek dan slang. Log sistem kadang menempel kode error. Model klasifikasi teks untuk kasus kita, pada v1, hanya boleh melihat apa yang akan tersedia saat tiket baru dibuat: judul dan isi. Bukan catatan resolusi. Bukan nama petugas yang menutup tiket. Bukan antrian terakhir setelah tiga hop.'),
                        $this->paragraph('Kategori yang diklik pengguna adalah sinyal lemah, bukan label emas. Pengguna sering memilih “umum” atau “aplikasi” karena itu tombol pertama. Kalau kamu memakai klik pengguna sebagai target, model belajar kebiasaan UI, bukan masalahnya.'),
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
                        $this->paragraph('Tulis kontrak input seolah-olah itu API: “string judul, string isi, opsional sumber kanal.” Segala sesuatu di luar kontrak adalah godaan overfitting. Lesson berikutnya menyusun label. Lesson kebocoran akan kembali ke tabel ini dengan contoh yang merusak metrik.'),
                    ],
                ),
                $this->textLesson(
                    'Ontologi label dan panduan anotasi',
                    'Lima kelas harus bisa dibedakan manusia. Jika anotator tidak setuju, model tidak akan ajaib.',
                    18,
                    [
                        $this->heading('Ontologi label dan panduan anotasi', 1),
                        $this->paragraph('Lima kelas kita: akses (identitas, login, hak, SSO, VPN akun), jaringan (koneksi, Wi-Fi, DNS, VPN saluran, putus gedung), aplikasi (bug, error 5xx, fitur tidak jalan), perangkat (laptop, monitor, printer, periferal), permintaan (akses folder baru, lisensi, perubahan yang bukan insiden). Setiap kelas butuh contoh kanonik, contoh batas, dan contoh yang dilarang.'),
                        $this->paragraph('Batas yang sering bertikai: “tidak bisa VPN” — akun dinonaktifkan (akses) atau terowongan putus (jaringan)? “Absensi error” — bug aplikasi atau perangkat yang tidak baca wajah? Panduan harus memutuskan, bukan membiarkan “terserah anotator.” Tanpa keputusan itu, kesepakatan antar-anotator (agreement) jatuh, dan macro-F1 di uji tidak berarti apa-apa.'),
                        $this->heading('Isi panduan yang wajib ada'),
                        $this->numbered([
                            'Definisi satu kalimat per kelas, plus 5 contoh kanonik.',
                            '5 contoh batas dengan keputusan tertulis dan alasan.',
                            'Aturan tiket campur: pilih kelas yang menghalangi pengguna sekarang, atau tandai “campur” untuk eskalasi — jangan berharap model membelah tiket.',
                            'Bahasa: label tetap Bahasa Indonesia; teks sumber boleh campur.',
                            'Cara menangani tiket kosong, tiket satu kata, tiket yang hanya screenshot (v1: “tidak yakin”).',
                        ]),
                        $this->paragraph('Ukur agreement di sampel 200 tiket yang dilabel dua orang. Jika mereka tidak mencapai kesepakatan yang memadai pada kelas perangkat, jangan latih. Perbaiki panduan. Model tidak menyembuhkan ontologi yang pecah; ia menghafalnya.'),
                        $this->quote('Label adalah spesifikasi tersembunyi. Panduan yang malas menghasilkan model yang kelihatan pintar di data latih dan bodoh di meja layanan.'),
                    ],
                ),
                $this->textLesson(
                    'Mengumpulkan, menyeimbangkan, dan membersihkan',
                    'Volume, kelas minoritas, duplikat, template, dan PII — pekerjaan data sebelum split.',
                    16,
                    [
                        $this->heading('Mengumpulkan, menyeimbangkan, dan membersihkan', 1),
                        $this->paragraph('Kumpulkan tiket historis yang sudah sampai antrian benar — bukan tiket yang masih melayang. Target emas adalah antrian akhir yang disepakati petugas, setelah hop yang salah dibuang, asalkan antrian itu memetakan 1:1 ke lima kelas. Jika historis memakai 30 kategori lama, kamu harus memetakan dulu, lalu membuang yang tidak masuk lima kelas, lalu menghitung sisa per kelas.'),
                        $this->paragraph('Kelas perangkat akan miskin. Jangan “menyeimbangkan” dengan menduplikasi tiket yang sama 20 kali ke data uji. Oversampling, bobot kelas, dan augmentasi (parafrase hati-hati) hanya di data latih. Data uji harus mengikuti distribusi hidup, termasuk ketimpangan, karena SLA hidup juga timpang.'),
                        $this->heading('Pembersihan yang tidak merusak sinyal'),
                        $this->bullets([
                            'Buang duplikat exact dan near-duplicate di latih (pengguna yang mengirim ulang).',
                            'Hapus signature email dan disclaimer hukum; itu bukan masalahnya.',
                            'Ganti identitas (NIP, email, nomor HP) dengan placeholder; jangan hapus seluruh kalimat jika sisa teks masih bermakna.',
                            'Jangan menormalisasi sampai hilang: “gak bisa login” vs “tidak dapat autentikasi” adalah variasi produksi.',
                            'Simpan kode error (ERR_CONNECTION_RESET, 401, 500); itu fitur, bukan sampah.',
                        ]),
                        $this->paragraph('Catat berapa tiket yang dibuang dan mengapa. Jika 40% historis dibuang karena tidak terpetakan, kasusnya belum siap — ontologi operasional belum sama dengan ontologi model. Kembali ke Lesson panduan, bukan memaksa latih.'),
                    ],
                ),
                $this->textLesson(
                    'Split temporal: latih, validasi, uji',
                    'Tiket adalah deret waktu. Acak 80/20 membocorkan masa depan ke masa lalu.',
                    18,
                    [
                        $this->heading('Split temporal: latih, validasi, uji', 1),
                        $this->paragraph('Tiket layanan punya musim: awal semester, rilis aplikasi absensi, pindah SSO, pengadaan laptop. Jika kamu mengacak semua tiket lalu 80% latih dan 20% uji, model melihat pola rilis yang sebenarnya baru muncul “minggu depan” di dunia nyata. Angka uji menjadi optimis. Serving di bulan berikutnya jatuh. Itu bukan nasib. Itu split yang salah.'),
                        $this->paragraph('Split yang benar untuk kasus ini: urutkan menurut waktu dibuat. Latih di periode paling lama, validasi di jendela berikutnya, uji di jendela paling baru yang belum kamu sentuh saat memilih model. Jangan pernah menyesuaikan ambang atau arsitektur berdasarkan uji. Uji adalah pakaian sekali pakai sampai kamu siap serving, lalu ia menjadi arsip.'),
                        $this->diagram(
                            'Split temporal untuk tiket: masa lalu untuk latih, tengah untuk validasi, yang terbaru untuk uji.',
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
                            'Thread yang sama (tiket turunan) tidak boleh terbelah ke latih dan uji.',
                            'Validasi boleh dipakai berulang untuk pilih model dan ambang; uji tidak.',
                            'Setelah serving, data baru tidak dicampur ke latih sampai putaran ulang yang terencana.',
                        ]),
                        $this->paragraph('Kalau datanya sedikit, orang tergoda memakai validasi silang acak. Untuk teks tiket, itu hampir selalu salah. Lebih jujur: latih kecil, uji temporal kecil, dan akui ketidakpastian, daripada angka bagus dari kebocoran waktu.'),
                    ],
                ),
                $this->textLesson(
                    'Kebocoran data yang merusak metrik',
                    'Nomor tiket, nama petugas, template, dan target yang menyelinap ke fitur.',
                    18,
                    [
                        $this->heading('Kebocoran data yang merusak metrik', 1),
                        $this->paragraph('Kebocoran adalah informasi yang tidak akan kamu miliki saat prediksi hidup, tetapi ada di data latih/uji sehingga metrik tampak bagus. Pada tiket layanan, kebocoran itu biasa dan licin. Nomor tiket sering mengandung kode antrian lama (“NET-2024-…”). Nama petugas di CC hanya muncul setelah routing manusia. Template auto-reply “tiket perangkat Anda sedang…” ditulis sistem setelah kelas dipilih. Jika model melihat itu, ia mencontek label, bukan membaca keluhan pengguna.'),
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
                            'Kebocoran: sinyal yang hanya ada setelah keputusan, tetapi ikut masuk ke model.',
                            [
                                ['id' => 'user', 'label' => 'Teks pengguna', 'hint' => 'boleh'],
                                ['id' => 'route', 'label' => 'Keputusan routing', 'hint' => 'target'],
                                ['id' => 'tmpl', 'label' => 'Template sistem', 'hint' => 'bocor'],
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
                        $this->paragraph('Uji kebocoran secara kasar: latih model linear hanya pada “fitur yang mencurigakan” (ID, nama, template). Jika F1-nya tinggi, kamu belum punya model bahasa. Kamu punya kebocoran yang terukur. Buang fitur itu, ulangi split, baru lanjut ke Lesson model.'),
                        $this->quote('Metrik yang indah dari data bocor adalah kebohongan yang rapi. Serving akan menagihnya dalam seminggu.'),
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
                        $this->paragraph('Sebelum encoder, tulis aturan. Daftar 30 frasa yang hampir pasti: “reset password”, “lupa OTP”, “tidak bisa SSO” → akses; “Wi-Fi gedung”, “DNS”, “ping timeout” → jaringan; dan seterusnya. Aturan akan salah di tiket campur dan slang, tetapi ia murah, bisa diaudit, dan sering sudah menutup 40–60% volume. Itu lantai. Model yang kalah dari aturan di kelas yang sama tidak layak serving.'),
                        $this->paragraph('Baseline statistik pertama: TF-IDF pada judul+isi (unigram+bigram, min_df kecil, n-gram Bahasa Indonesia tanpa stemmer agresif) plus regresi logistik multinomial dengan bobot kelas. Latih hanya di split latih, pilih C dan n-gram di validasi, laporkan macro-F1 di uji sekali. Ini cepat. Ini menandai kebocoran (kalau F1-nya ajaib, cek ID tiket). Ini memberi koefisien yang bisa dibaca: kata mana yang mendorong “perangkat”.'),
                        $this->diagram(
                            'Lantai yang wajib ada: aturan dulu, lalu model linear, baru naik tangga.',
                            [
                                ['id' => 'aturan', 'label' => 'Aturan frasa', 'hint' => 'auditabel'],
                                ['id' => 'tfidf', 'label' => 'TF-IDF + logreg', 'hint' => 'baseline'],
                                ['id' => 'enc', 'label' => 'Encoder', 'hint' => 'jika perlu'],
                                ['id' => 'gen', 'label' => 'Generatif', 'hint' => 'jarang untuk 5 kelas'],
                            ],
                            [],
                            4,
                        ),
                        $this->paragraph('Simpan prediksi baseline. Confusion matrix-nya adalah peta: aplikasi vs permintaan akan bertabrakan; perangkat akan under-recall. Encoder hanya dibenarkan jika ia memperbaiki sel yang mahal, bukan jika ia menaikkan akurasi global 1 poin dengan merusak kelas perangkat.'),
                        $this->quote('Orang yang melewatkan baseline tidak sedang bergerak cepat. Ia sedang kehilangan satu-satunya pembanding yang tidak bisa ia bohongi.'),
                    ],
                ),
                $this->textLesson(
                    'Tangga model: dari TF-IDF ke encoder',
                    'Naik satu anak tangga hanya jika anak sebelumnya gagal di metrik kasus.',
                    16,
                    [
                        $this->heading('Tangga model: dari TF-IDF ke encoder', 1),
                        $this->paragraph('Anak tangga setelah logreg: linear SVM pada vektor yang sama, atau fastText, jika kamu butuh margin yang lebih keras di kelas minoritas. Lalu encoder multilingual kecil atau encoder yang mengenal Bahasa Indonesia. Encoder membantu tiket pendek, slang, dan parafrase (“gak bisa masuk” ≈ “autentikasi gagal”) yang TF-IDF lewatkan. Ia tidak otomatis lebih baik di tiket yang sudah berisi kata “printer”.'),
                        $this->paragraph('Jangan meloncat ke model generatif berukuran besar untuk lima kelas. Klasifikasi adalah pemetaan ke himpunan tertutup. Model generatif bisa mengeluarkan label yang tidak ada, lebih mahal per tiket, lebih lambat, dan lebih sulit diaudit. Ia masuk akal jika tugas berubah jadi menyusun balasan — itu bukan kasus ini.'),
                        $this->diagram(
                            'Naik tangga hanya jika metrik kasus di validasi tidak lulus target.',
                            [
                                ['id' => 'b0', 'label' => 'Aturan', 'hint' => 'lantai'],
                                ['id' => 'b1', 'label' => 'Linear', 'hint' => 'TF-IDF'],
                                ['id' => 'b2', 'label' => 'Encoder', 'hint' => 'konteks'],
                                ['id' => 'stop', 'label' => 'Berhenti', 'hint' => 'target lulus'],
                            ],
                            [
                                ['from' => 'b0', 'to' => 'b1', 'label' => 'gagal target'],
                                ['from' => 'b1', 'to' => 'b2', 'label' => 'gagal target'],
                                ['from' => 'b2', 'to' => 'stop', 'label' => 'lulus'],
                            ],
                            4,
                        ),
                        $this->paragraph('Kriteria naik tangga harus tertulis: “encoder hanya jika macro-F1 validasi naik ≥ X dan recall perangkat tidak turun.” Tanpa itu, setiap paper baru akan mereset pekerjaanmu.'),
                    ],
                ),
                $this->textLesson(
                    'Klasifikasi, ekstraksi, atau generasi',
                    'Pilih jenis tugas yang benar. Salah pilih tugas tidak bisa diselamatkan oleh model yang lebih besar.',
                    16,
                    [
                        $this->heading('Klasifikasi, ekstraksi, atau generasi', 1),
                        $this->paragraph('Kasus kita adalah klasifikasi: satu label dari lima, atau tolak. Bukan ekstraksi (mengambil nomor aset, nama gedung, kode error sebagai field terstruktur). Bukan generasi (menulis balasan ke pengguna). Mencampur tiga tugas di satu model adalah cara klasik membuat evaluasi tidak bisa ditafsirkan: F1 label bagus, tetapi balasan mengarang SOP yang tidak ada.'),
                        $this->paragraph('Kalau organisasi juga ingin mengisi “gedung” dan “nomor aset”, itu pipeline kedua: ekstraksi setelah routing, dengan skema field dan evaluasi sendiri (exact match / F1 span). Kalau ingin draf balasan, itu pipeline ketiga, dengan manusia wajib baca sebelum kirim. Jangan memasukkan keduanya ke classifier v1 hanya karena satu vendor menjual “AI helpdesk all-in-one”.'),
                        $this->table(
                            ['Tugas', 'Keluaran', 'Evaluasi'],
                            [
                                ['Klasifikasi (kasus ini)', 'Satu kelas atau ragu', 'Macro-F1, recall mahal'],
                                ['Ekstraksi', 'Field terstruktur', 'Akurasi field, missing rate'],
                                ['Generasi balasan', 'Teks ke pengguna', 'Review manusia, bukan BLEU'],
                            ],
                        ),
                        $this->paragraph('Encoder klasifikasi bisa memakai kepala linear di atas [CLS] atau mean pooling. Itu cukup. Memaksa model generatif “jawab hanya dengan satu kata dari daftar” adalah klasifikasi yang mahal dan rapuh terhadap prompt. Untuk lima kelas, itu hampir selalu arsitektur yang salah.'),
                    ],
                ),
                $this->textLesson(
                    'Bahasa Indonesia, slang, dan kode tiket',
                    'Teks produksi bukan korpus berita. Tokenisasi, campur kode, dan istilah lokal menentukan pilihan model.',
                    16,
                    [
                        $this->heading('Bahasa Indonesia, slang, dan kode tiket', 1),
                        $this->paragraph('Tiket Indonesia jarang berupa paragraf baku. “pak wifi lt 3 ged B mati dari kmrn, sso juga kadang 401.” Ada singkatan, ada kode HTTP, ada nama gedung lokal, ada campur Inggris. Encoder yang hanya nyaman di berita akan memotong slang menjadi subword aneh, tetapi tetap biasanya lebih tahan daripada TF-IDF yang tidak pernah melihat “gak” di kamus latih.'),
                        $this->paragraph('Pilihan praktis: encoder multilingual yang kuat di Indonesian+English, atau encoder khusus Bahasa Indonesia, di-fine-tune pada tiketmu. Cek tokenisasi pada 50 tiket nyata: apakah “SSO”, “Wi-Fi”, “ERR_CONNECTION_RESET”, nama aplikasi internal utuh atau hancur? Jika nama aplikasi internal selalu dipecah dan itu sinyal kelas “aplikasi”, pertimbangkan kamus tambahan atau menormalisasi nama produk ke token khusus di latih dan hidup secara konsisten.'),
                        $this->heading('Yang tidak perlu kamu lakukan di v1'),
                        $this->bullets([
                            'Stemming agresif yang merusak “jaringan” vs “jalan”.',
                            'Menerjemahkan semua tiket ke Inggris “supaya model internasional lebih pintar”.',
                            'Membuang kode error karena “bukan bahasa alami”.',
                            'Lowercase buta jika kode produk case-sensitive — uji di validasi, jangan dogma.',
                        ]),
                        $this->paragraph('Bahasa adalah bagian dari kasus, bukan dekorasi. Model yang menang di benchmark sentimen Indonesia tidak otomatis menang di tiketmu. Satu-satunya benchmark yang dihitung adalah split temporal tiket layanan ini.'),
                    ],
                ),
                $this->textLesson(
                    'Biaya, latensi, dan interpretabilitas',
                    'p95 200 ms, biaya per tiket, dan kemampuan menjelaskan ke petugas — itu kendala kasus, bukan afterthought.',
                    15,
                    [
                        $this->heading('Biaya, latensi, dan interpretabilitas', 1),
                        $this->paragraph('Routing tiket terjadi di jalur masuk. Pengguna tidak menunggu 8 detik. Petugas tidak mau kotak hitam yang tidak bisa dibantah. Kendala kasus kita, sebagai contoh yang harus kamu tulis untuk organisasimu: latency p95 ≤ 200 ms di jaringan internal, biaya inferensi jauh di bawah biaya satu hop salah, dan setiap prediksi bisa menampilkan 3 n-gram atau token yang mendorong kelas (untuk linear) atau contoh tetangga (untuk encoder) supaya petugas bisa menolak.'),
                        $this->paragraph('Encoder kecil yang lulus metrik mengalahkan encoder raksasa yang p95-nya 2 detik. Model generatif yang “bisa menjelaskan” sering mengarang penjelasan. Penjelasan yang jujur dari logreg (koefisien) lebih berguna di audit daripada paragraf fasih yang tidak terikat bobot.'),
                        $this->paragraph('Hitung kasar: 2.000 tiket/hari × 30 hari. Kalau encoder kecil hemat dibanding hop salah 5% volume, angka itu yang dibawa ke pimpinan — bukan jumlah parameter. Lesson serving akan memakai kendala ini sebagai kontrak API.'),
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
                        $this->paragraph('Gabungkan judul dan isi dengan pemisah eksplisit, misalnya “JUDUL: …\nISI: …”. Judul sering berisi sinyal kelas yang padat (“VPN down”), isi berisi narasi. Truncasi encoder (512 token) harus mengutamakan judul + awal isi; jangan memotong judul. Tiket sangat panjang (log ditempel) boleh dipotong setelah N karakter, dengan aturan yang sama di serving.'),
                        $this->paragraph('Untuk TF-IDF: unigram+bigram, min_df dipilih di validasi, jangan membuang angka (401, 500). Untuk encoder: pakai tokenizer resmi model itu, jangan tokenizer lain. Jika kamu menormalisasi nama produk ke token `APLIKASI_ABSENSI`, normalisasi itu harus hidup di layanan yang sama, bukan hanya skrip latih.'),
                        $this->paragraph('Simpan contoh representasi di dokumentasi kasus: 5 tiket mentah → 5 string yang benar-benar masuk model. Tanpa itu, enam bulan kemudian orang mereplikasi praproses dengan salah dan menyalahkan “drift”.'),
                    ],
                ),
                $this->textLesson(
                    'Menyesuaikan encoder, bukan memaksa model generatif',
                    'Kepala klasifikasi di atas encoder, learning rate kecil, dan mengapa prompt bukan pengganti label.',
                    18,
                    [
                        $this->heading('Menyesuaikan encoder, bukan memaksa model generatif', 1),
                        $this->paragraph('Untuk lima kelas, pola yang sesuai: encoder pra-latih, kepala linear (atau kecil) ke lima logit, loss entropi silang. Kamu menyesuaikan (fine-tune) encoder pada tiket berlabel. Unfreeze bertahap jika data kecil: latih kepala dulu, baru lapisan atas. Learning rate encoder lebih kecil daripada kepala. Itu pola standar, dan untuk kasus ini hampir selalu lebih tepat daripada prompt.'),
                        $this->paragraph('Prompt ke model generatif “pilih salah satu: akses, jaringan…” tanpa menyesuaikan bobot adalah baseline yang sah untuk diukur — lalu biasanya kalah di latency, biaya, dan label di luar daftar. Few-shot di prompt juga membocorkan contoh yang seharusnya tidak ada di tiket hidup. Jika kamu mengukurnya, ukur di validasi yang sama, dengan biaya per tiket tertulis. Jangan mengganti classifier yang sudah lulus hanya karena demo prompt terlihat pintar di lima contoh.'),
                        $this->paragraph('Retrieval (mencari tiket lama yang mirip) bisa membantu petugas, tetapi itu bukan klasifikasi. Mencampur retrieval ke v1 merancukan metrik. Simpan sebagai eksperimen terpisah setelah classifier lulus target.'),
                        $this->quote('Menyesuaikan encoder pada label yang kamu miliki adalah pekerjaan kasus ini. Memaksa model generatif agar “berperilaku seperti classifier” adalah menghindari pekerjaan itu dengan biaya lebih besar.'),
                    ],
                ),
                $this->textLesson(
                    'Loop pelatihan: loss, batch, overfitting',
                    'Satu putaran: batch → loss → update → validasi. Overfitting terlihat di selisih latih vs validasi, bukan di akurasi latih.',
                    18,
                    [
                        $this->heading('Loop pelatihan: loss, batch, overfitting', 1),
                        $this->paragraph('Loop-nya sederhana dan wajib kamu bisa gambar. Ambil batch tiket latih. Model mengeluarkan logit lima kelas. Loss entropi silang (nanti berbobot) dihitung terhadap label. Gradien merambat. Parameter diupdate. Setelah epoch, ukur macro-F1 di validasi, bukan di latih. Ulangi. Berhenti menurut aturan early stopping, bukan menurut perasaan.'),
                        $this->paragraph('Overfitting pada tiket: F1 latih 0,97, F1 validasi 0,71, dan kelas perangkat di validasi runtuh. Penyebab umum: duplikat, kebocoran, encoder terlalu bebas pada data kecil, atau epoch kebanyakan. Obat yang sesuai kasus: lebih banyak dropout/weight decay, freeze sebagian encoder, kurangi epoch, tambah data kelas miskin yang benar-benar baru — bukan mengulang tiket yang sama.'),
                        $this->diagram(
                            'Loop latih–validasi: update hanya dari latih; keputusan berhenti hanya dari validasi.',
                            [
                                ['id' => 'batch', 'label' => 'Batch latih', 'hint' => 'tiket + label'],
                                ['id' => 'loss', 'label' => 'Loss', 'hint' => 'entropi silang'],
                                ['id' => 'upd', 'label' => 'Update', 'hint' => 'parameter'],
                                ['id' => 'val', 'label' => 'Validasi', 'hint' => 'macro-F1'],
                            ],
                            [
                                ['from' => 'batch', 'to' => 'loss'],
                                ['from' => 'loss', 'to' => 'upd'],
                                ['from' => 'upd', 'to' => 'val'],
                                ['from' => 'val', 'to' => 'batch', 'label' => 'epoch berikutnya'],
                            ],
                            4,
                        ),
                        $this->paragraph('Batch size dan learning rate dipilih di validasi. Jangan menyentuh uji. Catat seed, versi tokenizer, hash data latih. Lesson reproduksibilitas akan menuntut itu sebagai syarat, bukan hiasan.'),
                    ],
                ),
                $this->textLesson(
                    'Kelas timpang dan biaya salah routing',
                    'Bobot kelas, focal loss, dan mengapa mengoptimalkan akurasi adalah mengkhianati SLA perangkat.',
                    16,
                    [
                        $this->heading('Kelas timpang dan biaya salah routing', 1),
                        $this->paragraph('Distribusi hidup mungkin: akses 40%, aplikasi 30%, permintaan 15%, jaringan 10%, perangkat 5%. Loss tanpa bobot akan “belajar” hampir mengabaikan perangkat. Padahal salah routing perangkat ke aplikasi bisa berarti hardware kritis mengantri di tim yang salah selama berjam-jam.'),
                        $this->paragraph('Teknik yang sesuai, diuji di validasi, bukan ditumpuk semua: bobot kelas berbanding terbalik dengan frekuensi; cost-sensitive (salah perangkat lebih mahal di matriks biaya); oversampling terkendali hanya di latih; ambang per kelas (bukan satu ambang untuk semua) di Lesson evaluasi. Focal loss boleh dicoba jika bobot biasa tidak mengangkat recall perangkat. Jangan memakai akurasi sebagai loss proxy.'),
                        $this->paragraph('Tulis matriks biaya bersama pemilik antrian: “perangkat → aplikasi” lebih mahal daripada “permintaan → akses”. Matriks itu memilih model di validasi. Tanpa itu, macro-F1 masih bisa menyembunyikan satu sel yang secara bisnis tidak bisa diterima.'),
                    ],
                ),
                $this->textLesson(
                    'Reproduksibilitas dan early stopping',
                    'Seed, hash data, checkpoint, dan berhenti saat validasi tidak membaik — supaya angka bisa ditagih ulang.',
                    14,
                    [
                        $this->heading('Reproduksibilitas dan early stopping', 1),
                        $this->paragraph('Angka yang tidak bisa diulang bukan hasil, melainkan anekdot. Untuk kasus ini, minimal yang harus tercatat per percobaan: hash berkas latih/validasi/uji, versi tokenizer dan encoder, seed, hyperparameter, epoch terbaik menurut macro-F1 validasi (dengan aturan sabar: berhenti jika tidak membaik N epoch), dan confusion matrix validasi. Checkpoint epoch terbaik itulah yang diukur sekali di uji.'),
                        $this->paragraph('Early stopping melindungi dari overfitting dan dari “satu epoch lagi” yang merusak kelas minoritas. Sabar (patience) dipilih di validasi, lalu dikunci. Jangan early-stop berdasarkan uji. Jangan pilih epoch terbaik berdasarkan uji. Itu kebocoran yang kelihatan seperti ketelitian.'),
                        $this->paragraph('Jika dua orang di tim tidak bisa mengulang F1 validasi dalam toleransi kecil, kamu belum siap serving. Perbaiki pipa dulu. Model yang “hanya jalan di laptop satu orang” bukan model kasus; itu demo rapuh.'),
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
                        $this->paragraph('Confusion matrix adalah tabel 5×5: baris kebenaran, kolom prediksi. Diagonal adalah routing benar. Sel di luar diagonal adalah hop yang akan terjadi di produksi jika model dipercaya buta. “Aplikasi yang diprediksi permintaan” berarti bug masuk antrian pengadaan. “Perangkat yang diprediksi aplikasi” berarti hardware masuk tim software. Ceritakan setiap sel mahal dalam bahasa antrian, bukan dalam bahasa ML.'),
                        $this->paragraph('Pada tiket, dua tabrakan klasik: aplikasi vs permintaan (pengguna menulis “mohon diperbaiki absensi” yang bisa bug atau request fitur), dan akses vs jaringan (“VPN tidak jalan”). Baseline linear biasanya sudah terlihat di dua sel ini. Encoder hanya berhasil jika sel mahal mengecil tanpa mengorbankan diagonal perangkat.'),
                        $this->diagram(
                            'Baca confusion sebagai antrian: prediksi salah = hop ke meja yang salah.',
                            [
                                ['id' => 'true', 'label' => 'Kelas benar', 'hint' => 'antrian seharusnya'],
                                ['id' => 'pred', 'label' => 'Prediksi', 'hint' => 'antrian model'],
                                ['id' => 'ok', 'label' => 'Diagonal', 'hint' => 'hop 0'],
                                ['id' => 'hop', 'label' => 'Di luar diagonal', 'hint' => 'SLA terbakar'],
                            ],
                            [
                                ['from' => 'true', 'to' => 'pred'],
                                ['from' => 'pred', 'to' => 'ok', 'label' => 'cocok'],
                                ['from' => 'pred', 'to' => 'hop', 'label' => 'salah'],
                            ],
                            4,
                        ),
                        $this->paragraph('Lampirkan matrix validasi dan uji di dokumen kasus, lengkap dengan jumlah per sel, bukan hanya heatmap warna. Warna tanpa jumlah menyembunyikan bahwa kelas perangkat hanya 40 tiket uji — satu sel bisa menipu mata.'),
                    ],
                ),
                $this->textLesson(
                    'Macro-F1, bukan akurasi global',
                    'F1 per kelas, rata-rata makro, dan mengapa mikro/akurasi memihak akses.',
                    16,
                    [
                        $this->heading('Macro-F1, bukan akurasi global', 1),
                        $this->paragraph('Precision kelas X: dari yang diprediksi X, berapa yang benar. Recall kelas X: dari yang memang X, berapa yang ketemu. F1 adalah harmonik keduanya. Macro-F1 merata-ratakan F1 lima kelas tanpa peduli volume. Itu mendekati keadilan antar-antrian. Akurasi dan micro-F1 didominasi akses+aplikasi. Untuk kasus timpang, macro-F1 plus recall kelas mahal adalah paket minimum.'),
                        $this->paragraph('Laporkan F1 per kelas, bukan satu angka. Target tertulis di Lesson metrik harus dicek satu per satu. Model dengan macro-F1 0,84 tetapi recall perangkat 0,45 gagal kasus, meski slide-nya bagus. Model dengan macro-F1 0,79 dan recall perangkat 0,83 mungkin lebih layak jika matriks biaya bilang begitu.'),
                        $this->paragraph('Interval: dengan uji kecil, beda 0,02 macro-F1 bisa noise. Jangan naik tangga model demi 0,01. Pakai bootstrap di uji atau setidaknya bandingkan confusion-nya. Pimpinan butuh “lulus / tidak lulus target”, bukan perlombaan desimal.'),
                    ],
                ),
                $this->textLesson(
                    'Ambang tidak yakin dan eskalasi manusia',
                    'Keyakinan kalibrasi, ambang per kelas, dan kuota tiket yang boleh ke manusia.',
                    18,
                    [
                        $this->heading('Ambang tidak yakin dan eskalasi manusia', 1),
                        $this->paragraph('Softmax lima kelas memberi skor. Skor itu belum tentu kalibrasi: 0,70 tidak otomatis berarti “benar 70%”. Untuk kasus ini, kamu butuh kebijakan: jika skor maksimum < τ, atau selisih juara vs runner-up < δ, kirim ke manusia. τ dan δ dipilih di validasi agar macro-F1 pada tiket yang di-auto-route lulus target, dan persentase eskalasi tidak melewati kapasitas petugas (contoh: ≤ 25%).'),
                        $this->paragraph('Ambang boleh per kelas. Perangkat dengan skor 0,55 mungkin harus eskalasi, sementara akses dengan 0,55 sudah aman. Itu konsekuensi matriks biaya, bukan kerumitan berlebih. Yang penting: kebijakan yang sama dipakai di uji dan di serving. Mengubah τ setelah melihat uji adalah menyentuh uji.'),
                        $this->diagram(
                            'Serving dengan manusia di loop: skor rendah tidak dipaksa menjadi label.',
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
                        $this->paragraph('Eskalasi bukan aib model. Itu cara kasus tetap aman saat teks di luar distribusi: tiket campur, bahasa baru, produk baru. Lesson analisis galat akan memakai tumpukan eskalasi sebagai sumber belajar, bukan sebagai tempat menyembunyikan kegagalan.'),
                    ],
                ),
                $this->textLesson(
                    'Analisis galat: tiket campur dan bahasa campur',
                    'Baca 100 galat. Kelompokkan. Jangan langsung naikkan epoch.',
                    16,
                    [
                        $this->heading('Analisis galat: tiket campur dan bahasa campur', 1),
                        $this->paragraph('Ambil 100 tiket validasi yang salah atau yang eskalasi. Kelompokkan dengan tangan: (1) ontologi memang ambigu, (2) tiket campur dua masalah, (3) slang/typo ekstrem, (4) nama produk baru, (5) kebocoran yang tersisa, (6) label historis salah. Setiap kelompok punya obat berbeda. Ontologi → perbaiki panduan dan relabel. Campur → kebijakan “kelas penghalang utama” atau eskalasi wajib. Produk baru → kamus normalisasi, bukan epoch tambahan. Label salah → bersihkan emas, jangan hukum model.'),
                        $this->paragraph('Bahasa campur (“wifi down dari kemarin, SSO 401”) biasanya encoder sudah tahan. Yang merusak adalah campur tugas: pengguna minta laptop baru karena aplikasi absensi error — perangkat atau aplikasi? Panduan harus memilih. Model tidak boleh dibiarkan “kreatif”.'),
                        $this->paragraph('Hasil analisis galat adalah backlog data, bukan backlog arsitektur. Jika 60% galat adalah label historis salah, naik tangga ke encoder adalah hiburan. Relabel dulu.'),
                    ],
                ),
                $this->textLesson(
                    'Uji temporal di data yang lebih baru',
                    'Satu kali di uji terkunci. Lalu, setelah waktu berjalan, uji lagi di bulan yang tidak ikut memilih model.',
                    15,
                    [
                        $this->heading('Uji temporal di data yang lebih baru', 1),
                        $this->paragraph('Setelah model dan ambang dikunci dari validasi, ukur sekali di uji (misalnya Sep–Okt). Simpan angka itu. Serving boleh dimulai hanya jika target kasus lulus di uji itu, atau jika pemilik antrian menerima pengecualian tertulis (misalnya recall perangkat masih 0,77, dengan mode bayangan).'),
                        $this->paragraph('Dua bulan kemudian, tiket November–Desember adalah uji baru. Jangan mengutak-atik model untuk mengalahkan uji baru itu sebelum kamu mencatat angka beku. Itu pengukuran drift. Jika macro-F1 jatuh karena rilis SSO baru, itu sinyal putaran ulang (data baru, mungkin label baru), bukan sinyal “tambah epoch di data lama”.'),
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
                        $this->paragraph('Layanan v1 menerima JSON: judul, isi, id tiket (untuk log, bukan untuk fitur), dan id permintaan. Ia mengembalikan: label atau “tidak_yakin”, skor lima kelas, versi model, dan waktu inferensi. Timeout harus di bawah anggaran latency (contoh 200 ms p95, timeout 300 ms). Jika timeout, perilaku aman adalah eskalasi ke manusia — bukan menebak. Jangan pernah memblokir pembuatan tiket karena model mati; antrian lama adalah fallback.'),
                        $this->paragraph('Praproses di layanan harus bit-level sama dengan latih: pemisah judul/isi, truncasi, placeholder PII, kamus produk. Versi praproses adalah bagian dari versi model. Mengganti regex diam-diam adalah merilis model baru tanpa nama.'),
                        $this->diagram(
                            'Kontrak hidup: tiket masuk, prediksi atau fallback manusia, antrian tidak boleh menunggu model yang mati.',
                            [
                                ['id' => 'api', 'label' => 'API tiket', 'hint' => 'judul+isi'],
                                ['id' => 'mdl', 'label' => 'Model vN', 'hint' => '≤200 ms'],
                                ['id' => 'q', 'label' => 'Antrian', 'hint' => 'label'],
                                ['id' => 'fb', 'label' => 'Fallback', 'hint' => 'timeout / ragu'],
                            ],
                            [
                                ['from' => 'api', 'to' => 'mdl'],
                                ['from' => 'mdl', 'to' => 'q', 'label' => 'yakin'],
                                ['from' => 'mdl', 'to' => 'fb', 'label' => 'ragu/timeout'],
                                ['from' => 'fb', 'to' => 'q'],
                            ],
                            4,
                        ),
                        $this->paragraph('Log yang disimpan: id tiket, versi model, skor, label, apakah di-override petugas. Isi tiket di log mengikuti kebijakan privasi (Lesson berikutnya). Tanpa log override, kamu tidak punya data putaran ulang.'),
                    ],
                ),
                $this->textLesson(
                    'Mode bayangan dan peluncuran bertahap',
                    'Prediksi paralel dulu. Lalu persentase kecil. Baru default. Jangan cutover big-bang.',
                    16,
                    [
                        $this->heading('Mode bayangan dan peluncuran bertahap', 1),
                        $this->paragraph('Mode bayangan: routing lama tetap berkuasa. Model memprediksi di paralel, hasilnya dicatat, tidak mengubah antrian pengguna. Bandingkan prediksi vs antrian akhir petugas selama dua minggu. Hitung macro-F1 “hidup”, persentase ragu, latency, dan sel mahal. Jika angka bayangan jauh di bawah uji terkunci, ada kebocoran praproses atau drift. Jangan naik persentase.'),
                        $this->paragraph('Jika bayangan lulus, peluncuran bertahap: 10% tiket auto-route (yang di atas ambang), sisanya manusia. Naikkan hanya pada jadwal, dengan pemilik antrian yang bisa menarik tuas kembali dalam satu konfigurasi. Big-bang “besok semua ke model” adalah cara mengubah galat 8% menjadi insiden organisasi.'),
                        $this->paragraph('Kill switch: jika p95 latency tembus, atau override petugas meledak, atau satu kelas recall jatuh, kembali ke routing lama. Lesson ini bukan konsol untuk menekan tuas itu; tuas itu milik operasional di luar academy. Yang kamu pahami di sini: tuas harus ada sebelum serving, bukan setelah keluhan.'),
                    ],
                ),
                $this->textLesson(
                    'Umpan balik petugas dan putaran ulang',
                    'Override adalah emas. Jadwal relabel dan latih ulang, bukan “model sekali seumur hidup”.',
                    18,
                    [
                        $this->heading('Umpan balik petugas dan putaran ulang', 1),
                        $this->paragraph('Setiap kali petugas memindahkan tiket dari antrian prediksi ke antrian lain, itu pasangan (teks, label baru) untuk putaran berikutnya — setelah dicek bahwa pindahnya memang koreksi kelas, bukan hop karena tiket campur yang berubah di tengah jalan. Kumpulkan override. Jangan latih ulang setiap malam; itu merusak reproduksibilitas. Jadwalkan: misalnya setiap bulan, atau setelah N override di kelas mahal, atau setelah rilis produk besar.'),
                        $this->paragraph('Putaran ulang memakai split temporal baru: uji lama menjadi arsip, data terbaru menjadi uji, dan latih memasukkan data yang sebelumnya uji hanya setelah dikunci sebagai sejarah. Relabel sampel jika ontologi bergeser (kelas keenam “keamanan” muncul). Jangan diam-diam menambah kelas di serving tanpa Lesson ontologi yang baru.'),
                        $this->diagram(
                            'Iterasi: hidup menghasilkan override, override menjadi data, data menjadi model vN+1.',
                            [
                                ['id' => 'hidup', 'label' => 'Serving vN', 'hint' => 'prediksi'],
                                ['id' => 'ov', 'label' => 'Override', 'hint' => 'petugas'],
                                ['id' => 'data', 'label' => 'Data baru', 'hint' => 'split baru'],
                                ['id' => 'vnext', 'label' => 'Model vN+1', 'hint' => 'bayangan lagi'],
                            ],
                            [
                                ['from' => 'hidup', 'to' => 'ov'],
                                ['from' => 'ov', 'to' => 'data'],
                                ['from' => 'data', 'to' => 'vnext'],
                                ['from' => 'vnext', 'to' => 'hidup', 'label' => 'setelah bayangan'],
                            ],
                            4,
                        ),
                        $this->paragraph('Tanpa jadwal, organisasi punya dua gagal simetris: tidak pernah latih ulang (drift) atau latih ulang chaos tanpa uji terkunci (regresi). Keduanya bukan “AI mature”. Keduanya adalah tidak punya kasus yang dipegang.'),
                    ],
                ),
                $this->textLesson(
                    'Privasi tiket, bias, dan audit',
                    'PII, hak akses log, bias unit/gedung, dan jejak versi yang bisa diaudit.',
                    16,
                    [
                        $this->heading('Privasi tiket, bias, dan audit', 1),
                        $this->paragraph('Tiket berisi nama, NIP, keluhan kesehatan, konflik atasan, lampiran screenshot layar email. v1 tidak butuh identitas untuk klasifikasi lima kelas. Placeholder PII di praproses. Log produksi: id tiket + prediksi, isi lengkap hanya di sistem tiket yang sudah punya hak akses, bukan di penyimpanan model. Jangan mengirim teks tiket ke vendor luar tanpa dasar dan tanpa filter. Kursus ini tidak men-deploy apa pun, tetapi kontrak privasi harus tertulis sebelum serving di organisasimu.'),
                        $this->paragraph('Bias: model bisa lebih sering salah pada tiket dari unit tertentu karena bahasa, atau pada tiket pendek dari pengguna non-teknis. Ukur F1 per kanal (portal vs email) dan per panjang teks di validasi. Jika tiket satu kalimat dari pengguna non-teknis recall-nya jatuh, itu masalah kasus — mungkin eskalasi wajib untuk tiket sangat pendek — bukan alasan menambah model generatif.'),
                        $this->paragraph('Audit: setiap prediksi yang mengubah antrian harus menunjuk versi model. Setiap versi menunjuk hash data dan panduan label. Ketika ada keluhan “mengapa tiket ini ke perangkat?”, kamu harus bisa menjawab dengan versi, skor, dan kebijakan ambang — bukan “AI-nya begitu”.'),
                    ],
                ),
                $this->textLesson(
                    'Ringkasan: dari kasus ke model yang hidup',
                    'Enam bagian, satu kasus, satu tes: apakah keputusan routing tiket lebih baik, lebih aman, dan bisa diulang.',
                    16,
                    [
                        $this->heading('Ringkasan: dari kasus ke model yang hidup', 1),
                        $this->paragraph('Kamu sudah menelusuri satu kasus sampai ujung. Merangka: keputusan routing lima kelas, metrik SLA dan macro-F1, kapan tidak perlu model. Data: sumber yang sah, ontologi, split temporal, kebocoran. Model: aturan dan linear dulu, encoder jika perlu, bukan generasi. Melatih: representasi konsisten, kepala klasifikasi, loop loss, kelas timpang, seed dan early stopping. Evaluasi: confusion sebagai hop, macro-F1, ambang manusia, galat yang dikelompokkan, uji terkunci lalu uji kemudian. Serving: kontrak API, bayangan, override, privasi, putaran ulang.'),
                        $this->paragraph('Tes kedewasaan bukan “apakah kita punya encoder”. Tesnya: apakah ada pemilik metrik, apakah uji temporal lulus target tertulis, apakah manusia punya jalur ragu, apakah versi bisa diaudit, apakah Lesson ini tidak pernah menjadi lab. Jika salah satu tidak ada, model belum hidup. Ia masih slide.'),
                        $this->heading('Yang tidak kamu bawa pulang'),
                        $this->bullets([
                            'Akses Restricted Course atau runtime agen. Kursus ini tidak memberikannya.',
                            'Notebook di dalam Lesson. Pelatihan nyata terjadi di pipa organisasimu, di luar academy.',
                            'Resep universal untuk semua kasus. Deteksi dokumen, ranking, dan generasi punya metrik lain. Disiplinnya sama: kasus dulu.',
                        ]),
                        $this->quote('Model untuk kasus tertentu selesai bukan saat F1 bagus, melainkan saat antrian, manusia, dan putaran data punya pemilik. Itu pekerjaan yang kamu latih di sini.'),
                        $this->paragraph('Kerjakan kuis wajib. Jawaban ada di Lesson, bukan di tren. Kalau suatu soal terasa seperti “pilih arsitektur terbaru”, kamu sudah tahu itu jebakan: kembali ke kasus tiket.'),
                    ],
                ),
            ],
        ];
    }
}
