<?php
// includes/header.php
require_once __DIR__ . '/../classes/autoload.php';

$currentUser = CineTix::getCurrentUser();
$flash = CineTix::getFlash();

// Helper base url detection
$isSubdir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/kasir/') !== false;
$baseUrl = $isSubdir ? '../' : './';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'CineTix - Pemesanan Tiket Bioskop' ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-cinetix sticky-top">
    <div class="container">
        <a class="navbar-brand brand-logo d-flex align-items-center gap-2" href="<?= $baseUrl ?>index.php">
            <i class="bi bi-film text-warning"></i>
            <span>CINETIX</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= empty($activeNav) || $activeNav === 'home' ? 'active' : '' ?>" href="<?= $baseUrl ?>index.php">
                        <i class="bi bi-camera-reels me-1"></i> Film & Jadwal
                    </a>
                </li>

                <?php if ($currentUser instanceof Pelanggan): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'riwayat' ? 'active' : '' ?>" href="<?= $baseUrl ?>riwayat.php">
                            <i class="bi bi-ticket-perforated me-1"></i> Riwayat Pesanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'topup' ? 'active' : '' ?>" href="<?= $baseUrl ?>topup.php">
                            <i class="bi bi-wallet2 me-1"></i> Top Up E-Wallet
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($currentUser instanceof Admin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'admin' ? 'active' : '' ?>" href="<?= $baseUrl ?>admin/index.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'admin_film' ? 'active' : '' ?>" href="<?= $baseUrl ?>admin/film.php">
                            <i class="bi bi-collection-play me-1"></i> Kelola Film
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'admin_jadwal' ? 'active' : '' ?>" href="<?= $baseUrl ?>admin/jadwal.php">
                            <i class="bi bi-calendar3 me-1"></i> Kelola Jadwal
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($currentUser instanceof Kasir): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'kasir' ? 'active' : '' ?>" href="<?= $baseUrl ?>kasir/index.php">
                            <i class="bi bi-cash-coin me-1"></i> Verifikasi Pembayaran
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'kasir_validasi' ? 'active' : '' ?>" href="<?= $baseUrl ?>kasir/validasi.php">
                            <i class="bi bi-upc-scan me-1"></i> Validasi & Cetak Tiket
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if ($currentUser): ?>
                    <?php if ($currentUser instanceof Pelanggan): ?>
                        <div class="badge bg-dark border border-warning text-warning px-3 py-2 rounded-pill d-flex align-items-center gap-2">
                            <i class="bi bi-wallet-fill"></i>
                            <span><?= CineTix::rp($currentUser->getSaldo()) ?></span>
                        </div>
                    <?php elseif ($currentUser instanceof Admin): ?>
                        <span class="badge bg-danger px-3 py-2 rounded-pill">
                            <i class="bi bi-shield-lock me-1"></i> Admin (<?= htmlspecialchars($currentUser->getKodeAdmin()) ?>)
                        </span>
                    <?php elseif ($currentUser instanceof Kasir): ?>
                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill">
                            <i class="bi bi-person-badge me-1"></i> Kasir (Shift: <?= htmlspecialchars($currentUser->getIdShift()) ?>)
                        </span>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($currentUser->getNama()) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <li><span class="dropdown-item-text text-light opacity-75 small"><?= htmlspecialchars($currentUser->getEmail()) ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= $baseUrl ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>login.php" class="btn btn-outline-warning btn-sm px-3 rounded-pill">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                    <a href="<?= $baseUrl ?>register.php" class="btn btn-warning btn-sm px-3 rounded-pill text-dark fw-bold">
                        Daftar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Flash Message Container -->
<div class="container mt-3">
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['pesan']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_info'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_info']) ?>
            <?php unset($_SESSION['flash_info']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>

<main class="py-4">
