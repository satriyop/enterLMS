"""Potong NIK dan nomor HP sebelum teks masuk model atau log prediksi.

Nomor di berkas ini karangan. Pola NIK adalah 16 digit.
Nomor HP Indonesia lebih pendek, jadi pemotong yang hanya
mencari 16 digit membiarkan HP lolos.
Jalankan: python3 redaksi.py
"""

from __future__ import annotations

import re

NIK = re.compile(r"(?<!\d)\d{16}(?!\d)")
HP = re.compile(r"(?<!\d)(?:\+62|62|0)8\d{8,11}(?!\d)")

LOG_BURUK = (
    "2026-09-08 08:14:02 prediksi=kebersihan skor=0.62 "
    'teks="ktp saya hilang nik 3201013101900001 mohon diterbitkan ulang"'
)
HP_BANSOS = "ibu saya nomor 081234567890 bansos belum cair"
BARIS_LATIH = "jalan di gang melati ambles setelah hujan. sudah diteruskan ke bidang PU"

SETELAH_DITANGANI = ("sudah diteruskan", "telah diselesaikan", "ke bidang pu")


def redaksi(teks: str) -> str:
    teks = NIK.sub("[NIK]", teks)
    return HP.sub("[HP]", teks)


def redaksi_nik_saja(teks: str) -> str:
    """Pemotong yang belum selesai. HP tidak tersentuh."""
    return NIK.sub("[NIK]", teks)


def ada_identitas(teks: str) -> bool:
    return NIK.search(teks) is not None or HP.search(teks) is not None


def kalimat_setelah_ditangani(teks: str) -> bool:
    rendah = teks.lower()
    return any(tanda in rendah for tanda in SETELAH_DITANGANI)


def uji_log(baris: str) -> str:
    if ada_identitas(baris):
        return "gagal: log masih menyimpan NIK atau HP"
    return "lulus: log tanpa NIK dan tanpa HP"


def main() -> None:
    print("log September, sebelum dipotong")
    print(f"  identitas = {ada_identitas(LOG_BURUK)}")
    print(uji_log(LOG_BURUK))
    bersih = redaksi(LOG_BURUK)
    print("log setelah redaksi()")
    print(f"  {bersih}")
    print(uji_log(bersih))

    print()
    print("HP tidak 16 digit, pemotong NIK saja gagal")
    print(f"  nik saja : {redaksi_nik_saja(HP_BANSOS)}")
    print(f"  lengkap  : {redaksi(HP_BANSOS)}")
    print(f"  identitas tersisa pada nik saja = {ada_identitas(redaksi_nik_saja(HP_BANSOS))}")

    print()
    print("baris latih")
    print(f"  setelah ditangani = {kalimat_setelah_ditangani(BARIS_LATIH)}")
    print(f"  teks loket saja   = {kalimat_setelah_ditangani(BARIS_LATIH.split('.')[0])}")


if __name__ == "__main__":
    main()
