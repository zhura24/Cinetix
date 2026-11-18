<?php
// kasir/validasi.php - Validasi & Cetak Tiket (Kasir.validasi() & Kasir.cetakTiket())
$pageTitle = 'Kasir - Validasi & Cetak Tiket';
$activeNav = 'kasir_validasi';
require_once __DIR__ . '/../classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Kasir) {
    CineTix::setFlash("Akses ditolak! Halaman ini hanya untuk Kasir.", "danger");
    header("Location: ../login.php");
    exit;
}

$hasilValidasi = null;
$pesananCetak = null;

// 1. Validasi Kode Tiket (Check-In)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'validasi_tiket') {
    $kodeTiket = trim($_POST['kode_tiket'] ?? '');
    $tiket = CineTix::cariTiket($kodeTiket);

    if ($tiket === null) {
        $hasilValidasi = [
            'status' => 'not_found',
            'pesan'  => "Tiket dengan kode \"$kodeTiket\" TIDAK DITEMUKAN dalam sistem!"
        ];
    } else {
        $sukses = $tiket->validasi(); // Memanggil method validasi() pada class Tiket
        if ($sukses) {
            $hasilValidasi = [
                'status' => 'valid',
                'pesan'  => "Tiket VALID! Silakan masuk ke dalam studio bioskop.",
                'tiket'  => $tiket
            ];
        } else {
            $hasilValidasi = [
                'status' => 'used',
                'pesan'  => "PERINGATAN: Tiket \"{$tiket->getKodeTiket()}\" SUDAH PERNAH DIPAKAI SEBELUMNYA! Akses ditolak.",
                'tiket'  => $tiket
            ];
        }
    }
}

