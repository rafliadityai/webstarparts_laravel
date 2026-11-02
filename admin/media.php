<?php
// admin/media.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Media Library';
$admin     = current_admin();
$imageOptimizationReady = image_processing_available();

$uploadRoot = __DIR__ . '/../uploads';

if (!is_dir($uploadRoot)) {
    @mkdir($uploadRoot, 0755, true);
}

/*
|--------------------------------------------------------------------------
| Folder Media
|--------------------------------------------------------------------------
*/

$allowedFolders = [
    'general' => [
        'label' => 'General / Logo',
        'path'  => $uploadRoot . '/general'
    ],
    'images' => [
        'label' => 'Images / Content',
        'path'  => $uploadRoot . '/images'
    ],
    'banners' => [
        'label' => 'Banners / Hero',
        'path'  => $uploadRoot . '/banners'
    ],
    'pages' => [
        'label' => 'Pages / Featured',
        'path'  => $uploadRoot . '/pages'
    ],
    'products' => [
        'label' => 'Products',
        'path'  => $uploadRoot . '/products'
    ],
    'categories' => [
        'label' => 'Categories',
        'path'  => $uploadRoot . '/categories'
    ],
    'brands' => [
        'label' => 'Brands',
        'path'  => $uploadRoot . '/brands'
    ],
    'posts' => [
        'label' => 'Posts / News',
        'path'  => $uploadRoot . '/posts'
    ],
    'testimonials' => [
        'label' => 'Testimonials',
        'path'  => $uploadRoot . '/testimonials'
    ],
    'videos' => [
        'label' => 'Videos',
        'path'  => $uploadRoot . '/videos'
    ]
];

foreach ($allowedFolders as $folder) {
    if (!is_dir($folder['path'])) {
        @mkdir($folder['path'], 0755, true);
    }
}

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function media_normalize_folder(string $folder): string
{
    $allowed = [
        'general',
        'images',
        'banners',
        'pages',
        'products',
        'categories',
        'brands',
        'posts',
        'testimonials',
        'videos'
    ];

    return in_array($folder, $allowed, true)
        ? $folder
        : 'general';
}

function media_get_folder_path(string $folder): string
{
    global $uploadRoot;

    $folder = media_normalize_folder($folder);

    return $uploadRoot . '/' . $folder;
}

function media_build_relative_path(
    string $folder,
    string $filename
): string {
    $folder = media_normalize_folder($folder);

    return 'uploads/' .
        $folder .
        '/' .
        ltrim(
            basename($filename),
            '/'
        );
}

function media_file_path(
    string $folder,
    string $filename
): string {
    return media_get_folder_path(
        $folder
    ) .
    '/' .
    basename($filename);
}

function media_delete_local_file(
    string $path
): void {
    if (
        $path !== '' &&
        is_file($path)
    ) {
        delete_image_variants($path);
        @unlink($path);
    }
}

function media_detect_type(
    string $filename
): string {
    $extension = strtolower(
        pathinfo(
            $filename,
            PATHINFO_EXTENSION
        )
    );

    $videoExtensions = [
        'mp4',
        'webm',
        'ogg',
        'mov',
        'm4v'
    ];

    return in_array(
        $extension,
        $videoExtensions,
        true
    )
        ? 'video'
        : 'image';
}

function media_type_label(
    string $type
): string {
    return $type === 'video'
        ? 'Video'
        : 'Image';
}

function media_type_icon(
    string $type
): string {
    return $type === 'video'
        ? 'fa-video'
        : 'fa-image';
}

function media_public_url(
    string $path
): string {
    return media_url($path);
}

function media_record_folder(
    array $media
): string {

    if (
        !empty($media['file_path'])
    ) {

        $path = trim(
            str_replace('\\', '/', (string) $media['file_path']),
            '/'
        );

        if (
            preg_match(
                '#^uploads/([^/]+)/#i',
                $path,
                $matches
            )
        ) {
            return media_normalize_folder(
                $matches[1]
            );
        }
    }

    return 'general';
}

