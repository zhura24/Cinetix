<?php
// classes/Pembayaran.php
// Sesuai Laporan PBO Tahap 1 CineTix
// Hierarki Pembayaran (Abstract Class) -> PembayaranTunai, PembayaranEWallet, PembayaranTransfer
// Polimorfisme: method proses() diimplementasikan berbeda untuk setiap jenis pembayaran

abstract class Pembayaran {
    protected string $idPembayaran;
    protected string $status;
    protected float $jumlah;
    protected ?string $waktuBayar = null;
    protected Pemesanan $pemesanan; // Relasi 1 - 1 Pemesanan

    public function __construct(Pemesanan $pemesanan, ?string $idPembayaran = null, string $status = "MENUNGGU", ?string $waktuBayar = null) {
        $this->idPembayaran = $idPembayaran ?? CineTix::id("BYR");
        $this->pemesanan    = $pemesanan;
        $this->jumlah       = $pemesanan->getTotalHarga();
        $this->status       = $status;
        $this->waktuBayar   = $waktuBayar;
    }

    // Polimorfisme: setiap kelas turunan wajib mengimplementasikan method proses()
    abstract public function proses(): bool;

    public function getIdPembayaran(): string {
        return $this->idPembayaran;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getJumlah(): float {
        return $this->jumlah;
    }

    public function setJumlah(float $jumlah): void {
        $this->jumlah = $jumlah;
    }

    public function getPemesanan(): Pemesanan {
        return $this->pemesanan;
    }

    public function getWaktuBayar(): ?string {
        return $this->waktuBayar;
    }

    protected function setStatus(string $status): void {
        $this->status = $status;
        if ($status === "BERHASIL") {
            $this->waktuBayar = date('Y-m-d H:i:s');
        }
    }
}

class PembayaranTunai extends Pembayaran {
    private float $uangDiterima = 0;

    public function __construct(Pemesanan $pemesanan, ?string $idPembayaran = null, string $status = "MENUNGGU", float $uangDiterima = 0, ?string $waktuBayar = null) {
        parent::__construct($pemesanan, $idPembayaran, $status, $waktuBayar);
        $this->uangDiterima = $uangDiterima;
    }

    // Polimorfisme: validasi apakah uang tunai yang diserahkan ke kasir mencukupi
    public function proses(): bool {
        $berhasil = ($this->uangDiterima >= $this->getJumlah());
        $this->setStatus($berhasil ? "BERHASIL" : "GAGAL");
        return $berhasil;
    }

    public function hitungKembalian(): float {
        return max(0, $this->uangDiterima - $this->getJumlah());
    }

    public function getUangDiterima(): float {
        return $this->uangDiterima;
    }

    public function setUangDiterima(float $uangDiterima): void {
        $this->uangDiterima = $uangDiterima;
    }
}

class PembayaranEWallet extends Pembayaran {
    private string $providerWallet;
    private string $noAkun;

    public function __construct(Pemesanan $pemesanan, string $providerWallet, string $noAkun, ?string $idPembayaran = null, string $status = "MENUNGGU", ?string $waktuBayar = null) {
        parent::__construct($pemesanan, $idPembayaran, $status, $waktuBayar);
        $this->providerWallet = $providerWallet;
        $this->noAkun         = $noAkun;
    }

    // Polimorfisme: proses potong saldo e-wallet pelanggan
    public function proses(): bool {
        $pelanggan = $this->getPemesanan()->getPelanggan();
        $berhasil  = ($pelanggan->getSaldo() >= $this->getJumlah());

        if ($berhasil) {
            $saldoBaru = $pelanggan->getSaldo() - $this->getJumlah();
            $pelanggan->setSaldo($saldoBaru);
            $this->setStatus("BERHASIL");
        } else {
            $this->setStatus("GAGAL");
        }

        return $berhasil;
    }

    public function getProviderWallet(): string {
        return $this->providerWallet;
    }

    public function getNoAkun(): string {
        return $this->noAkun;
    }
}

class PembayaranTransfer extends Pembayaran {
    private string $namaBank;
    private string $noVirtualAccount;

    public function __construct(Pemesanan $pemesanan, string $namaBank, ?string $noVirtualAccount = null, ?string $idPembayaran = null, string $status = "MENUNGGU", ?string $waktuBayar = null) {
        parent::__construct($pemesanan, $idPembayaran, $status, $waktuBayar);
        $this->namaBank = $namaBank;
        $this->noVirtualAccount = $noVirtualAccount ?? ("8808" . rand(10000000, 99999999));
    }

    // Polimorfisme: proses pengecekan transfer mutasi bank
    public function proses(): bool {
        $berhasil = $this->cekMutasi();
        $this->setStatus($berhasil ? "BERHASIL" : "GAGAL");
        return $berhasil;
    }

    public function cekMutasi(): bool {
        // Simulasi: dana transfer dianggap sudah masuk
        return true;
    }

    public function getNamaBank(): string {
        return $this->namaBank;
    }

    public function getNoVirtualAccount(): string {
        return $this->noVirtualAccount;
    }
}
