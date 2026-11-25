-- ============================================================================
-- SQL PERBAIKAN DAN SINKRONISASI EXP DETEKTIF SIBER
-- SMP NEGERI 1 KARANGMONCOL
-- Jalankan query ini di Tab SQL phpMyAdmin (Hosting InfinityFree / cPanel / XAMPP)
-- ============================================================================

-- Langkah 1: Hapus entri duplikat pada xp_log untuk sumber 'kasus_keamanan'
-- Query ini mempertahankan 1 entri pertama (ID terkecil) dan menghapus duplikatnya
DELETE x1 FROM xp_log x1
INNER JOIN xp_log x2 
WHERE x1.sumber = 'kasus_keamanan' 
  AND x2.sumber = 'kasus_keamanan'
  AND x1.user_id = x2.user_id 
  AND x1.referensi_id = x2.referensi_id 
  AND x1.id > x2.id;

-- Langkah 2: Pastikan xp_earned pada tabel siswa_kasus_progress tidak melebihi reward asli kasus
UPDATE siswa_kasus_progress p
JOIN kasus_keamanan k ON p.kasus_id = k.id
SET p.xp_earned = k.xp_reward
WHERE p.xp_earned > k.xp_reward;

-- Langkah 3: Rekalkulasi total_xp dan level pada tabel gamifikasi berdasarkan riwayat xp_log riil
UPDATE gamifikasi g
JOIN (
    SELECT user_id, COALESCE(SUM(xp_amount), 0) AS real_xp
    FROM xp_log
    GROUP BY user_id
) r ON g.user_id = r.user_id
SET g.total_xp = r.real_xp,
    g.level = GREATEST(1, FLOOR(r.real_xp / 100) + 1);

-- Selesai! Cek hasil leaderboard terbaru:
SELECT u.id, u.nama, u.username, u.kelas, g.total_xp, g.level
FROM gamifikasi g
JOIN users u ON g.user_id = u.id
ORDER BY g.total_xp DESC, g.level DESC;
