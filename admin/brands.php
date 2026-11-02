<?php
// admin/brands.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Brand / Merek';
$admin    = current_admin();

$uploadDir = __DIR__ . '/../uploads/brands';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

/*
|--------------------------------------------------------------------------
| Helper Lokal
|--------------------------------------------------------------------------
*/

function brand_old_image_path(?string $image): ?string
{
    if (!$image) {
        return null;
    }

    $image = trim($image);

    if ($image === '') {
        return null;
    }

    if (strpos($image, 'uploads/') === 0) {
        $relative = '../' . $image;
    } elseif (strpos($image, '/uploads/') === 0) {
        $relative = '..' . $image;
    } else {
        $relative = '../uploads/brands/' . basename($image);
    }

    $path = __DIR__ . '/' . ltrim($relative, '/');

    return is_file($path) ? $path : null;
}

function brand_slug_exists(string $slug, ?int $excludeId = null): bool
{
    global $pdo;

    $sql = "SELECT id, name FROM brands";
    $params = [];

    if ($excludeId) {
        $sql .= " WHERE id != ?";
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (slugify((string) ($row['name'] ?? '')) === $slug) {
            return true;
        }
    }

    return false;
}

function brand_make_unique_slug(string $name, ?int $excludeId = null): string
{
    $base = slugify($name);

    if ($base === '') {
        $base = 'brand';
    }

    $slug  = $base;
    $count = 1;

    while (brand_slug_exists($slug, $excludeId)) {
        $count++;
        $slug = $base . '-' . $count;
    }

    return $slug;
}

function brand_safe_delete_file(?string $path): void
{
    if ($path && is_file($path)) {
        @unlink($path);
    }
}

/*
|--------------------------------------------------------------------------
| Process Form
|--------------------------------------------------------------------------
*/

