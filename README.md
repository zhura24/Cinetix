# CineTix - Sistem Pemesanan Tiket Bioskop (Versi Web PHP)

Proyek ini merupakan konversi lengkap dari kode Java **CineTix (Text UI)** ke dalam **Website PHP Modern berbasis Pemrograman Berorientasi Objek (PBO / OOP)**. Sesuai dengan Laporan Tahap 1 PBO Kelompok 8.

---

## 🌟 Fitur Utama & Kesesuaian Konsep PBO

Aplikasi ini mengimplementasikan **16 Class (termasuk 3 Abstract Class dan 2 Enum)** dengan struktur pewarisan (*inheritance*), polimorfisme (*polymorphism*), enkapsulasi (*encapsulation*), dan komposisi (*composition*):

### 1. Hierarki Kelas & Polimorfisme
| Kategori | Kelas / Enum | Keterangan & Penerapan OOP |
| :--- | :--- | :--- |
| **Enum** | `StatusKursi` | Nilai: `TERSEDIA`, `DITAHAN`, `TERPESAN`. |
| **Enum** | `StatusPesanan` | Nilai: `MENUNGGU_PEMBAYARAN`, `DIKONFIRMASI`, `DIBATALKAN`. |
| **Pengguna** | `abstract class Pengguna` | Kelas abstrak induk dengan atribut terenkapsulasi (`idPengguna`, `nama`, `email`, `password`) dan method abstrak `tampilkanMenu()`. |
| | `class Pelanggan extends Pengguna` | Memiliki atribut saldo e-wallet dan method `pesanTiket()`, `batalkanPesanan()`, `lihatRiwayat()`. |
| | `class Admin extends Pengguna` | Memiliki `kodeAdmin` dan method `kelolaFilm()`, `kelolaJadwal()`, `lihatLaporan()`. |
| | `class Kasir extends Pengguna` | Memiliki `idShift` dan method `verifikasiPembayaran()`, `cetakTiket()`. |
| **Film & Jadwal** | `class Film` | Entitas data film (`idFilm`, `judul`, `genre`, `durasi`, `ratingUsia`, `poster`, `sinopsis`). |
| | `class Jadwal` | Menghubungkan Film dan Studio dengan tanggal, jam mulai, dan harga dasar. Asosiasi: `Film 1 - * Jadwal`, `Studio 1 - * Jadwal`. |
| **Studio & Kursi** | `abstract class Studio` | Kelas abstrak dengan method abstrak `hitungHarga($hargaDasar)`. Memiliki **Komposisi 1..* Kursi**. |
| | `class StudioReguler extends Studio` | **Polimorfisme**: Menghitung harga tiket = harga dasar. |
| | `class StudioPremium extends Studio` | **Polimorfisme**: Menghitung harga tiket = harga dasar + biaya tambahan premium. |
| | `class Kursi` | Bagian komposisi studio (`nomorKursi`, `baris`, `status`). Method: `tahan()`, `pesan()`, `lepas()`. |
| **Pemesanan & Tiket**| `class Pemesanan` | Menghubungkan Pelanggan, Jadwal, dan Pembayaran. Memiliki **Komposisi 1..* Tiket**. Method: `hitungTotal()`, `tambahKursi()`, `konfirmasi()`, `batalkan()`. |
| | `class Tiket` | Relasi `Tiket * - 1 Kursi`. Method: `generateKode()`, `cetak()`, `validasi()`. |
| **Pembayaran** | `abstract class Pembayaran` | Kelas abstrak induk pembayaran dengan method abstrak `proses()`. |
| | `class PembayaranTunai extends Pembayaran` | **Polimorfisme**: Validasi uang diterima & hitung kembalian di kasir. |
| | `class PembayaranEWallet extends Pembayaran` | **Polimorfisme**: Validasi kecukupan saldo & otomatis potong saldo pelanggan. |
| | `class PembayaranTransfer extends Pembayaran` | **Polimorfisme**: Pembuatan nomor Virtual Account (`8808...`) & simulasi cek mutasi. |
| **Utilitas** | `class CineTix` | Service utama pengelola counter ID (`U1`, `F1`, `S1`, `J1`, `P1`, `BYR1`), format mata uang Rupiah, dan pengecekan batas waktu pembayaran 15 menit (`cekWaktuHabis()`). |