// 2. Cari Pesanan untuk Cetak Tiket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cari_pesanan_cetak') {
    $idPemesanan = trim($_POST['id_pemesanan'] ?? '');
    $ps = CineTix::cariPesanan($idPemesanan);

    if ($ps === null || $ps->getStatus() !== StatusPesanan::DIKONFIRMASI) {
        CineTix::setFlash("Pesanan tidak ditemukan atau belum berstatus DIKONFIRMASI.", "danger");
    } else {
        $pesananCetak = $ps;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-upc-scan text-info me-2"></i>Validasi Tiket (Gate Check-In) & Cetak
            </h3>
            <p class="text-muted small mb-0">Implementasi method <code>Tiket.validasi()</code> dan <code>Kasir.cetakTiket()</code></p>
        </div>
        <a href="index.php" class="btn btn-outline-warning btn-sm rounded-pill px-3">
            <i class="bi bi-cash-coin me-1"></i> Ke Verifikasi Pembayaran Tunai &rarr;
        </a>
    </div>

    <div class="row g-4">
        <!-- Panel 1: Validasi Tiket (Check-In Gate) -->
        <div class="col-lg-6">
            <div class="card card-custom p-4 shadow-sm h-100">
                <h5 class="fw-bold text-white border-bottom border-secondary pb-3 mb-3">
                    <i class="bi bi-qr-code-scan text-warning me-2"></i>Validasi Tiket Masuk Studio
                </h5>
                <p class="text-muted small">
                    Masukkan kode tiket penonton (contoh: <code>TKT-XXXXXX</code>) untuk memeriksa keabsahan dan mencatat check-in.
                </p>

                <form method="POST" action="validasi.php" class="mb-4">
                    <input type="hidden" name="action" value="validasi_tiket">
                    <div class="input-group mb-3">
                        <input type="text" name="kode_tiket" class="form-control bg-dark border-secondary text-light font-monospace fs-5 text-uppercase" placeholder="TKT-XXXXXX" required autofocus>
                        <button class="btn btn-info fw-bold px-4" type="submit">
                            <i class="bi bi-check2-circle me-1"></i> Validasi
                        </button>
                    </div>
                </form>

                <?php if ($hasilValidasi): ?>
                    <?php if ($hasilValidasi['status'] === 'valid'): ?>
                        <div class="alert alert-success border-success p-3 rounded">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-check-circle-fill fs-3"></i>
                                <h5 class="alert-heading fw-bold mb-0">TIKET VALID!</h5>
                            </div>
                            <p class="mb-2"><?= htmlspecialchars($hasilValidasi['pesan']) ?></p>
                            <?php $t = $hasilValidasi['tiket']; $j = $t->getPemesanan()->getJadwal(); ?>
                            <div class="p-2 bg-dark rounded border border-success small text-light">
                                <div><strong>Film:</strong> <?= htmlspecialchars($j->getFilm()->getJudul()) ?></div>
                                <div><strong>Studio:</strong> <?= htmlspecialchars($j->getStudio()->getNamaStudio()) ?></div>
                                <div><strong>Kursi:</strong> <span class="badge bg-warning text-dark fs-6"><?= htmlspecialchars($t->getKursi()->getNomorKursi()) ?></span></div>
                                <div><strong>Jadwal:</strong> <?= date('d M Y', strtotime($j->getTanggal())) ?> <?= htmlspecialchars($j->getJamMulai()) ?> WIB</div>
                            </div>
                        </div>
                    <?php elseif ($hasilValidasi['status'] === 'used'): ?>
                        <div class="alert alert-danger border-danger p-3 rounded">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-x-octagon-fill fs-3"></i>
                                <h5 class="alert-heading fw-bold mb-0">TIKET SUDAH DIPAKAI!</h5>
                            </div>
                            <p class="mb-0"><?= htmlspecialchars($hasilValidasi['pesan']) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning border-warning p-3 rounded">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                                <h5 class="alert-heading fw-bold mb-0">TIKET TIDAK DITEMUKAN</h5>
                            </div>
                            <p class="mb-0"><?= htmlspecialchars($hasilValidasi['pesan']) ?></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel 2: Cari & Cetak Tiket Fisik -->
        <div class="col-lg-6">
            <div class="card card-custom p-4 shadow-sm h-100">
                <h5 class="fw-bold text-white border-bottom border-secondary pb-3 mb-3">
                    <i class="bi bi-printer text-warning me-2"></i>Cetak Tiket Fisik Kasir
                </h5>
                <p class="text-muted small">
                    Masukkan kode pesanan penonton untuk mencetak seluruh lembar tiket yang telah terkonfirmasi.
                </p>

                <form method="POST" action="validasi.php" class="mb-4">
                    <input type="hidden" name="action" value="cari_pesanan_cetak">
                    <div class="input-group mb-3">
                        <input type="text" name="id_pemesanan" class="form-control bg-dark border-secondary text-light font-monospace fs-5 text-uppercase" placeholder="Contoh: P1" required>
                        <button class="btn btn-warning text-dark fw-bold px-4" type="submit">
                            <i class="bi bi-search me-1"></i> Cari Tiket
                        </button>
                    </div>
                </form>

                <?php if ($pesananCetak): ?>
                    <?php $j = $pesananCetak->getJadwal(); ?>
                    <div class="card bg-dark border-secondary p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($pesananCetak->getIdPemesanan()) ?></span>
                            <span class="badge bg-success">DIKONFIRMASI</span>
                        </div>
                        <h6 class="fw-bold text-white mb-1"><?= htmlspecialchars($j->getFilm()->getJudul()) ?></h6>
                        <div class="small text-muted mb-3">
                            <?= htmlspecialchars($j->getStudio()->getNamaStudio()) ?> &bull; <?= count($pesananCetak->getTiket()) ?> Kursi
                        </div>

                        <div class="d-grid gap-2 mb-3">
                            <?php foreach ($pesananCetak->getTiket() as $t): ?>
                                <div class="p-2 bg-black rounded border border-secondary d-flex justify-content-between align-items-center small text-light">
                                    <span>Kursi: <strong class="text-warning"><?= htmlspecialchars($t->getKursi()->getNomorKursi()) ?></strong></span>
                                    <span class="font-monospace text-muted"><?= htmlspecialchars($t->getKodeTiket()) ?></span>
                                    <span class="badge <?= $t->isStatusPakai() ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $t->isStatusPakai() ? 'Dipakai' : 'Belum Dipakai' ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <a href="../tiket.php?id=<?= urlencode($pesananCetak->getIdPemesanan()) ?>" class="btn btn-warning text-dark fw-bold rounded-pill" target="_blank">
                            <i class="bi bi-printer-fill me-1"></i> Buka Tampilan Cetak Tiket
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
