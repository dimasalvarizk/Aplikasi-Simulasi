<?php
// ─── Konstanta Aplikasi ───────────────────────────────────────────────────────
define('APP_NAME',       'SMP Negeri 1 Karangmoncol — Simulasi Topologi');
define('SCHOOL_NAME',    'SMP NEGERI 1 KARANGMONCOL');
define('APP_VERSION',    '1.0.0');

// Deteksi dinamis port & folder agar kompatibel dengan XAMPP maupun php artisan serve
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$subDir   = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$subDir   = ($subDir === '/' || $subDir === '\\' || $subDir === '.') ? '' : $subDir;

define('APP_URL',        $protocol . $host . $subDir);
define('BASE_PATH',      dirname(__DIR__));
define('UPLOAD_PATH',    BASE_PATH . '/public/uploads/');
define('MAX_FILE_SIZE',  5 * 1024 * 1024); // 5 MB
define('SESSION_TIMEOUT', 7200);            // 2 jam (detik)
define('MASTER_RECOVERY_KEY', 'SMPN1-GURU-2026'); // Kunci Keamanan Master Pemulihan Guru

// ─── Helper Fungsi Global ─────────────────────────────────────────────────────

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function base_url(string $path = ''): string {
    $path = ltrim($path, '/');
    return rtrim(APP_URL, '/') . ($path !== '' ? '/' . $path : '');
}

function asset(string $path = ''): string {
    $path = ltrim($path, '/');
    return rtrim(APP_URL, '/') . '/public/' . $path;
}
