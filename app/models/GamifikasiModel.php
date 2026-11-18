<?php
/**
 * GamifikasiModel.php
 * Model untuk sistem gamifikasi: XP, Level, Badges, Leaderboard
 */

require_once __DIR__ . '/../../config/database.php';

class GamifikasiModel
{
    private PDO $db;

    /** XP yang dibutuhkan per level (kumulatif). Level n butuh n*100 XP */
    private const XP_PER_LEVEL = 100;

    public function __construct()
    {
        $this->db = getDB();
    }

    // ================================================================
    // PROGRESS
    // ================================================================

    /**
     * Ambil data progress lengkap siswa: XP, level, streak, badges.
     */
    public function getProgress(int $user_id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT sp.*,
                    u.nama, u.username, u.kelas, u.avatar,
                    (SELECT COUNT(*) FROM siswa_badges sb WHERE sb.user_id = sp.user_id) AS jumlah_badge
             FROM siswa_progress sp
             JOIN users u ON u.id = sp.user_id
             WHERE sp.user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $user_id]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        // Tambahkan info level berikutnya
        $row['xp_next_level'] = $this->xpUntukLevel($row['level'] + 1);
        $row['xp_current_level'] = $this->xpUntukLevel($row['level']);
        $row['xp_in_level']   = $row['total_xp'] - $row['xp_current_level'];
        $row['xp_needed']     = $row['xp_next_level'] - $row['xp_current_level'];
        $row['progress_pct']  = $row['xp_needed'] > 0
            ? min(100, round(($row['xp_in_level'] / $row['xp_needed']) * 100))
            : 100;
        $row['level_title']   = $this->getLevelTitle($row['level']);

