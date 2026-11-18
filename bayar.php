<?php
// bayar.php - Pemilihan Metode Pembayaran dan Proses Pembayaran
$pageTitle = 'Pembayaran Tiket - CineTix';
require_once __DIR__ . '/classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Pelanggan) {
    CineTix::setFlash("Silakan masuk sebagai Pelanggan terlebih dahulu.", "warning");
    header("Location: login.php");
    exit;
}

// 1. Tangani pembuatan pesanan baru dari pesan.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_pesanan') {
    $idJadwal = $_POST['id_jadwal'] ?? '';
    $kursiStr = trim($_POST['kursi'] ?? '');

    $jadwal = CineTix::getJadwalById($idJadwal);
    if (!$jadwal) {
        CineTix::setFlash("Jadwal tidak valid!", "danger");
        header("Location: index.php");
        exit;
    }

    $daftarNomorKursi = array_filter(explode(' ', $kursiStr));
    if (empty($daftarNomorKursi)) {
        CineTix::setFlash("Pilih minimal 1 kursi!", "warning");
        header("Location: pesan.php?jadwal=" . urlencode($idJadwal));
        exit;
    }

    // Ambil kursi yang tersedia
    $kursiObjek = [];
    $semuaKursi = $jadwal->getKursiJadwal();
    foreach ($daftarNomorKursi as $no) {
        foreach ($semuaKursi as $k) {
            if (strtoupper($k->getNomorKursi()) === strtoupper($no)) {
                if (!$k->isTersedia()) {
                    CineTix::setFlash("Kursi $no sudah tidak tersedia. Silakan pilih kursi lain.", "danger");
                    header("Location: pesan.php?jadwal=" . urlencode($idJadwal));
                    exit;
                }
                $kursiObjek[] = $k;
            }
        }
    }

    // Buat Pemesanan (PBO)
    $ps = new Pemesanan($user, $jadwal);
    foreach ($kursiObjek as $k) {
        $ps->tambahKursi($k); // Kursi ditahan (DITAHAN)
    }
    $ps->hitungTotal(); // Menggunakan Studio.hitungHarga()

    // Default pembayaran tunai sebelum dipilih
    $bayarAwal = new PembayaranTunai($ps);
    $ps->setPembayaran($bayarAwal);

    CineTix::tambahPesanan($ps);

    header("Location: bayar.php?id=" . urlencode($ps->getIdPemesanan()));
    exit;
}

// 2. Ambil data pesanan
$idPemesanan = $_GET['id'] ?? '';
$pesanan = CineTix::cariPesanan($idPemesanan);

if (!$pesanan || $pesanan->getPelanggan()->getIdPengguna() !== $user->getIdPengguna()) {
    CineTix::setFlash("Pesanan tidak ditemukan atau tidak dapat diakses.", "danger");
    header("Location: riwayat.php");
    exit;
}

// Cek apakah pesanan kadaluarsa
if ($pesanan->isExpired()) {
    $pesanan->batalkan();
    CineTix::setFlash("Batas waktu pembayaran untuk pesanan {$pesanan->getIdPemesanan()} telah habis. Pesanan otomatis dibatalkan.", "danger");
    header("Location: riwayat.php");
    exit;
}

// 3. Tangani Pembatalan Pesanan oleh Pelanggan
if (isset($_GET['batal']) && $_GET['batal'] === '1') {
    if ($user->batalkanPesanan($pesanan->getIdPemesanan())) {
        CineTix::setFlash("Pesanan {$pesanan->getIdPemesanan()} berhasil dibatalkan.", "info");
    } else {
        CineTix::setFlash("Gagal membatalkan pesanan.", "danger");
    }
    header("Location: riwayat.php");
    exit;
}

// 4. Tangani Proses Pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'proses_bayar') {
    $metode = $_POST['metode_bayar'] ?? 'tunai';

    if ($metode === 'ewallet') {
        $provider = $_POST['ewallet_provider'] ?? 'DANA';
        $noAkun   = $_POST['ewallet_nomor'] ?? $user->getNoTelepon();
        $bayar    = new PembayaranEWallet($pesanan, $provider, $noAkun);
        $pesanan->setPembayaran($bayar);

        if ($bayar->proses()) { // Polimorfisme: potong saldo
            $pesanan->konfirmasi();
            CineTix::updatePesanan($pesanan);
            CineTix::setFlash("Pembayaran E-Wallet ($provider) berhasil! Saldo Anda dipotong " . CineTix::rp($pesanan->getTotalHarga()) . ". Tiket telah terbit.", "success");
            header("Location: riwayat.php");
            exit;
        } else {
            CineTix::setFlash("Saldo $provider tidak mencukupi! Silakan isi saldo e-wallet Anda terlebih dahulu.", "danger");
        }
    } elseif ($metode === 'transfer') {
        $bank  = $_POST['bank_nama'] ?? 'BCA';
        $bayar = new PembayaranTransfer($pesanan, $bank);
        $pesanan->setPembayaran($bayar);

        if ($bayar->proses()) { // Polimorfisme: cek mutasi simulasi
            $pesanan->konfirmasi();
            CineTix::updatePesanan($pesanan);
            CineTix::setFlash("Pembayaran Transfer Bank $bank ke VA {$bayar->getNoVirtualAccount()} berhasil dikonfirmasi! Tiket telah terbit.", "success");
            header("Location: riwayat.php");
            exit;
        } else {
            CineTix::setFlash("Transfer bank gagal diproses.", "danger");
        }
    } else {
        // Tunai di kasir
        $bayar = new PembayaranTunai($pesanan);
        $pesanan->setPembayaran($bayar);
        CineTix::updatePesanan($pesanan);
        CineTix::setFlash("Metode bayar Tunai dipilih. Silakan lakukan pembayaran di kasir bioskop sebelum batas waktu berakhir.", "warning");
        header("Location: riwayat.php");
        exit;
    }
}

