<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/response.php';

class GuruController
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
        requireLogin();
        requireRole('guru');
    }

    // =========================================================
    // DASHBOARD
    // =========================================================

    public function index()
    {
        $this->dashboard();
    }

    public function dashboard()
    {
        // Statistik umum
        $totalSiswa = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE role = 'siswa' AND is_active = 1")->fetchColumn();
        $totalModul = (int)$this->db->query("SELECT COUNT(*) FROM modul")->fetchColumn();
        $totalKuis  = (int)$this->db->query("SELECT COUNT(*) FROM kuis")->fetchColumn();
        $rataNilai  = (float)$this->db->query("SELECT COALESCE(AVG(skor), 0) FROM hasil_kuis")->fetchColumn();

        // Statistik Tantangan Detektif Siber (Kasus Keamanan)
        $totalKasus = 0;
        $totalKasusSelesai = 0;
        try {
            $totalKasus = (int)$this->db->query("SELECT COUNT(*) FROM kasus_keamanan WHERE is_published = 1")->fetchColumn();
            $totalKasusSelesai = (int)$this->db->query("SELECT COUNT(*) FROM siswa_kasus_progress WHERE is_correct = 1")->fetchColumn();
        } catch (Throwable $e) {}

        // Daftar modul terbaru
        $modul = $this->db->query(
            "SELECT m.*, COUNT(mt.id) AS jumlah_materi
             FROM modul m
             LEFT JOIN materi mt ON mt.modul_id = m.id
             GROUP BY m.id
             ORDER BY m.urutan ASC, m.created_at DESC
             LIMIT 5"
        )->fetchAll();

        // Siswa aktif & Papan Peringkat Terintegrasi (XP total dari Kuis, Simulasi Topologi, & Tantangan Siber)
        $siswaAktif = $this->db->query(
            "SELECT u.id, u.nama, u.username, u.email, u.kelas, u.avatar, u.last_login,
                    COALESCE(g.total_xp, 0) AS total_xp, COALESCE(g.level, 1) AS level,
                    (SELECT COUNT(*) FROM siswa_kasus_progress skp WHERE skp.user_id = u.id AND skp.is_correct = 1) AS kasus_selesai
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             WHERE u.role = 'siswa' AND u.is_active = 1
             ORDER BY g.total_xp DESC, u.last_login DESC
             LIMIT 8"
        )->fetchAll();

        // Riwayat kuis terbaru
        $kuisTerbaru = $this->db->query(
            "SELECT hk.*, u.nama AS siswa_nama, k.judul AS kuis_judul
             FROM hasil_kuis hk
             JOIN users u ON u.id = hk.user_id
             JOIN kuis k ON k.id = hk.kuis_id
             ORDER BY hk.created_at DESC
             LIMIT 6"
        )->fetchAll();

        // Riwayat aktivitas terbaru terintegrasi (Log XP: Tantangan Siber, Kuis, Simulasi, Modul)
        $riwayatAktivitas = [];
        try {
            $riwayatAktivitas = $this->db->query(
                "SELECT xl.*, u.nama AS siswa_nama, u.kelas AS siswa_kelas, u.avatar AS siswa_avatar
                 FROM xp_log xl
                 JOIN users u ON u.id = xl.user_id
                 ORDER BY xl.created_at DESC, xl.id DESC
                 LIMIT 8"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {}

        render('guru/dashboard', [
            'title'             => 'Dashboard Guru - SMP Negeri 1 Karangmoncol',
            'totalSiswa'        => $totalSiswa,
            'totalModul'        => $totalModul,
            'totalKuis'         => $totalKuis,
            'rataNilai'         => round($rataNilai, 1),
            'totalKasus'        => $totalKasus,
            'totalKasusSelesai' => $totalKasusSelesai,
            'modul'             => $modul,
            'siswaAktif'        => $siswaAktif,
            'kuisTerbaru'       => $kuisTerbaru,
            'riwayatAktivitas'  => $riwayatAktivitas,
            'flash'             => ['success' => getFlash('success'), 'error' => getFlash('error')],
        ]);
    }

    // =========================================================
    // MANAJEMEN SISWA
    // =========================================================

    public function siswa()
    {
        $search   = sanitize($_GET['q'] ?? '');
        $kelas    = sanitize($_GET['kelas'] ?? '');
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 15;
        $offset   = ($page - 1) * $perPage;

        $where  = "WHERE u.role = 'siswa' AND u.is_active = 1";
        $params = [];

        if ($search !== '') {
            $where   .= " AND (u.nama LIKE :q1 OR u.email LIKE :q2 OR u.username LIKE :q3 OR u.nisn LIKE :q4)";
            $params[':q1'] = "%{$search}%";
            $params[':q2'] = "%{$search}%";
            $params[':q3'] = "%{$search}%";
            $params[':q4'] = "%{$search}%";
        }
        if ($kelas !== '') {
            $where   .= " AND u.kelas = :kelas";
            $params[':kelas'] = $kelas;
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u {$where}");
        $countStmt->execute($params);
        $totalData = (int)$countStmt->fetchColumn();
        $totalPage = (int)ceil($totalData / $perPage);

        $params[':limit']  = $perPage;
        $params[':offset'] = $offset;

        $stmt = $this->db->prepare(
            "SELECT u.id, u.nama, u.username, u.email, u.kelas, u.nisn, u.last_login,
                    COALESCE(g.total_xp, 0) AS total_xp,
                    COALESCE(g.level, 1) AS level,
                    (SELECT AVG(skor) FROM hasil_kuis hk WHERE hk.user_id = u.id) AS rata_nilai,
                    (SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.id) AS total_badges,
                    (SELECT COUNT(*) FROM siswa_kasus_progress skp WHERE skp.user_id = u.id AND skp.is_correct = 1) AS total_kasus_selesai
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             {$where}
             ORDER BY total_xp DESC, u.nama ASC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            if ($k === ':limit' || $k === ':offset') continue;
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $siswa = $stmt->fetchAll();

        $kelasList = $this->db->query(
            "SELECT DISTINCT kelas FROM users WHERE role = 'siswa' AND kelas IS NOT NULL ORDER BY kelas"
        )->fetchAll(PDO::FETCH_COLUMN);

        if (empty($kelasList)) {
            $kelasList = ['VII A', 'VII B', 'VII C'];
        }

        render('guru/siswa', [
            'title'      => 'Data Siswa & Kemajuan - SMP Negeri 1 Karangmoncol',
            'siswa'      => $siswa,
            'search'     => $search,
            'kelas'      => $kelas,
            'kelasList'  => $kelasList,
            'page'       => $page,
            'totalPage'  => $totalPage,
            'totalData'  => $totalData,
            'csrf_token' => generateToken(),
        ]);
    }

    public function siswaCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/siswa'));
            }

            $nama     = sanitize($_POST['nama'] ?? '');
            $username = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '')));
            $nisn     = sanitize($_POST['nisn'] ?? '');
            $kelas    = sanitize($_POST['kelas'] ?? 'VII A');
            $email    = sanitize($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($nama) || empty($username)) {
                setFlash('error', 'Nama lengkap dan username siswa wajib diisi.');
                redirect(base_url('guru/siswa'));
            }

            if (empty($email)) {
                $email = $username . '@smpn1karangmoncol.sch.id';
            }

            if (empty($password)) {
                $password = 'password123';
            }

            // Cek duplikasi username
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                setFlash('error', "Username '{$username}' sudah digunakan oleh siswa lain.");
                redirect(base_url('guru/siswa'));
            }

            // Cek duplikasi email
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                setFlash('error', "Email '{$email}' sudah terdaftar.");
                redirect(base_url('guru/siswa'));
            }

            // Cek duplikasi NISN jika diisi
            if (!empty($nisn)) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE nisn = ?");
                $stmt->execute([$nisn]);
                if ($stmt->fetch()) {
                    setFlash('error', "NISN '{$nisn}' sudah terdaftar.");
                    redirect(base_url('guru/siswa'));
                }
            }

            $hash = hashPassword($password);
            $insert = $this->db->prepare(
                "INSERT INTO users (nama, username, email, password, role, kelas, nisn, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'siswa', ?, ?, 1, NOW(), NOW())"
            );
            $insert->execute([$nama, $username, $email, $hash, $kelas, $nisn]);
            $newId = (int)$this->db->lastInsertId();

            // Inisialisasi gamifikasi
            $this->db->prepare(
                "INSERT INTO gamifikasi (user_id, total_xp, level, streak_days, last_activity, created_at, updated_at)
                 VALUES (?, 0, 1, 1, CURDATE(), NOW(), NOW())"
            )->execute([$newId]);

            // Berikan badge pemula jika tersedia
            try {
                $checkBadge = $this->db->query("SELECT id FROM badges WHERE id = 1")->fetch();
                if ($checkBadge) {
                    $checkExisting = $this->db->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 1");
                    $checkExisting->execute([$newId]);
                    if (!$checkExisting->fetch()) {
                        $this->db->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 1, NOW())")->execute([$newId]);
                    }
                }
            } catch (Exception $e) {
                // Abaikan jika tabel badges belum ada
            }

            try {
                createNotification($this->db, [
                    'role_target' => 'guru',
                    'tipe'        => 'siswa',
                    'judul'       => 'Akun Siswa Baru Ditambahkan',
                    'pesan'       => "Siswa {$nama} ({$kelas}) berhasil didaftarkan ke sistem.",
                    'icon'        => 'fa-user-plus',
                    'color'       => 'amber',
                    'link'        => base_url('guru/siswa/' . $newId),
                ]);
            } catch (Throwable $e) {}

            setFlash('success', "Siswa {$nama} (Kelas {$kelas}) berhasil ditambahkan. Username: {$username}, Password: {$password}");
            redirect(base_url('guru/siswa'));
        }

        redirect(base_url('guru/siswa'));
    }

    public function siswaUpdate($id = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/siswa'));
            }

            $id       = (int)($_POST['id'] ?? $id ?? 0);
            $nama     = sanitize($_POST['nama'] ?? '');
            $username = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '')));
            $nisn     = sanitize($_POST['nisn'] ?? '');
            $kelas    = sanitize($_POST['kelas'] ?? 'VII A');
            $email    = sanitize($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if ($id <= 0 || empty($nama) || empty($username)) {
                setFlash('error', 'Data siswa tidak valid atau belum lengkap.');
                redirect(base_url('guru/siswa'));
            }

            // Pastikan user adalah siswa
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND role = 'siswa'");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                setFlash('error', 'Siswa tidak ditemukan.');
                redirect(base_url('guru/siswa'));
            }

            // Cek duplikasi username (kecuali milik sendiri)
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                setFlash('error', "Username '{$username}' sudah digunakan.");
                redirect(base_url('guru/siswa'));
            }

            if (empty($email)) {
                $email = $username . '@smpn1karangmoncol.sch.id';
            }

            // Cek duplikasi email (kecuali milik sendiri)
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                setFlash('error', "Email '{$email}' sudah terdaftar.");
                redirect(base_url('guru/siswa'));
            }

            // Update data
            if (!empty($password)) {
                $hash = hashPassword($password);
                $update = $this->db->prepare(
                    "UPDATE users SET nama = ?, username = ?, email = ?, password = ?, kelas = ?, nisn = ?, is_active = ?, updated_at = NOW() WHERE id = ?"
                );
                $update->execute([$nama, $username, $email, $hash, $kelas, $nisn, $isActive, $id]);
            } else {
                $update = $this->db->prepare(
                    "UPDATE users SET nama = ?, username = ?, email = ?, kelas = ?, nisn = ?, is_active = ?, updated_at = NOW() WHERE id = ?"
                );
                $update->execute([$nama, $username, $email, $kelas, $nisn, $isActive, $id]);
            }

            setFlash('success', "Data siswa {$nama} berhasil diperbarui.");
            redirect(base_url('guru/siswa'));
        }

        redirect(base_url('guru/siswa'));
    }

    /**
     * Otoritas Guru: Reset Kata Sandi Siswa Terpusat
     */
    public function siswaResetPassword()
    {
        requireRole('guru');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('guru/siswa'));
        }

        if (!verifyToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Token keamanan tidak valid.');
            redirect(base_url('guru/siswa'));
        }

        $id = (int)($_POST['id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? 'password123';

        if ($id <= 0) {
            setFlash('error', 'ID siswa tidak valid.');
            redirect(base_url('guru/siswa'));
        }

        if (empty($newPassword) || strlen($newPassword) < 6) {
            setFlash('error', 'Kata sandi baru minimal 6 karakter.');
            redirect(base_url('guru/siswa'));
        }

        $stmt = $this->db->prepare("SELECT id, nama, username FROM users WHERE id = ? AND role = 'siswa'");
        $stmt->execute([$id]);
        $siswa = $stmt->fetch();

        if (!$siswa) {
            setFlash('error', 'Data siswa tidak ditemukan.');
            redirect(base_url('guru/siswa'));
        }

        $hash = hashPassword($newPassword);
        $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
                 ->execute([$hash, $id]);

        setFlash('success', "Kata sandi siswa {$siswa['nama']} (@{$siswa['username']}) berhasil di-reset menjadi: {$newPassword}");
        redirect(base_url('guru/siswa'));
    }

    public function siswaDelete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            setFlash('error', 'ID siswa tidak valid.');
            redirect(base_url('guru/siswa'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/siswa'));
            }
        }

        // Cek siswa
        $stmt = $this->db->prepare("SELECT nama FROM users WHERE id = ? AND role = 'siswa'");
        $stmt->execute([$id]);
        $siswa = $stmt->fetch();

        if (!$siswa) {
            setFlash('error', 'Siswa tidak ditemukan.');
            redirect(base_url('guru/siswa'));
        }

        // Hapus riwayat dan relasi
        $this->db->prepare("DELETE FROM gamifikasi WHERE user_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM user_badges WHERE user_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM progress_materi WHERE user_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM hasil_kuis WHERE user_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM simulasi_hasil WHERE user_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM xp_log WHERE user_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM users WHERE id = ? AND role = 'siswa'")->execute([$id]);

        setFlash('success', "Siswa '{$siswa['nama']}' berhasil dihapus dari sistem.");
        redirect(base_url('guru/siswa'));
    }

    public function siswaDetail($id)
    {
        $id = (int)$id;

        $stmt = $this->db->prepare(
            "SELECT u.*, COALESCE(g.total_xp, 0) AS total_xp, COALESCE(g.level, 1) AS level, COALESCE(g.streak_days, 1) AS streak_days
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             WHERE u.id = :id AND u.role = 'siswa'"
        );
        $stmt->execute([':id' => $id]);
        $siswa = $stmt->fetch();

        if (!$siswa) {
            setFlash('error', 'Siswa tidak ditemukan.');
            redirect(base_url('guru/siswa'));
        }

        // Badges siswa
        $badges = $this->db->prepare(
            "SELECT b.*, ub.earned_at
             FROM user_badges ub
             JOIN badges b ON b.id = ub.badge_id
             WHERE ub.user_id = :id
             ORDER BY ub.earned_at DESC"
        );
        $badges->execute([':id' => $id]);
        $badgesList = $badges->fetchAll();

        // Riwayat kuis siswa
        $riwayatKuis = $this->db->prepare(
            "SELECT hk.*, k.judul AS kuis_judul, k.passing_grade
             FROM hasil_kuis hk
             JOIN kuis k ON k.id = hk.kuis_id
             WHERE hk.user_id = :id
             ORDER BY hk.created_at DESC"
        );
        $riwayatKuis->execute([':id' => $id]);
        $kuisList = $riwayatKuis->fetchAll();

        // Riwayat materi dibaca
        $riwayatMateri = $this->db->prepare(
            "SELECT pm.*, m.judul AS materi_judul, mo.judul AS modul_judul
             FROM progress_materi pm
             JOIN materi m ON m.id = pm.materi_id
             JOIN modul mo ON mo.id = m.modul_id
             WHERE pm.user_id = :id
             ORDER BY pm.read_at DESC"
        );
        $riwayatMateri->execute([':id' => $id]);
        $materiList = $riwayatMateri->fetchAll();

        // Riwayat tantangan simulasi
        $riwayatSimulasi = $this->db->prepare(
            "SELECT sh.*, sc.judul AS challenge_judul, sc.tipe_topologi
             FROM simulasi_hasil sh
             JOIN simulasi_challenge sc ON sc.id = sh.challenge_id
             WHERE sh.user_id = :id
             ORDER BY sh.created_at DESC"
        );
        $riwayatSimulasi->execute([':id' => $id]);
        $simulasiList = $riwayatSimulasi->fetchAll();

        // Riwayat tantangan studi kasus keamanan (Detektif Siber)
        $riwayatKasus = [];
        try {
            $stmtKasus = $this->db->prepare(
                "SELECT p.*, k.judul AS kasus_judul, k.kategori, k.platform, k.is_aman, k.xp_reward AS kasus_xp, k.pengirim
                 FROM siswa_kasus_progress p
                 JOIN kasus_keamanan k ON k.id = p.kasus_id
                 WHERE p.user_id = :id
                 ORDER BY p.completed_at DESC"
            );
            $stmtKasus->execute([':id' => $id]);
            $riwayatKasus = $stmtKasus->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {}

        render('guru/siswa_detail', [
            'title'           => 'Detail Siswa: ' . $siswa['nama'],
            'siswa'           => $siswa,
            'badges'          => $badgesList,
            'riwayatKuis'     => $kuisList,
            'riwayatMateri'   => $materiList,
            'riwayatSimulasi' => $simulasiList,
            'riwayatKasus'    => $riwayatKasus,
        ]);
    }

    // =========================================================
    // MANAJEMEN MODUL
    // =========================================================

    public function modul()
    {
        $modul = $this->db->query(
            "SELECT m.*, COUNT(mt.id) AS jumlah_materi
             FROM modul m
             LEFT JOIN materi mt ON mt.modul_id = m.id
             GROUP BY m.id
             ORDER BY m.urutan ASC, m.created_at DESC"
        )->fetchAll();

        render('guru/modul', [
            'title' => 'Kelola Modul Pembelajaran - SMP Negeri 1 Karangmoncol',
            'modul' => $modul,
            'flash' => ['success' => getFlash('success'), 'error' => getFlash('error')],
        ]);
    }

    public function modulCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/modul/create'));
            }

            $judul     = sanitize($_POST['judul'] ?? '');
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');
            $urutan    = (int)($_POST['urutan'] ?? 1);
            $isActive  = isset($_POST['is_active']) ? 1 : 0;
            $userId    = (int)(getUser()['id'] ?? getUser()['user_id']);

            if (empty($judul)) {
                setFlash('error', 'Judul modul wajib diisi.');
                redirect(base_url('guru/modul/create'));
            }

            $stmt = $this->db->prepare(
                "INSERT INTO modul (guru_id, created_by, judul, deskripsi, urutan, is_published, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$userId, $userId, $judul, $deskripsi, $urutan, $isActive, $isActive]);

            setFlash('success', 'Modul berhasil ditambahkan!');
            redirect(base_url('guru/modul'));
        }

        render('guru/modul_create', [
            'title'      => 'Tambah Modul Baru - SMP Negeri 1 Karangmoncol',
            'csrf_token' => generateToken(),
        ]);
    }

    public function modulEdit($id)
    {
        $id = (int)$id;
        $modul = $this->_findModul($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/modul/edit/' . $id));
            }

            $judul     = sanitize($_POST['judul'] ?? '');
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');
            $urutan    = (int)($_POST['urutan'] ?? 1);
            $isActive  = isset($_POST['is_active']) ? 1 : 0;

            if (empty($judul)) {
                setFlash('error', 'Judul modul wajib diisi.');
                redirect(base_url('guru/modul/edit/' . $id));
            }

            $stmt = $this->db->prepare(
                "UPDATE modul SET judul = ?, deskripsi = ?, urutan = ?, is_published = ?, is_active = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$judul, $deskripsi, $urutan, $isActive, $isActive, $id]);

            setFlash('success', 'Modul berhasil diperbarui!');
            redirect(base_url('guru/modul'));
        }

        render('guru/modul_edit', [
            'title'      => 'Edit Modul: ' . $modul['judul'],
            'modul'      => $modul,
            'csrf_token' => generateToken(),
        ]);
    }

    public function modulDelete($id)
    {
        $id = (int)$id;
        $this->db->prepare("DELETE FROM modul WHERE id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM materi WHERE modul_id = ?")->execute([$id]);

        setFlash('success', 'Modul beserta seluruh materinya berhasil dihapus.');
        redirect(base_url('guru/modul'));
    }

    // =========================================================
    // MANAJEMEN MATERI
    // =========================================================

    public function materi($modulId)
    {
        $modulId = (int)$modulId;
        $modul   = $this->_findModul($modulId);

        $materi = $this->db->prepare("SELECT * FROM materi WHERE modul_id = ? ORDER BY urutan ASC, id ASC");
        $materi->execute([$modulId]);
        $materiList = $materi->fetchAll();

        render('guru/materi', [
            'title'      => 'Kelola Materi: ' . $modul['judul'],
            'modul'      => $modul,
            'materi'     => $materiList,
            'csrf_token' => generateToken(),
            'flash'      => ['success' => getFlash('success'), 'error' => getFlash('error')],
        ]);
    }

    public function materiCreate($modulId)
    {
        $modulId = (int)$modulId;
        $modul   = $this->_findModul($modulId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/materi/create/' . $modulId));
            }

            $judul    = sanitize($_POST['judul'] ?? '');
            $konten   = $_POST['konten'] ?? ''; // HTML rich text
            $urutan   = (int)($_POST['urutan'] ?? 1);
            $xpReward = (int)($_POST['xp_reward'] ?? 10);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($judul) || empty($konten)) {
                setFlash('error', 'Judul dan konten materi wajib diisi.');
                redirect(base_url('guru/materi/create/' . $modulId));
            }

            $stmt = $this->db->prepare(
                "INSERT INTO materi (modul_id, judul, konten, tipe, urutan, xp_reward, is_published, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, 'teori', ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$modulId, $judul, $konten, $urutan, $xpReward, $isActive, $isActive]);

            setFlash('success', 'Materi berhasil ditambahkan!');
            redirect(base_url('guru/materi/' . $modulId));
        }

        render('guru/materi_create', [
            'title'      => 'Tambah Materi - ' . $modul['judul'],
            'modul'      => $modul,
            'csrf_token' => generateToken(),
        ]);
    }

    public function materiEdit($id)
    {
        $id     = (int)$id;
        $materi = $this->_findMateri($id);
        $modul  = $this->_findModul($materi['modul_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/materi/edit/' . $id));
            }

            $judul    = sanitize($_POST['judul'] ?? '');
            $konten   = $_POST['konten'] ?? '';
            $urutan   = (int)($_POST['urutan'] ?? 1);
            $xpReward = (int)($_POST['xp_reward'] ?? 10);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($judul) || empty($konten)) {
                setFlash('error', 'Judul dan konten materi wajib diisi.');
                redirect(base_url('guru/materi/edit/' . $id));
            }

            $stmt = $this->db->prepare(
                "UPDATE materi SET judul = ?, konten = ?, urutan = ?, xp_reward = ?, is_published = ?, is_active = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$judul, $konten, $urutan, $xpReward, $isActive, $isActive, $id]);

            setFlash('success', 'Materi berhasil diperbarui!');
            redirect(base_url('guru/materi/' . $materi['modul_id']));
        }

        render('guru/materi_edit', [
            'title'      => 'Edit Materi: ' . $materi['judul'],
            'materi'     => $materi,
            'modul'      => $modul,
            'csrf_token' => generateToken(),
        ]);
    }

    public function materiDelete($id)
    {
        $id     = (int)$id;
        $materi = $this->_findMateri($id);
        $modulId = $materi['modul_id'];

        $this->db->prepare("DELETE FROM materi WHERE id = ?")->execute([$id]);

        setFlash('success', 'Materi berhasil dihapus.');
        redirect(base_url('guru/materi/' . $modulId));
    }

    // =========================================================
    // MANAJEMEN KUIS
    // =========================================================

    public function kuis()
    {
        $kuis = $this->db->query(
            "SELECT k.*, mo.judul AS modul_judul,
                    (SELECT COUNT(*) FROM soal s WHERE s.kuis_id = k.id) AS jumlah_soal,
                    (SELECT COUNT(*) FROM hasil_kuis hk WHERE hk.kuis_id = k.id) AS total_peserta,
                    (SELECT AVG(skor) FROM hasil_kuis hk WHERE hk.kuis_id = k.id) AS rata_skor
             FROM kuis k
             LEFT JOIN modul mo ON mo.id = k.modul_id
             ORDER BY k.created_at DESC"
        )->fetchAll();

        render('guru/kuis', [
            'title' => 'Bank Kuis & Ujian - SMP Negeri 1 Karangmoncol',
            'kuis'  => $kuis,
            'flash' => ['success' => getFlash('success'), 'error' => getFlash('error')],
        ]);
    }

    public function kuisCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/kuis/create'));
            }

            $modulId      = (int)($_POST['modul_id'] ?? 1);
            $judul        = sanitize($_POST['judul'] ?? '');
            $deskripsi    = sanitize($_POST['deskripsi'] ?? '');
            $durasi       = (int)($_POST['durasi_menit'] ?? 15);
            $passingGrade = (int)($_POST['passing_grade'] ?? 70);
            $xpReward     = (int)($_POST['xp_reward'] ?? 50);
            $acakSoal     = isset($_POST['acak_soal']) ? 1 : 0;
            $isActive     = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $userId       = (int)(getUser()['id'] ?? getUser()['user_id']);

            if (empty($judul)) {
                setFlash('error', 'Judul kuis wajib diisi.');
                redirect(base_url('guru/kuis/create'));
            }

            $stmt = $this->db->prepare(
                "INSERT INTO kuis (modul_id, guru_id, created_by, judul, deskripsi, durasi_menit, passing_grade, xp_reward, xp_reward_lulus, xp_reward_sempurna, acak_soal, is_active, is_published, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$modulId, $userId, $userId, $judul, $deskripsi, $durasi, $passingGrade, $xpReward, $xpReward, $xpReward, $acakSoal, $isActive, $isActive]);
            $kuisId = (int)$this->db->lastInsertId();

            // Simpan soal-soal jika dikirim bersama form
            $soalPertanyaan = $_POST['soal_pertanyaan'] ?? [];
            $soalA          = $_POST['soal_a'] ?? [];
            $soalB          = $_POST['soal_b'] ?? [];
            $soalC          = $_POST['soal_c'] ?? [];
            $soalD          = $_POST['soal_d'] ?? [];
            $soalBenar      = $_POST['soal_benar'] ?? [];

            $insertSoal = $this->db->prepare(
                "INSERT INTO soal (kuis_id, modul_id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar, poin, urutan)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 25, ?)"
            );

            for ($i = 0; $i < count($soalPertanyaan); $i++) {
                if (!empty(trim($soalPertanyaan[$i]))) {
                    $insertSoal->execute([
                        $kuisId,
                        $modulId,
                        sanitize($soalPertanyaan[$i]),
                        sanitize($soalA[$i] ?? ''),
                        sanitize($soalB[$i] ?? ''),
                        sanitize($soalC[$i] ?? ''),
                        sanitize($soalD[$i] ?? ''),
                        strtoupper(trim($soalBenar[$i] ?? 'A')),
                        $i + 1
                    ]);
                }
            }

            setFlash('success', 'Kuis baru dan bank soal berhasil dibuat!');
            redirect(base_url('guru/kuis/detail/' . $kuisId));
        }

        $modulList = $this->db->query("SELECT id, judul FROM modul ORDER BY urutan ASC")->fetchAll();

        render('guru/kuis_create', [
            'title'      => 'Buat Kuis Baru - SMP Negeri 1 Karangmoncol',
            'modulList'  => $modulList,
            'csrf_token' => generateToken(),
        ]);
    }

    public function kuisDetail($id)
    {
        $id   = (int)$id;
        $kuis = $this->_findKuis($id);
        $modul = $this->_findModul($kuis['modul_id']);

        // Ambil semua soal dalam kuis ini
        $soal = $this->db->prepare("SELECT * FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
        $soal->execute([$id]);
        $soalList = $soal->fetchAll();

        // Ambil hasil nilai siswa
        $hasil = $this->db->prepare(
            "SELECT hk.*, u.nama AS siswa_nama, u.kelas, u.nisn
             FROM hasil_kuis hk
             JOIN users u ON u.id = hk.user_id
             WHERE hk.kuis_id = ?
             ORDER BY hk.skor DESC, hk.created_at DESC"
        );
        $hasil->execute([$id]);
        $hasilList = $hasil->fetchAll();

        render('guru/kuis_detail', [
            'title'     => 'Detail Kuis: ' . $kuis['judul'],
            'kuis'      => $kuis,
            'modul'     => $modul,
            'soal'      => $soalList,
            'hasil'     => $hasilList,
        ]);
    }

    public function kuisEdit($id)
    {
        $id   = (int)$id;
        $kuis = $this->_findKuis($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/kuis/edit/' . $id));
            }

            $judul        = sanitize($_POST['judul'] ?? '');
            $deskripsi    = sanitize($_POST['deskripsi'] ?? '');
            $durasi       = (int)($_POST['durasi_menit'] ?? 15);
            $passingGrade = (int)($_POST['passing_grade'] ?? 70);
            $xpReward     = (int)($_POST['xp_reward'] ?? 50);
            $acakSoal     = isset($_POST['acak_soal']) ? 1 : 0;
            $isActive     = isset($_POST['is_active']) ? 1 : 0;

            $stmt = $this->db->prepare(
                "UPDATE kuis SET judul = ?, deskripsi = ?, durasi_menit = ?, passing_grade = ?, xp_reward = ?, xp_reward_lulus = ?, xp_reward_sempurna = ?, acak_soal = ?, is_active = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$judul, $deskripsi, $durasi, $passingGrade, $xpReward, $xpReward, $xpReward, $acakSoal, $isActive, $id]);

            setFlash('success', 'Pengaturan kuis berhasil diperbarui.');
            redirect(base_url('guru/kuis/detail/' . $id));
        }

        $modulList = $this->db->query("SELECT id, judul FROM modul ORDER BY urutan ASC")->fetchAll();
        $soal = $this->db->prepare("SELECT * FROM soal WHERE kuis_id = ? ORDER BY urutan ASC");
        $soal->execute([$id]);

        render('guru/kuis_edit', [
            'title'      => 'Edit Kuis: ' . $kuis['judul'],
            'kuis'       => $kuis,
            'modulList'  => $modulList,
            'soal'       => $soal->fetchAll(),
            'csrf_token' => generateToken(),
        ]);
    }

    // =========================================================
    // LAPORAN & EKSPOR
    // =========================================================

    public function laporan()
    {
        $kelas = sanitize($_GET['kelas'] ?? '');
        $where = "WHERE u.role = 'siswa' AND u.is_active = 1";
        $params = [];

        if ($kelas !== '') {
            $where .= " AND u.kelas = :kelas";
            $params[':kelas'] = $kelas;
        }

        // Ambil daftar semua kuis
        $kuisList = $this->db->query("SELECT id, judul FROM kuis ORDER BY id ASC")->fetchAll();

        // Ambil daftar siswa
        $stmt = $this->db->prepare(
            "SELECT u.id, u.nama, u.kelas, u.nisn,
                    COALESCE(g.total_xp, 0) AS total_xp,
                    COALESCE(g.level, 1) AS level
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             {$where}
             ORDER BY u.kelas ASC, u.nama ASC"
        );
        $stmt->execute($params);
        $siswaList = $stmt->fetchAll();

        // Ambil semua skor kuis
        $skorMap = [];
        $skorQuery = $this->db->query("SELECT user_id, kuis_id, MAX(skor) AS skor_terbaik FROM hasil_kuis GROUP BY user_id, kuis_id")->fetchAll();
        foreach ($skorQuery as $sq) {
            $skorMap[$sq['user_id']][$sq['kuis_id']] = (float)$sq['skor_terbaik'];
        }

        // Hitung statistik
        foreach ($siswaList as &$s) {
            $nilaiSiswa = $skorMap[$s['id']] ?? [];
            $s['nilai_kuis'] = $nilaiSiswa;
            $s['rata_nilai'] = count($nilaiSiswa) > 0 ? round(array_sum($nilaiSiswa) / count($nilaiSiswa), 1) : 0;
        }
        unset($s);

        $kelasList = $this->db->query("SELECT DISTINCT kelas FROM users WHERE role = 'siswa' AND kelas IS NOT NULL ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

        render('guru/laporan', [
            'title'        => 'Rekapitulasi Nilai & Laporan Kelas - SMP Negeri 1 Karangmoncol',
            'siswaList'    => $siswaList,
            'kuisList'     => $kuisList,
            'kelasList'    => $kelasList,
            'currentKelas' => $kelas,
        ]);
    }

    public function exportCsv()
    {
        $kelas = sanitize($_GET['kelas'] ?? '');
        $where = "WHERE u.role = 'siswa' AND u.is_active = 1";
        $params = [];

        if ($kelas !== '') {
            $where .= " AND u.kelas = :kelas";
            $params[':kelas'] = $kelas;
        }

        $kuisList = $this->db->query("SELECT id, judul FROM kuis ORDER BY id ASC")->fetchAll();

        $stmt = $this->db->prepare(
            "SELECT u.id, u.nama, u.kelas, u.nisn,
                    COALESCE(g.total_xp, 0) AS total_xp,
                    COALESCE(g.level, 1) AS level
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             {$where}
             ORDER BY u.kelas ASC, u.nama ASC"
        );
        $stmt->execute($params);
        $siswaList = $stmt->fetchAll();

        $skorMap = [];
        $skorQuery = $this->db->query("SELECT user_id, kuis_id, MAX(skor) AS skor_terbaik FROM hasil_kuis GROUP BY user_id, kuis_id")->fetchAll();
        foreach ($skorQuery as $sq) {
            $skorMap[$sq['user_id']][$sq['kuis_id']] = (float)$sq['skor_terbaik'];
        }

        $filename = 'rekap_nilai_topologi_' . ($kelas ? str_replace(' ', '_', $kelas) . '_' : '') . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 for Excel

        // Header CSV
        $header = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Total XP', 'Level'];
        foreach ($kuisList as $k) {
            $header[] = $k['judul'];
        }
        $header[] = 'Rata-rata Nilai';
        fputcsv($out, $header);

        // Data rows
        $no = 1;
        foreach ($siswaList as $s) {
            $nilaiSiswa = $skorMap[$s['id']] ?? [];
            $rata = count($nilaiSiswa) > 0 ? round(array_sum($nilaiSiswa) / count($nilaiSiswa), 1) : 0;

            $row = [
                $no++,
                $s['nisn'] ?? '-',
                $s['nama'],
                $s['kelas'] ?? '-',
                $s['total_xp'],
                $s['level'],
            ];

            foreach ($kuisList as $k) {
                $row[] = isset($nilaiSiswa[$k['id']]) ? $nilaiSiswa[$k['id']] : '-';
            }

            $row[] = $rata;
            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }

    // =========================================================
    // SIMULASI TOPOLOGI (LAB GURU)
    // =========================================================

    public function simulasi($id = null)
    {
        if (!$id) {
            // Tampilkan daftar tantangan simulasi topologi + statistik siswa
            $challenges = $this->db->query(
                "SELECT sc.*, mo.judul AS modul_judul,
                        (SELECT COUNT(DISTINCT user_id) FROM simulasi_hasil WHERE challenge_id = sc.id AND is_correct = 1) AS jumlah_siswa_lulus,
                        (SELECT COUNT(*) FROM simulasi_hasil WHERE challenge_id = sc.id) AS total_percobaan
                 FROM simulasi_challenge sc
                 LEFT JOIN modul mo ON mo.id = sc.modul_id
                 ORDER BY sc.id ASC"
            )->fetchAll();

            $totalSiswa = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
            $modulList  = $this->db->query("SELECT id, judul FROM modul ORDER BY urutan ASC")->fetchAll();

            render('guru/simulasi_list', [
                'title'       => 'Bank Soal Simulasi Topologi — SMP Negeri 1 Karangmoncol',
                'currentPage' => 'simulasi',
                'challenges'  => $challenges,
                'totalSiswa'  => $totalSiswa,
                'modulList'   => $modulList,
                'csrf_token'  => generateToken(),
            ]);
            return;
        }

        $id = (int)$id;
        $stmt = $this->db->prepare(
            "SELECT s.*, mo.judul AS modul_judul FROM simulasi_challenge s
             LEFT JOIN modul mo ON mo.id = s.modul_id
             WHERE s.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $challenge = $stmt->fetch();

        if (!$challenge) {
            setFlash('error', 'Tantangan simulasi tidak ditemukan.');
            redirect(base_url('guru/simulasi'));
        }

        // Siswa yang telah menyelesaikan tantangan ini
        $siswaLulus = $this->db->prepare(
            "SELECT u.nama, u.username, u.kelas, sh.skor, sh.created_at
             FROM simulasi_hasil sh
             JOIN users u ON u.id = sh.user_id
             WHERE sh.challenge_id = :cid AND sh.is_correct = 1
             ORDER BY sh.created_at DESC"
        );
        $siswaLulus->execute([':cid' => $id]);
        $siswaLulusList = $siswaLulus->fetchAll();

        render('guru/simulasi', [
            'title'       => 'Simulasi Topologi: ' . $challenge['judul'] . ' — SMP Negeri 1 Karangmoncol',
            'currentPage' => 'simulasi',
            'challenge'   => $challenge,
            'siswaLulus'  => $siswaLulusList,
            'csrf_token'  => generateToken(),
        ]);
    }

    public function simulasiCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/simulasi'));
            }

            $judul        = trim($_POST['judul'] ?? '');
            $modulId      = (int)($_POST['modul_id'] ?? 1);
            $tipeTopologi = strtolower(trim($_POST['tipe_topologi'] ?? 'star'));
            $xpReward     = max(10, (int)($_POST['xp_reward'] ?? 50));
            $deskripsi    = trim($_POST['deskripsi'] ?? '');
            $instruksi    = trim($_POST['instruksi'] ?? '');
            $isActive     = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $guruId       = (int)(getUser()['id'] ?? 1);

            if (empty($judul)) {
                setFlash('error', 'Judul soal simulasi wajib diisi.');
                redirect(base_url('guru/simulasi'));
            }

            if (empty($instruksi)) {
                $instruksi = "Rangkai topologi " . ucfirst($tipeTopologi) . " sesuai instruksi pembelajaran.";
            }

            $minPc        = max(1, (int)($_POST['min_pc'] ?? 3));
            $minSwitch    = max(0, (int)($_POST['min_switch'] ?? 1));
            $minCables    = max(1, (int)($_POST['min_cables'] ?? 3));
            $needServer   = (int)($_POST['need_server'] ?? 0);

            $targetConfig = [
                'type'        => $tipeTopologi,
                'min_pc'      => $minPc,
                'min_switch'  => $minSwitch,
                'min_cables'  => $minCables,
                'need_server' => $needServer,
            ];

            // Deteksi apakah kolom created_by tersedia di tabel simulasi_challenge
            $hasCreatedBy = false;
            try {
                $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
                if ($driver === 'sqlite') {
                    $hasCreatedBy = true;
                } else {
                    $checkCol = $this->db->query("SHOW COLUMNS FROM simulasi_challenge LIKE 'created_by'")->fetch();
                    $hasCreatedBy = !empty($checkCol);
                }
            } catch (Throwable $e) {
                $hasCreatedBy = false;
            }

            if ($hasCreatedBy) {
                $stmt = $this->db->prepare(
                    "INSERT INTO simulasi_challenge 
                     (modul_id, guru_id, created_by, judul, deskripsi, tipe_topologi, instruksi, target_config, xp_reward, is_active, created_at)
                     VALUES (:mid, :gid, :created_by, :judul, :desk, :tipe, :instruksi, :target, :xp, :active, NOW())"
                );
                $stmt->execute([
                    ':mid'        => $modulId,
                    ':gid'        => $guruId,
                    ':created_by' => $guruId,
                    ':judul'      => $judul,
                    ':desk'       => $deskripsi,
                    ':tipe'       => $tipeTopologi,
                    ':instruksi'  => $instruksi,
                    ':target'     => json_encode($targetConfig),
                    ':xp'         => $xpReward,
                    ':active'     => $isActive,
                ]);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO simulasi_challenge 
                     (modul_id, guru_id, judul, deskripsi, tipe_topologi, instruksi, target_config, xp_reward, is_active, created_at)
                     VALUES (:mid, :gid, :judul, :desk, :tipe, :instruksi, :target, :xp, :active, NOW())"
                );
                $stmt->execute([
                    ':mid'        => $modulId,
                    ':gid'        => $guruId,
                    ':judul'      => $judul,
                    ':desk'       => $deskripsi,
                    ':tipe'       => $tipeTopologi,
                    ':instruksi'  => $instruksi,
                    ':target'     => json_encode($targetConfig),
                    ':xp'         => $xpReward,
                    ':active'     => $isActive,
                ]);
            }

            setFlash('success', 'Soal tantangan simulasi baru berhasil ditambahkan dan otomatis dapat dikerjakan siswa!');
            redirect(base_url('guru/simulasi'));
        }

        redirect(base_url('guru/simulasi'));
    }

    public function simulasiEdit($id = null)
    {
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('guru/simulasi'));
            }

            $judul        = trim($_POST['judul'] ?? '');
            $modulId      = (int)($_POST['modul_id'] ?? 1);
            $tipeTopologi = strtolower(trim($_POST['tipe_topologi'] ?? 'star'));
            $xpReward     = max(10, (int)($_POST['xp_reward'] ?? 50));
            $deskripsi    = trim($_POST['deskripsi'] ?? '');
            $instruksi    = trim($_POST['instruksi'] ?? '');
            $isActive     = isset($_POST['is_active']) ? 1 : 0;

            if (empty($judul)) {
                setFlash('error', 'Judul soal simulasi wajib diisi.');
                redirect(base_url('guru/simulasi'));
            }

            $minPc        = max(1, (int)($_POST['min_pc'] ?? 3));
            $minSwitch    = max(0, (int)($_POST['min_switch'] ?? 1));
            $minCables    = max(1, (int)($_POST['min_cables'] ?? 3));
            $needServer   = (int)($_POST['need_server'] ?? 0);

            // Pertahankan config canvas yang pernah disimpan guru jika ada
            $existing = $this->db->prepare("SELECT target_config FROM simulasi_challenge WHERE id = ?");
            $existing->execute([$id]);
            $currentConfig = json_decode($existing->fetchColumn() ?: '', true) ?: [];

            $currentConfig['type']        = $tipeTopologi;
            $currentConfig['min_pc']      = $minPc;
            $currentConfig['min_switch']  = $minSwitch;
            $currentConfig['min_cables']  = $minCables;
            $currentConfig['need_server'] = $needServer;

            $stmt = $this->db->prepare(
                "UPDATE simulasi_challenge SET 
                    modul_id = :mid,
                    judul = :judul,
                    deskripsi = :desk,
                    tipe_topologi = :tipe,
                    instruksi = :instruksi,
                    target_config = :target,
                    xp_reward = :xp,
                    is_active = :active
                 WHERE id = :id"
            );

            $stmt->execute([
                ':mid'       => $modulId,
                ':judul'     => $judul,
                ':desk'      => $deskripsi,
                ':tipe'      => $tipeTopologi,
                ':instruksi' => $instruksi,
                ':target'    => json_encode($currentConfig),
                ':xp'        => $xpReward,
                ':active'    => $isActive,
                ':id'        => $id,
            ]);

            setFlash('success', 'Soal tantangan simulasi berhasil diperbarui.');
            redirect(base_url('guru/simulasi'));
        }

        redirect(base_url('guru/simulasi'));
    }

    public function simulasiDelete($id)
    {
        $id = (int)$id;
        $this->db->prepare("DELETE FROM simulasi_hasil WHERE challenge_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM simulasi_challenge WHERE id = ?")->execute([$id]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'json'))) {
            jsonSuccess(null, 'Soal simulasi berhasil dihapus.');
        }

        setFlash('success', 'Soal tantangan simulasi berhasil dihapus.');
        redirect(base_url('guru/simulasi'));
    }

    public function simulasiSaveKey($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
        $token = $_POST['csrf_token'] ?? ($input['csrf_token'] ?? '');

        if (!verifyToken($token)) {
            jsonError('Token tidak valid.', 403);
        }

        $id = (int)$id;
        $stmt = $this->db->prepare("SELECT * FROM simulasi_challenge WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $challenge = $stmt->fetch();

        if (!$challenge) {
            jsonError('Challenge tidak ditemukan.', 404);
        }

        $canvas = $input['canvas_data'] ?? null;
        if (empty($canvas) || empty($canvas['devices'])) {
            jsonError('Canvas masih kosong. Silakan letakkan perangkat dan hubungkan kabel terlebih dahulu.', 400);
        }

        $devices = $canvas['devices'] ?? [];
        $connections = $canvas['connections'] ?? [];
        $types = array_count_values(array_column($devices, 'type'));

        $existing = json_decode($challenge['target_config'] ?? '', true) ?: [];

        $targetConfig = array_merge($existing, [
            'type'        => $challenge['tipe_topologi'],
            'min_pc'      => ($types['pc'] ?? 0) + ($types['server'] ?? 0),
            'min_switch'  => ($types['switch'] ?? 0) + ($types['hub'] ?? 0),
            'min_router'  => ($types['router'] ?? 0),
            'min_server'  => ($types['server'] ?? 0),
            'min_cables'  => count($connections),
            'devices'     => $devices,
            'connections' => $connections,
            'saved_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->db->prepare("UPDATE simulasi_challenge SET target_config = :tc WHERE id = :id")->execute([
            ':tc' => json_encode($targetConfig),
            ':id' => $id,
        ]);

        jsonSuccess([
            'target_config' => $targetConfig,
        ], 'Rangkaian topologi di canvas ini berhasil disimpan sebagai Kunci Jawaban standar acuan siswa!');
    }

    public function simulasiSubmit($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
        $token = $_POST['csrf_token'] ?? ($input['csrf_token'] ?? '');

        if (!verifyToken($token)) {
            jsonError('Token tidak valid.', 403);
        }

        $id = (int)$id;
        $stmt = $this->db->prepare("SELECT * FROM simulasi_challenge WHERE id = :id AND is_active = 1");
        $stmt->execute([':id' => $id]);
        $challenge = $stmt->fetch();

        if (!$challenge) {
            jsonError('Challenge tidak ditemukan.', 404);
        }

        $canvas = $input['canvas_data'] ?? ($_POST['canvas_data'] ?? null);
        if (is_string($canvas)) {
            $canvas = json_decode($canvas, true);
        }

        $evalResult = $this->_validateTopologyStructure($challenge['tipe_topologi'], $canvas, $challenge['target_config'] ?? null);
        $isCorrect = $evalResult['valid'];
        $skor = $isCorrect ? 100 : $evalResult['score'];
        $pesan = $evalResult['message'];

        jsonSuccess([
            'is_correct' => $isCorrect,
            'skor'       => $skor,
            'feedback'   => $pesan,
        ], 'Evaluasi desain topologi pengajar berhasil.');
    }

    private function _validateTopologyStructure(string $targetType, ?array $canvas, ?string $targetConfigRaw = null): array
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

        // Parse target_config dari database
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

    public function profil()
    {
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $guru = $stmt->fetch();

        render('guru/profil', [
            'title' => 'Profil Pengajar - SMP Negeri 1 Karangmoncol',
            'guru'  => $guru,
            'flash' => ['success' => getFlash('success'), 'error' => getFlash('error')],
        ]);
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    private function _findModul($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM modul WHERE id = ?");
        $stmt->execute([(int)$id]);
        $modul = $stmt->fetch();
        if (!$modul) {
            setFlash('error', 'Modul tidak ditemukan.');
            redirect(base_url('guru/modul'));
        }
        return $modul;
    }

    private function _findMateri($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM materi WHERE id = ?");
        $stmt->execute([(int)$id]);
        $materi = $stmt->fetch();
        if (!$materi) {
            setFlash('error', 'Materi tidak ditemukan.');
            redirect(base_url('guru/modul'));
        }
        return $materi;
    }

    private function _findKuis($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM kuis WHERE id = ?");
        $stmt->execute([(int)$id]);
        $kuis = $stmt->fetch();
        if (!$kuis) {
            setFlash('error', 'Kuis tidak ditemukan.');
            redirect(base_url('guru/kuis'));
        }
        return $kuis;
    }

    // =========================================================
    // REAL-TIME NOTIFIKASI PORTAL GURU
    // =========================================================

    public function notificationsPoll()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $unreadStmt = $this->db->query("SELECT COUNT(*) FROM notifikasi WHERE role_target = 'guru' AND is_read = 0");
            $unreadCount = (int)$unreadStmt->fetchColumn();

            $stmt = $this->db->query("SELECT * FROM notifikasi WHERE role_target = 'guru' ORDER BY created_at DESC LIMIT 12");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $r['id'] = (int)$r['id'];
                $r['is_read'] = (int)$r['is_read'];
                $r['time_ago'] = timeAgo($r['created_at']);
            }
            unset($r);

            jsonSuccess([
                'unread_count'  => $unreadCount,
                'notifications' => $rows,
            ]);
        } catch (Throwable $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    public function markAllNotificationsRead()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->db->exec("UPDATE notifikasi SET is_read = 1 WHERE role_target = 'guru'");
            jsonSuccess([], 'Semua notifikasi ditandai sudah dibaca.');
        } catch (Throwable $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    public function markNotificationRead($id = null)
    {
        header('Content-Type: application/json; charset=utf-8');
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

    // =========================================================
    // MANAJEMEN TANTANGAN STUDI KASUS KEAMANAN (ANTI-PHISHING)
    // =========================================================

    public function kasusKeamanan($id = null)
    {
        $guruId = (int)($_SESSION['user']['id'] ?? 1);

        // Jika minta data JSON (misal untuk modal edit)
        if ($id !== null && isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            $stmt = $this->db->prepare("SELECT * FROM kasus_keamanan WHERE id = ?");
            $stmt->execute([(int)$id]);
            $kasus = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($kasus) {
                jsonSuccess($kasus);
            } else {
                jsonError('Kasus tidak ditemukan.', 404);
            }
            exit;
        }

        // Ambil daftar kasus beserta ringkasan progres siswa
        $kasusList = $this->db->query(
            "SELECT k.*, u.nama AS pembuat_nama,
                    COUNT(p.id) AS total_dikerjakan,
                    COALESCE(SUM(CASE WHEN p.is_correct = 1 THEN 1 ELSE 0 END), 0) AS total_benar
             FROM kasus_keamanan k
             LEFT JOIN users u ON u.id = k.guru_id
             LEFT JOIN siswa_kasus_progress p ON p.kasus_id = k.id
             GROUP BY k.id, u.nama
             ORDER BY k.urutan ASC, k.id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Ringkasan statistik
        $totalKasus = count($kasusList);
        $totalPengerjaan = (int)$this->db->query("SELECT COUNT(*) FROM siswa_kasus_progress")->fetchColumn();
        $totalBenar = (int)$this->db->query("SELECT COUNT(*) FROM siswa_kasus_progress WHERE is_correct = 1")->fetchColumn();
        $persenAkurasi = $totalPengerjaan > 0 ? round(($totalBenar / $totalPengerjaan) * 100, 1) : 0;

        render('guru/kasus_keamanan', [
            'title'           => 'Tantangan Studi Kasus Keamanan - Guru SMP Negeri 1 Karangmoncol',
            'kasusList'       => $kasusList,
            'totalKasus'      => $totalKasus,
            'totalPengerjaan' => $totalPengerjaan,
            'persenAkurasi'   => $persenAkurasi,
            'flash'           => ['success' => getFlash('success'), 'error' => getFlash('error')],
        ]);
    }

    public function kasusKeamananCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('guru/kasus-keamanan'));
        }

        if (!verifyToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Token keamanan CSRF tidak valid.');
            redirect(base_url('guru/kasus-keamanan'));
        }

        $guruId = (int)($_SESSION['user']['id'] ?? 1);
        $judul = sanitize($_POST['judul'] ?? '');
        $kategori = sanitize($_POST['kategori'] ?? 'Phishing');
        $platform = sanitize($_POST['platform'] ?? 'whatsapp');
        $pengirim = sanitize($_POST['pengirim'] ?? '');
        $isiPesan = trim($_POST['isi_pesan'] ?? '');
        $linkUrl = sanitize($_POST['link_url'] ?? '');
        $tautanTerlihat = sanitize($_POST['tautan_terlihat'] ?? $linkUrl);
        $tautanAsli = sanitize($_POST['tautan_asli'] ?? $linkUrl);
        $lampiranNama = sanitize($_POST['lampiran_nama'] ?? '');
        $isAman = isset($_POST['is_aman']) && (int)$_POST['is_aman'] === 1 ? 1 : 0;
        $ciriBahaya = trim($_POST['ciri_bahaya'] ?? '');
        $penjelasan = trim($_POST['penjelasan'] ?? '');
        $xpReward = max(5, (int)($_POST['xp_reward'] ?? 25));
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        $urutan = (int)($_POST['urutan'] ?? 0);

        if (empty($judul) || empty($pengirim) || empty($isiPesan) || empty($penjelasan)) {
            setFlash('error', 'Semua bidang bertanda bintang (*) wajib diisi lengkap.');
            redirect(base_url('guru/kasus-keamanan'));
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO kasus_keamanan (guru_id, judul, kategori, platform, pengirim, isi_pesan, link_url, tautan_terlihat, tautan_asli, lampiran_nama, is_aman, ciri_bahaya, penjelasan, xp_reward, is_published, urutan, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $guruId, $judul, $kategori, $platform, $pengirim, $isiPesan,
                $linkUrl, $tautanTerlihat, $tautanAsli, $lampiranNama,
                $isAman, $ciriBahaya, $penjelasan, $xpReward, $isPublished, $urutan
            ]);

            setFlash('success', 'Studi kasus keamanan baru "' . htmlspecialchars($judul) . '" berhasil ditambahkan!');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menambahkan kasus: ' . $e->getMessage());
        }

        redirect(base_url('guru/kasus-keamanan'));
    }

    public function kasusKeamananEdit($id = null)
    {
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
            redirect(base_url('guru/kasus-keamanan'));
        }

        if (!verifyToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Token keamanan CSRF tidak valid.');
            redirect(base_url('guru/kasus-keamanan'));
        }

        $judul = sanitize($_POST['judul'] ?? '');
        $kategori = sanitize($_POST['kategori'] ?? 'Phishing');
        $platform = sanitize($_POST['platform'] ?? 'whatsapp');
        $pengirim = sanitize($_POST['pengirim'] ?? '');
        $isiPesan = trim($_POST['isi_pesan'] ?? '');
        $linkUrl = sanitize($_POST['link_url'] ?? '');
        $tautanTerlihat = sanitize($_POST['tautan_terlihat'] ?? $linkUrl);
        $tautanAsli = sanitize($_POST['tautan_asli'] ?? $linkUrl);
        $lampiranNama = sanitize($_POST['lampiran_nama'] ?? '');
        $isAman = isset($_POST['is_aman']) && (int)$_POST['is_aman'] === 1 ? 1 : 0;
        $ciriBahaya = trim($_POST['ciri_bahaya'] ?? '');
        $penjelasan = trim($_POST['penjelasan'] ?? '');
        $xpReward = max(5, (int)($_POST['xp_reward'] ?? 25));
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        $urutan = (int)($_POST['urutan'] ?? 0);

        if (empty($judul) || empty($pengirim) || empty($isiPesan) || empty($penjelasan)) {
            setFlash('error', 'Semua bidang bertanda bintang (*) wajib diisi lengkap.');
            redirect(base_url('guru/kasus-keamanan'));
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE kasus_keamanan 
                 SET judul = ?, kategori = ?, platform = ?, pengirim = ?, isi_pesan = ?,
                     link_url = ?, tautan_terlihat = ?, tautan_asli = ?, lampiran_nama = ?,
                     is_aman = ?, ciri_bahaya = ?, penjelasan = ?, xp_reward = ?,
                     is_published = ?, urutan = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([
                $judul, $kategori, $platform, $pengirim, $isiPesan,
                $linkUrl, $tautanTerlihat, $tautanAsli, $lampiranNama,
                $isAman, $ciriBahaya, $penjelasan, $xpReward,
                $isPublished, $urutan, $id
            ]);

            setFlash('success', 'Studi kasus keamanan berhasil diperbarui!');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal memperbarui kasus: ' . $e->getMessage());
        }

        redirect(base_url('guru/kasus-keamanan'));
    }

    public function kasusKeamananDelete($id = null)
    {
        $id = (int)$id;
        if ($id <= 0) {
            setFlash('error', 'ID kasus tidak valid.');
            redirect(base_url('guru/kasus-keamanan'));
        }

        try {
            $this->db->prepare("DELETE FROM siswa_kasus_progress WHERE kasus_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM kasus_keamanan WHERE id = ?")->execute([$id]);
            setFlash('success', 'Studi kasus keamanan berhasil dihapus.');
        } catch (Throwable $e) {
            setFlash('error', 'Gagal menghapus kasus: ' . $e->getMessage());
        }

        redirect(base_url('guru/kasus-keamanan'));
    }
}
