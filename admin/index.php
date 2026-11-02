<?php
// =========================================================
// admin/index.php
// Login Panel Admin PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| SESSION RECOVERY
|--------------------------------------------------------------------------
|
| Dipakai untuk membersihkan session/cookie lama yang dapat menyebabkan
| redirect loop setelah perubahan konfigurasi session.
|
*/

$resetSession = get_string('reset', '') === '1';

if ($resetSession) {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $cookieParams = session_get_cookie_params();
        $sessionName = session_name();

        setcookie(
            $sessionName,
            '',
            time() - 42000,
            $cookieParams['path'] ?? '/',
            $cookieParams['domain'] ?? '',
            (bool) ($cookieParams['secure'] ?? false),
            (bool) ($cookieParams['httponly'] ?? true)
        );

        // Bersihkan juga cookie dengan scope root yang mungkin berasal
        // dari konfigurasi/session lama di localhost.
        setcookie(
            $sessionName,
            '',
            time() - 42000,
            '/',
            $cookieParams['domain'] ?? '',
            (bool) ($cookieParams['secure'] ?? false),
            (bool) ($cookieParams['httponly'] ?? true)
        );
    }

    session_destroy();
    session_start();

    redirect(base_url('admin/index.php?reset=done'));
}


/*
|--------------------------------------------------------------------------
| ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (is_admin_logged_in()) {
    redirect(base_url('admin/dashboard.php'));
}


/*
|--------------------------------------------------------------------------
| PAGE STATE
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';
$sessionExpired = get_string('session_expired', '') === '1';
if ($sessionExpired) {
    $success = 'Sesi admin sebelumnya berakhir. Silakan login kembali.';
}


/*
|--------------------------------------------------------------------------
| LOGOUT MESSAGE
|--------------------------------------------------------------------------
*/

if (get_string('logout', '') === 'success') {
    $success = 'Anda berhasil keluar dari panel administrator.';
}


/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

