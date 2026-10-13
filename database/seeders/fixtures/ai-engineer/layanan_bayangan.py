"""Layanan prediksi yang masih mode bayangan.

Antrian warga tidak berubah selama MODE_BAYANGAN benar.
Log hanya menerima teks yang sudah dipotong.
Jalankan: python3 layanan_bayangan.py
"""

from __future__ import annotations

import re

NIK = re.compile(r"(?<!\d)\d{16}(?!\d)")
HP = re.compile(r"(?<!\d)(?:\+62|62|0)8\d{8,11}(?!\d)")
VERSI = "arunika-aturan-2026-10-05"
AMBANG_RECALL = 0.85

MODE_BAYANGAN = True

FISIK = ("ambles", "lampu", "rob", "genteng", "jembatan", "genangan")
KEPENDUDUKAN = ("ktp", "akta", "kartu keluarga")
PERIZINAN = ("imb", "izin usaha", "izin ")
KEBERSIHAN = ("sampah", "selokan", "bau")
SOSIAL = ("bantuan sosial", "bansos", "bantuan", "lansia")
TANYA = ("jam buka", "syarat imb", "info syarat")


def redaksi(teks: str) -> str:
    return HP.sub("[HP]", NIK.sub("[NIK]", teks))


def ada_identitas(teks: str) -> bool:
    return NIK.search(teks) is not None or HP.search(teks) is not None


def meja(teks: str) -> str:
    rendah = teks.lower()
    if "air masuk rumah" in rendah or any(kata in rendah for kata in FISIK):
        return "infrastruktur"
    if any(kata in rendah for kata in TANYA):
        return "ragu"
    kena = []
    if any(kata in rendah for kata in KEPENDUDUKAN):
        kena.append("kependudukan")
    if any(kata in rendah for kata in PERIZINAN):
        kena.append("perizinan")
    if any(kata in rendah for kata in KEBERSIHAN):
        kena.append("kebersihan")
    if any(kata in rendah for kata in SOSIAL):
        kena.append("sosial")
    if len(kena) == 1:
        return kena[0]
    return "ragu"


def keyakinan(teks: str, saran: str) -> float:
    if saran == "ragu":
        return 0.38
    rendah = teks.lower()
    kena = sum(kata in rendah for kata in FISIK + KEPENDUDUKAN + PERIZINAN + KEBERSIHAN + SOSIAL)
    return min(0.55 + (0.1 * kena), 0.96)


def tulis_log(saran: str, skor: float, teks_bersih: str) -> str:
    baris = f"versi={VERSI} mode={'bayangan' if MODE_BAYANGAN else 'hidup'} prediksi={saran} skor={skor:.2f} teks={teks_bersih!r}"
    if ada_identitas(baris):
        raise RuntimeError("log masih menyimpan NIK atau HP")
    return baris


def tanggapi(teks: str) -> dict[str, object]:
    bersih = redaksi(teks)
    saran = meja(bersih)
    skor = keyakinan(bersih, saran)
    return {
        "versi": VERSI,
        "saran": saran,
        "meja_antrian": None if MODE_BAYANGAN else saran,
        "skor": round(skor, 2),
        "ragu": saran == "ragu",
        "mode": "bayangan" if MODE_BAYANGAN else "hidup",
        "log": tulis_log(saran, skor, bersih),
    }


def rollback(alasan: str) -> dict[str, object]:
    global MODE_BAYANGAN
    MODE_BAYANGAN = True
    return {"mode": "bayangan", "antrian": "Dina", "alasan": alasan, "batas_menit": 15}


def uji_rollback(menit: int) -> str:
    if menit < 15:
        return f"lulus: antrian kembali ke Dina dalam {menit} menit"
    return f"gagal: {menit} menit melewati batas 15"


def picu_latih_ulang(recall_minggu_ini: float) -> str:
    if recall_minggu_ini < AMBANG_RECALL:
        return "tahan, panggil Bima, jangan promosikan"
    return "lanjut pantau"


def health(hasil: list[dict[str, object]]) -> dict[str, object]:
    identitas = 0
    ragu = 0
    for baris in hasil:
        log = str(baris["log"])
        if ada_identitas(log):
            identitas += 1
        if baris["ragu"]:
            ragu += 1
    return {
        "versi": VERSI,
        "permintaan": len(hasil),
        "identitas_di_log": identitas,
        "porsi_ragu": ragu / len(hasil),
        "mode": "bayangan" if all(baris["mode"] == "bayangan" for baris in hasil) else "campur",
    }


def main() -> None:
    contoh = [
        "ktp saya hilang nik 3201013101900001 mohon diterbitkan ulang",
        "jalan di gang melati ambles setelah hujan",
        "jam buka loket hari Sabtu sampai jam berapa?",
        "sampah tiga hari tidak diangkut",
        "ibu saya nomor 081234567890 bansos belum cair",
    ]
    hasil = [tanggapi(teks) for teks in contoh]
    for baris in hasil:
        print(baris["log"])
        print(f"  meja_antrian={baris['meja_antrian']!s} saran={baris['saran']} ragu={baris['ragu']}")

    print()
    ringkas = health(hasil)
    for kunci, nilai in ringkas.items():
        if isinstance(nilai, float):
            print(f"health {kunci} = {nilai:.2f}")
        else:
            print(f"health {kunci} = {nilai}")

    print()
    print(uji_rollback(6))
    print(uji_rollback(22))
    print(rollback("recall infrastruktur jatuh pada data yang lebih baru"))
    print(picu_latih_ulang(0.81))
    print(picu_latih_ulang(0.92))


if __name__ == "__main__":
    main()