        return $row;
    }

    // ================================================================
    // XP
    // ================================================================

    /**
     * Tambahkan XP ke siswa, update level jika naik, lalu cek badge.
     * @return array ['new_xp' => int, 'new_level' => int, 'level_up' => bool, 'badges' => array]
     */
    public function addXP(
        int    $user_id,
        int    $xp_amount,
        string $sumber,
        ?int   $referensi_id = null,
        string $keterangan   = ''
    ): array {
        // Catat di xp_log
        $this->db->prepare(
            'INSERT INTO xp_log (user_id, xp_amount, sumber, referensi_id, keterangan)
             VALUES (:uid, :xp, :sumber, :ref, :ket)'
        )->execute([
            ':uid'    => $user_id,
            ':xp'     => $xp_amount,
            ':sumber' => $sumber,
            ':ref'    => $referensi_id,
            ':ket'    => $keterangan,
        ]);

        // Ambil progress saat ini
        $stmt = $this->db->prepare(
            'SELECT total_xp, level FROM siswa_progress WHERE user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $user_id]);
        $cur = $stmt->fetch();

        if (!$cur) {
            // Buat baris baru jika belum ada
            $this->db->prepare(
                'INSERT INTO siswa_progress (user_id, total_xp, level) VALUES (:uid, 0, 1)'
            )->execute([':uid' => $user_id]);
            $cur = ['total_xp' => 0, 'level' => 1];
        }

        $newXP    = $cur['total_xp'] + $xp_amount;
        $oldLevel = (int) $cur['level'];
        $newLevel = $this->hitungLevel($newXP);
        $levelUp  = $newLevel > $oldLevel;

        $this->db->prepare(
            'UPDATE siswa_progress SET total_xp = :xp, level = :lvl, updated_at = NOW()
             WHERE user_id = :uid'
        )->execute([':xp' => $newXP, ':lvl' => $newLevel, ':uid' => $user_id]);

        // Cek dan berikan badge
        $newBadges = $this->checkAndAwardBadges($user_id);

        return [
            'new_xp'    => $newXP,
            'new_level' => $newLevel,
            'level_up'  => $levelUp,
            'badges'    => $newBadges,
        ];
    }

    // ================================================================
    // BADGES
    // ================================================================

    /**
     * Cek semua badge yang belum dimiliki siswa dan berikan yang sudah memenuhi syarat.
     * @return array Badge-badge baru yang baru saja diraih
     */
    public function checkAndAwardBadges(int $user_id): array
    {
        // Data aktual siswa
        $stmt = $this->db->prepare(
            'SELECT sp.total_xp, sp.level, sp.streak_days,
                    (SELECT COUNT(*) FROM hasil_kuis hk WHERE hk.user_id = sp.user_id) AS kuis_selesai,
                    (SELECT COUNT(*) FROM hasil_simulasi hs
                     WHERE hs.user_id = sp.user_id AND hs.is_correct = 1)              AS simulasi_selesai
             FROM siswa_progress sp WHERE sp.user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $user_id]);
        $data = $stmt->fetch();

        if (!$data) {
            return [];
        }

        // Badge yang sudah dimiliki
        $stmt = $this->db->prepare(
            'SELECT badge_id FROM siswa_badges WHERE user_id = :uid'
        );
        $stmt->execute([':uid' => $user_id]);
        $owned = array_column($stmt->fetchAll(), 'badge_id');

        // Semua badge
        $allBadges = $this->getAllBadges();
        $newBadges = [];

        foreach ($allBadges as $badge) {
            if (in_array($badge['id'], $owned, true)) {
                continue;
            }

            $earned = match ($badge['kondisi_type']) {
                'xp_total'         => $data['total_xp']       >= $badge['kondisi_nilai'],
                'level'            => $data['level']           >= $badge['kondisi_nilai'],
                'kuis_selesai'     => $data['kuis_selesai']    >= $badge['kondisi_nilai'],
                'simulasi_selesai' => $data['simulasi_selesai']>= $badge['kondisi_nilai'],
                'streak'           => $data['streak_days']     >= $badge['kondisi_nilai'],
                default            => false,
            };

            if ($earned) {
                // Berikan badge
                $this->db->prepare(
                    'INSERT IGNORE INTO siswa_badges (user_id, badge_id) VALUES (:uid, :bid)'
                )->execute([':uid' => $user_id, ':bid' => $badge['id']]);

                // Berikan XP reward badge
                if ($badge['xp_reward'] > 0) {
                    $this->addXP($user_id, $badge['xp_reward'], 'badge', $badge['id'],
                        'Badge: ' . $badge['nama']);
                }

                $newBadges[] = $badge;
            }
        }

        return $newBadges;
    }

    /**
     * Ambil semua badge milik satu siswa.
     */
    public function getBadgesByUser(int $user_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, sb.diraih_at
             FROM badges b
             JOIN siswa_badges sb ON sb.badge_id = b.id
             WHERE sb.user_id = :uid
             ORDER BY sb.diraih_at DESC'
        );
        $stmt->execute([':uid' => $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua badge yang tersedia di sistem.
     */
    public function getAllBadges(): array
    {
        return $this->db->query(
            'SELECT * FROM badges ORDER BY kondisi_nilai ASC'
        )->fetchAll();
    }

    // ================================================================
    // LEADERBOARD
    // ================================================================

    /**
     * Top siswa berdasarkan total XP.
     */
    public function getLeaderboard(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.nama, u.username, u.kelas, u.avatar,
                    sp.total_xp, sp.level, sp.streak_days,
                    (SELECT COUNT(*) FROM siswa_badges sb WHERE sb.user_id = u.id) AS jumlah_badge
             FROM siswa_progress sp
             JOIN users u ON u.id = sp.user_id
             WHERE u.role = \'siswa\' AND u.is_active = 1
             ORDER BY sp.total_xp DESC, sp.level DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Tambahkan ranking
        foreach ($rows as $i => &$row) {
            $row['rank'] = $i + 1;
        }
        unset($row);

        return $rows;
    }

    // ================================================================
    // STREAK
    // ================================================================

    /**
     * Update streak login harian siswa.
     * Dipanggil setiap kali siswa login berhasil.
     * @return array ['streak_days' => int, 'is_new_day' => bool]
     */
    public function updateStreak(int $user_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT streak_days, last_activity FROM siswa_progress WHERE user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $user_id]);
        $cur = $stmt->fetch();

        if (!$cur) {
            return ['streak_days' => 0, 'is_new_day' => false];
        }

        $today    = date('Y-m-d');
        $lastDate = $cur['last_activity'];
        $streak   = (int) $cur['streak_days'];
        $isNewDay = false;

        if ($lastDate === null || $lastDate < $today) {
            $isNewDay = true;
            if ($lastDate === date('Y-m-d', strtotime('-1 day'))) {
                $streak++; // Hari berturut-turut
            } elseif ($lastDate !== $today) {
                $streak = 1; // Reset streak
            }

            $this->db->prepare(
                'UPDATE siswa_progress SET streak_days = :s, last_activity = :d WHERE user_id = :uid'
            )->execute([':s' => $streak, ':d' => $today, ':uid' => $user_id]);
        }

        return ['streak_days' => $streak, 'is_new_day' => $isNewDay];
    }

    // ================================================================
    // HELPER PRIVATE
    // ================================================================

    /**
     * Hitung level berdasarkan total XP.
     * Formula: level = floor(total_xp / XP_PER_LEVEL) + 1, min level 1.
     */
    private function hitungLevel(int $xp): int
    {
        return max(1, (int) floor($xp / self::XP_PER_LEVEL) + 1);
    }

    /**
     * XP kumulatif minimum untuk memulai sebuah level.
     */
    private function xpUntukLevel(int $level): int
    {
        return ($level - 1) * self::XP_PER_LEVEL;
    }

    /**
     * Judul/gelar berdasarkan level.
     */
    private function getLevelTitle(int $level): string
    {
        return match (true) {
            $level >= 20 => 'Network Master',
            $level >= 15 => 'Senior Engineer',
            $level >= 10 => 'Network Engineer',
            $level >= 7  => 'Network Technician',
            $level >= 5  => 'Network Apprentice',
            $level >= 3  => 'Network Learner',
            default      => 'Pemula Jaringan',
        };
    }
}
