<?php
// classes/Pemesanan.php
// Sesuai Laporan PBO Tahap 1 CineTix - Class Pemesanan
// Relasi:
// - Pelanggan 1 - * Pemesanan (melakukan)
// - Jadwal 1 - * Pemesanan (dipesan lewat)
// - Pemesanan 1 - 1 Pembayaran (dibayar dengan)
// - Komposisi: Pemesanan menghasilkan 1..* Tiket

class Pemesanan {
    private string $idPemesanan;
    private string $waktuPesan; // Format datetime Y-m-d H:i:s
    private StatusPesanan $status;
    private float $totalHarga = 0;
    private Pelanggan $pelanggan;
    private Jadwal $jadwal;
    private ?Pembayaran $pembayaran = null;
    /** @var Tiket[] Komposisi */
    private array $tiket = [];

    public function __construct(
        Pelanggan $pelanggan,
        Jadwal $jadwal,
        ?string $idPemesanan = null,
        ?string $waktuPesan = null,
        StatusPesanan $status = StatusPesanan::MENUNGGU_PEMBAYARAN,
        float $totalHarga = 0
    ) {
        $this->idPemesanan = $idPemesanan ?? CineTix::id("P");
        $this->waktuPesan  = $waktuPesan ?? date('Y-m-d H:i:s');
        $this->status      = $status;
        $this->pelanggan   = $pelanggan;
        $this->jadwal      = $jadwal;
        $this->totalHarga  = $totalHarga;
    }

    /**
     * Menghitung total harga tiket berdasarkan jenis studio (Polimorfisme)
     */
    public function hitungTotal(): float {
        $hargaPerTiket = $this->jadwal->getStudio()->hitungHarga($this->jadwal->getHargaDasar());
        $this->totalHarga = count($this->tiket) * $hargaPerTiket;
        return $this->totalHarga;
    }

    /**
     * Tiap kursi menjadi satu Tiket (Tiket * - 1 Kursi)
     * Kursi ditahan sementara saat pemesanan dibuat
     */
    public function tambahKursi(Kursi $kursi): void {
        $kursi->tahan();
        $this->tiket[] = new Tiket($this, $kursi);
    }

    /**
     * Konfirmasi pesanan setelah pembayaran berhasil
     * Mengubah status kursi menjadi terpesan dan menghasilkan kode tiket unik
     */
    public function konfirmasi(): void {
        $this->status = StatusPesanan::DIKONFIRMASI;
        foreach ($this->tiket as $t) {
            $t->getKursi()->pesan();
            if ($t->getKodeTiket() === '-' || empty($t->getKodeTiket())) {
                $t->generateKode();
            }
        }
        CineTix::updatePesanan($this);
    }

    /**
     * Membatalkan pesanan dan melepaskan kembali kursi yang ditahan
     */
    public function batalkan(): void {
        $this->status = StatusPesanan::DIBATALKAN;
        foreach ($this->tiket as $t) {
            $t->getKursi()->lepas();
        }
        CineTix::updatePesanan($this);
    }

    public function getIdPemesanan(): string {
        return $this->idPemesanan;
    }

    public function getWaktuPesan(): string {
        return $this->waktuPesan;
    }

    public function getStatus(): StatusPesanan {
        return $this->status;
    }

    public function setStatus(StatusPesanan $status): void {
        $this->status = $status;
    }

    public function getTotalHarga(): float {
        return $this->totalHarga;
    }

    public function setTotalHarga(float $total): void {
        $this->totalHarga = $total;
    }

    public function getPelanggan(): Pelanggan {
        return $this->pelanggan;
    }

    public function getJadwal(): Jadwal {
        return $this->jadwal;
    }

    public function getPembayaran(): ?Pembayaran {
        return $this->pembayaran;
    }

    public function setPembayaran(Pembayaran $pembayaran): void {
        $this->pembayaran = $pembayaran;
    }

    /**
     * @return Tiket[]
     */
    public function getTiket(): array {
        return $this->tiket;
    }

    public function setTiket(array $tiket): void {
        $this->tiket = $tiket;
    }

    /**
     * Cek apakah waktu pembayaran telah kadaluarsa (15 menit)
     */
    public function isExpired(): bool {
        if ($this->status !== StatusPesanan::MENUNGGU_PEMBAYARAN) {
            return false;
        }
        $waktuPesanUnix = strtotime($this->waktuPesan);
        $batasWaktu     = $waktuPesanUnix + (CineTix::BATAS_BAYAR_MENIT * 60);
        return time() > $batasWaktu;
    }

    public function getSisaWaktuDetik(): int {
        if ($this->status !== StatusPesanan::MENUNGGU_PEMBAYARAN) {
            return 0;
        }
        $waktuPesanUnix = strtotime($this->waktuPesan);
        $batasWaktu     = $waktuPesanUnix + (CineTix::BATAS_BAYAR_MENIT * 60);
        return max(0, $batasWaktu - time());
    }
}
