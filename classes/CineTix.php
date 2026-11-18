<?php
// classes/CineTix.php
// Sesuai Laporan PBO Tahap 1 CineTix - Controller & Service Utama
// Menangani integrasi objek, database, sesi, timeout, dan format

class CineTix {
    public const BATAS_BAYAR_MENIT = 15;

    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionDir = __DIR__ . '/../sessions';
            if (!is_dir($sessionDir)) {
                @mkdir($sessionDir, 0777, true);
            }
            if (is_writable($sessionDir)) {
                @session_save_path($sessionDir);
            }
            @session_start();
        }
        // Auto-cek pesanan kadaluarsa setiap kali request dijalankan
        self::cekWaktuHabis();
    }

    public static function id(string $prefix): string {
        return Database::nextId($prefix);
    }

    public static function rp(float $angka): string {
        return "Rp " . number_format($angka, 0, ',', '.');
    }

    /**
     * Membatalkan pesanan yang belum dibayar jika melewati batas waktu (15 menit)
     * dan otomatis melepaskan kursi yang ditahan
     */
    public static function cekWaktuHabis(): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id_pemesanan, waktu_pesan FROM pemesanan WHERE status = 'MENUNGGU_PEMBAYARAN'");
            $pesananList = $stmt->fetchAll();

            $now = time();
            foreach ($pesananList as $row) {
                $waktuPesan = strtotime($row['waktu_pesan']);
                if (($now - $waktuPesan) > (self::BATAS_BAYAR_MENIT * 60)) {
                    $ps = self::cariPesanan($row['id_pemesanan']);
                    if ($ps !== null) {
                        $ps->batalkan();
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore error if database is initializing
        }
    }

    public static function setFlash(string $pesan, string $tipe = 'info'): void {
        $_SESSION['flash_message'] = [
            'pesan' => $pesan,
            'tipe'  => $tipe
        ];
    }

    public static function getFlash(): ?array {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $flash;
        }
        return null;
    }

    // =================== PENGGUNA & AUTH ===================

    public static function getCurrentUser(): ?Pengguna {
        self::init();
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return self::getPenggunaById($_SESSION['user_id']);
    }

    public static function getPenggunaById(string $id): ?Pengguna {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM pengguna WHERE id_pengguna = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        return self::mapRowToPengguna($row);
    }

    public static function login(string $email, string $password): ?Pengguna {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM pengguna WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        if ($row && $row['password'] === $password) {
            $user = self::mapRowToPengguna($row);
            $_SESSION['user_id']   = $user->getIdPengguna();
            $_SESSION['user_role'] = $row['role'];
            $_SESSION['user_nama'] = $user->getNama();
            return $user;
        }
        return null;
    }

    public static function registerPelanggan(string $nama, string $email, string $password, string $noTelepon): ?Pelanggan {
        $db = Database::getConnection();
        $id = self::id("U");
        $stmt = $db->prepare("
            INSERT INTO pengguna (id_pengguna, role, nama, email, password, no_telepon, saldo)
            VALUES (:id, 'pelanggan', :nama, :email, :password, :telp, 200000)
        ");
        try {
            $stmt->execute([
                ':id' => $id,
                ':nama' => $nama,
                ':email' => $email,
                ':password' => $password,
                ':telp' => $noTelepon
            ]);
            return new Pelanggan($nama, $email, $password, $noTelepon, 200000, $id);
        } catch (Exception $e) {
            return null;
        }
    }

    private static function mapRowToPengguna(array $row): Pengguna {
        if ($row['role'] === 'admin') {
            return new Admin($row['nama'], $row['email'], $row['password'], $row['kode_admin'] ?? 'ADM01', $row['id_pengguna']);
        } elseif ($row['role'] === 'kasir') {
            return new Kasir($row['nama'], $row['email'], $row['password'], $row['id_shift'] ?? 'PAGI', $row['id_pengguna']);
        } else {
            return new Pelanggan($row['nama'], $row['email'], $row['password'], $row['no_telepon'] ?? '', (float)$row['saldo'], $row['id_pengguna']);
        }
    }

    public static function simpanSaldoPelanggan(string $idPengguna, float $saldo): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE pengguna SET saldo = :saldo WHERE id_pengguna = :id");
        $stmt->execute([':saldo' => $saldo, ':id' => $idPengguna]);
    }

    // =================== FILM ===================

    /**
     * @return Film[]
     */
    public static function getAllFilm(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM film ORDER BY judul ASC");
        $films = [];
        while ($row = $stmt->fetch()) {
            $films[] = new Film(
                $row['judul'],
                $row['genre'],
                (int)$row['durasi'],
                $row['rating_usia'],
                $row['id_film'],
                $row['poster'] ?? '',
                $row['sinopsis'] ?? ''
            );
        }
        return $films;
    }

    public static function getFilmById(string $id): ?Film {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM film WHERE id_film = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        return new Film(
            $row['judul'],
            $row['genre'],
            (int)$row['durasi'],
            $row['rating_usia'],
            $row['id_film'],
            $row['poster'] ?? '',
            $row['sinopsis'] ?? ''
        );
    }

    public static function tambahFilm(string $judul, string $genre, int $durasi, string $ratingUsia, string $poster = '', string $sinopsis = ''): Film {
        $db = Database::getConnection();
        $id = self::id("F");
        $film = new Film($judul, $genre, $durasi, $ratingUsia, $id, $poster, $sinopsis);

        $stmt = $db->prepare("
            INSERT INTO film (id_film, judul, genre, durasi, rating_usia, poster, sinopsis)
            VALUES (:id, :judul, :genre, :durasi, :rating, :poster, :sinopsis)
        ");
        $stmt->execute([
            ':id' => $id,
            ':judul' => $judul,
            ':genre' => $genre,
            ':durasi' => $durasi,
            ':rating' => $ratingUsia,
            ':poster' => $film->getPoster(),
            ':sinopsis' => $film->getSinopsis()
        ]);

        return $film;
    }

    public static function updateFilm(Film $film): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE film SET judul = :judul, genre = :genre, durasi = :durasi, rating_usia = :rating, poster = :poster, sinopsis = :sinopsis
            WHERE id_film = :id
        ");
        $stmt->execute([
            ':id' => $film->getIdFilm(),
            ':judul' => $film->getJudul(),
            ':genre' => $film->getGenre(),
            ':durasi' => $film->getDurasi(),
            ':rating' => $film->getRatingUsia(),
            ':poster' => $film->getPoster(),
            ':sinopsis' => $film->getSinopsis()
        ]);
    }

    public static function hapusFilm(string $idFilm): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM film WHERE id_film = :id");
        $stmt->execute([':id' => $idFilm]);
    }

    // =================== STUDIO ===================

    /**
     * @return Studio[]
     */
    public static function getAllStudio(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM studio ORDER BY id_studio ASC");
        $studios = [];
        while ($row = $stmt->fetch()) {
            if ($row['tipe'] === 'premium') {
                $studios[] = new StudioPremium($row['nama_studio'], (int)$row['kapasitas'], (float)$row['biaya_tambahan'], $row['id_studio']);
            } else {
                $studios[] = new StudioReguler($row['nama_studio'], (int)$row['kapasitas'], $row['id_studio']);
            }
        }
        return $studios;
    }

    public static function getStudioById(string $id): ?Studio {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM studio WHERE id_studio = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        if ($row['tipe'] === 'premium') {
            return new StudioPremium($row['nama_studio'], (int)$row['kapasitas'], (float)$row['biaya_tambahan'], $row['id_studio']);
        } else {
            return new StudioReguler($row['nama_studio'], (int)$row['kapasitas'], $row['id_studio']);
        }
    }

    // =================== JADWAL ===================

    /**
     * @return Jadwal[]
     */
    public static function getAllJadwal(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM jadwal ORDER BY tanggal ASC, jam_mulai ASC");
        $list = [];
        while ($row = $stmt->fetch()) {
            $film = self::getFilmById($row['id_film']);
            $studio = self::getStudioById($row['id_studio']);
            if ($film && $studio) {
                $list[] = new Jadwal($film, $studio, $row['tanggal'], $row['jam_mulai'], (float)$row['harga_dasar'], $row['id_jadwal']);
            }
        }
        return $list;
    }

    public static function getJadwalById(string $id): ?Jadwal {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM jadwal WHERE id_jadwal = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $film = self::getFilmById($row['id_film']);
        $studio = self::getStudioById($row['id_studio']);
        if (!$film || !$studio) return null;

        return new Jadwal($film, $studio, $row['tanggal'], $row['jam_mulai'], (float)$row['harga_dasar'], $row['id_jadwal']);
    }

    public static function getJadwalByFilm(string $idFilm): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM jadwal WHERE id_film = :film ORDER BY tanggal ASC, jam_mulai ASC");
        $stmt->execute([':film' => $idFilm]);
        $list = [];
        while ($row = $stmt->fetch()) {
            $film = self::getFilmById($row['id_film']);
            $studio = self::getStudioById($row['id_studio']);
            if ($film && $studio) {
                $list[] = new Jadwal($film, $studio, $row['tanggal'], $row['jam_mulai'], (float)$row['harga_dasar'], $row['id_jadwal']);
            }
        }
        return $list;
    }

    public static function tambahJadwal(Film $film, Studio $studio, string $tanggal, string $jamMulai, float $hargaDasar): Jadwal {
        $db = Database::getConnection();
        $id = self::id("J");
        $jadwal = new Jadwal($film, $studio, $tanggal, $jamMulai, $hargaDasar, $id);

        $stmt = $db->prepare("
            INSERT INTO jadwal (id_jadwal, id_film, id_studio, tanggal, jam_mulai, harga_dasar)
            VALUES (:id, :film, :studio, :tanggal, :jam, :harga)
        ");
        $stmt->execute([
            ':id' => $id,
            ':film' => $film->getIdFilm(),
            ':studio' => $studio->getIdStudio(),
            ':tanggal' => $tanggal,
            ':jam' => $jamMulai,
            ':harga' => $hargaDasar
        ]);

        return $jadwal;
    }

    public static function updateJadwal(Jadwal $jadwal): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE jadwal SET id_film = :film, id_studio = :studio, tanggal = :tanggal, jam_mulai = :jam, harga_dasar = :harga
            WHERE id_jadwal = :id
        ");
        $stmt->execute([
            ':id' => $jadwal->getIdJadwal(),
            ':film' => $jadwal->getFilm()->getIdFilm(),
            ':studio' => $jadwal->getStudio()->getIdStudio(),
            ':tanggal' => $jadwal->getTanggal(),
            ':jam' => $jadwal->getJamMulai(),
            ':harga' => $jadwal->getHargaDasar()
        ]);
    }

    public static function hapusJadwal(string $idJadwal): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM jadwal WHERE id_jadwal = :id");
        $stmt->execute([':id' => $idJadwal]);
    }

    // =================== KURSI STATUS PER JADWAL ===================

    /**
     * Mengambil daftar kursi studio beserta status terkininya untuk suatu jadwal
     * @return Kursi[]
     */
    public static function getKursiForJadwal(Jadwal $jadwal): array {
        $db = Database::getConnection();
        $studio = $jadwal->getStudio();
        $kursiList = $studio->getKursi(); // Kursi default dari komposisi studio

        // Ambil tiket dari pesanan yang tidak dibatalkan pada jadwal ini
        $stmt = $db->prepare("
            SELECT t.nomor_kursi, p.status as status_pesanan
            FROM tiket t
            JOIN pemesanan p ON t.id_pemesanan = p.id_pemesanan
            WHERE p.id_jadwal = :jadwal AND p.status != 'DIBATALKAN'
        ");
        $stmt->execute([':jadwal' => $jadwal->getIdJadwal()]);
        $rows = $stmt->fetchAll();

        $occupied = [];
        foreach ($rows as $r) {
            $occupied[strtoupper($r['nomor_kursi'])] = $r['status_pesanan'];
        }

        $result = [];
        foreach ($kursiList as $k) {
            $nomor = strtoupper($k->getNomorKursi());
            $kursiBaru = new Kursi($k->getNomorKursi(), $k->getBaris());

            if (isset($occupied[$nomor])) {
                if ($occupied[$nomor] === StatusPesanan::DIKONFIRMASI->value) {
                    $kursiBaru->setStatus(StatusKursi::TERPESAN);
                } elseif ($occupied[$nomor] === StatusPesanan::MENUNGGU_PEMBAYARAN->value) {
                    $kursiBaru->setStatus(StatusKursi::DITAHAN);
                }
            }
            $result[] = $kursiBaru;
        }

        return $result;
    }

    // =================== PEMESANAN & TIKET ===================

    public static function tambahPesanan(Pemesanan $ps): void {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            // 1. Insert pemesanan
            $stmt = $db->prepare("
                INSERT INTO pemesanan (id_pemesanan, id_pelanggan, id_jadwal, waktu_pesan, status, total_harga)
                VALUES (:id, :pelanggan, :jadwal, :waktu, :status, :total)
            ");
            $stmt->execute([
                ':id' => $ps->getIdPemesanan(),
                ':pelanggan' => $ps->getPelanggan()->getIdPengguna(),
                ':jadwal' => $ps->getJadwal()->getIdJadwal(),
                ':waktu' => $ps->getWaktuPesan(),
                ':status' => $ps->getStatus()->value,
                ':total' => $ps->getTotalHarga()
            ]);

            // 2. Insert tiket
            $stmtTiket = $db->prepare("
                INSERT INTO tiket (id_pemesanan, nomor_kursi, baris, kode_tiket, status_pakai)
                VALUES (:pesanan, :kursi, :baris, :kode, :pakai)
            ");
            foreach ($ps->getTiket() as $t) {
                $stmtTiket->execute([
                    ':pesanan' => $ps->getIdPemesanan(),
                    ':kursi' => $t->getKursi()->getNomorKursi(),
                    ':baris' => $t->getKursi()->getBaris(),
                    ':kode' => $t->getKodeTiket(),
                    ':pakai' => $t->isStatusPakai() ? 1 : 0
                ]);
            }

            // 3. Insert pembayaran
            $bayar = $ps->getPembayaran();
            if ($bayar !== null) {
                $metode = 'tunai';
                $provider = null;
                $noAkun = null;
                $bank = null;
                $va = null;
                $uangDiterima = 0;

                if ($bayar instanceof PembayaranEWallet) {
                    $metode = 'ewallet';
                    $provider = $bayar->getProviderWallet();
                    $noAkun = $bayar->getNoAkun();
                } elseif ($bayar instanceof PembayaranTransfer) {
                    $metode = 'transfer';
                    $bank = $bayar->getNamaBank();
                    $va = $bayar->getNoVirtualAccount();
                } elseif ($bayar instanceof PembayaranTunai) {
                    $uangDiterima = $bayar->getUangDiterima();
                }

                $stmtBayar = $db->prepare("
                    INSERT INTO pembayaran (id_pembayaran, id_pemesanan, metode, status, jumlah, waktu_bayar, uang_diterima, provider_wallet, no_akun, nama_bank, no_va)
                    VALUES (:id, :pesanan, :metode, :status, :jumlah, :waktu, :uang, :provider, :akun, :bank, :va)
                ");
                $stmtBayar->execute([
                    ':id' => $bayar->getIdPembayaran(),
                    ':pesanan' => $ps->getIdPemesanan(),
                    ':metode' => $metode,
                    ':status' => $bayar->getStatus(),
                    ':jumlah' => $bayar->getJumlah(),
                    ':waktu' => $bayar->getWaktuBayar(),
                    ':uang' => $uangDiterima,
                    ':provider' => $provider,
                    ':akun' => $noAkun,
                    ':bank' => $bank,
                    ':va' => $va
                ]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function updatePesanan(Pemesanan $ps): void {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE pemesanan SET status = :status, total_harga = :total WHERE id_pemesanan = :id");
            $stmt->execute([
                ':status' => $ps->getStatus()->value,
                ':total' => $ps->getTotalHarga(),
                ':id' => $ps->getIdPemesanan()
            ]);

            // Update tiket
            $stmtTiket = $db->prepare("
                UPDATE tiket SET kode_tiket = :kode, status_pakai = :pakai
                WHERE id_pemesanan = :pesanan AND nomor_kursi = :kursi
            ");
            foreach ($ps->getTiket() as $t) {
                $stmtTiket->execute([
                    ':kode' => $t->getKodeTiket(),
                    ':pakai' => $t->isStatusPakai() ? 1 : 0,
                    ':pesanan' => $ps->getIdPemesanan(),
                    ':kursi' => $t->getKursi()->getNomorKursi()
                ]);
            }

            // Update pembayaran
            $bayar = $ps->getPembayaran();
            if ($bayar !== null) {
                $uangDiterima = ($bayar instanceof PembayaranTunai) ? $bayar->getUangDiterima() : 0;
                $stmtBayar = $db->prepare("
                    UPDATE pembayaran SET status = :status, waktu_bayar = :waktu, uang_diterima = :uang
                    WHERE id_pembayaran = :id
                ");
                $stmtBayar->execute([
                    ':status' => $bayar->getStatus(),
                    ':waktu' => $bayar->getWaktuBayar(),
                    ':uang' => $uangDiterima,
                    ':id' => $bayar->getIdPembayaran()
                ]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function cariPesanan(string $id): ?Pemesanan {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM pemesanan WHERE UPPER(id_pemesanan) = UPPER(:id)");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        return self::mapRowToPemesanan($row);
    }

    public static function cariTiket(string $kode): ?Tiket {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM tiket WHERE UPPER(kode_tiket) = UPPER(:kode)");
        $stmt->execute([':kode' => $kode]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $ps = self::cariPesanan($row['id_pemesanan']);
        $kursi = new Kursi($row['nomor_kursi'], $row['baris']);
        $tiket = new Tiket($ps, $kursi, $row['kode_tiket'], (bool)$row['status_pakai']);
        return $tiket;
    }

    public static function updateTiketStatusPakai(string $kodeTiket, bool $statusPakai): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE tiket SET status_pakai = :p WHERE UPPER(kode_tiket) = UPPER(:kode)");
        $stmt->execute([':p' => $statusPakai ? 1 : 0, ':kode' => $kodeTiket]);
    }

    public static function getRiwayatPelanggan(string $idPelanggan): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM pemesanan WHERE id_pelanggan = :p ORDER BY waktu_pesan DESC");
        $stmt->execute([':p' => $idPelanggan]);
        $list = [];
        while ($row = $stmt->fetch()) {
            $ps = self::mapRowToPemesanan($row);
            if ($ps) $list[] = $ps;
        }
        return $list;
    }

    public static function getAllPesanan(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM pemesanan ORDER BY waktu_pesan DESC");
        $list = [];
        while ($row = $stmt->fetch()) {
            $ps = self::mapRowToPemesanan($row);
            if ($ps) $list[] = $ps;
        }
        return $list;
    }

    private static function mapRowToPemesanan(array $row): ?Pemesanan {
        $pelanggan = self::getPenggunaById($row['id_pelanggan']);
        $jadwal    = self::getJadwalById($row['id_jadwal']);

        if (!$pelanggan instanceof Pelanggan || !$jadwal) return null;

        $status = StatusPesanan::from($row['status']);
        $ps = new Pemesanan($pelanggan, $jadwal, $row['id_pemesanan'], $row['waktu_pesan'], $status, (float)$row['total_harga']);

        // Ambil tiket
        $db = Database::getConnection();
        $stmtTiket = $db->prepare("SELECT * FROM tiket WHERE id_pemesanan = :id");
        $stmtTiket->execute([':id' => $row['id_pemesanan']]);
        $tiketList = [];
        while ($tRow = $stmtTiket->fetch()) {
            $kursi = new Kursi($tRow['nomor_kursi'], $tRow['baris']);
            if ($status === StatusPesanan::DIKONFIRMASI) {
                $kursi->setStatus(StatusKursi::TERPESAN);
            } elseif ($status === StatusPesanan::MENUNGGU_PEMBAYARAN) {
                $kursi->setStatus(StatusKursi::DITAHAN);
            }
            $tiket = new Tiket($ps, $kursi, $tRow['kode_tiket'], (bool)$tRow['status_pakai']);
            $tiketList[] = $tiket;
        }
        $ps->setTiket($tiketList);

        // Ambil pembayaran
        $stmtBayar = $db->prepare("SELECT * FROM pembayaran WHERE id_pemesanan = :id");
        $stmtBayar->execute([':id' => $row['id_pemesanan']]);
        $bRow = $stmtBayar->fetch();
        if ($bRow) {
            if ($bRow['metode'] === 'ewallet') {
                $bayar = new PembayaranEWallet($ps, $bRow['provider_wallet'] ?? 'DANA', $bRow['no_akun'] ?? '', $bRow['id_pembayaran'], $bRow['status'], $bRow['waktu_bayar']);
            } elseif ($bRow['metode'] === 'transfer') {
                $bayar = new PembayaranTransfer($ps, $bRow['nama_bank'] ?? 'BCA', $bRow['no_va'] ?? '', $bRow['id_pembayaran'], $bRow['status'], $bRow['waktu_bayar']);
            } else {
                $bayar = new PembayaranTunai($ps, $bRow['id_pembayaran'], $bRow['status'], (float)$bRow['uang_diterima'], $bRow['waktu_bayar']);
            }
            $ps->setPembayaran($bayar);
        }

        return $ps;
    }
}
