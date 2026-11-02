<?php
// admin/pages.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Halaman Website';
$admin     = current_admin();

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function page_slug_exists(string $slug, ?int $excludeId = null): bool
{
    global $pdo;

    if ($excludeId) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM pages
            WHERE slug = ?
              AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $slug,
            $excludeId
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id
            FROM pages
            WHERE slug = ?
            LIMIT 1
        ");

        $stmt->execute([
            $slug
        ]);
    }

    return (bool) $stmt->fetchColumn();
}

function page_make_unique_slug(
    string $title,
    ?int $excludeId = null
): string {
    $base = slugify($title);

    if ($base === '') {
        $base = 'halaman';
    }

    $slug = $base;
    $no   = 1;

    while (page_slug_exists($slug, $excludeId)) {
        $no++;
        $slug = $base . '-' . $no;
    }

    return $slug;
}

/*
|--------------------------------------------------------------------------
| POST ACTION
|--------------------------------------------------------------------------
*/

if (is_post()) {

    verify_csrf();

    $action = post_string('action');

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    if ($action === 'save') {

        $id = get_int('id');

        $title = trim(
            post_string('title')
        );

        $slug = trim(
            post_string('slug')
        );

        $content = trim(
            post_string('content')
        );

        $excerptText = trim(
            post_string('excerpt')
        );

        $metaTitle = trim(
            post_string('meta_title')
        );

        $metaDescription = trim(
            post_string('meta_description')
        );

        $status = post_string('status');

        $sortOrder = get_int(
            'sort_order'
        );

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if ($title === '') {

            set_flash(
                'error',
                'Judul halaman wajib diisi.'
            );

            redirect(
                BASE_URL .
                '/admin/pages.php' .
                ($id ? '?edit=' . $id : '') .
                '#page-form'
            );
        }

        if ($content === '') {

            set_flash(
                'error',
                'Isi halaman wajib diisi.'
            );

            redirect(
                BASE_URL .
                '/admin/pages.php' .
                ($id ? '?edit=' . $id : '') .
                '#page-form'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $status,
                [
                    'draft',
                    'published'
                ],
                true
            )
        ) {
            $status = 'published';
        }

        /*
        |--------------------------------------------------------------------------
        | Slug
        |--------------------------------------------------------------------------
        */

        if ($slug === '') {

            $slug = page_make_unique_slug(
                $title,
                $id ?: null
            );

        } else {

            $slug = slugify($slug);

            if ($slug === '') {

                $slug = page_make_unique_slug(
                    $title,
                    $id ?: null
                );

            } elseif (
                page_slug_exists(
                    $slug,
                    $id ?: null
                )
            ) {

                $slug = page_make_unique_slug(
                    $slug,
                    $id ?: null
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Data
        |--------------------------------------------------------------------------
        */

        $oldPage = null;

        if ($id > 0) {

            $stmtOld = $pdo->prepare("
                SELECT *
                FROM pages
                WHERE id = ?
                LIMIT 1
            ");

            $stmtOld->execute([
                $id
            ]);

            $oldPage = $stmtOld->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$oldPage) {

                set_flash(
                    'error',
                    'Data halaman tidak ditemukan.'
                );

                redirect(
                    BASE_URL .
                    '/admin/pages.php'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Featured Image
        |--------------------------------------------------------------------------
        */

        $featuredImage = trim(
            (string) (
                $oldPage['featured_image']
                ?? ''
            )
        );

        if (isset($_POST['remove_featured_image'])) {

            if ($featuredImage !== '') {
                $localPath = dirname(__DIR__) . '/' . ltrim($featuredImage, '/\\');

                if (
                    str_starts_with($featuredImage, 'uploads/')
                    && is_file($localPath)
                ) {
                    @unlink($localPath);
                }
            }

            $featuredImage = '';
        }

        if (
            isset($_FILES['featured_image'])
            && is_array($_FILES['featured_image'])
            && (int) ($_FILES['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ) {

            $upload = upload_image(
                $_FILES['featured_image'],
                'pages'
            );

            if (!($upload['success'] ?? false)) {
                set_flash(
                    'error',
                    (string) ($upload['message'] ?? 'Upload gambar gagal.')
                );

                redirect(
                    BASE_URL .
                    '/admin/pages.php' .
                    ($id ? '?edit=' . $id : '') .
                    '#page-form'
                );
            }

            if ($featuredImage !== '') {
                $localPath = dirname(__DIR__) . '/' . ltrim($featuredImage, '/\\');
                if (str_starts_with($featuredImage, 'uploads/') && is_file($localPath)) {
                    @unlink($localPath);
                }
            }

            $featuredImage = (string) $upload['path'];
        }

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();

            if ($id > 0) {

                $stmt = $pdo->prepare("
                    UPDATE pages
                    SET
                        title = ?,
                        slug = ?,
                        excerpt = ?,
                        content = ?,
                        featured_image = ?,
                        meta_title = ?,
                        meta_description = ?,
                        sort_order = ?,
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $stmt->execute([
                    $title,
                    $slug,
                    $excerptText,
                    $content,
                    $featuredImage,
                    $metaTitle,
                    $metaDescription,
                    $sortOrder,
                    $status,
                    $id
                ]);

                $pdo->commit();

                set_flash(
                    'success',
                    'Halaman berhasil diperbarui.'
                );

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO pages
                    (
                        title,
                        slug,
                        excerpt,
                        content,
                        featured_image,
                        meta_title,
                        meta_description,
                        sort_order,
                        status,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                    )
                ");

                $stmt->execute([
                    $title,
                    $slug,
                    $excerptText,
                    $content,
                    $featuredImage,
                    $metaTitle,
                    $metaDescription,
                    $sortOrder,
                    $status
                ]);

                $pdo->commit();

                set_flash(
                    'success',
                    'Halaman berhasil ditambahkan.'
                );
            }

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash(
                'error',
                'Terjadi kesalahan saat menyimpan halaman.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/pages.php'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id = get_int('id');

        if ($id <= 0) {

            set_flash(
                'error',
                'ID halaman tidak valid.'
            );

            redirect(
                BASE_URL .
                '/admin/pages.php'
            );
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM pages
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id
        ]);

        $page = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$page) {

            set_flash(
                'error',
                'Halaman tidak ditemukan.'
            );

            redirect(
                BASE_URL .
                '/admin/pages.php'
            );
        }

        try {

            $delete = $pdo->prepare("
                DELETE FROM pages
                WHERE id = ?
            ");

            $delete->execute([
                $id
            ]);

            set_flash(
                'success',
                'Halaman berhasil dihapus.'
            );

        } catch (Throwable $e) {

            set_flash(
                'error',
                'Halaman gagal dihapus.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/pages.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$search = trim(
    get_string('q')
);

$statusFilter = get_string(
    'status'
);

$editId = get_int(
    'edit'
);

/*
|--------------------------------------------------------------------------
| QUERY PAGES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM pages
    WHERE 1=1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            title LIKE ?
            OR slug LIKE ?
            OR content LIKE ?
        )
    ";

    $like = '%' . $search . '%';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter === 'published') {

    $sql .= "
        AND status = 'published'
    ";

} elseif ($statusFilter === 'draft') {

    $sql .= "
        AND status = 'draft'
    ";
}

$sql .= "
    ORDER BY
        title ASC,
        id DESC
";

$stmt = $pdo->prepare(
    $sql
);

$stmt->execute(
    $params
);

$pages = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);

/*
|--------------------------------------------------------------------------
| EDIT DATA
|--------------------------------------------------------------------------
*/

$editPage = null;

if ($editId > 0) {

    $stmtEdit = $pdo->prepare("
        SELECT *
        FROM pages
        WHERE id = ?
        LIMIT 1
    ");

    $stmtEdit->execute([
        $editId
    ]);

    $editPage = $stmtEdit->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$editPage) {

        set_flash(
            'error',
            'Data halaman tidak ditemukan.'
        );

        redirect(
            BASE_URL .
            '/admin/pages.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalPages = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM pages
    ")
    ->fetchColumn();

$publishedPages = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM pages
        WHERE status = 'published'
    ")
    ->fetchColumn();

$draftPages = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM pages
        WHERE status = 'draft'
    ")
    ->fetchColumn();

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
            --black: #111111;
            --text: #252525;
            --muted: #777777;
            --border: #e7e7e7;
            --bg: #f7f7f9;
            --white: #ffffff;
            --success: #14804a;
            --warning: #b87a00;
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
        | PAGE HEADER
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

        /*
        |--------------------------------------------------------------------------
        | STAT
        |--------------------------------------------------------------------------
        */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 19px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            flex: 0 0 50px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 18px;
            background: #fff0f0;
            color: var(--red);
        }

        .stat-label {
            font-size: 11px;
            color: var(--muted);
            margin-bottom: 3px;
        }

        .stat-value {
            font: 800 22px Poppins, sans-serif;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .form-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
        }

        .form-card-head h3 {
            margin: 0;
            font: 700 16px Poppins, sans-serif;
        }

        .form-card-head p {
            margin: 5px 0 0;
            font-size: 11px;
            color: var(--muted);
        }

        .form-card-body {
            padding: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
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

        .form-label span {
            color: var(--red);
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
            min-height: 115px;
            resize: vertical;
        }

        textarea.content-editor {
            min-height: 330px;
            line-height: 1.75;
        }

        textarea.meta-description {
            min-height: 105px;
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

        .form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | SWITCH
        |--------------------------------------------------------------------------
        */

        .switch-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 45px;
        }

        .switch {
            position: relative;
            width: 45px;
            height: 25px;
            flex: 0 0 45px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .switch-slider {
            position: absolute;
            inset: 0;
            background: #d8d8d8;
            border-radius: 999px;
            cursor: pointer;
            transition: .2s ease;
        }

        .switch-slider::before {
            content: '';
            position: absolute;
            width: 19px;
            height: 19px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,.15);
        }

        .switch input:checked + .switch-slider {
            background: var(--red);
        }

        .switch input:checked + .switch-slider::before {
            transform: translateX(20px);
        }

        /*
        |--------------------------------------------------------------------------
        | INFO BOX
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
        | FILTER
        |--------------------------------------------------------------------------
        */

        .filter-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 17px;
            margin-bottom: 18px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 180px auto auto;
            gap: 10px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-label {
            font-size: 10px;
            font-weight: 700;
            color: #777;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-head {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
        }

        .table-head h3 {
            margin: 0;
            font: 700 15px Poppins, sans-serif;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        th,
        td {
            padding: 13px 16px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        th {
            background: #fafafa;
            color: #777;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 700;
        }

        td {
            font-size: 12px;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .page-cell {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 320px;
        }

        .page-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: #fff0f0;
            color: var(--red);
            display: grid;
            place-items: center;
            flex: 0 0 46px;
            font-size: 17px;
        }

        .page-name {
            font-weight: 700;
            color: #222;
            line-height: 1.45;
        }

        .page-slug {
            margin-top: 3px;
            font-size: 10px;
            color: #999;
        }

        .page-excerpt {
            margin-top: 5px;
            color: #999;
            font-size: 10px;
            line-height: 1.5;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-published {
            background: #eaf8f1;
            color: var(--success);
        }

        .badge-draft {
            background: #fff6df;
            color: var(--warning);
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            border: 0;
            display: inline-grid;
            place-items: center;
            cursor: pointer;
            transition: .2s ease;
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        .action-edit {
            background: #f2f5ff;
            color: #4667d8;
        }

        .action-delete {
            background: #fff0f0;
            color: var(--danger);
        }

        .empty-state {
            padding: 55px 25px;
            text-align: center;
            color: #999;
        }

        .empty-state i {
            font-size: 38px;
            margin-bottom: 12px;
            color: #ccc;
        }

        .empty-state h4 {
            margin: 0 0 5px;
            color: #555;
            font: 700 15px Poppins, sans-serif;
        }

        .empty-state p {
            margin: 0;
            font-size: 11px;
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

        @media (max-width: 1100px) {

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .filter-grid {
                grid-template-columns:
                    minmax(0,1fr)
                    170px
                    auto
                    auto;
            }

        }

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

            .form-grid {
                grid-template-columns:
                    repeat(2,minmax(0,1fr));
            }

            .col-12,
            .col-8,
            .col-6,
            .col-4 {
                grid-column: span 2;
            }

            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 650px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-topbar {
                padding: 12px 15px;
            }

            .admin-content {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 23px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .form-card-body {
                padding: 16px;
            }

            .form-card-head {
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

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .form-actions .btn {
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
                    SPM
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
                class="active"
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
                        Halaman Website
                    </h2>

                    <p>
                        Kelola halaman statis Star Parts Motor
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

            <!-- PAGE HEADER -->

            <div class="page-header">

                <div>

                    <h1>
                        Halaman Website
                    </h1>

                    <p>
                        Kelola halaman statis seperti Tentang Kami,
                        Visi &amp; Misi, Distribusi, Kontak, dan halaman
                        informasi perusahaan lainnya.
                    </p>

                </div>

                <a
                    href="#page-form"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-plus"></i>
                    Tambah Halaman
                </a>

            </div>

            <!-- STATISTICS -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Halaman
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $totalPages,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-globe"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Published
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $publishedPages,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-file-pen"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Draft
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $draftPages,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

            <!--
            |--------------------------------------------------------------------------
            | FORM
            |--------------------------------------------------------------------------
            -->

            <div
                class="form-card"
                id="page-form"
            >

                <div class="form-card-head">

                    <div>

                        <h3>
                            <?= $editPage
                                ? 'Edit Halaman'
                                : 'Tambah Halaman Baru'
                            ?>
                        </h3>

                        <p>
                            <?= $editPage
                                ? 'Perbarui konten halaman yang dipilih.'
                                : 'Buat halaman statis baru untuk website.'
                            ?>
                        </p>

                    </div>

                    <?php if ($editPage): ?>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/pages.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Batal Edit
                        </a>

                    <?php endif; ?>

                </div>

                <div class="form-card-body">

                    <form
                        action="<?= e(BASE_URL) ?>/admin/pages.php"
                        method="post"
                    >

                        <?= csrf_input() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="save"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) (
                                $editPage['id']
                                ??
                                0
                            ) ?>"
                        >

                        <div class="form-grid">

                            <!-- TITLE -->

                            <div class="form-group col-8">

                                <label class="form-label">
                                    Judul Halaman
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    id="pageTitleInput"
                                    class="form-control"
                                    value="<?= e(
                                        $editPage['title']
                                        ??
                                        ''
                                    ) ?>"
                                    placeholder="Contoh: Tentang Kami"
                                    required
                                >

                            </div>

                            <!-- SORT -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Urutan Tampil
                                </label>

                                <input
                                    type="number"
                                    name="sort_order"
                                    class="form-control"
                                    value="<?= e((string) ($editPage['sort_order'] ?? 0)) ?>"
                                    min="0"
                                    step="1"
                                >

                                <div class="form-help">
                                    Nilai lebih kecil akan diprioritaskan.
                                </div>

                            </div>

                            <!-- SLUG -->

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Slug / URL
                                </label>

                                <input
                                    type="text"
                                    name="slug"
                                    id="pageSlugInput"
                                    class="form-control"
                                    value="<?= e(
                                        $editPage['slug']
                                        ??
                                        ''
                                    ) ?>"
                                    placeholder="tentang-kami"
                                >

                                <div class="form-help">
                                    Contoh URL: websiteanda.com/page/tentang-kami
                                </div>

                            </div>

                            <!-- EXCERPT -->

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Ringkasan Halaman
                                </label>

                                <textarea
                                    name="excerpt"
                                    class="form-control"
                                    placeholder="Ringkasan singkat halaman..."
                                ><?= e($editPage['excerpt'] ?? '') ?></textarea>

                                <div class="form-help">
                                    Ringkasan dapat digunakan sebagai intro pada halaman.
                                </div>

                            </div>

                            <!-- CONTENT -->

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Isi Halaman
                                    <span>*</span>
                                </label>

                                <textarea
                                    name="content"
                                    class="form-control content-editor"
                                    placeholder="Tulis isi halaman di sini..."
                                    required
                                ><?= e(
                                    $editPage['content']
                                    ??
                                    ''
                                ) ?></textarea>

                                <div class="form-help">
                                    Isi halaman dapat digunakan untuk konten utama perusahaan.
                                </div>

                            </div>

                            <!-- META TITLE -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    SEO Meta Title
                                </label>

                                <input
                                    type="text"
                                    name="meta_title"
                                    class="form-control"
                                    maxlength="255"
                                    value="<?= e($editPage['meta_title'] ?? '') ?>"
                                    placeholder="Tentang Star Parts Motor"
                                >

                                <div class="form-help">
                                    Judul SEO untuk mesin pencari.
                                </div>

                            </div>

                            <!-- META DESCRIPTION -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    SEO Meta Description
                                </label>

                                <textarea
                                    name="meta_description"
                                    class="form-control meta-description"
                                    maxlength="500"
                                    placeholder="Deskripsi singkat untuk hasil pencarian..."
                                ><?= e($editPage['meta_description'] ?? '') ?></textarea>

                            </div>

                            <!-- FEATURED IMAGE -->

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Gambar Utama / Featured Image
                                </label>

                                <?php
                                $pageFeaturedImage = trim(
                                    (string) (
                                        $editPage['featured_image']
                                        ?? ''
                                    )
                                );
                                $pageFeaturedImageUrl =
                                    $pageFeaturedImage !== ''
                                        ? media_url($pageFeaturedImage)
                                        : '';
                                ?>

                                <div style="border:1px dashed #d7d7de;border-radius:12px;padding:12px;background:#fafafd;">
                                    <?php if ($pageFeaturedImageUrl !== ''): ?>
                                        <img
                                            src="<?= e($pageFeaturedImageUrl); ?>"
                                            alt="Featured Image"
                                            style="display:block;width:100%;max-height:280px;object-fit:contain;border-radius:9px;background:#f2f2f2;"
                                        >
                                        <label style="display:flex;align-items:center;gap:7px;margin-top:9px;font-size:11px;color:#666;">
                                            <input type="checkbox" name="remove_featured_image" value="1">
                                            Hapus gambar saat simpan
                                        </label>
                                    <?php else: ?>
                                        <div style="height:120px;display:flex;align-items:center;justify-content:center;color:#999;font-size:11px;">
                                            Belum ada featured image
                                        </div>
                                    <?php endif; ?>
                                    <input
                                        type="file"
                                        name="featured_image"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        style="display:block;width:100%;font-size:11px;margin-top:9px;"
                                    >
                                    <div class="form-help">
                                        Gambar disimpan tanpa crop paksa sehingga rasio landscape/portrait tetap terjaga.
                                    </div>
                                </div>

                            </div>

                            <!-- STATUS -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Status
                                </label>

                                <select
                                    name="status"
                                    class="form-control"
                                >

                                    <option
                                        value="published"
                                        <?= (
                                            !$editPage ||
                                            ($editPage['status'] ?? 'published') === 'published'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Published
                                    </option>

                                    <option
                                        value="draft"
                                        <?= (
                                            $editPage &&
                                            ($editPage['status'] ?? 'published') === 'draft'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Draft
                                    </option>

                                </select>

                            </div>

                            <!-- INFORMATION -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Informasi
                                </label>

                                <div class="info-box">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Halaman Published dapat digunakan pada
                                    navigasi website setelah route halaman
                                    publik tersedia.
                                </div>

                            </div>

                            <!-- ACTION -->

                            <div class="col-12">

                                <div class="form-actions">

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="fa-solid fa-floppy-disk"></i>

                                        <?= $editPage
                                            ? 'Simpan Perubahan'
                                            : 'Simpan Halaman'
                                        ?>
                                    </button>

                                    <?php if ($editPage): ?>

                                        <a
                                            href="<?= e(BASE_URL) ?>/admin/pages.php"
                                            class="btn btn-light"
                                        >
                                            Batal
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

            <!--
            |--------------------------------------------------------------------------
            | FILTER
            |--------------------------------------------------------------------------
            -->

            <div class="filter-card">

                <form
                    method="get"
                    action="<?= e(BASE_URL) ?>/admin/pages.php"
                >

                    <div class="filter-grid">

                        <div class="filter-group">

                            <label class="filter-label">
                                Cari Halaman
                            </label>

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Cari judul, slug atau konten..."
                            >

                        </div>

                        <div class="filter-group">

                            <label class="filter-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-control"
                            >

                                <option value="">
                                    Semua
                                </option>

                                <option
                                    value="published"
                                    <?= $statusFilter === 'published'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Published
                                </option>

                                <option
                                    value="draft"
                                    <?= $statusFilter === 'draft'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Draft
                                </option>

                            </select>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-filter"></i>
                            Filter
                        </button>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/pages.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                            Reset
                        </a>

                    </div>

                </form>

            </div>

            <!--
            |--------------------------------------------------------------------------
            | TABLE
            |--------------------------------------------------------------------------
            -->

            <div class="table-card">

                <div class="table-head">

                    <h3>
                        Daftar Halaman
                    </h3>

                    <div
                        style="
                            color:#999;
                            font-size:10px;
                            margin-top:5px;
                        "
                    >
                        Menampilkan
                        <?= number_format(
                            count($pages),
                            0,
                            ',',
                            '.'
                        ) ?>
                        halaman
                    </div>

                </div>

                <div class="table-responsive">

                    <?php if (!$pages): ?>

                        <div class="empty-state">

                            <i class="fa-solid fa-file-lines"></i>

                            <h4>
                                Belum ada halaman
                            </h4>

                            <p>
                                Tambahkan halaman pertama untuk website.
                            </p>

                        </div>

                    <?php else: ?>

                        <table>

                            <thead>

                                <tr>

                                    <th width="65">
                                        No
                                    </th>

                                    <th>
                                        Halaman
                                    </th>

                                    <th width="140">
                                        Status
                                    </th>

                                    <th width="90">
                                        Urutan
                                    </th>

                                    <th width="160">
                                        Dibuat
                                    </th>

                                    <th width="110">
                                        Aksi
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach (
                                $pages
                                as $index => $page
                            ): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>

                                        <div class="page-cell">

                                            <div class="page-icon">
                                                <i class="fa-solid fa-file-lines"></i>
                                            </div>

                                            <div>

                                                <div class="page-name">
                                                    <?= e(
                                                        $page['title']
                                                    ) ?>
                                                </div>

                                                <div class="page-slug">
                                                    /<?= e(
                                                        $page['slug']
                                                    ) ?>
                                                </div>

                                                <?php if (!empty($page['content'])): ?>

                                                    <div class="page-excerpt">
                                                        <?= e(
                                                            excerpt(
                                                                strip_tags($page['content']),
                                                                110
                                                            )
                                                        ) ?>
                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <?php if (
                                            ($page['status'] ?? 'published') === 'published'
                                        ): ?>

                                            <span class="badge badge-published">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Published
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-draft">
                                                <i class="fa-solid fa-file-pen"></i>
                                                Draft
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        -
                                    </td>

                                    <td>

                                        <?php if (
                                            !empty(
                                                $page['created_at']
                                            )
                                        ): ?>

                                            <?= e(
                                                format_date_id(
                                                    $page['created_at']
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            <span
                                                style="
                                                    color:#aaa;
                                                "
                                            >
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="action-group">

                                            <a
                                                href="<?= e(BASE_URL) ?>/admin/pages.php?edit=<?= (int) $page['id'] ?>#page-form"
                                                class="action-btn action-edit"
                                                title="Edit"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                            <form
                                                method="post"
                                                action="<?= e(BASE_URL) ?>/admin/pages.php"
                                                style="display:inline;"
                                                onsubmit="
                                                    return confirm(
                                                        'Yakin ingin menghapus halaman ini?'
                                                    );
                                                "
                                            >

                                                <?= csrf_input() ?>

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $page['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-btn action-delete"
                                                    title="Hapus"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    <?php endif; ?>

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
        | SLUG OTOMATIS
        |--------------------------------------------------------------------------
        */

        const titleInput =
            document.getElementById(
                'pageTitleInput'
            );

        const slugInput =
            document.getElementById(
                'pageSlugInput'
            );

        let slugTouched =
            slugInput
                ? slugInput.value.trim() !== ''
                : false;

        if (slugInput) {

            slugInput.addEventListener(
                'input',
                function () {

                    slugTouched =
                        this.value.trim() !== '';

                }
            );

        }

        if (
            titleInput &&
            slugInput
        ) {

            titleInput.addEventListener(
                'input',
                function () {

                    if (slugTouched) {
                        return;
                    }

                    let value =
                        this.value
                            .toLowerCase()
                            .trim();

                    value =
                        value
                            .replace(
                                /[^a-z0-9\s-]/g,
                                ''
                            )
                            .replace(
                                /\s+/g,
                                '-'
                            )
                            .replace(
                                /-+/g,
                                '-'
                            );

                    slugInput.value =
                        value;

                }
            );

        }

        /*
        |--------------------------------------------------------------------------
        | DOUBLE SUBMIT PREVENTION
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

    }
);

</script>

</body>
</html>