$jadwal = $pesanan->getJadwal();
$film = $jadwal->getFilm();
$studio = $jadwal->getStudio();
$kursiList = [];
foreach ($pesanan->getTiket() as $t) {
    $kursiList[] = $t->getKursi()->getNomorKursi();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Alert Timer -->
            <div class="card bg-dark border-danger mb-4 p-3 d-flex flex-row justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-clock-history text-danger fs-3"></i>
                    <div>
                        <div class="fw-bold text-white">Selesaikan Pembayaran Sebelum Waktu Habis</div>
                        <div class="text-muted small">Kursi akan dilepaskan otomatis jika tidak dibayar dalam batas waktu.</div>
                    </div>
                </div>
                <div class="timer-box">
                    <i class="bi bi-stopwatch"></i>
                    <span id="countdown-timer" data-seconds-left="<?= $pesanan->getSisaWaktuDetik() ?>">--:--</span>
                </div>
            </div>

            <div class="card card-custom p-4 shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-3 mb-4">
                    <div>
                        <span class="badge bg-secondary mb-1">ID Pesanan: <?= htmlspecialchars($pesanan->getIdPemesanan()) ?></span>
                        <h4 class="fw-bold text-white mb-0">Pembayaran Tiket Bioskop</h4>
                    </div>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                        <?= htmlspecialchars($pesanan->getStatus()->value) ?>
                    </span>
                </div>

                <!-- Info Pesanan -->
                <div class="row g-3 mb-4 p-3 bg-dark rounded border border-secondary">
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Judul Film</span>
                        <span class="fw-bold text-light"><?= htmlspecialchars($film->getJudul()) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Studio & Jadwal</span>
                        <span class="fw-bold text-light"><?= htmlspecialchars($studio->getNamaStudio()) ?> &bull; <?= date('d M Y', strtotime($jadwal->getTanggal())) ?> <?= htmlspecialchars($jadwal->getJamMulai()) ?> WIB</span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Kursi Dipesan (<?= count($kursiList) ?> Kursi)</span>
                        <span class="badge bg-primary fs-6 me-1"><?= implode(', ', $kursiList) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Total Tagihan</span>
                        <span class="fw-bold text-warning fs-5"><?= CineTix::rp($pesanan->getTotalHarga()) ?></span>
                    </div>
                </div>

                <h5 class="fw-bold text-white mb-3">Pilih Metode Pembayaran:</h5>

                <!-- Tabs Metode Bayar -->
                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-dark rounded border border-secondary" id="paymentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-light" id="ewallet-tab" data-bs-toggle="pill" data-bs-target="#tab-ewallet" type="button" role="tab">
                            <i class="bi bi-wallet2 me-1"></i> 1. E-Wallet
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-light" id="transfer-tab" data-bs-toggle="pill" data-bs-target="#tab-transfer" type="button" role="tab">
                            <i class="bi bi-bank me-1"></i> 2. Transfer Bank
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-light" id="tunai-tab" data-bs-toggle="pill" data-bs-target="#tab-tunai" type="button" role="tab">
                            <i class="bi bi-cash me-1"></i> 3. Tunai di Kasir
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="paymentTabContent">
                    <!-- Tab 1: E-Wallet -->
                    <div class="tab-pane fade show active" id="tab-ewallet" role="tabpanel">
                        <div class="p-3 bg-dark rounded border border-secondary mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Saldo E-Wallet Anda:</span>
                                <span class="fw-bold text-warning fs-6"><?= CineTix::rp($user->getSaldo()) ?></span>
                            </div>
                            <?php if ($user->getSaldo() < $pesanan->getTotalHarga()): ?>
                                <div class="alert alert-danger py-2 small mb-0">
                                    <i class="bi bi-exclamation-circle me-1"></i> Saldo Anda tidak mencukupi untuk membayar pesanan ini.
                                    <a href="topup.php" class="alert-link ms-2">Isi Saldo di sini</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success py-2 small mb-0">
                                    <i class="bi bi-check-circle me-1"></i> Saldo e-wallet Anda mencukupi. Pembayaran akan dipotong otomatis.
                                </div>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="bayar.php?id=<?= urlencode($pesanan->getIdPemesanan()) ?>">
                            <input type="hidden" name="action" value="proses_bayar">
                            <input type="hidden" name="metode_bayar" value="ewallet">

                            <div class="mb-3">
                                <label class="form-label text-light small fw-bold">Pilih Provider E-Wallet</label>
                                <select name="ewallet_provider" class="form-select bg-dark border-secondary text-light">
                                    <option value="DANA">DANA</option>
                                    <option value="GoPay">GoPay</option>
                                    <option value="OVO">OVO</option>
                                    <option value="ShopeePay">ShopeePay</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-light small fw-bold">Nomor Akun / Handphone</label>
                                <input type="text" name="ewallet_nomor" class="form-control bg-dark border-secondary text-light" value="<?= htmlspecialchars($user->getNoTelepon()) ?>" required>
                            </div>

                            <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2 rounded-pill" <?= $user->getSaldo() < $pesanan->getTotalHarga() ? 'disabled' : '' ?>>
                                <i class="bi bi-wallet-fill me-1"></i> Bayar Sekarang (<?= CineTix::rp($pesanan->getTotalHarga()) ?>)
                            </button>
                        </form>
                    </div>

                    <!-- Tab 2: Transfer Bank -->
                    <div class="tab-pane fade" id="tab-transfer" role="tabpanel">
                        <div class="p-3 bg-dark rounded border border-secondary mb-3">
                            <p class="text-muted small mb-0">
                                Pembayaran transfer dilakukan melalui Virtual Account bank resmi CineTix dengan simulasi verifikasi mutasi otomatis.
                            </p>
                        </div>

                        <form method="POST" action="bayar.php?id=<?= urlencode($pesanan->getIdPemesanan()) ?>">
                            <input type="hidden" name="action" value="proses_bayar">
                            <input type="hidden" name="metode_bayar" value="transfer">

                            <div class="mb-4">
                                <label class="form-label text-light small fw-bold">Pilih Bank Tujuan</label>
                                <select name="bank_nama" class="form-select bg-dark border-secondary text-light">
                                    <option value="BCA">Bank Central Asia (BCA)</option>
                                    <option value="Mandiri">Bank Mandiri</option>
                                    <option value="BNI">Bank Negara Indonesia (BNI)</option>
                                    <option value="BRI">Bank Rakyat Indonesia (BRI)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary fw-bold w-100 py-2 rounded-pill">
                                <i class="bi bi-credit-card me-1"></i> Generate Virtual Account & Konfirmasi Transfer
                            </button>
                        </form>
                    </div>

                    <!-- Tab 3: Tunai di Kasir -->
                    <div class="tab-pane fade" id="tab-tunai" role="tabpanel">
                        <div class="p-3 bg-dark rounded border border-secondary mb-4">
                            <div class="fw-bold text-warning mb-2"><i class="bi bi-info-circle me-1"></i> Petunjuk Bayar di Kasir:</div>
                            <ol class="small text-muted mb-0 ps-3">
                                <li>Tunjukkan Kode Pesanan <strong><?= htmlspecialchars($pesanan->getIdPemesanan()) ?></strong> kepada kasir bioskop.</li>
                                <li>Serahkan uang tunai sebesar <strong><?= CineTix::rp($pesanan->getTotalHarga()) ?></strong>.</li>
                                <li>Kasir akan memverifikasi pembayaran dan mencetak tiket fisik untuk Anda.</li>
                                <li>Harap bayar sebelum batas waktu 15 menit berakhir.</li>
                            </ol>
                        </div>

                        <form method="POST" action="bayar.php?id=<?= urlencode($pesanan->getIdPemesanan()) ?>">
                            <input type="hidden" name="action" value="proses_bayar">
                            <input type="hidden" name="metode_bayar" value="tunai">

                            <button type="submit" class="btn btn-outline-warning fw-bold w-100 py-2 rounded-pill">
                                <i class="bi bi-shop me-1"></i> Pilih Bayar Tunai di Kasir
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Batalkan Pesanan Link -->
                <div class="text-center mt-4 pt-3 border-top border-secondary">
                    <a href="bayar.php?id=<?= urlencode($pesanan->getIdPemesanan()) ?>&batal=1" 
                       class="text-danger small text-decoration-none"
                       onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini? Kursi yang ditahan akan dilepaskan.');">
                        <i class="bi bi-trash me-1"></i> Batalkan Pesanan Ini
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
