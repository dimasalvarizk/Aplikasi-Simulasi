<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/response.php';

class AuthController
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Tampilkan halaman login
     */
    public function index()
    {
        if (isLoggedIn()) {
            $user = getUser();
            redirect(base_url($user['role'] === 'guru' ? 'guru/dashboard' : 'siswa/dashboard'));
        }

        $csrf_token = generateToken();

        $data = [
            'title'      => 'Login - ' . APP_NAME,
            'csrf_token' => $csrf_token,
            'flash'      => [
                'error'   => getFlash('error'),
                'success' => getFlash('success'),
            ],
        ];

        render('auth/login', $data);
    }

    /**
     * Proses login (POST)
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('login'));
        }

        $token = $_POST['csrf_token'] ?? '';

        if (!verifyToken($token)) {
            setFlash('error', 'Sesi login tidak valid. Silakan coba lagi.');
            redirect(base_url('login'));
        }

        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            setFlash('error', 'Username dan password wajib diisi.');
            redirect(base_url('login'));
        }

        // Dukung alias guru1 -> angzal dan guru2 -> hana
        $alias = $username;
        if ($username === 'guru1') {
            $alias = 'angzal';
        } elseif ($username === 'guru2') {
            $alias = 'hana';
        }

        $stmt = $this->db->prepare(
            "SELECT u.*, COALESCE(g.total_xp, 0) AS total_xp, COALESCE(g.level, 1) AS level
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             WHERE u.username = :username OR u.username = :alias OR u.email = :email OR u.nip = :nip
             LIMIT 1"
        );
        $stmt->execute([
            ':username' => $username,
            ':alias'    => $alias,
            ':email'    => $username,
            ':nip'      => $username,
        ]);
        $user = $stmt->fetch();

        if (!$user || empty($user['is_active'])) {
            setFlash('error', 'Akun tidak ditemukan atau tidak aktif.');
            redirect(base_url('login'));
        }

        if (!verifyPassword($password, $user['password'])) {
            setFlash('error', 'Kata sandi yang Anda masukkan salah.');
            redirect(base_url('login'));
        }

        // Set session user
        setUser([
            'id'       => (int)$user['id'],
            'user_id'  => (int)$user['id'],
            'nama'     => $user['nama'],
            'username' => $user['username'],
            'role'     => $user['role'],
            'email'    => $user['email'],
            'kelas'    => $user['kelas'] ?? '',
            'xp'       => (int)($user['total_xp'] ?? 0),
            'level'    => (int)($user['level'] ?? 1),
        ]);

        // Update last_login & streak harian
        $now = date('Y-m-d H:i:s');
        $update = $this->db->prepare("UPDATE users SET last_login = :now WHERE id = :id");
        $update->execute([':now' => $now, ':id' => $user['id']]);

        if ($user['role'] === 'siswa') {
            updateUserStreak($this->db, (int)$user['id']);
        }

        setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');

        if ($user['role'] === 'guru') {
            redirect(base_url('guru/dashboard'));
        } else {
            redirect(base_url('siswa/dashboard'));
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        logout();
        setFlash('success', 'Anda telah berhasil keluar dari akun.');
        redirect(base_url('login'));
    }

    /**
     * Register Siswa (Opsional/Self-Registration)
     */
    public function register()
    {
        if (isLoggedIn()) {
            redirect(base_url());
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!verifyToken($token)) {
                setFlash('error', 'Token pendaftaran tidak valid.');
                redirect(base_url('register'));
            }

            $nama     = sanitize($_POST['nama'] ?? '');
            $username = sanitize($_POST['username'] ?? '');
            $email    = sanitize($_POST['email'] ?? '');
            $kelas    = sanitize($_POST['kelas'] ?? '');
            $nisn     = sanitize($_POST['nisn'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($nama) || empty($username) || empty($password)) {
                setFlash('error', 'Mohon lengkapi formulir pendaftaran.');
                redirect(base_url('register'));
            }

            // Cek duplikasi
            $check = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                setFlash('error', 'Username atau Email sudah terdaftar.');
                redirect(base_url('register'));
            }

            $hash = hashPassword($password);
            $stmt = $this->db->prepare("INSERT INTO users (nama, username, email, password, role, kelas, nisn, is_active) VALUES (?, ?, ?, ?, 'siswa', ?, ?, 1)");
            $stmt->execute([$nama, $username, $email, $hash, $kelas, $nisn]);
            $newId = (int)$this->db->lastInsertId();

            // Init gamifikasi
            $this->db->prepare("INSERT INTO gamifikasi (user_id, total_xp, level, streak_days, last_activity) VALUES (?, 10, 1, 1, ?)")
                     ->execute([$newId, date('Y-m-d')]);

            // Beri badge pemula
            $this->db->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, 1)")
                     ->execute([$newId]);

            // Notifikasi Real-time ke Portal Guru
            try {
                createNotification($this->db, [
                    'role_target' => 'guru',
                    'tipe'        => 'siswa',
                    'judul'       => 'Pendaftaran Siswa Baru',
                    'pesan'       => 'Siswa baru terdaftar: ' . $nama . ' (' . ($kelas ?: 'Kelas VII') . ').',
                    'icon'        => 'fa-user-plus',
                    'color'       => 'amber',
                    'link'        => base_url('guru/siswa/' . $newId),
                ]);
            } catch (Throwable $e) {}

            setFlash('success', 'Pendaftaran berhasil! Silakan masuk dengan akun baru Anda.');
            redirect(base_url('login'));
        }

        $csrf_token = generateToken();
        render('auth/login', [
            'title'      => 'Daftar Akun Baru - ' . APP_NAME,
            'csrf_token' => $csrf_token,
            'isRegister' => true,
        ]);
    }

    /**
     * Halaman Lupa Kata Sandi (Mendukung Guru/Pengelola & Siswa)
     */
    public function forgot()
    {
        if (isLoggedIn()) {
            $user = getUser();
            redirect(base_url(($user['role'] ?? '') === 'guru' ? 'guru/dashboard' : 'siswa/dashboard'));
        }

        $initialRole = sanitize($_GET['role'] ?? 'guru');
        if (!in_array($initialRole, ['guru', 'siswa'], true)) {
            $initialRole = 'guru';
        }

        $csrf_token = generateToken();
        render('auth/forgot', [
            'title'       => 'Atur Ulang Kata Sandi — ' . APP_NAME,
            'csrf_token'  => $csrf_token,
            'initialRole' => $initialRole,
            'noLayout'    => true,
            'flash'       => [
                'error'   => getFlash('error'),
                'success' => getFlash('success'),
            ],
        ]);
    }

    /**
     * Proses Reset Kata Sandi Guru / Pengelola (POST)
     * Dilindungi dengan Verifikasi NIP + Kunci Keamanan Master (Master Recovery Key)
     */
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('auth/forgot'));
        }

        $token = $_POST['csrf_token'] ?? '';

        if (!verifyToken($token)) {
            setFlash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
            redirect(base_url('auth/forgot'));
        }

        $role            = sanitize($_POST['role'] ?? 'guru');
        $username        = sanitize($_POST['username'] ?? '');
        $identifier      = sanitize($_POST['identifier'] ?? ($_POST['nip'] ?? ''));
        $masterKey       = trim((string)($_POST['master_key'] ?? ''));
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirmation'] ?? '';

        if ($role !== 'guru') {
            $this->requestResetSiswa();
            return;
        }

        // 1. Proteksi Anti-Brute Force (Maksimal 5x gagal dalam 15 menit)
        $failedAttempts = (int)($_SESSION['_failed_guru_resets'] ?? 0);
        $lastAttemptTime = (int)($_SESSION['_failed_guru_time'] ?? 0);
        if ($failedAttempts >= 5 && (time() - $lastAttemptTime) < 900) {
            $sisaMenit = ceil((900 - (time() - $lastAttemptTime)) / 60);
            setFlash('error', "Terlalu banyak percobaan gagal. Akses pemulihan terkunci sementara demi keamanan. Silakan coba kembali dalam {$sisaMenit} menit.");
            redirect(base_url('auth/forgot?role=guru'));
        }

        // 2. Validasi input wajib
        if (empty($username) || empty($identifier) || empty($masterKey)) {
            setFlash('error', 'Username, NIP resmi, dan Kunci Keamanan Master wajib diisi.');
            redirect(base_url('auth/forgot?role=guru'));
        }

        if (empty($password) || strlen($password) < 6) {
            setFlash('error', 'Kata sandi baru minimal harus 6 karakter.');
            redirect(base_url('auth/forgot?role=guru'));
        }

        if ($password !== $confirmPassword) {
            setFlash('error', 'Konfirmasi kata sandi tidak cocok.');
            redirect(base_url('auth/forgot?role=guru'));
        }

        // 3. Verifikasi Kunci Keamanan Master (Master Recovery Key)
        $expectedMasterKey = defined('MASTER_RECOVERY_KEY') ? MASTER_RECOVERY_KEY : 'SMPN1-GURU-2026';
        if (!hash_equals($expectedMasterKey, $masterKey)) {
            $_SESSION['_failed_guru_resets'] = $failedAttempts + 1;
            $_SESSION['_failed_guru_time'] = time();
            setFlash('error', 'Kunci Keamanan Master tidak valid! Akses pemulihan akun guru ditolak.');
            redirect(base_url('auth/forgot?role=guru'));
        }

        // 4. Cari akun Guru berdasarkan username atau email dinas
        $stmt = $this->db->prepare(
            "SELECT id, nama, username, email, nip, role, is_active 
             FROM users 
             WHERE (username = ? OR email = ?) AND role = 'guru' 
             LIMIT 1"
        );
        $stmt->execute([$username, $username]);
        $guru = $stmt->fetch();

        if (!$guru) {
            $_SESSION['_failed_guru_resets'] = $failedAttempts + 1;
            $_SESSION['_failed_guru_time'] = time();
            setFlash('error', 'Akun Guru/Pengelola dengan username/email tersebut tidak ditemukan.');
            redirect(base_url('auth/forgot?role=guru'));
        }

        if (isset($guru['is_active']) && empty($guru['is_active'])) {
            setFlash('error', 'Akun Guru ini sedang dinonaktifkan.');
            redirect(base_url('auth/forgot?role=guru'));
        }

        // 5. Verifikasi NIP Guru
        $dbNip = preg_replace('/\D/', '', (string)($guru['nip'] ?? ''));
        $inputNip = preg_replace('/\D/', '', (string)$identifier);

        if (empty($dbNip) || $dbNip !== $inputNip) {
            $_SESSION['_failed_guru_resets'] = $failedAttempts + 1;
            $_SESSION['_failed_guru_time'] = time();
            setFlash('error', 'NIP tidak sesuai dengan data resmi Guru/Pengelola terdaftar di SMPN 1 Karangmoncol.');
            redirect(base_url('auth/forgot?role=guru'));
        }

        // Reset hitungan gagal jika sukses
        unset($_SESSION['_failed_guru_resets']);
        unset($_SESSION['_failed_guru_time']);

        // 6. Simpan kata sandi baru
        $hash = hashPassword($password);
        $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
                 ->execute([$hash, $guru['id']]);

        setFlash('success', 'Kata sandi akun Guru/Pengelola (' . $guru['nama'] . ') berhasil diperbarui! Silakan masuk dengan kata sandi baru.');
        redirect(base_url('login'));
    }

    /**
     * Permohonan Pemulihan Kata Sandi Siswa (Mengirimkan notifikasi ke Guru Pengelola)
     */
    public function requestResetSiswa()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('auth/forgot?role=siswa'));
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyToken($token)) {
            setFlash('error', 'Sesi keamanan kedaluwarsa. Silakan coba lagi.');
            redirect(base_url('auth/forgot?role=siswa'));
        }

        $username   = sanitize($_POST['username'] ?? '');
        $nisn       = sanitize($_POST['nisn'] ?? '');
        $keterangan = sanitize($_POST['keterangan'] ?? '');

        if (empty($username) || empty($nisn)) {
            setFlash('error', 'Username dan NISN siswa wajib diisi untuk verifikasi identitas.');
            redirect(base_url('auth/forgot?role=siswa'));
        }

        $stmt = $this->db->prepare(
            "SELECT id, nama, username, nisn, kelas, is_active 
             FROM users 
             WHERE (username = ? OR email = ?) AND role = 'siswa' 
             LIMIT 1"
        );
        $stmt->execute([$username, $username]);
        $siswa = $stmt->fetch();

        if (!$siswa) {
            setFlash('error', 'Data siswa dengan username/email tersebut tidak ditemukan.');
            redirect(base_url('auth/forgot?role=siswa'));
        }

        $dbNisn = preg_replace('/\D/', '', (string)($siswa['nisn'] ?? ''));
        $inputNisn = preg_replace('/\D/', '', (string)$nisn);

        if (empty($dbNisn) || $dbNisn !== $inputNisn) {
            setFlash('error', 'NISN tidak sesuai dengan data siswa terdaftar. Silakan periksa kartu pelajar/rapor Anda.');
            redirect(base_url('auth/forgot?role=siswa'));
        }

        // Kirim notifikasi prioritas tinggi ke Portal Guru
        try {
            $pesan = 'Siswa ' . $siswa['nama'] . ' (' . ($siswa['kelas'] ?: 'Kelas VII') . ') mengajukan pemulihan kata sandi.';
            if (!empty($keterangan)) {
                $pesan .= ' Alasan: "' . $keterangan . '"';
            }
            createNotification($this->db, [
                'role_target' => 'guru',
                'tipe'        => 'siswa',
                'judul'       => 'Permohonan Reset Kata Sandi Siswa',
                'pesan'       => $pesan,
                'icon'        => 'fa-key',
                'color'       => 'amber',
                'link'        => base_url('guru/siswa/' . $siswa['id']),
            ]);
        } catch (Throwable $e) {}

        setFlash('success', 'Permohonan pemulihan akun berhasil terkirim ke Portal Guru! Silakan temui Bpk. Angzal Maolana, S.Pd atau Ibu Hana Sifakhatin, S.Pd di ruang guru untuk mendapatkan kata sandi sementara Anda.');
        redirect(base_url('auth/forgot?role=siswa'));
    }
}
