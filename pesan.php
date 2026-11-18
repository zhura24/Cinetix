<?php
// pesan.php - Pemilihan Kursi dan Pembuatan Pesanan
$pageTitle = 'Pilih Kursi - CineTix';
require_once __DIR__ . '/classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Pelanggan) {
    CineTix::setFlash("Silakan masuk sebagai Pelanggan terlebih dahulu untuk memesan tiket.", "warning");
    header("Location: login.php");
    exit;
}

$idJadwal = $_GET['jadwal'] ?? '';
$jadwal = CineTix::getJadwalById($idJadwal);

if (!$jadwal) {
    CineTix::setFlash("Jadwal tidak ditemukan!", "danger");
    header("Location: index.php");
    exit;
}

$studio = $jadwal->getStudio();
$film = $jadwal->getFilm();
$kursiList = $jadwal->getKursiJadwal();
$hargaPerTiket = $jadwal->hitungHargaTiket();

// Kelompokkan kursi per baris
$barisGroup = [];
foreach ($kursiList as $k) {
    $barisGroup[$k->getBaris()][] = $k;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-warning">Beranda</a></li>
            <li class="breadcrumb-item active text-light" aria-current="page">Pilih Kursi</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main: Seat Matrix -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-3 mb-4">
                    <div>
                        <h4 class="fw-bold text-white mb-1">
                            <i class="bi bi-grid-3x3 text-warning me-2"></i>Pilih Kursi Bioskop
                        </h4>
                        <p class="text-muted small mb-0">Klik pada kursi yang tersedia untuk memilih tempat duduk Anda</p>
                    </div>
                    <span class="badge <?= $studio instanceof StudioPremium ? 'bg-warning text-dark' : 'bg-primary' ?> px-3 py-2 rounded-pill">
                        <?= htmlspecialchars($studio->getNamaStudio()) ?>
                    </span>
                </div>

                <!-- Screen -->
                <div class="cinema-screen-container">
                    <div class="cinema-screen">
                        <i class="bi bi-display me-2"></i> LAYAR BIOSKOP
                    </div>
                    <small class="text-muted">Arah pandangan ke depan</small>
                </div>

                <!-- Seat Grid -->
                <div class="seat-grid my-4">
                    <?php foreach ($barisGroup as $baris => $seats): ?>
                        <div class="seat-row">
                            <span class="row-label"><?= $baris ?></span>
                            <?php foreach ($seats as $seat): ?>
                                <?php
                                $status = $seat->getStatus();
                                $statusClass = 'available';
                                $title = "Kursi " . $seat->getNomorKursi() . " (Tersedia)";

                                if ($status === StatusKursi::DITAHAN) {
                                    $statusClass = 'held';
                                    $title = "Kursi " . $seat->getNomorKursi() . " (Sedang Ditahan/Menunggu Bayar)";
                                } elseif ($status === StatusKursi::TERPESAN) {
                                    $statusClass = 'booked';
                                    $title = "Kursi " . $seat->getNomorKursi() . " (Sudah Terpesan)";
                                }
                                ?>
                                <button type="button" 
                                        class="seat-btn <?= $statusClass ?>" 
                                        data-seat="<?= htmlspecialchars($seat->getNomorKursi()) ?>"
                                        title="<?= $title ?>"
                                        <?= $status !== StatusKursi::TERSEDIA ? 'disabled' : '' ?>>
                                    <?php if ($status === StatusKursi::TERSEDIA): ?>
                                        <?= htmlspecialchars($seat->getNomorKursi()) ?>
                                    <?php elseif ($status === StatusKursi::DITAHAN): ?>
                                        <i class="bi bi-hourglass-split"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-lg"></i>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                            <span class="row-label"><?= $baris ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Legend -->
                <div class="seat-legend">
                    <div class="legend-item">
                        <div class="legend-box" style="background-color: var(--seat-available); border: 1px solid #475569;"></div>
                        <span>Tersedia</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background-color: var(--seat-selected); border: 1px solid #fff;"></div>
                        <span>Dipilih</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background-color: var(--seat-held);"></div>
                        <span>Ditahan (Proses Bayar)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background-color: var(--seat-booked);"></div>
                        <span>Terpesan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Ringkasan Pemesanan -->
        <div class="col-lg-4">
            <div class="card card-custom p-4 shadow-sm sticky-top" style="top: 85px;">
                <h5 class="fw-bold text-white border-bottom border-secondary pb-3 mb-3">
                    <i class="bi bi-receipt text-warning me-2"></i>Ringkasan Tiket
                </h5>

                <div class="d-flex gap-3 align-items-center mb-3">
                    <img src="<?= htmlspecialchars($film->getPoster()) ?>" alt="Poster" class="rounded" style="width: 70px; height: 95px; object-fit: cover;">
                    <div>
                        <h6 class="fw-bold text-white mb-1"><?= htmlspecialchars($film->getJudul()) ?></h6>
                        <span class="badge bg-secondary mb-1"><?= htmlspecialchars($film->getGenre()) ?></span>
                        <div class="small text-muted"><?= $film->getDurasi() ?> menit &bull; <?= htmlspecialchars($film->getRatingUsia()) ?></div>
                    </div>
                </div>

                <div class="p-3 bg-dark rounded border border-secondary mb-3 small">
                    <div class="d-flex justify-content-between mb-2 text-light">
                        <span class="text-muted">Studio:</span>
                        <span class="fw-bold"><?= htmlspecialchars($studio->getNamaStudio()) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-light">
                        <span class="text-muted">Jadwal:</span>
                        <span class="fw-bold"><?= date('d M Y', strtotime($jadwal->getTanggal())) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-light">
                        <span class="text-muted">Jam Mulai:</span>
                        <span class="fw-bold"><?= htmlspecialchars($jadwal->getJamMulai()) ?> WIB</span>
                    </div>
                    <div class="d-flex justify-content-between text-light">
                        <span class="text-muted">Harga per Kursi:</span>
                        <span class="fw-bold text-warning"><?= CineTix::rp($hargaPerTiket) ?></span>
                    </div>
                </div>

                <!-- Form Pemesanan -->
                <form method="POST" action="bayar.php">
                    <input type="hidden" name="action" value="buat_pesanan">
                    <input type="hidden" name="id_jadwal" value="<?= htmlspecialchars($jadwal->getIdJadwal()) ?>">
                    <input type="hidden" id="ticket-price" value="<?= $hargaPerTiket ?>">
                    <input type="hidden" name="kursi" id="selected-seats-input" value="">

                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Kursi yang Dipilih:</label>
                        <div class="p-2 bg-dark rounded border border-secondary text-light fw-bold" id="selected-seats-display">
                            Belum ada kursi dipilih
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 bg-dark rounded border border-warning mb-4">
                        <span class="text-light fw-bold">Total Pembayaran:</span>
                        <span class="fs-5 fw-bold text-warning" id="total-price-display">Rp 0</span>
                    </div>

                    <button type="submit" id="btn-submit-booking" class="btn btn-warning text-dark fw-bold w-100 rounded-pill py-2" disabled>
                        <i class="bi bi-credit-card me-1"></i> Lanjut ke Pembayaran
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
