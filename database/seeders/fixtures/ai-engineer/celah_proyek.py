"""Celah proyek, diperiksa sebagai data, bukan sebagai kesan.

Serahan di bawah ini bukan paket siang. Ia bentuk serahan yang
belum bisa dioperasikan: metrik di slide, tidak ada layanan, tidak
ada rollback, GPU cloud, go-live dalam lima hari.
Jalankan: python3 celah_proyek.py
"""

from __future__ import annotations

SERAHAN = {
    "recall_infrastruktur": None,
    "layanan": None,
    "rollback_diuji": False,
    "pemilik_metrik": None,
    "gpu_cloud": True,
    "go_live_hari": 5,
}


def celah(serahan: dict[str, object]) -> list[str]:
    temu: list[str] = []
    if not serahan.get("recall_infrastruktur"):
        temu.append("celah: metrik yang dilaporkan bukan recall infrastruktur")
    if not serahan.get("layanan"):
        temu.append("celah: serahan tidak bisa dipasang di loket")
    if not serahan.get("rollback_diuji"):
        temu.append("celah: rollback belum diuji")
    if not serahan.get("pemilik_metrik"):
        temu.append("celah: tidak ada pemilik metrik")
    if serahan.get("gpu_cloud"):
        temu.append("celah: GPU cloud, teks keluar jaringan")
    if int(serahan.get("go_live_hari", 99)) < 10:
        temu.append("celah: jadwal melompati sepuluh hari mode bayangan")
    return temu


if __name__ == "__main__":
    for baris in celah(SERAHAN):
        print(baris)
