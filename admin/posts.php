<?php
// admin/posts.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Berita / Artikel';
$admin     = current_admin();

$uploadDir = __DIR__ . '/../uploads/posts';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function post_old_image_path(?string $image): ?string
{
    if (!$image) {
        return null;
    }

    $image = trim($image);

    if ($image === '') {
        return null;
    }

    if (strpos($image, 'uploads/') === 0) {
        $path = __DIR__ . '/../' . $image;
    } elseif (strpos($image, '/uploads/') === 0) {
        $path = __DIR__ . '/..' . $image;
    } else {
        $path = __DIR__ . '/../uploads/posts/' . basename($image);
    }

    return is_file($path) ? $path : null;
}

function post_slug_exists(string $slug, ?int $excludeId = null): bool
{
    global $pdo;

    if ($excludeId) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM posts
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
            FROM posts
            WHERE slug = ?
            LIMIT 1
        ");

        $stmt->execute([
            $slug
        ]);
    }

    return (bool) $stmt->fetchColumn();
}

function post_make_unique_slug(
    string $title,
    ?int $excludeId = null
): string {
    $base = slugify($title);

    if ($base === '') {
        $base = 'artikel';
    }

    $slug = $base;
    $no   = 1;

    while (post_slug_exists($slug, $excludeId)) {
        $no++;
        $slug = $base . '-' . $no;
    }

    return $slug;
}

function post_delete_file(?string $path): void
{
    if ($path && is_file($path)) {
        @unlink($path);
    }
}

/*
|--------------------------------------------------------------------------
| POST Action
|--------------------------------------------------------------------------
*/

