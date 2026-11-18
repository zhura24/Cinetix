-- database.sql
-- Skema dan Data Awal CineTix (Sistem Pemesanan Tiket Bioskop - Tugas PBO Kelompok 8)
-- Kompatibel dengan MySQL / MariaDB (phpMyAdmin) & SQLite

CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna VARCHAR(50) PRIMARY KEY,
    role VARCHAR(20) NOT NULL, -- pelanggan, admin, kasir
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    saldo DOUBLE DEFAULT 0,
    kode_admin VARCHAR(50),
    id_shift VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS film (
    id_film VARCHAR(50) PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    genre VARCHAR(50) NOT NULL,
    durasi INT NOT NULL,
    rating_usia VARCHAR(20) NOT NULL,
    poster TEXT,
    sinopsis TEXT
);

CREATE TABLE IF NOT EXISTS studio (
    id_studio VARCHAR(50) PRIMARY KEY,
    tipe VARCHAR(20) NOT NULL, -- reguler, premium
    nama_studio VARCHAR(100) NOT NULL,
    kapasitas INT NOT NULL,
    biaya_tambahan DOUBLE DEFAULT 0
);

CREATE TABLE IF NOT EXISTS jadwal (
    id_jadwal VARCHAR(50) PRIMARY KEY,
    id_film VARCHAR(50) NOT NULL,
    id_studio VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai VARCHAR(10) NOT NULL,
    harga_dasar DOUBLE NOT NULL,
    FOREIGN KEY (id_film) REFERENCES film(id_film) ON DELETE CASCADE,
    FOREIGN KEY (id_studio) REFERENCES studio(id_studio) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pemesanan (
    id_pemesanan VARCHAR(50) PRIMARY KEY,
    id_pelanggan VARCHAR(50) NOT NULL,
    id_jadwal VARCHAR(50) NOT NULL,
    waktu_pesan DATETIME NOT NULL,
    status VARCHAR(30) NOT NULL,
    total_harga DOUBLE NOT NULL,
    FOREIGN KEY (id_pelanggan) REFERENCES pengguna(id_pengguna),
    FOREIGN KEY (id_jadwal) REFERENCES jadwal(id_jadwal)
);

CREATE TABLE IF NOT EXISTS tiket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pemesanan VARCHAR(50) NOT NULL,
    nomor_kursi VARCHAR(10) NOT NULL,
    baris VARCHAR(5) NOT NULL,
    kode_tiket VARCHAR(50) NOT NULL,
    status_pakai INT DEFAULT 0,
    FOREIGN KEY (id_pemesanan) REFERENCES pemesanan(id_pemesanan) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pembayaran (
    id_pembayaran VARCHAR(50) PRIMARY KEY,
    id_pemesanan VARCHAR(50) NOT NULL,
    metode VARCHAR(30) NOT NULL,
    status VARCHAR(30) NOT NULL,
    jumlah DOUBLE NOT NULL,
    waktu_bayar DATETIME,
    uang_diterima DOUBLE DEFAULT 0,
    provider_wallet VARCHAR(50),
    no_akun VARCHAR(50),
    nama_bank VARCHAR(50),
    no_va VARCHAR(50),
    FOREIGN KEY (id_pemesanan) REFERENCES pemesanan(id_pemesanan) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS counters (
    prefix VARCHAR(20) PRIMARY KEY,
    last_number INT NOT NULL
);

-- Seed Data Awal
INSERT INTO pengguna (id_pengguna, role, nama, email, password, no_telepon, saldo, kode_admin, id_shift) VALUES
('U1', 'admin', 'Admin CineTix', 'admin@cinetix.com', 'admin123', NULL, 0, 'ADM01', NULL),
('U2', 'kasir', 'Kasir Bioskop', 'kasir@cinetix.com', 'kasir123', NULL, 0, NULL, 'PAGI'),
('U3', 'pelanggan', 'Budi', 'budi@mail.com', 'budi123', '08123456789', 200000, NULL, NULL);

INSERT INTO film (id_film, judul, genre, durasi, rating_usia, poster, sinopsis) VALUES
('F1', 'Laskar Pelangi 2', 'Drama', 120, 'SU', 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=600&q=80', 'Kisah inspiratif perjuangan pemuda pulau Belitung menggapai impian di tengah keterbatasan dengan semangat persahabatan yang abadi.'),
('F2', 'Galaxy Wars', 'Sci-Fi', 135, '13+', 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80', 'Pertempuran dahsyat antargalaksi untuk memperebutkan kristal energi terakhir demi kelangsungan alam semesta.'),
('F3', 'Misteri Rumah Tua', 'Horror', 105, '17+', 'https://images.unsplash.com/photo-1509281373149-e957c6296406?auto=format&fit=crop&w=600&q=80', 'Sekelompok mahasiswa mengungkap rahasia kelam keluarga bangsawan yang terkubur selama setengah abad di sebuah rumah tua terpencil.');

INSERT INTO studio (id_studio, tipe, nama_studio, kapasitas, biaya_tambahan) VALUES
('S1', 'reguler', 'Studio 1 (Reguler)', 10, 0),
('S2', 'premium', 'Studio 2 (Premium)', 10, 20000);

INSERT INTO jadwal (id_jadwal, id_film, id_studio, tanggal, jam_mulai, harga_dasar) VALUES
('J1', 'F1', 'S1', CURDATE(), '10:00', 40000),
('J2', 'F2', 'S2', CURDATE(), '13:00', 45000),
('J3', 'F3', 'S1', CURDATE(), '16:30', 40000),
('J4', 'F2', 'S2', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00', 50000);

INSERT INTO counters (prefix, last_number) VALUES
('U', 3), ('F', 3), ('S', 2), ('J', 4), ('P', 0), ('BYR', 0);
