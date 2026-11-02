<?php
// admin/settings.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Pengaturan Website';
$admin     = current_admin();

$uploadDir = __DIR__ . '/../uploads/general';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

/**
 * Deteksi kolom tabel settings agar tetap kompatibel
 * dengan variasi struktur:
 * - key / value
 * - setting_key / setting_value
 * - name / value
 */
function settings_get_columns(): array
{
    static $columns = null;

    if ($columns !== null) {
        return $columns;
    }

    global $pdo;

    $columns = [];

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM site_settings");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }
    } catch (Throwable $e) {
        $columns = [];
    }

    return $columns;
}

function settings_key_column(): string
{
    $columns = settings_get_columns();

    $candidates = [
        'key',
        'setting_key',
        'name',
        'code'
    ];

    foreach ($candidates as $column) {
        if (in_array($column, $columns, true)) {
            return $column;
        }
    }

    return 'key';
}

function settings_value_column(): string
{
    $columns = settings_get_columns();

    $candidates = [
        'value',
        'setting_value',
        'content'
    ];

    foreach ($candidates as $column) {
        if (in_array($column, $columns, true)) {
            return $column;
        }
    }

    return 'value';
}

function settings_id_column(): ?string
{
    $columns = settings_get_columns();

    return in_array('id', $columns, true)
        ? 'id'
        : null;
}

function settings_updated_column(): ?string
{
    $columns = settings_get_columns();

    $candidates = [
        'updated_at',
        'modified_at'
    ];

    foreach ($candidates as $column) {
        if (in_array($column, $columns, true)) {
            return $column;
        }
    }

    return null;
}

function settings_has_column(string $column): bool
{
    return in_array(
        $column,
        settings_get_columns(),
        true
    );
}

function setting_get_local(string $key, string $default = ''): string
{
    global $pdo;

    $keyColumn   = settings_key_column();
    $valueColumn = settings_value_column();

    try {
        $sql = "
            SELECT `{$valueColumn}`
            FROM site_settings
            WHERE `{$keyColumn}` = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $key
        ]);

        $value = $stmt->fetchColumn();

        return $value !== false && $value !== null
            ? (string) $value
            : $default;

    } catch (Throwable $e) {

        return $default;
    }
}

