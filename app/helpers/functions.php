<?php
/**
 * Functions Helper
 * Fungsi utility aplikasi Simulasi Topologi Jaringan.
 */

// ─── Input & Keamanan ─────────────────────────────────────────────────────────

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function generateToken(): string {
    startSession();
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verifyToken(string $token): bool {
    startSession();
    return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
}

// ─── Template Rendering ───────────────────────────────────────────────────────

/**
 * Render template view.
 * Jika view adalah 'auth/login' atau ada flag $noLayout = true, render standalone.
 * Jika tidak, wrap ke dalam views/layouts/app.php.
 */
function render(string $view, array $data = []): void {
    extract($data);

    if (!isset($pageTitle) && isset($title)) {
        $pageTitle = $title;
    }

    $viewFile = BASE_PATH . '/views/' . $view . '.php';
    if (!file_exists($viewFile)) {
        http_response_code(404);
        echo "<h2 style='font-family:sans-serif;color:#ef4444;padding:30px;'>View tidak ditemukan: " . htmlspecialchars($view) . "</h2>";
        exit;
    }

    // Standalone views (auth, error pages, atau yang memuat noLayout)
    if (!empty($noLayout) || str_starts_with($view, 'auth/') || $view === '404' || $view === '500') {
        include $viewFile;
        return;
    }

    // Render inner content
    ob_start();
    include $viewFile;
    $content = ob_get_clean();

    // Render inside layout
    $layoutFile = BASE_PATH . '/views/layouts/app.php';
    if (file_exists($layoutFile)) {
        include $layoutFile;
    } else {
        echo $content;
    }
}

// ─── Tanggal & Waktu ──────────────────────────────────────────────────────────

function formatDate(string $date, string $format = 'd F Y'): string {
    if (empty($date)) return '-';
    $bulan = [
        1  => 'Januari', 2  => 'Februari', 3  => 'Maret',
        4  => 'April',   5  => 'Mei',       6  => 'Juni',
        7  => 'Juli',    8  => 'Agustus',   9  => 'September',
        10 => 'Oktober', 11 => 'November',  12 => 'Desember',
    ];
    $hari = [
        'Sunday'    => 'Minggu',   'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',   'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',    'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
    ];

    $ts     = strtotime($date);
    $result = date($format, $ts);

    foreach ($bulan as $no => $nama) {
        $result = str_replace(date('F', mktime(0, 0, 0, $no, 1)), $nama, $result);
    }
    foreach ($hari as $en => $id) {
        $result = str_replace($en, $id, $result);
    }

    return $result;
}

function timeAgo(string $datetime): string {
    if (empty($datetime)) return '-';
    $diff = time() - strtotime($datetime);

    if ($diff < 60)         return 'Baru saja';
    if ($diff < 3600)       return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400)      return floor($diff / 3600) . ' jam lalu';
    if ($diff < 2592000)    return floor($diff / 86400) . ' hari lalu';
    if ($diff < 31536000)   return floor($diff / 2592000) . ' bulan lalu';
    return floor($diff / 31536000) . ' tahun lalu';
}

// ─── Gamifikasi ───────────────────────────────────────────────────────────────

function calculateLevel(int $xp): int {
    return max(1, (int) floor($xp / 100) + 1);
}

/**
 * Update dan pertahankan streak login harian siswa secara real-time.
 * Menghitung streak berturut-turut berdasarkan last_activity dan tanggal hari ini.
 */
