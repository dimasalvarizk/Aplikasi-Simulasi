<?php
/**
 * Database Configuration & Connection Provider
 * Mendukung MySQL (default XAMPP) dan SQLite (fallback otomatis).
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'db_simulasi_topologi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $isMySQL = false;

    // 1. Coba koneksi ke MySQL XAMPP
    try {
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $tempPdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT            => 2, // 2 detik timeout agar cepat
        ]);
        // Pastikan database ada
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $tempPdo = null;

        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $isMySQL = true;
    } catch (Throwable $e) {
        // 2. Fallback ke SQLite jika MySQL tidak berjalan
        $dbDir = dirname(__DIR__) . '/database';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }
        $sqlitePath = $dbDir . '/db.sqlite';
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        if (method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
            $pdo->sqliteCreateFunction('CURDATE', function() { return date('Y-m-d'); });
        }
        $isMySQL = false;
    }

    // Inisialisasi skema & seed data jika tabel belum ada
    initializeDatabaseIfNeeded($pdo, $isMySQL);

    // Pastikan skema kasus keamanan & tabel tambahannya selalu siap (MySQL & SQLite)
    ensureKasusKeamananSchema($pdo, $isMySQL);

    return $pdo;
}

function initializeDatabaseIfNeeded(PDO $pdo, bool $isMySQL): void {
    try {
        // Cek apakah tabel users sudah ada
        $check = $pdo->query("SELECT 1 FROM users LIMIT 1");
        if ($check !== false) {
            return; // Sudah diinisialisasi
        }
    } catch (Throwable $e) {
        // Tabel belum ada, lanjut create
    }

    $autoInc = $isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT";
    $textType = "TEXT";
    $jsonType = $isMySQL ? "JSON" : "TEXT";

    // 1. Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        nama VARCHAR(100) NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'siswa',
        kelas VARCHAR(50) NULL,
        nisn VARCHAR(30) NULL,
        nip VARCHAR(30) NULL,
        avatar VARCHAR(255) NULL,
        is_active INTEGER DEFAULT 1,
        last_login DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Gamifikasi / Siswa Progress
    $pdo->exec("CREATE TABLE IF NOT EXISTS gamifikasi (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        user_id INTEGER NOT NULL,
        total_xp INTEGER DEFAULT 0,
        level INTEGER DEFAULT 1,
        streak_days INTEGER DEFAULT 0,
        last_activity DATE NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Badges
    $pdo->exec("CREATE TABLE IF NOT EXISTS badges (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        code VARCHAR(50) UNIQUE NOT NULL,
        nama VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        icon VARCHAR(50),
        xp_reward INTEGER DEFAULT 10,
        kondisi_type VARCHAR(50) DEFAULT 'xp_total',
        kondisi_nilai INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. User Badges
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_badges (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        user_id INTEGER NOT NULL,
        badge_id INTEGER NOT NULL,
        earned_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Modul
    $pdo->exec("CREATE TABLE IF NOT EXISTS modul (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        guru_id INTEGER DEFAULT 1,
        created_by INTEGER DEFAULT 1,
        judul VARCHAR(200) NOT NULL,
        deskripsi TEXT,
        urutan INTEGER DEFAULT 1,
        is_published INTEGER DEFAULT 1,
        is_active INTEGER DEFAULT 1,
        thumbnail VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 6. Materi
    $pdo->exec("CREATE TABLE IF NOT EXISTS materi (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        modul_id INTEGER NOT NULL,
        judul VARCHAR(200) NOT NULL,
        konten LONGTEXT NOT NULL,
        tipe VARCHAR(50) DEFAULT 'teori',
        urutan INTEGER DEFAULT 1,
        xp_reward INTEGER DEFAULT 10,
        is_published INTEGER DEFAULT 1,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 7. Progress Materi
    $pdo->exec("CREATE TABLE IF NOT EXISTS progress_materi (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        user_id INTEGER NOT NULL,
        materi_id INTEGER NOT NULL,
        is_completed INTEGER DEFAULT 1,
        xp_earned INTEGER DEFAULT 10,
        read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 8. Kuis
    $pdo->exec("CREATE TABLE IF NOT EXISTS kuis (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        modul_id INTEGER NOT NULL,
        guru_id INTEGER DEFAULT 1,
        created_by INTEGER DEFAULT 1,
        judul VARCHAR(200) NOT NULL,
        deskripsi TEXT,
        durasi_menit INTEGER DEFAULT 15,
        passing_grade INTEGER DEFAULT 70,
        xp_reward INTEGER DEFAULT 50,
        xp_reward_lulus INTEGER DEFAULT 50,
        xp_reward_sempurna INTEGER DEFAULT 100,
        max_attempt INTEGER DEFAULT 3,
        acak_soal INTEGER DEFAULT 1,
        is_active INTEGER DEFAULT 1,
        is_published INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 9. Soal
    $pdo->exec("CREATE TABLE IF NOT EXISTS soal (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        kuis_id INTEGER NOT NULL,
        modul_id INTEGER DEFAULT 1,
        pertanyaan TEXT NOT NULL,
        opsi_a TEXT NOT NULL,
        opsi_b TEXT NOT NULL,
        opsi_c TEXT NOT NULL,
        opsi_d TEXT NOT NULL,
        jawaban_benar VARCHAR(10) NOT NULL,
        poin INTEGER DEFAULT 10,
        urutan INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 10. Hasil Kuis
    $pdo->exec("CREATE TABLE IF NOT EXISTS hasil_kuis (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        user_id INTEGER NOT NULL,
        kuis_id INTEGER NOT NULL,
        attempt_ke INTEGER DEFAULT 1,
        skor DECIMAL(5,2) DEFAULT 0,
        jumlah_benar INTEGER DEFAULT 0,
        total_benar INTEGER DEFAULT 0,
        jumlah_soal INTEGER DEFAULT 0,
        total_soal INTEGER DEFAULT 0,
        xp_earned INTEGER DEFAULT 0,
        durasi_detik INTEGER DEFAULT 0,
        status VARCHAR(20) DEFAULT 'lulus',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 11. Simulasi Challenge
    $pdo->exec("CREATE TABLE IF NOT EXISTS simulasi_challenge (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        modul_id INTEGER NOT NULL,
        guru_id INTEGER DEFAULT 1,
        created_by INTEGER DEFAULT 1,
        judul VARCHAR(200) NOT NULL,
        deskripsi TEXT,
        tipe_topologi VARCHAR(50) NOT NULL,
        instruksi TEXT NOT NULL,
        target_config TEXT,
        xp_reward INTEGER DEFAULT 30,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 12. Simulasi Hasil
    $pdo->exec("CREATE TABLE IF NOT EXISTS simulasi_hasil (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        user_id INTEGER NOT NULL,
        challenge_id INTEGER NOT NULL,
        canvas_data TEXT,
        is_correct INTEGER DEFAULT 0,
        skor DECIMAL(5,2) DEFAULT 0,
        xp_earned INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 13. XP Log
    $pdo->exec("CREATE TABLE IF NOT EXISTS xp_log (
        id INTEGER PRIMARY KEY " . ($isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT") . ",
        user_id INTEGER NOT NULL,
        xp_amount INTEGER NOT NULL,
        sumber VARCHAR(50) NOT NULL,
        referensi_id INTEGER NULL,
        keterangan VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Alias / Compatibility views/tables for models
    try {
        $pdo->exec("CREATE VIEW IF NOT EXISTS siswa_progress AS SELECT id, user_id, total_xp, level, streak_days, last_activity, created_at, updated_at FROM gamifikasi");
        $pdo->exec("CREATE VIEW IF NOT EXISTS siswa_badges AS SELECT id, user_id, badge_id, earned_at AS diraih_at FROM user_badges");
        $pdo->exec("CREATE VIEW IF NOT EXISTS siswa_materi_progress AS SELECT id, user_id, materi_id, is_completed, xp_earned, read_at AS completed_at, created_at FROM progress_materi");
        $pdo->exec("CREATE VIEW IF NOT EXISTS hasil_simulasi AS SELECT id, user_id, challenge_id, canvas_data, is_correct, skor, xp_earned, 1 AS attempt_ke, created_at AS completed_at, created_at FROM simulasi_hasil");
    } catch (Throwable $e) {
        // Ignore view creation if already exists
    }

    // SEED DATA
    seedInitialData($pdo);
}

function seedInitialData(PDO $pdo): void {
    // Password default: password123
    $hashPass = password_hash('password123', PASSWORD_BCRYPT);

    // 1. Users (Guru / Pengelola & Siswa)
    $stmt = $pdo->prepare("INSERT INTO users (nama, username, email, password, role, nip, kelas, nisn, is_active, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
    $stmt->execute(['Angzal Maolana, S.Pd', 'angzal', 'angzal@smpn1karangmoncol.sch.id', $hashPass, 'guru', '198703122014021001', null, null, 'public/img/avatars/avatar_8.png']);
    $stmt->execute(['Hana Sifakhatin, S.Pd', 'hana', 'hana@smpn1karangmoncol.sch.id', $hashPass, 'guru', '199008252015032002', null, null, 'public/img/avatars/avatar_2.png']);
    $stmt->execute(['Ahmad Fauzi', 'siswa1', 'ahmad@simulasi.test', $hashPass, 'siswa', null, 'VII A', '0051234567', 'public/img/avatars/avatar_1.png']);
    $stmt->execute(['Siti Rahmawati', 'siswa2', 'siti@simulasi.test', $hashPass, 'siswa', null, 'VII A', '0051234568', 'public/img/avatars/avatar_2.png']);
    $stmt->execute(['Bambang Wicaksono', 'siswa3', 'bambang@simulasi.test', $hashPass, 'siswa', null, 'VII B', '0051234569', 'public/img/avatars/avatar_3.png']);

    // 2. Gamifikasi awal untuk siswa
    $stmtGamif = $pdo->prepare("INSERT INTO gamifikasi (user_id, total_xp, level, streak_days, last_activity) VALUES (?, ?, ?, ?, ?)");
    $stmtGamif->execute([2, 450, 5, 7, date('Y-m-d')]);
    $stmtGamif->execute([3, 720, 8, 12, date('Y-m-d')]);
    $stmtGamif->execute([4, 180, 2, 3, date('Y-m-d')]);

    // 3. Badges
    $badges = [
        ['pemula', 'Pemula Jaringan', 'Selamat datang! Kamu telah bergabung di NetSim Academy.', '🌱', 10, 'level', 1],
        ['xp_100', 'Kolektor XP', 'Kumpulkan 100 XP pertamamu!', '⭐', 20, 'xp_total', 100],
        ['xp_500', 'XP Hunter', 'Kumpulkan total 500 XP!', '🌟', 50, 'xp_total', 500],
        ['level_5', 'Network Apprentice', 'Capai Level 5 ke atas!', '🥉', 30, 'level', 5],
        ['level_10', 'Network Engineer', 'Capai Level 10 ke atas!', '🥈', 60, 'level', 10],
        ['level_20', 'Network Master', 'Capai Level 20!', '🏆', 100, 'level', 20],
        ['kuis_1', 'Quiz Starter', 'Selesaikan kuis pertamamu!', '📝', 15, 'kuis_selesai', 1],
        ['kuis_10', 'Quiz Addict', 'Selesaikan 10 kali kuis!', '📚', 40, 'kuis_selesai', 10],
        ['simulasi_1', 'Network Builder', 'Rangkai topologi pertamamu di canvas simulasi!', '🔌', 20, 'simulasi_selesai', 1],
        ['simulasi_5', 'Topology Expert', 'Selesaikan 5 tantangan simulasi!', '🖧', 50, 'simulasi_selesai', 5],
        ['streak_7', 'Konsistensi Juara', 'Login dan aktif 7 hari berturut-turut!', '🔥', 35, 'streak', 7],
    ];
    $stmtBadge = $pdo->prepare("INSERT INTO badges (code, nama, deskripsi, icon, xp_reward, kondisi_type, kondisi_nilai) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($badges as $b) {
        $stmtBadge->execute($b);
    }

    // 4. Berikan badge awal ke siswa
    $stmtUb = $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
    $stmtUb->execute([2, 1]); // Pemula
    $stmtUb->execute([2, 2]); // Kolektor XP
    $stmtUb->execute([2, 4]); // Apprentice
    $stmtUb->execute([2, 9]); // Network Builder
    $stmtUb->execute([3, 1]);
    $stmtUb->execute([3, 2]);
    $stmtUb->execute([3, 3]);

    // 5. Modul Materi
    $stmtModul = $pdo->prepare("INSERT INTO modul (guru_id, created_by, judul, deskripsi, urutan, is_published, is_active) VALUES (?, ?, ?, ?, ?, 1, 1)");
    $stmtModul->execute([1, 1, 'Pengantar Jaringan Komputer', 'Konsep dasar jaringan komputer, perangkat penghubung, dan pengenalan topologi.', 1]);
    $stmtModul->execute([1, 1, 'Topologi Star (Bintang)', 'Memahami karakteristik, kelebihan, kelemahan, dan implementasi topologi Star dengan switch/hub.', 2]);
    $stmtModul->execute([1, 1, 'Topologi Bus (Jalur Lurus)', 'Memahami konsep backbone kabel koaksial, terminator, dan cara kerja topologi Bus.', 3]);
    $stmtModul->execute([1, 1, 'Topologi Ring & Mesh', 'Konsep token passing pada topologi Ring dan redundansi penuh pada topologi Mesh.', 4]);

    // 6. Konten Materi
    $stmtMateri = $pdo->prepare("INSERT INTO materi (modul_id, judul, konten, tipe, urutan, xp_reward, is_published, is_active) VALUES (?, ?, ?, 'teori', ?, ?, 1, 1)");
    
    $materi1 = '<h2>Apa itu Jaringan Komputer?</h2>
    <p class="text-gray-700 leading-relaxed mb-4">Jaringan komputer adalah kumpulan dua atau lebih komputer beserta perangkat jaringan lain yang saling terhubung melalui media transmisi kabel atau nirkabel (wireless) untuk bertukar data dan berbagi sumber daya (hardware/software).</p>
    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-r-xl my-4">
        <h4 class="font-bold text-indigo-900 mb-1">💡 Tujuan Utama Jaringan:</h4>
        <ul class="list-disc list-inside text-indigo-800 text-sm space-y-1">
            <li><strong>Resource Sharing:</strong> Berbagi printer, scanner, hard drive, dan koneksi internet bersama.</li>
            <li><strong>Komunikasi Cepat:</strong> Pengiriman email, pesan instan, dan kolaborasi online.</li>
            <li><strong>Integritas Data:</strong> Sentralisasi data pada server pusat untuk backup dan keamanan terjamin.</li>
        </ul>
    </div>
    <h3>Klasifikasi Berdasarkan Area Geografis:</h3>
    <ul class="list-disc list-inside text-gray-700 space-y-2 mt-2">
        <li><strong>LAN (Local Area Network):</strong> Jaringan lokal dalam satu ruangan, gedung kantor, atau sekolah (jangkauan &lt; 1 km).</li>
        <li><strong>MAN (Metropolitan Area Network):</strong> Jaringan antar gedung dalam satu kota (10 - 50 km).</li>
        <li><strong>WAN (Wide Area Network):</strong> Jaringan skala luas antar kota, pulau, atau negara (internet).</li>
    </ul>';
    $stmtMateri->execute([1, 'Pengantar Jaringan & Klasifikasi', $materi1, 1, 15]);

    $materi2 = '<h2>Perangkat Keras Jaringan (Hardware)</h2>
    <p class="text-gray-700 leading-relaxed mb-4">Dalam membangun arsitektur topologi jaringan, berikut adalah komponen hardware utama yang wajib dipahami:</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-4">
        <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
            <span class="text-3xl">🔴</span>
            <h4 class="font-bold text-gray-800 mt-2">Router</h4>
            <p class="text-sm text-gray-600 mt-1">Bekerja di Layer 3 (Network). Menghubungkan beberapa network berbeda subnet dan menentukan jalur routing data terbaik.</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
            <span class="text-3xl">🟢</span>
            <h4 class="font-bold text-gray-800 mt-2">Switch</h4>
            <p class="text-sm text-gray-600 mt-1">Bekerja di Layer 2 (Data Link). Menghubungkan banyak PC dalam satu LAN secara cerdas menggunakan MAC Address table.</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
            <span class="text-3xl">🟡</span>
            <h4 class="font-bold text-gray-800 mt-2">Hub</h4>
            <p class="text-sm text-gray-600 mt-1">Bekerja di Layer 1 (Physical). Menyebarkan sinyal listrik (broadcast) ke semua port tanpa memeriksa tujuan paket data.</p>
        </div>
        <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
            <span class="text-3xl">💻</span>
            <h4 class="font-bold text-gray-800 mt-2">PC Client & Server</h4>
            <p class="text-sm text-gray-600 mt-1">End-device yang menjadi pengirim atau penerima paket data dalam lalu lintas jaringan.</p>
        </div>
    </div>';
    $stmtMateri->execute([1, 'Komponen Perangkat Jaringan', $materi2, 2, 15]);

    $materi3 = '<h2>Karakteristik & Analisis Topologi Star</h2>
    <p class="text-gray-700 leading-relaxed mb-4">Topologi Star (Bintang) merupakan topologi jaringan yang paling banyak digunakan di perkantoran dan laboratorium sekolah modern. Pada topologi ini, setiap host/PC terhubung langsung ke satu perangkat konsentrator pusat (Switch atau Hub) menggunakan kabel UTP (Unshielded Twisted Pair).</p>
    <div class="bg-blue-50 border-l-4 border-blue-600 p-4 rounded-r-xl my-4">
        <h4 class="font-bold text-blue-900 mb-2">⭐ Kelebihan Utama Topologi Star:</h4>
        <ul class="list-disc list-inside text-blue-800 text-sm space-y-1">
            <li><strong>Isolasi Kerusakan Mudah:</strong> Jika salah satu kabel PC putus, PC lain tidak akan terganggu sama sekali.</li>
            <li><strong>Skalabilitas Tinggi:</strong> Menambah atau memindahkan PC sangat mudah, cukup colok ke port switch yang kosong.</li>
            <li><strong>Deteksi Masalah Cepat:</strong> Lampu indikator port switch mempermudah teknisi mengidentifikasi kabel yang bermasalah.</li>
        </ul>
    </div>
    <div class="bg-amber-50 border-l-4 border-amber-600 p-4 rounded-r-xl my-4">
        <h4 class="font-bold text-amber-900 mb-2">⚠️ Kekurangan Topologi Star:</h4>
        <ul class="list-disc list-inside text-amber-800 text-sm space-y-1">
            <li><strong>Single Point of Failure:</strong> Jika Switch pusat mengalami mati listrik atau rusak total, seluruh jaringan mati.</li>
            <li><strong>Biaya Kabel Lebih Tinggi:</strong> Membutuhkan kabel individual dari setiap PC ke arah switch pusat.</li>
        </ul>
    </div>';
    $stmtMateri->execute([2, 'Konsep & Implementasi Topologi Star', $materi3, 1, 20]);

    $materi4 = '<h2>Karakteristik & Analisis Topologi Bus</h2>
    <p class="text-gray-700 leading-relaxed mb-4">Topologi Bus menghubungkan semua perangkat komputer di sepanjang satu jalur kabel utama yang disebut <em>backbone</em>. Setiap ujung kabel backbone harus ditutup dengan komponen <strong>terminator</strong> (biasanya bernilai 50 ohm) untuk menyerap pantulan sinyal listrik agar tidak terjadi tabrakan data (collision).</p>
    <div class="bg-emerald-50 border-l-4 border-emerald-600 p-4 rounded-r-xl my-4">
        <h4 class="font-bold text-emerald-900 mb-2">🚌 Kelebihan Topologi Bus:</h4>
        <ul class="list-disc list-inside text-emerald-800 text-sm space-y-1">
            <li>Hemat penggunaan kabel dibandingkan topologi Star.</li>
            <li>Biaya implementasi murah untuk jaringan berskala kecil (3-5 komputer).</li>
        </ul>
    </div>
    <div class="bg-rose-50 border-l-4 border-rose-600 p-4 rounded-r-xl my-4">
        <h4 class="font-bold text-rose-900 mb-2">❌ Kekurangan Topologi Bus:</h4>
        <ul class="list-disc list-inside text-rose-800 text-sm space-y-1">
            <li>Jika kabel utama backbone putus di satu titik, <strong>seluruh jaringan akan lumpuh</strong>.</li>
            <li>Sering terjadi tabrakan data (packet collision) saat lalu lintas padat.</li>
            <li>Troubleshooting sangat sulit dilakukan karena sulit menemukan letak kabel putus.</li>
        </ul>
    </div>';
    $stmtMateri->execute([3, 'Konsep & Analisis Topologi Bus', $materi4, 1, 20]);

    // 7. Kuis
    $stmtKuis = $pdo->prepare("INSERT INTO kuis (modul_id, guru_id, created_by, judul, deskripsi, durasi_menit, passing_grade, xp_reward, acak_soal, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)");
    $stmtKuis->execute([1, 1, 1, 'Kuis Evaluasi Dasar Jaringan Komputer', 'Uji pemahaman dasar mengenai perangkat jaringan, model layer, dan definisi jaringan.', 15, 70, 50]);
    $stmtKuis->execute([2, 1, 1, 'Kuis Penguasaan Topologi Star', 'Evaluasi pemahaman mendalam tentang kelebihan, kelemahan, dan troubleshooting topologi Star.', 20, 75, 75]);
    $stmtKuis->execute([3, 1, 1, 'Kuis Karakteristik Topologi Bus', 'Uji pemahaman mengenai kabel backbone, terminator, dan deteksi collision.', 15, 70, 50]);

    // 8. Soal Kuis
    $stmtSoal = $pdo->prepare("INSERT INTO soal (kuis_id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar, poin, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, 25, ?)");
    
    // Soal Kuis 1
    $stmtSoal->execute([1, 'Perangkat jaringan yang berfungsi menghubungkan dua jaringan dengan subnet/protokol yang berbeda dan menentukan rute terbaik adalah...', 'Switch', 'Hub', 'Router', 'Repeater', 'C', 1]);
    $stmtSoal->execute([1, 'Jaringan komputer dengan jangkauan satu ruangan laboratorium komputer sekolah termasuk dalam kategori...', 'LAN (Local Area Network)', 'MAN (Metropolitan Area Network)', 'WAN (Wide Area Network)', 'PAN (Personal Area Network)', 'A', 2]);
    $stmtSoal->execute([1, 'Perangkat yang mengirimkan paket data ke semua port secara broadcast tanpa memeriksa MAC Address tujuan adalah...', 'Switch Layer 2', 'Hub', 'Router', 'Bridge', 'B', 3]);
    $stmtSoal->execute([1, 'Media transmisi kabel yang paling umum digunakan pada jaringan LAN topologi Star modern adalah...', 'Kabel Koaksial BNC', 'Kabel Twisted Pair (UTP/STP)', 'Kabel Fiber Optic Single Mode', 'Kabel Telepon RJ-11', 'B', 4]);

    // Soal Kuis 2 (Topologi Star)
    $stmtSoal->execute([2, 'Pada topologi Star, perangkat sentral yang menjadi pusat penghubung seluruh komputer client adalah...', 'Terminator', 'Switch / Hub', 'Modem dial-up', 'NIC Client', 'B', 1]);
    $stmtSoal->execute([2, 'Apa yang terjadi jika salah satu kabel PC client putus pada topologi Star?', 'Seluruh jaringan langsung mati total', 'Hanya PC bersangkutan yang terputus, PC lain normal', 'Switch pusat akan restart otomatis', 'Terjadi tabrakan data massal', 'B', 2]);
    $stmtSoal->execute([2, 'Kelemahan paling fatal (Single Point of Failure) dari topologi Star adalah...', 'Kabel yang dibutuhkan sangat pendek', 'Kecepatan transfer data selalu lambat', 'Jika Switch/Hub pusat rusak, seluruh jaringan down', 'Tidak dapat menambah perangkat baru', 'C', 3]);
    $stmtSoal->execute([2, 'Topologi Star memiliki keunggulan dalam hal kemudahan isolasi kerusakan karena...', 'Menggunakan kabel backbone tunggal', 'Setiap perangkat memiliki kabel tersendiri ke pusat', 'Menggunakan sistem token sirkular', 'Tidak memerlukan perangkat sentral', 'B', 4]);

    // Soal Kuis 3 (Topologi Bus)
    $stmtSoal->execute([3, 'Komponen penting yang dipasang pada kedua ujung kabel utama topologi Bus untuk menyerap pantulan sinyal adalah...', 'Switch', 'Terminator', 'Repeater', 'Splitter', 'B', 1]);
    $stmtSoal->execute([3, 'Kabel utama tunggal yang menghubungkan semua perangkat pada topologi Bus disebut...', 'Backbone', 'Fiber Core', 'Twisted Wire', 'Patch Cord', 'A', 2]);

    // 9. Tantangan Simulasi Topologi
    $stmtSim = $pdo->prepare("INSERT INTO simulasi_challenge (modul_id, guru_id, created_by, judul, deskripsi, tipe_topologi, instruksi, target_config, xp_reward, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    $instruksiStar = '1. Tarik 1 unit <strong>Switch</strong> ke tengah canvas.<br>2. Tarik minimal 3 unit <strong>PC</strong> dan letakkan mengelilingi Switch.<br>3. Hubungkan setiap PC ke Switch pusat menggunakan kabel koneksi.<br>4. Klik tombol <strong>"Validasi Topologi"</strong> untuk menguji konfigurasi dan klaim XP!';
    $configStar = json_encode([
        'type' => 'star',
        'min_pcs' => 3,
        'center_device' => 'switch',
    ]);
    $stmtSim->execute([2, 1, 1, 'Tantangan 1: Rangkai Topologi Star Lab Komputer', 'Rangkai topologi Star yang menghubungkan 3 PC client ke 1 switch pusat secara simetris.', 'star', $instruksiStar, $configStar, 50]);

    $instruksiBus = '1. Tarik minimal 4 unit <strong>PC</strong> atau Server ke canvas secara horizontal.<br>2. Hubungkan perangkat secara berurutan membentuk jalur rantai/bus.<br>3. Klik tombol <strong>"Validasi Topologi"</strong> untuk memeriksa susunan topologi Bus Anda!';
    $configBus = json_encode([
        'type' => 'bus',
        'min_pcs' => 4,
    ]);
    $stmtSim->execute([3, 1, 1, 'Tantangan 2: Rangkai Jalur Topologi Bus', 'Susun 4 unit workstation yang saling terhubung dalam satu lintasan bus lurus.', 'bus', $instruksiBus, $configBus, 50]);

    $instruksiRing = '1. Tarik minimal 4 unit <strong>PC</strong> ke canvas membentuk lingkaran.<br>2. Hubungkan setiap PC ke tetangganya hingga membentuk satu loop tertutup (Ring).<br>3. Klik <strong>"Validasi Topologi"</strong> untuk memverifikasi token ring loop!';
    $configRing = json_encode([
        'type' => 'ring',
        'min_pcs' => 4,
    ]);
    $stmtSim->execute([4, 1, 1, 'Tantangan 3: Rangkai Topologi Ring Tertutup', 'Buat jalur sirkular tertutup di mana setiap node terhubung tepat ke dua tetangganya.', 'ring', $instruksiRing, $configRing, 60]);

    // 10. Catat progress materi dibaca oleh siswa1
    $stmtPm = $pdo->prepare("INSERT INTO progress_materi (user_id, materi_id, is_completed, xp_earned) VALUES (?, ?, 1, 15)");
    $stmtPm->execute([2, 1]);
    $stmtPm->execute([2, 2]);

    // 11. Catat hasil kuis awal siswa1
    $stmtHk = $pdo->prepare("INSERT INTO hasil_kuis (user_id, kuis_id, skor, jumlah_benar, total_benar, jumlah_soal, total_soal, xp_earned, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtHk->execute([2, 1, 100.0, 4, 4, 4, 4, 50, 'lulus']);
    $stmtHk->execute([3, 1, 100.0, 4, 4, 4, 4, 50, 'lulus']);
    $stmtHk->execute([3, 2, 85.0, 3, 3, 4, 4, 60, 'lulus']);

    // 12. XP Logs
    $stmtXp = $pdo->prepare("INSERT INTO xp_log (user_id, xp_amount, sumber, referensi_id, keterangan) VALUES (?, ?, ?, ?, ?)");
    $stmtXp->execute([2, 10, 'badge', 1, 'Perolehan badge: Pemula Jaringan']);
    $stmtXp->execute([2, 15, 'materi', 1, 'Membaca materi: Pengantar Jaringan & Klasifikasi']);
    $stmtXp->execute([2, 15, 'materi', 2, 'Membaca materi: Komponen Perangkat Jaringan']);
    $stmtXp->execute([2, 50, 'kuis', 1, 'Menyelesaikan kuis: Kuis Evaluasi Dasar Jaringan Komputer (Skor 100)']);
}

function ensureKasusKeamananSchema(PDO $pdo, bool $isMySQL): void {
    try {
        $check = $pdo->query("SELECT 1 FROM kasus_keamanan LIMIT 1");
        if ($check !== false) {
            return;
        }
    } catch (Throwable $e) {
        // Tabel belum ada, lanjut pembuatan
    }

    $autoInc = $isMySQL ? "AUTO_INCREMENT" : "AUTOINCREMENT";

    try {
        // 1. Buat tabel kasus_keamanan
        $pdo->exec("CREATE TABLE IF NOT EXISTS kasus_keamanan (
            id INTEGER PRIMARY KEY $autoInc,
            guru_id INTEGER NOT NULL DEFAULT 1,
            judul VARCHAR(200) NOT NULL,
            kategori VARCHAR(50) DEFAULT 'Phishing',
            platform VARCHAR(50) DEFAULT 'whatsapp',
            pengirim VARCHAR(100) NOT NULL,
            isi_pesan TEXT NOT NULL,
            link_url TEXT,
            tautan_terlihat TEXT,
            tautan_asli TEXT,
            lampiran_nama VARCHAR(255) NULL,
            is_aman INTEGER DEFAULT 0,
            ciri_bahaya TEXT NULL,
            penjelasan TEXT NOT NULL,
            xp_reward INTEGER DEFAULT 25,
            is_published INTEGER DEFAULT 1,
            is_active INTEGER DEFAULT 1,
            urutan INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 2. Buat tabel siswa_kasus_progress
        $pdo->exec("CREATE TABLE IF NOT EXISTS siswa_kasus_progress (
            id INTEGER PRIMARY KEY $autoInc,
            user_id INTEGER NOT NULL,
            kasus_id INTEGER NOT NULL,
            jawaban_siswa INTEGER NOT NULL,
            is_correct INTEGER NOT NULL,
            xp_earned INTEGER DEFAULT 0,
            attempt_count INTEGER DEFAULT 1,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 3. Masukkan seed data 5 kasus awal jika kosong
        $count = (int)$pdo->query("SELECT COUNT(*) FROM kasus_keamanan")->fetchColumn();
        if ($count === 0) {
            $cases = [
                [
                    1,
                    'Pesan WhatsApp Undangan Pernikahan (.APK)',
                    'Malware APK',
                    'whatsapp',
                    '+62 813-9821-4402 (Nomor Tak Dikenal)',
                    'Halo kak, kami bermaksud mengundang kakak dan keluarga ke acara pernikahan kami besok Minggu. Mohon dibuka surat undangannya ya kak, ditunggu kehadirannya.',
                    'Surat_Undangan_Pernikahan.apk (4.8 MB)',
                    'Surat_Undangan_Pernikahan.apk (4.8 MB)',
                    'Surat_Undangan_Pernikahan.apk (4.8 MB)',
                    'Undangan_Pernikahan.apk',
                    0,
                    'Format file berupa aplikasi Android (.apk) bukan gambar/PDF. Mengirimkan file aplikasi dari orang asing sangat berisiko.',
                    'BERBAHAYA! Surat undangan resmi tidak pernah berbentuk file aplikasi .apk. Jika file ini di-install, malware akan membaca SMS/OTP dan mengambil alih data ponsel Anda.',
                    25,
                    1
                ],
                [
                    1,
                    'SMS Hadiah Kuota Belajar 50 GB',
                    'Phishing Kuota',
                    'sms',
                    'KEMDIKBUD-INFO (0857-1122-3344)',
                    'Selamat! Nomor Anda terpilih mendapatkan Bantuan Kuota Internet Belajar 50GB Kemdikbud tahap 2. Segera aktifkan kuota Anda sebelum pukul 24.00 di tautan berikut.',
                    'http://kuotabelajar-kemdikbud.free-claim.site/login',
                    'http://kuotabelajar-kemdikbud.free-claim.site/login',
                    'http://kuotabelajar-kemdikbud.free-claim.site/login',
                    null,
                    0,
                    'Domain website palsu (.free-claim.site bukan .kemdikbud.go.id), tidak menggunakan HTTPS, dan menggunakan batas waktu darurat.',
                    'BERBAHAYA! Kemdikbud hanya menyalurkan bantuan melalui sekolah secara langsung dan domain resminya selalu berakhiran .kemdikbud.go.id. Tautan ini adalah jebakan phishing.',
                    25,
                    2
                ],
                [
                    1,
                    'Pengumuman Jadwal Ulangan SMPN 1 Karangmoncol',
                    'Link Resmi Sekolah',
                    'whatsapp',
                    'Bpk. Angzal Maolana, S.Pd (Wali Kelas VII A)',
                    "Assalamu'alaikum wr. wb. Anak-anak sekalian, jadwal Penilaian Akhir Semester (PAS) Genap telah dirilis. Silakan unduh jadwal resmi dan tata tertib ujian di website resmi sekolah kita.",
                    'https://smpn1karangmoncol.sch.id/agenda/jadwal-pas-genap',
                    'https://smpn1karangmoncol.sch.id/agenda/jadwal-pas-genap',
                    'https://smpn1karangmoncol.sch.id/agenda/jadwal-pas-genap',
                    null,
                    1,
                    'Tautan aman, domain resmi sekolah berakhiran .sch.id dan menggunakan protokol terenkripsi https://.',
                    'AMAN! Tautan ini resmi dari Guru Pengampu dan mengarah langsung ke domain sekolah berakhiran .sch.id yang terverifikasi dan aman untuk dibuka.',
                    25,
                    3
                ],
                [
                    1,
                    'Email Peringatan Akun Google Dibekukan',
                    'Phishing Akun',
                    'email',
                    'Google Security Alert <support@g00gle-security-center.com>',
                    'PERINGATAN: Kami mendeteksi aktivitas login mencurigakan pada akun Google Anda. Akun Anda akan dinonaktifkan dalam 24 jam kecuali Anda memverifikasi kata sandi sekarang.',
                    'http://verify-myaccount-google.com/auth/login',
                    'http://verify-myaccount-google.com/auth/login',
                    'http://verify-myaccount-google.com/auth/login',
                    null,
                    0,
                    'Alamat email pengirim palsu (menggunakan angka 0 g00gle bukan google.com), URL login palsu, dan menakut-nakuti pengguna.',
                    'BERBAHAYA! Google tidak pernah mengirimkan email darurat dengan tautan login di luar domain resmi google.com. Trik ini sengaja dibuat agar Anda panik lalu memasukkan kata sandi.',
                    25,
                    4
                ],
                [
                    1,
                    'Tawaran Diamond & Skin Game Gratis',
                    'Scam Game Online',
                    'medsos',
                    'Event MLBB Official (@diamond_gratis_mlbb)',
                    'Spesial Ulang Tahun! Klaim 10.000 Diamond dan Skin Collector gratis hari ini. Cukup login dan masukkan ID serta kata sandi akun Moonton Anda di tautan event ini!',
                    'http://event-claim-diamond-mlbb.xyz/spin',
                    'http://event-claim-diamond-mlbb.xyz/spin',
                    'http://event-claim-diamond-mlbb.xyz/spin',
                    null,
                    0,
                    'Meminta password akun game, domain gratisan (.xyz), dan janji hadiah yang tidak masuk akal.',
                    'BERBAHAYA! Developer game resmi tidak pernah meminta kata sandi pemain untuk membagikan hadiah. Website ini bertujuan membajak akun game Anda.',
                    25,
                    5
                ],
            ];

            $stmt = $pdo->prepare(
                "INSERT INTO kasus_keamanan 
                 (guru_id, judul, kategori, platform, pengirim, isi_pesan, link_url, tautan_terlihat, tautan_asli, lampiran_nama, is_aman, ciri_bahaya, penjelasan, xp_reward, is_published, is_active, urutan)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?)"
            );

            foreach ($cases as $c) {
                $stmt->execute($c);
            }
        }

        // 4. Pastikan badge 'cyber_detective' ada
        $cekBadge = $pdo->query("SELECT id FROM badges WHERE code = 'cyber_detective' LIMIT 1");
        if ($cekBadge && !$cekBadge->fetch()) {
            $pdo->prepare("INSERT INTO badges (code, nama, deskripsi, icon, xp_reward, kondisi_type, kondisi_nilai) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute(['cyber_detective', 'Detektif Siber', 'Pecahkan tantangan analisis keamanan internet & anti-phishing!', '🕵️‍♂️', 50, 'xp_total', 10]);
        }
    } catch (Throwable $e) {
        // Abaikan jika sudah ada
    }
}
