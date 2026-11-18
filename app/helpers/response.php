<?php
/**
 * Response Helper
 * Fungsi standar untuk mengembalikan JSON response.
 */

/**
 * Kirim JSON response sukses.
 *
 * @param mixed  $data    Data yang dikembalikan (array, object, atau null)
 * @param string $message Pesan sukses
 * @param int    $code    HTTP status code (default 200)
 */
function jsonSuccess($data = null, string $message = 'Berhasil', int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'success',
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Kirim JSON response error.
 *
 * @param string $message Pesan error
 * @param int    $code    HTTP status code (default 400)
 * @param mixed  $errors  Detail error tambahan (opsional)
 */
function jsonError(string $message = 'Terjadi kesalahan', int $code = 400, $errors = null): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $payload = [
        'status'  => 'error',
        'message' => $message,
    ];
    if ($errors !== null) {
        $payload['errors'] = $errors;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
