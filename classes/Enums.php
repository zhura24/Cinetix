<?php
// classes/Enums.php
// Sesuai Laporan PBO Tahap 1 CineTix - Status Kursi & Status Pesanan

enum StatusKursi: string {
    case TERSEDIA = 'TERSEDIA';
    case DITAHAN  = 'DITAHAN';
    case TERPESAN = 'TERPESAN';
}

enum StatusPesanan: string {
    case MENUNGGU_PEMBAYARAN = 'MENUNGGU_PEMBAYARAN';
    case DIKONFIRMASI        = 'DIKONFIRMASI';
    case DIBATALKAN          = 'DIBATALKAN';
}
