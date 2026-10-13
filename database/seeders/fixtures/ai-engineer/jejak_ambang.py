"""Status bukti tidak sama dengan lulus ambang.

Lolos di sini berarti angkanya boleh dipakai. Belum sah berarti
angkanya ada tetapi datanya bocor. Celah berarti bukti tidak ada.
Recall 1.00 pada data bocor tetap tidak boleh ditulis sebagai kelulusan.

Angka di bawah ini hasil evaluasi_latihan.py, bukan paket siang.
Jalankan: python3 jejak_ambang.py
"""

from __future__ import annotations

AMBANG = 0.85


def status_bukti(ada: bool, jujur: bool) -> str:
    if not ada:
        return "celah"
    if not jujur:
        return "belum sah"
    return "lolos"


def lulus_ambang(recall: float | None, jujur: bool) -> bool:
    if recall is None or not jujur:
        return False
    return recall >= AMBANG


def baris(nama: str, recall: float | None, ada: bool, jujur: bool) -> None:
    status = status_bukti(ada, jujur)
    lulus = lulus_ambang(recall, jujur)
    angka = "tidak ada" if recall is None else f"{recall:.2f}"
    print(f"{nama}: bukti={status} lulus_ambang={lulus} recall={angka}")


if __name__ == "__main__":
    baris("latihan jujur", 0.50, ada=True, jujur=True)
    baris("kalimat setelah ditangani", 1.00, ada=True, jujur=False)
    baris("tanpa bukti latensi", None, ada=False, jujur=False)
