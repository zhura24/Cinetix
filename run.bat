@echo off
title CineTix - Web Bioskop Lokal
cd /d "%~dp0"
echo ======================================================================
echo         CINETIX - SISTEM PEMESANAN TIKET BIOSKOP (VERSI WEB PHP)
echo             Tugas Kelompok Pemrograman Berorientasi Objek (PBO)
echo ======================================================================
echo.
echo Memulai server lokal PHP pada http://localhost:8000 ...
echo Halaman browser akan dibuka secara otomatis.
echo.
echo Akun Demo:
echo   - Pelanggan : budi@mail.com / budi123 (Saldo: Rp 200.000)
echo   - Kasir     : kasir@cinetix.com / kasir123 (Shift: PAGI)
echo   - Admin     : admin@cinetix.com / admin123 (Kode: ADM01)
echo.
echo [INFO] Tekan tombol CTRL + C untuk menghentikan server.
echo ======================================================================
echo.

start "" "http://localhost:8000"
php -S localhost:8000
pause