function setting_save_local(
    string $key,
    string $value
): bool {
    global $pdo;

    $keyColumn       = settings_key_column();
    $valueColumn     = settings_value_column();
    $idColumn        = settings_id_column();
    $updatedColumn   = settings_updated_column();

    try {

        $sqlFind = "
            SELECT " .
            ($idColumn
                ? "`{$idColumn}`"
                : "`{$keyColumn}`"
            ) .
            "
            FROM site_settings
            WHERE `{$keyColumn}` = ?
            LIMIT 1
        ";

        $stmtFind = $pdo->prepare($sqlFind);

        $stmtFind->execute([
            $key
        ]);

        $existing = $stmtFind->fetchColumn();

        if ($existing !== false) {

            $setParts = [
                "`{$valueColumn}` = ?"
            ];

            $params = [
                $value
            ];

            if ($updatedColumn) {
                $setParts[] =
                    "`{$updatedColumn}` = NOW()";
            }

            if ($idColumn) {

                $sql = "
                    UPDATE site_settings
                    SET " .
                    implode(', ', $setParts) .
                    "
                    WHERE `{$idColumn}` = ?
                ";

                $params[] = $existing;

            } else {

                $sql = "
                    UPDATE site_settings
                    SET " .
                    implode(', ', $setParts) .
                    "
                    WHERE `{$keyColumn}` = ?
                ";

                $params[] = $key;
            }

            $stmtUpdate = $pdo->prepare($sql);

            return $stmtUpdate->execute(
                $params
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Insert Baru
        |--------------------------------------------------------------------------
        */

        $columns = [
            "`{$keyColumn}`",
            "`{$valueColumn}`"
        ];

        $placeholders = [
            '?',
            '?'
        ];

        $params = [
            $key,
            $value
        ];

        /*
        |--------------------------------------------------------------------------
        | Kolom tambahan opsional
        |--------------------------------------------------------------------------
        */

        if (
            settings_has_column('created_at')
        ) {

            $columns[] = '`created_at`';
            $placeholders[] = 'NOW()';

        }

        if (
            settings_has_column('updated_at')
        ) {

            $columns[] = '`updated_at`';
            $placeholders[] = 'NOW()';

        }

        $sqlInsert = "
            INSERT INTO site_settings
            (" .
            implode(', ', $columns) .
            ")
            VALUES
            (" .
            implode(', ', $placeholders) .
            ")
        ";

        $stmtInsert =
            $pdo->prepare(
                $sqlInsert
            );

        return $stmtInsert->execute(
            $params
        );

    } catch (Throwable $e) {

        return false;
    }
}

function setting_delete_local_file(
    ?string $path
): void {
    if (
        $path &&
        is_file($path)
    ) {
        delete_image_variants($path);
        @unlink($path);
    }
}

function setting_local_file_path(
    ?string $file
): ?string {

    if (!$file) {
        return null;
    }

    $file = trim($file);

    if ($file === '') {
        return null;
    }

    if (
        strpos($file, 'uploads/') === 0
    ) {

        $path =
            __DIR__ .
            '/../' .
            $file;

    } elseif (
        strpos($file, '/uploads/') === 0
    ) {

        $path =
            __DIR__ .
            '/..' .
            $file;

    } else {

        $path =
            __DIR__ .
            '/../uploads/general/' .
            basename($file);
    }

    return is_file($path)
        ? $path
        : null;
}

/*
|--------------------------------------------------------------------------
| Setting Groups
|--------------------------------------------------------------------------
*/

$settingGroups = [

    'company' => [
        'label' => 'Identitas Perusahaan',
        'icon'  => 'fa-building',
        'items' => [
            'site_name' => [
                'label' => 'Nama Website / Perusahaan',
                'type'  => 'text',
                'placeholder' => 'Star Parts Motor'
            ],
            'site_tagline' => [
                'label' => 'Tagline',
                'type'  => 'text',
                'placeholder' => 'Distributor Ban FDR Terpercaya'
            ],
            'site_description' => [
                'label' => 'Deskripsi Website',
                'type'  => 'textarea',
                'placeholder' => 'Deskripsi singkat perusahaan...'
            ],
            'phone_primary' => [
                'label' => 'Nomor Telepon',
                'type'  => 'text',
                'placeholder' => '021xxxxxxx'
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'type'  => 'text',
                'placeholder' => '628xxxxxxxxxx'
            ],
            'email' => [
                'label' => 'Email',
                'type'  => 'email',
                'placeholder' => 'info@starpartsmotor.com'
            ],
            'address' => [
                'label' => 'Alamat',
                'type'  => 'textarea',
                'placeholder' => 'Alamat kantor / gudang...'
            ]
        ]
    ],

    'about' => [
        'label' => 'Tentang Perusahaan',
        'icon'  => 'fa-circle-info',
        'items' => [
            'about_title' => [
                'label' => 'Judul Tentang Kami',
                'type'  => 'text',
                'placeholder' => 'Tentang Star Parts Motor'
            ],
            'about_content' => [
                'label' => 'Konten Tentang Kami',
                'type'  => 'textarea',
                'placeholder' => 'Profil perusahaan...'
            ],
            'vision' => [
                'label' => 'Visi',
                'type'  => 'textarea',
                'placeholder' => 'Visi perusahaan...'
            ],
            'mission' => [
                'label' => 'Misi',
                'type'  => 'textarea',
                'placeholder' => 'Misi perusahaan...'
            ]
        ]
    ],

    'social' => [
        'label' => 'Media Sosial',
        'icon'  => 'fa-share-nodes',
        'items' => [
            'facebook_url' => [
                'label' => 'Facebook',
                'type'  => 'url',
                'placeholder' => 'https://facebook.com/...'
            ],
            'instagram_url' => [
                'label' => 'Instagram',
                'type'  => 'url',
                'placeholder' => 'https://instagram.com/...'
            ],
            'youtube_url' => [
                'label' => 'YouTube',
                'type'  => 'url',
                'placeholder' => 'https://youtube.com/...'
            ],
            'linkedin_url' => [
                'label' => 'LinkedIn',
                'type'  => 'url',
                'placeholder' => 'https://linkedin.com/company/...'
            ],
            'tiktok_url' => [
                'label' => 'TikTok',
                'type'  => 'url',
                'placeholder' => 'https://tiktok.com/@...'
            ]
        ]
    ],

    'seo' => [
        'label' => 'SEO Website',
        'icon'  => 'fa-magnifying-glass',
        'items' => [
            'meta_title' => [
                'label' => 'Meta Title',
                'type'  => 'text',
                'placeholder' => 'Star Parts Motor'
            ],
            'meta_description' => [
                'label' => 'Meta Description',
                'type'  => 'textarea',
                'placeholder' => 'Deskripsi website untuk mesin pencari...'
            ],
            'meta_keywords' => [
                'label' => 'Meta Keywords',
                'type'  => 'text',
                'placeholder' => 'ban motor, FDR, distributor ban, sparepart'
            ]
        ]
    ],

    'distribution' => [
        'label' => 'Informasi Distribusi',
        'icon'  => 'fa-truck-fast',
        'items' => [
            'distribution_title' => [
                'label' => 'Judul Distribusi',
                'type'  => 'text',
                'placeholder' => 'Jaringan Distribusi'
            ],
            'distribution_content' => [
                'label' => 'Konten Distribusi',
                'type'  => 'textarea',
                'placeholder' => 'Jelaskan cakupan distribusi perusahaan...'
            ]
        ]
    ]

];

/*
|--------------------------------------------------------------------------
| POST ACTION
|--------------------------------------------------------------------------
*/

if (is_post()) {

    verify_csrf();

    $action = post_string(
        'action'
    );

    /*
    |--------------------------------------------------------------------------
    | SAVE SETTINGS
    |--------------------------------------------------------------------------
    */

    if ($action === 'save') {

        $settingsToSave = [];

        foreach ($settingGroups as $group) {

            foreach (
                $group['items']
                as $key => $config
            ) {

                $value = trim(
                    post_string($key)
                );

                $settingsToSave[$key] =
                    $value;
            }
        }

        try {

            $pdo->beginTransaction();

            foreach (
                $settingsToSave
                as $key => $value
            ) {

                if (
                    !setting_save_local(
                        $key,
                        $value
                    )
                ) {

                    throw new RuntimeException(
                        'Gagal menyimpan setting: ' .
                        $key
                    );
                }
            }

            $pdo->commit();

            set_flash(
                'success',
                'Pengaturan website berhasil disimpan.'
            );

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash(
                'error',
                'Pengaturan gagal disimpan.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/settings.php'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LOGO
    |--------------------------------------------------------------------------
    */

    if ($action === 'upload_logo') {

        if (
            !isset($_FILES['logo']) ||
            empty($_FILES['logo']['name'])
        ) {

            set_flash(
                'error',
                'Silakan pilih file logo terlebih dahulu.'
            );

            redirect(
                BASE_URL .
                '/admin/settings.php'
            );
        }

        $upload = upload_image(
            $_FILES['logo'],
            'general'
        );

        if (
            empty(
                $upload['success']
            )
        ) {

            set_flash(
                'error',
                $upload['message']
                ??
                'Logo gagal diupload.'
            );

            redirect(
                BASE_URL .
                '/admin/settings.php'
            );
        }

        $newLogo =
            'uploads/general/' .
            ltrim(
                $upload['filename'],
                '/'
            );

        $oldLogo =
            setting_get_local(
                'site_logo'
            );

        if (
            setting_save_local(
                'site_logo',
                $newLogo
            )
        ) {

            if (
                $oldLogo &&
                $oldLogo !== $newLogo
            ) {

                setting_delete_local_file(
                    setting_local_file_path(
                        $oldLogo
                    )
                );
            }

            set_flash(
                'success',
                'Logo website berhasil diperbarui.'
            );

        } else {

            setting_delete_local_file(
                setting_local_file_path(
                    $newLogo
                )
            );

            set_flash(
                'error',
                'Logo berhasil diupload tetapi gagal disimpan.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/settings.php'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REMOVE LOGO
    |--------------------------------------------------------------------------
    */

    if ($action === 'remove_logo') {

        $oldLogo =
            setting_get_local(
                'site_logo'
            );

        if (
            setting_save_local(
                'site_logo',
                ''
            )
        ) {

            if ($oldLogo) {

                setting_delete_local_file(
                    setting_local_file_path(
                        $oldLogo
                    )
                );
            }

            set_flash(
                'success',
                'Logo website berhasil dihapus.'
            );

        } else {

            set_flash(
                'error',
                'Logo gagal dihapus.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/settings.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| LOAD SETTINGS
|--------------------------------------------------------------------------
*/

$settings = [];

foreach ($settingGroups as $group) {

    foreach (
        $group['items']
        as $key => $config
    ) {

        $settings[$key] =
            setting_get_local($key);
    }
}

$logo =
    setting_get_local(
        'site_logo'
    );

$flash = get_flash();

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($pageTitle) ?> - Star Parts Motor
    </title>

    <meta
        name="theme-color"
        content="#d90000"
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
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        :root {
            --red: #d90000;
            --red-dark: #b80000;
            --black: #111;
            --text: #252525;
            --muted: #777;
            --border: #e7e7e7;
            --bg: #f7f7f9;
            --white: #fff;
            --success: #14804a;
            --danger: #c92525;
            --shadow: 0 12px 35px rgba(0,0,0,.07);
            --radius: 18px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        textarea,
        select {
            font: inherit;
        }

        .admin-layout {
            min-height: 100vh;
            display: flex;
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .admin-sidebar {
            width: 275px;
            background: var(--black);
            color: #fff;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1000;
            overflow-y: auto;
            transition: .3s ease;
        }

        .admin-brand {
            padding: 24px 22px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .admin-brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-brand-logo {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: var(--red);
            color: #fff;
            font-weight: 800;
            font-family: Poppins, sans-serif;
            box-shadow: 0 8px 20px rgba(217,0,0,.3);
            overflow: hidden;
        }

        .admin-brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #fff;
        }

        .admin-brand h1 {
            margin: 0;
            font: 700 17px/1.2 Poppins, sans-serif;
        }

        .admin-brand p {
            margin: 4px 0 0;
            color: rgba(255,255,255,.55);
            font-size: 11px;
        }

        .admin-nav {
            padding: 18px 13px 24px;
        }

        .admin-nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: rgba(255,255,255,.35);
            padding: 12px 11px 8px;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px;
            border-radius: 11px;
            color: rgba(255,255,255,.72);
            margin-bottom: 4px;
            transition: .2s ease;
            font-size: 13px;
            font-weight: 600;
        }

        .admin-nav a i {
            width: 19px;
            text-align: center;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            color: #fff;
            background: var(--red);
            box-shadow: 0 8px 18px rgba(217,0,0,.22);
        }

        .admin-sidebar-bottom {
            margin-top: 20px;
            padding: 14px 13px 24px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .admin-user-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px;
            border-radius: 12px;
            background: rgba(255,255,255,.05);
            margin-bottom: 10px;
        }

        .admin-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #fff;
            color: var(--black);
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .admin-user-name {
            font-weight: 700;
            font-size: 12px;
        }

        .admin-user-role {
            color: rgba(255,255,255,.5);
            font-size: 10px;
            margin-top: 2px;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .admin-main {
            flex: 1;
            margin-left: 275px;
            min-width: 0;
        }

        .admin-topbar {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .admin-topbar-left {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .admin-mobile-toggle {
            width: 42px;
            height: 42px;
            display: none;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 12px;
            cursor: pointer;
        }

        .topbar-title h2 {
            margin: 0;
            font: 700 18px Poppins, sans-serif;
        }

        .topbar-title p {
            margin: 3px 0 0;
            font-size: 11px;
            color: var(--muted);
        }

        .admin-content {
            padding: 28px;
        }

        /*
        |--------------------------------------------------------------------------
        | FLASH
        |--------------------------------------------------------------------------
        */

        .flash {
            padding: 14px 16px;
            border-radius: 13px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .flash.success {
            background: #eaf8f1;
            color: var(--success);
            border: 1px solid #caeddc;
        }

        .flash.error {
            background: #fff0f0;
            color: var(--danger);
            border: 1px solid #ffd2d2;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin: 0;
            font: 800 28px Poppins, sans-serif;
            letter-spacing: -.5px;
        }

        .page-header p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        /*
        |--------------------------------------------------------------------------
        | CARDS
        |--------------------------------------------------------------------------
        */

        .setting-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .setting-head {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 19px 21px;
            border-bottom: 1px solid var(--border);
        }

        .setting-head-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: var(--red);
            background: #fff0f0;
        }

        .setting-head h3 {
            margin: 0;
            font: 700 15px Poppins, sans-serif;
        }

        .setting-head p {
            margin: 3px 0 0;
            font-size: 10px;
            color: var(--muted);
        }

        .setting-body {
            padding: 21px;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0,1fr));
            gap: 16px;
        }

        .col-12 {
            grid-column: span 12;
        }

        .col-8 {
            grid-column: span 8;
        }

        .col-6 {
            grid-column: span 6;
        }

        .col-4 {
            grid-column: span 4;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-label {
            font-size: 11px;
            font-weight: 700;
            color: #444;
        }

        .form-control {
            width: 100%;
            min-height: 45px;
            padding: 10px 13px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            color: #222;
            outline: none;
            transition: .2s ease;
            font-size: 12px;
        }

        textarea.form-control {
            min-height: 125px;
            resize: vertical;
            line-height: 1.7;
        }

        .form-control:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(217,0,0,.08);
        }

        .form-help {
            color: #999;
            font-size: 10px;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | LOGO
        |--------------------------------------------------------------------------
        */

        .logo-setting {
            display: grid;
            grid-template-columns: 190px minmax(0,1fr);
            gap: 22px;
            align-items: center;
        }

        .logo-preview {
            width: 190px;
            height: 125px;
            border-radius: 15px;
            border: 1px dashed #d5d5d5;
            background: #fafafa;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .logo-preview img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .logo-empty {
            text-align: center;
            color: #aaa;
            font-size: 11px;
        }

        .logo-empty i {
            display: block;
            font-size: 25px;
            margin-bottom: 7px;
        }

        .logo-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 12px;
        }

        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 43px;
            padding: 0 16px;
            border: 0;
            border-radius: 11px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: .2s ease;
        }

        .btn-primary {
            color: #fff;
            background: var(--red);
            box-shadow: 0 8px 20px rgba(217,0,0,.18);
        }

        .btn-primary:hover {
            background: var(--red-dark);
            transform: translateY(-1px);
        }

        .btn-light {
            background: #f2f2f4;
            color: #333;
        }

        .btn-danger {
            background: #fff0f0;
            color: var(--danger);
        }

        .save-bar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | INFO
        |--------------------------------------------------------------------------
        */

        .info-box {
            padding: 13px 15px;
            border-radius: 12px;
            background: #fafafa;
            border: 1px solid #eee;
            color: #666;
            font-size: 11px;
            line-height: 1.7;
        }

        .info-box i {
            color: var(--red);
            margin-right: 5px;
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR OVERLAY
        |--------------------------------------------------------------------------
        */

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 950;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-mobile-toggle {
                display: inline-flex;
            }

            .admin-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .logo-setting {
                grid-template-columns: 1fr;
            }

            .logo-preview {
                width: 100%;
                max-width: 320px;
            }

        }

        @media (max-width: 650px) {

            .admin-topbar {
                padding: 12px 15px;
            }

            .admin-content {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 23px;
            }

            .setting-body {
                padding: 16px;
            }

            .setting-head {
                padding: 17px 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .col-12,
            .col-8,
            .col-6,
            .col-4 {
                grid-column: span 1;
            }

            .save-bar {
                flex-direction: column;
            }

            .save-bar .btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!--
    |--------------------------------------------------------------------------
    | SIDEBAR
    |--------------------------------------------------------------------------
    -->

    <aside
        class="admin-sidebar"
        id="adminSidebar"
    >

        <div class="admin-brand">

            <div class="admin-brand-wrap">

                <div class="admin-brand-logo">

                    <?php if ($logo): ?>

                        <img
                            src="<?= e(media_url($logo)) ?>"
                            alt="Logo"
                        >

                    <?php else: ?>

                        SPM

                    <?php endif; ?>

                </div>

                <div>

                    <h1>
                        Star Parts Motor
                    </h1>

                    <p>
                        Administration Panel
                    </p>

                </div>

            </div>

        </div>

        <nav class="admin-nav">

            <div class="admin-nav-label">
                Main
            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/dashboard.php"
            >
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>

            <div class="admin-nav-label">
                Management
            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/banners.php"
            >
                <i class="fa-solid fa-images"></i>
                <span>Banner</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/products.php"
            >
                <i class="fa-solid fa-box-open"></i>
                <span>Produk</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/categories.php"
            >
                <i class="fa-solid fa-layer-group"></i>
                <span>Kategori</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/brands.php"
            >
                <i class="fa-solid fa-tags"></i>
                <span>Brand</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/posts.php"
            >
                <i class="fa-solid fa-newspaper"></i>
                <span>Berita / Artikel</span>
            </a>

            <div class="admin-nav-label">
                Website
            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/pages.php"
            >
                <i class="fa-solid fa-file-lines"></i>
                <span>Halaman</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/media.php"
            >
                <i class="fa-solid fa-photo-film"></i>
                <span>Media</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/settings.php"
                class="active"
            >
                <i class="fa-solid fa-sliders"></i>
                <span>Pengaturan</span>
            </a>

            <div class="admin-nav-label">
                Others
            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/messages.php"
            >
                <i class="fa-solid fa-envelope"></i>
                <span>Pesan Kontak</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/locations.php"
            >
                <i class="fa-solid fa-location-dot"></i>
                <span>Lokasi</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/testimonials.php"
            >
                <i class="fa-solid fa-comment-dots"></i>
                <span>Testimoni</span>
            </a>

        </nav>

        <div class="admin-sidebar-bottom">

            <div class="admin-user-box">

                <div class="admin-user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>

                    <div class="admin-user-name">

                        <?= e(
                            $admin['name']
                            ??
                            $admin['username']
                            ??
                            'Administrator'
                        ) ?>

                    </div>

                    <div class="admin-user-role">
                        Administrator
                    </div>

                </div>

            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/logout.php"
                style="
                    display:flex;
                    align-items:center;
                    gap:10px;
                    padding:11px 12px;
                    color:rgba(255,255,255,.65);
                    font-size:12px;
                    font-weight:600;
                "
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>

        </div>

    </aside>

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>

    <!--
    |--------------------------------------------------------------------------
    | MAIN
    |--------------------------------------------------------------------------
    -->

    <main class="admin-main">

        <header class="admin-topbar">

            <div class="admin-topbar-left">

                <button
                    type="button"
                    class="admin-mobile-toggle"
                    id="adminMobileToggle"
                    aria-label="Buka menu"
                >
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="topbar-title">

                    <h2>
                        Pengaturan Website
                    </h2>

                    <p>
                        Kelola identitas dan informasi Star Parts Motor
                    </p>

                </div>

            </div>

            <a
                href="<?= e(BASE_URL) ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-light"
            >
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Lihat Website
            </a>

        </header>

        <div class="admin-content">

            <?php if ($flash): ?>

                <div
                    class="flash <?= e(
                        $flash['type']
                        ?? 'success'
                    ) ?>"
                >
                    <?= e(
                        $flash['message']
                        ?? ''
                    ) ?>
                </div>

            <?php endif; ?>

            <div class="page-header">

                <div>

                    <h1>
                        Pengaturan Website
                    </h1>

                    <p>
                        Atur identitas perusahaan, kontak, sosial media,
                        SEO, informasi perusahaan, dan logo website.
                    </p>

                </div>

            </div>

            <!--
            |--------------------------------------------------------------------------
            | LOGO
            |--------------------------------------------------------------------------
            -->

            <div class="setting-card">

                <div class="setting-head">

                    <div class="setting-head-icon">
                        <i class="fa-solid fa-image"></i>
                    </div>

                    <div>

                        <h3>
                            Logo Website
                        </h3>

                        <p>
                            Logo utama yang digunakan pada website dan panel admin.
                        </p>

                    </div>

                </div>

                <div class="setting-body">

                    <div class="logo-setting">

                        <div class="logo-preview">

                            <?php if ($logo): ?>

                                <img
                                    src="<?= e(
                                        media_url($logo)
                                    ) ?>"
                                    alt="Logo Star Parts Motor"
                                    id="logoPreview"
                                >

                            <?php else: ?>

                                <div
                                    class="logo-empty"
                                    id="logoEmpty"
                                >

                                    <i
                                        class="fa-regular fa-image"
                                    ></i>

                                    Belum ada logo

                                </div>

                                <img
                                    src=""
                                    alt=""
                                    id="logoPreview"
                                    style="display:none;"
                                >

                            <?php endif; ?>

                        </div>

                        <div>

                            <form
                                method="post"
                                action="<?= e(BASE_URL) ?>/admin/settings.php"
                                enctype="multipart/form-data"
                                id="logoForm"
                            >

                                <?= csrf_input() ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="upload_logo"
                                >

                                <label class="form-label">
                                    Pilih Logo
                                </label>

                                <input
                                    type="file"
                                    name="logo"
                                    id="logoInput"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp,.gif,image/*"
                                    required
                                >

                                <div class="form-help">
                                    Gunakan PNG transparan untuk hasil terbaik.
                                    Maksimal 5 MB.
                                </div>

                                <div class="logo-actions">

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                        Upload Logo
                                    </button>

                                </div>

                            </form>

                            <?php if ($logo): ?>

                                <form
                                    method="post"
                                    action="<?= e(BASE_URL) ?>/admin/settings.php"
                                    style="margin-top:9px;"
                                    onsubmit="
                                        return confirm(
                                            'Yakin ingin menghapus logo website?'
                                        );
                                    "
                                >

                                    <?= csrf_input() ?>

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove_logo"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                        Hapus Logo
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

            <!--
            |--------------------------------------------------------------------------
            | SETTINGS FORM
            |--------------------------------------------------------------------------
            -->

            <form
                method="post"
                action="<?= e(BASE_URL) ?>/admin/settings.php"
                id="settingsForm"
            >

                <?= csrf_input() ?>

                <input
                    type="hidden"
                    name="action"
                    value="save"
                >

                <?php foreach (
                    $settingGroups
                    as $groupKey => $group
                ): ?>

                    <div class="setting-card">

                        <div class="setting-head">

                            <div class="setting-head-icon">

                                <i
                                    class="fa-solid <?= e(
                                        $group['icon']
                                    ) ?>"
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    <?= e(
                                        $group['label']
                                    ) ?>
                                </h3>

                                <p>
                                    Pengaturan
                                    <?= e(
                                        strtolower(
                                            $group['label']
                                        )
                                    ) ?>
                                    website.
                                </p>

                            </div>

                        </div>

                        <div class="setting-body">

                            <div class="form-grid">

                                <?php
                                $groupItems =
                                    array_values(
                                        $group['items']
                                    );

                                $itemKeys =
                                    array_keys(
                                        $group['items']
                                    );

                                foreach (
                                    $group['items']
                                    as $key => $item
                                ):
                                ?>

                                    <?php

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Lebar Field
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        $item['type'] ===
                                        'textarea'
                                    ) {

                                        $columnClass =
                                            'col-12';

                                    } elseif (
                                        in_array(
                                            $key,
                                            [
                                                'site_name',
                                                'site_tagline',
                                                'phone_primary',
                                                'whatsapp',
                                                'email',
                                                'meta_title',
                                                'meta_keywords'
                                            ],
                                            true
                                        )
                                    ) {

                                        $columnClass =
                                            'col-6';

                                    } else {

                                        $columnClass =
                                            'col-6';
                                    }

                                    ?>

                                    <div
                                        class="form-group <?= e(
                                            $columnClass
                                        ) ?>"
                                    >

                                        <label
                                            class="form-label"
                                            for="<?= e($key) ?>"
                                        >
                                            <?= e(
                                                $item['label']
                                            ) ?>
                                        </label>

                                        <?php if (
                                            $item['type'] ===
                                            'textarea'
                                        ): ?>

                                            <textarea
                                                name="<?= e($key) ?>"
                                                id="<?= e($key) ?>"
                                                class="form-control"
                                                placeholder="<?= e(
                                                    $item['placeholder']
                                                    ??
                                                    ''
                                                ) ?>"
                                            ><?= e(
                                                $settings[$key]
                                                ??
                                                ''
                                            ) ?></textarea>

                                        <?php else: ?>

                                            <input
                                                type="<?= e(
                                                    $item['type']
                                                ) ?>"
                                                name="<?= e($key) ?>"
                                                id="<?= e($key) ?>"
                                                class="form-control"
                                                value="<?= e(
                                                    $settings[$key]
                                                    ??
                                                    ''
                                                ) ?>"
                                                placeholder="<?= e(
                                                    $item['placeholder']
                                                    ??
                                                    ''
                                                ) ?>"
                                            >

                                        <?php endif; ?>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

                <div class="save-bar">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Simpan Semua Pengaturan
                    </button>

                </div>

            </form>

            <!--
            |--------------------------------------------------------------------------
            | INFORMATION
            |--------------------------------------------------------------------------
            -->

            <div class="setting-card" style="margin-top:20px;">

                <div class="setting-head">

                    <div class="setting-head-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>

                    <div>

                        <h3>
                            Informasi
                        </h3>

                        <p>
                            Penggunaan pengaturan pada website.
                        </p>

                    </div>

                </div>

                <div class="setting-body">

                    <div class="info-box">

                        <i class="fa-solid fa-check-circle"></i>

                        Setelah pengaturan disimpan, halaman website
                        dapat mengambil data tersebut melalui fungsi
                        <strong>get_setting()</strong> yang sudah digunakan
                        pada project.

                        <br><br>

                        <i class="fa-solid fa-folder-open"></i>

                        Logo diupload ke folder:

                        <strong>
                            uploads/general/
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | MOBILE SIDEBAR
        |--------------------------------------------------------------------------
        */

        const sidebar =
            document.getElementById(
                'adminSidebar'
            );

        const toggle =
            document.getElementById(
                'adminMobileToggle'
            );

        const overlay =
            document.getElementById(
                'sidebarOverlay'
            );

        function openSidebar() {

            if (!sidebar) {
                return;
            }

            sidebar.classList.add(
                'show'
            );

            if (overlay) {
                overlay.classList.add(
                    'show'
                );
            }

            document.body.style.overflow =
                'hidden';
        }

        function closeSidebar() {

            if (!sidebar) {
                return;
            }

            sidebar.classList.remove(
                'show'
            );

            if (overlay) {
                overlay.classList.remove(
                    'show'
                );
            }

            document.body.style.overflow =
                '';
        }

        if (toggle) {

            toggle.addEventListener(
                'click',
                function () {

                    if (
                        sidebar.classList.contains(
                            'show'
                        )
                    ) {

                        closeSidebar();

                    } else {

                        openSidebar();

                    }

                }
            );

        }

        if (overlay) {

            overlay.addEventListener(
                'click',
                closeSidebar
            );

        }

        /*
        |--------------------------------------------------------------------------
        | LOGO PREVIEW
        |--------------------------------------------------------------------------
        */

        const logoInput =
            document.getElementById(
                'logoInput'
            );

        const logoPreview =
            document.getElementById(
                'logoPreview'
            );

        const logoEmpty =
            document.getElementById(
                'logoEmpty'
            );

        if (
            logoInput &&
            logoPreview
        ) {

            logoInput.addEventListener(
                'change',
                function () {

                    const file =
                        this.files &&
                        this.files[0];

                    if (!file) {
                        return;
                    }

                    if (
                        !file.type.startsWith(
                            'image/'
                        )
                    ) {

                        alert(
                            'File logo harus berupa gambar.'
                        );

                        this.value = '';

                        return;
                    }

                    const maxSize =
                        5 *
                        1024 *
                        1024;

                    if (
                        file.size >
                        maxSize
                    ) {

                        alert(
                            'Ukuran logo maksimal 5 MB.'
                        );

                        this.value = '';

                        return;
                    }

                    const reader =
                        new FileReader();

                    reader.onload =
                        function (event) {

                            logoPreview.src =
                                event.target.result;

                            logoPreview.style.display =
                                'block';

                            if (logoEmpty) {
                                logoEmpty.style.display =
                                    'none';
                            }

                        };

                    reader.readAsDataURL(
                        file
                    );

                }
            );

        }

        /*
        |--------------------------------------------------------------------------
        | DOUBLE SUBMIT
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('form')
            .forEach(
                function (form) {

                    form.addEventListener(
                        'submit',
                        function (event) {

                            if (
                                form.dataset
                                    .submitting ===
                                'true'
                            ) {

                                event.preventDefault();

                                return;

                            }

                            form.dataset.submitting =
                                'true';

                            const button =
                                form.querySelector(
                                    'button[type="submit"]'
                                );

                            if (button) {

                                button.disabled =
                                    true;

                                button.style.opacity =
                                    '.7';

                            }

                        }
                    );

                }
            );

        /*
        |--------------------------------------------------------------------------
        | CHARACTER COUNTERS
        |--------------------------------------------------------------------------
        */

        [
            'site_description',
            'about_content',
            'vision',
            'mission',
            'meta_description',
            'distribution_content'
        ].forEach(
            function (id) {

                const element =
                    document.getElementById(
                        id
                    );

                if (!element) {
                    return;
                }

                const wrapper =
                    element.parentElement;

                if (!wrapper) {
                    return;
                }

                const counter =
                    document.createElement(
                        'div'
                    );

                counter.style.cssText =
                    `
                    text-align:right;
                    color:#aaa;
                    font-size:9px;
                    margin-top:-2px;
                    `;

                wrapper.appendChild(
                    counter
                );

                function updateCounter() {

                    counter.textContent =
                        element.value.length +
                        ' karakter';

                }

                element.addEventListener(
                    'input',
                    updateCounter
                );

                updateCounter();

            }
        );

    }
);

</script>

</body>
</html>