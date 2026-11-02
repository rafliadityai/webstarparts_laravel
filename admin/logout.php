<?php
// =========================================================
// admin/logout.php
// Logout Panel Admin PT Star Parts Motor
// =========================================================

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| LOAD CONFIG
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (function_exists('admin_audit_log')) {
    admin_audit_log('logout', 'admin_user', !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null, 'Logout admin.');
}


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

/*
 * Pastikan session aktif.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
 * Hapus seluruh data session.
 */

$_SESSION = [];


/*
|--------------------------------------------------------------------------
| HAPUS SESSION COOKIE
|--------------------------------------------------------------------------
*/

if (
    ini_get('session.use_cookies')
) {

    $params = session_get_cookie_params();

    $sessionCookieName = session_name();

    setcookie(
        $sessionCookieName,
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        (bool) $params['secure'],
        (bool) $params['httponly']
    );

    // Bersihkan juga cookie dengan scope root yang mungkin berasal
    // dari konfigurasi session lama pada localhost.
    setcookie(
        $sessionCookieName,
        '',
        time() - 42000,
        '/',
        $params['domain'],
        (bool) $params['secure'],
        (bool) $params['httponly']
    );
}


/*
|--------------------------------------------------------------------------
| DESTROY SESSION
|--------------------------------------------------------------------------
*/

session_destroy();


/*
|--------------------------------------------------------------------------
| REDIRECT LOGIN
|--------------------------------------------------------------------------
*/

header(
    'Location: ' .
    rtrim(
        BASE_URL,
        '/'
    ) .
    '/admin/index.php?logout=success'
);

exit;