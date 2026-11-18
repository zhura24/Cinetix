<?php
// login.php - Login Pengguna CineTix
$pageTitle = 'Login - CineTix';
require_once __DIR__ . '/classes/autoload.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $user = CineTix::login($email, $password);
    if ($user) {
        // Polimorfisme: redirect sesuai menu pengguna
        $target = $user->tampilkanMenu();
        header("Location: $target");
        exit;
    } else {
        $error = "Email atau password salah!";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-custom p-4 shadow-lg">
                <div class="text-center mb-4">
                    <i class="bi bi-person-lock text-warning display-4"></i>
                    <h3 class="fw-bold text-white mt-2">Masuk ke CineTix</h3>
                    <p class="text-muted small">Gunakan akun Anda untuk memesan tiket bioskop</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label text-light small fw-bold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control bg-dark border-secondary text-light" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-light small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-key"></i></span>
                            <input type="password" name="password" class="form-control bg-dark border-secondary text-light" placeholder="Masukkan password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100 rounded-pill py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top border-secondary text-center">
                    <p class="text-muted small mb-2">Belum memiliki akun?</p>
                    <a href="register.php" class="btn btn-outline-light btn-sm rounded-pill px-4">
                        Daftar Sebagai Pelanggan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
