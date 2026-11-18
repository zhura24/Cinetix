<?php
// admin/jadwal.php - Kelola Jadwal (Admin.kelolaJadwal())
$pageTitle = 'Kelola Jadwal - CineTix';
$activeNav = 'admin_jadwal';
require_once __DIR__ . '/../classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Admin) {
    CineTix::setFlash("Akses ditolak!", "danger");
    header("Location: ../login.php");
    exit;
}

$allFilm = CineTix::getAllFilm();
$allStudio = CineTix::getAllStudio();

// 1. Tambah Jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $idFilm     = $_POST['id_film'] ?? '';
    $idStudio   = $_POST['id_studio'] ?? '';
    $tanggal    = $_POST['tanggal'] ?? '';
    $jamMulai   = $_POST['jam_mulai'] ?? '';
    $hargaDasar = (float)($_POST['harga_dasar'] ?? 0);

    $film   = CineTix::getFilmById($idFilm);
    $studio = CineTix::getStudioById($idStudio);

    if ($film && $studio && !empty($tanggal) && !empty($jamMulai) && $hargaDasar > 0) {
        CineTix::tambahJadwal($film, $studio, $tanggal, $jamMulai, $hargaDasar);
        CineTix::setFlash("Jadwal penayangan untuk \"{$film->getJudul()}\" di {$studio->getNamaStudio()} berhasil ditambahkan!", "success");
    } else {
        CineTix::setFlash("Gagal menambah jadwal. Pastikan semua kolom terisi dengan benar.", "danger");
    }
    header("Location: jadwal.php");
    exit;
}

// 2. Ubah Jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah') {
    $idJadwal   = $_POST['id_jadwal'] ?? '';
    $jadwal     = CineTix::getJadwalById($idJadwal);

    if ($jadwal) {
        $idFilm     = $_POST['id_film'] ?? $jadwal->getFilm()->getIdFilm();
        $idStudio   = $_POST['id_studio'] ?? $jadwal->getStudio()->getIdStudio();
        $tanggal    = $_POST['tanggal'] ?? $jadwal->getTanggal();
        $jamMulai   = $_POST['jam_mulai'] ?? $jadwal->getJamMulai();
        $hargaDasar = (float)($_POST['harga_dasar'] ?? $jadwal->getHargaDasar());

        $film   = CineTix::getFilmById($idFilm);
        $studio = CineTix::getStudioById($idStudio);

        if ($film) $jadwal->setFilm($film);
        if ($studio) $jadwal->setStudio($studio);
        $jadwal->setTanggal($tanggal);
        $jadwal->setJamMulai($jamMulai);
        $jadwal->setHargaDasar($hargaDasar);

        CineTix::updateJadwal($jadwal);
        CineTix::setFlash("Jadwal {$jadwal->getIdJadwal()} berhasil diperbarui!", "success");
    }
    header("Location: jadwal.php");
    exit;
}

// 3. Hapus Jadwal
if (isset($_GET['hapus'])) {
    $idHapus = $_GET['hapus'];
    CineTix::hapusJadwal($idHapus);
    CineTix::setFlash("Jadwal $idHapus berhasil dihapus!", "info");
    header("Location: jadwal.php");
    exit;
}

