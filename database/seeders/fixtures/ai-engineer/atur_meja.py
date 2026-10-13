"""Aturan meja Dina, ditulis supaya bisa dijalankan.

Dua belas teks ini sama dengan daftar di lesson kasus.
Bahaya fisik menang dulu. Pertanyaan jam atau syarat tidak dipaksa ke meja.
Jalankan dari folder ini: python3 atur_meja.py
"""

from __future__ import annotations

PENGADUAN = [
    "ktp saya hilang, mohon diterbitkan ulang",
    "ktp saya hilang nik 3201013101900001 mohon diterbitkan ulang",
    "imb rumah ditolak tanpa alasan",
    "jalan di gang melati ambles setelah hujan",
    "sampah tiga hari tidak diangkut di rt 04",
    "bantuan sosial ibu saya tidak cair",
    "lampu jalan mati dan sampah menumpuk",
    "air rob masuk rumah dan lampu mati",
    "akta lahir anak saya belum jadi sudah 3 bulan",
    "izin usaha warung ditolak",
    "selokan mampet depan rumah, baunya sampai ke dalam",
    "jam buka loket hari Sabtu sampai jam berapa?",
]

LATIHAN = [
    "genteng tetangga jatuh ke jalan gang melati",
    "minta info syarat imb, saya belum mengajukan",
    "sampah pasar tidak diangkut dan lampu di gang yang sama mati",
    "bantuan lansia ibu saya tidak cair sejak Agustus",
]

FISIK = ("ambles", "lampu", "rob", "genteng", "jembatan", "genangan")
KEPENDUDUKAN = ("ktp", "akta", "kartu keluarga")
PERIZINAN = ("imb", "izin usaha", "izin ")
KEBERSIHAN = ("sampah", "selokan", "bau")
SOSIAL = ("bantuan sosial", "bansos", "bantuan", "lansia")
TANYA = ("jam buka", "syarat imb", "info syarat")


def bahaya_fisik(teks: str) -> bool:
    """Jalan ambles, lampu mati, rob, atau air yang masuk rumah."""
    rendah = teks.lower()
    if "air masuk rumah" in rendah:
        return True
    return any(kata in rendah for kata in FISIK)


def meja(teks: str) -> str:
    """Satu meja, atau ragu. NIK di dalam teks tidak mengubah meja."""
    rendah = teks.lower()
    if bahaya_fisik(rendah):
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


def cetak(judul: str, baris: list[str]) -> None:
    print(judul)
    for nomor, teks in enumerate(baris, start=1):
        print(f"{nomor:2}. {meja(teks):16} {teks}")


if __name__ == "__main__":
    cetak("September, aturan Dina", PENGADUAN)
    print()
    cetak("Empat latihan", LATIHAN)
