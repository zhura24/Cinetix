<?php
// admin/film.php - Kelola Film (Admin.kelolaFilm())
$pageTitle = 'Kelola Film - CineTix';
$activeNav = 'admin_film';
require_once __DIR__ . '/../classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Admin) {
    CineTix::setFlash("Akses ditolak!", "danger");
    header("Location: ../login.php");
    exit;
}

// 1. Tambah Film
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $judul      = trim($_POST['judul'] ?? '');
    $genre      = trim($_POST['genre'] ?? '');
    $durasi     = (int)($_POST['durasi'] ?? 0);
    $ratingUsia = trim($_POST['rating_usia'] ?? 'SU');
    $poster     = trim($_POST['poster'] ?? '');
    $sinopsis   = trim($_POST['sinopsis'] ?? '');

    if (!empty($judul) && !empty($genre) && $durasi > 0) {
        CineTix::tambahFilm($judul, $genre, $durasi, $ratingUsia, $poster, $sinopsis);
        CineTix::setFlash("Film \"$judul\" berhasil ditambahkan!", "success");
    } else {
        CineTix::setFlash("Form tidak lengkap!", "danger");
    }
    header("Location: film.php");
    exit;
}

// 2. Ubah Rating Usia / Detail Film
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah') {
    $idFilm     = $_POST['id_film'] ?? '';
    $film       = CineTix::getFilmById($idFilm);
    if ($film) {
        $film->setJudul(trim($_POST['judul'] ?? $film->getJudul()));
        $film->setGenre(trim($_POST['genre'] ?? $film->getGenre()));
        $film->setDurasi((int)($_POST['durasi'] ?? $film->getDurasi()));
        $film->setRatingUsia(trim($_POST['rating_usia'] ?? $film->getRatingUsia()));
        if (!empty($_POST['poster'])) $film->setPoster(trim($_POST['poster']));
        if (!empty($_POST['sinopsis'])) $film->setSinopsis(trim($_POST['sinopsis']));

        CineTix::updateFilm($film);
        CineTix::setFlash("Data film \"{$film->getJudul()}\" berhasil diperbarui!", "success");
    }
    header("Location: film.php");
    exit;
}

// 3. Hapus Film
if (isset($_GET['hapus'])) {
    $idHapus = $_GET['hapus'];
    $filmHapus = CineTix::getFilmById($idHapus);
    if ($filmHapus) {
        CineTix::hapusFilm($idHapus);
        CineTix::setFlash("Film \"{$filmHapus->getJudul()}\" dan jadwal terkait berhasil dihapus!", "info");
    }
    header("Location: film.php");
    exit;
}

