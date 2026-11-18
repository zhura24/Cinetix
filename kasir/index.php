<?php
// kasir/index.php - Verifikasi Pembayaran Tunai (Kasir.verifikasiPembayaran())
$pageTitle = 'Kasir - Verifikasi Pembayaran Tunai';
$activeNav = 'kasir';
require_once __DIR__ . '/../classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Kasir) {
    CineTix::setFlash("Akses ditolak! Halaman ini hanya untuk Kasir.", "danger");
    header("Location: ../login.php");
    exit;
}

$hasilVerifikasi = null;

// Tangani verifikasi pembayaran tunai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verifikasi') {
    $idPemesanan  = trim($_POST['id_pemesanan'] ?? '');
    $uangDiterima = (float)($_POST['uang_diterima'] ?? 0);

    $hasilVerifikasi = $user->verifikasiPembayaran($idPemesanan, $uangDiterima);
    if ($hasilVerifikasi['sukses']) {
        CineTix::setFlash($hasilVerifikasi['pesan'], "success");
    } else {
        CineTix::setFlash($hasilVerifikasi['pesan'], "danger");
    }
}

// Ambil semua pesanan yang menunggu pembayaran
$allPesanan = CineTix::getAllPesanan();
$pesananMenunggu = array_filter($allPesanan, function($p) {
    return $p->getStatus() === StatusPesanan::MENUNGGU_PEMBAYARAN;
});

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-cash-coin text-warning me-2"></i>Verifikasi Pembayaran Tunai
            </h3>
            <p class="page-subtitle mb-0">Petugas Kasir: <strong class="text-white"><?= htmlspecialchars($user->getNama()) ?></strong> &bull; Shift: <strong class="text-warning"><?= htmlspecialchars($user->getIdShift()) ?></strong></p>
        </div>
        <a href="validasi.php" class="btn btn-outline-info btn-sm rounded-pill px-3">
            <i class="bi bi-upc-scan me-1"></i> Ke Validasi / Scan Tiket &rarr;
        </a>
    </div>

    <!-- Hasil Verifikasi Modal/Alert -->
    <?php if ($hasilVerifikasi && $hasilVerifikasi['sukses']): ?>
        <div class="card bg-dark border-success p-4 mb-4 shadow">
            <div class="d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-check-circle-fill text-success display-5"></i>
                <div>
                    <h4 class="fw-bold text-success mb-1">Pembayaran Terverifikasi!</h4>
                    <p class="text-light mb-0">Pesanan <strong><?= htmlspecialchars($hasilVerifikasi['pesanan']->getIdPemesanan()) ?></strong> telah berstatus DIKONFIRMASI.</p>
                </div>
            </div>
            <div class="p-3 bg-black rounded border border-secondary mb-3">
                <div class="d-flex justify-content-between fs-5 mb-1">
                    <span class="text-light opacity-75">Total Tagihan:</span>
                    <span class="text-light fw-bold"><?= CineTix::rp($hasilVerifikasi['pesanan']->getTotalHarga()) ?></span>
                </div>
                <div class="d-flex justify-content-between fs-5 mb-2">
                    <span class="text-light opacity-75">Uang Diterima:</span>
                    <span class="text-info fw-bold"><?= CineTix::rp($_POST['uang_diterima']) ?></span>
                </div>
                <div class="d-flex justify-content-between fs-4 border-top border-secondary pt-2">
                    <span class="text-warning fw-bold">Kembalian:</span>
                    <span class="text-warning fw-black"><?= CineTix::rp($hasilVerifikasi['kembalian']) ?></span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="../tiket.php?id=<?= urlencode($hasilVerifikasi['pesanan']->getIdPemesanan()) ?>" class="btn btn-warning text-dark fw-bold rounded-pill px-4" target="_blank">
                    <i class="bi bi-printer me-1"></i> Cetak Tiket Sekarang
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Verifikasi Cepat -->
        <div class="col-lg-5">
            <div class="card card-custom p-4 shadow-sm">
                <h5 class="fw-bold text-white border-bottom border-secondary pb-3 mb-3">
                    <i class="bi bi-input-cursor-text text-warning me-2"></i>Input Pembayaran Kasir
                </h5>
                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="verifikasi">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-light">Kode / ID Pesanan</label>
                        <input type="text" name="id_pemesanan" id="input_id_pesanan" class="form-control bg-dark border-secondary text-light font-monospace" placeholder="Contoh: P1" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-light">Uang Tunai Diterima (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-warning fw-bold">Rp</span>
                            <input type="number" name="uang_diterima" class="form-control bg-dark border-secondary text-light fs-5 fw-bold" placeholder="50000" step="1000" required>
                        </div>
                        <small class="text-light opacity-75">Sistem akan otomatis menghitung kembalian.</small>
                    </div>

                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100 rounded-pill py-2">
                        <i class="bi bi-check2-circle me-1"></i> Proses Verifikasi Tunai
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Pesanan Menunggu Pembayaran -->
        <div class="col-lg-7">
            <div class="card card-custom p-4 shadow-sm">
                <h5 class="fw-bold text-white border-bottom border-secondary pb-3 mb-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hourglass-split text-danger me-2"></i>Antrean Pesanan Menunggu Bayar</span>
                    <span class="badge bg-danger rounded-pill"><?= count($pesananMenunggu) ?> Pesanan</span>
                </h5>

                <?php if (empty($pesananMenunggu)): ?>
                    <div class="alert alert-dark border border-secondary text-center py-4 mb-0">
                        <i class="bi bi-check2-all fs-2 text-success mb-2 d-block"></i>
                        Tidak ada pesanan yang sedang menunggu pembayaran tunai.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pesananMenunggu as $ps): ?>
                            <?php
                            $j = $ps->getJadwal();
                            $kursiStr = [];
                            foreach ($ps->getTiket() as $t) $kursiStr[] = $t->getKursi()->getNomorKursi();
                            ?>
                            <div class="list-group-item bg-dark border-secondary text-light p-3 mb-2 rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-secondary font-monospace fs-6 me-2"><?= htmlspecialchars($ps->getIdPemesanan()) ?></span>
                                        <span class="fw-bold text-white"><?= htmlspecialchars($ps->getPelanggan()->getNama()) ?></span>
                                    </div>
                                    <span class="fw-bold text-warning fs-6"><?= CineTix::rp($ps->getTotalHarga()) ?></span>
                                </div>
                                <div class="small text-light opacity-75 mb-2">
                                    Film: <strong class="text-white"><?= htmlspecialchars($j->getFilm()->getJudul()) ?></strong> &bull; 
                                    <?= htmlspecialchars($j->getStudio()->getNamaStudio()) ?> &bull; 
                                    Kursi: <span class="badge bg-primary"><?= implode(', ', $kursiStr) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-danger">
                                        <i class="bi bi-clock me-1"></i>Sisa waktu: <?= floor($ps->getSisaWaktuDetik() / 60) ?> menit
                                    </small>
                                    <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3" 
                                            onclick="document.getElementById('input_id_pesanan').value = '<?= $ps->getIdPemesanan() ?>'; window.scrollTo({top: 0, behavior: 'smooth'});">
                                        Pilih Pesanan Ini
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