if (is_post()) {

    if (!verify_csrf()) {

        set_flash(
            'error',
            'Token keamanan tidak valid. Silakan coba lagi.'
        );

        redirect(BASE_URL . '/admin/posts.php');
    }

    $action = post_string('action');

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    if ($action === 'save') {

        $id = post_int('id');

        $title       = trim(post_string('title'));
        $slug        = trim(post_string('slug'));
        $excerptText = trim(post_string('excerpt'));
        $content     = trim(post_string('content'));
        $metaTitle = trim(post_string('meta_title'));
        $metaDescription = trim(post_string('meta_description'));
        $featuredImageAlt = trim(post_string('featured_image_alt'));
        $authorName   = trim(post_string('author_name'));

        $status = post_string('status');

        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        $publishedAtInput = trim(post_string('published_at'));

        /*
        |--------------------------------------------------------------------------
        | Validasi dasar
        |--------------------------------------------------------------------------
        */

        if ($title === '') {

            set_flash(
                'error',
                'Judul artikel wajib diisi.'
            );

            redirect(
                BASE_URL .
                '/admin/posts.php' .
                ($id ? '?edit=' . $id : '') .
                '#post-form'
            );
        }

        if ($content === '') {

            set_flash(
                'error',
                'Isi artikel wajib diisi.'
            );

            redirect(
                BASE_URL .
                '/admin/posts.php' .
                ($id ? '?edit=' . $id : '') .
                '#post-form'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }

        /*
        |--------------------------------------------------------------------------
        | Slug
        |--------------------------------------------------------------------------
        */

        if ($slug === '') {

            $slug = post_make_unique_slug(
                $title,
                $id ?: null
            );

        } else {

            $slug = slugify($slug);

            if ($slug === '') {

                $slug = post_make_unique_slug(
                    $title,
                    $id ?: null
                );

            } elseif (post_slug_exists($slug, $id ?: null)) {

                $slug = post_make_unique_slug(
                    $slug,
                    $id ?: null
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Published At
        |--------------------------------------------------------------------------
        */

        $publishedAt = null;

        if ($status === 'published') {

            if ($publishedAtInput !== '') {

                $timestamp = strtotime(
                    str_replace('T', ' ', $publishedAtInput)
                );

                if ($timestamp !== false) {
                    $publishedAt = date(
                        'Y-m-d H:i:s',
                        $timestamp
                    );
                }
            }

            if ($publishedAt === null) {
                $publishedAt = date('Y-m-d H:i:s');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Data Lama
        |--------------------------------------------------------------------------
        */

        $oldPost = null;

        if ($id > 0) {

            $stmtOld = $pdo->prepare("
                SELECT *
                FROM posts
                WHERE id = ?
                LIMIT 1
            ");

            $stmtOld->execute([
                $id
            ]);

            $oldPost = $stmtOld->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$oldPost) {

                set_flash(
                    'error',
                    'Data artikel tidak ditemukan.'
                );

                redirect(
                    BASE_URL .
                    '/admin/posts.php'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Image
        |--------------------------------------------------------------------------
        */

        $image = $oldPost['featured_image'] ?? null;

        if (
            isset($_FILES['image']) &&
            !empty($_FILES['image']['name'])
        ) {

            $upload = upload_image(
                $_FILES['image'],
                $uploadDir,
                [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp',
                    'gif'
                ],
                5 * 1024 * 1024
            );

            if (
                empty($upload['success'])
            ) {

                set_flash(
                    'error',
                    $upload['message']
                    ?? 'Gagal mengupload thumbnail artikel.'
                );

                redirect(
                    BASE_URL .
                    '/admin/posts.php' .
                    ($id ? '?edit=' . $id : '') .
                    '#post-form'
                );
            }

            $image = $upload['path'];
        }

        /*
        |--------------------------------------------------------------------------
        | Save Database
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();

            if ($id > 0) {

                $stmt = $pdo->prepare("
                    UPDATE posts
                    SET
                        title = ?,
                        slug = ?,
                        excerpt = ?,
                        content = ?,
                        featured_image = ?,
                        author = ?,
                        status = ?,
                        published_at = ?,
                        meta_title = ?,
                        meta_description = ?,
                        featured_image_alt = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $stmt->execute([
                    $title,
                    $slug,
                    $excerptText !== ''
                        ? $excerptText
                        : null,
                    $content,
                    $image,
                    $authorName !== ''
                        ? $authorName
                        : null,
                    $status,
                    $publishedAt,
                    $metaTitle !== '' ? $metaTitle : null,
                    $metaDescription !== '' ? $metaDescription : null,
                    $featuredImageAlt !== '' ? $featuredImageAlt : null,
                    $id
                ]);

                $pdo->commit();

                /*
                |--------------------------------------------------------------------------
                | Hapus image lama setelah update sukses
                |--------------------------------------------------------------------------
                */

                if (
                    $image !==
                    ($oldPost['featured_image'] ?? null)
                    &&
                    !empty($oldPost['featured_image'])
                ) {

                    post_delete_file(
                        post_old_image_path(
                            $oldPost['featured_image']
                        )
                    );
                }

                set_flash(
                    'success',
                    'Artikel berhasil diperbarui.'
                );

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO posts
                    (
                        title,
                        slug,
                        excerpt,
                        content,
                        featured_image,
                        author,
                        status,
                        published_at,
                        sort_order,
                        meta_title,
                        meta_description,
                        featured_image_alt,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW()
                    )
                ");

                $stmt->execute([
                    $title,
                    $slug,
                    $excerptText !== ''
                        ? $excerptText
                        : null,
                    $content,
                    $image,
                    $authorName !== ''
                        ? $authorName
                        : null,
                    $status,
                    $publishedAt,
                    $metaTitle !== '' ? $metaTitle : null,
                    $metaDescription !== '' ? $metaDescription : null,
                    $featuredImageAlt !== '' ? $featuredImageAlt : null
                ]);

                $pdo->commit();

                set_flash(
                    'success',
                    'Artikel berhasil ditambahkan.'
                );
            }

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            /*
            |--------------------------------------------------------------------------
            | Hapus upload baru jika database gagal
            |--------------------------------------------------------------------------
            */

            if (
                !empty($image) &&
                $image !==
                ($oldPost['featured_image'] ?? null)
            ) {

                post_delete_file(
                    post_old_image_path($image)
                );
            }

            set_flash(
                'error',
                'Terjadi kesalahan saat menyimpan artikel.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/posts.php'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id = post_int('id');

        if ($id <= 0) {

            set_flash(
                'error',
                'ID artikel tidak valid.'
            );

            redirect(
                BASE_URL .
                '/admin/posts.php'
            );
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM posts
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id
        ]);

        $post = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$post) {

            set_flash(
                'error',
                'Artikel tidak ditemukan.'
            );

            redirect(
                BASE_URL .
                '/admin/posts.php'
            );
        }

        try {

            $delete = $pdo->prepare("
                DELETE FROM posts
                WHERE id = ?
            ");

            $delete->execute([
                $id
            ]);

            if (!empty($post['featured_image'])) {

                post_delete_file(
                    post_old_image_path(
                        $post['featured_image']
                    )
                );
            }

            set_flash(
                'success',
                'Artikel berhasil dihapus.'
            );

        } catch (Throwable $e) {

            error_log(
                'Star Parts admin delete post error: ' .
                $e->getMessage()
            );

            set_flash(
                'error',
                'Artikel gagal dihapus. Periksa error log hosting jika masalah berulang.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/posts.php'
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

$statusFilter = get_string('status');

$featuredFilter = get_string('featured');

$editId = get_int('edit');

/*
|--------------------------------------------------------------------------
| Query Posts
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM posts
    WHERE 1=1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            title LIKE ?
            OR slug LIKE ?
            OR excerpt LIKE ?
            OR content LIKE ?
            OR author LIKE ?
        )
    ";

    $like = '%' . $search . '%';

    $params[] = $like;
    $params[] = $like;
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

if ($featuredFilter === 'yes') {
    $sql .= "
        AND 1 = 0
    ";
} elseif ($featuredFilter === 'no') {
    // Schema database saat ini tidak menyediakan kolom featured.
    // Filter 'no' tidak perlu menambahkan kondisi SQL.
}

$sql .= "
    ORDER BY
        CASE WHEN status = 'published' THEN 1 ELSE 0 END DESC,
        COALESCE(published_at, created_at) DESC,
        id DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute(
    $params
);

$posts = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);

/*
|--------------------------------------------------------------------------
| Edit Data
|--------------------------------------------------------------------------
*/

$editPost = null;

if ($editId > 0) {

    $stmtEdit = $pdo->prepare("
        SELECT *
        FROM posts
        WHERE id = ?
        LIMIT 1
    ");

    $stmtEdit->execute([
        $editId
    ]);

    $editPost = $stmtEdit->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$editPost) {

        set_flash(
            'error',
            'Data artikel tidak ditemukan.'
        );

        redirect(
            BASE_URL .
            '/admin/posts.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalPosts = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM posts
    ")
    ->fetchColumn();

$publishedPosts = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM posts
        WHERE status = 'published'
    ")
    ->fetchColumn();

$draftPosts = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM posts
        WHERE status = 'draft'
    ")
    ->fetchColumn();

$featuredPosts = 0;

$flash = get_flash();

/*
|--------------------------------------------------------------------------
| Published datetime for input
|--------------------------------------------------------------------------
*/

$editPublishedAt = '';

if (
    !empty($editPost['published_at'])
) {

    $editPublishedAt = date(
        'Y-m-d\TH:i',
        strtotime(
            $editPost['published_at']
        )
    );
}

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
            --muted: #777;
            --border: #e7e7e7;
            --bg: #f7f7f9;
            --white: #fff;
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
        }

        /*
        |--------------------------------------------------------------------------
        | BUTTONS
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
            background: #fff1f1;
            color: var(--danger);
        }

        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
            min-height: 300px;
            line-height: 1.7;
        }

        .form-control:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(217,0,0,.08);
        }

        .form-help {
            color: #999;
            font-size: 10px;
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
        | IMAGE PREVIEW
        |--------------------------------------------------------------------------
        */

        .upload-preview {
            width: 100%;
            min-height: 170px;
            border-radius: 13px;
            border: 1px dashed #d4d4d4;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-top: 2px;
        }

        .upload-preview img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            display: block;
        }

        .upload-empty {
            text-align: center;
            color: #aaa;
            padding: 25px;
        }

        .upload-empty i {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }

        .upload-empty span {
            display: block;
            font-size: 11px;
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
            grid-template-columns: minmax(0, 1fr) 180px 180px auto auto;
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
            min-width: 1120px;
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

        .article-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 330px;
        }

        .article-thumb {
            width: 72px;
            height: 55px;
            flex: 0 0 72px;
            border-radius: 10px;
            overflow: hidden;
            background: #f3f3f3;
            border: 1px solid #ececec;
            display: grid;
            place-items: center;
        }

        .article-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .article-thumb i {
            color: #aaa;
            font-size: 18px;
        }

        .article-title {
            font-weight: 700;
            color: #222;
            line-height: 1.45;
        }

        .article-slug {
            margin-top: 3px;
            font-size: 10px;
            color: #999;
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

        .badge-featured {
            background: #fff0f0;
            color: var(--red);
        }

        .badge-normal {
            background: #f2f2f2;
            color: #777;
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

        @media (max-width: 1250px) {

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .filter-grid {
                grid-template-columns:
                    minmax(0,1fr)
                    160px
                    160px
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
                grid-template-columns: repeat(2,minmax(0,1fr));
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
                class="active"
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
                        Berita / Artikel
                    </h2>

                    <p>
                        Kelola konten berita Star Parts Motor
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

            <!-- Page Header -->

            <div class="page-header">

                <div>

                    <h1>
                        Berita / Artikel
                    </h1>

                    <p>
                        Publikasikan informasi, berita perusahaan,
                        produk, tips otomotif, dan konten Star Parts Motor.
                    </p>

                </div>

                <a
                    href="#post-form"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-plus"></i>
                    Tambah Artikel
                </a>

            </div>

            <!-- Statistics -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-newspaper"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Artikel
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $totalPosts,
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
                                $publishedPosts,
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
                                $draftPosts,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Featured
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $featuredPosts,
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
                id="post-form"
            >

                <div class="form-card-head">

                    <div>

                        <h3>
                            <?= $editPost
                                ? 'Edit Artikel'
                                : 'Tambah Artikel Baru'
                            ?>
                        </h3>

                        <p>
                            <?= $editPost
                                ? 'Perbarui informasi dan isi artikel.'
                                : 'Buat berita atau artikel baru untuk website.'
                            ?>
                        </p>

                    </div>

                    <?php if ($editPost): ?>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/posts.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Batal Edit
                        </a>

                    <?php endif; ?>

                </div>

                <div class="form-card-body">

                    <form
                        action="<?= e(BASE_URL) ?>/admin/posts.php"
                        method="post"
                        enctype="multipart/form-data"
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
                                $editPost['id']
                                ??
                                0
                            ) ?>"
                        >

                        <div class="form-grid">

                            <!-- Title -->

                            <div class="form-group col-8">

                                <label class="form-label">
                                    Judul Artikel
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    id="postTitle"
                                    class="form-control"
                                    value="<?= e(
                                        $editPost['title']
                                        ??
                                        ''
                                    ) ?>"
                                    placeholder="Contoh: Mengenal Keunggulan Ban FDR untuk Motor Harian"
                                    required
                                >

                            </div>

                            <!-- Author -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Penulis
                                </label>

                                <input
                                    type="text"
                                    name="author_name"
                                    class="form-control"
                                    value="<?= e(
                                        $editPost['author']
                                        ??
                                        ($admin['name'] ?? '')
                                    ) ?>"
                                    placeholder="Nama penulis"
                                >

                            </div>

                            <!-- Slug -->

                            <div class="form-group col-8">

                                <label class="form-label">
                                    Slug
                                </label>

                                <input
                                    type="text"
                                    name="slug"
                                    id="postSlug"
                                    class="form-control"
                                    value="<?= e(
                                        $editPost['slug']
                                        ??
                                        ''
                                    ) ?>"
                                    placeholder="mengenal-keunggulan-ban-fdr"
                                >

                                <div class="form-help">
                                    Kosongkan untuk membuat slug otomatis dari judul.
                                </div>

                            </div>

                            <!-- Published Date -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Tanggal Publikasi
                                </label>

                                <input
                                    type="datetime-local"
                                    name="published_at"
                                    class="form-control"
                                    value="<?= e(
                                        $editPublishedAt
                                    ) ?>"
                                >

                                <div class="form-help">
                                    Digunakan ketika status artikel Published.
                                </div>

                            </div>

                            <!-- Excerpt -->

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Ringkasan / Excerpt
                                </label>

                                <textarea
                                    name="excerpt"
                                    class="form-control"
                                    placeholder="Tulis ringkasan singkat artikel..."
                                ><?= e(
                                    $editPost['excerpt']
                                    ??
                                    ''
                                ) ?></textarea>

                                <div class="form-help">
                                    Ringkasan digunakan pada card berita di halaman website.
                                </div>

                            </div>

                            <!-- SEO -->

                            <div class="form-group col-6">
                                <label class="form-label" for="postMetaTitle">SEO Title</label>
                                <input type="text" name="meta_title" id="postMetaTitle" class="form-control" maxlength="255" value="<?= e($editPost['meta_title'] ?? '') ?>" placeholder="Judul artikel yang tampil di hasil pencarian">
                            </div>

                            <div class="form-group col-6">
                                <label class="form-label" for="postMetaDescription">SEO Description</label>
                                <textarea name="meta_description" id="postMetaDescription" class="form-control" maxlength="500" placeholder="Ringkasan unik artikel untuk mesin pencari."><?= e($editPost['meta_description'] ?? '') ?></textarea>
                            </div>

                            <!-- Content -->

                            <div class="form-group col-8">

                                <label class="form-label">
                                    Isi Artikel
                                    <span>*</span>
                                </label>

                                <textarea
                                    name="content"
                                    class="form-control content-editor"
                                    placeholder="Tulis isi artikel di sini..."
                                    required
                                ><?= e(
                                    $editPost['content']
                                    ??
                                    ''
                                ) ?></textarea>

                                <div class="form-help">
                                    Gunakan baris baru untuk memisahkan paragraf.
                                </div>

                            </div>

                            <!-- Image -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Thumbnail Artikel
                                </label>

                                <input
                                    type="file"
                                    name="image"
                                    id="postImage"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp,.gif,image/*"
                                >

                                <div class="form-help">
                                    JPG, PNG, WEBP atau GIF. Maksimal 5 MB.
                                </div>

                                <div class="upload-preview">

                                    <?php if (
                                        !empty(
                                            $editPost['featured_image']
                                        )
                                    ): ?>

                                        <img
                                            src="<?= e(
                                                media_url(
                                                    $editPost['featured_image']
                                                )
                                            ) ?>"
                                            id="postPreview"
                                            alt="<?= e(
                                                $editPost['title']
                                                ??
                                                'Artikel'
                                            ) ?>"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="upload-empty"
                                            id="postPreviewEmpty"
                                        >
                                            <i class="fa-regular fa-image"></i>

                                            <span>
                                                Preview thumbnail akan tampil di sini.
                                            </span>
                                        </div>

                                        <img
                                            src=""
                                            id="postPreview"
                                            alt=""
                                            style="display:none;"
                                        >

                                    <?php endif; ?>

                                </div>

                                <div style="margin-top:12px;">
                                    <label class="form-label" for="featuredImageAlt">ALT Text Thumbnail</label>
                                    <input type="text" name="featured_image_alt" id="featuredImageAlt" class="form-control" maxlength="255" value="<?= e($editPost['featured_image_alt'] ?? '') ?>" placeholder="Aktivitas distribusi produk Star Parts Motor">
                                    <div class="form-help">Deskripsikan isi gambar secara natural.</div>
                                </div>

                            </div>

                            <!-- Status -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Status Publikasi
                                </label>

                                <select
                                    name="status"
                                    id="postStatus"
                                    class="form-control"
                                >

                                    <option
                                        value="draft"
                                        <?= (
                                            !$editPost ||
                                            empty(
                                                $editPost['status']
                                            )
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Draft
                                    </option>

                                    <option
                                        value="published"
                                        <?= (
                                            $editPost &&
                                            (
                                                $editPost['status']
                                                ??
                                                'draft'
                                            ) === 'published'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Published
                                    </option>

                                </select>

                            </div>

                            <!-- Featured -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Featured
                                </label>

                                <div class="switch-wrap">

                                    <label class="switch">

                                        <input
                                            type="checkbox"
                                            name="is_featured"
                                            value="1"
                                            <?= (
                                                $editPost &&
                                                (int) (
                                                    $editPost['is_featured']
                                                    ??
                                                    0
                                                ) === 1
                                            )
                                                ? 'checked'
                                                : ''
                                            ?>
                                        >

                                        <span class="switch-slider"></span>

                                    </label>

                                    <div>

                                        <strong
                                            style="
                                                font-size:12px;
                                            "
                                        >
                                            Jadikan artikel unggulan
                                        </strong>

                                        <div
                                            style="
                                                color:#999;
                                                font-size:10px;
                                                margin-top:3px;
                                            "
                                        >
                                            Artikel featured dapat diprioritaskan pada website.
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- Actions -->

                            <div class="col-12">

                                <div class="form-actions">

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="fa-solid fa-floppy-disk"></i>

                                        <?= $editPost
                                            ? 'Simpan Perubahan'
                                            : 'Simpan Artikel'
                                        ?>
                                    </button>

                                    <?php if ($editPost): ?>

                                        <a
                                            href="<?= e(BASE_URL) ?>/admin/posts.php"
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
                    action="<?= e(BASE_URL) ?>/admin/posts.php"
                >

                    <div class="filter-grid">

                        <div class="filter-group">

                            <label class="filter-label">
                                Cari Artikel
                            </label>

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Cari judul, slug, penulis..."
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

                        <div class="filter-group">

                            <label class="filter-label">
                                Featured
                            </label>

                            <select
                                name="featured"
                                class="form-control"
                            >

                                <option value="">
                                    Semua
                                </option>

                                <option
                                    value="yes"
                                    <?= $featuredFilter === 'yes'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Featured
                                </option>

                                <option
                                    value="no"
                                    <?= $featuredFilter === 'no'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Normal
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
                            href="<?= e(BASE_URL) ?>/admin/posts.php"
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
            | ARTICLE TABLE
            |--------------------------------------------------------------------------
            -->

            <div class="table-card">

                <div class="table-head">

                    <h3>
                        Daftar Artikel
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
                            count($posts),
                            0,
                            ',',
                            '.'
                        ) ?>
                        artikel
                    </div>

                </div>

                <div class="table-responsive">

                    <?php if (!$posts): ?>

                        <div class="empty-state">

                            <i class="fa-solid fa-newspaper"></i>

                            <h4>
                                Belum ada artikel
                            </h4>

                            <p>
                                Silakan tambahkan artikel pertama untuk website.
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
                                        Artikel
                                    </th>

                                    <th width="160">
                                        Penulis
                                    </th>

                                    <th width="140">
                                        Status
                                    </th>

                                    <th width="120">
                                        Featured
                                    </th>

                                    <th width="160">
                                        Publikasi
                                    </th>

                                    <th width="110">
                                        Aksi
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach (
                                $posts
                                as $index => $post
                            ): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>

                                        <div class="article-cell">

                                            <div class="article-thumb">

                                                <?php if (
                                                    !empty(
                                                        $post['featured_image']
                                                    )
                                                ): ?>

                                                    <img
                                                        src="<?= e(
                                                            media_url(
                                                                $post['featured_image']
                                                            )
                                                        ) ?>"
                                                        alt="<?= e(
                                                            $post['title']
                                                        ) ?>"
                                                        loading="lazy"
                                                        onerror="
                                                            this.style.display='none';
                                                            this.nextElementSibling.style.display='block';
                                                        "
                                                    >

                                                    <i
                                                        class="fa-regular fa-image"
                                                        style="display:none;"
                                                    ></i>

                                                <?php else: ?>

                                                    <i class="fa-solid fa-newspaper"></i>

                                                <?php endif; ?>

                                            </div>

                                            <div>

                                                <div class="article-title">
                                                    <?= e(
                                                        $post['title']
                                                    ) ?>
                                                </div>

                                                <div class="article-slug">
                                                    <?= e(
                                                        $post['slug']
                                                    ) ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <?= e(
                                            $post['author']
                                            ??
                                            '-'
                                        ) ?>

                                    </td>

                                    <td>

                                        <?php if (
                                            (
                                                ($post['status']
                                                ??
                                                'draft')
                                                === 'published'
                                            )
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

                                        <?php if (false): ?>

                                            <span class="badge badge-featured">
                                                <i class="fa-solid fa-star"></i>
                                                Featured
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-normal">
                                                Normal
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php
                                        $dateValue =
                                            $post['published_at']
                                            ??
                                            $post['created_at']
                                            ??
                                            null;
                                        ?>

                                        <?php if ($dateValue): ?>

                                            <div
                                                style="
                                                    font-weight:600;
                                                "
                                            >
                                                <?= e(
                                                    format_date_id(
                                                        $dateValue
                                                    )
                                                ) ?>
                                            </div>

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
                                                href="<?= e(
                                                    BASE_URL
                                                ) ?>/admin/posts.php?edit=<?= (int) $post['id'] ?>#post-form"
                                                class="action-btn action-edit"
                                                title="Edit"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                            <form
                                                method="post"
                                                action="<?= e(
                                                    BASE_URL
                                                ) ?>/admin/posts.php"
                                                style="display:inline;"
                                                onsubmit="
                                                    return confirm(
                                                        'Yakin ingin menghapus artikel ini?'
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
                                                    value="<?= (int) $post['id'] ?>"
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
                'postTitle'
            );

        const slugInput =
            document.getElementById(
                'postSlug'
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
        | IMAGE PREVIEW
        |--------------------------------------------------------------------------
        */

        const imageInput =
            document.getElementById(
                'postImage'
            );

        const preview =
            document.getElementById(
                'postPreview'
            );

        const emptyPreview =
            document.getElementById(
                'postPreviewEmpty'
            );

        if (
            imageInput &&
            preview
        ) {

            imageInput.addEventListener(
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
                            'File harus berupa gambar.'
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
                            'Ukuran thumbnail maksimal 5 MB.'
                        );

                        this.value = '';

                        return;
                    }

                    const reader =
                        new FileReader();

                    reader.onload =
                        function (event) {

                            preview.src =
                                event.target.result;

                            preview.style.display =
                                'block';

                            if (
                                emptyPreview
                            ) {

                                emptyPreview.style.display =
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