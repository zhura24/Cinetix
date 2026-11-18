<?php
// topup.php - Simulasi Top Up Saldo E-Wallet Pelanggan
$pageTitle = 'Top Up Saldo E-Wallet - CineTix';
$activeNav = 'topup';
require_once __DIR__ . '/classes/autoload.php';

$user = CineTix::getCurrentUser();
if (!$user instanceof Pelanggan) {
    CineTix::setFlash("Silakan masuk sebagai Pelanggan terlebih dahulu.", "warning");
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = (float)($_POST['nominal'] ?? 0);
    if ($nominal > 0) {
        $saldoBaru = $user->getSaldo() + $nominal;
        $user->setSaldo($saldoBaru);
        CineTix::setFlash("Top up berhasil! Saldo e-wallet Anda bertambah " . CineTix::rp($nominal) . ". Saldo sekarang: " . CineTix::rp($saldoBaru), "success");
        header("Location: topup.php");
        exit;
    } else {
        CineTix::setFlash("Nominal top up tidak valid!", "danger");
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-custom p-4 shadow-sm">
                <div class="text-center mb-4">
                    <i class="bi bi-wallet2 text-warning display-4"></i>
                    <h3 class="fw-bold text-white mt-2">Top Up Saldo E-Wallet</h3>
                    <p class="text-light opacity-75 small">Simulasi isi saldo dompet digital untuk pembayaran tiket bioskop</p>
                </div>

                <div class="p-3 bg-dark rounded border border-secondary mb-4 text-center">
                    <span class="text-light opacity-75 small d-block">Saldo E-Wallet Saat Ini:</span>
                    <span class="fw-bold text-warning display-6"><?= CineTix::rp($user->getSaldo()) ?></span>
                </div>

                <form method="POST" action="topup.php">
                    <label class="form-label text-light small fw-bold mb-2">Pilih Nominal Top Up Cepat:</label>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-warning w-100 py-2 small" onclick="document.getElementById('input-nominal').value = 50000">
                                + Rp 50.000
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-warning w-100 py-2 small" onclick="document.getElementById('input-nominal').value = 100000">
                                + Rp 100.000
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-warning w-100 py-2 small" onclick="document.getElementById('input-nominal').value = 200000">
                                + Rp 200.000
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-warning w-100 py-2 small" onclick="document.getElementById('input-nominal').value = 500000">
                                + Rp 500.000
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-light small fw-bold">Atau Masukkan Nominal Sendiri (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-warning fw-bold">Rp</span>
                            <input type="number" id="input-nominal" name="nominal" class="form-control bg-dark border-secondary text-light" placeholder="50000" min="10000" step="5000" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100 rounded-pill py-2">
                        <i class="bi bi-plus-circle-fill me-1"></i> Konfirmasi Isi Saldo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
