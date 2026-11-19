<?php
// =========================================================
// config.php
// Konfigurasi utama website PT Star Parts Motor
// =========================================================

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| LOCAL CONFIG + ENVIRONMENT
|--------------------------------------------------------------------------
| Opsional: salin config.local.example.php menjadi config.local.php, lalu
| isi kredensial database cPanel. File lokal tidak perlu ikut dibagikan.
| Variabel environment STARPARTS_* memiliki prioritas tertinggi.
|--------------------------------------------------------------------------
*/

$starpartsLocalConfig = [];
$starpartsLocalConfigFile = __DIR__ . '/config.local.php';
if (is_file($starpartsLocalConfigFile) && is_readable($starpartsLocalConfigFile)) {
    $starpartsLoadedConfig = require $starpartsLocalConfigFile;
    if (is_array($starpartsLoadedConfig)) {
        $starpartsLocalConfig = $starpartsLoadedConfig;
    }
}

$starpartsConfigValue = static function (string $environmentName, string $key, mixed $default = '') use (&$starpartsLocalConfig): mixed {
    $environmentValue = getenv($environmentName);
    if ($environmentValue !== false) {
        return $environmentValue;
    }
    return array_key_exists($key, $starpartsLocalConfig)
        ? $starpartsLocalConfig[$key]
        : $default;
};

