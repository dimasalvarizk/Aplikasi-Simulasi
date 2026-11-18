<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/response.php';

class ApiController
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    // =========================================================
    // INFO & AUTH
    // =========================================================

    public function index()
    {
        jsonSuccess([
            'app'       => APP_NAME,
            'version'   => APP_VERSION,
            'role_mode' => 'Aplikasi Pembelajaran Siswa & Portal Guru',
            'status'    => 'active',
            'endpoints' => [
                'POST /api/login'             => 'Login Siswa (Username & Password)',
                'GET  /api/modul'             => 'Daftar Modul & Materi',
                'GET  /api/materi/{id}'       => 'Konten Materi Tertentu',
                'POST /api/materi/{id}/selesai' => 'Klaim XP Membaca Materi',
                'GET  /api/kuis'              => 'Daftar Kuis',
                'GET  /api/kuis/{id}'         => 'Soal Kuis',
                'POST /api/kuis/{id}/submit'  => 'Submit Jawaban Kuis',
                'GET  /api/simulasi'          => 'Tantangan Simulasi Topologi',
                'GET  /api/simulasi/{id}'     => 'Detail Simulasi Topologi',
                'POST /api/simulasi/{id}/submit' => 'Validasi Canvas Topologi & Klaim XP',
                'GET  /api/profil'            => 'Data Profil Siswa, Badges, Level, XP',
                'GET  /api/leaderboard'       => 'Papan Peringkat Siswa',
            ]
        ], 'NetSim Academy RESTful API Ready.');
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
        $username = sanitize($_POST['username'] ?? ($input['username'] ?? ''));
        $password = $_POST['password'] ?? ($input['password'] ?? '');

        if (empty($username) || empty($password)) {
            jsonError('Username dan password wajib diisi.', 400);
        }

        // Dukung alias guru1 -> angzal dan guru2 -> hana, serta NISN / NIP
        $alias = $username;
        if ($username === 'guru1') {
            $alias = 'angzal';
        } elseif ($username === 'guru2') {
            $alias = 'hana';
        }

        $stmt = $this->db->prepare(
            "SELECT u.*, COALESCE(g.total_xp, 0) AS total_xp, COALESCE(g.level, 1) AS level, COALESCE(g.streak_days, 1) AS streak_days
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             WHERE u.username = :username OR u.username = :alias OR u.email = :email OR u.nisn = :nisn OR u.nip = :nip
             LIMIT 1"
        );
        $stmt->execute([
            ':username' => $username,
            ':alias'    => $alias,
            ':email'    => $username,
            ':nisn'     => $username,
            ':nip'      => $username,
        ]);
        $user = $stmt->fetch();

        if (!$user || empty($user['is_active'])) {
            jsonError('Akun tidak ditemukan atau tidak aktif.', 401);
        }

        if (!verifyPassword($password, $user['password'])) {
            jsonError('Kata sandi salah.', 401);
        }

        if ($user['role'] !== 'siswa') {
            jsonError('Aplikasi ini khusus untuk akun Siswa. Pengajar silakan masuk melalui portal web.', 403);
        }

        // Generate token sederhana
        $token = bin2hex(random_bytes(24));
        $xp = (int)$user['total_xp'];
        $level = calculateLevel($xp);

        // Update last login & daily streak
        $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        $currentStreak = updateUserStreak($this->db, (int)$user['id']);

        jsonSuccess([
            'token' => $token,
            'user'  => [
                'id'          => (int)$user['id'],
                'nama'        => $user['nama'],
                'username'    => $user['username'],
                'email'       => $user['email'],
                'role'        => $user['role'],
                'kelas'       => $user['kelas'] ?? '',
                'nisn'        => $user['nisn'] ?? '',
                'total_xp'    => $xp,
                'level'       => $level,
                'level_title' => getLevelTitle($level),
                'streak_days' => $currentStreak,
                'avatar'      => $user['avatar'] ?? '',
                'avatar_url'  => !empty($user['avatar']) ? (str_starts_with($user['avatar'], 'http') ? $user['avatar'] : base_url($user['avatar'])) : '',
            ]
        ], 'Login berhasil!');
    }

    public function resetPasswordSiswa()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];

        $role        = trim((string)($_POST['role'] ?? ($input['role'] ?? 'siswa')));
        $username    = trim((string)($_POST['username'] ?? ($input['username'] ?? '')));
        $nip         = trim((string)($_POST['nip'] ?? ($input['nip'] ?? '')));
        $nisn        = trim((string)($_POST['nisn'] ?? ($input['nisn'] ?? '')));
        $identifier  = trim((string)($_POST['identifier'] ?? ($input['identifier'] ?? ($role === 'guru' || !empty($nip) ? $nip : $nisn))));
        $newPassword = (string)($_POST['new_password'] ?? ($input['new_password'] ?? ($_POST['password'] ?? ($input['password'] ?? ''))));

        if (empty($username)) {
            jsonError('Username wajib diisi.', 400);
        }
        if (empty($identifier)) {
            jsonError(($role === 'guru' || !empty($nip)) ? 'NIP Guru wajib diisi untuk verifikasi identitas.' : 'NISN Siswa wajib diisi untuk verifikasi identitas.', 400);
        }
        if (empty($newPassword) || strlen($newPassword) < 6) {
            jsonError('Kata sandi baru minimal harus 6 karakter.', 400);
        }

        if ($role === 'guru' || !empty($nip)) {
            $masterKey = trim((string)($_POST['master_key'] ?? ($input['master_key'] ?? '')));
            $expected = defined('MASTER_RECOVERY_KEY') ? MASTER_RECOVERY_KEY : 'SMPN1-GURU-2026';
            if (empty($masterKey) || !hash_equals($expected, $masterKey)) {
                jsonError('Kunci Keamanan Master tidak valid! Akses pemulihan guru ditolak.', 403);
            }

            $stmt = $this->db->prepare(
                "SELECT id, nama, username, nip, role, is_active 
                 FROM users 
                 WHERE (username = ? OR email = ?) AND role = 'guru' 
                 LIMIT 1"
            );
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if (!$user) {
                jsonError('Akun Guru/Pengelola dengan username/email tersebut tidak ditemukan.', 404);
            }
            if (isset($user['is_active']) && empty($user['is_active'])) {
                jsonError('Akun Guru ini sedang dinonaktifkan.', 403);
            }

            $dbNip = preg_replace('/\D/', '', (string)($user['nip'] ?? ''));
            $inNip = preg_replace('/\D/', '', (string)$identifier);
            if (empty($dbNip) || $dbNip !== $inNip) {
                jsonError('NIP tidak sesuai dengan data Guru/Pengelola terdaftar di SMPN 1 Karangmoncol.', 400);
            }

            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
                     ->execute([$hash, $user['id']]);

            jsonSuccess([
                'user_id'  => (int)$user['id'],
                'username' => $user['username'],
                'nama'     => $user['nama'],
                'role'     => 'guru',
            ], 'Kata sandi akun Guru berhasil diatur ulang! Silakan masuk dengan kata sandi baru Anda.');
        } else {
            $stmt = $this->db->prepare(
                "SELECT id, nama, username, nisn, role, is_active 
                 FROM users 
                 WHERE (username = ? OR email = ?) AND role = 'siswa' 
                 LIMIT 1"
            );
            $stmt->execute([$username, $username]);
            $siswa = $stmt->fetch();

            if (!$siswa) {
                jsonError('Akun siswa dengan username/email tersebut tidak ditemukan.', 404);
            }

            if (isset($siswa['is_active']) && empty($siswa['is_active'])) {
                jsonError('Akun siswa ini sedang dinonaktifkan. Silakan hubungi guru pembimbing.', 403);
            }

            // Verifikasi kesesuaian NISN
            $dbNisn = preg_replace('/\D/', '', (string)($siswa['nisn'] ?? ''));
            $inNisn = preg_replace('/\D/', '', (string)$identifier);
            if (empty($dbNisn) || $dbNisn !== $inNisn) {
                jsonError('NISN tidak sesuai dengan data siswa terdaftar. Pastikan memasukkan NISN yang tertera pada kartu pelajar/rapor.', 400);
            }

            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
                     ->execute([$hash, $siswa['id']]);

            jsonSuccess([
                'user_id'  => (int)$siswa['id'],
                'username' => $siswa['username'],
                'nama'     => $siswa['nama'],
                'role'     => 'siswa',
            ], 'Kata sandi berhasil diatur ulang! Silakan masuk dengan kata sandi baru Anda.');
        }
    }

    /**
     * API Permohonan Reset Kata Sandi Siswa (Notifikasi ke Guru)
     */
    public function requestResetSiswa()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];

        $username   = trim((string)($_POST['username'] ?? ($input['username'] ?? '')));
        $nisn       = trim((string)($_POST['nisn'] ?? ($input['nisn'] ?? '')));
        $keterangan = trim((string)($_POST['keterangan'] ?? ($input['keterangan'] ?? '')));

        if (empty($username) || empty($nisn)) {
            jsonError('Username dan NISN siswa wajib diisi.', 400);
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
            jsonError('Data siswa dengan username tersebut tidak ditemukan.', 404);
        }

        $dbNisn = preg_replace('/\D/', '', (string)($siswa['nisn'] ?? ''));
        $inNisn = preg_replace('/\D/', '', (string)$nisn);
        if (empty($dbNisn) || $dbNisn !== $inNisn) {
            jsonError('NISN tidak sesuai dengan data siswa terdaftar.', 400);
        }

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

        jsonSuccess([
            'siswa_id' => (int)$siswa['id'],
            'nama'     => $siswa['nama'],
        ], 'Permohonan pemulihan akun berhasil dikirim ke Guru Pengampu (Bpk. Angzal Maolana / Ibu Hana Sifakhatin). Silakan temui guru di sekolah untuk menerima kata sandi sementara Anda.');
    }

    // =========================================================
    // MODUL & MATERI
    // =========================================================

    public function getModul()
    {
        $userId = (int)($_GET['user_id'] ?? 0);

        $modulList = $this->db->query(
            "SELECT m.*, COUNT(mt.id) AS jumlah_materi
             FROM modul m
             LEFT JOIN materi mt ON mt.modul_id = m.id AND (mt.is_active = 1 OR mt.is_published = 1)
             WHERE (m.is_active = 1 OR m.is_published = 1)
             GROUP BY m.id
             ORDER BY m.urutan ASC"
        )->fetchAll();

        foreach ($modulList as &$m) {
            $m['id'] = (int)$m['id'];
            $materiStmt = $this->db->prepare(
                "SELECT id, modul_id, judul, konten, urutan, xp_reward,
                        (SELECT COUNT(*) FROM progress_materi pm WHERE pm.user_id = :uid AND pm.materi_id = m.id) AS sudah_selesai
                 FROM materi m
                 WHERE m.modul_id = :mid AND (m.is_active = 1 OR m.is_published = 1)
                 ORDER BY m.urutan ASC"
            );
            $materiStmt->execute([':uid' => $userId, ':mid' => $m['id']]);
            $materis = $materiStmt->fetchAll();

            foreach ($materis as &$mat) {
                $mat['id'] = (int)$mat['id'];
                $mat['modul_id'] = (int)$mat['modul_id'];
                $mat['urutan'] = (int)($mat['urutan'] ?? 1);
                $mat['xp_reward'] = (int)($mat['xp_reward'] ?? 15);
                $isDone = ((int)($mat['sudah_selesai'] ?? 0)) > 0;
                $mat['sudah_selesai'] = $isDone ? 1 : 0;
                $mat['is_completed'] = $isDone;
            }
            unset($mat);

            $m['materi_list'] = $materis;
            $m['materi'] = $materis;
            $m['materi_count'] = count($materis);
            $m['jumlah_materi'] = count($materis);
        }
        unset($m);

        jsonSuccess($modulList, 'Data modul pembelajaran.');
    }

    public function getMateri($id)
    {
        $id = (int)$id;
        $userId = (int)($_GET['user_id'] ?? 0);

        $stmt = $this->db->prepare(
            "SELECT mt.*, mo.judul AS modul_judul
             FROM materi mt
             JOIN modul mo ON mo.id = mt.modul_id
             WHERE mt.id = ?"
        );
        $stmt->execute([$id]);
        $materi = $stmt->fetch();

        if (!$materi) {
            jsonError('Materi tidak ditemukan.', 404);
        }

        $materi['id'] = (int)$materi['id'];
        $materi['xp_reward'] = (int)($materi['xp_reward'] ?? 15);

        // Cek sudah selesai
        $cek = $this->db->prepare("SELECT id FROM progress_materi WHERE user_id = ? AND materi_id = ?");
        $cek->execute([$userId, $id]);
        $isDone = (bool)$cek->fetch();
        $materi['sudah_selesai'] = $isDone ? 1 : 0;
        $materi['is_completed'] = $isDone;

        jsonSuccess($materi, 'Detail bab materi.');
    }

    public function materiSelesai($id)
    {
        $id = (int)$id;
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
        $userId = (int)($_POST['user_id'] ?? ($input['user_id'] ?? 0));

        if ($userId <= 0) {
            jsonError('User ID tidak valid.', 400);
        }

        $stmt = $this->db->prepare("SELECT id, judul, xp_reward FROM materi WHERE id = ?");
        $stmt->execute([$id]);
        $materi = $stmt->fetch();

        if (!$materi) {
            jsonError('Materi tidak ditemukan.', 404);
        }

        $xpEarned = 0;
        $cek = $this->db->prepare("SELECT id FROM progress_materi WHERE user_id = ? AND materi_id = ?");
        $cek->execute([$userId, $id]);
        if (!$cek->fetch()) {
            $xpEarned = (int)$materi['xp_reward'];
            $this->db->prepare("INSERT INTO progress_materi (user_id, materi_id, read_at, is_completed, xp_earned) VALUES (?, ?, NOW(), 1, ?)")
                     ->execute([$userId, $id, $xpEarned]);

            $this->_addXP($userId, $xpEarned, 'materi', $id);

            // Notifikasi Real-time ke Guru
            $userNama = $this->db->query("SELECT nama FROM users WHERE id = {$userId}")->fetchColumn() ?: 'Siswa';
            createNotification($this->db, [
                'role_target' => 'guru',
                'tipe'        => 'materi',
                'judul'       => 'Modul Materi Selesai Dibaca',
                'pesan'       => $userNama . ' telah selesai membaca materi "' . $materi['judul'] . '" (+' . $xpEarned . ' XP).',
                'icon'        => 'fa-book-open',
                'color'       => 'indigo',
                'link'        => base_url('guru/siswa/' . $userId),
            ]);

            // Notifikasi Real-time ke Siswa
            createNotification($this->db, [
                'user_id'     => $userId,
                'role_target' => 'siswa',
                'tipe'        => 'materi',
                'judul'       => 'Materi Selesai Dipelajari',
                'pesan'       => 'Selamat! Kamu telah membaca materi "' . $materi['judul'] . '" dan mendapatkan +' . $xpEarned . ' XP.',
                'icon'        => 'fa-book-open',
                'color'       => 'indigo',
                'link'        => '/modul',
            ]);
        }

        $userStmt = $this->db->prepare("SELECT COALESCE(total_xp, 0) FROM gamifikasi WHERE user_id = ?");
        $userStmt->execute([$userId]);
        $totalXp = (int)$userStmt->fetchColumn();

        jsonSuccess([
            'xp_reward' => $xpEarned,
            'xp_earned' => $xpEarned,
            'total_xp'  => $totalXp,
        ], $xpEarned > 0 ? 'Materi selesai dibaca! XP ditambahkan.' : 'Materi sudah pernah diselesaikan.');
    }

    // =========================================================
    // KUIS
    // =========================================================

    public function getKuis($id = null)
    {
        $userId = (int)($_GET['user_id'] ?? 0);

        if (!$id) {
            $stmt = $this->db->prepare(
                "SELECT k.*, mo.judul AS modul_judul,
                        (SELECT COUNT(*) FROM soal s WHERE s.kuis_id = k.id) AS jumlah_soal,
                        (SELECT MAX(skor) FROM hasil_kuis hk WHERE hk.user_id = :uid AND hk.kuis_id = k.id) AS skor_tertinggi
                 FROM kuis k
                 LEFT JOIN modul mo ON mo.id = k.modul_id
                 WHERE (k.is_active = 1 OR k.is_active IS NULL OR k.is_published = 1)
                 ORDER BY k.id ASC"
            );
            $stmt->execute([':uid' => $userId]);
            $list = $stmt->fetchAll();

            foreach ($list as &$k) {
                $k['id'] = (int)$k['id'];
                $k['durasi_menit'] = (int)($k['durasi_menit'] ?? 15);
                $k['kkm'] = (int)($k['passing_grade'] ?? 70);
                $k['passing_grade'] = $k['kkm'];
                $totalS = (int)($k['jumlah_soal'] ?? 0);
                $k['jumlah_soal'] = $totalS;
                $k['total_soal'] = $totalS;
                $skor = $k['skor_tertinggi'] !== null ? (float)$k['skor_tertinggi'] : null;
                $k['skor_tertinggi'] = $skor;
                $k['nilai_tertinggi'] = $skor;
            }
            unset($k);

            jsonSuccess($list, 'Daftar kuis aktif.');
        }

        $id = (int)$id;
        $stmt = $this->db->prepare(
            "SELECT k.*, mo.judul AS modul_judul FROM kuis k
             LEFT JOIN modul mo ON mo.id = k.modul_id
             WHERE k.id = ? AND (k.is_active = 1 OR k.is_active IS NULL OR k.is_published = 1)"
        );
        $stmt->execute([$id]);
        $kuis = $stmt->fetch();

        if (!$kuis) {
            jsonError('Kuis tidak ditemukan.', 404);
        }

        $kuis['id'] = (int)$kuis['id'];
        $kuis['kkm'] = (int)($kuis['passing_grade'] ?? 70);
        $kuis['durasi_menit'] = (int)($kuis['durasi_menit'] ?? 15);

        // Ambil soal (tanpa membocorkan jawaban benar)
        $soal = $this->db->prepare("SELECT id, kuis_id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, poin, urutan FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
        $soal->execute([$id]);
        $soalList = $soal->fetchAll();

        foreach ($soalList as &$s) {
            $s['id'] = (int)$s['id'];
            $s['kuis_id'] = (int)$s['kuis_id'];
            $s['pilihan_a'] = $s['opsi_a'];
            $s['pilihan_b'] = $s['opsi_b'];
            $s['pilihan_c'] = $s['opsi_c'];
            $s['pilihan_d'] = $s['opsi_d'];
        }
        unset($s);

        if ($kuis['acak_soal']) {
            shuffle($soalList);
        }

        $kuis['soal_list'] = $soalList;
        $kuis['soal'] = $soalList;
        $kuis['total_soal'] = count($soalList);

        jsonSuccess($kuis, 'Detail kuis dan daftar soal.');
    }

    public function submitKuis($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $id = (int)$id;
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
        $userId = (int)($_POST['user_id'] ?? ($input['user_id'] ?? 0));
        $jawaban = $_POST['jawaban'] ?? ($input['jawaban'] ?? []);

        if ($userId <= 0) {
            jsonError('User ID tidak valid.', 400);
        }

        $stmt = $this->db->prepare("SELECT * FROM kuis WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $kuis = $stmt->fetch();

        if (!$kuis) {
            jsonError('Kuis tidak ditemukan.', 404);
        }

        $soalStmt = $this->db->prepare("SELECT id, jawaban_benar FROM soal WHERE kuis_id = ?");
        $soalStmt->execute([$id]);
        $soalList = $soalStmt->fetchAll();

        $totalSoal = count($soalList);
        $benar = 0;

        foreach ($soalList as $s) {
            $pilihan = strtoupper(trim($jawaban[$s['id']] ?? ''));
            $kunci = strtoupper(trim($s['jawaban_benar']));
            if ($pilihan !== '' && $pilihan === $kunci) {
                $benar++;
            }
        }

        $skor = $totalSoal > 0 ? round(($benar / $totalSoal) * 100, 1) : 0;
        $passingGrade = (int)($kuis['passing_grade'] ?? 70);
        $lulus = $skor >= $passingGrade;

        // Cek apakah siswa sudah pernah lulus atau sudah pernah memperoleh XP untuk kuis ini sebelumnya
        $cekLulus = $this->db->prepare(
            "SELECT id FROM hasil_kuis 
             WHERE user_id = ? AND kuis_id = ? AND (status = 'lulus' OR xp_earned > 0)
             LIMIT 1"
        );
        $cekLulus->execute([$userId, $id]);
        $sudahPernahKlaim = (bool)$cekLulus->fetch();

        if (!$sudahPernahKlaim) {
            $cekLog = $this->db->prepare(
                "SELECT id FROM xp_log 
                 WHERE user_id = ? AND sumber = 'kuis' AND referensi_id = ?
                 LIMIT 1"
            );
            $cekLog->execute([$userId, $id]);
            $sudahPernahKlaim = (bool)$cekLog->fetch();
        }

        $xpReward = 0;
        if ($lulus && !$sudahPernahKlaim) {
            // Berikan reward XP hanya pada penyelesaian lulus pertama kali (kerjakan ulang tidak menambah XP lagi)
            $xpReward = (int)($kuis['xp_reward'] ?? 50);
            $this->_addXP($userId, $xpReward, 'kuis', $id);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->prepare(
            "INSERT INTO hasil_kuis (user_id, kuis_id, skor, jumlah_benar, total_benar, jumlah_soal, total_soal, xp_earned, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$userId, $id, $skor, $benar, $benar, $totalSoal, $totalSoal, $xpReward, $lulus ? 'lulus' : 'tidak_lulus', $now]);

        // Real-time Notification ke Portal Guru
        try {
            $userNama = $this->db->query("SELECT nama FROM users WHERE id = {$userId}")->fetchColumn() ?: 'Siswa';
            createNotification($this->db, [
                'role_target' => 'guru',
                'tipe'        => 'kuis',
                'judul'       => $lulus ? 'Kuis Selesai: Lulus' : 'Kuis Dikerjakan: Perlu Remedial',
                'pesan'       => $userNama . ' menyelesaikan kuis "' . ($kuis['judul'] ?? 'Kuis') . '" dengan nilai ' . $skor . ($lulus ? ' (Lulus' . ($xpReward > 0 ? ', +' . $xpReward . ' XP' : '') . ')' : ' (Remedial)'),
                'icon'        => $lulus ? 'fa-check-circle' : 'fa-clipboard-question',
                'color'       => $lulus ? 'emerald' : 'amber',
                'link'        => base_url('guru/kuis/detail/' . $id),
            ]);

            // Real-time Notification ke Siswa
            createNotification($this->db, [
                'user_id'     => $userId,
                'role_target' => 'siswa',
                'tipe'        => 'kuis',
                'judul'       => $lulus ? 'Selamat! Kamu Lulus Kuis' : 'Evaluasi Kuis Selesai',
                'pesan'       => $lulus
                    ? 'Kerja bagus! Kamu lulus kuis "' . ($kuis['judul'] ?? 'Kuis') . '" dengan skor ' . $skor . ($xpReward > 0 ? ' (+' . $xpReward . ' XP)' : '') . '.'
                    : 'Nilai kuis kamu ' . $skor . '. Batas kelulusan (KKM) adalah ' . $passingGrade . '. Kamu bisa mencoba kerjakan ulang.',
                'icon'        => $lulus ? 'fa-check-circle' : 'fa-clipboard-question',
                'color'       => $lulus ? 'emerald' : 'amber',
                'link'        => '/kuis',
            ]);
        } catch (Throwable $e) {}

        $salah = $totalSoal - $benar;
        $kkm = $passingGrade;

        $userStmt = $this->db->prepare("SELECT COALESCE(total_xp, 0) FROM gamifikasi WHERE user_id = ?");
        $userStmt->execute([$userId]);
        $totalXp = (int)$userStmt->fetchColumn();

        $pesan = '';
        if ($lulus) {
            if ($xpReward > 0) {
                $pesan = 'Selamat! Kamu lulus kuis ini (+' . $xpReward . ' XP).';
            } else {
                $pesan = 'Selamat! Kamu lulus kuis ini. XP sudah pernah diperoleh pada percobaan sebelumnya.';
            }
        } else {
            $pesan = 'Belum mencapai batas KKM (' . $passingGrade . '). Silakan kerjakan ulang.';
        }

        jsonSuccess([
            'skor'                   => $skor,
            'nilai'                  => $skor,
            'benar'                  => $benar,
            'salah'                  => $salah,
            'total'                  => $totalSoal,
            'total_soal'             => $totalSoal,
            'lulus'                  => $lulus,
            'kkm'                    => $kkm,
            'xp_earned'              => $xpReward,
            'sudah_klaim_sebelumnya' => $sudahPernahKlaim,
            'total_xp'               => $totalXp,
            'message'                => $pesan,
        ], 'Evaluasi kuis selesai.');
    }

    // =========================================================
    // SIMULASI TOPOLOGI
    // =========================================================

    public function getSimulasi($id = null)
    {
        $userId = (int)($_GET['user_id'] ?? 0);

        if (!$id) {
            $stmt = $this->db->prepare(
                "SELECT sc.*, mo.judul AS modul_judul,
                        (SELECT MAX(skor) FROM simulasi_hasil WHERE user_id = :uid1 AND challenge_id = sc.id) AS skor_tertinggi,
                        (SELECT COUNT(*) FROM simulasi_hasil WHERE user_id = :uid2 AND challenge_id = sc.id AND is_correct = 1) AS sudah_lulus
                 FROM simulasi_challenge sc
                 LEFT JOIN modul mo ON mo.id = sc.modul_id
                 WHERE (sc.is_active = 1 OR sc.is_active IS NULL)
                 ORDER BY sc.id ASC"
            );
            $stmt->execute([':uid1' => $userId, ':uid2' => $userId]);
            $list = $stmt->fetchAll();

            foreach ($list as &$c) {
                $c['id'] = (int)$c['id'];
                $c['xp_reward'] = (int)($c['xp_reward'] ?? 50);
                $isCompleted = ((int)($c['sudah_lulus'] ?? 0)) > 0;
                $c['is_completed'] = $isCompleted;
                $c['sudah_lulus'] = $isCompleted ? 1 : 0;
                $c['kesulitan'] = match(strtolower($c['tipe_topologi'] ?? '')) {
                    'star' => 'Mudah',
                    'bus'  => 'Sedang',
                    'ring' => 'Sedang',
                    'tree' => 'Sulit',
                    'mesh' => 'Sulit',
                    default => 'Sedang',
                };
            }
            unset($c);

            jsonSuccess($list, 'Daftar tantangan simulasi topologi.');
        }

        $id = (int)$id;
        $stmt = $this->db->prepare(
            "SELECT sc.*, mo.judul AS modul_judul FROM simulasi_challenge sc
             LEFT JOIN modul mo ON mo.id = sc.modul_id
             WHERE sc.id = ? AND (sc.is_active = 1 OR sc.is_active IS NULL)"
        );
        $stmt->execute([$id]);
        $challenge = $stmt->fetch();

        if (!$challenge) {
            jsonError('Tantangan simulasi tidak ditemukan.', 404);
        }

        $challenge['id'] = (int)$challenge['id'];
        $challenge['xp_reward'] = (int)($challenge['xp_reward'] ?? 50);
        $challenge['kesulitan'] = match(strtolower($challenge['tipe_topologi'] ?? '')) {
            'star' => 'Mudah',
            'bus'  => 'Sedang',
            'ring' => 'Sedang',
            'tree' => 'Sulit',
            'mesh' => 'Sulit',
            default => 'Sedang',
        };

        jsonSuccess($challenge, 'Detail tantangan simulasi.');
    }

    public function submitSimulasi($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $id = (int)$id;
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
        $userId = (int)($_POST['user_id'] ?? ($input['user_id'] ?? 0));
        $canvas = $input['canvas_data'] ?? ($_POST['canvas_data'] ?? null);

        if (is_string($canvas)) {
            $canvas = json_decode($canvas, true);
        }

        $stmt = $this->db->prepare("SELECT * FROM simulasi_challenge WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $challenge = $stmt->fetch();

        if (!$challenge) {
            jsonError('Challenge tidak ditemukan.', 404);
        }

        $eval = $this->_validateTopologyStructure($challenge['tipe_topologi'], $canvas, $challenge['target_config'] ?? null);
        $isCorrect = $eval['valid'];
        $skor = $isCorrect ? 100 : $eval['score'];
        $pesan = $eval['message'];

        // Cek sudah pernah lulus
        $cek = $this->db->prepare("SELECT id FROM simulasi_hasil WHERE user_id = ? AND challenge_id = ? AND is_correct = 1");
        $cek->execute([$userId, $id]);
        $pernahLulus = (bool)$cek->fetch();

        $xpReward = 0;
        if ($isCorrect && !$pernahLulus && $userId > 0) {
            $xpReward = (int)($challenge['xp_reward'] ?? 50);
            $this->_addXP($userId, $xpReward, 'simulasi', $id);
        }

        if ($userId > 0) {
            $this->db->prepare(
                "INSERT INTO simulasi_hasil (user_id, challenge_id, canvas_data, is_correct, skor, xp_earned, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            )->execute([$userId, $id, json_encode($canvas), $isCorrect ? 1 : 0, $skor, $xpReward]);

            // Real-time Notification ke Portal Guru
            try {
                $userNama = $this->db->query("SELECT nama FROM users WHERE id = {$userId}")->fetchColumn() ?: 'Siswa';
                createNotification($this->db, [
                    'role_target' => 'guru',
                    'tipe'        => 'simulasi',
                    'judul'       => $isCorrect ? 'Tantangan Topologi Berhasil' : 'Uji Coba Topologi Siswa',
                    'pesan'       => $userNama . ' ' . ($isCorrect ? 'berhasil merangkai' : 'menguji rangkaian') . ' "' . ($challenge['judul'] ?? 'Simulasi Topologi') . '"' . ($isCorrect ? ' (Skor ' . $skor . ($xpReward > 0 ? ', +' . $xpReward . ' XP' : '') . ')' : ' (Skor ' . $skor . ')'),
                    'icon'        => $isCorrect ? 'fa-diagram-project' : 'fa-laptop-code',
                    'color'       => $isCorrect ? 'blue' : 'slate',
                    'link'        => base_url('guru/siswa/' . $userId),
                ]);

                // Real-time Notification ke Siswa
                createNotification($this->db, [
                    'user_id'     => $userId,
                    'role_target' => 'siswa',
                    'tipe'        => 'simulasi',
                    'judul'       => $isCorrect ? 'Topologi Berhasil Dirangkai! 🎉' : 'Rangkaian Topologi Belum Tepat',
                    'pesan'       => $isCorrect
                        ? 'Mantap! Rangkaian "' . ($challenge['judul'] ?? 'Simulasi') . '" valid dengan skor ' . $skor . ($xpReward > 0 ? ' (+' . $xpReward . ' XP)' : '') . '.'
                        : 'Rangkaian belum sesuai spesifikasi topologi. ' . $pesan,
                    'icon'        => $isCorrect ? 'fa-diagram-project' : 'fa-triangle-exclamation',
                    'color'       => $isCorrect ? 'blue' : 'rose',
                    'link'        => '/simulasi',
                ]);
            } catch (Throwable $e) {}
        }

        jsonSuccess([
            'is_correct' => $isCorrect,
            'valid'      => $isCorrect,
            'skor'       => $skor,
            'score'      => $skor,
            'xp_earned'  => $xpReward,
            'message'    => $pesan,
        ], 'Evaluasi topologi canvas selesai.');
    }

    // =========================================================
    // PROFIL & LEADERBOARD
    // =========================================================

    public function getProfil()
    {
        $userId = (int)($_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            jsonError('User ID diperlukan.', 400);
        }

        $stmt = $this->db->prepare(
            "SELECT u.id, u.nama, u.username, u.email, u.kelas, u.nisn, u.avatar, u.last_login,
                    COALESCE(g.total_xp, 0) AS total_xp,
                    COALESCE(g.level, 1) AS level,
                    COALESCE(g.streak_days, 1) AS streak_days
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             WHERE u.id = ? AND u.role = 'siswa'"
        );
        $stmt->execute([$userId]);
        $siswa = $stmt->fetch();

        if (!$siswa) {
            jsonError('Data siswa tidak ditemukan.', 404);
        }

        $currentStreak = updateUserStreak($this->db, $userId);
        $siswa['streak_days'] = $currentStreak;

        $avatar = $siswa['avatar'] ?? '';
        $siswa['avatar_url'] = !empty($avatar) ? (str_starts_with($avatar, 'http') ? $avatar : base_url($avatar)) : '';

        $xp = (int)$siswa['total_xp'];
        $level = calculateLevel($xp);
        $siswa['level'] = $level;
        $siswa['level_title'] = getLevelTitle($level);
        $siswa['xp_next'] = calculateXPForNextLevel($level);

        // 1. Sinkronisasi & Periksa Kriteria Lencana (Real-time auto-award)
        $this->_checkAndAwardBadges($userId, $xp, $level);

        // 2. Ambil Lencana Terbaru beserta Status Unlocked
        $badgeStmt = $this->db->prepare(
            "SELECT b.*, CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END AS diraih, ub.earned_at
             FROM badges b
             LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = ?
             ORDER BY diraih DESC, b.id ASC"
        );
        $badgeStmt->execute([$userId]);
        $badgesRaw = $badgeStmt->fetchAll();
        $badgesList = [];
        foreach ($badgesRaw as $b) {
            $isUnlocked = ((int)$b['diraih'] === 1);
            $badgesList[] = [
                'id'          => (int)$b['id'],
                'nama'        => $b['nama'],
                'deskripsi'   => $b['deskripsi'] ?? '',
                'icon'        => $b['icon'] ?? 'award',
                'xp_reward'   => (int)($b['xp_reward'] ?? 10),
                'diraih'      => $isUnlocked ? 1 : 0,
                'unlocked'    => $isUnlocked,
                'is_unlocked' => $isUnlocked,
                'earned_at'   => $b['earned_at'] ?? null,
            ];
        }
        $siswa['badges'] = $badgesList;

        // 3. Ambil Riwayat Perolehan XP Siswa (Real-time Activity Log)
        $actStmt = $this->db->prepare(
            "SELECT id, xp_amount, sumber, referensi_id, keterangan, created_at
             FROM xp_log
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT 30"
        );
        $actStmt->execute([$userId]);
        $actRaw = $actStmt->fetchAll();
        $aktivitasList = [];
        foreach ($actRaw as $act) {
            $aktivitasList[] = [
                'id'         => (int)$act['id'],
                'xp'         => (int)$act['xp_amount'],
                'xp_amount'  => (int)$act['xp_amount'],
                'sumber'     => $act['sumber'],
                'judul'      => $act['keterangan'] ?? 'Aktivitas Belajar',
                'aktivitas'  => $act['keterangan'] ?? 'Aktivitas Belajar',
                'tanggal'    => $act['created_at'],
                'created_at' => $act['created_at'],
            ];
        }
        $siswa['aktivitas'] = $aktivitasList;

        // Support both direct root and ['user'] wrapping
        $res = array_merge($siswa, [
            'user' => $siswa,
        ]);

        jsonSuccess($res, 'Data profil siswa.');
    }

    public function uploadAvatar()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];

        $userId = (int)($_POST['user_id'] ?? ($input['user_id'] ?? ($_GET['user_id'] ?? 0)));
        if ($userId <= 0) {
            jsonError('User ID diperlukan.', 400);
        }

        $avatarDir = dirname(__DIR__, 2) . '/public/uploads/avatars';
        if (!is_dir($avatarDir)) {
            @mkdir($avatarDir, 0777, true);
        }

        $avatarPath = null;

        // 1. Upload via file multipart
        if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                jsonError('Format file tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF.', 400);
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                jsonError('Ukuran file maksimal 5 MB.', 400);
            }

            $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $targetPath = $avatarDir . '/' . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $avatarPath = 'public/uploads/avatars/' . $newFileName;
            } else {
                jsonError('Gagal menyimpan file foto.', 500);
            }
        }
        // 2. Upload via Base64 Image string
        elseif (!empty($input['avatar_base64']) || !empty($_POST['avatar_base64'])) {
            $base64 = $input['avatar_base64'] ?? $_POST['avatar_base64'];
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
                $ext = strtolower($matches[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                $base64 = substr($base64, strpos($base64, ',') + 1);
            } else {
                $ext = 'png';
            }
            $data = base64_decode($base64);
            if ($data !== false) {
                $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $targetPath = $avatarDir . '/' . $newFileName;
                if (file_put_contents($targetPath, $data)) {
                    $avatarPath = 'public/uploads/avatars/' . $newFileName;
                }
            }
        }
        // 3. Preset avatar / Online URL
        elseif (!empty($input['avatar_url']) || !empty($_POST['avatar_url']) || !empty($input['preset_avatar']) || !empty($_POST['preset_avatar'])) {
            $url = trim($input['avatar_url'] ?? ($_POST['avatar_url'] ?? ($input['preset_avatar'] ?? ($_POST['preset_avatar'] ?? ''))));
            if (preg_match('/^avatar_[0-9]+\.(svg|png)$/', $url)) {
                $avatarPath = 'public/img/avatars/' . $url;
            } elseif (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, 'public/')) {
                $avatarPath = $url;
            }
        }

        if (empty($avatarPath)) {
            jsonError('Tidak ada file foto atau avatar yang dipilih.', 400);
        }

        $this->db->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?")
                 ->execute([$avatarPath, $userId]);

        $fullUrl = str_starts_with($avatarPath, 'http') ? $avatarPath : base_url($avatarPath);

        jsonSuccess([
            'avatar'     => $avatarPath,
            'avatar_url' => $fullUrl,
        ], 'Foto profil berhasil diperbarui!');
    }

    /**
     * API Ganti Kata Sandi Siswa (Mandiri)
     * Untuk mengganti kata sandi sementara dari Guru
     */
    public function updatePasswordSiswa()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];

        $userId          = (int)($_POST['user_id'] ?? ($input['user_id'] ?? 0));
        $currentPassword = (string)($_POST['current_password'] ?? ($input['current_password'] ?? ''));
        $newPassword     = (string)($_POST['new_password'] ?? ($input['new_password'] ?? ''));
        $confirmPassword = (string)($_POST['confirm_password'] ?? ($input['confirm_password'] ?? ''));

        if ($userId <= 0) {
            jsonError('ID Pengguna diperlukan.', 400);
        }

        if (empty($currentPassword) || empty($newPassword)) {
            jsonError('Kata sandi saat ini dan kata sandi baru wajib diisi.', 400);
        }

        if (strlen($newPassword) < 6) {
            jsonError('Kata sandi baru minimal harus 6 karakter.', 400);
        }

        if (!empty($confirmPassword) && $newPassword !== $confirmPassword) {
            jsonError('Konfirmasi kata sandi baru tidak cocok.', 400);
        }

        $stmt = $this->db->prepare("SELECT id, password, nama FROM users WHERE id = ? AND role = 'siswa' LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonError('Akun siswa tidak ditemukan.', 404);
        }

        if (!verifyPassword($currentPassword, $user['password'])) {
            jsonError('Kata sandi saat ini (atau kata sandi sementara dari Guru) tidak sesuai.', 400);
        }

        $hash = hashPassword($newPassword);
        $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
                 ->execute([$hash, $userId]);

        try {
            createNotification($this->db, [
                'user_id'     => $userId,
                'role_target' => 'siswa',
                'tipe'        => 'sistem',
                'judul'       => 'Kata Sandi Berhasil Diperbarui 🔒',
                'pesan'       => 'Kata sandi akun Anda berhasil diganti. Gunakan kata sandi baru ini untuk login berikutnya.',
                'icon'        => 'fa-key',
                'color'       => 'emerald',
                'link'        => base_url('siswa/profil'),
            ]);
        } catch (Throwable $e) {}

        jsonSuccess([
            'user_id' => $userId,
        ], 'Kata sandi berhasil diperbarui! Silakan gunakan kata sandi baru Anda.');
    }

    public function getLeaderboard()
    {
        $stmt = $this->db->query(
            "SELECT u.id, u.nama, u.kelas, u.avatar,
                    COALESCE(g.total_xp, 0) AS total_xp,
                    COALESCE(g.level, 1) AS level
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             WHERE u.role = 'siswa' AND u.is_active = 1
             ORDER BY total_xp DESC, level DESC
             LIMIT 50"
        );
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $av = $r['avatar'] ?? '';
            $r['avatar_url'] = !empty($av) ? (str_starts_with($av, 'http') ? $av : base_url($av)) : '';
        }
        unset($r);
        jsonSuccess($rows, 'Papan peringkat siswa.');
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function _addXP(int $userId, int $amount, string $type, int $refId): void
    {
        if ($amount <= 0 || $userId <= 0) return;

        $now = date('Y-m-d H:i:s');
        $this->db->prepare(
            "INSERT INTO xp_log (user_id, xp_amount, sumber, referensi_id, keterangan, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$userId, $amount, $type, $refId, 'Menyelesaikan ' . ucfirst($type) . ' (+' . $amount . ' XP)', $now]);

        $cek = $this->db->prepare("SELECT total_xp FROM gamifikasi WHERE user_id = ?");
        $cek->execute([$userId]);
        $row = $cek->fetch();

        if ($row) {
            $newXp = (int)$row['total_xp'] + $amount;
            $newLevel = calculateLevel($newXp);
            $this->db->prepare("UPDATE gamifikasi SET total_xp = ?, level = ?, last_activity = ?, updated_at = ? WHERE user_id = ?")
                     ->execute([$newXp, $newLevel, date('Y-m-d'), $now, $userId]);
        } else {
            $newXp = $amount;
            $newLevel = calculateLevel($newXp);
            $this->db->prepare("INSERT INTO gamifikasi (user_id, total_xp, level, streak_days, last_activity, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?, ?)")
                     ->execute([$userId, $newXp, $newLevel, date('Y-m-d'), $now, $now]);
        }

        // Auto-award badges secara real-time
        $this->_checkAndAwardBadges($userId, $newXp, $newLevel);
    }

    private function _checkAndAwardBadges(int $userId, int $xp, int $level): void
    {
        if ($userId <= 0) return;

        try {
            // Hitung kuis lulus
            $kuisCount = (int)$this->db->query("SELECT COUNT(*) FROM hasil_kuis WHERE user_id = {$userId} AND (status = 'lulus' OR skor >= 70)")->fetchColumn();

            // Hitung simulasi lulus
            $simCount = (int)$this->db->query("SELECT COUNT(*) FROM simulasi_hasil WHERE user_id = {$userId} AND is_correct = 1")->fetchColumn();

            // Ambil streak
            $streak = (int)$this->db->query("SELECT COALESCE(streak_days, 1) FROM gamifikasi WHERE user_id = {$userId}")->fetchColumn();

            // Ambil semua badges
            $badges = $this->db->query("SELECT * FROM badges")->fetchAll();

            foreach ($badges as $b) {
                $type = $b['kondisi_type'] ?? '';
                $target = (int)($b['kondisi_nilai'] ?? 0);
                $eligible = false;

                if ($type === 'level' && $level >= $target) {
                    $eligible = true;
                } elseif ($type === 'xp_total' && $xp >= $target) {
                    $eligible = true;
                } elseif ($type === 'kuis_selesai' && $kuisCount >= $target) {
                    $eligible = true;
                } elseif ($type === 'simulasi_selesai' && $simCount >= $target) {
                    $eligible = true;
                } elseif ($type === 'streak' && $streak >= $target) {
                    $eligible = true;
                }

                if ($eligible) {
                    $cek = $this->db->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?");
                    $cek->execute([$userId, (int)$b['id']]);
                    if (!$cek->fetch()) {
                        $this->db->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, NOW())")
                                 ->execute([$userId, (int)$b['id']]);
                    }
                }
            }
        } catch (Throwable $e) {
            // Abaikan jika tabel atau kolom belum tersedia
        }
    }

    private function _validateTopologyStructure(string $targetType, ?array $canvas, $targetConfigRaw = null): array
    {
        if (empty($canvas) || empty($canvas['devices'])) {
            return [
                'valid'   => false,
                'score'   => 0,
                'message' => 'Kanvas masih kosong. Letakkan perangkat dan hubungkan kabel jaringan terlebih dahulu.',
            ];
        }

        $devices = $canvas['devices'] ?? [];
        $connections = $canvas['connections'] ?? [];
        $deviceCount = count($devices);
        $connCount = count($connections);

        if ($deviceCount < 2) {
            return [
                'valid'   => false,
                'score'   => 20,
                'message' => 'Jaringan membutuhkan minimal 2 perangkat untuk dapat berkomunikasi.',
            ];
        }

        if ($connCount < 1) {
            return [
                'valid'   => false,
                'score'   => 20,
                'message' => 'Belum ada kabel yang dipasang. Pasang kabel antar-perangkat terlebih dahulu.',
            ];
        }

        // Parse target_config dari guru / database
        $tc = is_string($targetConfigRaw) ? json_decode($targetConfigRaw, true) : (is_array($targetConfigRaw) ? $targetConfigRaw : []);
        $targetType = strtolower(trim($targetType));

        // Buat peta perangkat: id => [id, type, name]
        $deviceMap = [];
        $typeCounts = ['pc' => 0, 'switch' => 0, 'hub' => 0, 'router' => 0, 'server' => 0];
        foreach ($devices as $d) {
            $id = (string)($d['id'] ?? '');
            $type = strtolower(trim($d['type'] ?? 'pc'));
            $name = $d['name'] ?? strtoupper($type);
            $deviceMap[$id] = [
                'id'   => $id,
                'type' => $type,
                'name' => $name,
            ];
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }

        // Bangun adjacency list graf & kumpulkan koneksi unik
        $adj = [];
        foreach ($deviceMap as $id => $d) {
            $adj[$id] = [];
        }

        $uniqueEdges = [];
        $pcToPcEdges = [];

        foreach ($connections as $conn) {
            $from = (string)($conn['fromId'] ?? ($conn['from'] ?? ''));
            $to   = (string)($conn['toId'] ?? ($conn['to'] ?? ''));

            if ($from === '' || $to === '' || $from === $to) continue;
            if (!isset($deviceMap[$from]) || !isset($deviceMap[$to])) continue;

            $edgeKey = $from < $to ? "$from|$to" : "$to|$from";
            if (isset($uniqueEdges[$edgeKey])) continue; // hindari duplicate edge
            $uniqueEdges[$edgeKey] = true;

            $adj[$from][] = $to;
            $adj[$to][]   = $from;

            // Deteksi kabel langsung antar PC / Server
            $typeA = $deviceMap[$from]['type'];
            $typeB = $deviceMap[$to]['type'];
            if (($typeA === 'pc' || $typeA === 'server') && ($typeB === 'pc' || $typeB === 'server')) {
                $pcToPcEdges[] = [$deviceMap[$from]['name'], $deviceMap[$to]['name']];
            }
        }

        $realConnCount = count($uniqueEdges);

        // 1. Cek Node Terisolir (Derajat = 0)
        $isolatedNodes = [];
        foreach ($adj as $id => $neighbors) {
            if (count($neighbors) === 0) {
                $isolatedNodes[] = $deviceMap[$id]['name'] . ' (' . strtoupper($deviceMap[$id]['type']) . ')';
            }
        }
        if (!empty($isolatedNodes)) {
            $listStr = implode(', ', array_slice($isolatedNodes, 0, 3));
            if (count($isolatedNodes) > 3) $listStr .= ' dan ' . (count($isolatedNodes) - 3) . ' lainnya';
            return [
                'valid'   => false,
                'score'   => 35,
                'message' => "Ada perangkat yang terisolir (belum dihubungkan kabel): {$listStr}. Hubungkan kabel atau hapus perangkat yang tidak terpakai.",
            ];
        }

        // 2. Cek Keterhubungan Graf (Connected Components via BFS)
        $visited = [];
        $nodeIds = array_keys($deviceMap);
        $queue = [$nodeIds[0]];
        $visited[$nodeIds[0]] = true;

        while (!empty($queue)) {
            $curr = array_shift($queue);
            foreach ($adj[$curr] as $nb) {
                if (!isset($visited[$nb])) {
                    $visited[$nb] = true;
                    $queue[] = $nb;
                }
            }
        }

        if (count($visited) !== count($deviceMap)) {
            $unreachedCount = count($deviceMap) - count($visited);
            return [
                'valid'   => false,
                'score'   => 40,
                'message' => "Jaringan terputus / terpisah menjadi beberapa bagian! Ada {$unreachedCount} perangkat yang tidak terhubung ke jaringan utama.",
            ];
        }

        // Kebutuhan dari kunci jawaban guru / target_config
        $minPcs = (int)($tc['min_pcs'] ?? ($tc['min_pc'] ?? 0));
        $centerDeviceReq = strtolower(trim($tc['center_device'] ?? ''));

        // ========================================================
        // ATURAN TOPOLOGI STAR
        // ========================================================
        if ($targetType === 'star') {
            $reqPc = $minPcs > 0 ? $minPcs : 3;
            $clientCount = $typeCounts['pc'] + $typeCounts['server'];

            if ($clientCount < $reqPc) {
                return [
                    'valid'   => false,
                    'score'   => 45,
                    'message' => "Topologi Star membutuhkan minimal {$reqPc} PC Client (ditemukan: {$clientCount}).",
                ];
            }

            // Topologi star harus memiliki tepat 1 konsentrator pusat (Switch atau Hub)
            $concentrators = [];
            foreach ($deviceMap as $id => $d) {
                if ($d['type'] === 'switch' || $d['type'] === 'hub') {
                    $concentrators[] = $d;
                }
            }

            if (empty($concentrators)) {
                return [
                    'valid'   => false,
                    'score'   => 30,
                    'message' => "Topologi Star wajib memiliki 1 Switch atau Hub sebagai pusat konsentrator jaringan.",
                ];
            }

            if (count($concentrators) > 1) {
                return [
                    'valid'   => false,
                    'score'   => 50,
                    'message' => "Topologi Star dasar hanya menggunakan 1 konsentrator pusat, ditemukan " . count($concentrators) . " konsentrator. Hapus Switch/Hub tambahan.",
                ];
            }

            $center = $concentrators[0];
            if ($centerDeviceReq !== '' && $centerDeviceReq !== 'any' && $center['type'] !== $centerDeviceReq) {
                return [
                    'valid'   => false,
                    'score'   => 50,
                    'message' => "Kunci jawaban guru meminta perangkat pusat bertipe " . strtoupper($centerDeviceReq) . ", tetapi Anda menggunakan " . strtoupper($center['type']) . ".",
                ];
            }

            // Tidak boleh ada kabel langsung antar PC
            if (!empty($pcToPcEdges)) {
                $pair = $pcToPcEdges[0];
                return [
                    'valid'   => false,
                    'score'   => 50,
                    'message' => "Pelanggaran Topologi Star: Terdeteksi kabel langsung antar-PC ({$pair[0]} terhubung ke {$pair[1]}). Seluruh PC harus terhubung langsung ke Switch pusat ({$center['name']})!",
                ];
            }

            // Setiap node client (PC/Server) derajatnya harus tepat 1 (hanya terhubung ke center)
            foreach ($deviceMap as $id => $d) {
                if ($d['id'] === $center['id']) continue;
                $neighbors = $adj[$id];
                if (count($neighbors) !== 1 || $neighbors[0] !== $center['id']) {
                    return [
                        'valid'   => false,
                        'score'   => 60,
                        'message' => "Perangkat {$d['name']} tidak terhubung langsung ke Switch pusat ({$center['name']}). Pada topologi Star, setiap PC harus langsung terpasang ke Switch pusat.",
                    ];
                }
            }

            // Derajat switch pusat harus sama dengan jumlah perangkat client
            if (count($adj[$center['id']]) !== $clientCount) {
                return [
                    'valid'   => false,
                    'score'   => 65,
                    'message' => "Pastikan seluruh {$clientCount} PC terhubung ke Switch pusat {$center['name']}.",
                ];
            }

            return [
                'valid'   => true,
                'score'   => 100,
                'message' => "Sempurna! Seluruh {$clientCount} PC terhubung langsung ke Switch pusat ({$center['name']}) membentuk Topologi Star yang valid.",
            ];
        }

        // ========================================================
        // ATURAN TOPOLOGI BUS
        // ========================================================
        if ($targetType === 'bus') {
            $reqPc = $minPcs > 0 ? $minPcs : 4;

            // LARANGAN KERAS: Topologi Bus Murni TIDAK BOLEH ada Switch, Hub, atau Router
            $forbiddenFound = [];
            foreach ($deviceMap as $id => $d) {
                if (in_array($d['type'], ['switch', 'hub', 'router'])) {
                    $forbiddenFound[] = $d['name'] . ' (' . strtoupper($d['type']) . ')';
                }
            }
            if (!empty($forbiddenFound)) {
                $listStr = implode(', ', $forbiddenFound);
                return [
                    'valid'   => false,
                    'score'   => 35,
                    'message' => "Pelanggaran Topologi Bus: Topologi Bus murni bekerja menggunakan kabel coaxial backbone berantai antar-PC, TIDAK BOLEH menggunakan Switch, Hub, atau Router (ditemukan: {$listStr}). Hapus perangkat tersebut dari kanvas!",
                ];
            }

            if ($deviceCount < $reqPc) {
                return [
                    'valid'   => false,
                    'score'   => 45,
                    'message' => "Topologi Bus membutuhkan minimal {$reqPc} PC Client di sepanjang jalur bus (ditemukan: {$deviceCount}).",
                ];
            }

            // Pada graf bus linier (daisy-chain):
            // Jumlah edge HARUS TEPAT n - 1
            if ($realConnCount !== $deviceCount - 1) {
                if ($realConnCount >= $deviceCount) {
                    return [
                        'valid'   => false,
                        'score'   => 50,
                        'message' => "Pelanggaran Topologi Bus: Rangkaian membentuk loop tertutup (melingkar). Topologi Bus harus terbuka pada kedua ujungnya (terminator), tidak boleh ada siklus/loop!",
                    ];
                }
                return [
                    'valid'   => false,
                    'score'   => 50,
                    'message' => "Jumlah kabel tidak sesuai untuk {$deviceCount} perangkat. Dibutuhkan tepat " . ($deviceCount - 1) . " kabel berantai.",
                ];
            }

            // Derajat node pada Topologi Bus:
            // Tepat 2 node memiliki derajat = 1 (ujung / terminator)
            // Seluruh node lainnya memiliki derajat = 2 (tengah rantai)
            $endNodes = 0;
            $branchedNodes = [];
            foreach ($adj as $id => $neighbors) {
                $deg = count($neighbors);
                if ($deg === 1) {
                    $endNodes++;
                } elseif ($deg > 2) {
                    $branchedNodes[] = $deviceMap[$id]['name'] . " ({$deg} kabel)";
                }
            }

            if (!empty($branchedNodes)) {
                $listStr = implode(', ', $branchedNodes);
                return [
                    'valid'   => false,
                    'score'   => 50,
                    'message' => "Pelanggaran Topologi Bus: Ditemukan percabangan bintang pada {$listStr}. Topologi Bus tidak boleh memiliki cabang, harus membentuk satu jalur rantai lurus berurutan!",
                ];
            }

            if ($endNodes !== 2) {
                return [
                    'valid'   => false,
                    'score'   => 55,
                    'message' => "Topologi Bus harus memiliki tepat 2 komputer ujung (terminator) dan sisanya terhubung berantai di tengah.",
                ];
            }

            return [
                'valid'   => true,
                'score'   => 100,
                'message' => "Hebat! Struktur jalur kabel Bus (daisy-chain) tersusun lurus dengan 2 terminator ujung secara sempurna tanpa switch.",
            ];
        }

        // ========================================================
        // ATURAN TOPOLOGI RING
        // ========================================================
        if ($targetType === 'ring') {
            $reqPc = $minPcs > 0 ? $minPcs : 4;

            // Topologi ring murni tidak menggunakan switch/hub konsentrator di tengah
            $forbiddenFound = [];
            foreach ($deviceMap as $id => $d) {
                if (in_array($d['type'], ['switch', 'hub', 'router'])) {
                    $forbiddenFound[] = $d['name'] . ' (' . strtoupper($d['type']) . ')';
                }
            }
            if (!empty($forbiddenFound)) {
                $listStr = implode(', ', $forbiddenFound);
                return [
                    'valid'   => false,
                    'score'   => 35,
                    'message' => "Pelanggaran Topologi Ring: Topologi Ring murni menghubungkan PC langsung secara melingkar tertutup, tidak menggunakan Switch atau Router di tengah (ditemukan: {$listStr}). Hapus perangkat tersebut.",
                ];
            }

            if ($deviceCount < $reqPc) {
                return [
                    'valid'   => false,
                    'score'   => 45,
                    'message' => "Topologi Ring membutuhkan minimal {$reqPc} PC Client dalam satu loop tertutup (ditemukan: {$deviceCount}).",
                ];
            }

            // Jumlah kabel pada graf sirkular harus tepat sama dengan jumlah node (|E| = |V|)
            if ($realConnCount !== $deviceCount) {
                if ($realConnCount < $deviceCount) {
                    return [
                        'valid'   => false,
                        'score'   => 55,
                        'message' => "Rangkaian Ring belum tertutup! Ditemukan {$realConnCount} kabel dari {$deviceCount} perangkat. Hubungkan kedua ujung untuk menutup lingkaran.",
                    ];
                } else {
                    return [
                        'valid'   => false,
                        'score'   => 55,
                        'message' => "Ditemukan terlalu banyak kabel ({$realConnCount} kabel untuk {$deviceCount} perangkat). Topologi Ring murni hanya membutuhkan tepat 1 kabel per node tanpa kabel silang.",
                    ];
                }
            }

            // SETIAP node pada Topologi Ring harus memiliki derajat TEPAT 2
            foreach ($adj as $id => $neighbors) {
                $deg = count($neighbors);
                if ($deg !== 2) {
                    return [
                        'valid'   => false,
                        'score'   => 60,
                        'message' => "Perangkat {$deviceMap[$id]['name']} memiliki {$deg} koneksi kabel. Pada topologi Ring, SETIAP perangkat harus terhubung ke tepat 2 tetangga (sebelum & sesudah).",
                    ];
                }
            }

            return [
                'valid'   => true,
                'score'   => 100,
                'message' => "Luar biasa! Topologi Ring melingkar tertutup (token passing ring) berhasil diverifikasi dengan sempurna.",
            ];
        }

        // ========================================================
        // ATURAN TOPOLOGI MESH
        // ========================================================
        if ($targetType === 'mesh') {
            $reqPc = $minPcs > 0 ? $minPcs : 4;
            if ($deviceCount < $reqPc) {
                return [
                    'valid'   => false,
                    'score'   => 45,
                    'message' => "Topologi Mesh membutuhkan minimal {$reqPc} perangkat (ditemukan: {$deviceCount}).",
                ];
            }

            // Setiap node minimal derajat >= 2
            foreach ($adj as $id => $neighbors) {
                if (count($neighbors) < 2) {
                    return [
                        'valid'   => false,
                        'score'   => 55,
                        'message' => "Perangkat {$deviceMap[$id]['name']} hanya memiliki " . count($neighbors) . " koneksi. Topologi Mesh membutuhkan redundansi (minimal 2 koneksi per perangkat).",
                    ];
                }
            }

            return [
                'valid'   => true,
                'score'   => 100,
                'message' => "Hebat! Struktur Topologi Mesh dengan redundansi multi-jalur berhasil divalidasi.",
            ];
        }

        // ========================================================
        // ATURAN TOPOLOGI TREE
        // ========================================================
        if ($targetType === 'tree') {
            $switchCount = $typeCounts['switch'] + $typeCounts['hub'];
            $pcCount = $typeCounts['pc'] + $typeCounts['server'];

            if ($switchCount < 2) {
                return [
                    'valid'   => false,
                    'score'   => 45,
                    'message' => "Topologi Tree membutuhkan minimal 2 Switch (Switch Utama / Backbone dan Switch Cabang).",
                ];
            }
            if ($pcCount < 3) {
                return [
                    'valid'   => false,
                    'score'   => 45,
                    'message' => "Topologi Tree membutuhkan minimal 3 PC Client yang terdistribusi ke switch cabang.",
                ];
            }
            if ($realConnCount !== $deviceCount - 1) {
                return [
                    'valid'   => false,
                    'score'   => 55,
                    'message' => "Topologi Tree harus berbentuk pohon hierarki tanpa siklus/loop (tepat " . ($deviceCount - 1) . " kabel untuk {$deviceCount} node).",
                ];
            }

            return [
                'valid'   => true,
                'score'   => 100,
                'message' => "Hebat! Struktur Topologi Tree (hierarki pohon bertingkat) berhasil divalidasi.",
            ];
        }

        return [
            'valid'   => true,
            'score'   => 100,
            'message' => "Rangkaian topologi {$targetType} berhasil diverifikasi.",
        ];
    }

    // =========================================================
    // NOTIFIKASI SISWA
    // =========================================================

    public function getNotifikasi()
    {
        $userId = (int)($_GET['user_id'] ?? 0);

        try {
            $unreadSql = "SELECT COUNT(*) FROM notifikasi WHERE role_target = 'siswa' AND is_read = 0";
            $params = [];
            if ($userId > 0) {
                $unreadSql .= " AND (user_id = ? OR user_id IS NULL)";
                $params[] = $userId;
            }
            $stmtUnread = $this->db->prepare($unreadSql);
            $stmtUnread->execute($params);
            $unreadCount = (int)$stmtUnread->fetchColumn();

            $listSql = "SELECT * FROM notifikasi WHERE role_target = 'siswa'";
            $listParams = [];
            if ($userId > 0) {
                $listSql .= " AND (user_id = ? OR user_id IS NULL)";
                $listParams[] = $userId;
            }
            $listSql .= " ORDER BY created_at DESC LIMIT 30";

            $stmtList = $this->db->prepare($listSql);
            $stmtList->execute($listParams);
            $rows = $stmtList->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $r['id'] = (int)$r['id'];
                $r['user_id'] = $r['user_id'] !== null ? (int)$r['user_id'] : null;
                $r['is_read'] = (int)$r['is_read'];
                $r['time_ago'] = timeAgo($r['created_at']);
            }
            unset($r);

            jsonSuccess([
                'unread_count'  => $unreadCount,
                'notifications' => $rows,
            ], 'Data notifikasi siswa berhasil dimuat.');
        } catch (Throwable $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    public function markAllNotifikasiRead()
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
        $userId = (int)($_POST['user_id'] ?? ($input['user_id'] ?? ($_GET['user_id'] ?? 0)));

        try {
            if ($userId > 0) {
                $stmt = $this->db->prepare("UPDATE notifikasi SET is_read = 1 WHERE role_target = 'siswa' AND (user_id = ? OR user_id IS NULL)");
                $stmt->execute([$userId]);
            } else {
                $this->db->exec("UPDATE notifikasi SET is_read = 1 WHERE role_target = 'siswa'");
            }
            jsonSuccess([], 'Semua notifikasi siswa ditandai sudah dibaca.');
        } catch (Throwable $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    public function markNotifikasiRead($id = null)
    {
        $id = (int)$id;
        if ($id > 0) {
            try {
                $this->db->prepare("UPDATE notifikasi SET is_read = 1 WHERE id = ?")->execute([$id]);
                jsonSuccess([], 'Notifikasi ditandai sudah dibaca.');
            } catch (Throwable $e) {
                jsonError($e->getMessage(), 500);
            }
        }
        jsonError('ID notifikasi tidak valid.', 400);
    }
}
