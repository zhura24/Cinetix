<?php
// tiket.php - Tampilan & Cetak E-Tiket CineTix
$pageTitle = 'E-Tiket - CineTix';
require_once __DIR__ . '/classes/autoload.php';

$idPemesanan = $_GET['id'] ?? '';
$pesanan = CineTix::cariPesanan($idPemesanan);

if (!$pesanan || $pesanan->getStatus() !== StatusPesanan::DIKONFIRMASI) {
    CineTix::setFlash("Tiket tidak ditemukan atau pesanan belum dikonfirmasi.", "danger");
    header("Location: riwayat.php");
    exit;
}

$jadwal = $pesanan->getJadwal();
$film = $jadwal->getFilm();
$studio = $jadwal->getStudio();
$tiketList = $pesanan->getTiket();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <a href="riwayat.php" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
            </a>
            <h3 class="fw-bold text-white mb-0">E-Tiket Bioskop Resmi</h3>
        </div>
        <button onclick="window.print()" class="btn btn-warning text-dark fw-bold rounded-pill px-4">
            <i class="bi bi-printer-fill me-1"></i> Cetak Tiket
        </button>
    </div>

    <div class="alert alert-info border-info mb-4 no-print">
        <i class="bi bi-info-circle-fill me-1"></i> Tunjukkan kode tiket ini kepada petugas kasir / gate bioskop saat memasuki studio.
    </div>

    <!-- Ticket Cards List -->
    <div class="row g-4 justify-content-center">
        <?php foreach ($tiketList as $index => $t): ?>
            <div class="col-md-6 col-lg-5">
                <div class="ticket-card shadow-lg">
                    <div class="ticket-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-film fs-5"></i>
                            <span class="fw-black tracking-wider">CINETIX TICKET</span>
                        </div>
                        <span class="badge bg-dark text-warning border border-warning font-monospace fs-6">
                            <?= htmlspecialchars($t->getKodeTiket()) ?>
                        </span>
                    </div>

                    <div class="ticket-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="fw-bold text-white mb-1"><?= htmlspecialchars($film->getJudul()) ?></h4>
                                <span class="badge bg-secondary me-1"><?= htmlspecialchars($film->getGenre()) ?></span>
                                <span class="badge bg-dark border border-secondary text-secondary">Rating: <?= htmlspecialchars($film->getRatingUsia()) ?></span>
                            </div>
                            <span class="badge <?= $studio instanceof StudioPremium ? 'bg-warning text-dark' : 'bg-primary' ?> px-2 py-1 rounded">
                                <?= htmlspecialchars($studio->getNamaStudio()) ?>
                            </span>
                        </div>

                        <div class="p-3 bg-dark rounded border border-secondary mb-3">
                            <div class="row text-center g-2">
                                <div class="col-4 border-end border-secondary">
                                    <span class="text-muted small d-block">TANGGAL</span>
                                    <span class="fw-bold text-light small"><?= date('d M Y', strtotime($jadwal->getTanggal())) ?></span>
                                </div>
                                <div class="col-4 border-end border-secondary">
                                    <span class="text-muted small d-block">JAM</span>
                                    <span class="fw-bold text-warning small"><?= htmlspecialchars($jadwal->getJamMulai()) ?> WIB</span>
                                </div>
                                <div class="col-4">
                                    <span class="text-muted small d-block">KURSI</span>
                                    <span class="fw-black text-danger fs-5"><?= htmlspecialchars($t->getKursi()->getNomorKursi()) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="ticket-divider"></div>

                        <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                            <span>Nama Penonton:</span>
                            <span class="text-light fw-bold"><?= htmlspecialchars($pesanan->getPelanggan()->getNama()) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small text-muted mb-3">
                            <span>Status Tiket:</span>
                            <?php if ($t->isStatusPakai()): ?>
                                <span class="badge bg-danger">SUDAH DIPAKAI (CHECKED-IN)</span>
                            <?php else: ?>
                                <span class="badge bg-success">VALID (BELUM DIPAKAI)</span>
                            <?php endif; ?>
                        </div>

                        <!-- Barcode Simulation -->
                        <div class="barcode-sim"></div>
                        <div class="text-center font-monospace text-muted small mt-1" style="font-size: 0.75rem;">
                            * <?= htmlspecialchars($t->getKodeTiket()) ?> *
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
@media print {
    body {
        background: #fff !important;
        color: #000 !important;
    }
    .no-print, nav, footer, .btn {
        display: none !important;
    }
    .ticket-card {
        border: 2px solid #000 !important;
        background: #fff !important;
        color: #000 !important;
        page-break-inside: avoid;
        margin-bottom: 20px;
    }
    .ticket-header {
        background: #000 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
    }
    .ticket-body {
        background: #fff !important;
    }
    .text-white, .text-light {
        color: #000 !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