---

## 🔑 Akun Bawaan (Default Login)

Tersedia tombol **1-Klik Demo Login** di halaman login dan footer untuk memudahkan pengujian cepat:

| Role / Peran | Email | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Pelanggan** | `budi@mail.com` | `budi123` | Saldo awal e-wallet: **Rp 200.000**, No. HP: `08123456789` |
| **Kasir** | `kasir@cinetix.com` | `kasir123` | Shift: **PAGI** |
| **Admin** | `admin@cinetix.com` | `admin123` | Kode Admin: **ADM01** |

---

## 🚀 Cara Menjalankan Website Secara Lokal

Aplikasi menggunakan database lokal SQLite (`cinetix.sqlite`) bawaan PHP yang otomatis dibuat dan diisi data awal saat pertama kali dijalankan (**tanpa perlu install/konfigurasi MySQL atau import database**).

### Cara 1: Menggunakan File Batch (Paling Mudah di Windows)
1. Cukup klik ganda (double-click) pada file **`run.bat`**.
2. Browser akan otomatis terbuka ke alamat `http://localhost:8000`.

### Cara 2: Menggunakan Terminal / Command Prompt / PowerShell
1. Buka Terminal di folder proyek ini:
   ```bash
   cd "c:\Users\user\Downloads\PBO Tugas Kelompok"
   ```
2. Jalankan built-in server PHP:
   ```bash
   php -S localhost:8000
   ```
3. Buka browser dan akses: `http://localhost:8000`

### Cara 3: Menggunakan XAMPP
1. Salin (copy) folder `PBO Tugas Kelompok` ke dalam folder `htdocs` XAMPP (misal `C:\xampp\htdocs\cinetix`).
2. Nyalakan Apache di XAMPP Control Panel.
3. Buka browser dan akses: `http://localhost/cinetix/`
4. *(Opsional)* Jika dosen mewajibkan MySQL, file skema dan data awal MySQL telah disediakan di file `database.sql`.

---

## 🖥️ Panduan Alur Pengujian Fitur

### 1. Alur Pengujian Pelanggan:
1. Login sebagai **Budi** (`budi@mail.com` / `budi123`).
2. Pada halaman utama, lihat daftar film dan pilih salah satu jadwal tayang -> klik **Pilih Kursi & Pesan**.
3. Di layar denah kursi interaktif:
   - Klik kursi yang tersedia (contoh: `A1`, `A2`). Total harga akan terhitung otomatis sesuai jenis studio (Reguler vs Premium).
   - Klik tombol **Lanjut ke Pembayaran**.
4. Di halaman pembayaran, kursi Anda berstatus **DITAHAN** dengan batas waktu 15 menit (ada timer hitung mundur):
   - **Opsi E-Wallet**: Saldo akan dipotong otomatis jika cukup -> Tiket langsung terbit.
   - **Opsi Transfer Bank**: Menghasilkan nomor Virtual Account `8808...` -> Tiket langsung terbit.
   - **Opsi Tunai di Kasir**: Pesanan menunggu verifikasi uang tunai di kasir bioskop.
5. Buka menu **Riwayat Pesanan**:
   - Klik **Cetak / Lihat Tiket** untuk mencetak e-tiket resmi lengkap dengan barcode dan kode tiket unik (`TKT-XXXXXX`).
   - Tersedia tombol **Batalkan Pesanan** untuk pesanan yang belum dibayar (kursi otomatis dilepas kembali).
6. Buka menu **Top Up E-Wallet** untuk menambah saldo simulasi.

