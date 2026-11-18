<?php
// index.php - Halaman Utama CineTix (Katalog Film & Jadwal)
$pageTitle = 'CineTix - Film & Jadwal Bioskop';
$activeNav = 'home';
require_once __DIR__ . '/includes/header.php';

$allFilm = CineTix::getAllFilm();
$allJadwal = CineTix::getAllJadwal();
?>

<!-- Hero Banner -->
<div class="hero-section mb-5">
    <div class="container text-center">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
            <i class="bi bi-stars me-1"></i> BIOSKOP CINETIX RESMI
        </span>
        <h1 class="display-4 fw-black text-white mb-3">Tonton Film Favorit Anda Hari Ini</h1>
        <p class="lead text-secondary mx-auto" style="max-width: 650px;">
            Pesan tiket film bioskop secara cepat, pilih kursi favorit Anda, dan nikmati kemudahan pembayaran tunai, e-wallet, atau transfer bank.
        </p>
    </div>
</div>

<div class="container mb-5">
    <!-- Section: Sedang Tayang (Film List) -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-film text-danger me-2"></i>Film Sedang Tayang
            </h3>
            <p class="text-muted small mb-0">Daftar film bioskop yang tersedia di CineTix</p>
        </div>
        <span class="badge bg-dark border border-secondary text-secondary px-3 py-2 rounded-pill">
            <?= count($allFilm) ?> Film Tersedia
        </span>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4 mb-5">
        <?php foreach ($allFilm as $f): ?>
            <?php
            $ageClass = 'su';
            if ($f->getRatingUsia() === '13+') $ageClass = 'r13';
            if ($f->getRatingUsia() === '17+') $ageClass = 'd17';
            ?>
            <div class="col">
                <div class="card card-movie h-100 shadow-sm">
                    <div class="poster-wrapper">
                        <img src="<?= htmlspecialchars($f->getPoster()) ?>" class="poster-img" alt="<?= htmlspecialchars($f->getJudul()) ?>">
                        <span class="badge-age <?= $ageClass ?>">
                            <?= htmlspecialchars($f->getRatingUsia()) ?>
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-secondary text-light"><?= htmlspecialchars($f->getGenre()) ?></span>
                            <span class="text-muted small"><i class="bi bi-clock me-1"></i><?= $f->getDurasi() ?> menit</span>
                        </div>
                        <h5 class="card-title fw-bold text-white mb-2"><?= htmlspecialchars($f->getJudul()) ?></h5>
                        <p class="card-text text-secondary small flex-grow-1">
                            <?= htmlspecialchars($f->getSinopsis()) ?>
                        </p>
                        <a href="#jadwal-film-<?= $f->getIdFilm() ?>" class="btn btn-outline-warning w-100 mt-3 rounded-pill">
                            <i class="bi bi-calendar-check me-1"></i> Lihat Jadwal
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Section: Jadwal Tayang (Schedules) -->
    <div class="pt-4 border-top border-secondary" id="jadwal-section">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-white mb-1">
                    <i class="bi bi-clock-history text-warning me-2"></i>Jadwal Tayang & Tiket
                </h3>
                <p class="text-muted small mb-0">Pilih jadwal penayangan dan studio untuk memesan kursi</p>
            </div>
        </div>

        <?php if (empty($allJadwal)): ?>
            <div class="alert alert-dark border border-secondary text-center py-4">
                <i class="bi bi-calendar-x fs-2 text-muted mb-2 d-block"></i>
                Belum ada jadwal penayangan yang tersedia saat ini.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($allJadwal as $j): ?>
                    <?php
                    $studio = $j->getStudio();
                    $isTersedia = $j->cekKetersediaan();
                    $isPremium = ($studio instanceof StudioPremium);
                    $hargaAkhir = $j->hitungHargaTiket();
                    ?>
                    <div class="col-md-6 col-lg-4" id="jadwal-film-<?= $j->getFilm()->getIdFilm() ?>">
                        <div class="card card-custom h-100 shadow-sm border <?= $isPremium ? 'border-warning' : 'border-secondary' ?>">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge <?= $isPremium ? 'bg-warning text-dark' : 'bg-primary' ?> px-3 py-1 rounded-pill fw-bold">
                                        <?= htmlspecialchars($studio->getNamaStudio()) ?>
                                    </span>
                                    <span class="badge <?= $isTersedia ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                        <?= $isTersedia ? 'Kursi Tersedia' : 'Penuh' ?>
                                    </span>
                                </div>

                                <h5 class="fw-bold text-white mb-1"><?= htmlspecialchars($j->getFilm()->getJudul()) ?></h5>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-tag me-1"></i><?= htmlspecialchars($j->getFilm()->getGenre()) ?> &bull; 
                                    <?= $j->getFilm()->getDurasi() ?> mnt &bull; 
                                    Rating: <?= htmlspecialchars($j->getFilm()->getRatingUsia()) ?>
                                </p>

                                <div class="p-3 bg-dark rounded-3 mb-3 border border-secondary">
                                    <div class="d-flex justify-content-between mb-1 small text-light">
                                        <span><i class="bi bi-calendar3 text-info me-2"></i>Tanggal:</span>
                                        <span class="fw-bold"><?= date('d M Y', strtotime($j->getTanggal())) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 small text-light">
                                        <span><i class="bi bi-alarm text-info me-2"></i>Jam Mulai:</span>
                                        <span class="fw-bold"><?= htmlspecialchars($j->getJamMulai()) ?> WIB</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-light">
                                        <span><i class="bi bi-cash text-success me-2"></i>Harga Tiket:</span>
                                        <span class="fw-bold text-warning fs-6"><?= CineTix::rp($hargaAkhir) ?></span>
                                    </div>
                                    <?php if ($isPremium): ?>
                                        <div class="text-end small text-secondary mt-1" style="font-size: 0.75rem;">
                                            (Harga dasar <?= CineTix::rp($j->getHargaDasar()) ?> + Biaya Premium <?= CineTix::rp($studio->getBiayaTambahan()) ?>)
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-auto">
                                    <?php if ($isTersedia): ?>
                                        <a href="pesan.php?jadwal=<?= urlencode($j->getIdJadwal()) ?>" class="btn btn-warning text-dark fw-bold w-100 rounded-pill">
                                            <i class="bi bi-ticket-detailed me-1"></i> Pilih Kursi & Pesan
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100 rounded-pill" disabled>
                                            <i class="bi bi-x-circle me-1"></i> Kursi Penuh
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
