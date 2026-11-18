<?php
// classes/Database.php
// SQLite Database Manager dengan auto-migration dan auto-seeding
// Bekerja langsung secara lokal tanpa perlu install/konfigurasi MySQL

class Database {
    private static ?PDO $pdo = null;
    private static string $dbFile = __DIR__ . '/../cinetix.sqlite';

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $isNew = !file_exists(self::$dbFile);
            self::$pdo = new PDO('sqlite:' . self::$dbFile);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            if ($isNew) {
                self::initSchema();
                self::seedInitialData();
            }
        }
        return self::$pdo;
    }

    public static function initSchema(): void {
        $db = self::$pdo;

        $db->exec("
            CREATE TABLE IF NOT EXISTS pengguna (
                id_pengguna TEXT PRIMARY KEY,
                role TEXT NOT NULL, -- pelanggan, admin, kasir
                nama TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                no_telepon TEXT,
                saldo REAL DEFAULT 0,
                kode_admin TEXT,
                id_shift TEXT
            );

            CREATE TABLE IF NOT EXISTS film (
                id_film TEXT PRIMARY KEY,
                judul TEXT NOT NULL,
                genre TEXT NOT NULL,
                durasi INTEGER NOT NULL,
                rating_usia TEXT NOT NULL,
                poster TEXT,
                sinopsis TEXT
            );

            CREATE TABLE IF NOT EXISTS studio (
                id_studio TEXT PRIMARY KEY,
                tipe TEXT NOT NULL, -- reguler, premium
                nama_studio TEXT NOT NULL,
                kapasitas INTEGER NOT NULL,
                biaya_tambahan REAL DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS jadwal (
                id_jadwal TEXT PRIMARY KEY,
                id_film TEXT NOT NULL,
                id_studio TEXT NOT NULL,
                tanggal TEXT NOT NULL,
                jam_mulai TEXT NOT NULL,
                harga_dasar REAL NOT NULL,
                FOREIGN KEY (id_film) REFERENCES film(id_film) ON DELETE CASCADE,
                FOREIGN KEY (id_studio) REFERENCES studio(id_studio) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS pemesanan (
                id_pemesanan TEXT PRIMARY KEY,
                id_pelanggan TEXT NOT NULL,
                id_jadwal TEXT NOT NULL,
                waktu_pesan TEXT NOT NULL,
                status TEXT NOT NULL,
                total_harga REAL NOT NULL,
                FOREIGN KEY (id_pelanggan) REFERENCES pengguna(id_pengguna),
                FOREIGN KEY (id_jadwal) REFERENCES jadwal(id_jadwal)
            );

            CREATE TABLE IF NOT EXISTS tiket (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_pemesanan TEXT NOT NULL,
                nomor_kursi TEXT NOT NULL,
                baris TEXT NOT NULL,
                kode_tiket TEXT NOT NULL,
                status_pakai INTEGER DEFAULT 0,
                FOREIGN KEY (id_pemesanan) REFERENCES pemesanan(id_pemesanan) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS pembayaran (
                id_pembayaran TEXT PRIMARY KEY,
                id_pemesanan TEXT NOT NULL,
                metode TEXT NOT NULL, -- tunai, ewallet, transfer
                status TEXT NOT NULL,
                jumlah REAL NOT NULL,
                waktu_bayar TEXT,
                uang_diterima REAL DEFAULT 0,
                provider_wallet TEXT,
                no_akun TEXT,
                nama_bank TEXT,
                no_va TEXT,
                FOREIGN KEY (id_pemesanan) REFERENCES pemesanan(id_pemesanan) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS counters (
                prefix TEXT PRIMARY KEY,
                last_number INTEGER NOT NULL
            );
        ");
    }

    public static function seedInitialData(): void {
        $db = self::$pdo;

        // 1. Data Pengguna Default sesuai Java CineTix
        // Admin: admin@cinetix.com / admin123 (ADM01)
        // Kasir: kasir@cinetix.com / kasir123 (PAGI)
        // Pelanggan: budi@mail.com / budi123 (08123456789, saldo 200000)
        $stmtPengguna = $db->prepare("
            INSERT INTO pengguna (id_pengguna, role, nama, email, password, no_telepon, saldo, kode_admin, id_shift)
            VALUES (:id, :role, :nama, :email, :password, :no_telp, :saldo, :kode_admin, :id_shift)
        ");

        $stmtPengguna->execute([
            ':id' => 'U1', ':role' => 'admin', ':nama' => 'Admin CineTix',
            ':email' => 'admin@cinetix.com', ':password' => 'admin123',
            ':no_telp' => null, ':saldo' => 0, ':kode_admin' => 'ADM01', ':id_shift' => null
        ]);

        $stmtPengguna->execute([
            ':id' => 'U2', ':role' => 'kasir', ':nama' => 'Kasir Bioskop',
            ':email' => 'kasir@cinetix.com', ':password' => 'kasir123',
            ':no_telp' => null, ':saldo' => 0, ':kode_admin' => null, ':id_shift' => 'PAGI'
        ]);

        $stmtPengguna->execute([
            ':id' => 'U3', ':role' => 'pelanggan', ':nama' => 'Budi',
            ':email' => 'budi@mail.com', ':password' => 'budi123',
            ':no_telp' => '08123456789', ':saldo' => 200000, ':kode_admin' => null, ':id_shift' => null
        ]);

        // 2. Data Film Default
        $stmtFilm = $db->prepare("
            INSERT INTO film (id_film, judul, genre, durasi, rating_usia, poster, sinopsis)
            VALUES (:id, :judul, :genre, :durasi, :rating, :poster, :sinopsis)
        ");

        $stmtFilm->execute([
            ':id' => 'F1',
            ':judul' => 'Laskar Pelangi 2',
            ':genre' => 'Drama',
            ':durasi' => 120,
            ':rating' => 'SU',
            ':poster' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=600&q=80',
            ':sinopsis' => 'Kisah inspiratif perjuangan pemuda pulau Belitung menggapai impian di tengah keterbatasan dengan semangat persahabatan yang abadi.'
        ]);

        $stmtFilm->execute([
            ':id' => 'F2',
            ':judul' => 'Galaxy Wars',
            ':genre' => 'Sci-Fi',
            ':durasi' => 135,
            ':rating' => '13+',
            ':poster' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80',
            ':sinopsis' => 'Pertempuran dahsyat antargalaksi untuk memperebutkan kristal energi terakhir demi kelangsungan alam semesta.'
        ]);

        $stmtFilm->execute([
            ':id' => 'F3',
            ':judul' => 'Misteri Rumah Tua',
            ':genre' => 'Horror',
            ':durasi' => 105,
            ':rating' => '17+',
            ':poster' => 'https://images.unsplash.com/photo-1509281373149-e957c6296406?auto=format&fit=crop&w=600&q=80',
            ':sinopsis' => 'Sekelompok mahasiswa mengungkap rahasia kelam keluarga bangsawan yang terkubur selama setengah abad di sebuah rumah tua terpencil.'
        ]);

        // 3. Data Studio Default
        $stmtStudio = $db->prepare("
            INSERT INTO studio (id_studio, tipe, nama_studio, kapasitas, biaya_tambahan)
            VALUES (:id, :tipe, :nama, :kapasitas, :biaya)
        ");

        $stmtStudio->execute([
            ':id' => 'S1', ':tipe' => 'reguler', ':nama' => 'Studio 1 (Reguler)',
            ':kapasitas' => 10, ':biaya' => 0
        ]);

        $stmtStudio->execute([
            ':id' => 'S2', ':tipe' => 'premium', ':nama' => 'Studio 2 (Premium)',
            ':kapasitas' => 10, ':biaya' => 20000
        ]);

        // 4. Data Jadwal Default
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $stmtJadwal = $db->prepare("
            INSERT INTO jadwal (id_jadwal, id_film, id_studio, tanggal, jam_mulai, harga_dasar)
            VALUES (:id, :id_film, :id_studio, :tanggal, :jam, :harga)
        ");

        $stmtJadwal->execute([
            ':id' => 'J1', ':id_film' => 'F1', ':id_studio' => 'S1',
            ':tanggal' => $today, ':jam' => '10:00', ':harga' => 40000
        ]);

        $stmtJadwal->execute([
            ':id' => 'J2', ':id_film' => 'F2', ':id_studio' => 'S2',
            ':tanggal' => $today, ':jam' => '13:00', ':harga' => 45000
        ]);

        $stmtJadwal->execute([
            ':id' => 'J3', ':id_film' => 'F3', ':id_studio' => 'S1',
            ':tanggal' => $today, ':jam' => '16:30', ':harga' => 40000
        ]);

        $stmtJadwal->execute([
            ':id' => 'J4', ':id_film' => 'F2', ':id_studio' => 'S2',
            ':tanggal' => $tomorrow, ':jam' => '19:00', ':harga' => 50000
        ]);

        // 5. Counters
        $stmtCounter = $db->prepare("INSERT INTO counters (prefix, last_number) VALUES (:prefix, :num)");
        $stmtCounter->execute([':prefix' => 'U', ':num' => 3]);
        $stmtCounter->execute([':prefix' => 'F', ':num' => 3]);
        $stmtCounter->execute([':prefix' => 'S', ':num' => 2]);
        $stmtCounter->execute([':prefix' => 'J', ':num' => 4]);
        $stmtCounter->execute([':prefix' => 'P', ':num' => 0]);
        $stmtCounter->execute([':prefix' => 'BYR', ':num' => 0]);
    }

    public static function nextId(string $prefix): string {
        $db = self::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT last_number FROM counters WHERE prefix = :p");
            $stmt->execute([':p' => $prefix]);
            $row = $stmt->fetch();

            if ($row) {
                $newNum = $row['last_number'] + 1;
                $update = $db->prepare("UPDATE counters SET last_number = :n WHERE prefix = :p");
                $update->execute([':n' => $newNum, ':p' => $prefix]);
            } else {
                $newNum = 1;
                $insert = $db->prepare("INSERT INTO counters (prefix, last_number) VALUES (:p, :n)");
                $insert->execute([':p' => $prefix, ':n' => $newNum]);
            }
            $db->commit();
            return $prefix . $newNum;
        } catch (Exception $e) {
            $db->rollBack();
            return $prefix . rand(100, 9999);
        }
    }
}