$daftarFilm = CineTix::getAllFilm();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-collection-play-fill text-warning me-2"></i>Kelola Film
            </h3>
            <p class="text-muted small mb-0">Implementasi method <code>Admin.kelolaFilm()</code> (Tambah, Ubah Rating Usia, Hapus)</p>
        </div>
        <button class="btn btn-warning text-dark fw-bold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahFilm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Film Baru
        </button>
    </div>

    <!-- Table of Films -->
    <div class="card card-custom p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary small">
                        <th>POSTER</th>
                        <th>ID FILM</th>
                        <th>JUDUL</th>
                        <th>GENRE</th>
                        <th>DURASI</th>
                        <th>RATING USIA</th>
                        <th class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarFilm as $f): ?>
                        <tr>
                            <td style="width: 70px;">
                                <img src="<?= htmlspecialchars($f->getPoster()) ?>" alt="Poster" class="rounded" style="width: 50px; height: 70px; object-fit: cover;">
                            </td>
                            <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($f->getIdFilm()) ?></span></td>
                            <td class="fw-bold text-white"><?= htmlspecialchars($f->getJudul()) ?></td>
                            <td><span class="badge bg-dark border border-secondary text-light"><?= htmlspecialchars($f->getGenre()) ?></span></td>
                            <td class="text-light"><?= $f->getDurasi() ?> menit</td>
                            <td>
                                <span class="badge bg-warning text-dark fw-bold"><?= htmlspecialchars($f->getRatingUsia()) ?></span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-outline-info btn-sm rounded-pill px-3 me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEditFilm<?= $f->getIdFilm() ?>">
                                    <i class="bi bi-pencil-square me-1"></i> Ubah
                                </button>
                                <a href="film.php?hapus=<?= urlencode($f->getIdFilm()) ?>" 
                                   class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                   onclick="return confirm('Hapus film <?= addslashes($f->getJudul()) ?>? Semua jadwal terkait juga akan terhapus.');">
                                    <i class="bi bi-trash me-1"></i> Hapus
                                </a>
                            </td>
                        </tr>

                        <!-- Modal Edit Film -->
                        <div class="modal fade" id="modalEditFilm<?= $f->getIdFilm() ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bg-dark border border-secondary text-light">
                                    <div class="modal-header border-secondary">
                                        <h5 class="modal-title fw-bold">Ubah Data Film</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="film.php">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="ubah">
                                            <input type="hidden" name="id_film" value="<?= htmlspecialchars($f->getIdFilm()) ?>">

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Judul Film</label>
                                                <input type="text" name="judul" class="form-control bg-dark border-secondary text-light" value="<?= htmlspecialchars($f->getJudul()) ?>" required>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">Genre</label>
                                                    <input type="text" name="genre" class="form-control bg-dark border-secondary text-light" value="<?= htmlspecialchars($f->getGenre()) ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">Durasi (Menit)</label>
                                                    <input type="number" name="durasi" class="form-control bg-dark border-secondary text-light" value="<?= $f->getDurasi() ?>" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Rating Usia</label>
                                                <select name="rating_usia" class="form-select bg-dark border-secondary text-light">
                                                    <option value="SU" <?= $f->getRatingUsia() === 'SU' ? 'selected' : '' ?>>Semua Umur (SU)</option>
                                                    <option value="13+" <?= $f->getRatingUsia() === '13+' ? 'selected' : '' ?>>Remaja (13+)</option>
                                                    <option value="17+" <?= $f->getRatingUsia() === '17+' ? 'selected' : '' ?>>Dewasa (17+)</option>
                                                    <option value="21+" <?= $f->getRatingUsia() === '21+' ? 'selected' : '' ?>>21+</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">URL Poster (Opsional)</label>
                                                <input type="url" name="poster" class="form-control bg-dark border-secondary text-light" value="<?= htmlspecialchars($f->getPoster()) ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Sinopsis</label>
                                                <textarea name="sinopsis" class="form-control bg-dark border-secondary text-light" rows="3"><?= htmlspecialchars($f->getSinopsis()) ?></textarea>
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

<!-- Modal Tambah Film -->
<div class="modal fade" id="modalTambahFilm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-warning me-2"></i>Tambah Film Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="film.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="tambah">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Judul Film</label>
                        <input type="text" name="judul" class="form-control bg-dark border-secondary text-light" placeholder="Contoh: Petualangan Sherina 2" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Genre</label>
                            <input type="text" name="genre" class="form-control bg-dark border-secondary text-light" placeholder="Drama, Action, Sci-Fi" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Durasi (Menit)</label>
                            <input type="number" name="durasi" class="form-control bg-dark border-secondary text-light" placeholder="120" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Rating Usia</label>
                        <select name="rating_usia" class="form-select bg-dark border-secondary text-light">
                            <option value="SU">Semua Umur (SU)</option>
                            <option value="13+">Remaja (13+)</option>
                            <option value="17+">Dewasa (17+)</option>
                            <option value="21+">21+</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">URL Poster Gambar (Opsional)</label>
                        <input type="url" name="poster" class="form-control bg-dark border-secondary text-light" placeholder="https://example.com/poster.jpg">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Sinopsis Singkat</label>
                        <textarea name="sinopsis" class="form-control bg-dark border-secondary text-light" rows="3" placeholder="Tuliskan ringkasan cerita film..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4">Tambah Film</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