function media_local_path_from_record(
    array $media
): ?string {

    if (empty($media['file_path'])) {
        return null;
    }

    $path = trim(
        str_replace('\\', '/', (string) $media['file_path']),
        '/'
    );

    if (strpos($path, 'uploads/') === 0) {
        $fullPath =
            __DIR__ .
            '/../' .
            $path;
    } else {
        $fullPath =
            __DIR__ .
            '/../' .
            ltrim($path, '/');
    }

    return is_file($fullPath)
        ? $fullPath
        : null;
}

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
    | UPLOAD
    |--------------------------------------------------------------------------
    */

    if ($action === 'upload') {

        $folder = media_normalize_folder(
            post_string('folder')
        );

        $title = trim(
            post_string('title')
        );

        $altText = trim(
            post_string('alt_text')
        );

        if (mb_strlen($altText) > 500) {
            $altText = mb_substr($altText, 0, 500);
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi file
        |--------------------------------------------------------------------------
        */

        if (
            !isset($_FILES['media_file']) ||
            empty($_FILES['media_file']['name'])
        ) {

            set_flash(
                'error',
                'Silakan pilih file media terlebih dahulu.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        $file = $_FILES['media_file'];

        if (
            !isset($file['error']) ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {

            set_flash(
                'error',
                'Upload file gagal. Silakan coba kembali.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tentukan tipe media
        |--------------------------------------------------------------------------
        */

        $mimeType =
            function_exists('mime_content_type')
                ? @mime_content_type(
                    $file['tmp_name']
                )
                : '';

        $extension = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );

        $imageExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'gif'
        ];

        $videoExtensions = [
            'mp4',
            'webm',
            'ogg',
            'mov',
            'm4v'
        ];

        $isImage =
            in_array(
                $extension,
                $imageExtensions,
                true
            );

        $isVideo =
            in_array(
                $extension,
                $videoExtensions,
                true
            );

        $imageWidth = null;
        $imageHeight = null;

        if ($isImage) {
            $imageInfo = @getimagesize($file['tmp_name']);

            if (is_array($imageInfo)) {
                $imageWidth = isset($imageInfo[0]) ? (int) $imageInfo[0] : null;
                $imageHeight = isset($imageInfo[1]) ? (int) $imageInfo[1] : null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Folder otomatis untuk tipe media
        |--------------------------------------------------------------------------
        */

        if ($isVideo) {
            $folder = 'videos';
        } elseif ($isImage && $folder === 'videos') {
            $folder = 'images';
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi MIME
        |--------------------------------------------------------------------------
        */

        $allowedImageMime = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif'
        ];

        $allowedVideoMime = [
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/quicktime',
            'video/x-m4v'
        ];

        if ($isImage) {

            if (
                $mimeType !== '' &&
                !in_array(
                    $mimeType,
                    $allowedImageMime,
                    true
                )
            ) {

                set_flash(
                    'error',
                    'Format file gambar tidak valid.'
                );

                redirect(
                    BASE_URL .
                    '/admin/media.php'
                );
            }

            $maxSize =
                10 *
                1024 *
                1024;

        } elseif ($isVideo) {

            if (
                $mimeType !== '' &&
                !in_array(
                    $mimeType,
                    $allowedVideoMime,
                    true
                )
            ) {

                set_flash(
                    'error',
                    'Format file video tidak valid.'
                );

                redirect(
                    BASE_URL .
                    '/admin/media.php'
                );
            }

            $maxSize =
                100 *
                1024 *
                1024;

        } else {

            set_flash(
                'error',
                'Jenis file belum didukung. Gunakan JPG, PNG, WEBP, GIF, MP4, WEBM atau OGG.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Size
        |--------------------------------------------------------------------------
        */

        if (
            (int) $file['size'] >
            $maxSize
        ) {

            set_flash(
                'error',
                $isVideo
                    ? 'Ukuran video maksimal 100 MB.'
                    : 'Ukuran gambar maksimal 10 MB.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Generate filename aman
        |--------------------------------------------------------------------------
        */

        $originalName =
            basename(
                $file['name']
            );

        $baseName =
            pathinfo(
                $originalName,
                PATHINFO_FILENAME
            );

        $safeBase =
            slugify(
                $baseName
            );

        if ($safeBase === '') {
            $safeBase = 'media';
        }

        $newFilename =
            $safeBase .
            '-' .
            date('YmdHis') .
            '-' .
            bin2hex(
                random_bytes(4)
            ) .
            '.' .
            $extension;

        $targetDir =
            media_get_folder_path(
                $folder
            );

        $targetPath =
            $targetDir .
            '/' .
            $newFilename;

        /*
        |--------------------------------------------------------------------------
        | Upload file
        |--------------------------------------------------------------------------
        */

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $targetPath
            )
        ) {

            set_flash(
                'error',
                'File gagal dipindahkan ke folder upload.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        $variantFiles = $isImage
            ? generate_image_variants($targetPath)
            : [];

        $relativePath =
            media_build_relative_path(
                $folder,
                $newFilename
            );

        /*
        |--------------------------------------------------------------------------
        | Simpan Database
        |--------------------------------------------------------------------------
        */

        try {

            $stmt = $pdo->prepare("
                INSERT INTO media
                (
                    title,
                    alt_text,
                    file_name,
                    file_path,
                    file_type,
                    mime_type,
                    file_size,
                    image_width,
                    image_height,
                    uploaded_by,
                    created_at
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )
            ");

            $currentAdminId =
                !empty($admin['id'])
                    ? (int) $admin['id']
                    : null;

            $stmt->execute([
                $title !== ''
                    ? $title
                    : $baseName,
                $altText !== ''
                    ? $altText
                    : null,
                $newFilename,
                $relativePath,
                $isVideo
                    ? 'video'
                    : 'image',
                $mimeType !== ''
                    ? $mimeType
                    : null,
                (int) $file['size'],
                $imageWidth,
                $imageHeight,
                $currentAdminId
            ]);

            $successMessage = 'Media berhasil diupload.';
            if ($isImage && $variantFiles) {
                $successMessage .= ' ' . count($variantFiles) . ' versi WebP responsive otomatis dibuat.';
            } elseif ($isImage && !image_processing_available()) {
                $successMessage .= ' Server belum mengaktifkan GD; file asli tetap digunakan.';
            }

            set_flash(
                'success',
                $successMessage
            );

        } catch (Throwable $e) {

            media_delete_local_file(
                $targetPath
            );

            set_flash(
                'error',
                'Media gagal disimpan ke database.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/media.php'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE METADATA
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | REGENERATE RESPONSIVE VARIANTS
    |--------------------------------------------------------------------------
    */

    if ($action === 'regenerate_variants') {
        if (!image_processing_available()) {
            set_flash('error', 'PHP GD belum aktif. Aktifkan extension GD pada PHP/XAMPP untuk membuat WebP responsive.');
            redirect(BASE_URL . '/admin/media.php');
        }

        $rows = $pdo->query("SELECT id, file_path, file_type FROM media WHERE file_type = 'image' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $generated = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $local = media_local_path_from_record($row);
            if (!$local) { $skipped++; continue; }
            $created = generate_image_variants($local);
            if ($created) $generated += count($created); else $skipped++;
        }

        set_flash('success', 'Regenerasi WebP selesai: ' . $generated . ' varian dibuat.' . ($skipped ? ' ' . $skipped . ' media dilewati.' : ''));
        redirect(BASE_URL . '/admin/media.php');
    }

    if ($action === 'update') {

        $id = get_int('id');

        $title = trim(
            post_string('title')
        );

        $altText = trim(
            post_string('alt_text')
        );

        if (mb_strlen($altText) > 500) {
            $altText = mb_substr($altText, 0, 500);
        }

        if ($id <= 0) {

            set_flash(
                'error',
                'ID media tidak valid.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM media
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id
        ]);

        $media = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$media) {

            set_flash(
                'error',
                'Media tidak ditemukan.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        try {

            $stmtUpdate = $pdo->prepare("
                UPDATE media
                SET
                    title = ?,
                    alt_text = ?
                WHERE id = ?
            ");

            $stmtUpdate->execute([
                $title !== ''
                    ? $title
                    : null,
                $altText !== ''
                    ? $altText
                    : null,
                $id
            ]);

            set_flash(
                'success',
                'Informasi media berhasil diperbarui.'
            );

        } catch (Throwable $e) {

            set_flash(
                'error',
                'Informasi media gagal diperbarui.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/media.php'
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
                'ID media tidak valid.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM media
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id
        ]);

        $media = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$media) {

            set_flash(
                'error',
                'Media tidak ditemukan.'
            );

            redirect(
                BASE_URL .
                '/admin/media.php'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Path Lokal
        |--------------------------------------------------------------------------
        */

        $localPath =
            media_local_path_from_record(
                $media
            );

        /*
        |--------------------------------------------------------------------------
        | Hapus Database
        |--------------------------------------------------------------------------
        */

        try {

            $delete = $pdo->prepare("
                DELETE FROM media
                WHERE id = ?
            ");

            $delete->execute([
                $id
            ]);

            if ($localPath) {
                media_delete_local_file(
                    $localPath
                );
            }

            set_flash(
                'success',
                'Media berhasil dihapus.'
            );

        } catch (Throwable $e) {

            set_flash(
                'error',
                'Media gagal dihapus.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/media.php'
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

$typeFilter = get_string(
    'type'
);

$folderFilter = get_string(
    'folder'
);

$uploadFolder = media_normalize_folder(
    get_string('upload_folder', 'images')
);

$editId = get_int(
    'edit'
);

/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM media
    WHERE 1=1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            file_name LIKE ?
            OR title LIKE ?
            OR file_path LIKE ?
        )
    ";

    $like =
        '%' .
        $search .
        '%';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (
    in_array(
        $typeFilter,
        [
            'image',
            'video'
        ],
        true
    )
) {

    $sql .= "
        AND file_type = ?
    ";

    $params[] =
        $typeFilter;
}

if (isset($allowedFolders[$folderFilter])) {

    $sql .= "
        AND file_path LIKE ?
    ";

    $params[] =
        'uploads/' . $folderFilter . '/%';
}

$sql .= "
    ORDER BY id DESC
";

$stmt = $pdo->prepare(
    $sql
);

$stmt->execute(
    $params
);

$mediaList = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);

/*
|--------------------------------------------------------------------------
| EDIT MEDIA
|--------------------------------------------------------------------------
*/

$editMedia = null;

if ($editId > 0) {

    $stmtEdit = $pdo->prepare("
        SELECT *
        FROM media
        WHERE id = ?
        LIMIT 1
    ");

    $stmtEdit->execute([
        $editId
    ]);

    $editMedia = $stmtEdit->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$editMedia) {

        set_flash(
            'error',
            'Media tidak ditemukan.'
        );

        redirect(
            BASE_URL .
            '/admin/media.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalMedia = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM media
    ")
    ->fetchColumn();

$totalImages = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM media
        WHERE file_type = 'image'
    ")
    ->fetchColumn();

$totalVideos = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM media
        WHERE file_type = 'video'
    ")
    ->fetchColumn();

$totalSize = (int) $pdo
    ->query("
        SELECT COALESCE(SUM(file_size), 0)
        FROM media
    ")
    ->fetchColumn();

function media_format_bytes(
    int $bytes
): string {

    if ($bytes <= 0) {
        return '0 B';
    }

    $units = [
        'B',
        'KB',
        'MB',
        'GB'
    ];

    $index = 0;
    $size  = $bytes;

    while (
        $size >= 1024 &&
        $index < count($units) - 1
    ) {

        $size /= 1024;
        $index++;
    }

    return number_format(
        $size,
        $index === 0 ? 0 : 1,
        ',',
        '.'
    ) .
    ' ' .
    $units[$index];
}

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
        href="https://fonts.googleapis.com"
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

        .btn-danger {
            background: #fff0f0;
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
            font: 800 21px Poppins, sans-serif;
        }

        /*
        |--------------------------------------------------------------------------
        | UPLOAD FORM
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
        | DROP ZONE
        |--------------------------------------------------------------------------
        */

        .drop-zone {
            min-height: 180px;
            border: 2px dashed #d9d9d9;
            border-radius: 15px;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            cursor: pointer;
            background: #fcfcfc;
            transition: .2s ease;
        }

        .drop-zone:hover,
        .drop-zone.dragging {
            border-color: var(--red);
            background: #fff8f8;
        }

        .drop-zone-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            margin: 0 auto 12px;
            background: #fff0f0;
            color: var(--red);
            font-size: 23px;
        }

        .drop-zone strong {
            display: block;
            font-size: 13px;
            color: #333;
        }

        .drop-zone span {
            display: block;
            margin-top: 5px;
            color: #999;
            font-size: 10px;
        }

        .file-name-preview {
            margin-top: 8px;
            color: #555;
            font-size: 11px;
            font-weight: 600;
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
            grid-template-columns: minmax(0,1fr) 160px 160px auto auto;
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
        | MEDIA GRID
        |--------------------------------------------------------------------------
        */

        .media-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0,1fr));
            gap: 16px;
            padding: 18px;
        }

        .media-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 15px;
            overflow: hidden;
            transition: .2s ease;
        }

        .media-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0,0,0,.08);
        }

        .media-preview {
            width: 100%;
            aspect-ratio: 16 / 10;
            background: #f3f3f3;
            position: relative;
            overflow: hidden;
        }

        .media-preview img,
        .media-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .media-file-icon {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #aaa;
            font-size: 38px;
        }

        .media-type-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,.72);
            color: #fff;
            border-radius: 999px;
            padding: 6px 9px;
            font-size: 9px;
            font-weight: 700;
        }

        .media-card-body {
            padding: 13px;
        }

        .media-title {
            font-weight: 700;
            font-size: 12px;
            line-height: 1.45;
            color: #222;
            min-height: 35px;
            word-break: break-word;
        }

        .media-file-name {
            margin-top: 4px;
            color: #999;
            font-size: 9px;
            line-height: 1.5;
            word-break: break-all;
        }

        .media-meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-top: 9px;
            color: #999;
            font-size: 9px;
        }

        .media-actions {
            display: flex;
            gap: 6px;
            margin-top: 12px;
        }

        .media-action {
            flex: 1;
            min-height: 33px;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 10px;
            font-weight: 700;
        }

        .media-copy {
            background: #f2f5ff;
            color: #4667d8;
        }

        .media-edit {
            background: #f2f2f2;
            color: #555;
        }

        .media-delete {
            width: 35px;
            flex: 0 0 35px;
            background: #fff0f0;
            color: var(--danger);
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

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
        | OVERLAY
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

        @media (max-width: 1200px) {

            .media-grid {
                grid-template-columns:
                    repeat(3,minmax(0,1fr));
            }

            .stats-grid {
                grid-template-columns:
                    repeat(2,minmax(0,1fr));
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

            .media-grid {
                grid-template-columns:
                    repeat(2,minmax(0,1fr));
            }

        }

        @media (max-width: 600px) {

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

            .media-grid {
                grid-template-columns: 1fr;
                padding: 15px;
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
            >
                <i class="fa-solid fa-file-lines"></i>
                <span>Halaman</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/media.php"
                class="active"
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
                        Media Library
                    </h2>

                    <p>
                        Kelola foto dan video website
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
                        Media Library
                    </h1>

                    <p>
                        Upload dan kelola foto atau video yang digunakan
                        pada website Star Parts Motor.
                    </p>

                </div>

            </div>

            <!-- STATS -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-photo-film"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Media
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $totalMedia,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-image"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Images
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $totalImages,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-video"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Videos
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $totalVideos,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-hard-drive"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Ukuran
                        </div>

                        <div class="stat-value">
                            <?= e(
                                media_format_bytes(
                                    $totalSize
                                )
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

            <!--
            |--------------------------------------------------------------------------
            | UPLOAD
            |--------------------------------------------------------------------------
            -->

            <div class="form-card">

                <div class="form-card-head" style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;">

                    <div>

                        <h3>
                            Upload Media
                        </h3>

                        <p>
                            Upload foto dan video ke Media Library.
                        </p>

                        <div id="image-optimization-notice" style="margin-top:8px;padding:10px 12px;border-radius:10px;background:#f7f7f8;color:#666;font-size:10px;line-height:1.55;">
                            <strong style="color:#222;">Optimasi otomatis:</strong> <?= image_processing_available() ? 'gambar baru akan memiliki varian WebP responsive tanpa mengubah rasio asli.' : 'GD belum aktif pada PHP ini; file asli tetap dipakai sampai GD diaktifkan.'; ?>
                        </div>

                    </div>

                    <form method="post" action="<?= e(BASE_URL) ?>/admin/media.php" style="margin:0;" onsubmit="return confirm('Buat ulang semua varian WebP untuk gambar yang sudah ada?');">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="regenerate_variants">
                        <button type="submit" class="btn btn-light" style="white-space:nowrap;"><i class="fa-solid fa-wand-magic-sparkles"></i> Regenerasi WebP Lama</button>
                    </form>

                </div>

                <div class="form-card-body">

                    <form
                        method="post"
                        action="<?= e(BASE_URL) ?>/admin/media.php"
                        enctype="multipart/form-data"
                        id="uploadMediaForm"
                    >

                        <?= csrf_input() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="upload"
                        >

                        <div class="form-grid">

                            <div class="form-group col-8">

                                <label class="form-label">
                                    File Media
                                    <span>*</span>
                                </label>

                                <label
                                    class="drop-zone"
                                    for="mediaFile"
                                    id="dropZone"
                                >

                                    <div>

                                        <div class="drop-zone-icon">

                                            <i
                                                class="fa-solid fa-cloud-arrow-up"
                                            ></i>

                                        </div>

                                        <strong>
                                            Klik untuk memilih file
                                        </strong>

                                        <span>
                                            atau drag &amp; drop file ke area ini
                                        </span>

                                        <div
                                            class="file-name-preview"
                                            id="fileNamePreview"
                                        ></div>

                                    </div>

                                </label>

                                <input
                                    type="file"
                                    name="media_file"
                                    id="mediaFile"
                                    accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg,video/quicktime"
                                    style="display:none;"
                                    required
                                >

                            </div>

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Folder
                                </label>

                                <select
                                    name="folder"
                                    class="form-control"
                                    id="mediaFolder"
                                >

                                    <?php foreach ($allowedFolders as $folderKey => $folderConfig): ?>
                                        <option
                                            value="<?= e($folderKey); ?>"
                                            <?= (
                                                $uploadFolder === $folderKey
                                            ) ? 'selected' : ''; ?>
                                        >
                                            <?= e((string) $folderConfig['label']); ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>

                                <div class="form-help">
                                    Pilih folder berdasarkan posisi pemakaian: Hero, Featured Page, Product, News, Brand, dan lainnya. Video otomatis masuk folder Videos.
                                </div>

                            </div>

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Judul Media
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    id="mediaTitle"
                                    class="form-control"
                                    placeholder="Contoh: Ban FDR Sport XR"
                                >

                            </div>

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Alt Text
                                </label>

                                <input
                                    type="text"
                                    name="alt_text"
                                    class="form-control"
                                    placeholder="Deskripsi gambar untuk SEO/accessibility"
                                >

                            </div>

                            <div class="col-12">

                                <div class="form-actions">

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                        Upload Media
                                    </button>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

            <!--
            |--------------------------------------------------------------------------
            | EDIT MEDIA
            |--------------------------------------------------------------------------
            -->

            <?php if ($editMedia): ?>

                <div class="form-card">

                    <div class="form-card-head">

                        <div>

                            <h3>
                                Edit Informasi Media
                            </h3>

                            <p>
                                Perbarui judul dan alt text media.
                            </p>

                        </div>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/media.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Batal
                        </a>

                    </div>

                    <div class="form-card-body">

                        <form
                            method="post"
                            action="<?= e(BASE_URL) ?>/admin/media.php"
                        >

                            <?= csrf_input() ?>

                            <input
                                type="hidden"
                                name="action"
                                value="update"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $editMedia['id'] ?>"
                            >

                            <div class="form-grid">

                                <div class="form-group col-6">

                                    <label class="form-label">
                                        Judul Media
                                    </label>

                                    <input
                                        type="text"
                                        name="title"
                                        class="form-control"
                                        value="<?= e(
                                            $editMedia['title']
                                            ?? ''
                                        ) ?>"
                                    >

                                </div>

                                <div class="form-group col-6">

                                    <label class="form-label">
                                        Alt Text
                                    </label>

                                    <input
                                        type="text"
                                        name="alt_text"
                                        class="form-control"
                                        value="<?= e(
                                            $editMedia['alt_text']
                                            ?? ''
                                        ) ?>"
                                    >

                                </div>

                                <div class="form-group col-12">

                                    <div class="info-box"
                                        style="
                                            padding:13px 15px;
                                            border-radius:12px;
                                            background:#fafafa;
                                            border:1px solid #eee;
                                            color:#666;
                                            font-size:11px;
                                            line-height:1.7;
                                        "
                                    >

                                        <strong>
                                            <?= e(
                                                $editMedia['file_name']
                                                ??
                                                $editMedia['file_name']
                                                ??
                                                '-'
                                            ) ?>
                                        </strong>

                                        <br>

                                        <?= e(
                                            $editMedia['file_path']
                                            ??
                                            '-'
                                        ) ?>

                                    </div>

                                </div>

                                <div class="col-12">

                                    <div class="form-actions">

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            Simpan Perubahan
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

            <?php endif; ?>

            <!--
            |--------------------------------------------------------------------------
            | FILTER
            |--------------------------------------------------------------------------
            -->

            <div class="filter-card">

                <form
                    method="get"
                    action="<?= e(BASE_URL) ?>/admin/media.php"
                >

                    <div class="filter-grid">

                        <div class="filter-group">

                            <label class="filter-label">
                                Cari Media
                            </label>

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Nama file, judul, alt text..."
                            >

                        </div>

                        <div class="filter-group">

                            <label class="filter-label">
                                Tipe
                            </label>

                            <select
                                name="type"
                                class="form-control"
                            >

                                <option value="">
                                    Semua
                                </option>

                                <option
                                    value="image"
                                    <?= $typeFilter === 'image'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Image
                                </option>

                                <option
                                    value="video"
                                    <?= $typeFilter === 'video'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Video
                                </option>

                            </select>

                        </div>

                        <div class="filter-group">

                            <label class="filter-label">
                                Folder
                            </label>

                            <select
                                name="folder"
                                class="form-control"
                            >

                                <option value="">
                                    Semua Folder
                                </option>

                                <option
                                    value="general"
                                    <?= $folderFilter === 'general'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    General
                                </option>

                                <option
                                    value="images"
                                    <?= $folderFilter === 'images'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Images
                                </option>

                                <option
                                    value="videos"
                                    <?= $folderFilter === 'videos'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Videos
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
                            href="<?= e(BASE_URL) ?>/admin/media.php"
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
            | MEDIA LIST
            |--------------------------------------------------------------------------
            -->

            <div class="form-card">

                <div class="form-card-head">

                    <div>

                        <h3>
                            Media Library
                        </h3>

                        <p>
                            <?= number_format(
                                count($mediaList),
                                0,
                                ',',
                                '.'
                            ) ?>
                            media ditampilkan
                        </p>

                    </div>

                </div>

                <?php if (!$mediaList): ?>

                    <div class="empty-state">

                        <i class="fa-solid fa-photo-film"></i>

                        <h4>
                            Belum ada media
                        </h4>

                        <p>
                            Upload foto atau video untuk mulai menggunakan
                            Media Library.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="media-grid">

                        <?php foreach (
                            $mediaList
                            as $media
                        ): ?>

                            <?php

                            $mediaType =
                                $media['file_type']
                                ??
                                media_detect_type(
                                    $media['file_name']
                                    ??
                                    ''
                                );

                            $publicPath =
                                $media['file_path']
                                ??
                                media_build_relative_path(
                                    media_record_folder($media)
                                    ??
                                    'general',
                                    $media['file_name']
                                    ??
                                    ''
                                );

                            $publicUrl =
                                media_public_url(
                                    $publicPath
                                );

                            $title =
                                $media['title']
                                ??
                                $media['file_name']
                                ??
                                $media['file_name']
                                ??
                                'Media';

                            ?>

                            <article class="media-card">

                                <div class="media-preview">

                                    <?php if (
                                        $mediaType === 'video'
                                    ): ?>

                                        <video
                                            src="<?= e($publicUrl) ?>"
                                            preload="metadata"
                                            controls
                                        ></video>

                                    <?php else: ?>

                                        <img
                                            src="<?= e($publicUrl) ?>"
                                            alt="<?= e(
                                                $media['title']
                                                ??
                                                $title
                                            ) ?>"
                                            loading="lazy"
                                            onerror="
                                                this.style.display='none';
                                                this.nextElementSibling.style.display='grid';
                                            "
                                        >

                                        <div
                                            class="media-file-icon"
                                            style="display:none;"
                                        >
                                            <i
                                                class="fa-solid fa-image"
                                            ></i>
                                        </div>

                                    <?php endif; ?>

                                    <span class="media-type-badge">
                                        <i
                                            class="fa-solid <?= e(
                                                media_type_icon(
                                                    $mediaType
                                                )
                                            ) ?>"
                                        ></i>

                                        <?= e(
                                            media_type_label(
                                                $mediaType
                                            )
                                        ) ?>
                                    </span>

                                </div>

                                <div class="media-card-body">

                                    <div class="media-title">
                                        <?= e($title) ?>
                                    </div>

                                    <div class="media-file-name">
                                        <?= e(
                                            $media['file_name']
                                            ??
                                            $media['file_name']
                                            ??
                                            '-'
                                        ) ?>
                                    </div>

                                    <div class="media-meta">

                                        <span>
                                            <i
                                                class="fa-solid fa-folder"
                                            ></i>

                                            <?= e(
                                                ucfirst(
                                                    media_record_folder($media)
                                                    ??
                                                    'general'
                                                )
                                            ) ?>
                                        </span>

                                        <span>
                                            <?= e(
                                                media_format_bytes(
                                                    (int) (
                                                        $media['file_size']
                                                        ??
                                                        0
                                                    )
                                                )
                                            ) ?>
                                        </span>

                                    </div>

                                    <div class="media-actions">

                                        <button
                                            type="button"
                                            class="media-action media-copy"
                                            data-copy-url="<?= e(
                                                $publicUrl
                                            ) ?>"
                                            title="Copy URL"
                                        >
                                            <i
                                                class="fa-regular fa-copy"
                                            ></i>
                                            Copy URL
                                        </button>

                                        <a
                                            href="<?= e(
                                                BASE_URL
                                            ) ?>/admin/media.php?edit=<?= (int) $media['id'] ?>"
                                            class="media-action media-edit"
                                        >
                                            <i
                                                class="fa-solid fa-pen"
                                            ></i>
                                            Edit
                                        </a>

                                        <form
                                            method="post"
                                            action="<?= e(
                                                BASE_URL
                                            ) ?>/admin/media.php"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Yakin ingin menghapus media ini? File juga akan dihapus dari server.'
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
                                                value="<?= (int) $media['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="media-action media-delete"
                                                title="Hapus"
                                            >
                                                <i
                                                    class="fa-solid fa-trash"
                                                ></i>
                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

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
        | FILE INPUT + DRAG DROP
        |--------------------------------------------------------------------------
        */

        const fileInput =
            document.getElementById(
                'mediaFile'
            );

        const dropZone =
            document.getElementById(
                'dropZone'
            );

        const fileNamePreview =
            document.getElementById(
                'fileNamePreview'
            );

        const folderSelect =
            document.getElementById(
                'mediaFolder'
            );

        const titleInput =
            document.getElementById(
                'mediaTitle'
            );

        function updateFileInfo(file) {

            if (!fileNamePreview) {
                return;
            }

            if (!file) {

                fileNamePreview.textContent =
                    '';

                return;
            }

            fileNamePreview.textContent =
                file.name +
                ' (' +
                formatBytes(
                    file.size
                ) +
                ')';

            /*
            |--------------------------------------------------------------------------
            | Folder otomatis
            |--------------------------------------------------------------------------
            */

            if (
                folderSelect &&
                file.type.startsWith(
                    'video/'
                )
            ) {

                folderSelect.value =
                    'videos';

            } else if (
                folderSelect &&
                file.type.startsWith(
                    'image/'
                )
            ) {

                folderSelect.value =
                    'images';

            }

            /*
            |--------------------------------------------------------------------------
            | Title otomatis
            |--------------------------------------------------------------------------
            */

            if (
                titleInput &&
                titleInput.value.trim() === ''
            ) {

                const name =
                    file.name.replace(
                        /\.[^/.]+$/,
                        ''
                    );

                titleInput.value =
                    name
                        .replace(
                            /[-_]+/g,
                            ' '
                        )
                        .replace(
                            /\s+/g,
                            ' '
                        )
                        .trim();

            }

        }

        function handleFiles(
            fileList
        ) {

            if (
                !fileList ||
                !fileList.length
            ) {
                return;
            }

            updateFileInfo(
                fileList[0]
            );

        }

        if (fileInput) {

            fileInput.addEventListener(
                'change',
                function () {

                    handleFiles(
                        this.files
                    );

                }
            );

        }

        if (dropZone) {

            [
                'dragenter',
                'dragover'
            ].forEach(
                function (eventName) {

                    dropZone.addEventListener(
                        eventName,
                        function (event) {

                            event.preventDefault();

                            event.stopPropagation();

                            dropZone.classList.add(
                                'dragging'
                            );

                        }
                    );

                }
            );

            [
                'dragleave',
                'drop'
            ].forEach(
                function (eventName) {

                    dropZone.addEventListener(
                        eventName,
                        function (event) {

                            event.preventDefault();

                            event.stopPropagation();

                            dropZone.classList.remove(
                                'dragging'
                            );

                        }
                    );

                }
            );

            dropZone.addEventListener(
                'drop',
                function (event) {

                    const files =
                        event.dataTransfer.files;

                    if (
                        fileInput &&
                        files &&
                        files.length
                    ) {

                        fileInput.files =
                            files;

                        handleFiles(
                            files
                        );

                    }

                }
            );

        }

        /*
        |--------------------------------------------------------------------------
        | COPY URL
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '[data-copy-url]'
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        'click',
                        async function () {

                            const url =
                                this.dataset.copyUrl
                                ??
                                '';

                            if (!url) {
                                return;
                            }

                            try {

                                await navigator
                                    .clipboard
                                    .writeText(
                                        url
                                    );

                            } catch (error) {

                                const temp =
                                    document.createElement(
                                        'textarea'
                                    );

                                temp.value =
                                    url;

                                document.body
                                    .appendChild(
                                        temp
                                    );

                                temp.select();

                                document.execCommand(
                                    'copy'
                                );

                                temp.remove();

                            }

                            const oldHtml =
                                this.innerHTML;

                            this.innerHTML =
                                '<i class="fa-solid fa-check"></i> Copied';

                            setTimeout(
                                () => {
                                    this.innerHTML =
                                        oldHtml;
                                },
                                1400
                            );

                        }
                    );

                }
            );

        /*
        |--------------------------------------------------------------------------
        | DOUBLE SUBMIT
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                'form'
            )
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
        | FORMAT BYTES
        |--------------------------------------------------------------------------
        */

        function formatBytes(
            bytes
        ) {

            if (
                !bytes ||
                bytes <= 0
            ) {
                return '0 B';
            }

            const units = [
                'B',
                'KB',
                'MB',
                'GB'
            ];

            let index = 0;
            let size  = bytes;

            while (
                size >= 1024 &&
                index <
                    units.length - 1
            ) {

                size /= 1024;
                index++;

            }

            return size.toFixed(
                index === 0
                    ? 0
                    : 1
            ) +
            ' ' +
            units[index];

        }

    }
);

</script>

</body>
</html>