<?php
// ─── Bootstrap Dasar ──────────────────────────────────────────────────────────
require_once __DIR__ . '/config/app.php';

// ─── URL Routing Awal & Static File Direct Server ──────────────────────────────
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

if ($scriptDir !== '/' && $scriptDir !== '\\' && !empty($scriptDir)) {
    if (str_starts_with($requestUri, $scriptDir)) {
        $route = substr($requestUri, strlen($scriptDir));
    } else {
        $route = $requestUri;
    }
} else {
    $route = $requestUri;
}
$route = trim($route, '/');

// Static file fallback untuk folder public (misal foto avatar, logo, css, js)
if (str_starts_with($route, 'public/')) {
    $filePath = __DIR__ . '/' . $route;
    if (is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            'svg'         => 'image/svg+xml',
            'css'         => 'text/css',
            'js'          => 'application/javascript',
            default       => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

// ─── Bootstrap Lanjutan (Database, Session, Helpers) ───────────────────────────
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/session.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/helpers/response.php';

startSession();

// Jika root, redirect sesuai status autentikasi
if (empty($route) || $route === 'index.php') {
    if (isLoggedIn()) {
        $user = getUser();
        redirect(base_url(($user['role'] ?? '') === 'guru' ? 'guru/dashboard' : 'siswa/dashboard'));
    } else {
        redirect(base_url('login'));
    }
}

// Pisahkan segment URL
$segments = explode('/', $route);
$segment0 = $segments[0] ?? '';
$segment1 = $segments[1] ?? '';
$segment2 = $segments[2] ?? '';
$segment3 = $segments[3] ?? '';

// ─── Route Mapping ────────────────────────────────────────────────────────────

// 1. Auth routes
if ($segment0 === 'login') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $ctrl = new AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->login();
    } else {
        $ctrl->index();
    }
    exit;
}

if ($segment0 === 'logout') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $ctrl = new AuthController();
    $ctrl->logout();
    exit;
}

if ($segment0 === 'register') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $ctrl = new AuthController();
    $ctrl->register();
    exit;
}

if ($segment0 === 'forgot-password' || $segment0 === 'lupa-password' || $segment0 === 'forgot') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $ctrl = new AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->resetPassword();
    } else {
        $ctrl->forgot();
    }
    exit;
}

if ($segment0 === 'auth') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $ctrl = new AuthController();
    $method = $segment1 ?: 'index';
    if (method_exists($ctrl, $method)) {
        $ctrl->$method();
    } else {
        $ctrl->index();
    }
    exit;
}