function updateUserStreak(PDO $db, int $userId): int {
    if ($userId <= 0) return 1;

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $stmt = $db->prepare("SELECT id, streak_days, last_activity FROM gamifikasi WHERE user_id = ?");
    $stmt->execute([$userId]);
    $gamif = $stmt->fetch();

    if (!$gamif) {
        $db->prepare("INSERT INTO gamifikasi (user_id, total_xp, level, streak_days, last_activity, created_at, updated_at) 
                      VALUES (?, 0, 1, 1, ?, NOW(), NOW())")
           ->execute([$userId, $today]);
        return 1;
    }

    $currentStreak = (int)$gamif['streak_days'];
    $lastActivity = !empty($gamif['last_activity']) ? date('Y-m-d', strtotime($gamif['last_activity'])) : null;

    if ($lastActivity === $today) {
        // Sudah aktif hari ini: pertahankan streak saat ini (minimal 1)
        $newStreak = max(1, $currentStreak);
        if ($newStreak !== $currentStreak) {
            $db->prepare("UPDATE gamifikasi SET streak_days = ? WHERE id = ?")->execute([$newStreak, $gamif['id']]);
        }
        return $newStreak;
    } elseif ($lastActivity === $yesterday) {
        // Aktif berturut-turut hari kemarin dan hari ini: tambah streak
        $newStreak = max(1, $currentStreak) + 1;
    } else {
        // Terputus / baru login kembali setelah beberapa hari: mulai dari 1
        $newStreak = 1;
    }

    $db->prepare("UPDATE gamifikasi SET streak_days = ?, last_activity = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$newStreak, $today, $gamif['id']]);

    return $newStreak;
}

function calculateXPForNextLevel(int $level): int {
    return $level * 100;
}

function getLevelTitle(int $level): string {
    $titles = [
        1  => 'Pemula Jaringan',
        2  => 'Penjelajah LAN',
        3  => 'Teknisi Jaringan',
        4  => 'Spesialis Topologi',
        5  => 'Arsitek Jaringan',
        6  => 'Network Engineer',
        7  => 'Network Senior',
        8  => 'Network Master',
        9  => 'Grandmaster Jaringan',
        10 => 'Network Guru Legend',
    ];
    return $titles[min($level, 10)] ?? 'Network Legend';
}

function getBadgeIcon(string $badge_code): string {
    $badges = [
        'pemula'            => '🌱',
        'xp_100'            => '⭐',
        'xp_500'            => '🌟',
        'level_5'           => '🥉',
        'level_10'          => '🥈',
        'level_20'          => '🏆',
        'kuis_1'            => '📝',
        'kuis_10'           => '📚',
        'simulasi_1'        => '🔌',
        'simulasi_5'        => '🖧',
        'streak_7'          => '🔥',
        'first_login'       => '🎉',
        'first_simulasi'    => '🖥️',
        'perfect_score'     => '💯',
        'speed_learner'     => '⚡',
        'topology_master'   => '🗺️',
    ];
    return $badges[$badge_code] ?? '🎖️';
}

// ─── String & Format ──────────────────────────────────────────────────────────

function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
}

function formatNumber(int|float $number, int $decimals = 0): string {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Resolusi URL Avatar Pengguna (Siswa/Guru) yang Tangguh & Bebas 404
 */
function getAvatarUrl(?string $avatar, string $fallbackName = ''): string {
    if (!empty($avatar)) {
        $avatar = trim($avatar);
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }
        $cleanPath = ltrim($avatar, '/');
        // Periksa keberadaan file fisik di disk
        $root = dirname(__DIR__, 2);
        if (file_exists($root . '/' . $cleanPath)) {
            return base_url($cleanPath);
        }
    }

    // Jika berupa nama pengguna, gunakan UI Avatars sebagai fallback visual bersih
    if (!empty($fallbackName)) {
        return 'https://ui-avatars.com/api/?name=' . urlencode($fallbackName) . '&background=4f46e5&color=fff&bold=true';
    }

    return base_url('public/img/avatars/avatar_1.png');
}

/**
 * Mengembalikan data URI base64 logo SMP Negeri 1 Karangmoncol agar selalu muncul 100% tanpa risiko 404 / path issue.
 */
function getSchoolLogoSrc(): string {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $logoPath = dirname(__DIR__, 2) . '/public/img/logo.jpg';
    if (file_exists($logoPath)) {
        $content = file_get_contents($logoPath);
        $cached = 'data:image/jpeg;base64,' . base64_encode($content);
        return $cached;
    }

    return asset('img/logo.jpg');
}

/**
 * Buat notifikasi baru untuk sistem portal guru / pengguna.
 */
function createNotification(PDO $db, array $data): int {
    try {
        $stmt = $db->prepare(
            "INSERT INTO notifikasi (user_id, role_target, tipe, judul, pesan, icon, color, link, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())"
        );
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['role_target'] ?? 'guru',
            $data['tipe'] ?? 'info',
            $data['judul'] ?? 'Pemberitahuan Sistem',
            $data['pesan'] ?? '',
            $data['icon'] ?? 'fa-bell',
            $data['color'] ?? 'blue',
            $data['link'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    } catch (Throwable $e) {
        return 0;
    }
}