if (is_post()) {
    if (!verify_csrf()) {
        set_flash('error', 'Sesi keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.');
        redirect(BASE_URL . '/admin/brands.php');
    }

    $action = post_string('action');

    /*
    |--------------------------------------------------------------------------
    | Simpan / Update Brand
    |--------------------------------------------------------------------------
    */
    if ($action === 'save') {
        $id = get_int('id');

        $name = trim(post_string('name'));
        $slug = trim(post_string('slug'));
        $description = trim(post_string('description'));
        $website = trim(post_string('website'));
        $sortOrder = get_int('sort_order');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            set_flash('error', 'Nama brand wajib diisi.');
            redirect(BASE_URL . '/admin/brands.php' . ($id ? '?edit=' . $id : ''));
        }

        /*
        |--------------------------------------------------------------------------
        | Slug
        |--------------------------------------------------------------------------
        */
        if ($slug === '') {
            $slug = brand_make_unique_slug($name, $id ?: null);
        } else {
            $slug = slugify($slug);

            if ($slug === '') {
                $slug = brand_make_unique_slug($name, $id ?: null);
            } elseif (brand_slug_exists($slug, $id ?: null)) {
                $slug = brand_make_unique_slug($slug, $id ?: null);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Data Lama
        |--------------------------------------------------------------------------
        */
        $oldImage = null;

        if ($id > 0) {
            $stmtOld = $pdo->prepare("
                SELECT *
                FROM brands
                WHERE id = ?
                LIMIT 1
            ");

            $stmtOld->execute([$id]);
            $oldImage = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if (!$oldImage) {
                set_flash('error', 'Data brand tidak ditemukan.');
                redirect(BASE_URL . '/admin/brands.php');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Upload Logo
        |--------------------------------------------------------------------------
        */
        $image = $oldImage['logo'] ?? null;

        if (!empty($_FILES['image']['name'])) {
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

            if (!$upload['success']) {
                set_flash(
                    'error',
                    $upload['message'] ?? 'Gagal mengupload logo brand.'
                );

                redirect(BASE_URL . '/admin/brands.php' . ($id ? '?edit=' . $id : ''));
            }

            $image = $upload['filename'];
        }

        try {
            $pdo->beginTransaction();

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE brands
                    SET
                        name = ?,
                        slug = ?,
                        description = ?,
                        logo = ?,
                        website_url = ?,
                        sort_order = ?,
                        is_active = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $stmt->execute([
                    $name,
                    $slug,
                    $description !== '' ? $description : null,
                    $image,
                    $website !== '' ? $website : null,
                    $sortOrder,
                    $isActive,
                    $id
                ]);

                $pdo->commit();

                /*
                |--------------------------------------------------------------------------
                | Hapus gambar lama setelah update sukses
                |--------------------------------------------------------------------------
                */
                if (
                    $image !== ($oldImage['logo'] ?? null) &&
                    !empty($oldImage['logo'])
                ) {
                    brand_safe_delete_file(
                        brand_old_image_path($oldImage['logo'])
                    );
                }

                set_flash('success', 'Brand berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO brands
                    (
                        name,
                        slug,
                        description,
                        logo,
                        website_url,
                        sort_order,
                        is_active,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");

                $stmt->execute([
                    $name,
                    $slug,
                    $description !== '' ? $description : null,
                    $image,
                    $website !== '' ? $website : null,
                    $sortOrder,
                    $isActive
                ]);

                $pdo->commit();

                set_flash('success', 'Brand berhasil ditambahkan.');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            /*
            |--------------------------------------------------------------------------
            | Hapus file baru jika query gagal
            |--------------------------------------------------------------------------
            */
            if (
                !empty($image) &&
                $image !== ($oldImage['logo'] ?? null)
            ) {
                brand_safe_delete_file(
                    brand_old_image_path($image)
                );
            }

            set_flash(
                'error',
                'Terjadi kesalahan saat menyimpan brand.'
            );
        }

        redirect(BASE_URL . '/admin/brands.php');
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus Brand
    |--------------------------------------------------------------------------
    */
    if ($action === 'delete') {
        $id = get_int('id');

        if ($id <= 0) {
            set_flash('error', 'ID brand tidak valid.');
            redirect(BASE_URL . '/admin/brands.php');
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM brands
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        $brand = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$brand) {
            set_flash('error', 'Brand tidak ditemukan.');
            redirect(BASE_URL . '/admin/brands.php');
        }

        /*
        |--------------------------------------------------------------------------
        | Cek apakah brand masih digunakan produk
        |--------------------------------------------------------------------------
        */
        $productCount = 0;

        /*
         * Database tidak menyimpan brand_id pada products.
         * Hubungan brand -> produk diturunkan dari kategori yang
         * memiliki nama/slug sesuai brand.
         */
        $brandName = trim((string) ($brand['name'] ?? ''));
        if ($brandName !== '') {
            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM products p
                INNER JOIN product_categories c
                    ON c.id = p.category_id
                WHERE p.is_active = 1
                  AND c.is_active = 1
                  AND (
                      LOWER(TRIM(c.name)) = LOWER(TRIM(?))
                      OR LOWER(TRIM(c.name)) LIKE CONCAT('% ', LOWER(TRIM(?)))
                      OR c.slug = ?
                  )
            ");
            $brandSlug = slugify($brandName);
            $countStmt->execute([$brandName, $brandName, $brandSlug]);
            $productCount = (int) $countStmt->fetchColumn();
        }

        if ($productCount > 0) {
            set_flash(
                'error',
                'Brand tidak dapat dihapus karena masih digunakan oleh ' .
                $productCount .
                ' produk.'
            );

            redirect(BASE_URL . '/admin/brands.php');
        }

        try {
            $delete = $pdo->prepare("
                DELETE FROM brands
                WHERE id = ?
            ");

            $delete->execute([$id]);

            if (!empty($brand['logo'])) {
                brand_safe_delete_file(
                    brand_old_image_path($brand['logo'])
                );
            }

            set_flash('success', 'Brand berhasil dihapus.');
        } catch (Throwable $e) {
            set_flash(
                'error',
                'Brand gagal dihapus karena terjadi kesalahan.'
            );
        }

        redirect(BASE_URL . '/admin/brands.php');
    }
}

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/

$search = trim(get_string('q'));
$status = get_string('status');
$editId = get_int('edit');

/*
|--------------------------------------------------------------------------
| Query Brand
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        b.*,
        b.logo AS image,
        b.website_url AS website
    FROM brands b
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            b.name LIKE ?
            OR b.description LIKE ?
        )
    ";

    $like = '%' . $search . '%';

    $params[] = $like;
    $params[] = $like;
}

if ($status === 'active') {
    $sql .= " AND b.is_active = 1 ";
} elseif ($status === 'inactive') {
    $sql .= " AND b.is_active = 0 ";
}

$sql .= "
    ORDER BY
        b.sort_order ASC,
        b.name ASC,
        b.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
 * `brands.slug` dan `products.brand_id` tidak ada pada schema database.
 * Slug diturunkan dari nama, sedangkan jumlah produk diturunkan dari
 * kategori yang namanya sesuai dengan brand.
 */
$categoryCountsStmt = $pdo->query("
    SELECT
        c.slug AS category_slug,
        c.name AS category_name,
        COUNT(p.id) AS product_count
    FROM product_categories c
    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id, c.slug, c.name
");
$categoryCounts = $categoryCountsStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($brands as &$brand) {
    $brand['slug'] = slugify((string) ($brand['name'] ?? ''));
    $brand['product_count'] = 0;

    $brandName = strtolower(trim((string) ($brand['name'] ?? '')));
    foreach ($categoryCounts as $category) {
        $categoryName = strtolower(trim((string) ($category['category_name'] ?? '')));
        $categorySlug = slugify((string) ($category['category_slug'] ?? ''));

        if (
            $categorySlug === $brand['slug']
            || $categoryName === $brandName
            || str_ends_with($categoryName, ' ' . $brandName)
        ) {
            $brand['product_count'] += (int) ($category['product_count'] ?? 0);
        }
    }
}
unset($brand);

/*
|--------------------------------------------------------------------------
| Data Edit
|--------------------------------------------------------------------------
*/

$editBrand = null;

if ($editId > 0) {
    $stmtEdit = $pdo->prepare("
        SELECT
            b.*,
            b.logo AS image,
            b.website_url AS website
        FROM brands b
        WHERE b.id = ?
        LIMIT 1
    ");

    $stmtEdit->execute([$editId]);
    $editBrand = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if (!$editBrand) {
        set_flash('error', 'Data brand tidak ditemukan.');
        redirect(BASE_URL . '/admin/brands.php');
    }

    $editBrand['slug'] = slugify((string) ($editBrand['name'] ?? ''));
}

/*
|--------------------------------------------------------------------------
| Statistik
|--------------------------------------------------------------------------
*/

$totalBrands = (int) $pdo
    ->query("SELECT COUNT(*) FROM brands")
    ->fetchColumn();

$activeBrands = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM brands
        WHERE is_active = 1
    ")
    ->fetchColumn();

$inactiveBrands = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM brands
        WHERE is_active = 0
    ")
    ->fetchColumn();

$totalBrandProducts = 0;

foreach ($brands as $brand) {
    $totalBrandProducts += (int) ($brand['product_count'] ?? 0);
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
            --dark: #181818;
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
        | Sidebar
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
            padding: 12px 12px;
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
        | Main
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
        | Flash
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
        | Header
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

        .btn-success {
            background: #eaf8f1;
            color: var(--success);
        }

        /*
        |--------------------------------------------------------------------------
        | Stats
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
        | Form Card
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

        .col-7 {
            grid-column: span 7;
        }

        .col-6 {
            grid-column: span 6;
        }

        .col-5 {
            grid-column: span 5;
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
            margin-top: 6px;
        }

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
        | Upload Preview
        |--------------------------------------------------------------------------
        */

        .upload-preview {
            width: 100%;
            min-height: 160px;
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
            max-width: 100%;
            max-height: 160px;
            object-fit: contain;
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
        | Filter
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
            grid-template-columns: minmax(0, 1fr) 200px auto auto;
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
        | Table
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
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
            min-width: 880px;
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

        .brand-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 220px;
        }

        .brand-thumb {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            overflow: hidden;
            background: #f7f7f7;
            border: 1px solid #ececec;
            display: grid;
            place-items: center;
            flex: 0 0 56px;
        }

        .brand-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-thumb-placeholder {
            color: #aaa;
            font-size: 17px;
        }

        .brand-name {
            font-weight: 700;
            color: #222;
        }

        .brand-slug {
            color: #999;
            margin-top: 3px;
            font-size: 10px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
        }

        .badge-active {
            background: #eaf8f1;
            color: var(--success);
        }

        .badge-inactive {
            background: #f2f2f2;
            color: #777;
        }

        .badge-products {
            background: #fff0f0;
            color: var(--red);
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
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
        | Overlay
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

        @media (max-width: 1180px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-grid {
                grid-template-columns: 1fr 180px auto;
            }

            .filter-grid .filter-search {
                grid-column: span 1;
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
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .col-12,
            .col-8,
            .col-7,
            .col-6,
            .col-5,
            .col-4 {
                grid-column: span 2;
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
            .col-7,
            .col-6,
            .col-5,
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

            .table-head {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <!-- Sidebar -->
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
                    <h1>Star Parts Motor</h1>
                    <p>Administration Panel</p>
                </div>

            </div>
        </div>

        <nav class="admin-nav">

            <div class="admin-nav-label">
                Main
            </div>

            <a href="<?= e(BASE_URL) ?>/admin/dashboard.php">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>

            <div class="admin-nav-label">
                Management
            </div>

            <a href="<?= e(BASE_URL) ?>/admin/banners.php">
                <i class="fa-solid fa-images"></i>
                <span>Banner</span>
            </a>

            <a href="<?= e(BASE_URL) ?>/admin/products.php">
                <i class="fa-solid fa-box-open"></i>
                <span>Produk</span>
            </a>

            <a href="<?= e(BASE_URL) ?>/admin/categories.php">
                <i class="fa-solid fa-layer-group"></i>
                <span>Kategori</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/brands.php"
                class="active"
            >
                <i class="fa-solid fa-tags"></i>
                <span>Brand</span>
            </a>

            <a href="<?= e(BASE_URL) ?>/admin/posts.php">
                <i class="fa-solid fa-newspaper"></i>
                <span>Berita / Artikel</span>
            </a>

            <div class="admin-nav-label">
                Website
            </div>

            <a href="<?= e(BASE_URL) ?>/admin/pages.php">
                <i class="fa-solid fa-file-lines"></i>
                <span>Halaman</span>
            </a>

            <a href="<?= e(BASE_URL) ?>/admin/media.php">
                <i class="fa-solid fa-photo-film"></i>
                <span>Media</span>
            </a>

            <a href="<?= e(BASE_URL) ?>/admin/settings.php">
                <i class="fa-solid fa-sliders"></i>
                <span>Pengaturan</span>
            </a>

            <div class="admin-nav-label">
                Others
            </div>

            <a href="<?= e(BASE_URL) ?>/admin/messages.php">
                <i class="fa-solid fa-envelope"></i>
                <span>Pesan Kontak</span>
            </a>

            <a href="<?= e(BASE_URL) ?>/admin/locations.php">
                <i class="fa-solid fa-location-dot"></i>
                <span>Lokasi</span>
            </a>

            <a href="<?= e(BASE_URL) ?>/admin/testimonials.php">
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
                        <?= e($admin['name'] ?? $admin['username'] ?? 'Administrator') ?>
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

    <!-- Main -->
    <main class="admin-main">

        <!-- Topbar -->
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
                    <h2>Brand / Merek</h2>
                    <p>Kelola brand produk Star Parts Motor</p>
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
                <div class="flash <?= e($flash['type'] ?? 'success') ?>">
                    <?= e($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">

                <div>
                    <h1>Brand / Merek</h1>

                    <p>
                        Tambahkan dan kelola merek yang tersedia pada katalog produk.
                    </p>
                </div>

                <a
                    href="#brand-form"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-plus"></i>
                    Tambah Brand
                </a>

            </div>

            <!-- Statistics -->
            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-tags"></i>
                    </div>

                    <div>
                        <div class="stat-label">
                            Total Brand
                        </div>

                        <div class="stat-value">
                            <?= number_format($totalBrands, 0, ',', '.') ?>
                        </div>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>
                        <div class="stat-label">
                            Brand Aktif
                        </div>

                        <div class="stat-value">
                            <?= number_format($activeBrands, 0, ',', '.') ?>
                        </div>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-circle-pause"></i>
                    </div>

                    <div>
                        <div class="stat-label">
                            Brand Nonaktif
                        </div>

                        <div class="stat-value">
                            <?= number_format($inactiveBrands, 0, ',', '.') ?>
                        </div>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>

                    <div>
                        <div class="stat-label">
                            Produk Ber-Brand
                        </div>

                        <div class="stat-value">
                            <?= number_format($totalBrandProducts, 0, ',', '.') ?>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Form -->
            <div
                class="form-card"
                id="brand-form"
            >

                <div class="form-card-head">

                    <div>

                        <h3>
                            <?= $editBrand ? 'Edit Brand' : 'Tambah Brand Baru' ?>
                        </h3>

                        <p>
                            <?= $editBrand
                                ? 'Perbarui informasi brand yang dipilih.'
                                : 'Masukkan informasi brand yang akan tampil di website.'
                            ?>
                        </p>

                    </div>

                    <?php if ($editBrand): ?>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/brands.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Batal Edit
                        </a>

                    <?php endif; ?>

                </div>

                <div class="form-card-body">

                    <form
                        action="<?= e(BASE_URL) ?>/admin/brands.php"
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
                            value="<?= (int) ($editBrand['id'] ?? 0) ?>"
                        >

                        <div class="form-grid">

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Nama Brand
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    id="brandName"
                                    class="form-control"
                                    value="<?= e($editBrand['name'] ?? '') ?>"
                                    placeholder="Contoh: FDR"
                                    required
                                >

                            </div>

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Slug
                                </label>

                                <input
                                    type="text"
                                    name="slug"
                                    id="brandSlug"
                                    class="form-control"
                                    value="<?= e($editBrand['slug'] ?? '') ?>"
                                    placeholder="fdr"
                                >

                                <div class="form-help">
                                    Slug dibuat otomatis dari nama brand sesuai struktur database saat ini.
                                </div>

                            </div>

                            <div class="form-group col-8">

                                <label class="form-label">
                                    Website Brand
                                </label>

                                <input
                                    type="url"
                                    name="website"
                                    class="form-control"
                                    value="<?= e($editBrand['website'] ?? '') ?>"
                                    placeholder="https://www.example.com"
                                >

                                <div class="form-help">
                                    Masukkan alamat website resmi brand jika tersedia.
                                </div>

                            </div>

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Urutan Tampil
                                </label>

                                <input
                                    type="number"
                                    name="sort_order"
                                    class="form-control"
                                    value="<?= (int) ($editBrand['sort_order'] ?? 0) ?>"
                                    min="0"
                                    step="1"
                                >

                                <div class="form-help">
                                    Nilai lebih kecil akan tampil lebih dahulu.
                                </div>

                            </div>

                            <div class="form-group col-8">

                                <label class="form-label">
                                    Deskripsi
                                </label>

                                <textarea
                                    name="description"
                                    class="form-control"
                                    placeholder="Deskripsi singkat brand..."
                                ><?= e($editBrand['description'] ?? '') ?></textarea>

                            </div>

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Logo Brand
                                </label>

                                <input
                                    type="file"
                                    name="image"
                                    id="brandImage"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp,.gif,image/*"
                                >

                                <div class="form-help">
                                    JPG, PNG, WEBP atau GIF. Maksimal 5 MB.
                                </div>

                                <div class="upload-preview">

                                    <?php if (!empty($editBrand['image'])): ?>

                                        <img
                                            id="brandPreview"
                                            src="<?= e(media_url($editBrand['image'])) ?>"
                                            alt="<?= e($editBrand['name'] ?? 'Brand') ?>"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="upload-empty"
                                            id="brandPreviewEmpty"
                                        >
                                            <i class="fa-regular fa-image"></i>
                                            <span>
                                                Preview logo akan tampil di sini.
                                            </span>
                                        </div>

                                        <img
                                            id="brandPreview"
                                            src=""
                                            alt=""
                                            style="display:none;"
                                        >

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Status
                                </label>

                                <div class="switch-wrap">

                                    <label class="switch">
                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            <?= !isset($editBrand['is_active']) || (int) $editBrand['is_active'] === 1
                                                ? 'checked'
                                                : ''
                                            ?>
                                        >
                                        <span class="switch-slider"></span>
                                    </label>

                                    <div>
                                        <strong style="font-size:12px;">
                                            Aktifkan brand
                                        </strong>

                                        <div
                                            style="
                                                color:#999;
                                                font-size:10px;
                                                margin-top:3px;
                                            "
                                        >
                                            Brand aktif dapat ditampilkan pada website.
                                        </div>
                                    </div>

                                </div>

                            </div>

                            <div class="col-12">

                                <div class="form-actions">

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="fa-solid fa-floppy-disk"></i>

                                        <?= $editBrand
                                            ? 'Simpan Perubahan'
                                            : 'Simpan Brand'
                                        ?>
                                    </button>

                                    <?php if ($editBrand): ?>

                                        <a
                                            href="<?= e(BASE_URL) ?>/admin/brands.php"
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

            <!-- Filter -->
            <div class="filter-card">

                <form
                    action="<?= e(BASE_URL) ?>/admin/brands.php"
                    method="get"
                >

                    <div class="filter-grid">

                        <div class="filter-group filter-search">

                            <label class="filter-label">
                                Cari Brand
                            </label>

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Cari nama, slug atau deskripsi..."
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
                                    Semua Status
                                </option>

                                <option
                                    value="active"
                                    <?= $status === 'active' ? 'selected' : '' ?>
                                >
                                    Aktif
                                </option>

                                <option
                                    value="inactive"
                                    <?= $status === 'inactive' ? 'selected' : '' ?>
                                >
                                    Nonaktif
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
                            href="<?= e(BASE_URL) ?>/admin/brands.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                            Reset
                        </a>

                    </div>

                </form>

            </div>

            <!-- Table -->
            <div class="table-card">

                <div class="table-head">

                    <div>

                        <h3>
                            Daftar Brand
                        </h3>

                        <div
                            style="
                                color:#999;
                                font-size:10px;
                                margin-top:4px;
                            "
                        >
                            Menampilkan <?= number_format(count($brands), 0, ',', '.') ?> brand
                        </div>

                    </div>

                </div>

                <div class="table-responsive">

                    <?php if (!$brands): ?>

                        <div class="empty-state">

                            <i class="fa-solid fa-tags"></i>

                            <h4>
                                Belum ada brand
                            </h4>

                            <p>
                                Tambahkan brand pertama untuk mulai mengisi katalog.
                            </p>

                        </div>

                    <?php else: ?>

                        <table>

                            <thead>
                                <tr>
                                    <th width="70">
                                        No
                                    </th>

                                    <th>
                                        Brand
                                    </th>

                                    <th>
                                        Deskripsi
                                    </th>

                                    <th width="130">
                                        Produk
                                    </th>

                                    <th width="120">
                                        Status
                                    </th>

                                    <th width="90">
                                        Urutan
                                    </th>

                                    <th width="125">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($brands as $index => $brand): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>

                                        <div class="brand-cell">

                                            <div class="brand-thumb">

                                                <?php if (!empty($brand['logo'])): ?>

                                                    <img
                                                        src="<?= e(media_url($brand['image'])) ?>"
                                                        alt="<?= e($brand['name']) ?>"
                                                        loading="lazy"
                                                        onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
                                                    >

                                                    <div
                                                        class="brand-thumb-placeholder"
                                                        style="display:none;"
                                                    >
                                                        <i class="fa-solid fa-tags"></i>
                                                    </div>

                                                <?php else: ?>

                                                    <div class="brand-thumb-placeholder">
                                                        <i class="fa-solid fa-tags"></i>
                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                            <div>

                                                <div class="brand-name">
                                                    <?= e($brand['name']) ?>
                                                </div>

                                                <div class="brand-slug">
                                                    <?= e($brand['slug']) ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <div
                                            style="
                                                max-width:330px;
                                                color:#666;
                                                line-height:1.6;
                                            "
                                        >
                                            <?= !empty($brand['description'])
                                                ? e(excerpt($brand['description'], 120))
                                                : '<span style="color:#aaa;">Tidak ada deskripsi</span>'
                                            ?>
                                        </div>

                                    </td>

                                    <td>

                                        <span class="badge badge-products">
                                            <i class="fa-solid fa-box"></i>

                                            <?= number_format(
                                                (int) $brand['product_count'],
                                                0,
                                                ',',
                                                '.'
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <?php if ((int) $brand['is_active'] === 1): ?>

                                            <span class="badge badge-active">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Aktif
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-inactive">
                                                <i class="fa-solid fa-circle-pause"></i>
                                                Nonaktif
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?= (int) $brand['sort_order'] ?>
                                    </td>

                                    <td>

                                        <div class="action-group">

                                            <a
                                                href="<?= e(BASE_URL) ?>/admin/brands.php?edit=<?= (int) $brand['id'] ?>#brand-form"
                                                class="action-btn action-edit"
                                                title="Edit"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                            <form
                                                action="<?= e(BASE_URL) ?>/admin/brands.php"
                                                method="post"
                                                style="display:inline;"
                                                onsubmit="return confirm('Yakin ingin menghapus brand ini?');"
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
                                                    value="<?= (int) $brand['id'] ?>"
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
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Sidebar Mobile
    |--------------------------------------------------------------------------
    */

    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.getElementById('adminMobileToggle');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if (!sidebar) return;

        sidebar.classList.add('show');

        if (overlay) {
            overlay.classList.add('show');
        }

        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar) return;

        sidebar.classList.remove('show');

        if (overlay) {
            overlay.classList.remove('show');
        }

        document.body.style.overflow = '';
    }

    if (toggle) {
        toggle.addEventListener('click', function () {

            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }

        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    /*
    |--------------------------------------------------------------------------
    | Slug Generator
    |--------------------------------------------------------------------------
    */

    const nameInput = document.getElementById('brandName');
    const slugInput = document.getElementById('brandSlug');

    let slugTouched = false;

    if (slugInput) {
        slugInput.addEventListener('input', function () {
            slugTouched = this.value.trim() !== '';
        });
    }

    if (nameInput && slugInput) {

        nameInput.addEventListener('input', function () {

            if (slugTouched) {
                return;
            }

            let value = this.value
                .toLowerCase()
                .trim();

            value = value
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');

            slugInput.value = value;

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Preview Logo
    |--------------------------------------------------------------------------
    */

    const imageInput = document.getElementById('brandImage');
    const imagePreview = document.getElementById('brandPreview');
    const emptyPreview = document.getElementById('brandPreviewEmpty');

    if (imageInput && imagePreview) {

        imageInput.addEventListener('change', function () {

            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('File yang dipilih harus berupa gambar.');
                this.value = '';
                return;
            }

            const maxSize = 5 * 1024 * 1024;

            if (file.size > maxSize) {
                alert('Ukuran logo maksimal 5 MB.');
                this.value = '';
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {

                imagePreview.src = event.target.result;
                imagePreview.style.display = 'block';

                if (emptyPreview) {
                    emptyPreview.style.display = 'none';
                }

            };

            reader.readAsDataURL(file);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Prevent Double Submit
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('form').forEach(function (form) {

        form.addEventListener('submit', function (event) {

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';

            const submitButton = form.querySelector(
                'button[type="submit"]'
            );

            if (
                submitButton &&
                submitButton.name !== ''
            ) {
                submitButton.disabled = true;
            }

        });

    });

});
</script>

</body>
</html>