<?php
/**
 * Alat Pembersih & Sinkronisasi EXP Tantangan Cyber Siswa
 * SMP Negeri 1 Karangmoncol
 * 
 * Script ini aman dijalankan berulang kali (Idempotent).
 * Berfungsi untuk membersihkan poin ganda yang didapat dari pengulangan kasus keamanan,
 * serta merekap total XP dan level siswa secara akurat.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/functions.php';

$pdo = getDB();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

$success = false;
$message = '';
$results = [];
$totalDuplicatesDeleted = 0;
$totalProgressAdjusted = 0;

try {
    $pdo->beginTransaction();

    // 1. Deteksi dan hapus duplikasi di xp_log untuk sumber 'kasus_keamanan'
    $stmt = $pdo->query("
        SELECT user_id, referensi_id, COUNT(*) as cnt, MIN(id) as keep_id
        FROM xp_log
        WHERE sumber = 'kasus_keamanan'
        GROUP BY user_id, referensi_id
        HAVING cnt > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($duplicates as $dup) {
        $userId = (int)$dup['user_id'];
        $refId = (int)$dup['referensi_id'];
        $keepId = (int)$dup['keep_id'];

        $delStmt = $pdo->prepare("
            DELETE FROM xp_log 
            WHERE sumber = 'kasus_keamanan' 
              AND user_id = ? 
              AND referensi_id = ? 
              AND id != ?
        ");
        $delStmt->execute([$userId, $refId, $keepId]);
        $totalDuplicatesDeleted += $delStmt->rowCount();
    }

    // 2. Perbaiki xp_earned di tabel siswa_kasus_progress agar tidak melebihi reward asli kasus
    $stmtFixProg = $pdo->query("
        SELECT p.id, p.user_id, p.kasus_id, p.xp_earned, k.xp_reward
        FROM siswa_kasus_progress p
        JOIN kasus_keamanan k ON p.kasus_id = k.id
        WHERE p.xp_earned > k.xp_reward
    ");
    $inflatedProg = $stmtFixProg->fetchAll(PDO::FETCH_ASSOC);

    $updProgStmt = $pdo->prepare("UPDATE siswa_kasus_progress SET xp_earned = ? WHERE id = ?");
    foreach ($inflatedProg as $prog) {
        $updProgStmt->execute([$prog['xp_reward'], $prog['id']]);
        $totalProgressAdjusted++;
    }

    // 3. Rekalkulasi total_xp dan level untuk semua pengguna di gamifikasi berdasarkan riwayat log
    $stmtUsers = $pdo->query("SELECT id, user_id, total_xp, level FROM gamifikasi");
    $gamifList = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    $nowExpr = ($driver === 'sqlite') ? "datetime('now')" : "NOW()";
    $updGamifStmt = $pdo->prepare("UPDATE gamifikasi SET total_xp = ?, level = ?, updated_at = $nowExpr WHERE user_id = ?");
    $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(xp_amount), 0) FROM xp_log WHERE user_id = ?");
    $userStmt = $pdo->prepare("SELECT id, nama, username, kelas FROM users WHERE id = ?");

    foreach ($gamifList as $gamif) {
        $uId = (int)$gamif['user_id'];
        $oldXp = (int)$gamif['total_xp'];
        $oldLevel = (int)$gamif['level'];

        $sumStmt->execute([$uId]);
        $realXp = (int)$sumStmt->fetchColumn();
        $realLevel = calculateLevel($realXp);

        if ($oldXp !== $realXp || $oldLevel !== $realLevel) {
            $updGamifStmt->execute([$realXp, $realLevel, $uId]);

            $userStmt->execute([$uId]);
            $uInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

            $results[] = [
                'user_id' => $uId,
                'nama' => $uInfo['nama'] ?? "User #$uId",
                'username' => $uInfo['username'] ?? "-",
                'kelas' => $uInfo['kelas'] ?? "-",
                'old_xp' => $oldXp,
                'new_xp' => $realXp,
                'diff_xp' => $oldXp - $realXp,
                'old_level' => $oldLevel,
                'new_level' => $realLevel,
            ];
        }
    }

    $pdo->commit();
    $success = true;
    $message = "Proses perbaikan berhasil dijalankan!";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $success = false;
    $message = "Terjadi kesalahan: " . $e->getMessage();
}

// Jika request meminta JSON (misal dipanggil via API atau cURL)
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $success ? 'success' : 'error',
        'message' => $message,
        'duplicates_deleted' => $totalDuplicatesDeleted,
        'progress_adjusted' => $totalProgressAdjusted,
        'users_recalculated' => count($results),
        'details' => $results,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaikan EXP Detektif Siber - SMP Negeri 1 Karangmoncol</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            padding: 30px 15px;
            display: flex;
            justify-content: center;
        }
        .container {
            max-width: 850px;
            width: 100%;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            padding: 32px;
            border: 1px solid #e2e8f0;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 24px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 24px;
        }
        .badge-icon {
            width: 52px;
            height: 52px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        h1 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        p.subtitle { font-size: 13px; color: #64748b; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        .stat-val { font-size: 26px; font-weight: 800; color: #2563eb; }
        .stat-label { font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 600; }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 600;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 12px;
        }
        th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            text-align: left;
            padding: 10px 14px;
            border-radius: 6px;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .xp-chip-old {
            background: #fee2e2;
            color: #dc2626;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            text-decoration: line-through;
        }
        .xp-chip-new {
            background: #dcfce7;
            color: #15803d;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
        }
        .btn-action {
            display: inline-block;
            margin-top: 24px;
            padding: 10px 20px;
            background: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-action:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="badge-icon">🛡️</div>
            <div>
                <h1>Pembersihan & Rekalkulasi EXP Detektif Siber</h1>
                <p class="subtitle">SMP Negeri 1 Karangmoncol • Sistem Sinkronisasi Database Otomatis</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert-success">
                ✅ <?= htmlspecialchars($message) ?> Database telah disinkronkan dan poin ganda berhasil dinormalkan.
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-val"><?= $totalDuplicatesDeleted ?></div>
                    <div class="stat-label">Log Duplikat Dihapus</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val"><?= $totalProgressAdjusted ?></div>
                    <div class="stat-label">Progress Dinormalkan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val"><?= count($results) ?></div>
                    <div class="stat-label">Akun Siswa Disesuaikan</div>
                </div>
            </div>

            <?php if (!empty($results)): ?>
                <h3 style="font-size: 15px; margin-bottom: 8px;">Daftar Akun yang Disesuaikan:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Siswa / Username</th>
                            <th>Kelas</th>
                            <th>Total EXP Sebelum</th>
                            <th>Total EXP Sesudah</th>
                            <th>Level Sekarang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $res): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($res['nama']) ?></strong><br>
                                    <span style="color:#64748b; font-size:11px;">@<?= htmlspecialchars($res['username']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($res['kelas']) ?></td>
                                <td><span class="xp-chip-old"><?= $res['old_xp'] ?> XP</span></td>
                                <td><span class="xp-chip-new"><?= $res['new_xp'] ?> XP</span> (-<?= $res['diff_xp'] ?> XP)</td>
                                <td>Level <?= $res['new_level'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="font-size: 13px; color: #64748b; text-align: center; padding: 20px 0;">
                    Semua poin pengguna sudah bersih dan akurat. Tidak ada data EXP yang menggembung.
                </p>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert-error">
                ❌ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div style="text-align: right;">
            <a href="index.php" class="btn-action">Kembali ke Beranda &rarr;</a>
        </div>
    </div>
</body>
</html>
