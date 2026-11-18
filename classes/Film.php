<?php
// classes/Film.php
// Sesuai Laporan PBO Tahap 1 CineTix - Class Film

class Film {
    private string $idFilm;
    private string $judul;
    private string $genre;
    private int $durasi;
    private string $ratingUsia;
    private string $poster;
    private string $sinopsis;

    public function __construct(
        string $judul,
        string $genre,
        int $durasi,
        string $ratingUsia,
        ?string $idFilm = null,
        string $poster = '',
        string $sinopsis = ''
    ) {
        $this->idFilm     = $idFilm ?? CineTix::id("F");
        $this->judul      = $judul;
        $this->genre      = $genre;
        $this->durasi     = $durasi;
        $this->ratingUsia = $ratingUsia;
        $this->poster     = $poster ?: 'assets/img/default-movie.jpg';
        $this->sinopsis   = $sinopsis ?: "Sinopsis untuk film {$this->judul}. Saksikan keseruan film ini hanya di CineTix!";
    }

    public function getIdFilm(): string {
        return $this->idFilm;
    }

    public function getJudul(): string {
        return $this->judul;
    }

    public function setJudul(string $judul): void {
        $this->judul = $judul;
    }

    public function getGenre(): string {
        return $this->genre;
    }

    public function setGenre(string $genre): void {
        $this->genre = $genre;
    }

    public function getDurasi(): int {
        return $this->durasi;
    }

    public function setDurasi(int $durasi): void {
        $this->durasi = $durasi;
    }

    public function getRatingUsia(): string {
        return $this->ratingUsia;
    }

    public function setRatingUsia(string $ratingUsia): void {
        $this->ratingUsia = $ratingUsia;
    }

    public function getPoster(): string {
        return $this->poster;
    }

    public function setPoster(string $poster): void {
        $this->poster = $poster;
    }

    public function getSinopsis(): string {
        return $this->sinopsis;
    }

    public function setSinopsis(string $sinopsis): void {
        $this->sinopsis = $sinopsis;
    }
}
