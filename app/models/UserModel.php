<?php
/**
 * UserModel.php
 * Model untuk tabel users (guru & siswa)
 */

require_once __DIR__ . '/../../config/database.php';

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // ----------------------------------------------------------------
    // Pencarian
    // ----------------------------------------------------------------

    /**
     * Cari user berdasarkan username.
     */
    public function findByUsername(string $username): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Cari user berdasarkan email.
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Cari user berdasarkan ID.
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ----------------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------------

    /**
     * Buat user baru.
     * $data: nama, username, email, password (plain), role, kelas?, nisn?, nip?
     * Password akan di-hash di sini.
     */
    public function create(array $data): int|false
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $sql = 'INSERT INTO users (nama, username, email, password, role, kelas, nisn, nip, avatar)
                VALUES (:nama, :username, :email, :password, :role, :kelas, :nisn, :nip, :avatar)';

        $stmt = $this->db->prepare($sql);
        $ok   = $stmt->execute([
            ':nama'     => $data['nama'],
            ':username' => $data['username'],
            ':email'    => $data['email'],
            ':password' => $data['password'],
            ':role'     => $data['role'] ?? 'siswa',
            ':kelas'    => $data['kelas']  ?? null,
            ':nisn'     => $data['nisn']   ?? null,
            ':nip'      => $data['nip']    ?? null,
            ':avatar'   => $data['avatar'] ?? null,
        ]);

        if (!$ok) {
            return false;
        }

        $newId = (int) $this->db->lastInsertId();

        // Buat baris siswa_progress untuk siswa baru
        if (($data['role'] ?? 'siswa') === 'siswa') {
            $this->db->prepare(
                'INSERT INTO siswa_progress (user_id) VALUES (:uid)'
            )->execute([':uid' => $newId]);
        }

        return $newId;
    }

    /**
     * Update data user.
     * $data boleh berisi subset kolom yang ingin diubah.
     * Jika terdapat key 'password', akan di-hash ulang.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['nama', 'username', 'email', 'password', 'kelas', 'nisn', 'nip', 'avatar', 'is_active'];
        $sets    = [];
        $params  = [':id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $val = $data[$col];
                if ($col === 'password') {
                    $val = password_hash($val, PASSWORD_BCRYPT, ['cost' => 12]);
                }
                $sets[]           = "$col = :$col";
                $params[":$col"]  = $val;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql  = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ----------------------------------------------------------------
    // Daftar siswa
    // ----------------------------------------------------------------

    /**
     * Ambil semua siswa dengan filter opsional (search + kelas).
     */
    public function getAllSiswa(string $search = '', string $kelas = ''): array
    {
        $sql    = 'SELECT u.*, sp.total_xp, sp.level, sp.streak_days
                   FROM users u
                   LEFT JOIN siswa_progress sp ON sp.user_id = u.id
                   WHERE u.role = \'siswa\'';
        $params = [];

        if ($search !== '') {
            $sql            .= ' AND (u.nama LIKE :search1 OR u.username LIKE :search2 OR u.nisn LIKE :search3)';
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        if ($kelas !== '') {
            $sql             .= ' AND u.kelas = :kelas';
            $params[':kelas'] = $kelas;
        }

        $sql .= ' ORDER BY u.nama ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ----------------------------------------------------------------
    // Login
    // ----------------------------------------------------------------

    /**
     * Update kolom last_login ke waktu sekarang.
     */
    public function updateLastLogin(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id'
        );
        return $stmt->execute([':id' => $id]);
    }

    // ----------------------------------------------------------------
    // Statistik dashboard guru
    // ----------------------------------------------------------------

    /**
     * Statistik umum untuk dashboard guru:
     * - total_siswa
     * - siswa_aktif   (login dalam 7 hari)
     * - rata_xp
     * - total_kuis_dikerjakan
     */
    public function getStats(): array
    {
        $stats = [];

        // Total siswa
        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM users WHERE role = \'siswa\' AND is_active = 1'
        )->fetch();
        $stats['total_siswa'] = (int) ($row['total'] ?? 0);

        // Siswa aktif (last_login 7 hari terakhir)
        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM users
             WHERE role = \'siswa\' AND is_active = 1
               AND last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
        )->fetch();
        $stats['siswa_aktif'] = (int) ($row['total'] ?? 0);

        // Rata-rata XP siswa
        $row = $this->db->query(
            'SELECT COALESCE(AVG(sp.total_xp), 0) AS rata
             FROM siswa_progress sp
             JOIN users u ON u.id = sp.user_id
             WHERE u.role = \'siswa\''
        )->fetch();
        $stats['rata_xp'] = round((float) ($row['rata'] ?? 0), 1);

        // Total kuis yang sudah dikerjakan
        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM hasil_kuis'
        )->fetch();
        $stats['total_kuis_dikerjakan'] = (int) ($row['total'] ?? 0);

        // Total simulasi yang sudah dikerjakan
        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM hasil_simulasi'
        )->fetch();
        $stats['total_simulasi_dikerjakan'] = (int) ($row['total'] ?? 0);

        // Rata-rata skor kuis
        $row = $this->db->query(
            'SELECT COALESCE(AVG(skor), 0) AS rata FROM hasil_kuis'
        )->fetch();
        $stats['rata_skor_kuis'] = round((float) ($row['rata'] ?? 0), 1);

        return $stats;
    }
}
