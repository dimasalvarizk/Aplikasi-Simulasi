<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/response.php';

class SiswaController
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
        requireLogin();
        requireRole('siswa');
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
        $user    = getUser();
        $userId  = (int)($user['id'] ?? $user['user_id']);

        // Data gamifikasi
        $gamif = $this->db->prepare(
            "SELECT total_xp, level, streak_days FROM gamifikasi WHERE user_id = :uid LIMIT 1"
        );
        $gamif->execute([':uid' => $userId]);
        $gamif = $gamif->fetch() ?: ['total_xp' => 0, 'level' => 1, 'streak_days' => 1];

        $xp           = (int)$gamif['total_xp'];
        $level        = calculateLevel($xp);
        $xpNextLevel  = calculateXPForNextLevel($level);
        $xpCurrentLvl = calculateXPForNextLevel($level - 1);
        $xpInLevel    = $xp - $xpCurrentLvl;
        $xpNeeded     = $xpNextLevel - $xpCurrentLvl;
        $xpProgress   = $xpNeeded > 0 ? min(100, max(0, round(($xpInLevel / $xpNeeded) * 100))) : 100;

        // Update session
        $_SESSION['user']['xp'] = $xp;
        $_SESSION['user']['level'] = $level;

        // Badges terbaru
        $badges = $this->db->prepare(
            "SELECT b.nama, b.deskripsi, b.icon, ub.earned_at
             FROM user_badges ub
             JOIN badges b ON b.id = ub.badge_id
             WHERE ub.user_id = :uid
             ORDER BY ub.earned_at DESC
             LIMIT 6"
        );
        $badges->execute([':uid' => $userId]);
        $badges = $badges->fetchAll();

        // Modul tersedia
        $modul = $this->db->query(
            "SELECT m.*, COUNT(mt.id) AS jumlah_materi
             FROM modul m
             LEFT JOIN materi mt ON mt.modul_id = m.id AND (mt.is_active = 1 OR mt.is_published = 1)
             WHERE (m.is_active = 1 OR m.is_published = 1)
             GROUP BY m.id
             ORDER BY m.urutan ASC"
        )->fetchAll();

        // Hitung progress per modul
        foreach ($modul as &$m) {
            $m['jumlah_materi'] = (int)$m['jumlah_materi'];
            if ($m['jumlah_materi'] > 0) {
                $prog = $this->db->prepare(
                    "SELECT COUNT(*) FROM progress_materi pm
                     JOIN materi mt ON mt.id = pm.materi_id
                     WHERE pm.user_id = :uid AND mt.modul_id = :mid"
                );
                $prog->execute([':uid' => $userId, ':mid' => $m['id']]);
                $dibaca = (int)$prog->fetchColumn();
                $m['progress_pct'] = min(100, round(($dibaca / $m['jumlah_materi']) * 100));
            } else {
                $m['progress_pct'] = 0;
            }
        }
        unset($m);

        // Leaderboard mini (Top 5)
        $topSiswa = $this->db->query(
            "SELECT u.id, u.nama, u.kelas, COALESCE(g.total_xp, 0) AS total_xp, COALESCE(g.level, 1) AS level
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             WHERE u.role = 'siswa' AND u.is_active = 1
             ORDER BY total_xp DESC
             LIMIT 5"
        )->fetchAll();

        // Kuis dan Simulasi aktif untuk quick link
        $kuisAktif = $this->db->query("SELECT id, judul, xp_reward FROM kuis WHERE is_active = 1 LIMIT 3")->fetchAll();
        $simulasiAktif = $this->db->query("SELECT id, judul, tipe_topologi, xp_reward FROM simulasi_challenge WHERE is_active = 1 LIMIT 3")->fetchAll();

        render('siswa/dashboard', [
            'title'         => 'Dashboard Belajar - NetSim Academy',
            'user'          => $user,
            'xp'            => $xp,
            'level'         => $level,
            'levelTitle'    => getLevelTitle($level),
            'xpNextLevel'   => $xpNextLevel,
            'xpProgress'    => $xpProgress,
            'streak'        => (int)$gamif['streak_days'],
            'badges'        => $badges,
            'modul'         => $modul,
            'topSiswa'      => $topSiswa,
            'kuisAktif'     => $kuisAktif,
            'simulasiAktif' => $simulasiAktif,
            'flash'         => ['success' => getFlash('success'), 'error' => getFlash('error')],
        ]);
    }

    // =========================================================
    // MATERI
    // =========================================================

    public function materi($id = null)
    {
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);

        if (!$id) {
            // Cari materi pertama yang ada
            $firstMateri = $this->db->query(
                "SELECT id FROM materi WHERE (is_active = 1 OR is_published = 1) ORDER BY modul_id ASC, urutan ASC LIMIT 1"
            )->fetch();
            if ($firstMateri) {
                redirect(base_url('siswa/materi/' . $firstMateri['id']));
            } else {
                setFlash('error', 'Belum ada materi pembelajaran yang aktif.');
                redirect(base_url('siswa/dashboard'));
            }
        }

        $id = (int)$id;

        $stmt = $this->db->prepare(
            "SELECT mt.*, mo.judul AS modul_judul, mo.id AS modul_id
             FROM materi mt
             JOIN modul mo ON mo.id = mt.modul_id
             WHERE mt.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $materi = $stmt->fetch();

        if (!$materi) {
            setFlash('error', 'Materi tidak ditemukan.');
            redirect(base_url('siswa/dashboard'));
        }

        // Tandai sebagai dibaca jika belum
        $cek = $this->db->prepare(
            "SELECT id FROM progress_materi WHERE user_id = :uid AND materi_id = :mid"
        );
        $cek->execute([':uid' => $userId, ':mid' => $id]);
        $sudahBaca = (bool)$cek->fetch();
        $xpEarned = 0;

        if (!$sudahBaca) {
            $now = date('Y-m-d H:i:s');
            $this->db->prepare(
                "INSERT INTO progress_materi (user_id, materi_id, read_at, is_completed, xp_earned) VALUES (:uid, :mid, :now, 1, :xp)"
            )->execute([':uid' => $userId, ':mid' => $id, ':now' => $now, ':xp' => (int)$materi['xp_reward']]);

            $xpEarned = (int)$materi['xp_reward'];
            $this->_addXP($userId, $xpEarned, 'materi', $id);
            setFlash('success', 'Materi selesai dibaca! +' . $xpEarned . ' XP berhasil ditambahkan.');

            // Real-time Notification ke Guru & Siswa
            try {
                $userNama = getUser()['nama'] ?? 'Siswa';
                createNotification($this->db, [
                    'role_target' => 'guru',
                    'tipe'        => 'materi',
                    'judul'       => 'Modul Materi Selesai Dibaca',
                    'pesan'       => $userNama . ' telah selesai membaca materi "' . $materi['judul'] . '" (+' . $xpEarned . ' XP).',
                    'icon'        => 'fa-book-open',
                    'color'       => 'indigo',
                    'link'        => base_url('guru/siswa/' . $userId),
                ]);

                createNotification($this->db, [
                    'user_id'     => $userId,
                    'role_target' => 'siswa',
                    'tipe'        => 'materi',
                    'judul'       => 'Materi Selesai Dipelajari',
                    'pesan'       => 'Selamat! Kamu telah membaca materi "' . $materi['judul'] . '" dan mendapatkan +' . $xpEarned . ' XP.',
                    'icon'        => 'fa-book-open',
                    'color'       => 'indigo',
                    'link'        => base_url('siswa/materi/' . $id),
                ]);
            } catch (Throwable $e) {}
        }

        // Daftar materi dalam modul ini untuk sidebar modul
        $materiList = $this->db->prepare(
            "SELECT m.id, m.judul, m.urutan, m.xp_reward,
                    (SELECT COUNT(*) FROM progress_materi pm WHERE pm.user_id = :uid AND pm.materi_id = m.id) AS sudah_selesai
             FROM materi m
             WHERE m.modul_id = :mid AND (m.is_active = 1 OR m.is_published = 1)
             ORDER BY m.urutan ASC"
        );
        $materiList->execute([':uid' => $userId, ':mid' => $materi['modul_id']]);
        $allMateri = $materiList->fetchAll();

        // Cari prev & next materi
        $prevId = null;
        $nextId = null;
        for ($i = 0; $i < count($allMateri); $i++) {
            if ($allMateri[$i]['id'] == $id) {
                if ($i > 0) $prevId = $allMateri[$i - 1]['id'];
                if ($i < count($allMateri) - 1) $nextId = $allMateri[$i + 1]['id'];
                break;
            }
        }

        render('siswa/materi', [
            'title'       => $materi['judul'] . ' - NetSim Academy',
            'materi'      => $materi,
            'allMateri'   => $allMateri,
            'prevId'      => $prevId,
            'nextId'      => $nextId,
            'sudahBaca'   => $sudahBaca,
            'xpEarned'    => $xpEarned,
        ]);
    }

    // =========================================================
    // KUIS
    // =========================================================

    public function kuis($id = null)
    {
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);

        if (!$id) {
            // Tampilkan daftar semua kuis yang tersedia
            $kuisList = $this->db->prepare(
                "SELECT k.*, mo.judul AS modul_judul,
                        (SELECT MAX(skor) FROM hasil_kuis WHERE user_id = :uid AND kuis_id = k.id) AS skor_tertinggi,
                        (SELECT COUNT(*) FROM hasil_kuis WHERE user_id = :uid AND kuis_id = k.id) AS total_attempt,
                        (SELECT COUNT(*) FROM soal WHERE kuis_id = k.id) AS jumlah_soal
                 FROM kuis k
                 LEFT JOIN modul mo ON mo.id = k.modul_id
                 WHERE k.is_active = 1
                 ORDER BY k.created_at ASC"
            );
            $kuisList->execute([':uid' => $userId]);
            $list = $kuisList->fetchAll();

            render('siswa/kuis_list', [
                'title'    => 'Daftar Kuis Topologi - NetSim Academy',
                'kuisList' => $list,
            ]);
            return;
        }

        $id = (int)$id;

        $kuisStmt = $this->db->prepare(
            "SELECT k.*, mo.judul AS modul_judul
             FROM kuis k
             LEFT JOIN modul mo ON mo.id = k.modul_id
             WHERE k.id = :id AND k.is_active = 1"
        );
        $kuisStmt->execute([':id' => $id]);
        $kuis = $kuisStmt->fetch();

        if (!$kuis) {
            setFlash('error', 'Kuis tidak ditemukan atau belum aktif.');
            redirect(base_url('siswa/kuis'));
        }

        // Ambil soal
        $soalStmt = $this->db->prepare("SELECT id, kuis_id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, poin, urutan FROM soal WHERE kuis_id = :id ORDER BY urutan ASC");
        $soalStmt->execute([':id' => $id]);
        $soal = $soalStmt->fetchAll();

        if ($kuis['acak_soal']) {
            shuffle($soal);
        }

        render('siswa/kuis', [
            'title'      => 'Kuis: ' . $kuis['judul'],
            'kuis'       => $kuis,
            'soal'       => $soal,
            'csrf_token' => generateToken(),
        ]);
    }

    public function kuisSubmit($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonError('Method not allowed.', 405);
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token)) {
            $raw = json_decode(file_get_contents('php://input'), true);
            $token = $raw['csrf_token'] ?? '';
        }

        if (!verifyToken($token)) {
            jsonError('Sesi kuis tidak valid.', 403);
        }

        $id     = (int)$id;
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);

        $kuisStmt = $this->db->prepare("SELECT * FROM kuis WHERE id = :id AND is_active = 1");
        $kuisStmt->execute([':id' => $id]);
        $kuis = $kuisStmt->fetch();

        if (!$kuis) {
            jsonError('Kuis tidak ditemukan.', 404);
        }

        // Jawaban siswa
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $jawaban = $_POST['jawaban'] ?? ($input['jawaban'] ?? []);

        // Ambil kunci jawaban
        $soalStmt = $this->db->prepare("SELECT id, jawaban_benar FROM soal WHERE kuis_id = :id");
        $soalStmt->execute([':id' => $id]);
        $soalList = $soalStmt->fetchAll();

        $totalSoal = count($soalList);
        $benar     = 0;

        foreach ($soalList as $s) {
            $pilihanSiswa = strtoupper(trim($jawaban[$s['id']] ?? ''));
            $kunci = strtoupper(trim($s['jawaban_benar']));
            if ($pilihanSiswa !== '' && $pilihanSiswa === $kunci) {
                $benar++;
            }
        }

        $skor = $totalSoal > 0 ? round(($benar / $totalSoal) * 100, 1) : 0;
        $passingGrade = (int)($kuis['passing_grade'] ?? 70);
        $lulus = $skor >= $passingGrade;

        // Cek apakah siswa sudah pernah lulus atau sudah pernah mendapatkan reward XP untuk kuis ini
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

        // Beri XP jika lulus hanya untuk penyelesaian lulus pertama kali (kerjakan ulang tidak menambah XP lagi)
        $xpReward = 0;
        if ($lulus && !$sudahPernahKlaim) {
            $xpReward = (int)($kuis['xp_reward'] ?? 50);
            $this->_addXP($userId, $xpReward, 'kuis', $id);
        }

        // Simpan hasil
        $now = date('Y-m-d H:i:s');
        $this->db->prepare(
            "INSERT INTO hasil_kuis (user_id, kuis_id, skor, jumlah_benar, total_benar, jumlah_soal, total_soal, xp_earned, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $userId,
            $id,
            $skor,
            $benar,
            $benar,
            $totalSoal,
            $totalSoal,
            $xpReward,
            $lulus ? 'lulus' : 'tidak_lulus',
            $now,
        ]);

        // Real-time Notification ke Portal Guru & Siswa
        try {
            $userNama = getUser()['nama'] ?? 'Siswa';
            createNotification($this->db, [
                'role_target' => 'guru',
                'tipe'        => 'kuis',
                'judul'       => $lulus ? 'Kuis Selesai: Lulus' : 'Kuis Dikerjakan: Perlu Remedial',
                'pesan'       => $userNama . ' menyelesaikan kuis "' . ($kuis['judul'] ?? 'Kuis') . '" dengan nilai ' . $skor . ($lulus ? ' (Lulus' . ($xpReward > 0 ? ', +' . $xpReward . ' XP' : '') . ')' : ' (Remedial)'),
                'icon'        => $lulus ? 'fa-check-circle' : 'fa-clipboard-question',
                'color'       => $lulus ? 'emerald' : 'amber',
                'link'        => base_url('guru/kuis/detail/' . $id),
            ]);

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
                'link'        => base_url('siswa/kuisHasil/' . $this->db->lastInsertId()),
            ]);
        } catch (Throwable $e) {}

        jsonSuccess([
            'skor'        => $skor,
            'benar'       => $benar,
            'total'       => $totalSoal,
            'lulus'       => $lulus,
            'xp_earned'   => $xpReward,
            'redirect'    => base_url('siswa/kuisHasil/' . $this->db->lastInsertId()),
        ], 'Kuis berhasil diselesaikan!');
    }

    public function kuisHasil($hasilId)
    {
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);
        $hasilId = (int)$hasilId;

        $stmt = $this->db->prepare(
            "SELECT hk.*, k.judul AS kuis_judul, k.passing_grade, k.durasi_menit
             FROM hasil_kuis hk
             JOIN kuis k ON k.id = hk.kuis_id
             WHERE hk.id = :id AND hk.user_id = :uid"
        );
        $stmt->execute([':id' => $hasilId, ':uid' => $userId]);
        $hasil = $stmt->fetch();

        if (!$hasil) {
            setFlash('error', 'Hasil kuis tidak ditemukan.');
            redirect(base_url('siswa/kuis'));
        }

        render('siswa/hasil_kuis', [
            'title' => 'Hasil Kuis: ' . $hasil['kuis_judul'],
            'hasil' => $hasil,
        ]);
    }

    // =========================================================
    // SIMULASI TOPOLOGI
    // =========================================================

    public function simulasi($id = null)
    {
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);

        if (!$id) {
            // Tampilkan list challenge simulasi
            $challenges = $this->db->prepare(
                "SELECT sc.*, mo.judul AS modul_judul,
                        (SELECT MAX(skor) FROM simulasi_hasil WHERE user_id = :uid AND challenge_id = sc.id) AS skor_tertinggi,
                        (SELECT COUNT(*) FROM simulasi_hasil WHERE user_id = :uid AND challenge_id = sc.id AND is_correct = 1) AS sudah_lulus
                 FROM simulasi_challenge sc
                 LEFT JOIN modul mo ON mo.id = sc.modul_id
                 WHERE sc.is_active = 1
                 ORDER BY sc.id ASC"
            );
            $challenges->execute([':uid' => $userId]);
            $list = $challenges->fetchAll();

            render('siswa/simulasi_list', [
                'title'      => 'Tantangan Simulasi Topologi - NetSim Academy',
                'challenges' => $list,
            ]);
            return;
        }

        $id = (int)$id;

        $stmt = $this->db->prepare(
            "SELECT s.*, mo.judul AS modul_judul FROM simulasi_challenge s
             LEFT JOIN modul mo ON mo.id = s.modul_id
             WHERE s.id = :id AND s.is_active = 1"
        );
        $stmt->execute([':id' => $id]);
        $challenge = $stmt->fetch();

        if (!$challenge) {
            setFlash('error', 'Tantangan simulasi tidak ditemukan.');
            redirect(base_url('siswa/simulasi'));
        }

        // Riwayat percobaan
        $riwayat = $this->db->prepare(
            "SELECT is_correct, skor, xp_earned, created_at FROM simulasi_hasil
             WHERE user_id = :uid AND challenge_id = :cid
             ORDER BY created_at DESC LIMIT 5"
        );
        $riwayat->execute([':uid' => $userId, ':cid' => $id]);
        $riwayat = $riwayat->fetchAll();

        render('siswa/simulasi', [
            'title'      => 'Simulasi: ' . $challenge['judul'],
            'challenge'  => $challenge,
            'riwayat'    => $riwayat,
            'csrf_token' => generateToken(),
        ]);
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

        $id     = (int)$id;
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);

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

        // Validasi topologi sesuai target_config kunci jawaban guru
        $evalResult = $this->_validateTopologyStructure($challenge['tipe_topologi'], $canvas, $challenge['target_config'] ?? null);
        $isCorrect = $evalResult['valid'];
        $skor = $isCorrect ? 100 : $evalResult['score'];
        $pesan = $evalResult['message'];

        // Cek apakah sudah pernah dapat XP untuk tantangan ini
        $cekLulus = $this->db->prepare("SELECT id FROM simulasi_hasil WHERE user_id = :uid AND challenge_id = :cid AND is_correct = 1");
        $cekLulus->execute([':uid' => $userId, ':cid' => $id]);
        $pernahLulus = (bool)$cekLulus->fetch();

        $xpReward = 0;
        if ($isCorrect && !$pernahLulus) {
            $xpReward = (int)($challenge['xp_reward'] ?? 50);
            $this->_addXP($userId, $xpReward, 'simulasi', $id);
        }

        // Simpan hasil
        $now = date('Y-m-d H:i:s');
        $this->db->prepare(
            "INSERT INTO simulasi_hasil (user_id, challenge_id, canvas_data, is_correct, skor, xp_earned, created_at)
             VALUES (:uid, :cid, :cdata, :ok, :skor, :xp, :now)"
        )->execute([
            ':uid'   => $userId,
            ':cid'   => $id,
            ':cdata' => json_encode($canvas),
            ':ok'    => $isCorrect ? 1 : 0,
            ':skor'  => $skor,
            ':xp'    => $xpReward,
            ':now'   => $now,
        ]);

        // Real-time Notification ke Portal Guru
        try {
            $userNama = getUser()['nama'] ?? 'Siswa';
            createNotification($this->db, [
                'role_target' => 'guru',
                'tipe'        => 'simulasi',
                'judul'       => $isCorrect ? 'Tantangan Topologi Berhasil' : 'Uji Coba Topologi Siswa',
                'pesan'       => $userNama . ' ' . ($isCorrect ? 'berhasil merangkai' : 'menguji rangkaian') . ' "' . ($challenge['judul'] ?? 'Simulasi Topologi') . '"' . ($isCorrect ? ' (Skor ' . $skor . ($xpReward > 0 ? ', +' . $xpReward . ' XP' : '') . ')' : ' (Skor ' . $skor . ')'),
                'icon'        => $isCorrect ? 'fa-diagram-project' : 'fa-laptop-code',
                'color'       => $isCorrect ? 'blue' : 'slate',
                'link'        => base_url('guru/siswa/' . $userId),
            ]);

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
                'link'        => base_url('siswa/simulasi/' . $id),
            ]);
        } catch (Throwable $e) {}

        jsonSuccess([
            'is_correct' => $isCorrect,
            'skor'       => $skor,
            'xp_earned'  => $xpReward,
            'feedback'   => $pesan,
        ], 'Evaluasi selesai.');
    }

    // =========================================================
    // PROFIL & LEADERBOARD
    // =========================================================

    public function profil()
    {
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);

        $user = $this->db->prepare(
            "SELECT u.*, COALESCE(g.total_xp, 0) AS total_xp, COALESCE(g.level, 1) AS level, COALESCE(g.streak_days, 1) AS streak_days
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             WHERE u.id = :id"
        );
        $user->execute([':id' => $userId]);
        $siswa = $user->fetch();

        // Semua badges dengan status kepemilikan
        $allBadges = $this->db->prepare(
            "SELECT b.*,
                    CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END AS diraih,
                    ub.earned_at
             FROM badges b
             LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = :uid
             ORDER BY diraih DESC, b.xp_reward ASC"
        );
        $allBadges->execute([':uid' => $userId]);
        $badges = $allBadges->fetchAll();

        // Riwayat aktivitas XP
        $xpLogs = $this->db->prepare(
            "SELECT * FROM xp_log WHERE user_id = :uid ORDER BY created_at DESC LIMIT 15"
        );
        $xpLogs->execute([':uid' => $userId]);
        $riwayatXp = $xpLogs->fetchAll();

        // Statistik kuis & materi
        $statKuis = $this->db->prepare("SELECT COUNT(*) AS total, AVG(skor) AS rata_skor FROM hasil_kuis WHERE user_id = :uid");
        $statKuis->execute([':uid' => $userId]);
        $kuisInfo = $statKuis->fetch();

        $statMateri = $this->db->prepare("SELECT COUNT(*) FROM progress_materi WHERE user_id = :uid");
        $statMateri->execute([':uid' => $userId]);
        $totalMateriSelesai = $statMateri->fetchColumn();

        $statSimulasi = $this->db->prepare("SELECT COUNT(*) FROM simulasi_hasil WHERE user_id = :uid AND is_correct = 1");
        $statSimulasi->execute([':uid' => $userId]);
        $totalSimulasiSelesai = $statSimulasi->fetchColumn();

        render('siswa/profil', [
            'title'                => 'Profil & Prestasi - NetSim Academy',
            'siswa'                => $siswa,
            'badges'               => $badges,
            'riwayatXp'            => $riwayatXp,
            'totalKuis'            => (int)($kuisInfo['total'] ?? 0),
            'rataSkor'             => round((float)($kuisInfo['rata_skor'] ?? 0), 1),
            'totalMateriSelesai'   => (int)$totalMateriSelesai,
            'totalSimulasiSelesai' => (int)$totalSimulasiSelesai,
            'csrf_token'           => generateToken(),
        ]);
    }

    public function uploadFoto()
    {
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);
        if ($userId <= 0) {
            redirect(base_url('login'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyToken($_POST['csrf_token'] ?? '')) {
                setFlash('error', 'Token keamanan tidak valid.');
                redirect(base_url('siswa/profil'));
            }

            // 1. Cek upload file dari galeri / disk
            if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    setFlash('error', 'Format file harus berupa JPG, PNG, WEBP, atau GIF.');
                    redirect(base_url('siswa/profil'));
                }

                if ($file['size'] > 5 * 1024 * 1024) {
                    setFlash('error', 'Ukuran foto maksimal 5 MB.');
                    redirect(base_url('siswa/profil'));
                }

                $avatarDir = dirname(__DIR__, 2) . '/public/uploads/avatars';
                if (!is_dir($avatarDir)) {
                    @mkdir($avatarDir, 0777, true);
                }

                $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $targetPath = $avatarDir . '/' . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $avatarPath = 'public/uploads/avatars/' . $newFileName;
                    $this->db->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?")
                             ->execute([$avatarPath, $userId]);

                    $sessionUser = getUser();
                    $sessionUser['avatar'] = $avatarPath;
                    setUser($sessionUser);

                    setFlash('success', 'Foto profil berhasil diunggah!');
                    redirect(base_url('siswa/profil'));
                } else {
                    setFlash('error', 'Gagal memindahkan file foto ke server.');
                    redirect(base_url('siswa/profil'));
                }
            } 
            // 2. Cek pemilihan preset avatar siap pakai
            elseif (!empty($_POST['preset_avatar'])) {
                $preset = sanitize($_POST['preset_avatar']);
                if (preg_match('/^avatar_[0-9]+\.(svg|png)$/', $preset)) {
                    $avatarPath = 'public/img/avatars/' . $preset;
                    $this->db->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?")
                             ->execute([$avatarPath, $userId]);

                    $sessionUser = getUser();
                    $sessionUser['avatar'] = $avatarPath;
                    setUser($sessionUser);

                    setFlash('success', 'Avatar profil berhasil dipilih!');
                    redirect(base_url('siswa/profil'));
                }
            }

            setFlash('error', 'Silakan pilih file foto atau salah satu avatar yang tersedia.');
            redirect(base_url('siswa/profil'));
        }

        redirect(base_url('siswa/profil'));
    }

    /**
     * Pembaruan Kata Sandi Mandiri Siswa
     * Mendukung penggantian kata sandi setelah menerima reset sementara dari Guru
     */
    public function updatePassword()
    {
        $userId = (int)(getUser()['id'] ?? (getUser()['user_id'] ?? 0));
        if ($userId <= 0) {
            redirect(base_url('login'));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('siswa/profil'));
        }

        if (!verifyToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Token keamanan kedaluwarsa. Silakan coba lagi.');
            redirect(base_url('siswa/profil'));
        }

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword     = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            setFlash('error', 'Semua kolom kata sandi wajib diisi.');
            redirect(base_url('siswa/profil'));
        }

        if (strlen($newPassword) < 6) {
            setFlash('error', 'Kata sandi baru minimal harus 6 karakter.');
            redirect(base_url('siswa/profil'));
        }

        if ($newPassword !== $confirmPassword) {
            setFlash('error', 'Konfirmasi kata sandi baru tidak cocok.');
            redirect(base_url('siswa/profil'));
        }

        // Ambil data akun siswa
        $stmt = $this->db->prepare("SELECT id, password, nama FROM users WHERE id = ? AND role = 'siswa' LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            setFlash('error', 'Data pengguna siswa tidak ditemukan.');
            redirect(base_url('login'));
        }

        // Verifikasi kata sandi lama / sementara
        if (!verifyPassword($currentPassword, $user['password'])) {
            setFlash('error', 'Kata sandi saat ini (atau kata sandi sementara dari Guru) tidak sesuai.');
            redirect(base_url('siswa/profil'));
        }

        // Update password baru dengan hash aman
        $hash = hashPassword($newPassword);
        $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
                 ->execute([$hash, $userId]);

        try {
            createNotification($this->db, [
                'user_id'     => $userId,
                'role_target' => 'siswa',
                'tipe'        => 'sistem',
                'judul'       => 'Kata Sandi Berhasil Diperbarui 🔒',
                'pesan'       => 'Kata sandi akun Anda berhasil diganti. Pastikan mengingat kata sandi baru Anda dengan baik.',
                'icon'        => 'fa-key',
                'color'       => 'emerald',
                'link'        => base_url('siswa/profil'),
            ]);
        } catch (Throwable $e) {}

        setFlash('success', 'Kata sandi berhasil diperbarui! Silakan gunakan kata sandi baru Anda untuk login berikutnya.');
        redirect(base_url('siswa/profil'));
    }

    public function leaderboard()
    {
        $userId = (int)(getUser()['id'] ?? getUser()['user_id']);

        $kelas = sanitize($_GET['kelas'] ?? '');
        $where = "WHERE u.role = 'siswa' AND u.is_active = 1";
        $params = [];

        if ($kelas !== '') {
            $where .= " AND u.kelas = :kelas";
            $params[':kelas'] = $kelas;
        }

        $stmt = $this->db->prepare(
            "SELECT u.id, u.nama, u.username, u.kelas,
                    COALESCE(g.total_xp, 0) AS total_xp,
                    COALESCE(g.level, 1) AS level,
                    (SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.id) AS total_badges
             FROM users u
             LEFT JOIN gamifikasi g ON g.user_id = u.id
             {$where}
             ORDER BY total_xp DESC, level DESC
             LIMIT 50"
        );
        $stmt->execute($params);
        $leaderboard = $stmt->fetchAll();

        // Daftar kelas untuk filter
        $kelasList = $this->db->query("SELECT DISTINCT kelas FROM users WHERE role = 'siswa' AND kelas IS NOT NULL ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

        render('siswa/leaderboard', [
            'title'       => 'Papan Peringkat (Leaderboard) - NetSim Academy',
            'leaderboard' => $leaderboard,
            'kelasList'   => $kelasList,
            'currentKelas'=> $kelas,
            'currentUserId'=> $userId,
        ]);
    }

    // =========================================================
    // PRIVATE ENGINE
    // =========================================================

    private function _addXP(int $userId, int $amount, string $type, int $refId): void
    {
        if ($amount <= 0) return;

        $now = date('Y-m-d H:i:s');
        $keterangan = 'Menyelesaikan ' . ucfirst($type) . ' (+' . $amount . ' XP)';
        if ($type === 'kasus_keamanan') {
            try {
                $kasusJudul = $this->db->query("SELECT judul FROM kasus_keamanan WHERE id = {$refId}")->fetchColumn();
                $keterangan = $kasusJudul ? 'Detektif Siber: ' . $kasusJudul . ' (+' . $amount . ' XP)' : 'Tantangan Detektif Siber (+' . $amount . ' XP)';
            } catch (Throwable $e) {}
        }

        // Catat ke xp_log
        $this->db->prepare(
            "INSERT INTO xp_log (user_id, xp_amount, sumber, referensi_id, keterangan, created_at)
             VALUES (:uid, :amount, :type, :ref, :ket, :now)"
        )->execute([
            ':uid'    => $userId,
            ':amount' => $amount,
            ':type'   => $type,
            ':ref'    => $refId,
            ':ket'    => $keterangan,
            ':now'    => $now,
        ]);

        // Cek gamifikasi record
        $cek = $this->db->prepare("SELECT total_xp FROM gamifikasi WHERE user_id = :uid");
        $cek->execute([':uid' => $userId]);
        $row = $cek->fetch();

        if ($row) {
            $newXp = (int)$row['total_xp'] + $amount;
            $newLevel = calculateLevel($newXp);
            $this->db->prepare(
                "UPDATE gamifikasi SET total_xp = :xp, level = :lvl, last_activity = :act, updated_at = :up WHERE user_id = :uid"
            )->execute([
                ':xp'  => $newXp,
                ':lvl' => $newLevel,
                ':act' => date('Y-m-d'),
                ':up'  => $now,
                ':uid' => $userId,
            ]);
        } else {
            $newXp = $amount;
            $newLevel = calculateLevel($newXp);
            $this->db->prepare(
                "INSERT INTO gamifikasi (user_id, total_xp, level, streak_days, last_activity, created_at, updated_at)
                 VALUES (?, ?, ?, 1, ?, ?, ?)"
            )->execute([
                $userId,
                $newXp,
                $newLevel,
                date('Y-m-d'),
                $now,
                $now,
            ]);
        }

        // Update session
        $_SESSION['user']['xp'] = $newXp;
        $_SESSION['user']['level'] = $newLevel;

        // Cek perolehan badges baru
        $this->_checkBadges($userId, $newXp, $newLevel);
    }

    private function _checkBadges(int $userId, int $xp, int $level): void
    {
        // Badges berdasarkan XP total
        $badgesXp = $this->db->query("SELECT id FROM badges WHERE kondisi_type = 'xp_total' AND kondisi_nilai <= {$xp}")->fetchAll(PDO::FETCH_COLUMN);
        // Badges berdasarkan level
        $badgesLvl = $this->db->query("SELECT id FROM badges WHERE kondisi_type = 'level' AND kondisi_nilai <= {$level}")->fetchAll(PDO::FETCH_COLUMN);

        $eligible = array_unique(array_merge($badgesXp, $badgesLvl));

        // Badge Detektif Siber (jika berhasil memecahkan minimal 1 kasus keamanan)
        try {
            $kasusBenar = (int)$this->db->query("SELECT COUNT(*) FROM siswa_kasus_progress WHERE user_id = {$userId} AND is_correct = 1")->fetchColumn();
            if ($kasusBenar >= 1) {
                $bId = $this->db->query("SELECT id FROM badges WHERE code = 'cyber_detective' LIMIT 1")->fetchColumn();
                if ($bId) {
                    $eligible[] = (int)$bId;
                }
            }
        } catch (Throwable $e) {}

        foreach ($eligible as $badgeId) {
            $cek = $this->db->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?");
            $cek->execute([$userId, $badgeId]);
            if (!$cek->fetch()) {
                $this->db->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)")
                         ->execute([$userId, $badgeId]);
            }
        }
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
    // REAL-TIME NOTIFIKASI PORTAL SISWA
    // =========================================================

    public function notificationsPoll()
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = (int)(getUser()['id'] ?? (getUser()['user_id'] ?? 0));

        try {
            $unreadStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM notifikasi 
                 WHERE role_target = 'siswa' AND is_read = 0 AND (user_id = ? OR user_id IS NULL)"
            );
            $unreadStmt->execute([$userId]);
            $unreadCount = (int)$unreadStmt->fetchColumn();

            $stmt = $this->db->prepare(
                "SELECT * FROM notifikasi 
                 WHERE role_target = 'siswa' AND (user_id = ? OR user_id IS NULL)
                 ORDER BY created_at DESC LIMIT 15"
            );
            $stmt->execute([$userId]);
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
        $userId = (int)(getUser()['id'] ?? (getUser()['user_id'] ?? 0));
        try {
            $stmt = $this->db->prepare(
                "UPDATE notifikasi SET is_read = 1 
                 WHERE role_target = 'siswa' AND (user_id = ? OR user_id IS NULL)"
            );
            $stmt->execute([$userId]);
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
    // TANTANGAN STUDI KASUS KEAMANAN (ANTI-PHISHING) SISWA
    // =========================================================

    public function kasusKeamanan($id = null)
    {
        $userId = (int)($_SESSION['user']['id'] ?? (getUser()['id'] ?? 0));

        // Ambil semua tantangan kasus yang dipublikasikan
        $kasusList = $this->db->query(
            "SELECT k.*, u.nama AS pembuat_nama
             FROM kasus_keamanan k
             LEFT JOIN users u ON u.id = k.guru_id
             WHERE k.is_published = 1
             ORDER BY k.urutan ASC, k.id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Ambil progress user
        $progressMap = [];
        if ($userId > 0) {
            $stmt = $this->db->prepare("SELECT * FROM siswa_kasus_progress WHERE user_id = ?");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
                $progressMap[(int)$p['kasus_id']] = $p;
            }
        }

        $totalSelesai = 0;
        $totalXpDidapat = 0;

        foreach ($kasusList as &$k) {
            $cid = (int)$k['id'];
            if (isset($progressMap[$cid])) {
                $p = $progressMap[$cid];
                $k['progress'] = $p;
                $k['sudah_dikerjakan'] = true;
                $k['is_correct'] = ((int)$p['is_correct'] === 1);
                $k['jawaban_siswa'] = (int)$p['jawaban_siswa'];
                $k['xp_earned'] = (int)$p['xp_earned'];
                if ($k['is_correct']) {
                    $totalSelesai++;
                }
                $totalXpDidapat += $k['xp_earned'];
            } else {
                $k['progress'] = null;
                $k['sudah_dikerjakan'] = false;
                $k['is_correct'] = null;
                $k['jawaban_siswa'] = null;
                $k['xp_earned'] = 0;
            }
        }
        unset($k);

        // Cek apakah siswa sudah memiliki badge 'cyber_detective'
        $hasBadge = false;
        if ($userId > 0) {
            $bStmt = $this->db->prepare("SELECT ub.id FROM user_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.user_id = ? AND b.code = 'cyber_detective'");
            $bStmt->execute([$userId]);
            $hasBadge = (bool)$bStmt->fetch();
        }

        render('siswa/kasus_keamanan', [
            'title'          => 'Detektif Siber - NetSim Academy',
            'kasusList'      => $kasusList,
            'totalKasus'     => count($kasusList),
            'totalSelesai'   => $totalSelesai,
            'totalXpDidapat' => $totalXpDidapat,
            'hasBadge'       => $hasBadge,
            'flash'          => ['success' => getFlash('success'), 'error' => getFlash('error')],
        ]);
    }

    public function kasusKeamananSubmit($id = null)
    {
        $id = (int)$id;
        if ($id <= 0) {
            redirect(base_url('siswa/kasus-keamanan'));
        }

        $userId = (int)($_SESSION['user']['id'] ?? (getUser()['id'] ?? 0));
        $jawabanRaw = $_POST['jawaban'] ?? null;

        if ($jawabanRaw === null || !in_array((string)$jawabanRaw, ['0', '1', 0, 1], true)) {
            setFlash('error', 'Silakan pilih keputusan: AMAN atau BERBAHAYA.');
            redirect(base_url('siswa/kasus-keamanan'));
        }

        $jawabanSiswa = (int)$jawabanRaw;

        try {
            $stmt = $this->db->prepare("SELECT * FROM kasus_keamanan WHERE id = ? AND is_published = 1 LIMIT 1");
            $stmt->execute([$id]);
            $kasus = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$kasus) {
                setFlash('error', 'Tantangan kasus keamanan tidak ditemukan.');
                redirect(base_url('siswa/kasus-keamanan'));
            }

            $kunciAman = (int)$kasus['is_aman'];
            $isCorrect = ($jawabanSiswa === $kunciAman);
            $xpReward = 0;
            $now = date('Y-m-d H:i:s');

            // Cek progress sebelumnya
            $pStmt = $this->db->prepare("SELECT * FROM siswa_kasus_progress WHERE user_id = ? AND kasus_id = ?");
            $pStmt->execute([$userId, $id]);
            $prev = $pStmt->fetch(PDO::FETCH_ASSOC);

            if ($prev) {
                $prevCorrect = ((int)$prev['is_correct'] === 1);
                if (!$prevCorrect && $isCorrect) {
                    $xpReward = (int)($kasus['xp_reward'] ?? 20);
                }
                $uStmt = $this->db->prepare(
                    "UPDATE siswa_kasus_progress 
                     SET jawaban_siswa = ?, is_correct = ?, xp_earned = xp_earned + ?, attempt_count = attempt_count + 1, completed_at = ?
                     WHERE id = ?"
                );
                $uStmt->execute([$jawabanSiswa, $isCorrect ? 1 : 0, $xpReward, $now, (int)$prev['id']]);
            } else {
                if ($isCorrect) {
                    $xpReward = (int)($kasus['xp_reward'] ?? 20);
                }
                $iStmt = $this->db->prepare(
                    "INSERT INTO siswa_kasus_progress (user_id, kasus_id, jawaban_siswa, is_correct, xp_earned, attempt_count, completed_at)
                     VALUES (?, ?, ?, ?, ?, 1, ?)"
                );
                $iStmt->execute([$userId, $id, $jawabanSiswa, $isCorrect ? 1 : 0, $xpReward, $now]);
            }

            if ($xpReward > 0) {
                $this->_addXP($userId, $xpReward, 'kasus_keamanan', $id);
            }

            // Cek unlock badge cyber_detective
            if ($isCorrect) {
                $bStmt = $this->db->query("SELECT * FROM badges WHERE code = 'cyber_detective' LIMIT 1");
                $badge = $bStmt ? $bStmt->fetch(PDO::FETCH_ASSOC) : null;
                if ($badge) {
                    $ubStmt = $this->db->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?");
                    $ubStmt->execute([$userId, (int)$badge['id']]);
                    if (!$ubStmt->fetch()) {
                        $this->db->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, ?)")
                                 ->execute([$userId, (int)$badge['id'], $now]);
                        if ((int)$badge['xp_reward'] > 0) {
                            $this->_addXP($userId, (int)$badge['xp_reward'], 'badge', (int)$badge['id']);
                        }
                    }
                }
            }

            // Jika AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                jsonSuccess([
                    'is_correct'    => $isCorrect,
                    'jawaban_siswa' => $jawabanSiswa,
                    'kunci_jawaban' => $kunciAman,
                    'xp_earned'     => $xpReward,
                    'ciri_bahaya'   => $kasus['ciri_bahaya'],
                    'penjelasan'    => $kasus['penjelasan'],
                ], $isCorrect ? 'Analisis Tepat! Anda mendapatkan reward XP.' : 'Analisis Kurang Tepat. Pelajari penjelasan edukasinya.');
                exit;
            }

            if ($isCorrect) {
                setFlash('success', 'Analisis TEPAT SEKALI! ' . ($xpReward > 0 ? "Kamu meraih +{$xpReward} XP!" : "Kamu telah berhasil menganalisis kasus ini."));
            } else {
                setFlash('error', 'Analisis kurang tepat. Waspadai tanda-tanda ancaman pada pesan tersebut!');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        redirect(base_url('siswa/kasus-keamanan'));
    }
}
