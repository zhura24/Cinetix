<?php
// riwayat.php - Riwayat Pemesanan dan Tiket Pelanggan
$pageTitle = 'Riwayat Pesanan - CineTix';
$activeNav = 'riwayat';
require_once __DIR__ . '/classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Pelanggan) {
    CineTix::setFlash("Silakan masuk sebagai Pelanggan untuk melihat riwayat tiket.", "warning");
    header("Location: login.php");
    exit;
}

// Tangani pembatalan pesanan
if (isset($_GET['batal_id'])) {
    $idBatal = $_GET['batal_id'];
    if ($user->batalkanPesanan($idBatal)) {
        CineTix::setFlash("Pesanan $idBatal berhasil dibatalkan dan kursi telah dilepaskan.", "info");
    } else {
        CineTix::setFlash("Pesanan $idBatal tidak dapat dibatalkan (mungkin sudah dibayar atau tidak ditemukan).", "danger");
    }
    header("Location: riwayat.php");
    exit;
}

$riwayat = $user->lihatRiwayat();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-ticket-perforated text-warning me-2"></i>Riwayat Pemesanan Tiket
            </h3>
            <p class="text-muted small mb-0">Daftar semua transaksi pemesanan tiket Anda di CineTix</p>
        </div>
        <a href="index.php" class="btn btn-warning btn-sm text-dark fw-bold rounded-pill px-3">
            <i class="bi bi-plus-circle me-1"></i> Pesan Tiket Baru
        </a>
    </div>

    <?php if (empty($riwayat)): ?>
        <div class="card card-custom p-5 text-center shadow-sm">
            <i class="bi bi-ticket-detailed display-3 text-muted mb-3"></i>
            <h5 class="text-white fw-bold mb-2">Belum Ada Riwayat Pemesanan</h5>
            <p class="text-muted small mb-4">Anda belum pernah memesan tiket film di CineTix. Yuk tonton film seru sekarang!</p>
            <div>
                <a href="index.php" class="btn btn-warning text-dark fw-bold rounded-pill px-4">
                    Jelajahi Film
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($riwayat as $ps): ?>
                <?php
                $jadwal = $ps->getJadwal();
                $film = $jadwal->getFilm();
                $studio = $jadwal->getStudio();
                $status = $ps->getStatus();
                $bayar = $ps->getPembayaran();

                $badgeClass = 'bg-warning text-dark';
                if ($status === StatusPesanan::DIKONFIRMASI) $badgeClass = 'bg-success';
                if ($status === StatusPesanan::DIBATALKAN) $badgeClass = 'bg-secondary';
                ?>
                <div class="col-12">
                    <div class="card card-custom p-4 shadow-sm border <?= $status === StatusPesanan::DIKONFIRMASI ? 'border-success' : 'border-secondary' ?>">
                        <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom border-secondary pb-3 mb-3 gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-dark border border-secondary text-light font-monospace">
                                    <?= htmlspecialchars($ps->getIdPemesanan()) ?>
                                </span>
                                <span class="text-muted small">&bull; <?= date('d M Y, H:i', strtotime($ps->getWaktuPesan())) ?> WIB</span>
                            </div>
                            <div>
                                <span class="badge <?= $badgeClass ?> px-3 py-2 rounded-pill fw-bold">
                                    <?= htmlspecialchars($status->value) ?>
                                </span>
                            </div>
                        </div>

                        <div class="row align-items-center g-3">
                            <div class="col-md-2 text-center text-md-start">
                                <img src="<?= htmlspecialchars($film->getPoster()) ?>" alt="Poster" class="rounded shadow-sm" style="width: 90px; height: 125px; object-fit: cover;">
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-bold text-white mb-1"><?= htmlspecialchars($film->getJudul()) ?></h5>
                                <div class="text-muted small mb-2">
                                    <span class="badge bg-secondary me-1"><?= htmlspecialchars($film->getGenre()) ?></span>
                                    <span><?= $film->getDurasi() ?> menit &bull; Rating: <?= htmlspecialchars($film->getRatingUsia()) ?></span>
                                </div>
                                <div class="small text-light mb-1">
                                    <i class="bi bi-display text-warning me-1"></i> <strong><?= htmlspecialchars($studio->getNamaStudio()) ?></strong>
                                </div>
                                <div class="small text-light mb-2">
                                    <i class="bi bi-calendar-event text-info me-1"></i> <?= date('d M Y', strtotime($jadwal->getTanggal())) ?>, <?= htmlspecialchars($jadwal->getJamMulai()) ?> WIB
                                </div>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <span class="small text-muted me-1">Kursi:</span>
                                    <?php foreach ($ps->getTiket() as $t): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars($t->getKursi()->getNomorKursi()) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end border-top border-md-top-0 border-secondary pt-3 pt-md-0">
                                <div class="text-muted small mb-1">Total Tagihan</div>
                                <h4 class="fw-bold text-warning mb-2"><?= CineTix::rp($ps->getTotalHarga()) ?></h4>
                                <div class="small text-muted mb-3">
                                    Bayar: <strong><?= $bayar ? htmlspecialchars($bayar->getStatus()) : '-' ?></strong>
                                </div>

                                <div class="d-flex justify-content-md-end gap-2 flex-wrap">
                                    <?php if ($status === StatusPesanan::DIKONFIRMASI): ?>
                                        <a href="tiket.php?id=<?= urlencode($ps->getIdPemesanan()) ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
                                            <i class="bi bi-printer me-1"></i> Cetak / Lihat Tiket
                                        </a>
                                    <?php elseif ($status === StatusPesanan::MENUNGGU_PEMBAYARAN): ?>
                                        <a href="bayar.php?id=<?= urlencode($ps->getIdPemesanan()) ?>" class="btn btn-warning text-dark btn-sm rounded-pill px-3 fw-bold">
                                            <i class="bi bi-credit-card me-1"></i> Lanjut Bayar
                                        </a>
                                        <a href="riwayat.php?batal_id=<?= urlencode($ps->getIdPemesanan()) ?>" 
                                           class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                           onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');">
                                            Batalkan
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pesanan Dibatalkan</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
