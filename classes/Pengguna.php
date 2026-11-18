<?php
// classes/Pengguna.php
// Sesuai Laporan PBO Tahap 1 CineTix
// Hierarki Pengguna (Abstract Class) -> Pelanggan, Admin, Kasir

abstract class Pengguna {
    protected string $idPengguna;
    protected string $nama;
    protected string $email;
    protected string $password;

    public function __construct(string $nama, string $email, string $password, ?string $idPengguna = null) {
        $this->idPengguna = $idPengguna ?? CineTix::id("U");
        $this->nama = $nama;
        $this->email = $email;
        $this->password = $password;
    }

    public function login(string $email, string $password): bool {
        return $this->email === $email && $this->password === $password;
    }

    public function logout(): void {
        // Output logout message (disimpan di session flash untuk web)
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['flash_info'] = "Logout. Sampai jumpa, " . $this->nama . "!";
        }
    }

    public function getIdPengguna(): string {
        return $this->idPengguna;
    }

    public function getNama(): string {
        return $this->nama;
    }

    public function setNama(string $nama): void {
        $this->nama = $nama;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }

    // Abstract method: wajib diimplementasikan oleh subclass (Polimorfisme)
    abstract public function tampilkanMenu(): string;
}

class Pelanggan extends Pengguna {
    private string $noTelepon;
    private float $saldo; // saldo e-wallet awal (simulasi Rp 200.000)

    public function __construct(string $nama, string $email, string $password, string $noTelepon, float $saldo = 200000, ?string $idPengguna = null) {
        parent::__construct($nama, $email, $password, $idPengguna);
        $this->noTelepon = $noTelepon;
        $this->saldo = $saldo;
    }

    public function getSaldo(): float {
        return $this->saldo;
    }

    public function setSaldo(float $saldo): void {
        $this->saldo = $saldo;
        CineTix::simpanSaldoPelanggan($this->idPengguna, $saldo);
    }

    public function getNoTelepon(): string {
        return $this->noTelepon;
    }

    public function setNoTelepon(string $noTelepon): void {
        $this->noTelepon = $noTelepon;
    }

    public function tampilkanMenu(): string {
        return "index.php"; // Di web mengarah ke halaman utama pemesanan tiket
    }

    /**
     * Membuat pesanan tiket dan menentukan metode pembayaran
     */
    public function pesanTiket(Jadwal $jadwal, array $kursi, string $metodeBayar, array $dataBayar = []): Pemesanan {
        $ps = new Pemesanan($this, $jadwal);
        foreach ($kursi as $k) {
            $ps->tambahKursi($k);
        }
        $ps->hitungTotal();

        // Inisialisasi Polimorfisme Pembayaran
        if ($metodeBayar === 'ewallet') {
            $provider = $dataBayar['provider'] ?? 'DANA';
            $noAkun   = $dataBayar['no_akun'] ?? $this->noTelepon;
            $bayar    = new PembayaranEWallet($ps, $provider, $noAkun);
        } elseif ($metodeBayar === 'transfer') {
            $bank  = $dataBayar['bank'] ?? 'BCA';
            $bayar = new PembayaranTransfer($ps, $bank);
        } else {
            // Default tunai
            $bayar = new PembayaranTunai($ps);
        }

        $ps->setPembayaran($bayar);
        CineTix::tambahPesanan($ps);

        if ($bayar instanceof PembayaranTunai) {
            // Bayar tunai di kasir (menunggu pembayaran)
            CineTix::setFlash("Pesanan {$ps->getIdPemesanan()} dibuat! Silakan bayar tunai di kasir (batas " . CineTix::BATAS_BAYAR_MENIT . " menit).", "warning");
        } elseif ($bayar->proses()) {
            // Polimorfisme: proses() sesuai jenis pembayaran
            $ps->konfirmasi();
            CineTix::setFlash("Pembayaran berhasil! Tiket Anda telah diterbitkan.", "success");
        } else {
            $ps->batalkan();
            CineTix::setFlash("Pembayaran gagal! Saldo tidak mencukupi atau transaksi dibatalkan.", "danger");
        }

        return $ps;
    }

