<?php
// register.php - Pendaftaran Pelanggan Baru CineTix
$pageTitle = 'Registrasi Pelanggan - CineTix';
require_once __DIR__ . '/classes/autoload.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $telepon  = trim($_POST['telepon'] ?? '');

    if (empty($nama) || empty($email) || empty($password) || empty($telepon)) {
        $error = "Semua kolom wajib diisi!";
    } else {
        $pelanggan = CineTix::registerPelanggan($nama, $email, $password, $telepon);
        if ($pelanggan) {
            CineTix::setFlash("Registrasi berhasil! Silakan masuk dengan akun Anda.", "success");
            header("Location: login.php");
            exit;
        } else {
            $error = "Gagal mendaftar. Email mungkin sudah terdaftar.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-custom p-4 shadow-lg">
                <div class="text-center mb-4">
                    <i class="bi bi-person-plus text-warning display-4"></i>
                    <h3 class="fw-bold text-white mt-2">Daftar Akun Pelanggan</h3>
                    <p class="text-muted small">Dapatkan saldo awal e-wallet simulasi Rp 200.000!</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label text-light small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control bg-dark border-secondary text-light" placeholder="Contoh: Budi Santoso" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light small fw-bold">Email</label>
                        <input type="email" name="email" class="form-control bg-dark border-secondary text-light" placeholder="budi@mail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light small fw-bold">Nomor Telepon</label>
                        <input type="tel" name="telepon" class="form-control bg-dark border-secondary text-light" placeholder="08123456789" required value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-light small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control bg-dark border-secondary text-light" placeholder="Buat password yang kuat" required>
                    </div>

                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100 rounded-pill py-2">
                        <i class="bi bi-check-circle me-1"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top border-secondary text-center">
                    <p class="text-muted small mb-0">Sudah punya akun? <a href="login.php" class="text-warning fw-bold">Masuk di sini</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