if (is_post()) {

    $username = post_string('username');
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf()) {

        $error =
            'Permintaan tidak valid. Silakan refresh halaman dan coba kembali.';

    } elseif ($username === '' || $password === '') {

        $error =
            'Username dan password wajib diisi.';

    } else {

        $rateLimit = admin_login_is_allowed($username);
        if (!$rateLimit['allowed']) {
            $minutes = max(1, (int) ceil(((int) $rateLimit['retry_after']) / 60));
            $error = 'Terlalu banyak percobaan login. Silakan coba kembali dalam sekitar ' . $minutes . ' menit.';
        } else try {

            $stmt = $pdo->prepare(
                "SELECT
                    id,
                    username,
                    password,
                    full_name,
                    email,
                    role,
                    status
                 FROM admin_users
                 WHERE username = ?
                 LIMIT 1"
            );

            $stmt->execute([
                $username
            ]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            $validPassword =
                is_array($admin)
                && !empty($admin['password'])
                && password_verify(
                    $password,
                    (string) $admin['password']
                );

            if (
                !$admin
                || !$validPassword
                || (int) ($admin['status'] ?? 0) !== 1
            ) {

                admin_login_record_failure($username);
                $error =
                    'Username atau password salah.';

                /*
                 * Jangan membocorkan apakah username ada,
                 * password salah, atau akun dinonaktifkan.
                 */

            } else {

                /*
                 * Hindari session fixation setelah login berhasil.
                 */
                session_regenerate_id(true);
                admin_login_clear_failures($username);
                $_SESSION['admin_last_activity'] = time();
                $_SESSION['admin_session_started'] = time();

                $_SESSION['admin_id'] =
                    (int) $admin['id'];

                $_SESSION['admin_name'] =
                    (string) $admin['full_name'];

                $_SESSION['admin_username'] =
                    (string) $admin['username'];

                $_SESSION['admin_role'] =
                    (string) $admin['role'];

                /*
                 * Update waktu login terakhir.
                 */
                try {

                    $updateLogin = $pdo->prepare(
                        "UPDATE admin_users
                         SET last_login = NOW()
                         WHERE id = ?"
                    );

                    $updateLogin->execute([
                        (int) $admin['id']
                    ]);

                } catch (Throwable $e) {

                    /*
                     * Gagal mencatat last_login tidak boleh
                     * menggagalkan login yang sudah valid.
                     */
                    error_log(
                        'Star Parts admin last_login error: ' .
                        $e->getMessage()
                    );
                }

                admin_audit_log('login_success', 'admin_user', (int) $admin['id'], 'Login admin berhasil.');
                redirect(
                    base_url('admin/dashboard.php')
                );
            }

        } catch (Throwable $e) {

            error_log(
                'Star Parts admin login error: ' .
                $e->getMessage()
            );

            $error =
                'Terjadi kendala saat memproses login. Silakan coba kembali.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| SITE DATA
|--------------------------------------------------------------------------
*/

$siteName = trim(
    (string) get_setting(
        'site_name',
        'PT Star Parts Motor'
    )
);

$logo = trim(
    (string) get_setting(
        'site_logo',
        ''
    )
);

$logoUrl =
    $logo !== ''
        ? media_url($logo)
        : '';

$csrf = csrf_token();

?>
<!DOCTYPE html>
<html
    lang="id"
>
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login Admin | <?= e($siteName); ?>
    </title>

    <meta
        name="robots"
        content="noindex,nofollow"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >

    <style>
        :root {
            --spm-red: #d71920;
            --spm-red-dark: #a90f15;
            --spm-black: #101114;
            --spm-text: #1b1d22;
            --spm-muted: #6f7480;
            --spm-border: #e4e6eb;
            --spm-bg: #f4f5f7;
            --spm-white: #ffffff;
            --spm-shadow: 0 22px 60px rgba(16, 17, 20, .12);
            --spm-font: 'Inter', sans-serif;
            --spm-heading: 'Poppins', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            background: var(--spm-bg);
            color: var(--spm-text);
            font-family: var(--spm-font);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 18px;
            position: relative;
            overflow: hidden;
        }

        .login-page::before,
        .login-page::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .login-page::before {
            width: 430px;
            height: 430px;
            top: -220px;
            right: -170px;
            background: rgba(215, 25, 32, .08);
        }

        .login-page::after {
            width: 360px;
            height: 360px;
            bottom: -200px;
            left: -150px;
            background: rgba(16, 17, 20, .05);
        }

        .login-shell {
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: var(--spm-white);
            border: 1px solid var(--spm-border);
            border-radius: 20px;
            box-shadow: var(--spm-shadow);
            padding: 34px;
        }

        .login-brand {
            display: flex;
            justify-content: center;
            margin-bottom: 24px;
        }

        .login-brand-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 56px;
        }

        .login-brand-logo {
            max-width: 210px;
            max-height: 58px;
            display: block;
            object-fit: contain;
        }

        .login-brand-fallback {
            display: flex;
            flex-direction: column;
            align-items: center;
            line-height: 1;
        }

        .login-brand-fallback strong {
            color: var(--spm-black);
            font-family: var(--spm-heading);
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .login-brand-fallback span {
            margin-top: 7px;
            color: var(--spm-red);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .24em;
        }

        .login-heading {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--spm-red);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .login-heading h1 {
            margin: 0;
            color: var(--spm-black);
            font-family: var(--spm-heading);
            font-size: clamp(26px, 4vw, 32px);
            line-height: 1.15;
        }

        .login-heading p {
            margin: 10px 0 0;
            color: var(--spm-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .alert {
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 13px;
            line-height: 1.55;
            margin-bottom: 18px;
        }

        .alert-error {
            border: 1px solid #f1c4c7;
            background: #fff5f5;
            color: #951820;
        }

        .alert-success {
            border: 1px solid #c9ead5;
            background: #f3fcf6;
            color: #20643b;
        }

        .login-form {
            display: grid;
            gap: 18px;
        }

        .form-group {
            display: grid;
            gap: 8px;
        }

        .form-label {
            color: var(--spm-text);
            font-size: 13px;
            font-weight: 700;
        }

        .form-control-wrap {
            position: relative;
        }

        .form-control-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9095a1;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            min-height: 48px;
            border: 1px solid var(--spm-border);
            border-radius: 10px;
            background: #fff;
            color: var(--spm-text);
            font: inherit;
            font-size: 14px;
            padding: 0 14px 0 42px;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .form-control:focus {
            border-color: var(--spm-red);
            box-shadow: 0 0 0 4px rgba(215, 25, 32, .09);
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            background: transparent;
            color: #858a95;
            cursor: pointer;
            border-radius: 8px;
        }

        .password-toggle:hover,
        .password-toggle:focus-visible {
            background: #f4f5f7;
            color: var(--spm-text);
            outline: none;
        }

        .password-input {
            padding-right: 50px;
        }

        .btn-login {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 10px;
            background: var(--spm-red);
            color: #fff;
            font-family: var(--spm-heading);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
            box-shadow: 0 10px 24px rgba(215, 25, 32, .18);
        }

        .btn-login:hover {
            background: var(--spm-red-dark);
            transform: translateY(-1px);
        }

        .btn-login:focus-visible {
            outline: 3px solid rgba(215, 25, 32, .16);
            outline-offset: 2px;
        }

        .login-footer {
            text-align: center;
            margin-top: 22px;
            color: var(--spm-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .login-footer a {
            color: var(--spm-red);
            font-weight: 700;
        }

        @media (max-width: 520px) {
            .login-page {
                padding: 18px 12px;
            }

            .login-card {
                padding: 24px 18px;
                border-radius: 16px;
            }
        }
    </style>

</head>

<body>

<main class="login-page">

    <div class="login-shell">

        <section class="login-card">

            <div class="login-brand">

                <a
                    href="<?= e(base_url('index.php')); ?>"
                    class="login-brand-link"
                    aria-label="<?= e($siteName); ?>"
                >

                    <?php if ($logoUrl !== ''): ?>

                        <img
                            src="<?= e($logoUrl); ?>"
                            alt="<?= e($siteName); ?>"
                            class="login-brand-logo"
                        >

                    <?php else: ?>

                        <div class="login-brand-fallback">
                            <strong>STAR PARTS</strong>
                            <span>MOTOR</span>
                        </div>

                    <?php endif; ?>

                </a>

            </div>


            <div class="login-heading">

                <span class="login-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i>
                    Administrator
                </span>

                <h1>
                    Masuk ke Panel Admin
                </h1>

                <p>
                    Kelola konten dan informasi website <?= e($siteName); ?>.
                </p>

            </div>


            <?php if ($error !== ''): ?>

                <div
                    class="alert alert-error"
                    role="alert"
                >
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= e($error); ?></span>
                </div>

            <?php endif; ?>


            <?php if ($success !== ''): ?>

                <div
                    class="alert alert-success"
                    role="status"
                >
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= e($success); ?></span>
                </div>

            <?php endif; ?>


            <form
                method="post"
                action=""
                class="login-form"
                autocomplete="on"
                novalidate
            >

                <?= csrf_input(); ?>

                <div class="form-group">

                    <label
                        for="username"
                        class="form-label"
                    >
                        Username
                    </label>

                    <div class="form-control-wrap">

                        <i
                            class="fa-regular fa-user form-control-icon"
                            aria-hidden="true"
                        ></i>

                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control"
                            value="<?= e(post_string('username')); ?>"
                            autocomplete="username"
                            maxlength="100"
                            required
                            autofocus
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>

                    <div class="form-control-wrap">

                        <i
                            class="fa-solid fa-lock form-control-icon"
                            aria-hidden="true"
                        ></i>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control password-input"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="password-toggle"
                            aria-label="Tampilkan password"
                        >
                            <i class="fa-regular fa-eye"></i>
                        </button>

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn-login"
                >
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Masuk
                </button>

            </form>


            <div class="login-footer">
                <span>
                    &copy; <?= e(date('Y')); ?> <?= e($siteName); ?>
                </span>
                <span aria-hidden="true">&nbsp;•&nbsp;</span>
                <a
                    href="<?= e(base_url('index.php')); ?>"
                >
                    Kembali ke Website
                </a>
            </div>

        </section>

    </div>

</main>


<script>
(function () {
    const input = document.getElementById('password');
    const toggle = document.getElementById('password-toggle');

    if (!input || !toggle) {
        return;
    }

    toggle.addEventListener('click', function () {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        toggle.setAttribute(
            'aria-label',
            isPassword
                ? 'Sembunyikan password'
                : 'Tampilkan password'
        );

        const icon = toggle.querySelector('i');

        if (icon) {
            icon.classList.toggle(
                'fa-eye',
                !isPassword
            );

            icon.classList.toggle(
                'fa-eye-slash',
                isPassword
            );
        }
    });
})();
</script>

</body>
</html>