// 2. Guru routes
if ($segment0 === 'guru') {
    require_once __DIR__ . '/app/controllers/GuruController.php';
    $ctrl = new GuruController();

    // Map nested URLs to controller methods
    // contoh: guru/modul/create -> modulCreate()
    // contoh: guru/modul/edit/1 -> modulEdit(1)
    // contoh: guru/siswa/1 atau guru/siswaDetail/1 -> siswaDetail(1)
    $action = $segment1 ?: 'dashboard';
    $param1 = $segment2 ?: null;
    $param2 = $segment3 ?: null;

    if ($action === 'modul' && $param1 === 'create') {
        $ctrl->modulCreate();
        exit;
    } elseif ($action === 'modul' && $param1 === 'edit') {
        $ctrl->modulEdit($param2);
        exit;
    } elseif ($action === 'modul' && $param1 === 'delete') {
        $ctrl->modulDelete($param2);
        exit;
    } elseif ($action === 'materi' && $param1 === 'create') {
        $ctrl->materiCreate($param2);
        exit;
    } elseif ($action === 'materi' && $param1 === 'edit') {
        $ctrl->materiEdit($param2);
        exit;
    } elseif ($action === 'materi' && $param1 === 'delete') {
        $ctrl->materiDelete($param2);
        exit;
    } elseif ($action === 'kuis' && $param1 === 'create') {
        $ctrl->kuisCreate();
        exit;
    } elseif ($action === 'kuis' && $param1 === 'edit') {
        $ctrl->kuisEdit($param2);
        exit;
    } elseif ($action === 'kuis' && $param1 === 'detail') {
        $ctrl->kuisDetail($param2);
        exit;
    } elseif ($action === 'kuis' && $param1 === 'delete') {
        $ctrl->kuisDelete($param2);
        exit;
    } elseif ($action === 'soal' && $param1 === 'delete') {
        $ctrl->soalDelete($param2);
        exit;
    } elseif ($action === 'siswa' && $param1 === 'create') {
        $ctrl->siswaCreate();
        exit;
    } elseif ($action === 'siswa' && $param1 === 'update') {
        $ctrl->siswaUpdate($param2);
        exit;
    } elseif ($action === 'siswa' && $param1 === 'delete') {
        $ctrl->siswaDelete($param2);
        exit;
    } elseif ($action === 'siswa' && ($param1 === 'resetPassword' || $param1 === 'reset')) {
        $ctrl->siswaResetPassword();
        exit;
    } elseif ($action === 'siswa' && is_numeric($param1)) {
        $ctrl->siswaDetail($param1);
        exit;
    } elseif ($action === 'simulasi' && $param1 === 'saveKey') {
        $ctrl->simulasiSaveKey($param2);
        exit;
    } elseif ($action === 'simulasi' && $param1 === 'create') {
        $ctrl->simulasiCreate();
        exit;
    } elseif ($action === 'simulasi' && $param1 === 'edit') {
        $ctrl->simulasiEdit($param2);
        exit;
    } elseif ($action === 'simulasi' && $param1 === 'delete') {
        $ctrl->simulasiDelete($param2);
        exit;
    } elseif ($action === 'simulasi' && $param1 === 'submit') {
        $ctrl->simulasiSubmit($param2);
        exit;
    } elseif ($action === 'simulasi') {
        $ctrl->simulasi($param1);
        exit;
    } elseif (($action === 'kasus-keamanan' || $action === 'kasus_keamanan' || $action === 'kasus') && $param1 === 'create') {
        $ctrl->kasusKeamananCreate();
        exit;
    } elseif (($action === 'kasus-keamanan' || $action === 'kasus_keamanan' || $action === 'kasus') && $param1 === 'edit') {
        $ctrl->kasusKeamananEdit($param2);
        exit;
    } elseif (($action === 'kasus-keamanan' || $action === 'kasus_keamanan' || $action === 'kasus') && $param1 === 'delete') {
        $ctrl->kasusKeamananDelete($param2);
        exit;
    } elseif ($action === 'kasus-keamanan' || $action === 'kasus_keamanan' || $action === 'kasus') {
        $ctrl->kasusKeamanan($param1);
        exit;
    } elseif ($action === 'profil') {
        $ctrl->profil();
        exit;
    }

    // Direct method calls
    if (method_exists($ctrl, $action)) {
        $ctrl->$action($param1);
        exit;
    }

    http_response_code(404);
    render('404', ['title' => 'Halaman Tidak Ditemukan']);
    exit;
}

// 3. Siswa routes
if ($segment0 === 'siswa') {
    require_once __DIR__ . '/app/controllers/SiswaController.php';
    $ctrl = new SiswaController();

    $action = $segment1 ?: 'dashboard';
    $param1 = $segment2 ?: null;

    // Normalisasi action
    if ($action === 'nilai' || $action === 'badge') {
        $action = 'profil';
    } elseif ($action === 'upload-foto') {
        $action = 'uploadFoto';
    } elseif ($action === 'update-password' || $action === 'ganti-password' || ($action === 'profil' && ($param1 === 'update-password' || $param1 === 'ganti-password'))) {
        $ctrl->updatePassword();
        exit;
    } elseif (($action === 'kasus-keamanan' || $action === 'kasus' || $action === 'tantangan-siber') && $param1 === 'submit') {
        $ctrl->kasusKeamananSubmit($segment3 ?: null);
        exit;
    } elseif ($action === 'kasus-keamanan' || $action === 'kasus' || $action === 'tantangan-siber') {
        $ctrl->kasusKeamanan($param1);
        exit;
    }

    if (method_exists($ctrl, $action)) {
        $ctrl->$action($param1);
        exit;
    }

    http_response_code(404);
    render('404', ['title' => 'Halaman Tidak Ditemukan']);
    exit;
}

