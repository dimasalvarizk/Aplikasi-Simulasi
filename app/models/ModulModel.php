<?php
/**
 * ModulModel.php
 * Model untuk tabel modul dan materi
 */

require_once __DIR__ . '/../../config/database.php';

class ModulModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // ================================================================
    // MODUL
    // ================================================================

    /**
     * Ambil semua modul.
     * @param int|null $is_published  null = semua, 1 = published, 0 = draft
     */
    public function getAll(?int $is_published = null): array
    {
        $sql    = 'SELECT m.*, u.nama AS nama_guru,
                          (SELECT COUNT(*) FROM materi mt WHERE mt.modul_id = m.id AND mt.is_published = 1) AS jumlah_materi
                   FROM modul m
                   JOIN users u ON u.id = m.guru_id';
        $params = [];

        if ($is_published !== null) {
            $sql              .= ' WHERE m.is_published = :pub';
            $params[':pub']    = $is_published;
        }

        $sql .= ' ORDER BY m.urutan ASC, m.created_at ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu modul berdasarkan ID (beserta info guru).
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, u.nama AS nama_guru
             FROM modul m
             JOIN users u ON u.id = m.guru_id
             WHERE m.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Buat modul baru.
     * $data: guru_id, judul, deskripsi, urutan, is_published, thumbnail?
     */
    public function create(array $data): int|false
    {
        $sql  = 'INSERT INTO modul (guru_id, judul, deskripsi, urutan, is_published, thumbnail)
                 VALUES (:guru_id, :judul, :deskripsi, :urutan, :is_published, :thumbnail)';
        $stmt = $this->db->prepare($sql);
        $ok   = $stmt->execute([
            ':guru_id'      => $data['guru_id'],
            ':judul'        => $data['judul'],
            ':deskripsi'    => $data['deskripsi']    ?? null,
            ':urutan'       => $data['urutan']       ?? 0,
            ':is_published' => $data['is_published'] ?? 0,
            ':thumbnail'    => $data['thumbnail']    ?? null,
        ]);

        return $ok ? (int) $this->db->lastInsertId() : false;
    }

    /**
     * Update data modul.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['judul', 'deskripsi', 'urutan', 'is_published', 'thumbnail'];
        $sets    = [];
        $params  = [':id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]          = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE modul SET ' . implode(', ', $sets) . ' WHERE id = :id'
        );
        return $stmt->execute($params);
    }

    /**
     * Hapus modul (cascade ke materi).
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM modul WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ================================================================
    // MATERI
    // ================================================================

    /**
     * Ambil semua materi dalam satu modul.
     * @param int|null $is_published  null = semua, 1 = published, 0 = draft
     */
    public function getMateriByModul(int $modul_id, ?int $is_published = null): array
    {
        $sql    = 'SELECT * FROM materi WHERE modul_id = :modul_id';
        $params = [':modul_id' => $modul_id];

        if ($is_published !== null) {
            $sql              .= ' AND is_published = :pub';
            $params[':pub']    = $is_published;
        }

        $sql .= ' ORDER BY urutan ASC, created_at ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu materi berdasarkan ID.
     */
    public function getMateriById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT mt.*, m.judul AS judul_modul
             FROM materi mt
             JOIN modul m ON m.id = mt.modul_id
             WHERE mt.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Buat materi baru.
     * $data: modul_id, judul, konten, tipe, urutan, xp_reward, is_published
     */
    public function createMateri(array $data): int|false
    {
        $sql  = 'INSERT INTO materi (modul_id, judul, konten, tipe, urutan, xp_reward, is_published)
                 VALUES (:modul_id, :judul, :konten, :tipe, :urutan, :xp_reward, :is_published)';
        $stmt = $this->db->prepare($sql);
        $ok   = $stmt->execute([
            ':modul_id'    => $data['modul_id'],
            ':judul'       => $data['judul'],
            ':konten'      => $data['konten'],
            ':tipe'        => $data['tipe']        ?? 'teori',
            ':urutan'      => $data['urutan']      ?? 0,
            ':xp_reward'   => $data['xp_reward']   ?? 10,
            ':is_published'=> $data['is_published'] ?? 1,
        ]);

        return $ok ? (int) $this->db->lastInsertId() : false;
    }

    /**
     * Update materi.
     */
    public function updateMateri(int $id, array $data): bool
    {
        $allowed = ['judul', 'konten', 'tipe', 'urutan', 'xp_reward', 'is_published'];
        $sets    = [];
        $params  = [':id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]          = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE materi SET ' . implode(', ', $sets) . ' WHERE id = :id'
        );
        return $stmt->execute($params);
    }

    /**
     * Hapus materi (cascade ke progress & gambar).
     */
    public function deleteMateri(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM materi WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ----------------------------------------------------------------
    // Progress materi siswa
    // ----------------------------------------------------------------

    /**
     * Tandai materi sebagai selesai dibaca oleh siswa.
     * Return ['already_done' => bool, 'xp_earned' => int]
     */
    public function markMateriSelesai(int $user_id, int $materi_id): array
    {
        // Cek apakah sudah pernah selesai
        $stmt = $this->db->prepare(
            'SELECT id, is_completed FROM siswa_materi_progress
             WHERE user_id = :uid AND materi_id = :mid LIMIT 1'
        );
        $stmt->execute([':uid' => $user_id, ':mid' => $materi_id]);
        $existing = $stmt->fetch();

        if ($existing && $existing['is_completed']) {
            return ['already_done' => true, 'xp_earned' => 0];
        }

        // Ambil xp_reward materi
        $materi = $this->getMateriById($materi_id);
        $xp     = $materi ? (int) $materi['xp_reward'] : 10;

        if ($existing) {
            // Update row yang ada
            $this->db->prepare(
                'UPDATE siswa_materi_progress
                 SET is_completed = 1, xp_earned = :xp, completed_at = NOW()
                 WHERE user_id = :uid AND materi_id = :mid'
            )->execute([':xp' => $xp, ':uid' => $user_id, ':mid' => $materi_id]);
        } else {
            // Insert baru
            $this->db->prepare(
                'INSERT INTO siswa_materi_progress (user_id, materi_id, is_completed, xp_earned, completed_at)
                 VALUES (:uid, :mid, 1, :xp, NOW())'
            )->execute([':uid' => $user_id, ':mid' => $materi_id, ':xp' => $xp]);
        }

        return ['already_done' => false, 'xp_earned' => $xp];
    }

    /**
     * Ambil progress materi siswa pada sebuah modul.
     */
    public function getProgressByModul(int $user_id, int $modul_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT mt.id, mt.judul, mt.tipe, mt.urutan,
                    COALESCE(smp.is_completed, 0) AS is_completed,
                    smp.completed_at
             FROM materi mt
             LEFT JOIN siswa_materi_progress smp
                    ON smp.materi_id = mt.id AND smp.user_id = :uid
             WHERE mt.modul_id = :mid AND mt.is_published = 1
             ORDER BY mt.urutan ASC'
        );
        $stmt->execute([':uid' => $user_id, ':mid' => $modul_id]);
        return $stmt->fetchAll();
    }
}
