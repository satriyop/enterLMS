"""Ambang hari 1, dihitung dari rekap September, bukan disalin dari tabel.

Jalankan: python3 lembar_september.py
Dokumen sasaran memakai koma desimal. Python mencetak titik.
0.85 di sini adalah 0,85 di halaman yang ditandatangani.
"""

from __future__ import annotations

import math

SEPTEMBER = {
    "kependudukan": 3360,
    "perizinan": 1920,
    "kebersihan": 1440,
    "infrastruktur": 800,
    "sosial": 480,
}

# Sampel waktu jawab model di notebook Bima, milidetik. Ini bukan loket.
NOTEBOOK_MS = [
    12, 14, 11, 18, 15, 13, 16, 19, 14, 12,
    17, 15, 13, 21, 14, 16, 18, 12, 15, 28,
]

# Sampel yang sama bentuknya, diukur pada layanan di jaringan dinas.
LAYANAN_MS = [
    90, 100, 110, 120, 130, 140, 150, 160, 170, 180,
    190, 200, 210, 220, 230, 250, 260, 280, 340, 890,
]


def recall(terlewat: int, jumlah: int) -> float:
    """Bagian yang tertangkap. Terlewat dibagi jumlah, lalu 1 dikurangi hasil itu."""
    if jumlah <= 0:
        raise ValueError("jumlah meja kosong")
    return 1 - (terlewat / jumlah)


def menit_ragu(pengaduan_per_hari: int, porsi: float, menit_per_tiket: int) -> tuple[int, int]:
    tiket = round(pengaduan_per_hari * porsi)
    return tiket, tiket * menit_per_tiket


def p95(ms: list[int]) -> int:
    """Peringkat terdekat: ceil(0.95 * n), dihitung dari 1."""
    if not ms:
        raise ValueError("tidak ada sampel")
    urut = sorted(ms)
    peringkat = math.ceil(0.95 * len(urut))
    return urut[peringkat - 1]


def main() -> None:
    total = sum(SEPTEMBER.values())
    print(f"total September = {total}")
    for nama, jumlah in SEPTEMBER.items():
        print(f"  {nama:16} {jumlah:5}  porsi {jumlah / total:.2f}")

    print()
    print("infrastruktur: 120 terlewat dari 800")
    print(f"  recall = {recall(120, 800):.2f}")
    print("  ambang minimum = 0.85")

    tiket, menit = menit_ragu(400, 0.20, 2)
    print()
    print("ragu: 20 persen dari 400 pengaduan, 2 menit tiap tiket")
    print(f"  tiket = {tiket}")
    print(f"  menit Dina = {menit}")

    print()
    print(f"p95 notebook Bima = {p95(NOTEBOOK_MS)} ms")
    print(f"p95 layanan dinas = {p95(LAYANAN_MS)} ms")
    print("ambang p95 = 300 ms, diukur pada layanan, bukan pada notebook")
    print("sosial: panggil recall(terlewat_yang_kamu_pilih, 480)")


if __name__ == "__main__":
    main()
