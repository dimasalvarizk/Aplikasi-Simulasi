<?php
/**
 * KuisModel.php
 * Model untuk kuis, soal, dan penilaian otomatis
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/GamifikasiModel.php';

class KuisModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // ================================================================
    // KUIS
    // ================================================================

    /**
     * Ambil semua kuis berdasarkan modul.
     */
    public function getByModul(int $modul_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT k.*, u.nama AS nama_guru,
                    (SELECT COUNT(*) FROM kuis_soal ks WHERE ks.kuis_id = k.id) AS jumlah_soal
             FROM kuis k
             JOIN users u ON u.id = k.guru_id
             WHERE k.modul_id = :mid
             ORDER BY k.created_at DESC'
        );
        $stmt->execute([':mid' => $modul_id]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu kuis beserta daftar ID soalnya.
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT k.*, m.judul AS judul_modul, u.nama AS nama_guru
             FROM kuis k
             JOIN modul m ON m.id = k.modul_id
             JOIN users u ON u.id = k.guru_id
             WHERE k.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $kuis = $stmt->fetch();

        if ($kuis) {
            $kuis['soal_ids'] = array_column(
                $this->db->prepare(
                    'SELECT soal_id FROM kuis_soal WHERE kuis_id = :kid'
                )->execute([':kid' => $id]) ? [] : [],
                'soal_id'
            );
            // Re-fetch soal_ids properly
            $s = $this->db->prepare('SELECT soal_id FROM kuis_soal WHERE kuis_id = :kid');
            $s->execute([':kid' => $id]);
            $kuis['soal_ids'] = array_column($s->fetchAll(), 'soal_id');
        }

        return $kuis;
    }

    /**
     * Buat kuis baru beserta relasi soal.
     * $data: modul_id, guru_id, judul, deskripsi?, durasi_menit, passing_grade,
     *        xp_reward_lulus, xp_reward_sempurna, max_attempt, acak_soal, is_active,
     *        soal_ids (array of soal.id)
     */
    public function create(array $data): int|false
    {
        $this->db->beginTransaction();
        try {
            $sql  = 'INSERT INTO kuis
                       (modul_id, guru_id, judul, deskripsi, durasi_menit, passing_grade,
                        xp_reward_lulus, xp_reward_sempurna, max_attempt, acak_soal, is_active)
                     VALUES
                       (:modul_id, :guru_id, :judul, :deskripsi, :durasi, :pg,
                        :xp_lulus, :xp_sempurna, :max_att, :acak, :active)';
            $this->db->prepare($sql)->execute([
                ':modul_id'    => $data['modul_id'],
                ':guru_id'     => $data['guru_id'],
                ':judul'       => $data['judul'],
                ':deskripsi'   => $data['deskripsi']        ?? null,
                ':durasi'      => $data['durasi_menit']     ?? 30,
                ':pg'          => $data['passing_grade']    ?? 70,
                ':xp_lulus'    => $data['xp_reward_lulus']  ?? 50,
                ':xp_sempurna' => $data['xp_reward_sempurna'] ?? 100,
                ':max_att'     => $data['max_attempt']      ?? 3,
                ':acak'        => $data['acak_soal']        ?? 1,
                ':active'      => $data['is_active']        ?? 1,
            ]);

            $kuisId = (int) $this->db->lastInsertId();

            // Tambahkan soal ke kuis_soal
            if (!empty($data['soal_ids'])) {
                $ins = $this->db->prepare(
                    'INSERT INTO kuis_soal (kuis_id, soal_id) VALUES (:kid, :sid)'
                );
                foreach ($data['soal_ids'] as $soalId) {
                    $ins->execute([':kid' => $kuisId, ':sid' => $soalId]);
                }
            }

            $this->db->commit();
            return $kuisId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Update kuis.
     * Jika $data['soal_ids'] diset, relasi soal akan di-refresh.
     */
    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();
        try {
            $allowed = ['judul', 'deskripsi', 'durasi_menit', 'passing_grade',
                        'xp_reward_lulus', 'xp_reward_sempurna', 'max_attempt',
                        'acak_soal', 'is_active'];
            $sets    = [];
            $params  = [':id' => $id];

            foreach ($allowed as $col) {
                if (array_key_exists($col, $data)) {
                    $sets[]          = "$col = :$col";
                    $params[":$col"] = $data[$col];
                }
            }

            if (!empty($sets)) {
                $this->db->prepare(
                    'UPDATE kuis SET ' . implode(', ', $sets) . ' WHERE id = :id'
                )->execute($params);
            }

            // Update relasi soal jika dikirim
            if (isset($data['soal_ids']) && is_array($data['soal_ids'])) {
                $this->db->prepare('DELETE FROM kuis_soal WHERE kuis_id = :kid')
                         ->execute([':kid' => $id]);
                $ins = $this->db->prepare(
                    'INSERT INTO kuis_soal (kuis_id, soal_id) VALUES (:kid, :sid)'
                );
                foreach ($data['soal_ids'] as $soalId) {
                    $ins->execute([':kid' => $id, ':sid' => $soalId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Hapus kuis.
     */
    public function delete(int $id): bool
    {
        return $this->db->prepare('DELETE FROM kuis WHERE id = :id')
                        ->execute([':id' => $id]);
    }

    // ================================================================
    // SOAL
    // ================================================================

    /**
     * Ambil soal-soal dalam kuis beserta pilihan jawaban.
     * @param bool $acak  Acak urutan soal
     */
    public function getSoalByKuis(int $kuis_id, bool $acak = true): array
    {
        $order = $acak ? 'ORDER BY RAND()' : 'ORDER BY s.id ASC';
        $sql   = "SELECT s.*
                  FROM soal s
                  JOIN kuis_soal ks ON ks.soal_id = s.id
                  WHERE ks.kuis_id = :kid
                  $order";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':kid' => $kuis_id]);
        $soals = $stmt->fetchAll();

        // Ambil pilihan untuk tiap soal
        $pilStmt = $this->db->prepare(
            'SELECT id, soal_id, teks, urutan FROM soal_pilihan
             WHERE soal_id = :sid ORDER BY urutan ASC'
        );
        foreach ($soals as &$soal) {
            $pilStmt->execute([':sid' => $soal['id']]);
            $soal['pilihan'] = $pilStmt->fetchAll();
        }
        unset($soal);

        return $soals;
    }

    // ================================================================
    // SUBMIT & PENILAIAN
    // ================================================================

    /**
     * Submit jawaban kuis siswa, hitung skor, dan simpan hasilnya.
     * @param array $jawaban_array  ['soal_id' => 'pilihan_id', ...]
     * @return array Hasil kuis: skor, status, xp_earned, detail, dll.
     */
    public function submitJawaban(int $user_id, int $kuis_id, array $jawaban_array): array
    {
        $kuis = $this->getById($kuis_id);
        if (!$kuis) {
            return ['error' => 'Kuis tidak ditemukan'];
        }

        // Cek jumlah attempt
        $stmtAtt = $this->db->prepare(
            'SELECT COUNT(*) AS cnt FROM hasil_kuis WHERE user_id = :uid AND kuis_id = :kid'
        );
        $stmtAtt->execute([':uid' => $user_id, ':kid' => $kuis_id]);
        $attCount = (int) $stmtAtt->fetch()['cnt'];

        if ($attCount >= (int) $kuis['max_attempt']) {
            return ['error' => 'Batas percobaan telah tercapai (' . $kuis['max_attempt'] . 'x)'];
        }

        // Ambil semua soal dalam kuis beserta jawaban benar
        $stmt = $this->db->prepare(
            'SELECT s.id AS soal_id, s.poin,
                    sp.id AS pilihan_benar_id
             FROM soal s
             JOIN kuis_soal ks ON ks.soal_id = s.id
             JOIN soal_pilihan sp ON sp.soal_id = s.id AND sp.is_benar = 1
             WHERE ks.kuis_id = :kid'
        );
        $stmt->execute([':kid' => $kuis_id]);
        $kunciJawaban = [];
        $totalPoin    = 0;
        foreach ($stmt->fetchAll() as $row) {
            $kunciJawaban[$row['soal_id']] = [
                'pilihan_benar_id' => $row['pilihan_benar_id'],
                'poin'             => $row['poin'],
            ];
            $totalPoin += $row['poin'];
        }

        // Hitung skor
        $totalSoal   = count($kunciJawaban);
        $totalBenar  = 0;
        $poinDiraih  = 0;
        $detailJawab = [];

        foreach ($kunciJawaban as $soalId => $kunci) {
            $pilihanUser = isset($jawaban_array[$soalId]) ? (int) $jawaban_array[$soalId] : null;
            $isBenar     = $pilihanUser !== null && $pilihanUser === (int) $kunci['pilihan_benar_id'];

            if ($isBenar) {
                $totalBenar++;
                $poinDiraih += $kunci['poin'];
            }

            $detailJawab[] = [
                'soal_id'   => $soalId,
                'pilihan_id'=> $pilihanUser,
                'is_benar'  => $isBenar ? 1 : 0,
            ];
        }

        $skor   = $totalPoin > 0 ? round(($poinDiraih / $totalPoin) * 100, 2) : 0;
        $status = $skor >= $kuis['passing_grade'] ? 'lulus' : 'tidak_lulus';

        // Cek apakah siswa sudah pernah lulus atau memperoleh reward XP kuis ini sebelumnya
        $cekLulus = $this->db->prepare(
            'SELECT id FROM hasil_kuis WHERE user_id = :uid AND kuis_id = :kid AND (status = "lulus" OR xp_earned > 0) LIMIT 1'
        );
        $cekLulus->execute([':uid' => $user_id, ':kid' => $kuis_id]);
        $sudahPernahKlaim = (bool)$cekLulus->fetch();

        // Hitung XP yang diraih sesuai nilai xp_reward yang ditentukan Guru (hanya untuk penyelesaian pertama)
        $xpEarned = 0;
        if ($status === 'lulus' && !$sudahPernahKlaim) {
            $xpEarned = (int) ($kuis['xp_reward'] ?? $kuis['xp_reward_lulus'] ?? 50);
        }

        // Simpan hasil_kuis
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO hasil_kuis
                   (user_id, kuis_id, attempt_ke, skor, total_benar, total_soal,
                    xp_earned, status, started_at, finished_at)
                 VALUES
                   (:uid, :kid, :att, :skor, :benar, :total, :xp, :status, NOW(), NOW())'
            )->execute([
                ':uid'    => $user_id,
                ':kid'    => $kuis_id,
                ':att'    => $attCount + 1,
                ':skor'   => $skor,
                ':benar'  => $totalBenar,
                ':total'  => $totalSoal,
                ':xp'     => $xpEarned,
                ':status' => $status,
            ]);

            $hasilId = (int) $this->db->lastInsertId();

            // Simpan detail jawaban
            $insDetail = $this->db->prepare(
                'INSERT INTO hasil_kuis_detail (hasil_kuis_id, soal_id, pilihan_id, is_benar)
                 VALUES (:hid, :sid, :pid, :benar)'
            );
            foreach ($detailJawab as $d) {
                $insDetail->execute([
                    ':hid'   => $hasilId,
                    ':sid'   => $d['soal_id'],
                    ':pid'   => $d['pilihan_id'],
                    ':benar' => $d['is_benar'],
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['error' => 'Gagal menyimpan hasil: ' . $e->getMessage()];
        }

        // Tambahkan XP dan cek badge
        $gamifikasi = new GamifikasiModel();
        $xpResult   = [];
        if ($xpEarned > 0) {
            $xpResult = $gamifikasi->addXP(
                $user_id, $xpEarned, 'kuis', $hasilId,
                'Kuis: ' . $kuis['judul']
            );
        } else {
            $gamifikasi->checkAndAwardBadges($user_id);
        }

        return [
            'hasil_id'    => $hasilId,
            'skor'        => $skor,
            'total_benar' => $totalBenar,
            'total_soal'  => $totalSoal,
            'status'      => $status,
            'xp_earned'   => $xpEarned,
            'level_up'    => $xpResult['level_up'] ?? false,
            'new_level'   => $xpResult['new_level'] ?? null,
            'new_badges'  => $xpResult['badges']    ?? [],
            'detail'      => $detailJawab,
        ];
    }

    // ================================================================
    // HASIL & REKAP
    // ================================================================

    /**
     * Semua hasil kuis seorang siswa untuk kuis tertentu.
     */
    public function getHasilByUser(int $user_id, int $kuis_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM hasil_kuis
             WHERE user_id = :uid AND kuis_id = :kid
             ORDER BY attempt_ke DESC'
        );
        $stmt->execute([':uid' => $user_id, ':kid' => $kuis_id]);
        return $stmt->fetchAll();
    }

    /**
     * Rekap nilai semua siswa untuk satu kuis (guru).
     */
    public function getRekapNilai(int $kuis_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.nama, u.kelas,
                    MAX(hk.skor)        AS skor_terbaik,
                    COUNT(hk.id)        AS jumlah_attempt,
                    MAX(hk.total_benar) AS benar_terbaik,
                    hk.total_soal,
                    MAX(CASE WHEN hk.status = \'lulus\' THEN 1 ELSE 0 END) AS sudah_lulus
             FROM hasil_kuis hk
             JOIN users u ON u.id = hk.user_id
             WHERE hk.kuis_id = :kid
             GROUP BY u.id, u.nama, u.kelas, hk.total_soal
             ORDER BY skor_terbaik DESC'
        );
        $stmt->execute([':kid' => $kuis_id]);
        return $stmt->fetchAll();
    }
}