### 2. Alur Pengujian Kasir:
1. Login sebagai **Kasir** (`kasir@cinetix.com` / `kasir123`).
2. Masuk ke menu **Verifikasi Pembayaran**:
   - Terlihat daftar pesanan yang sedang menunggu pembayaran tunai.
   - Pilih pesanan atau masukkan kode pesanan (misal: `P1`).
   - Masukkan nominal uang yang diterima dari penonton -> sistem otomatis menghitung kembalian dan mengonfirmasi pesanan.
3. Masuk ke menu **Validasi & Cetak Tiket**:
   - Masukkan kode tiket (misal: `TKT-XXXXXX`) untuk simulasi Gate Check-in saat penonton masuk studio.
   - Tiket pertama kali akan berstatus **VALID**. Jika dimasukkan lagi, sistem akan menolak dengan pesan **TIKET SUDAH DIPAKAI**.

### 3. Alur Pengujian Admin:
1. Login sebagai **Admin** (`admin@cinetix.com` / `admin123`).
2. Masuk ke **Dashboard Admin**:
   - Lihat ringkasan total pendapatan penjualan, jumlah tiket terjual, dan tabel **Laporan Penjualan** (`Admin.lihatLaporan()`).
3. Masuk ke **Kelola Film**:
   - Tambah film baru, ubah rating usia/detail film, atau hapus film.
4. Masuk ke **Kelola Jadwal**:
   - Tambah jadwal penayangan baru (pilih film, pilih studio reguler atau premium, tanggal, jam, dan harga dasar).

---

## 📁 Struktur Berkas Proyek

```
PBO Tugas Kelompok/
├── classes/
│   ├── Enums.php             # Enum StatusKursi & StatusPesanan
│   ├── Pengguna.php          # abstract Pengguna, Pelanggan, Admin, Kasir
│   ├── Film.php              # Class Film
│   ├── Studio.php            # abstract Studio, StudioReguler, StudioPremium, Kursi
│   ├── Jadwal.php            # Class Jadwal
│   ├── Tiket.php             # Class Tiket (kode TKT-XXXXXX, validasi, cetak)
│   ├── Pembayaran.php        # abstract Pembayaran, Tunai, EWallet, Transfer
│   ├── Pemesanan.php         # Class Pemesanan
│   ├── CineTix.php           # Service utama, ID generator, format Rupiah, timeout
│   ├── Database.php          # Koneksi SQLite & auto-seed
│   └── autoload.php          # Bootstrap & autoloader kelas
├── includes/
│   ├── header.php            # Navigasi responsif, role badge, saldo pill, flash alert
│   └── footer.php            # Footer informasi PBO & 1-klik akun demo
├── assets/
│   ├── css/style.css         # Styling modern bertema bioskop gelap (dark cinema UI)
│   └── js/script.js          # Pemilihan kursi interaktif & countdown timer
├── admin/
│   ├── index.php             # Dashboard Admin & Laporan Penjualan
│   ├── film.php              # CRUD Film (Admin.kelolaFilm())
│   └── jadwal.php            # CRUD Jadwal (Admin.kelolaJadwal())
├── kasir/
│   ├── index.php             # Verifikasi Pembayaran Tunai & Kembalian
│   └── validasi.php          # Validasi Tiket (Gate Check-in) & Cetak Kasir
├── index.php                 # Katalog Film & Jadwal Tayang
├── login.php                 # Form Login dengan tombol demo cepat
├── register.php              # Registrasi Pelanggan baru (+saldo Rp 200.000)
├── logout.php                # Logout pengguna
├── pesan.php                 # Denah Kursi Bioskop & Pembuatan Pesanan
├── bayar.php                 # Pembayaran (Tunai, E-Wallet, Transfer VA)
├── riwayat.php               # Riwayat Pemesanan & Pembatalan Tiket Pelanggan
├── tiket.php                 # Tampilan cetak E-Tiket dengan Barcode
├── topup.php                 # Simulasi isi saldo dompet digital E-Wallet
├── database.sql              # Skema SQL MySQL jika diperlukan untuk tugas
├── run.bat                   # Launcher 1-klik server lokal Windows
└── README.md                 # Dokumentasi lengkap proyek
```
