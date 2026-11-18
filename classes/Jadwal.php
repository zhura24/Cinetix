<?php
// classes/Jadwal.php
// Sesuai Laporan PBO Tahap 1 CineTix - Class Jadwal
// Asosiasi: Film 1 - * Jadwal, Studio 1 - * Jadwal

class Jadwal {
    private string $idJadwal;
    private string $tanggal;    // YYYY-MM-DD
    private string $jamMulai;   // HH:MM
    private float $hargaDasar;
    private Film $film;         // Film 1 - * Jadwal (ditayangkan pada)
    private Studio $studio;     // Studio 1 - * Jadwal (dipakai untuk)

    public function __construct(
        Film $film,
        Studio $studio,
        string $tanggal,
        string $jamMulai,
        float $hargaDasar,
        ?string $idJadwal = null
    ) {
        $this->idJadwal   = $idJadwal ?? CineTix::id("J");
        $this->film       = $film;
        $this->studio     = $studio;
        $this->tanggal    = $tanggal;
        $this->jamMulai   = $jamMulai;
        $this->hargaDasar = $hargaDasar;
    }

    public function cekKetersediaan(): bool {
        $kursiList = $this->getKursiJadwal();
        foreach ($kursiList as $k) {
            if ($k->isTersedia()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Mendapatkan daftar kursi dengan status aktual sesuai pesanan untuk jadwal ini
     * @return Kursi[]
     */
    public function getKursiJadwal(): array {
        return CineTix::getKursiForJadwal($this);
    }

    public function hitungHargaTiket(): float {
        // Polimorfisme: Memanggil hitungHarga() sesuai subclass Studio (Reguler atau Premium)
        return $this->studio->hitungHarga($this->hargaDasar);
    }

    public function getIdJadwal(): string {
        return $this->idJadwal;
    }

    public function getTanggal(): string {
        return $this->tanggal;
    }

    public function setTanggal(string $tanggal): void {
        $this->tanggal = $tanggal;
    }

    public function getJamMulai(): string {
        return $this->jamMulai;
    }

    public function setJamMulai(string $jamMulai): void {
        $this->jamMulai = $jamMulai;
    }

    public function getHargaDasar(): float {
        return $this->hargaDasar;
    }

    public function setHargaDasar(float $hargaDasar): void {
        $this->hargaDasar = $hargaDasar;
    }

    public function getFilm(): Film {
        return $this->film;
    }

    public function setFilm(Film $film): void {
        $this->film = $film;
    }

    public function getStudio(): Studio {
        return $this->studio;
    }

    public function setStudio(Studio $studio): void {
        $this->studio = $studio;
    }
}
