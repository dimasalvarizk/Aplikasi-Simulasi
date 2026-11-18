<?php
/**
 * Session Helper
 * Manajemen session dan autentikasi aplikasi.
 */

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 7200;

        // Pastikan direktori session writable
        $savePath = session_save_path();
        if (empty($savePath) || !is_writable($savePath)) {
            $localSessionDir = dirname(__DIR__, 2) . '/storage/sessions';
            if (!is_dir($localSessionDir)) {
                @mkdir($localSessionDir, 0777, true);
            }
            if (is_writable($localSessionDir)) {
                @session_save_path($localSessionDir);
            } else {
                $sysTmp = sys_get_temp_dir();
                if (is_writable($sysTmp)) {
                    @session_save_path($sysTmp);
                }
            }
        }

        if (!headers_sent()) {
            @session_set_cookie_params([
                'lifetime' => $timeout,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            @ini_set('session.use_strict_mode', 1);
        }

        @session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user']) && (!empty($_SESSION['user']['id']) || !empty($_SESSION['user']['user_id']));
}

function getUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function setUser(array $userData): void {
    startSession();
    $id = $userData['id'] ?? $userData['user_id'] ?? 0;
    $userData['id'] = $id;
    $userData['user_id'] = $id;
    $_SESSION['user'] = $userData;
}

function logout(): void {
    startSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies") && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    @session_destroy();
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Silakan masuk terlebih dahulu.');
        redirect(base_url('login'));
    }
}

function requireRole(string $role): void {
    requireLogin();
    $user = getUser();
    if (($user['role'] ?? '') !== $role) {
        setFlash('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
        redirect(base_url($user['role'] === 'guru' ? 'guru/dashboard' : 'siswa/dashboard'));
    }
}

function setFlash(string $key, string $message): void {
    startSession();
    $_SESSION['_flash'][$key] = $message;
}

function getFlash(string $key): ?string {
    startSession();
    if (isset($_SESSION['_flash'][$key])) {
        $msg = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }
    return null;
}