$daftarJadwal = CineTix::getAllJadwal();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-calendar3-range-fill text-warning me-2"></i>Kelola Jadwal Penayangan
            </h3>
            <p class="text-muted small mb-0">Atur penayangan film, tanggal, jam tayang, studio, dan harga tiket</p>
        </div>
        <button class="btn btn-warning text-dark fw-bold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahJadwal">
            <i class="bi bi-plus-circle me-1"></i> Tambah Jadwal Baru
        </button>
    </div>

    <!-- Table of Schedules -->
    <div class="card card-custom p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary small">
                        <th>ID JADWAL</th>
                        <th>FILM</th>
                        <th>STUDIO</th>
                        <th>TANGGAL & JAM</th>
                        <th>HARGA DASAR</th>
                        <th>HARGA TIKET</th>
                        <th>STATUS</th>
                        <th class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarJadwal as $j): ?>
                        <?php
                        $studio = $j->getStudio();
                        $isPremium = ($studio instanceof StudioPremium);
                        $isTersedia = $j->cekKetersediaan();
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($j->getIdJadwal()) ?></span></td>
                            <td class="fw-bold text-white"><?= htmlspecialchars($j->getFilm()->getJudul()) ?></td>
                            <td>
                                <span class="badge <?= $isPremium ? 'bg-warning text-dark' : 'bg-primary' ?>">
                                    <?= htmlspecialchars($studio->getNamaStudio()) ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-light"><?= date('d M Y', strtotime($j->getTanggal())) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($j->getJamMulai()) ?> WIB</div>
                            </td>
                            <td class="text-secondary"><?= CineTix::rp($j->getHargaDasar()) ?></td>
                            <td class="fw-bold text-warning">
                                <?= CineTix::rp($j->hitungHargaTiket()) ?>
                                <?php if ($isPremium): ?>
                                    <span class="small text-muted d-block" style="font-size: 0.75rem;">(+<?= CineTix::rp($studio->getBiayaTambahan()) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $isTersedia ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                    <?= $isTersedia ? 'Tersedia' : 'Penuh' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-outline-info btn-sm rounded-pill px-3 me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEditJadwal<?= $j->getIdJadwal() ?>">
                                    <i class="bi bi-pencil-square me-1"></i> Ubah
                                </button>
                                <a href="jadwal.php?hapus=<?= urlencode($j->getIdJadwal()) ?>" 
                                   class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                   onclick="return confirm('Hapus jadwal <?= $j->getIdJadwal() ?>?');">
                                    <i class="bi bi-trash me-1"></i> Hapus
                                </a>
                            </td>
                        </tr>

                        <!-- Modal Edit Jadwal -->
                        <div class="modal fade" id="modalEditJadwal<?= $j->getIdJadwal() ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bg-dark border border-secondary text-light">
                                    <div class="modal-header border-secondary">
                                        <h5 class="modal-title fw-bold">Ubah Jadwal Penayangan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="jadwal.php">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="ubah">
                                            <input type="hidden" name="id_jadwal" value="<?= htmlspecialchars($j->getIdJadwal()) ?>">

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Pilih Film</label>
                                                <select name="id_film" class="form-select bg-dark border-secondary text-light" required>
                                                    <?php foreach ($allFilm as $f): ?>
                                                        <option value="<?= $f->getIdFilm() ?>" <?= $f->getIdFilm() === $j->getFilm()->getIdFilm() ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($f->getJudul()) ?> (<?= $f->getDurasi() ?> mnt)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Pilih Studio</label>
                                                <select name="id_studio" class="form-select bg-dark border-secondary text-light" required>
                                                    <?php foreach ($allStudio as $s): ?>
                                                        <option value="<?= $s->getIdStudio() ?>" <?= $s->getIdStudio() === $j->getStudio()->getIdStudio() ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($s->getNamaStudio()) ?> (Kapasitas <?= $s->getKapasitas() ?> Kursi)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">Tanggal</label>
                                                    <input type="date" name="tanggal" class="form-control bg-dark border-secondary text-light" value="<?= htmlspecialchars($j->getTanggal()) ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">Jam Mulai</label>
                                                    <input type="time" name="jam_mulai" class="form-control bg-dark border-secondary text-light" value="<?= htmlspecialchars($j->getJamMulai()) ?>" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Harga Dasar (Rp)</label>
                                                <input type="number" name="harga_dasar" class="form-control bg-dark border-secondary text-light" value="<?= $j->getHargaDasar() ?>" step="1000" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-secondary">
                                            <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Jadwal -->
<div class="modal fade" id="modalTambahJadwal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-warning me-2"></i>Tambah Jadwal Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="jadwal.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="tambah">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Film</label>
                        <select name="id_film" class="form-select bg-dark border-secondary text-light" required>
                            <option value="">-- Pilih Film --</option>
                            <?php foreach ($allFilm as $f): ?>
                                <option value="<?= $f->getIdFilm() ?>">
                                    <?= htmlspecialchars($f->getJudul()) ?> (<?= $f->getDurasi() ?> mnt)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Studio</label>
                        <select name="id_studio" class="form-select bg-dark border-secondary text-light" required>
                            <option value="">-- Pilih Studio --</option>
                            <?php foreach ($allStudio as $s): ?>
                                <option value="<?= $s->getIdStudio() ?>">
                                    <?= htmlspecialchars($s->getNamaStudio()) ?> (Kapasitas <?= $s->getKapasitas() ?> Kursi)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control bg-dark border-secondary text-light" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control bg-dark border-secondary text-light" value="14:00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Harga Dasar (Rp)</label>
                        <input type="number" name="harga_dasar" class="form-control bg-dark border-secondary text-light" value="40000" step="1000" required>
                        <small class="text-muted">Untuk studio premium, biaya tambahan akan dihitung otomatis.</small>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4">Tambah Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