$starpartsRequestHost = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost')));
if ($starpartsRequestHost === '' || !preg_match('/\\A(?:[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?|\\[[0-9a-f:]+\\])(?::[0-9]{1,5})?\\z/i', $starpartsRequestHost)) {
    $starpartsRequestHost = 'localhost';
}
$starpartsHostWithoutPort = preg_replace('/:\\d+$/', '', $starpartsRequestHost) ?? $starpartsRequestHost;
$starpartsIsLocalHost = in_array($starpartsHostWithoutPort, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
    || str_ends_with($starpartsHostWithoutPort, '.test')
    || str_ends_with($starpartsHostWithoutPort, '.localhost');

$starpartsDefaultEnvironment = $starpartsIsLocalHost ? 'development' : 'production';
$starpartsConfiguredEnvironment = trim((string) $starpartsConfigValue('STARPARTS_ENV', 'app_env', $starpartsDefaultEnvironment));
if ($starpartsConfiguredEnvironment === '') {
    $starpartsConfiguredEnvironment = $starpartsDefaultEnvironment;
}

define('APP_ENV', strtolower($starpartsConfiguredEnvironment));

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

define('DB_HOST', (string) $starpartsConfigValue('STARPARTS_DB_HOST', 'db_host', '127.0.0.1'));
define('DB_NAME', (string) $starpartsConfigValue('STARPARTS_DB_NAME', 'db_name', 'spm'));
define('DB_USER', (string) $starpartsConfigValue('STARPARTS_DB_USER', 'db_user', 'root'));
define('DB_PASS', (string) $starpartsConfigValue('STARPARTS_DB_PASS', 'db_pass', ''));

/*
|--------------------------------------------------------------------------
| SITE URL
|--------------------------------------------------------------------------
| STARPARTS_BASE_URL atau base_url di config.local.php dapat mengunci URL.
| Jika tidak diatur, host, HTTPS, dan lokasi folder aplikasi terdeteksi
| otomatis. Dengan demikian XAMPP /starparts/ dan domain cPanel root tetap
| menggunakan asset/link yang sesuai tanpa edit kode satu per satu.
|--------------------------------------------------------------------------
*/

$starpartsConfiguredBaseUrl = trim((string) $starpartsConfigValue('STARPARTS_BASE_URL', 'base_url', ''));
if ($starpartsConfiguredBaseUrl !== '' && !preg_match('~^https?://[^\\s]+$~i', $starpartsConfiguredBaseUrl)) {
    $starpartsConfiguredBaseUrl = '';
}

$starpartsRequestScheme = (
    (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
) ? 'https' : 'http';

// Sejumlah shared hosting memakai reverse proxy/SSL terminator. Hormati
// protokol yang diteruskan hosting bila nilainya eksplisit dan valid.
$starpartsForwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
if (in_array($starpartsForwardedProto, ['http', 'https'], true)) {
    $starpartsRequestScheme = $starpartsForwardedProto;
}

// Pastikan BASE_URL yang ditetapkan sama protokol dengan request saat ini
// jika host-nya sama. Ini mencegah browser memblokir CSS/JS karena mixed content
// ketika config.local.php masih berisi http:// tetapi pengunjung membuka HTTPS.
if ($starpartsConfiguredBaseUrl !== '') {
    $starpartsConfiguredHost = strtolower(trim((string) (parse_url($starpartsConfiguredBaseUrl, PHP_URL_HOST) ?: '')));
    if ($starpartsConfiguredHost !== '' && $starpartsConfiguredHost === $starpartsHostWithoutPort) {
        $starpartsConfiguredBaseUrl = preg_replace('~^https?://~i', $starpartsRequestScheme . '://', $starpartsConfiguredBaseUrl) ?: $starpartsConfiguredBaseUrl;
    }
}

$starpartsBasePath = '/';
$starpartsDocumentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
$starpartsApplicationRoot = realpath(__DIR__);
if ($starpartsDocumentRoot !== false && $starpartsApplicationRoot !== false) {
    $documentRootNormalized = rtrim($starpartsDocumentRoot, DIRECTORY_SEPARATOR);
    if ($starpartsApplicationRoot === $documentRootNormalized) {
        $starpartsBasePath = '/';
    } elseif (str_starts_with($starpartsApplicationRoot, $documentRootNormalized . DIRECTORY_SEPARATOR)) {
        $relativeApplicationPath = substr($starpartsApplicationRoot, strlen($documentRootNormalized) + 1);
        $relativeApplicationPath = trim(str_replace(DIRECTORY_SEPARATOR, '/', $relativeApplicationPath), '/');
        $starpartsBasePath = $relativeApplicationPath === '' ? '/' : '/' . $relativeApplicationPath . '/';
    }
}

if ($starpartsDocumentRoot === false && $starpartsIsLocalHost) {
    // Fallback untuk command line tanpa DOCUMENT_ROOT, sesuai folder XAMPP standar proyek.
    $starpartsBasePath = '/starparts/';
}

$starpartsDetectedBaseUrl = $starpartsRequestScheme . '://' . $starpartsRequestHost . $starpartsBasePath;
$starpartsBaseUrl = $starpartsConfiguredBaseUrl !== '' ? $starpartsConfiguredBaseUrl : $starpartsDetectedBaseUrl;
define('BASE_URL', rtrim($starpartsBaseUrl, '/') . '/');
define('SITE_NAME', 'PT Star Parts Motor');

/*
|--------------------------------------------------------------------------
| TIMEZONE
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Jakarta');


/*
|--------------------------------------------------------------------------
| ERROR REPORTING
|--------------------------------------------------------------------------
| Saat development aktifkan semua error.
| Saat production sebaiknya ubah ke 0.
|--------------------------------------------------------------------------
*/

if (APP_ENV === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}


/*
|--------------------------------------------------------------------------
| UPLOAD
|--------------------------------------------------------------------------
*/

define(
    'UPLOAD_DIR',
    __DIR__ . '/uploads/'
);

define(
    'UPLOAD_URL',
    BASE_URL . 'uploads/'
);


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
|
| Pisahkan session Star Parts dari project PHP lain yang mungkin juga
| berjalan di localhost. Tanpa nama session yang unik, cookie default
| `PHPSESSID` dapat dipakai bersama oleh beberapa aplikasi localhost
| dan menyebabkan login admin terus memantul antara login dan dashboard.
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    /*
     * Gunakan nama cookie session khusus Star Parts Motor.
     */
    session_name('STARPARTSSESSID');

    /*
     * Batasi cookie ke folder aplikasi.
     */
    $sessionCookiePath = parse_url(
        BASE_URL,
        PHP_URL_PATH
    );

    if (
        !is_string($sessionCookiePath)
        || $sessionCookiePath === ''
    ) {
        $sessionCookiePath = '/';
    }

    $sessionCookiePath = '/' . trim($sessionCookiePath, '/');
    if ($sessionCookiePath !== '/') {
        $sessionCookiePath .= '/';
    }

    $sessionCookieSecure =
        !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $sessionCookiePath,
        'secure' => $sessionCookieSecure || str_starts_with(strtolower(BASE_URL), 'https://'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| PDO CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $pdo = new PDO(
        'mysql:host=' . DB_HOST .
        ';dbname=' . DB_NAME .
        ';charset=utf8mb4',

        DB_USER,
        DB_PASS,

        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES =>
                false
        ]
    );

} catch (PDOException $e) {

    // Detail hanya masuk ke error log server, tidak ditampilkan kepada pengunjung.
    error_log('[Star Parts Motor] Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die(
        'Koneksi database gagal. Periksa DB_HOST, DB_NAME, DB_USER, dan DB_PASS di config.local.php atau environment hosting.'
    );
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}


function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id'])
        && !empty($_SESSION['admin_id']);
}


function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {

        redirect(
            BASE_URL . 'admin/index.php'
        );
    }
}