<?php
// classes/Tiket.php
// Sesuai Laporan PBO Tahap 1 CineTix - Class Tiket
// Komposisi: Pemesanan menghasilkan 1..* Tiket
// Relasi: Tiket * - 1 Kursi (menempati)

class Tiket {
    private string $kodeTiket = "-";
    private bool $statusPakai = false;
    private ?Pemesanan $pemesanan = null; // Komposisi dari Pemesanan
    private Kursi $kursi;                 // Tiket * - 1 Kursi

    public function __construct(?Pemesanan $pemesanan, Kursi $kursi, string $kodeTiket = "-", bool $statusPakai = false) {
        $this->pemesanan   = $pemesanan;
        $this->kursi       = $kursi;
        $this->kodeTiket   = $kodeTiket;
        $this->statusPakai = $statusPakai;
    }

    public function generateKode(): void {
        $randomHex = strtoupper(bin2hex(random_bytes(3))); // 6 karakter acak
        $this->kodeTiket = "TKT-" . $randomHex;
    }

    public function cetak(): void {
        if ($this->pemesanan === null) return;
        $j = $this->pemesanan->getJadwal();
        // Method cetak untuk representasi data tiket
    }

    /**
     * Memvalidasi tiket saat check-in di kasir/pintu masuk studio
     * Mengembalikan true jika tiket belum pernah dipakai dan berhasil divalidasi,
     * false jika tiket sudah pernah digunakan sebelumnya.
     */
    public function validasi(): bool {
        if ($this->statusPakai) {
            return false;
        }
        $this->statusPakai = true;
        CineTix::updateTiketStatusPakai($this->kodeTiket, true);
        return true;
    }

    public function getKodeTiket(): string {
        return $this->kodeTiket;
    }

    public function setKodeTiket(string $kode): void {
        $this->kodeTiket = $kode;
    }

    public function getKursi(): Kursi {
        return $this->kursi;
    }

    public function isStatusPakai(): bool {
        return $this->statusPakai;
    }

    public function setStatusPakai(bool $statusPakai): void {
        $this->statusPakai = $statusPakai;
    }

    public function getPemesanan(): ?Pemesanan {
        return $this->pemesanan;
    }

    public function setPemesanan(Pemesanan $pemesanan): void {
        $this->pemesanan = $pemesanan;
    }
}
