<?php
// admin/testimonials.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Testimoni';
$admin     = current_admin();

$uploadDir = __DIR__ . '/../uploads/testimonials';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

/*
|--------------------------------------------------------------------------
| Helper: deteksi struktur tabel
|--------------------------------------------------------------------------
*/

function testimonial_table_columns(): array
{
    static $columns = null;

    if ($columns !== null) {
        return $columns;
    }

    global $pdo;

    $columns = [];

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM testimonials");

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

function testimonial_has_column(string $column): bool
{
    return in_array(
        $column,
        testimonial_table_columns(),
        true
    );
}

function testimonial_column(
    array $candidates
): ?string {
    $columns = testimonial_table_columns();

    foreach ($candidates as $column) {
        if (in_array($column, $columns, true)) {
            return $column;
        }
    }

    return null;
}

function testimonial_id_column(): ?string
{
    return testimonial_column([
        'id',
        'testimonial_id'
    ]);
}

function testimonial_name_column(): ?string
{
    return testimonial_column([
        'name',
        'customer_name',
        'author_name',
        'full_name',
        'nama'
    ]);
}

function testimonial_role_column(): ?string
{
    return testimonial_column([
        'role',
        'position',
        'job_title',
        'jabatan'
    ]);
}

function testimonial_company_column(): ?string
{
    return testimonial_column([
        'company',
        'company_name',
        'organization',
        'business_name',
        'perusahaan'
    ]);
}

function testimonial_content_column(): ?string
{
    return testimonial_column([
        'content',
        'message',
        'testimonial',
        'text',
        'quote',
        'pesan'
    ]);
}

function testimonial_rating_column(): ?string
{
    return testimonial_column([
        'rating',
        'stars',
        'score'
    ]);
}

function testimonial_image_column(): ?string
{
    return testimonial_column([
        'image',
        'photo',
        'avatar',
        'photo_url'
    ]);
}

function testimonial_featured_column(): ?string
{
    return testimonial_column([
        'is_featured',
        'featured'
    ]);
}

function testimonial_active_column(): ?string
{
    return testimonial_column([
        'is_active',
        'active',
        'status'
    ]);
}

function testimonial_sort_column(): ?string
{
    return testimonial_column([
        'sort_order',
        'display_order',
        'urutan'
    ]);
}

function testimonial_created_column(): ?string
{
    return testimonial_column([
        'created_at',
        'created_on',
        'date_created'
    ]);
}

function testimonial_updated_column(): ?string
{
    return testimonial_column([
        'updated_at',
        'modified_at',
        'updated_on'
    ]);
}

function testimonial_identifier(
    string $column
): string {
    return '`' .
        str_replace(
            '`',
            '``',
            $column
        ) .
        '`';
}

/*
|--------------------------------------------------------------------------
| Deteksi kolom
|--------------------------------------------------------------------------
*/

$testimonialIdColumn =
    testimonial_id_column();

$testimonialNameColumn =
    testimonial_name_column();

$testimonialRoleColumn =
    testimonial_role_column();

$testimonialCompanyColumn =
    testimonial_company_column();

$testimonialContentColumn =
    testimonial_content_column();

$testimonialRatingColumn =
    testimonial_rating_column();

$testimonialImageColumn =
    testimonial_image_column();

$testimonialFeaturedColumn =
    testimonial_featured_column();

$testimonialActiveColumn =
    testimonial_active_column();

$testimonialSortColumn =
    testimonial_sort_column();

$testimonialCreatedColumn =
    testimonial_created_column();

$testimonialUpdatedColumn =
    testimonial_updated_column();

/*
|--------------------------------------------------------------------------
| Validasi minimum
|--------------------------------------------------------------------------
*/

if (!$testimonialIdColumn) {
    die(
        'Tabel testimonials tidak memiliki kolom ID yang dapat digunakan.'
    );
}

if (!$testimonialNameColumn) {
    die(
        'Tabel testimonials tidak memiliki kolom nama.'
    );
}

if (!$testimonialContentColumn) {
    die(
        'Tabel testimonials tidak memiliki kolom isi testimoni.'
    );
}

/*
|--------------------------------------------------------------------------
| Helper File
|--------------------------------------------------------------------------
*/

function testimonial_file_path(
    ?string $file
): ?string {

    if (!$file) {
        return null;
    }

    $file = trim($file);

    if ($file === '') {
        return null;
    }

    if (strpos($file, 'uploads/') === 0) {

        $path =
            __DIR__ .
            '/../' .
            $file;

    } elseif (strpos($file, '/uploads/') === 0) {

        $path =
            __DIR__ .
            '/..' .
            $file;

    } else {

        $path =
            __DIR__ .
            '/../uploads/testimonials/' .
            basename($file);
    }

    return is_file($path)
        ? $path
        : null;
}

function testimonial_delete_file(
    ?string $path
): void {

    if (
        isset($_POST['remove_image']) &&
        $_POST['remove_image'] === '1'
    ) {
        $image = null;
    } elseif (
        $path &&
        is_file($path)
    ) {
        @unlink($path);
    }
}

/*
|--------------------------------------------------------------------------
| POST ACTION
|--------------------------------------------------------------------------
*/

if (is_post()) {

    if (!verify_csrf()) {

        set_flash(
            'error',
            'Permintaan tidak valid. Silakan coba lagi.'
        );

        redirect(
            BASE_URL .
            '/admin/testimonials.php'
        );
    }

    $action = post_string(
        'action'
    );

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    if ($action === 'save') {

        $id =
            get_int('id');

        $name =
            trim(
                post_string('name')
            );

        $role =
            trim(
                post_string('role')
            );

        $company =
            trim(
                post_string('company')
            );

        $content =
            trim(
                post_string('content')
            );

        $ratingInput =
            trim(
                post_string('rating')
            );

        $sortOrder =
            get_int('sort_order');

        $isFeatured =
            isset(
                $_POST['is_featured']
            )
            ? 1
            : 0;

        $isActive =
            isset(
                $_POST['is_active']
            )
            ? 1
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Validasi
        |--------------------------------------------------------------------------
        */

        if ($name === '') {

            set_flash(
                'error',
                'Nama pemberi testimoni wajib diisi.'
            );

            redirect(
                BASE_URL .
                '/admin/testimonials.php' .
                ($id
                    ? '?edit=' . $id
                    : '') .
                '#testimonial-form'
            );
        }

        if ($content === '') {

            set_flash(
                'error',
                'Isi testimoni wajib diisi.'
            );

            redirect(
                BASE_URL .
                '/admin/testimonials.php' .
                ($id
                    ? '?edit=' . $id
                    : '') .
                '#testimonial-form'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Rating
        |--------------------------------------------------------------------------
        */

        $rating = 5;

        if ($ratingInput !== '') {

            if (
                !is_numeric(
                    $ratingInput
                )
            ) {

                set_flash(
                    'error',
                    'Rating harus berupa angka.'
                );

                redirect(
                    BASE_URL .
                    '/admin/testimonials.php' .
                    ($id
                        ? '?edit=' . $id
                        : '') .
                    '#testimonial-form'
                );
            }

            $rating =
                (int) round(
                    (float) $ratingInput
                );
        }

        if ($rating < 1) {
            $rating = 1;
        }

        if ($rating > 5) {
            $rating = 5;
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Data
        |--------------------------------------------------------------------------
        */

        $oldTestimonial = null;

        if ($id > 0) {

            $stmtOld =
                $pdo->prepare("
                    SELECT *
                    FROM testimonials
                    WHERE " .
                    testimonial_identifier(
                        $testimonialIdColumn
                    ) .
                    " = ?
                    LIMIT 1
                ");

            $stmtOld->execute([
                $id
            ]);

            $oldTestimonial =
                $stmtOld->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$oldTestimonial) {

                set_flash(
                    'error',
                    'Data testimoni tidak ditemukan.'
                );

                redirect(
                    BASE_URL .
                    '/admin/testimonials.php'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Image
        |--------------------------------------------------------------------------
        */

        $image =
            $testimonialImageColumn
            ? (
                $oldTestimonial[
                    $testimonialImageColumn
                ]
                ??
                null
            )
            : null;

        if (
            $testimonialImageColumn &&
            isset($_FILES['image']) &&
            !empty($_FILES['image']['name'])
        ) {

            $upload =
                upload_image(
                    $_FILES['image'],
                    'testimonials'
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
                    'Gagal mengupload foto testimoni.'
                );

                redirect(
                    BASE_URL .
                    '/admin/testimonials.php' .
                    ($id
                        ? '?edit=' . $id
                        : '') .
                    '#testimonial-form'
                );
            }

            $image =
                $upload['path'];
        } elseif (($selectedImage = selected_media_path('image')) !== '') {
            $image = $selectedImage;
        }

        /*
        |--------------------------------------------------------------------------
        | Build Data
        |--------------------------------------------------------------------------
        */

        $data = [];

        $data[
            $testimonialNameColumn
        ] = $name;

        if ($testimonialRoleColumn) {

            $data[
                $testimonialRoleColumn
            ] =
                $role !== ''
                    ? $role
                    : null;
        }

        if ($testimonialCompanyColumn) {

            $data[
                $testimonialCompanyColumn
            ] =
                $company !== ''
                    ? $company
                    : null;
        }

        $data[
            $testimonialContentColumn
        ] = $content;

        if ($testimonialRatingColumn) {

            $data[
                $testimonialRatingColumn
            ] = $rating;
        }

        if ($testimonialImageColumn) {

            $data[
                $testimonialImageColumn
            ] = $image;
        }

        if ($testimonialFeaturedColumn) {

            $data[
                $testimonialFeaturedColumn
            ] = $isFeatured;
        }

        if ($testimonialActiveColumn) {

            $data[
                $testimonialActiveColumn
            ] = $isActive;
        }

        if ($testimonialSortColumn) {

            $data[
                $testimonialSortColumn
            ] = $sortOrder;
        }

        /*
        |--------------------------------------------------------------------------
        | Database
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();

            if ($id > 0) {

                $setParts = [];
                $params   = [];

                foreach (
                    $data
                    as $column => $value
                ) {

                    $setParts[] =
                        testimonial_identifier(
                            $column
                        ) .
                        ' = ?';

                    $params[] =
                        $value;
                }

                if ($testimonialUpdatedColumn) {

                    $setParts[] =
                        testimonial_identifier(
                            $testimonialUpdatedColumn
                        ) .
                        ' = NOW()';
                }

                $params[] =
                    $id;

                $sql = "
                    UPDATE testimonials
                    SET " .
                    implode(
                        ', ',
                        $setParts
                    ) .
                    "
                    WHERE " .
                    testimonial_identifier(
                        $testimonialIdColumn
                    ) .
                    " = ?
                ";

                $stmt =
                    $pdo->prepare(
                        $sql
                    );

                $stmt->execute(
                    $params
                );

                $pdo->commit();

                /*
                |--------------------------------------------------------------------------
                | Delete old image
                |--------------------------------------------------------------------------
                */

                if (
                    $testimonialImageColumn &&
                    $image !== (
                        $oldTestimonial[
                            $testimonialImageColumn
                        ] ?? null
                    ) &&
                    !empty(
                        $oldTestimonial[
                            $testimonialImageColumn
                        ]
                    )
                ) {

                    testimonial_delete_file(
                        testimonial_file_path(
                            $oldTestimonial[
                                $testimonialImageColumn
                            ]
                        )
                    );
                }

                set_flash(
                    'success',
                    'Testimoni berhasil diperbarui.'
                );

            } else {

                $columns = [];
                $values  = [];
                $params  = [];

                foreach (
                    $data
                    as $column => $value
                ) {

                    $columns[] =
                        testimonial_identifier(
                            $column
                        );

                    $values[] =
                        '?';

                    $params[] =
                        $value;
                }

                if ($testimonialCreatedColumn) {

                    $columns[] =
                        testimonial_identifier(
                            $testimonialCreatedColumn
                        );

                    $values[] =
                        'NOW()';
                }

                if ($testimonialUpdatedColumn) {

                    $columns[] =
                        testimonial_identifier(
                            $testimonialUpdatedColumn
                        );

                    $values[] =
                        'NOW()';
                }

                $sql = "
                    INSERT INTO testimonials
                    (" .
                    implode(
                        ', ',
                        $columns
                    ) .
                    ")
                    VALUES
                    (" .
                    implode(
                        ', ',
                        $values
                    ) .
                    ")
                ";

                $stmt =
                    $pdo->prepare(
                        $sql
                    );

                $stmt->execute(
                    $params
                );

                $pdo->commit();

                set_flash(
                    'success',
                    'Testimoni berhasil ditambahkan.'
                );
            }

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            /*
            |--------------------------------------------------------------------------
            | Remove new upload if DB failed
            |--------------------------------------------------------------------------
            */

            if (
                $testimonialImageColumn &&
                $image &&
                $image !== (
                    $oldTestimonial[
                        $testimonialImageColumn
                    ] ?? null
                )
            ) {

                testimonial_delete_file(
                    testimonial_file_path(
                        $image
                    )
                );
            }

            set_flash(
                'error',
                'Terjadi kesalahan saat menyimpan testimoni.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/testimonials.php'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id =
            get_int('id');

        if ($id <= 0) {

            set_flash(
                'error',
                'ID testimoni tidak valid.'
            );

            redirect(
                BASE_URL .
                '/admin/testimonials.php'
            );
        }

        $stmt =
            $pdo->prepare("
                SELECT *
                FROM testimonials
                WHERE " .
                testimonial_identifier(
                    $testimonialIdColumn
                ) .
                " = ?
                LIMIT 1
            ");

        $stmt->execute([
            $id
        ]);

        $testimonial =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$testimonial) {

            set_flash(
                'error',
                'Testimoni tidak ditemukan.'
            );

            redirect(
                BASE_URL .
                '/admin/testimonials.php'
            );
        }

        try {

            $stmtDelete =
                $pdo->prepare("
                    DELETE FROM testimonials
                    WHERE " .
                    testimonial_identifier(
                        $testimonialIdColumn
                    ) .
                    " = ?
                ");

            $stmtDelete->execute([
                $id
            ]);

            if (
                $testimonialImageColumn &&
                !empty(
                    $testimonial[
                        $testimonialImageColumn
                    ]
                )
            ) {

                testimonial_delete_file(
                    testimonial_file_path(
                        $testimonial[
                            $testimonialImageColumn
                        ]
                    )
                );
            }

            set_flash(
                'success',
                'Testimoni berhasil dihapus.'
            );

        } catch (Throwable $e) {

            set_flash(
                'error',
                'Testimoni gagal dihapus.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/testimonials.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        get_string('q')
    );

$statusFilter =
    get_string('status');

$featuredFilter =
    get_string('featured');

$editId =
    get_int('edit');

/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$where = [
    '1=1'
];

$params = [];

$searchColumns = [
    $testimonialNameColumn,
    $testimonialRoleColumn,
    $testimonialCompanyColumn,
    $testimonialContentColumn
];

if ($search !== '') {

    $searchConditions = [];

    foreach (
        $searchColumns
        as $column
    ) {

        if (!$column) {
            continue;
        }

        $searchConditions[] =
            testimonial_identifier(
                $column
            ) .
            ' LIKE ?';

        $params[] =
            '%' .
            $search .
            '%';
    }

    if ($searchConditions) {

        $where[] =
            '(' .
            implode(
                ' OR ',
                $searchConditions
            ) .
            ')';
    }
}

if (
    $testimonialActiveColumn &&
    $statusFilter === 'active'
) {

    $where[] =
        testimonial_identifier(
            $testimonialActiveColumn
        ) .
        ' = 1';

} elseif (
    $testimonialActiveColumn &&
    $statusFilter === 'inactive'
) {

    $where[] =
        testimonial_identifier(
            $testimonialActiveColumn
        ) .
        ' = 0';
}

if (
    $testimonialFeaturedColumn &&
    $featuredFilter === 'yes'
) {

    $where[] =
        testimonial_identifier(
            $testimonialFeaturedColumn
        ) .
        ' = 1';

} elseif (
    $testimonialFeaturedColumn &&
    $featuredFilter === 'no'
) {

    $where[] =
        testimonial_identifier(
            $testimonialFeaturedColumn
        ) .
        ' = 0';
}

/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$orderParts = [];

if ($testimonialFeaturedColumn) {

    $orderParts[] =
        testimonial_identifier(
            $testimonialFeaturedColumn
        ) .
        ' DESC';
}

if ($testimonialSortColumn) {

    $orderParts[] =
        testimonial_identifier(
            $testimonialSortColumn
        ) .
        ' ASC';
}

if ($testimonialCreatedColumn) {

    $orderParts[] =
        testimonial_identifier(
            $testimonialCreatedColumn
        ) .
        ' DESC';
}

$orderParts[] =
    testimonial_identifier(
        $testimonialIdColumn
    ) .
    ' DESC';

$sql = "
    SELECT *
    FROM testimonials
    WHERE " .
    implode(
        ' AND ',
        $where
    ) .
    "
    ORDER BY " .
    implode(
        ', ',
        $orderParts
    );

$stmt =
    $pdo->prepare(
        $sql
    );

$stmt->execute(
    $params
);

$testimonials =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
*/

$editTestimonial = null;

if ($editId > 0) {

    $stmtEdit =
        $pdo->prepare("
            SELECT *
            FROM testimonials
            WHERE " .
            testimonial_identifier(
                $testimonialIdColumn
            ) .
            " = ?
            LIMIT 1
        ");

    $stmtEdit->execute([
        $editId
    ]);

    $editTestimonial =
        $stmtEdit->fetch(
            PDO::FETCH_ASSOC
        );

    if (!$editTestimonial) {

        set_flash(
            'error',
            'Data testimoni tidak ditemukan.'
        );

        redirect(
            BASE_URL .
            '/admin/testimonials.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalTestimonials = 0;
$activeTestimonials = 0;
$inactiveTestimonials = 0;
$featuredTestimonials = 0;

try {

    $totalTestimonials =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM testimonials
            ")
            ->fetchColumn();

} catch (Throwable $e) {
    $totalTestimonials = 0;
}

if ($testimonialActiveColumn) {

    try {

        $activeTestimonials =
            (int) $pdo
                ->query("
                    SELECT COUNT(*)
                    FROM testimonials
                    WHERE " .
                    testimonial_identifier(
                        $testimonialActiveColumn
                    ) .
                    " = 1
                ")
                ->fetchColumn();

        $inactiveTestimonials =
            (int) $pdo
                ->query("
                    SELECT COUNT(*)
                    FROM testimonials
                    WHERE " .
                    testimonial_identifier(
                        $testimonialActiveColumn
                    ) .
                    " = 0
                ")
                ->fetchColumn();

    } catch (Throwable $e) {

        $activeTestimonials = 0;
        $inactiveTestimonials = 0;
    }

} else {

    $activeTestimonials =
        $totalTestimonials;
}

if ($testimonialFeaturedColumn) {

    try {

        $featuredTestimonials =
            (int) $pdo
                ->query("
                    SELECT COUNT(*)
                    FROM testimonials
                    WHERE " .
                    testimonial_identifier(
                        $testimonialFeaturedColumn
                    ) .
                    " = 1
                ")
                ->fetchColumn();

    } catch (Throwable $e) {

        $featuredTestimonials = 0;
    }
}

function testimonial_field(
    ?array $row,
    ?string $column,
    string $default = ''
): string {

    if (
        !$row ||
        !$column
    ) {
        return $default;
    }

    return trim(
        (string) (
            $row[$column]
            ??
            $default
        )
    );
}

function testimonial_stars(
    float $rating
): string {

    $rating =
        max(
            1,
            min(
                5,
                $rating
            )
        );

    $html = '';

    for (
        $i = 1;
        $i <= 5;
        $i++
    ) {

        if ($i <= $rating) {

            $html .=
                '<i class="fa-solid fa-star"></i>';

        } elseif (
            $i - $rating < 1
        ) {

            $html .=
                '<i class="fa-solid fa-star-half-stroke"></i>';

        } else {

            $html .=
                '<i class="fa-regular fa-star"></i>';
        }
    }

    return $html;
}

$flash =
    get_flash();

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
            --black: #111;
            --text: #252525;
            --muted: #777;
            --border: #e7e7e7;
            --bg: #f7f7f9;
            --white: #fff;
            --success: #14804a;
            --warning: #b87a00;
            --danger: #c92525;
            --blue: #4667d8;
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

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: #fff;
            background: var(--red);
            box-shadow: 0 8px 20px rgba(217,0,0,.18);
        }

        .btn-light {
            background: #f2f2f4;
            color: #333;
        }

        .btn-success {
            background: #eaf8f1;
            color: var(--success);
        }

        .btn-warning {
            background: #fff6df;
            color: var(--warning);
        }

        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .stats-grid {
            display: grid;
            grid-template-columns:
                repeat(4,minmax(0,1fr));
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
            background: #fff0f0;
            color: var(--red);
            font-size: 18px;
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
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .form-card-head {
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .form-card-head h3 {
            margin: 0;
            font: 700 16px Poppins, sans-serif;
        }

        .form-card-head p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 11px;
        }

        .form-card-body {
            padding: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(12,minmax(0,1fr));
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
            font-size: 12px;
            transition: .2s ease;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
            line-height: 1.75;
        }

        .form-control:focus {
            border-color: var(--red);
            box-shadow:
                0 0 0 3px
                rgba(217,0,0,.08);
        }

        .form-help {
            color: #999;
            font-size: 10px;
            line-height: 1.5;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | RATING
        |--------------------------------------------------------------------------
        */

        .rating-select {
            display: flex;
            align-items: center;
            gap: 5px;
            min-height: 45px;
        }

        .rating-star {
            width: 32px;
            height: 32px;
            border: 0;
            padding: 0;
            background: transparent;
            color: #ccc;
            cursor: pointer;
            font-size: 20px;
            transition: .15s ease;
        }

        .rating-star.active {
            color: #f2a900;
        }

        .rating-value {
            margin-left: 5px;
            color: #777;
            font-size: 11px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        .image-preview {
            min-height: 170px;
            border: 1px dashed #d7d7d7;
            border-radius: 14px;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .image-preview img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            display: block;
        }

        .image-empty {
            text-align: center;
            color: #aaa;
            font-size: 11px;
            padding: 20px;
        }

        .image-empty i {
            display: block;
            margin-bottom: 8px;
            font-size: 30px;
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
            grid-template-columns:
                minmax(0,1fr)
                160px
                160px
                auto
                auto;
            gap: 10px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-label {
            color: #777;
            font-size: 10px;
            font-weight: 700;
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

        .table-head p {
            margin: 5px 0 0;
            color: #999;
            font-size: 10px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 16px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
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

        .testimonial-person {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 250px;
        }

        .testimonial-avatar {
            width: 52px;
            height: 52px;
            flex: 0 0 52px;
            border-radius: 50%;
            overflow: hidden;
            background: #f1f1f1;
            display: grid;
            place-items: center;
            color: #aaa;
            font-size: 18px;
        }

        .testimonial-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .testimonial-name {
            color: #222;
            font-weight: 700;
        }

        .testimonial-role {
            margin-top: 3px;
            color: #999;
            font-size: 10px;
        }

        .testimonial-company {
            margin-top: 2px;
            color: #666;
            font-size: 10px;
        }

        .quote-cell {
            max-width: 380px;
            color: #666;
            line-height: 1.6;
        }

        .stars {
            color: #f2a900;
            white-space: nowrap;
            font-size: 12px;
            letter-spacing: 1px;
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

        .badge-active {
            background: #eaf8f1;
            color: var(--success);
        }

        .badge-inactive {
            background: #f2f2f2;
            color: #777;
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
            color: var(--blue);
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
            display: block;
            color: #ccc;
            font-size: 38px;
            margin-bottom: 12px;
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

            .stats-grid {
                grid-template-columns:
                    repeat(2,minmax(0,1fr));
            }

            .filter-grid {
                grid-template-columns:
                    minmax(0,1fr)
                    150px
                    150px
                    auto;
            }

        }

        @media (max-width: 900px) {

            .admin-sidebar {
                transform:
                    translateX(-100%);
            }

            .admin-sidebar.show {
                transform:
                    translateX(0);
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
                grid-template-columns:
                    1fr 1fr;
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

            .form-card-head {
                padding: 17px 16px;
            }

            .form-card-body {
                padding: 16px;
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
<?php render_admin_sidebar_assets(); ?>
<?php render_admin_sidebar('testimonials.php'); ?>

<div class="admin-layout">

    <!-- SIDEBAR -->

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

            <a href="<?= e(BASE_URL) ?>/admin/careers.php">
                <i class="fa-solid fa-briefcase"></i>
                <span>Lowongan Karir</span>
            </a>
            <a href="<?= e(BASE_URL) ?>/admin/menus.php">
                <i class="fa-solid fa-bars"></i>
                <span>Menu Website</span>
            </a>
            <a href="<?= e(BASE_URL) ?>/admin/security.php">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Security</span>
            </a>
            <div class="admin-nav-label">Others</div>

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
                class="active"
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

    <!-- MAIN -->

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
                        Testimoni
                    </h2>

                    <p>
                        Kelola testimoni pelanggan dan partner
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
                        ??
                        'success'
                    ) ?>"
                >
                    <?= e(
                        $flash['message']
                        ??
                        ''
                    ) ?>
                </div>

            <?php endif; ?>

            <!-- HEADER -->

            <div class="page-header">

                <div>

                    <h1>
                        Testimoni
                    </h1>

                    <p>
                        Kelola testimoni pelanggan, toko, distributor,
                        maupun partner bisnis Star Parts Motor.
                    </p>

                </div>

                <a
                    href="#testimonial-form"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-plus"></i>
                    Tambah Testimoni
                </a>

            </div>

            <!-- STATS -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Testimoni
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $totalTestimonials,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Aktif
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $activeTestimonials,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-circle-pause"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Nonaktif
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $inactiveTestimonials,
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
                                $featuredTestimonials,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

            <!-- FORM -->

            <div
                class="form-card"
                id="testimonial-form"
            >

                <div class="form-card-head">

                    <div>

                        <h3>
                            <?= $editTestimonial
                                ? 'Edit Testimoni'
                                : 'Tambah Testimoni Baru'
                            ?>
                        </h3>

                        <p>
                            <?= $editTestimonial
                                ? 'Perbarui informasi testimoni yang dipilih.'
                                : 'Tambahkan testimoni untuk meningkatkan kepercayaan calon pelanggan.'
                            ?>
                        </p>

                    </div>

                    <?php if ($editTestimonial): ?>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/testimonials.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Batal Edit
                        </a>

                    <?php endif; ?>

                </div>

                <div class="form-card-body">

                    <form
                        method="post"
                        action="<?= e(BASE_URL) ?>/admin/testimonials.php"
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
                                $editTestimonial[
                                    $testimonialIdColumn
                                ]
                                ??
                                0
                            ) ?>"
                        >

                        <div class="form-grid">

                            <!-- NAME -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Nama
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    value="<?= e(
                                        testimonial_field(
                                            $editTestimonial,
                                            $testimonialNameColumn
                                        )
                                    ) ?>"
                                    placeholder="Contoh: Budi Santoso"
                                    required
                                >

                            </div>

                            <!-- ROLE -->

                            <?php if ($testimonialRoleColumn): ?>

                                <div class="form-group col-6">

                                    <label class="form-label">
                                        Jabatan / Posisi
                                    </label>

                                    <input
                                        type="text"
                                        name="role"
                                        class="form-control"
                                        value="<?= e(
                                            testimonial_field(
                                                $editTestimonial,
                                                $testimonialRoleColumn
                                            )
                                        ) ?>"
                                        placeholder="Pemilik Toko / Purchasing / Manager"
                                    >

                                </div>

                            <?php endif; ?>

                            <!-- COMPANY -->

                            <?php if ($testimonialCompanyColumn): ?>

                                <div class="form-group col-6">

                                    <label class="form-label">
                                        Perusahaan / Toko
                                    </label>

                                    <input
                                        type="text"
                                        name="company"
                                        class="form-control"
                                        value="<?= e(
                                            testimonial_field(
                                                $editTestimonial,
                                                $testimonialCompanyColumn
                                            )
                                        ) ?>"
                                        placeholder="Nama perusahaan atau toko"
                                    >

                                </div>

                            <?php endif; ?>

                            <!-- RATING -->

                            <?php if ($testimonialRatingColumn): ?>

                                <?php

                                $editRating =
                                    $editTestimonial[
                                        $testimonialRatingColumn
                                    ]
                                    ??
                                    5;

                                $editRating =
                                    max(
                                        1,
                                        min(
                                            5,
                                            (float) $editRating
                                        )
                                    );

                                ?>

                                <div class="form-group col-6">

                                    <label class="form-label">
                                        Rating
                                    </label>

                                    <input
                                        type="hidden"
                                        name="rating"
                                        id="ratingInput"
                                        value="<?= e(
                                            $editRating
                                        ) ?>"
                                    >

                                    <div class="rating-select">

                                        <?php for (
                                            $star = 1;
                                            $star <= 5;
                                            $star++
                                        ): ?>

                                            <button
                                                type="button"
                                                class="rating-star <?= $star <= $editRating
                                                    ? 'active'
                                                    : ''
                                                ?>"
                                                data-rating="<?= $star ?>"
                                                aria-label="Rating <?= $star ?>"
                                            >
                                                <i class="fa-solid fa-star"></i>
                                            </button>

                                        <?php endfor; ?>

                                        <span
                                            class="rating-value"
                                            id="ratingValue"
                                        >
                                            <?= e(
                                                rtrim(
                                                    rtrim(
                                                        number_format(
                                                            $editRating,
                                                            1,
                                                            '.',
                                                            ''
                                                        ),
                                                        '0'
                                                    ),
                                                    '.'
                                                )
                                            ) ?>
                                            / 5
                                        </span>

                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- CONTENT -->

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Isi Testimoni
                                    <span>*</span>
                                </label>

                                <textarea
                                    name="content"
                                    class="form-control"
                                    placeholder="Tulis testimoni pelanggan atau partner..."
                                    required
                                ><?= e(
                                    testimonial_field(
                                        $editTestimonial,
                                        $testimonialContentColumn
                                    )
                                ) ?></textarea>

                            </div>

                            <!-- IMAGE -->

                            <?php if ($testimonialImageColumn): ?>

                                <div class="form-group col-6">

                                    <label class="form-label">
                                        Foto
                                    </label>

                                    <input
                                        type="file"
                                        name="image"
                                        id="testimonialImage"
                                        class="form-control"
                                        accept=".jpg,.jpeg,.png,.webp,.gif,image/*"
                                    >

                                    <div class="form-help">
                                        JPG, PNG, WEBP atau GIF. Maksimal 5 MB.
                                    </div>

                                    <div class="image-preview">

                                        <?php
                                        $editImage =
                                            testimonial_field(
                                                $editTestimonial,
                                                $testimonialImageColumn
                                            );
                                        ?>

                                        <?php if ($editImage): ?>

                                            <img
                                                id="testimonialPreview"
                                                src="<?= e(
                                                    media_url(
                                                        $editImage
                                                    )
                                                ) ?>"
                                                alt="Foto testimoni"
                                            >

                                        <?php else: ?>

                                            <div
                                                class="image-empty"
                                                id="testimonialEmpty"
                                            >
                                                <i class="fa-regular fa-image"></i>
                                                Belum ada foto.
                                            </div>

                                            <img
                                                id="testimonialPreview"
                                                src=""
                                                alt=""
                                                style="display:none;"
                                            >

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- SORT -->

                            <?php if ($testimonialSortColumn): ?>

                                <div class="form-group col-6">

                                    <label class="form-label">
                                        Urutan Tampil
                                    </label>

                                    <input
                                        type="number"
                                        name="sort_order"
                                        class="form-control"
                                        min="0"
                                        step="1"
                                        value="<?= (int) (
                                            $editTestimonial[
                                                $testimonialSortColumn
                                            ]
                                            ??
                                            0
                                        ) ?>"
                                    >

                                    <div class="form-help">
                                        Nilai lebih kecil akan tampil lebih dahulu.
                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- FEATURED -->

                            <?php if ($testimonialFeaturedColumn): ?>

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
                                                    !$editTestimonial ||
                                                    (int) (
                                                        $editTestimonial[
                                                            $testimonialFeaturedColumn
                                                        ]
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
                                                Jadikan unggulan
                                            </strong>

                                            <div
                                                style="
                                                    color:#999;
                                                    font-size:10px;
                                                    margin-top:3px;
                                                "
                                            >
                                                Testimoni unggulan dapat diprioritaskan di website.
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- ACTIVE -->

                            <?php if ($testimonialActiveColumn): ?>

                                <div class="form-group col-6">

                                    <label class="form-label">
                                        Status
                                    </label>

                                    <div class="switch-wrap">

                                        <label class="switch">

                                            <input
                                                type="checkbox"
                                                name="is_active"
                                                value="1"
                                                <?= (
                                                    !$editTestimonial ||
                                                    (int) (
                                                        $editTestimonial[
                                                            $testimonialActiveColumn
                                                        ]
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
                                                Aktifkan testimoni
                                            </strong>

                                            <div
                                                style="
                                                    color:#999;
                                                    font-size:10px;
                                                    margin-top:3px;
                                                "
                                            >
                                                Testimoni aktif dapat ditampilkan pada website.
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- ACTION -->

                            <div class="col-12">

                                <div class="form-actions">

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="fa-solid fa-floppy-disk"></i>

                                        <?= $editTestimonial
                                            ? 'Simpan Perubahan'
                                            : 'Simpan Testimoni'
                                        ?>
                                    </button>

                                    <?php if ($editTestimonial): ?>

                                        <a
                                            href="<?= e(BASE_URL) ?>/admin/testimonials.php"
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

            <!-- FILTER -->

            <div class="filter-card">

                <form
                    method="get"
                    action="<?= e(BASE_URL) ?>/admin/testimonials.php"
                >

                    <div class="filter-grid">

                        <div class="filter-group">

                            <label class="filter-label">
                                Cari
                            </label>

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                value="<?= e(
                                    $search
                                ) ?>"
                                placeholder="Nama, perusahaan atau isi testimoni..."
                            >

                        </div>

                        <?php if ($testimonialActiveColumn): ?>

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
                                        value="active"
                                        <?= $statusFilter === 'active'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Aktif
                                    </option>

                                    <option
                                        value="inactive"
                                        <?= $statusFilter === 'inactive'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Nonaktif
                                    </option>

                                </select>

                            </div>

                        <?php endif; ?>

                        <?php if ($testimonialFeaturedColumn): ?>

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

                        <?php endif; ?>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-filter"></i>
                            Filter
                        </button>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/testimonials.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                            Reset
                        </a>

                    </div>

                </form>

            </div>

            <!-- TABLE -->

            <div class="table-card">

                <div class="table-head">

                    <h3>
                        Daftar Testimoni
                    </h3>

                    <p>
                        Menampilkan
                        <?= number_format(
                            count($testimonials),
                            0,
                            ',',
                            '.'
                        ) ?>
                        testimoni
                    </p>

                </div>

                <div class="table-responsive">

                    <?php if (!$testimonials): ?>

                        <div class="empty-state">

                            <i class="fa-solid fa-comment-dots"></i>

                            <h4>
                                Belum ada testimoni
                            </h4>

                            <p>
                                Tambahkan testimoni pelanggan atau partner.
                            </p>

                        </div>

                    <?php else: ?>

                        <table>

                            <thead>

                                <tr>

                                    <th width="60">
                                        No
                                    </th>

                                    <th>
                                        Pelanggan / Partner
                                    </th>

                                    <th>
                                        Testimoni
                                    </th>

                                    <?php if (
                                        $testimonialRatingColumn
                                    ): ?>

                                        <th width="120">
                                            Rating
                                        </th>

                                    <?php endif; ?>

                                    <?php if (
                                        $testimonialFeaturedColumn
                                    ): ?>

                                        <th width="115">
                                            Featured
                                        </th>

                                    <?php endif; ?>

                                    <?php if (
                                        $testimonialActiveColumn
                                    ): ?>

                                        <th width="105">
                                            Status
                                        </th>

                                    <?php endif; ?>

                                    <th width="90">
                                        Urutan
                                    </th>

                                    <th width="110">
                                        Aksi
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach (
                                $testimonials
                                as $index => $testimonial
                            ): ?>

                                <?php

                                $testimonialId =
                                    (int) (
                                        $testimonial[
                                            $testimonialIdColumn
                                        ]
                                        ??
                                        0
                                    );

                                $personName =
                                    testimonial_field(
                                        $testimonial,
                                        $testimonialNameColumn,
                                        'Pelanggan'
                                    );

                                $role =
                                    testimonial_field(
                                        $testimonial,
                                        $testimonialRoleColumn
                                    );

                                $company =
                                    testimonial_field(
                                        $testimonial,
                                        $testimonialCompanyColumn
                                    );

                                $content =
                                    testimonial_field(
                                        $testimonial,
                                        $testimonialContentColumn
                                    );

                                $rating =
                                    $testimonialRatingColumn
                                        ? (float) (
                                            $testimonial[
                                                $testimonialRatingColumn
                                            ]
                                            ??
                                            5
                                        )
                                        : 5;

                                $image =
                                    testimonial_field(
                                        $testimonial,
                                        $testimonialImageColumn
                                    );

                                $isFeatured =
                                    $testimonialFeaturedColumn
                                        ? (
                                            (int) (
                                                $testimonial[
                                                    $testimonialFeaturedColumn
                                                ]
                                                ??
                                                0
                                            ) === 1
                                        )
                                        : false;

                                $isActive =
                                    $testimonialActiveColumn
                                        ? (
                                            (int) (
                                                $testimonial[
                                                    $testimonialActiveColumn
                                                ]
                                                ??
                                                0
                                            ) === 1
                                        )
                                        : true;

                                $sortOrder =
                                    $testimonialSortColumn
                                        ? (int) (
                                            $testimonial[
                                                $testimonialSortColumn
                                            ]
                                            ??
                                            0
                                        )
                                        : 0;

                                ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>

                                        <div class="testimonial-person">

                                            <div class="testimonial-avatar">

                                                <?php if ($image): ?>

                                                    <img
                                                        src="<?= e(
                                                            media_url(
                                                                $image
                                                            )
                                                        ) ?>"
                                                        alt="<?= e(
                                                            $personName
                                                        ) ?>"
                                                        loading="lazy"
                                                        onerror="
                                                            this.style.display='none';
                                                            this.nextElementSibling.style.display='block';
                                                        "
                                                    >

                                                    <i
                                                        class="fa-solid fa-user"
                                                        style="display:none;"
                                                    ></i>

                                                <?php else: ?>

                                                    <i class="fa-solid fa-user"></i>

                                                <?php endif; ?>

                                            </div>

                                            <div>

                                                <div class="testimonial-name">
                                                    <?= e(
                                                        $personName
                                                    ) ?>
                                                </div>

                                                <?php if ($role): ?>

                                                    <div class="testimonial-role">
                                                        <?= e(
                                                            $role
                                                        ) ?>
                                                    </div>

                                                <?php endif; ?>

                                                <?php if ($company): ?>

                                                    <div class="testimonial-company">
                                                        <?= e(
                                                            $company
                                                        ) ?>
                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <div class="quote-cell">

                                            "<?= e(
                                                excerpt(
                                                    $content,
                                                    220
                                                )
                                            ) ?>"

                                        </div>

                                    </td>

                                    <?php if (
                                        $testimonialRatingColumn
                                    ): ?>

                                        <td>

                                            <div class="stars">
                                                <?= testimonial_stars(
                                                    $rating
                                                ) ?>
                                            </div>

                                            <div
                                                style="
                                                    margin-top:5px;
                                                    color:#999;
                                                    font-size:9px;
                                                "
                                            >
                                                <?= e(
                                                    number_format(
                                                        $rating,
                                                        1,
                                                        ',',
                                                        '.'
                                                    )
                                                ) ?>
                                                / 5
                                            </div>

                                        </td>

                                    <?php endif; ?>

                                    <?php if (
                                        $testimonialFeaturedColumn
                                    ): ?>

                                        <td>

                                            <?php if (
                                                $isFeatured
                                            ): ?>

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

                                    <?php endif; ?>

                                    <?php if (
                                        $testimonialActiveColumn
                                    ): ?>

                                        <td>

                                            <?php if (
                                                $isActive
                                            ): ?>

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

                                    <?php endif; ?>

                                    <td>
                                        <?= $sortOrder ?>
                                    </td>

                                    <td>

                                        <div class="action-group">

                                            <a
                                                href="<?= e(BASE_URL) ?>/admin/testimonials.php?edit=<?= $testimonialId ?>#testimonial-form"
                                                class="action-btn action-edit"
                                                title="Edit"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                            <form
                                                method="post"
                                                action="<?= e(BASE_URL) ?>/admin/testimonials.php"
                                                style="display:inline;"
                                                onsubmit="
                                                    return confirm(
                                                        'Yakin ingin menghapus testimoni ini?'
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
                                                    value="<?= $testimonialId ?>"
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
        | RATING
        |--------------------------------------------------------------------------
        */

        const ratingInput =
            document.getElementById(
                'ratingInput'
            );

        const ratingValue =
            document.getElementById(
                'ratingValue'
            );

        const ratingStars =
            document.querySelectorAll(
                '.rating-star'
            );

        function updateRating(
            rating
        ) {

            rating =
                parseFloat(
                    rating
                );

            if (
                Number.isNaN(
                    rating
                )
            ) {
                rating = 5;
            }

            if (rating < 1) {
                rating = 1;
            }

            if (rating > 5) {
                rating = 5;
            }

            if (ratingInput) {

                ratingInput.value =
                    rating;
            }

            ratingStars.forEach(
                function (star) {

                    const starRating =
                        parseInt(
                            star.dataset.rating,
                            10
                        );

                    if (
                        starRating <=
                        rating
                    ) {

                        star.classList.add(
                            'active'
                        );

                    } else {

                        star.classList.remove(
                            'active'
                        );

                    }

                }
            );

            if (ratingValue) {

                ratingValue.textContent =
                    rating +
                    ' / 5';

            }

        }

        ratingStars.forEach(
            function (star) {

                star.addEventListener(
                    'click',
                    function () {

                        updateRating(
                            this.dataset.rating
                        );

                    }
                );

            }
        );

        /*
        |--------------------------------------------------------------------------
        | IMAGE PREVIEW
        |--------------------------------------------------------------------------
        */

        const imageInput =
            document.getElementById(
                'testimonialImage'
            );

        const preview =
            document.getElementById(
                'testimonialPreview'
            );

        const emptyPreview =
            document.getElementById(
                'testimonialEmpty'
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
                            'Ukuran foto maksimal 5 MB.'
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

    }
);

</script>

</body>
</html>