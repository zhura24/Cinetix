<?php
// classes/Studio.php
// Sesuai Laporan PBO Tahap 1 CineTix
// Hierarki Studio (Abstract Class) -> StudioReguler, StudioPremium
// Komposisi: Studio memiliki 1..* Kursi

abstract class Studio {
    protected string $idStudio;
    protected string $namaStudio;
    protected int $kapasitas;
    /** @var Kursi[] Komposisi: Studio memiliki 1..* Kursi */
    protected array $kursi = [];

    public function __construct(string $namaStudio, int $kapasitas, ?string $idStudio = null) {
        $this->idStudio = $idStudio ?? CineTix::id("S");
        $this->namaStudio = $namaStudio;
        $this->kapasitas = $kapasitas;

        // 5 kursi per baris: A1..A5, B1..B5, dst.
        for ($i = 0; $i < $kapasitas; $i++) {
            $barisHuruf = chr(ord('A') + intdiv($i, 5));
            $nomorKolom = ($i % 5) + 1;
            $kodeKursi  = $barisHuruf . $nomorKolom;
            $this->kursi[] = new Kursi($kodeKursi, $barisHuruf);
        }
    }

    public function getIdStudio(): string {
        return $this->idStudio;
    }

    public function getNamaStudio(): string {
        return $this->namaStudio;
    }

    public function getKapasitas(): int {
        return $this->kapasitas;
    }

    /**
     * @return Kursi[]
     */
    public function getKursi(): array {
        return $this->kursi;
    }

    public function cariKursi(string $nomorKursi): ?Kursi {
        foreach ($this->kursi as $k) {
            if (strtoupper($k->getNomorKursi()) === strtoupper($nomorKursi)) {
                return $k;
            }
        }
        return null;
    }

    // Polimorfisme: Setiap jenis studio punya perhitungan harga berbeda
    abstract public function hitungHarga(float $hargaDasar): float;
}

class StudioReguler extends Studio {
    public function __construct(string $namaStudio, int $kapasitas, ?string $idStudio = null) {
        parent::__construct($namaStudio, $kapasitas, $idStudio);
    }

    // Polimorfisme: Studio reguler memakai harga dasar tanpa biaya tambahan
    public function hitungHarga(float $hargaDasar): float {
        return $hargaDasar;
    }
}

class StudioPremium extends Studio {
    private float $biayaTambahan;

    public function __construct(string $namaStudio, int $kapasitas, float $biayaTambahan = 20000, ?string $idStudio = null) {
        parent::__construct($namaStudio, $kapasitas, $idStudio);
        $this->biayaTambahan = $biayaTambahan;
    }

    public function getBiayaTambahan(): float {
        return $this->biayaTambahan;
    }

    public function setBiayaTambahan(float $biayaTambahan): void {
        $this->biayaTambahan = $biayaTambahan;
    }

    // Polimorfisme: Studio premium menambahkan biaya fasilitas premium ke harga dasar
    public function hitungHarga(float $hargaDasar): float {
        return $hargaDasar + $this->biayaTambahan;
    }
}

class Kursi {
    private string $nomorKursi;
    private string $baris;
    private StatusKursi $status;

    public function __construct(string $nomorKursi, string $baris, StatusKursi $status = StatusKursi::TERSEDIA) {
        $this->nomorKursi = $nomorKursi;
        $this->baris      = $baris;
        $this->status     = $status;
    }

    public function isTersedia(): bool {
        return $this->status === StatusKursi::TERSEDIA;
    }

    public function tahan(): void {
        $this->status = StatusKursi::DITAHAN;
    }

    public function pesan(): void {
        $this->status = StatusKursi::TERPESAN;
    }

    public function lepas(): void {
        $this->status = StatusKursi::TERSEDIA;
    }

    public function getNomorKursi(): string {
        return $this->nomorKursi;
    }

    public function getBaris(): string {
        return $this->baris;
    }

    public function getStatus(): StatusKursi {
        return $this->status;
    }

    public function setStatus(StatusKursi $status): void {
        $this->status = $status;
    }
}
