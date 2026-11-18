<?php
/**
 * SimulasiModel.php
 * Model untuk challenge simulasi topologi jaringan
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/GamifikasiModel.php';

class SimulasiModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // ================================================================
    // CHALLENGE
    // ================================================================

    /**
     * Ambil semua challenge berdasarkan modul.
     */
    public function getByModul(int $modul_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT sc.*, u.nama AS nama_guru
             FROM simulasi_challenge sc
             JOIN users u ON u.id = sc.guru_id
             WHERE sc.modul_id = :mid AND sc.is_active = 1
             ORDER BY sc.created_at ASC'
        );
        $stmt->execute([':mid' => $modul_id]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu challenge berdasarkan ID (termasuk target_config JSON).
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT sc.*, m.judul AS judul_modul, u.nama AS nama_guru
             FROM simulasi_challenge sc
             JOIN modul m ON m.id = sc.modul_id
             JOIN users u ON u.id = sc.guru_id
             WHERE sc.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row && is_string($row['target_config'])) {
            $row['target_config'] = json_decode($row['target_config'], true);
        }

        return $row;
    }

    /**
     * Buat challenge simulasi baru.
     * $data: modul_id, guru_id, judul, deskripsi, tipe_topologi,
     *        instruksi, target_config (array/object), xp_reward, is_active
     */
    public function create(array $data): int|false
    {
        $sql  = 'INSERT INTO simulasi_challenge
                   (modul_id, guru_id, judul, deskripsi, tipe_topologi,
                    instruksi, target_config, xp_reward, is_active)
                 VALUES
                   (:modul_id, :guru_id, :judul, :deskripsi, :tipe,
                    :instruksi, :target, :xp, :active)';

        $stmt = $this->db->prepare($sql);
        $ok   = $stmt->execute([
            ':modul_id'  => $data['modul_id'],
            ':guru_id'   => $data['guru_id'],
            ':judul'     => $data['judul'],
            ':deskripsi' => $data['deskripsi']     ?? null,
            ':tipe'      => $data['tipe_topologi'],
            ':instruksi' => $data['instruksi'],
            ':target'    => isset($data['target_config'])
                              ? json_encode($data['target_config'])
                              : null,
            ':xp'        => $data['xp_reward']     ?? 30,
            ':active'    => $data['is_active']      ?? 1,
        ]);

        return $ok ? (int) $this->db->lastInsertId() : false;
    }

    // ================================================================
    // VALIDASI TOPOLOGI
    // ================================================================

    /**
     * Validasi topologi yang dibuat siswa terhadap target_config challenge.
     *
     * Aturan validasi:
     * 1. Jumlah node harus sama.
     * 2. Tipe setiap node harus cocok.
     * 3. Jumlah koneksi (edge) harus sama.
     * 4. Koneksi antara node harus sesuai pola yang diizinkan per tipe topologi.
     *
     * @param int   $challenge_id
     * @param array $canvas_data  ['nodes' => [...], 'edges' => [...]]
     * @return array ['is_correct' => bool, 'skor' => float, 'pesan' => string, 'detail' => array]
     */
    public function validateTopologi(int $challenge_id, array $canvas_data): array
    {
        $challenge = $this->getById($challenge_id);
        if (!$challenge) {
            return ['is_correct' => false, 'skor' => 0, 'pesan' => 'Challenge tidak ditemukan', 'detail' => []];
        }

        $target = $challenge['target_config'] ?? null;
        if (!$target) {
            // Jika tidak ada target_config, validasi otomatis sukses (free-form)
            return ['is_correct' => true, 'skor' => 100, 'pesan' => 'Topologi diterima!', 'detail' => []];
        }

        $nodesUser   = $canvas_data['nodes'] ?? [];
        $edgesUser   = $canvas_data['edges'] ?? [];
        $nodesTarget = $target['nodes']      ?? [];
        $edgesTarget = $target['edges']      ?? [];
        $tipe        = $challenge['tipe_topologi'];

        $detail  = [];
        $poin    = 0;
        $maxPoin = 100;

        // ---- Cek 1: Jumlah node ----
        $nodeUserCount   = count($nodesUser);
        $nodeTargetCount = count($nodesTarget);
        if ($nodeUserCount === $nodeTargetCount) {
            $poin += 25;
            $detail[] = "✅ Jumlah perangkat benar ({$nodeTargetCount} perangkat).";
        } else {
            $detail[] = "❌ Jumlah perangkat salah. Diharapkan: {$nodeTargetCount}, ditemukan: {$nodeUserCount}.";
        }

        // ---- Cek 2: Tipe node ----
        $typesUser   = $this->countNodeTypes($nodesUser);
        $typesTarget = $this->countNodeTypes($nodesTarget);
        $typeMatch   = true;
        foreach ($typesTarget as $type => $count) {
            if (($typesUser[$type] ?? 0) !== $count) {
                $typeMatch = false;
                break;
            }
        }
        if ($typeMatch) {
            $poin += 25;
            $detail[] = '✅ Jenis perangkat benar.';
        } else {
            $detail[] = '❌ Jenis perangkat tidak sesuai.';
        }

        // ---- Cek 3: Jumlah koneksi ----
        $edgeUserCount   = count($edgesUser);
        $edgeTargetCount = count($edgesTarget);
        if ($edgeUserCount === $edgeTargetCount) {
            $poin += 25;
            $detail[] = "✅ Jumlah koneksi benar ({$edgeTargetCount} koneksi).";
        } else {
            $detail[] = "❌ Jumlah koneksi salah. Diharapkan: {$edgeTargetCount}, ditemukan: {$edgeUserCount}.";
        }

        // ---- Cek 4: Pola topologi ----
        $polaCocok = $this->cekPolaTopologi($tipe, $nodesUser, $edgesUser);
        if ($polaCocok) {
            $poin += 25;
            $detail[] = "✅ Pola topologi {$tipe} sesuai.";
        } else {
            $detail[] = "❌ Pola topologi {$tipe} tidak sesuai.";
        }

        $isCorrect = $poin >= 75; // Minimal 75% benar

        return [
            'is_correct' => $isCorrect,
            'skor'       => (float) $poin,
            'pesan'      => $isCorrect
                ? '🎉 Selamat! Topologi kamu sudah benar!'
                : '😕 Topologi belum sesuai. Periksa kembali!',
            'detail'     => $detail,
        ];
    }

    // ================================================================
    // HASIL SIMULASI
    // ================================================================

    /**
     * Simpan hasil simulasi siswa.
     */
    public function saveHasil(
        int   $user_id,
        int   $challenge_id,
        array $canvas_data,
        bool  $is_correct,
        float $skor
    ): array {
        $challenge = $this->getById($challenge_id);
        $xpEarned  = 0;

        // Hitung attempt ke berapa
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS cnt FROM hasil_simulasi WHERE user_id = :uid AND challenge_id = :cid'
        );
        $stmt->execute([':uid' => $user_id, ':cid' => $challenge_id]);
        $attemptKe = (int) $stmt->fetch()['cnt'] + 1;

        // Berikan XP hanya jika benar (dan attempt pertama yang benar)
        if ($is_correct && $challenge) {
            // Cek apakah sebelumnya sudah pernah benar
            $stmtPrev = $this->db->prepare(
                'SELECT COUNT(*) AS cnt FROM hasil_simulasi
                 WHERE user_id = :uid AND challenge_id = :cid AND is_correct = 1'
            );
            $stmtPrev->execute([':uid' => $user_id, ':cid' => $challenge_id]);
            $sudahBenar = (int) $stmtPrev->fetch()['cnt'];

            if ($sudahBenar === 0) {
                $xpEarned = (int) $challenge['xp_reward'];
            }
        }

        // Insert hasil
        $this->db->prepare(
            'INSERT INTO hasil_simulasi
               (user_id, challenge_id, canvas_data, is_correct, skor, xp_earned, attempt_ke, completed_at)
             VALUES (:uid, :cid, :canvas, :correct, :skor, :xp, :att, NOW())'
        )->execute([
            ':uid'    => $user_id,
            ':cid'    => $challenge_id,
            ':canvas' => json_encode($canvas_data),
            ':correct'=> $is_correct ? 1 : 0,
            ':skor'   => $skor,
            ':xp'     => $xpEarned,
            ':att'    => $attemptKe,
        ]);

        $hasilId = (int) $this->db->lastInsertId();

        // Tambahkan XP & badge
        $gamifikasi = new GamifikasiModel();
        $xpResult   = [];
        if ($xpEarned > 0) {
            $xpResult = $gamifikasi->addXP(
                $user_id, $xpEarned, 'simulasi', $hasilId,
                'Simulasi: ' . ($challenge['judul'] ?? '')
            );
        } else {
            $gamifikasi->checkAndAwardBadges($user_id);
        }

        return [
            'hasil_id'   => $hasilId,
            'is_correct' => $is_correct,
            'skor'       => $skor,
            'xp_earned'  => $xpEarned,
            'attempt_ke' => $attemptKe,
            'level_up'   => $xpResult['level_up'] ?? false,
            'new_level'  => $xpResult['new_level'] ?? null,
            'new_badges' => $xpResult['badges']    ?? [],
        ];
    }

    /**
     * Ambil semua hasil simulasi seorang siswa untuk challenge tertentu.
     */
    public function getHasilByUser(int $user_id, int $challenge_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM hasil_simulasi
             WHERE user_id = :uid AND challenge_id = :cid
             ORDER BY attempt_ke DESC'
        );
        $stmt->execute([':uid' => $user_id, ':cid' => $challenge_id]);
        $rows = $stmt->fetchAll();

        // Decode canvas_data JSON
        foreach ($rows as &$row) {
            if (is_string($row['canvas_data'])) {
                $row['canvas_data'] = json_decode($row['canvas_data'], true);
            }
        }
        unset($row);

        return $rows;
    }

    // ================================================================
    // HELPER PRIVATE
    // ================================================================

    /**
     * Hitung jumlah node per tipe dari array nodes.
     * Node diharapkan punya key 'type' (mis. 'router', 'switch', 'pc', 'hub').
     */
    private function countNodeTypes(array $nodes): array
    {
        $counts = [];
        foreach ($nodes as $node) {
            $type = strtolower($node['type'] ?? 'unknown');
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        return $counts;
    }

    /**
     * Cek apakah koneksi antar node membentuk pola yang sesuai dengan tipe topologi.
     *
     * Untuk setiap tipe:
     * - star  : semua end-device terhubung ke tepat satu hub/switch (central node).
     * - bus   : semua node terhubung dalam satu rantai linier atau ke kabel backbone.
     * - ring  : setiap node memiliki tepat 2 koneksi dan membentuk siklus.
     * - mesh  : setiap node terhubung ke semua node lainnya.
     * - hybrid: setidaknya ada dua pola berbeda yang dapat dikenali.
     */
    private function cekPolaTopologi(string $tipe, array $nodes, array $edges): bool
    {
        if (empty($nodes) || empty($edges)) {
            return false;
        }

        $nodeIds  = array_column($nodes, 'id');
        $nodeSet  = array_flip($nodeIds);

        // Bangun adjacency list
        $adj = [];
        foreach ($nodeIds as $nid) {
            $adj[$nid] = [];
        }
        foreach ($edges as $edge) {
            $src = $edge['source'] ?? $edge['from'] ?? null;
            $tgt = $edge['target'] ?? $edge['to']   ?? null;
            if ($src !== null && $tgt !== null && isset($nodeSet[$src], $nodeSet[$tgt])) {
                $adj[$src][] = $tgt;
                $adj[$tgt][] = $src;
            }
        }

        return match ($tipe) {
            'star'   => $this->isStar($nodes, $adj),
            'bus'    => $this->isBus($adj),
            'ring'   => $this->isRing($adj, $nodeIds),
            'mesh'   => $this->isMesh($adj, $nodeIds),
            'hybrid' => true, // Validasi hybrid cukup longgar
            default  => false,
        };
    }

    /** Star: harus ada tepat satu central node yang terhubung ke semua node lain. */
    private function isStar(array $nodes, array $adj): bool
    {
        $n = count($adj);
        if ($n < 2) return false;

        foreach ($adj as $nodeId => $neighbors) {
            if (count($neighbors) === $n - 1) {
                // Semua node lain terhubung hanya ke node ini
                $allLeaf = true;
                foreach ($adj as $otherId => $otherNeighbors) {
                    if ($otherId === $nodeId) continue;
                    if (count($otherNeighbors) !== 1) {
                        $allLeaf = false;
                        break;
                    }
                }
                if ($allLeaf) return true;
            }
        }
        return false;
    }

    /** Bus: setidaknya ada node dengan degree 1 (ujung) dan tidak ada siklus. */
    private function isBus(array $adj): bool
    {
        $n = count($adj);
        if ($n < 2) return false;

        $endNodes = 0;
        $totalEdges = 0;
        foreach ($adj as $neighbors) {
            $deg = count($neighbors);
            if ($deg === 1) $endNodes++;
            $totalEdges += $deg;
        }
        $totalEdges /= 2;

        // Bus: tree linier = n-1 edge, 2 ujung
        return $endNodes === 2 && $totalEdges === $n - 1;
    }

    /** Ring: setiap node memiliki tepat 2 tetangga, dan graph terhubung. */
    private function isRing(array $adj, array $nodeIds): bool
    {
        $n = count($adj);
        if ($n < 3) return false;

        foreach ($adj as $neighbors) {
            if (count($neighbors) !== 2) return false;
        }

        // Cek keterhubungan (BFS/DFS)
        return $this->isConnected($adj, $nodeIds);
    }

    /** Mesh: setiap node terhubung ke semua node lainnya. */
    private function isMesh(array $adj, array $nodeIds): bool
    {
        $n = count($adj);
        if ($n < 2) return false;

        foreach ($adj as $neighbors) {
            if (count($neighbors) !== $n - 1) return false;
        }
        return true;
    }

    /** Cek apakah graph terhubung (BFS). */
    private function isConnected(array $adj, array $nodeIds): bool
    {
        if (empty($nodeIds)) return true;

        $visited = [];
        $queue   = [$nodeIds[0]];
        $visited[$nodeIds[0]] = true;

        while (!empty($queue)) {
            $cur = array_shift($queue);
            foreach ($adj[$cur] as $nb) {
                if (!isset($visited[$nb])) {
                    $visited[$nb] = true;
                    $queue[]      = $nb;
                }
            }
        }

        return count($visited) === count($nodeIds);
    }
}
