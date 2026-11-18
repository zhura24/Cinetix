<?php
// admin/index.php - Dashboard Admin & Laporan Penjualan CineTix
$pageTitle = 'Dashboard Admin - CineTix';
$activeNav = 'admin';
require_once __DIR__ . '/../classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Admin) {
    CineTix::setFlash("Akses ditolak! Halaman ini hanya untuk Administrator.", "danger");
    header("Location: ../login.php");
    exit;
}

$laporanData = $user->lihatLaporan();
$daftarPesanan = $laporanData['daftar'];
$totalPenjualan = $laporanData['total'];

$allFilm = CineTix::getAllFilm();
$allJadwal = CineTix::getAllJadwal();

$totalTiketTerjual = 0;
foreach ($daftarPesanan as $ps) {
    $totalTiketTerjual += count($ps->getTiket());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-shield-lock-fill text-danger me-2"></i>Panel Administrator
            </h3>
            <p class="text-muted small mb-0">Kode Admin: <strong><?= htmlspecialchars($user->getKodeAdmin()) ?></strong> &bull; Kelola bioskop dan lihat laporan penjualan</p>
        </div>
        <div class="d-flex gap-2">
            <a href="film.php" class="btn btn-outline-warning btn-sm rounded-pill px-3">
                <i class="bi bi-film me-1"></i> Kelola Film
            </a>
            <a href="jadwal.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-calendar3 me-1"></i> Kelola Jadwal
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-custom p-3 border-success shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Total Penjualan</span>
                    <i class="bi bi-cash-stack text-success fs-4"></i>
                </div>
                <h4 class="fw-bold text-success mb-0"><?= CineTix::rp($totalPenjualan) ?></h4>
                <span class="small text-muted">Dari pesanan terkonfirmasi</span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-custom p-3 border-warning shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Tiket Terjual</span>
                    <i class="bi bi-ticket-perforated-fill text-warning fs-4"></i>
                </div>
                <h4 class="fw-bold text-warning mb-0"><?= $totalTiketTerjual ?> Tiket</h4>
                <span class="small text-muted">Kursi bioskop terisi</span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-custom p-3 border-info shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Film Aktif</span>
                    <i class="bi bi-film text-info fs-4"></i>
                </div>
                <h4 class="fw-bold text-info mb-0"><?= count($allFilm) ?> Film</h4>
                <span class="small text-muted"><a href="film.php" class="text-info text-decoration-none">Kelola film &rarr;</a></span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-custom p-3 border-secondary shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Jadwal Penayangan</span>
                    <i class="bi bi-calendar-event text-secondary fs-4"></i>
                </div>
                <h4 class="fw-bold text-light mb-0"><?= count($allJadwal) ?> Jadwal</h4>
                <span class="small text-muted"><a href="jadwal.php" class="text-light text-decoration-none">Kelola jadwal &rarr;</a></span>
            </div>
        </div>
    </div>

    <!-- Section: Laporan Penjualan (Admin.lihatLaporan()) -->
    <div class="card card-custom p-4 shadow-sm" id="laporan">
        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-3 mb-3">
            <div>
                <h4 class="fw-bold text-white mb-1">
                    <i class="bi bi-file-earmark-bar-graph text-warning me-2"></i>Laporan Penjualan Bioskop
                </h4>
                <p class="text-muted small mb-0">Implementasi method <code>Admin.lihatLaporan()</code> dari kode Java CineTix</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Cetak Laporan
            </button>
        </div>

        <?php if (empty($daftarPesanan)): ?>
            <div class="alert alert-dark border border-secondary text-center py-4">
                <i class="bi bi-inbox fs-2 text-muted mb-2 d-block"></i>
                Belum ada transaksi penjualan yang berhasil dikonfirmasi.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-secondary small">
                            <th>ID PESANAN</th>
                            <th>PELANGGAN</th>
                            <th>FILM</th>
                            <th>STUDIO & JADWAL</th>
                            <th>KURSI</th>
                            <th>METODE BAYAR</th>
                            <th class="text-end">TOTAL HARGA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftarPesanan as $ps): ?>
                            <?php
                            $j = $ps->getJadwal();
                            $kursiStr = [];
                            foreach ($ps->getTiket() as $t) {
                                $kursiStr[] = $t->getKursi()->getNomorKursi();
                            }
                            $bayar = $ps->getPembayaran();
                            $metodeLabel = 'Tunai';
                            if ($bayar instanceof PembayaranEWallet) $metodeLabel = 'E-Wallet (' . $bayar->getProviderWallet() . ')';
                            if ($bayar instanceof PembayaranTransfer) $metodeLabel = 'Transfer (' . $bayar->getNamaBank() . ')';
                            ?>
                            <tr>
                                <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($ps->getIdPemesanan()) ?></span></td>
                                <td>
                                    <div class="fw-bold text-light"><?= htmlspecialchars($ps->getPelanggan()->getNama()) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($ps->getPelanggan()->getEmail()) ?></div>
                                </td>
                                <td class="fw-bold text-white"><?= htmlspecialchars($j->getFilm()->getJudul()) ?></td>
                                <td class="small">
                                    <div><?= htmlspecialchars($j->getStudio()->getNamaStudio()) ?></div>
                                    <div class="text-muted"><?= date('d/m/Y', strtotime($j->getTanggal())) ?> <?= htmlspecialchars($j->getJamMulai()) ?> WIB</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= implode(', ', $kursiStr) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-light"><?= htmlspecialchars($metodeLabel) ?></span>
                                </td>
                                <td class="text-end fw-bold text-warning"><?= CineTix::rp($ps->getTotalHarga()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <td colspan="6" class="fw-bold text-end text-light">TOTAL KESELURUHAN PENJUALAN:</td>
                            <td class="text-end fw-black text-warning fs-5"><?= CineTix::rp($totalPenjualan) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
