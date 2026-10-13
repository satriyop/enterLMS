"""Evaluasi latihan. Angkanya bukan paket siang dan bukan angka kuis.

Modelnya sengaja kecil: bobot kata yang bisa dihitung dengan tangan.
Satu baris ditunjukkan skornya. Lalu metrik dihitung ulang setelah
kalimat "sudah diteruskan ke bidang PU" menempel pada kelas infrastruktur.
Kalimat itu belum ada pada saat Dina memilih meja.

Jalankan: python3 evaluasi_latihan.py
"""

from __future__ import annotations

KELAS = (
    "kependudukan",
    "perizinan",
    "infrastruktur",
    "kebersihan",
    "sosial",
    "ragu",
)

FITUR = {
    "ktp": ("ktp",),
    "akta": ("akta",),
    "imb": ("imb",),
    "izin": ("izin",),
    "ambles": ("ambles", "genteng"),
    "lampu": ("lampu",),
    "rob": ("rob",),
    "jalan": ("jalan",),
    "sampah": ("sampah",),
    "selokan": ("selokan", "bau"),
    "bantuan": ("bantuan", "lansia"),
    "tanya": ("jam buka", "syarat"),
}

# bias membuat meja besar menang ketika kata kuncinya lemah.
BOBOT = {
    "kependudukan": {"bias": 0.6, "ktp": 2.0, "akta": 2.0},
    "perizinan": {"bias": 0.2, "imb": 2.0, "izin": 2.0},
    "infrastruktur": {"bias": 0.0, "ambles": 2.0, "lampu": 1.0, "rob": 2.0, "jalan": 1.0},
    "kebersihan": {"bias": 0.3, "sampah": 2.0, "selokan": 2.0},
    "sosial": {"bias": 0.2, "bantuan": 2.0},
    "ragu": {"bias": 0.5, "tanya": 2.0},
}

# label, teks, tanggal. Uji harus lebih baru daripada latih.
LATIH = [
    ("kependudukan", "ktp saya hilang mohon diterbitkan ulang", "2026-09-02"),
    ("kependudukan", "akta lahir anak saya belum jadi", "2026-09-04"),
    ("kependudukan", "ktp rusak kena air", "2026-09-08"),
    ("kependudukan", "kk saya tertinggal di kampung", "2026-09-11"),
    ("perizinan", "imb rumah ditolak tanpa alasan", "2026-09-03"),
    ("perizinan", "izin usaha warung ditolak", "2026-09-07"),
    ("perizinan", "imb pagar ditolak", "2026-09-14"),
    ("perizinan", "izin renovasi rumah ditahan", "2026-09-18"),
    ("infrastruktur", "jalan di gang melati ambles setelah hujan", "2026-09-05"),
    ("infrastruktur", "lampu jalan mati dan sampah menumpuk", "2026-09-09"),
    ("infrastruktur", "ambles gan", "2026-09-15"),
    ("infrastruktur", "jalan rusak di rt 04", "2026-09-20"),
    ("kebersihan", "sampah tiga hari tidak diangkut di rt 04", "2026-09-06"),
    ("kebersihan", "selokan mampet depan rumah baunya sampai ke dalam", "2026-09-10"),
    ("kebersihan", "sampah pasar menumpuk", "2026-09-16"),
    ("kebersihan", "bau sampah dari truk yang mogok", "2026-09-21"),
    ("sosial", "bantuan sosial ibu saya tidak cair", "2026-09-06"),
    ("sosial", "bantuan lansia ibu saya tidak cair sejak Agustus", "2026-09-12"),
    ("sosial", "bantuan belum masuk", "2026-09-19"),
    ("sosial", "lansia di rt 02 belum terima bantuan", "2026-09-22"),
    ("ragu", "jam buka loket hari Sabtu sampai jam berapa", "2026-09-08"),
    ("ragu", "minta info syarat imb saya belum mengajukan", "2026-09-13"),
]

TANGGAL_LATIH_TERAKHIR = "2026-09-30"
TANGGAL_UJI_JUJUR = "2026-10-06"
TANGGAL_UJI_ACAK = "2026-09-18"
AMBANG_RECALL_INFRA = 0.85


def vektor(teks: str) -> dict[str, int]:
    rendah = teks.lower()
    return {
        nama: int(any(kunci in rendah for kunci in kata))
        for nama, kata in FITUR.items()
    }


def skor_kelas(teks: str, bocor: int = 0) -> dict[str, float]:
    v = vektor(teks)
    hasil = {}
    for kelas, bobot in BOBOT.items():
        nilai = bobot["bias"]
        for nama, aktif in v.items():
            nilai += bobot.get(nama, 0.0) * aktif
        if kelas == "infrastruktur":
            # Bobot ini hanya hidup jika kalimat setelah penanganan ikut dibaca.
            nilai += 3.0 * bocor
        hasil[kelas] = nilai
    return hasil


