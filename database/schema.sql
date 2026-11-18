-- ============================================================
-- Schema Database: Aplikasi Simulasi Topologi Jaringan
-- Database: db_simulasi_topologi
-- Charset: utf8mb4 / utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_simulasi_topologi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_simulasi_topologi;

-- -----------------------------------------------------------
-- Tabel users (guru dan siswa)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('guru', 'siswa') NOT NULL DEFAULT 'siswa',
    kelas VARCHAR(20) NULL COMMENT 'Hanya untuk siswa',
    nisn VARCHAR(20) NULL COMMENT 'Nomor Induk Siswa',
    nip VARCHAR(20) NULL COMMENT 'Nomor Induk Pegawai (guru)',
    avatar VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------
-- Tabel gamifikasi untuk siswa
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS siswa_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_xp INT DEFAULT 0,
    level INT DEFAULT 1,
    streak_days INT DEFAULT 0,
    last_activity DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel badges/lencana
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    icon VARCHAR(10) COMMENT 'Emoji icon',
    xp_reward INT DEFAULT 0,
    kondisi_type ENUM('xp_total', 'level', 'kuis_selesai', 'simulasi_selesai', 'streak') NOT NULL,
    kondisi_nilai INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------
-- Tabel siswa_badges (many to many)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS siswa_badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    diraih_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_siswa_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel modul materi
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS modul (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guru_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    urutan INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 0,
    thumbnail VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel konten materi
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS materi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modul_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    konten LONGTEXT NOT NULL COMMENT 'HTML content dari WYSIWYG',
    tipe ENUM('teori', 'simulasi', 'kuis') DEFAULT 'teori',
    urutan INT DEFAULT 0,
    xp_reward INT DEFAULT 10 COMMENT 'XP untuk membaca materi',
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (modul_id) REFERENCES modul(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel gambar untuk materi
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS materi_gambar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    materi_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materi_id) REFERENCES materi(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel siswa_materi_progress (tracking bacaan)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS siswa_materi_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    materi_id INT NOT NULL,
    is_completed TINYINT(1) DEFAULT 0,
    xp_earned INT DEFAULT 0,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (user_id, materi_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (materi_id) REFERENCES materi(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel bank soal
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS soal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modul_id INT NOT NULL,
    pertanyaan TEXT NOT NULL,
    tipe ENUM('pilihan_ganda', 'benar_salah') DEFAULT 'pilihan_ganda',
    gambar VARCHAR(255) NULL,
    poin INT DEFAULT 10,
    tingkat_kesulitan ENUM('mudah', 'sedang', 'sulit') DEFAULT 'sedang',
    penjelasan TEXT NULL COMMENT 'Penjelasan jawaban',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (modul_id) REFERENCES modul(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel pilihan jawaban
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS soal_pilihan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    soal_id INT NOT NULL,
    teks TEXT NOT NULL,
    is_benar TINYINT(1) DEFAULT 0,
    urutan CHAR(1) DEFAULT 'A' COMMENT 'A, B, C, D',
    FOREIGN KEY (soal_id) REFERENCES soal(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel kuis/ujian
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS kuis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modul_id INT NOT NULL,
    guru_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    durasi_menit INT DEFAULT 30,
    passing_grade INT DEFAULT 70,
    xp_reward_lulus INT DEFAULT 50,
    xp_reward_sempurna INT DEFAULT 100,
    max_attempt INT DEFAULT 3,
    acak_soal TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (modul_id) REFERENCES modul(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel soal dalam kuis (many-to-many)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS kuis_soal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kuis_id INT NOT NULL,
    soal_id INT NOT NULL,
    FOREIGN KEY (kuis_id) REFERENCES kuis(id) ON DELETE CASCADE,
    FOREIGN KEY (soal_id) REFERENCES soal(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel hasil kuis siswa
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS hasil_kuis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    kuis_id INT NOT NULL,
    attempt_ke INT DEFAULT 1,
    skor DECIMAL(5,2) DEFAULT 0,
    total_benar INT DEFAULT 0,
    total_soal INT DEFAULT 0,
    xp_earned INT DEFAULT 0,
    durasi_detik INT DEFAULT 0,
    status ENUM('lulus', 'tidak_lulus') DEFAULT 'tidak_lulus',
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kuis_id) REFERENCES kuis(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel detail jawaban kuis
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS hasil_kuis_detail (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hasil_kuis_id INT NOT NULL,
    soal_id INT NOT NULL,
    pilihan_id INT NULL,
    is_benar TINYINT(1) DEFAULT 0,
    FOREIGN KEY (hasil_kuis_id) REFERENCES hasil_kuis(id) ON DELETE CASCADE,
    FOREIGN KEY (soal_id) REFERENCES soal(id) ON DELETE CASCADE,
    FOREIGN KEY (pilihan_id) REFERENCES soal_pilihan(id) ON DELETE SET NULL
);

-- -----------------------------------------------------------
-- Tabel simulasi topologi
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS simulasi_challenge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modul_id INT NOT NULL,
    guru_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    tipe_topologi ENUM('star', 'bus', 'ring', 'mesh', 'hybrid') NOT NULL,
    instruksi TEXT NOT NULL COMMENT 'Panduan step by step',
    target_config JSON COMMENT 'Konfigurasi topologi yang benar dalam JSON',
    xp_reward INT DEFAULT 30,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (modul_id) REFERENCES modul(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel hasil simulasi
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS hasil_simulasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    challenge_id INT NOT NULL,
    canvas_data JSON COMMENT 'Data canvas yang disimpan siswa',
    is_correct TINYINT(1) DEFAULT 0,
    skor DECIMAL(5,2) DEFAULT 0,
    xp_earned INT DEFAULT 0,
    attempt_ke INT DEFAULT 1,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES simulasi_challenge(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel XP log
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS xp_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    xp_amount INT NOT NULL,
    sumber ENUM('materi', 'kuis', 'simulasi', 'badge', 'login_streak', 'kasus_keamanan') NOT NULL,
    referensi_id INT NULL,
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel Tantangan Studi Kasus Keamanan Siber
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS kasus_keamanan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guru_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    kategori VARCHAR(50) DEFAULT 'phishing',
    platform VARCHAR(50) DEFAULT 'whatsapp',
    pengirim VARCHAR(100) NOT NULL,
    isi_pesan TEXT NOT NULL,
    lampiran_nama VARCHAR(255) NULL,
    tautan_terlihat VARCHAR(255) NULL,
    tautan_asli VARCHAR(500) NULL,
    is_aman TINYINT(1) DEFAULT 0,
    ciri_bahaya TEXT NULL,
    penjelasan TEXT NOT NULL,
    xp_reward INT DEFAULT 20,
    is_published TINYINT(1) DEFAULT 1,
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Tabel Progres Kasus Keamanan Siswa
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS siswa_kasus_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    kasus_id INT NOT NULL,
    jawaban_siswa TINYINT(1) NOT NULL,
    is_correct TINYINT(1) NOT NULL,
    xp_earned INT DEFAULT 0,
    attempt_count INT DEFAULT 1,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_siswa_kasus (user_id, kasus_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kasus_id) REFERENCES kasus_keamanan(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- DATA AWAL (SEED)
-- -----------------------------------------------------------

-- Akun guru / pengelola default (password: password123)
INSERT INTO users (nama, username, email, password, role, nip, avatar) VALUES
('Angzal Maolana, S.Pd', 'angzal', 'angzal@smpn1karangmoncol.sch.id',
 '$2y$12$YKFqC1JJ0FPCKd5mU2gxM.m9z2f/rN0FhcRdHiijCjy7F.YGpH34G', 'guru', '198703122014021001', 'public/img/avatars/avatar_8.png'),
('Hana Sifakhatin, S.Pd', 'hana', 'hana@smpn1karangmoncol.sch.id',
 '$2y$12$YKFqC1JJ0FPCKd5mU2gxM.m9z2f/rN0FhcRdHiijCjy7F.YGpH34G', 'guru', '199008252015032002', 'public/img/avatars/avatar_2.png');

-- Badges awal
INSERT INTO badges (code, nama, deskripsi, icon, xp_reward, kondisi_type, kondisi_nilai) VALUES
('pemula',       'Pemula Jaringan',   'Selamat datang! Kamu telah bergabung.',    '🌱', 10,  'level',            1),
('xp_100',       'Kolektor XP',       'Kumpulkan 100 XP pertamamu!',              '⭐', 20,  'xp_total',       100),
('xp_500',       'XP Hunter',         'Kumpulkan 500 XP!',                        '🌟', 50,  'xp_total',       500),
('level_5',      'Network Apprentice','Capai Level 5!',                            '🥉', 30,  'level',            5),
('level_10',     'Network Engineer',  'Capai Level 10!',                           '🥈', 60,  'level',           10),
('level_20',     'Network Master',    'Capai Level 20!',                           '🏆', 100, 'level',           20),
('kuis_1',       'Quiz Starter',      'Selesaikan kuis pertamamu!',               '📝', 15,  'kuis_selesai',     1),
('kuis_10',      'Quiz Addict',       'Selesaikan 10 kuis!',                      '📚', 40,  'kuis_selesai',    10),
('simulasi_1',   'Network Builder',   'Buat topologi pertamamu!',                 '🔌', 20,  'simulasi_selesai', 1),
('simulasi_5',   'Topology Expert',   'Selesaikan 5 simulasi!',                   '🖧', 50,  'simulasi_selesai', 5),
('streak_7',     'Konsisten',         'Login 7 hari berturut-turut!',             '🔥', 35,  'streak',           7);

-- Modul awal
INSERT INTO modul (guru_id, judul, deskripsi, urutan, is_published) VALUES
(1, 'Pengantar Jaringan Komputer', 'Dasar-dasar jaringan komputer dan topologi', 1, 1),
(1, 'Topologi Star',  'Memahami dan mensimulasikan topologi Star',  2, 1),
(1, 'Topologi Bus',   'Memahami dan mensimulasikan topologi Bus',   3, 1),
(1, 'Topologi Ring',  'Memahami dan mensimulasikan topologi Ring',  4, 0);

-- Materi awal
INSERT INTO materi (modul_id, judul, konten, tipe, urutan, xp_reward, is_published) VALUES
(1, 'Apa itu Jaringan Komputer?',
 '<h2>Pengantar Jaringan Komputer</h2><p>Jaringan komputer adalah sekumpulan komputer dan perangkat yang saling terhubung untuk berbagi sumber daya dan informasi.</p><h3>Manfaat Jaringan Komputer</h3><ul><li>Berbagi data dan informasi</li><li>Berbagi perangkat (printer, scanner)</li><li>Komunikasi antar pengguna</li><li>Akses internet bersama</li></ul>',
 'teori', 1, 10, 1),

(1, 'Perangkat Jaringan Dasar',
 '<h2>Perangkat-Perangkat dalam Jaringan</h2><p>Beberapa perangkat utama yang digunakan dalam jaringan komputer:</p><h3>1. Router</h3><p>Router adalah perangkat yang menghubungkan jaringan yang berbeda dan menentukan jalur terbaik untuk transmisi data.</p><h3>2. Switch</h3><p>Switch adalah perangkat yang menghubungkan beberapa perangkat dalam satu jaringan lokal (LAN).</p><h3>3. Hub</h3><p>Hub adalah perangkat yang menyebarkan sinyal ke semua port.</p><h3>4. PC/Komputer</h3><p>End-device yang menjadi pengguna layanan jaringan.</p>',
 'teori', 2, 10, 1),

(2, 'Konsep Topologi Star',
 '<h2>Topologi Star (Bintang)</h2><p>Topologi star adalah topologi jaringan di mana semua node terhubung ke satu titik pusat (switch/hub).</p><h3>Karakteristik</h3><ul><li>Semua perangkat terhubung ke switch/hub pusat</li><li>Jika satu kabel putus, hanya satu perangkat yang terpengaruh</li><li>Mudah dalam troubleshooting</li></ul><h3>Kelebihan</h3><ul><li>Mudah dikelola dan dikembangkan</li><li>Keandalan tinggi</li><li>Performa baik</li></ul><h3>Kekurangan</h3><ul><li>Jika switch/hub pusat rusak, seluruh jaringan down</li><li>Membutuhkan banyak kabel</li></ul>',
 'teori', 1, 15, 1);
