<?php
// classes/autoload.php
// Autoloader dan bootstrap untuk aplikasi CineTix

require_once __DIR__ . '/Enums.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/CineTix.php';
require_once __DIR__ . '/Pengguna.php';
require_once __DIR__ . '/Film.php';
require_once __DIR__ . '/Studio.php';
require_once __DIR__ . '/Jadwal.php';
require_once __DIR__ . '/Tiket.php';
require_once __DIR__ . '/Pembayaran.php';
require_once __DIR__ . '/Pemesanan.php';

// Inisialisasi session dan pengecekan batas waktu pembayaran
CineTix::init();