def prediksi(teks: str, bocor: int = 0) -> str:
    skor = skor_kelas(teks, bocor)
    urut = sorted(skor, key=skor.get, reverse=True)
    if skor[urut[0]] - skor[urut[1]] < 0.5:
        return "ragu"
    if skor[urut[0]] < 1.0:
        return "ragu"
    return urut[0]


def metrik(pasangan: list[tuple[str, str]]) -> dict[str, float | dict[str, float]]:
    benar = 0
    recall: dict[str, float] = {}
    f1: dict[str, float] = {}
    for kelas in KELAS:
        gold = [baris for baris in pasangan if baris[0] == kelas]
        tebakan = [baris for baris in pasangan if baris[1] == kelas]
        kena = [baris for baris in pasangan if baris[0] == kelas and baris[1] == kelas]
        rec = (len(kena) / len(gold)) if gold else 0.0
        prec = (len(kena) / len(tebakan)) if tebakan else 0.0
        recall[kelas] = rec
        f1[kelas] = 0.0 if prec + rec == 0 else (2 * prec * rec / (prec + rec))
    benar = sum(1 for gold, tebak in pasangan if gold == tebak)
    ada = [kelas for kelas in KELAS if any(gold == kelas for gold, _ in pasangan)]
    return {
        "akurasi": benar / len(pasangan),
        "macro_f1": sum(f1[kelas] for kelas in ada) / len(ada),
        "recall": recall,
    }


def split_jujur(tanggal_uji: str, tanggal_latih_terakhir: str) -> bool:
    return tanggal_uji > tanggal_latih_terakhir


def cetak_baris(teks: str) -> None:
    skor = skor_kelas(teks)
    print(f"teks: {teks}")
    for kelas in KELAS:
        print(f"  {kelas:16} {skor[kelas]:.1f}")
    print(f"  prediksi = {prediksi(teks)}")


def evaluasi(judul: str, baris: list[tuple[str, str, str]], bocor: bool) -> dict[str, float | dict[str, float]]:
    pasangan = []
    for label, teks, _tanggal in baris:
        teks_model = teks
        tanda = 0
        if bocor and label == "infrastruktur":
            teks_model = teks + " sudah diteruskan ke bidang PU"
            tanda = 1
        pasangan.append((label, prediksi(teks_model, tanda)))
    angka = metrik(pasangan)
    print(judul)
    print(f"  akurasi   {angka['akurasi']:.2f}")
    print(f"  macro-F1  {angka['macro_f1']:.2f}")
    recall = angka["recall"]
    assert isinstance(recall, dict)
    for kelas in KELAS:
        print(f"  recall {kelas:16} {recall[kelas]:.2f}")
    return angka


def main() -> None:
    print("satu baris campur, dihitung tangan")
    cetak_baris("lampu jalan mati dan sampah menumpuk")
    print("label emas = infrastruktur")
    print("kebersihan 2.3 dan infrastruktur 2.0, selisih 0.3, di bawah 0.5, jadi ragu")
    print("aturan dinas tidak setuju: lampu mati harus menang, bukan abstain")
    print()

    print("satu baris tanpa kata ambles")
    cetak_baris("jalan rusak di rt 04")
    print("label emas = infrastruktur. Skor 1.0 terlalu dekat dengan bias kependudukan 0.6")
    print()

    print("fitur tidak memuat kk")
    cetak_baris("kk saya tertinggal di kampung")
    print("label emas = kependudukan. Tidak ada kata yang menyala, prediksi jatuh ke ragu")
    print()

    tanpa = evaluasi("tanpa kalimat setelah ditangani", LATIH, bocor=False)
    print()
    dengan = evaluasi("kalimat setelah ditangani menempel di infrastruktur", LATIH, bocor=True)

    recall_tanpa = tanpa["recall"]
    recall_dengan = dengan["recall"]
    assert isinstance(recall_tanpa, dict)
    assert isinstance(recall_dengan, dict)
    print()
    print(
        "kenaikan recall infrastruktur "
        f"{recall_tanpa['infrastruktur']:.2f} -> {recall_dengan['infrastruktur']:.2f} "
        "tidak sah: fiturnya belum ada di loket"
    )
    lulus = float(recall_tanpa["infrastruktur"]) >= AMBANG_RECALL_INFRA
    print(f"lulus ambang 0.85 pada data jujur = {lulus}")

    print()
    print(f"uji {TANGGAL_UJI_JUJUR} lebih baru daripada latih {TANGGAL_LATIH_TERAKHIR} = {split_jujur(TANGGAL_UJI_JUJUR, TANGGAL_LATIH_TERAKHIR)}")
    print(f"uji {TANGGAL_UJI_ACAK} lebih baru daripada latih {TANGGAL_LATIH_TERAKHIR} = {split_jujur(TANGGAL_UJI_ACAK, TANGGAL_LATIH_TERAKHIR)}")
    print("paket siang tidak ada di berkas ini")


if __name__ == "__main__":
    main()