// 4. API routes (Untuk Mobile Flutter & Integrasi)
if ($segment0 === 'api') {
    require_once __DIR__ . '/app/controllers/ApiController.php';
    $ctrl = new ApiController();

    $action = $segment1 ?: 'index';
    $param1 = $segment2 ?: null;
    $param2 = $segment3 ?: null;

    if ($action === 'login') {
        $ctrl->login();
        exit;
    } elseif ($action === 'forgot-password' || $action === 'reset-password') {
        $ctrl->resetPasswordSiswa();
        exit;
    } elseif ($action === 'request-reset-password' || $action === 'request-reset') {
        $ctrl->requestResetSiswa();
        exit;
    } elseif ($action === 'modul') {
        $ctrl->getModul();
        exit;
    } elseif ($action === 'materi' && $param2 === 'selesai') {
        $ctrl->materiSelesai($param1);
        exit;
    } elseif ($action === 'materi') {
        $ctrl->getMateri($param1);
        exit;
    } elseif ($action === 'kuis' && $param2 === 'submit') {
        $ctrl->submitKuis($param1);
        exit;
    } elseif ($action === 'kuis') {
        $ctrl->getKuis($param1);
        exit;
    } elseif ($action === 'simulasi' && $param2 === 'submit') {
        $ctrl->submitSimulasi($param1);
        exit;
    } elseif ($action === 'simulasi') {
        $ctrl->getSimulasi($param1);
        exit;
    } elseif ($action === 'profil' && $param1 === 'avatar') {
        $ctrl->uploadAvatar();
        exit;
    } elseif ($action === 'profil' && ($param1 === 'update-password' || $param1 === 'password')) {
        $ctrl->updatePasswordSiswa();
        exit;
    } elseif ($action === 'update-password' || $action === 'ganti-password') {
        $ctrl->updatePasswordSiswa();
        exit;
    } elseif ($action === 'profil') {
        $ctrl->getProfil();
        exit;
    } elseif ($action === 'leaderboard') {
        $ctrl->getLeaderboard();
        exit;
    } elseif ($action === 'notifikasi' && $param1 === 'read-all') {
        $ctrl->markAllNotifikasiRead();
        exit;
    } elseif ($action === 'notifikasi' && $param2 === 'read') {
        $ctrl->markNotifikasiRead($param1);
        exit;
    } elseif ($action === 'notifikasi') {
        $ctrl->getNotifikasi();
        exit;
    } elseif (($action === 'kasus-keamanan' || $action === 'kasus') && $param2 === 'submit') {
        $ctrl->submitKasusKeamanan($param1);
        exit;
    } elseif (($action === 'kasus-keamanan' || $action === 'kasus') && $param1 !== null) {
        $ctrl->getKasusKeamananDetail($param1);
        exit;
    } elseif ($action === 'kasus-keamanan' || $action === 'kasus') {
        $ctrl->getKasusKeamanan();
        exit;
    }

    if (method_exists($ctrl, $action)) {
        $ctrl->$action($param1);
        exit;
    }

    jsonError('API Endpoint not found.', 404);
    exit;
}

// 5. Short route aliases
if (in_array($segment0, ['materi', 'kuis', 'simulasi', 'kasus-keamanan', 'tantangan-siber'])) {
    if (isLoggedIn()) {
        redirect(base_url('siswa/' . $route));
    } else {
        redirect(base_url('login'));
    }
}

// 404 Fallback
http_response_code(404);
render('404', ['title' => 'Halaman Tidak Ditemukan']);