    public function batalkanPesanan(string $id): bool {
        $ps = CineTix::cariPesanan($id);
        if ($ps !== null && $ps->getPelanggan()->getIdPengguna() === $this->idPengguna && $ps->getStatus() === StatusPesanan::MENUNGGU_PEMBAYARAN) {
            $ps->batalkan();
            CineTix::updatePesanan($ps);
            return true;
        }
        return false;
    }

    public function lihatRiwayat(): array {
        return CineTix::getRiwayatPelanggan($this->idPengguna);
    }
}

class Admin extends Pengguna {
    private string $kodeAdmin;

    public function __construct(string $nama, string $email, string $password, string $kodeAdmin, ?string $idPengguna = null) {
        parent::__construct($nama, $email, $password, $idPengguna);
        $this->kodeAdmin = $kodeAdmin;
    }

    public function getKodeAdmin(): string {
        return $this->kodeAdmin;
    }

    public function setKodeAdmin(string $kodeAdmin): void {
        $this->kodeAdmin = $kodeAdmin;
    }

    public function tampilkanMenu(): string {
        return "admin/index.php"; // Dashboard admin
    }

    public function kelolaFilm(): array {
        return CineTix::getAllFilm();
    }

    public function kelolaJadwal(): array {
        return CineTix::getAllJadwal();
    }

    public function lihatLaporan(): array {
        $pesananList = CineTix::getAllPesanan();
        $totalPenjualan = 0;
        $laporan = [];

        foreach ($pesananList as $ps) {
            if ($ps->getStatus() === StatusPesanan::DIKONFIRMASI) {
                $laporan[] = $ps;
                $totalPenjualan += $ps->getTotalHarga();
            }
        }

        return [
            'daftar' => $laporan,
            'total'  => $totalPenjualan
        ];
    }
}

class Kasir extends Pengguna {
    private string $idShift;

    public function __construct(string $nama, string $email, string $password, string $idShift, ?string $idPengguna = null) {
        parent::__construct($nama, $email, $password, $idPengguna);
        $this->idShift = $idShift;
    }

    public function getIdShift(): string {
        return $this->idShift;
    }

    public function setIdShift(string $idShift): void {
        $this->idShift = $idShift;
    }

    public function tampilkanMenu(): string {
        return "kasir/index.php"; // Dashboard kasir
    }

    public function verifikasiPembayaran(string $idPemesanan, float $uangDiterima): array {
        $ps = CineTix::cariPesanan($idPemesanan);
        if ($ps === null || $ps->getStatus() !== StatusPesanan::MENUNGGU_PEMBAYARAN) {
            return ['sukses' => false, 'pesan' => 'Pesanan tidak ditemukan atau bukan status menunggu pembayaran.'];
        }

        $pembayaran = $ps->getPembayaran();
        if (!$pembayaran instanceof PembayaranTunai) {
            $pembayaran = new PembayaranTunai($ps);
            $ps->setPembayaran($pembayaran);
        }

        $pembayaran->setUangDiterima($uangDiterima);
        if (!$pembayaran->proses()) {
            return ['sukses' => false, 'pesan' => 'Uang yang diterima kurang! Total tagihan: ' . CineTix::rp($ps->getTotalHarga())];
        }

        $ps->konfirmasi();
        CineTix::updatePesanan($ps);

        return [
            'sukses'    => true,
            'kembalian' => $pembayaran->hitungKembalian(),
            'pesanan'   => $ps,
            'pesan'     => 'Pembayaran terverifikasi! Kembalian: ' . CineTix::rp($pembayaran->hitungKembalian())
        ];
    }

    public function cetakTiket(Tiket $tiket): void {
        $tiket->cetak();
    }
}
