<?php
// =========================================================
// admin/categories.php
// Management Kategori Produk
// PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PROTECT ADMIN
|--------------------------------------------------------------------------
*/

require_admin();


/*
|--------------------------------------------------------------------------
| BASIC DATA
|--------------------------------------------------------------------------
*/

$admin = current_admin();

$adminName =
    $admin['full_name']
    ??
    $_SESSION['admin_name']
    ??
    'Administrator';

$siteName = trim(
    (string) get_setting(
        'site_name',
        'PT Star Parts Motor'
    )
);

$siteLogo = trim(
    (string) get_setting(
        'site_logo',
        ''
    )
);

$siteLogoUrl =
    $siteLogo !== ''
        ? media_url($siteLogo)
        : '';

$listUrl =
    base_url(
        'admin/categories.php'
    );

$uploadFolder =
    'categories';


/*
|--------------------------------------------------------------------------
| PAGE STATE
|--------------------------------------------------------------------------
*/

$action =
    strtolower(
        get_string(
            'action',
            'list'
        )
    );

$editId =
    get_int(
        'id',
        0
    );

$error = '';

$flash = null;


/*
|--------------------------------------------------------------------------
| GET CATEGORY
|--------------------------------------------------------------------------
*/

function admin_get_category(
    PDO $pdo,
    int $id
): ?array {

    if (
        $id <= 0
    ) {
        return null;
    }


    $stmt =
        $pdo->prepare(
            "SELECT *
             FROM product_categories
             WHERE id = ?
             LIMIT 1"
        );


    $stmt->execute([
        $id
    ]);


    $category =
        $stmt->fetch();


    return $category ?: null;
}


/*
|--------------------------------------------------------------------------
| DELETE LOCAL FILE
|--------------------------------------------------------------------------
*/

function admin_category_delete_file(
    ?string $relativePath
): void {

    if (
        empty($relativePath)
    ) {
        return;
    }


    $relativePath =
        ltrim(
            trim($relativePath),
            '/\\'
        );


    if (
        !str_starts_with(
            $relativePath,
            'uploads/'
        )
    ) {
        return;
    }


    $projectRoot =
        dirname(__DIR__);


    $fullPath =
        $projectRoot
        . DIRECTORY_SEPARATOR
        . str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $relativePath
        );


    $uploadsRoot =
        realpath(
            $projectRoot
            . DIRECTORY_SEPARATOR
            . 'uploads'
        );


    $fileRoot =
        is_file($fullPath)
            ? realpath($fullPath)
            : false;


    if (
        $uploadsRoot === false
        ||
        $fileRoot === false
    ) {
        return;
    }


    if (
        $fileRoot !== $uploadsRoot
        &&
        !str_starts_with(
            $fileRoot,
            $uploadsRoot
            . DIRECTORY_SEPARATOR
        )
    ) {
        return;
    }


    @unlink(
        $fileRoot
    );
}


/*
|--------------------------------------------------------------------------
| SAVE CATEGORY
|--------------------------------------------------------------------------
*/

if (
    is_post()
) {

    $formAction =
        strtolower(
            post_string(
                'form_action'
            )
        );


    /*
     * CSRF
     */

    if (
        !verify_csrf()
    ) {

        $error =
            'Permintaan tidak valid. Silakan refresh halaman dan coba kembali.';

    } else {


        /*
         * =====================================================
         * SAVE
         * =====================================================
         */

        if (
            $formAction === 'save'
        ) {

            $id =
                (int)
                (
                    $_POST['id']
                    ??
                    0
                );


            $name =
                post_string(
                    'name'
                );


            $slug =
                post_string(
                    'slug'
                );


            $description =
                isset(
                    $_POST['description']
                )
                    ? trim(
                        (string)
                        $_POST['description']
                    )
                    : '';


            $sortOrder =
                isset(
                    $_POST['sort_order']
                )
                    ? (int)
                        $_POST['sort_order']
                    : 0;


            $isActive =
                isset(
                    $_POST['is_active']
                )
                    ? 1
                    : 0;


            /*
             * Existing category.
             */

            $existingCategory =
                $id > 0
                    ? admin_get_category(
                        $pdo,
                        $id
                    )
                    : null;


            if (
                $id > 0
                &&
                !$existingCategory
            ) {

                $error =
                    'Kategori yang ingin diedit tidak ditemukan.';

            } elseif (
                $name === ''
            ) {

                $error =
                    'Nama kategori wajib diisi.';

            }


            /*
             * =================================================
             * SLUG
             * =================================================
             */

            if (
                $error === ''
            ) {

                if (
                    $slug === ''
                ) {

                    $slug =
                        slugify(
                            $name
                        );

                } else {

                    $slug =
                        slugify(
                            $slug
                        );
                }


                $baseSlug =
                    $slug;

                $counter =
                    1;


                while (
                    true
                ) {

                    if (
                        $id > 0
                    ) {

                        $slugStmt =
                            $pdo->prepare(
                                "SELECT id
                                 FROM product_categories
                                 WHERE slug = ?
                                 AND id <> ?
                                 LIMIT 1"
                            );


                        $slugStmt->execute([
                            $slug,
                            $id
                        ]);

                    } else {

                        $slugStmt =
                            $pdo->prepare(
                                "SELECT id
                                 FROM product_categories
                                 WHERE slug = ?
                                 LIMIT 1"
                            );


                        $slugStmt->execute([
                            $slug
                        ]);
                    }


                    if (
                        !$slugStmt->fetch()
                    ) {
                        break;
                    }


                    $counter++;


                    $slug =
                        $baseSlug
                        . '-'
                        . $counter;
                }
            }


            /*
             * =================================================
             * IMAGE
             * =================================================
             */

            $imagePath =
                $existingCategory['image']
                ??
                '';


            $newImageUploaded =
                false;


            /*
             * Apakah user meminta hapus image
             * tanpa upload image baru?
             */

            $removeImage =
                isset(
                    $_POST['remove_image']
                )
                &&
                $_POST['remove_image'] === '1';


            /*
             * =================================================
             * UPLOAD IMAGE
             * =================================================
             */

            if (
                $error === ''
                &&
                isset(
                    $_FILES['image']
                )
                &&
                (
                    $_FILES['image']['error']
                    ??
                    UPLOAD_ERR_NO_FILE
                )
                !==
                UPLOAD_ERR_NO_FILE
            ) {

                $upload =
                    upload_image(
                        $_FILES['image'],
                        $uploadFolder
                    );


                if (
                    !$upload['success']
                ) {

                    $error =
                        (string)
                        $upload['message'];

                } else {

                    $imagePath =
                        (string)
                        $upload['path'];

                    $newImageUploaded =
                        true;

                    /*
                     * Jika ada gambar lama,
                     * gambar baru akan menggantikannya.
                     */
                }
            }


            /*
             * =================================================
             * REMOVE IMAGE
             * =================================================
             */

            if (
                $error === ''
                &&
                !$newImageUploaded
                &&
                $removeImage
            ) {

                $imagePath =
                    '';
            }


            /*
             * =================================================
             * SAVE DATABASE
             * =================================================
             */

            if (
                $error === ''
            ) {

                try {

                    if (
                        $id > 0
                    ) {

                        $stmt =
                            $pdo->prepare(
                                "UPDATE product_categories
                                 SET
                                    name = ?,
                                    slug = ?,
                                    description = ?,
                                    image = ?,
                                    sort_order = ?,
                                    is_active = ?
                                 WHERE id = ?"
                            );


                        $stmt->execute([
                            $name,
                            $slug,
                            $description !== ''
                                ? $description
                                : null,
                            $imagePath !== ''
                                ? $imagePath
                                : null,
                            $sortOrder,
                            $isActive,
                            $id
                        ]);


                        /*
                         * Hapus gambar lama setelah database
                         * berhasil diperbarui.
                         */

                        if (
                            (
                                $newImageUploaded
                                ||
                                $removeImage
                            )
                            &&
                            !empty(
                                $existingCategory['image']
                            )
                            &&
                            $existingCategory['image']
                            !==
                            $imagePath
                        ) {

                            admin_category_delete_file(
                                $existingCategory['image']
                            );
                        }


                        set_flash(
                            'success',
                            'Kategori berhasil diperbarui.'
                        );

                    } else {

                        $stmt =
                            $pdo->prepare(
                                "INSERT INTO product_categories
                                (
                                    name,
                                    slug,
                                    description,
                                    image,
                                    sort_order,
                                    is_active
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?
                                )"
                            );


                        $stmt->execute([
                            $name,
                            $slug,
                            $description !== ''
                                ? $description
                                : null,
                            $imagePath !== ''
                                ? $imagePath
                                : null,
                            $sortOrder,
                            $isActive
                        ]);


                        set_flash(
                            'success',
                            'Kategori berhasil ditambahkan.'
                        );
                    }


                    redirect(
                        $listUrl
                    );


                } catch (
                    Throwable $e
                ) {

                    /*
                     * DB gagal:
                     * hapus image baru.
                     */

                    if (
                        $newImageUploaded
                    ) {

                        admin_category_delete_file(
                            $imagePath
                        );
                    }


                    $error =
                        'Kategori gagal disimpan. Silakan coba kembali.';
                }
            }


            /*
             * Kembali ke form jika error.
             */

            if (
                $error !== ''
            ) {

                $action =
                    $id > 0
                        ? 'edit'
                        : 'add';

                $editId =
                    $id;
            }
        }


        /*
         * =====================================================
         * DELETE
         * =====================================================
         */

        elseif (
            $formAction === 'delete'
        ) {

            $id =
                (int)
                (
                    $_POST['id']
                    ??
                    0
                );


            if (
                $id <= 0
            ) {

                $error =
                    'ID kategori tidak valid.';

            } else {

                try {

                    $category =
                        admin_get_category(
                            $pdo,
                            $id
                        );


                    if (
                        !$category
                    ) {

                        $error =
                            'Kategori tidak ditemukan.';

                    } else {

                        /*
                         * Cek apakah masih digunakan
                         * oleh produk.
                         */

                        $usageStmt =
                            $pdo->prepare(
                                "SELECT COUNT(*)
                                 FROM products
                                 WHERE category_id = ?"
                            );


                        $usageStmt->execute([
                            $id
                        ]);


                        $usageCount =
                            (int)
                            $usageStmt->fetchColumn();


                        if (
                            $usageCount > 0
                        ) {

                            $error =
                                'Kategori tidak dapat dihapus karena masih digunakan oleh '
                                .
                                $usageCount
                                .
                                ' produk. Nonaktifkan kategori jika sudah tidak digunakan.';

                        } else {

                            $stmt =
                                $pdo->prepare(
                                    "DELETE FROM product_categories
                                     WHERE id = ?"
                                );


                            $stmt->execute([
                                $id
                            ]);


                            admin_category_delete_file(
                                $category['image']
                                ??
                                ''
                            );


                            set_flash(
                                'success',
                                'Kategori berhasil dihapus.'
                            );


                            redirect(
                                $listUrl
                            );
                        }
                    }


                } catch (
                    Throwable $e
                ) {

                    $error =
                        'Kategori gagal dihapus.';
                }
            }
        }


        /*
         * =====================================================
         * TOGGLE
         * =====================================================
         */

        elseif (
            $formAction === 'toggle'
        ) {

            $id =
                (int)
                (
                    $_POST['id']
                    ??
                    0
                );


            if (
                $id <= 0
            ) {

                $error =
                    'ID kategori tidak valid.';

            } else {

                try {

                    $category =
                        admin_get_category(
                            $pdo,
                            $id
                        );


                    if (
                        !$category
                    ) {

                        $error =
                            'Kategori tidak ditemukan.';

                    } else {

                        $current =
                            (int)
                            (
                                $category['is_active']
                                ??
                                0
                            );


                        $newStatus =
                            $current === 1
                                ? 0
                                : 1;


                        $stmt =
                            $pdo->prepare(
                                "UPDATE product_categories
                                 SET is_active = ?
                                 WHERE id = ?"
                            );


                        $stmt->execute([
                            $newStatus,
                            $id
                        ]);


                        set_flash(
                            'success',
                            $newStatus === 1
                                ? 'Kategori diaktifkan.'
                                : 'Kategori dinonaktifkan.'
                        );


                        redirect(
                            $listUrl
                        );
                    }


                } catch (
                    Throwable $e
                ) {

                    $error =
                        'Status kategori gagal diubah.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

$flash =
    get_flash();


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    get_string(
        'search'
    );

$statusFilter =
    get_string(
        'status',
        'all'
    );


/*
|--------------------------------------------------------------------------
| CATEGORY LIST
|--------------------------------------------------------------------------
*/

$categories = [];

try {

    $sql = "
        SELECT
            c.*,
            COUNT(p.id) AS product_count
        FROM product_categories c
        LEFT JOIN products p
            ON p.category_id = c.id
        WHERE 1 = 1
    ";

    $params = [];


    if (
        $search !== ''
    ) {

        $sql .= "
            AND (
                c.name LIKE ?
                OR c.slug LIKE ?
                OR c.description LIKE ?
            )
        ";

        $like =
            '%' .
            $search .
            '%';


        $params[] =
            $like;

        $params[] =
            $like;

        $params[] =
            $like;
    }


    if (
        $statusFilter === 'active'
    ) {

        $sql .= "
            AND c.is_active = 1
        ";

    } elseif (
        $statusFilter === 'inactive'
    ) {

        $sql .= "
            AND c.is_active = 0
        ";
    }


    $sql .= "
        GROUP BY
            c.id,
            c.name,
            c.slug,
            c.description,
            c.image,
            c.sort_order,
            c.is_active,
            c.created_at,
            c.updated_at
        ORDER BY
            c.sort_order ASC,
            c.id DESC
    ";


    $stmt =
        $pdo->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $categories =
        $stmt->fetchAll();

} catch (
    Throwable $e
) {

    $categories = [];
}


/*
|--------------------------------------------------------------------------
| EDIT CATEGORY
|--------------------------------------------------------------------------
*/

$editCategory =
    null;


if (
    $action === 'edit'
    &&
    $editId > 0
) {

    $editCategory =
        admin_get_category(
            $pdo,
            $editId
        );


    if (
        !$editCategory
        &&
        $error === ''
    ) {

        set_flash(
            'error',
            'Kategori tidak ditemukan.'
        );


        redirect(
            $listUrl
        );
    }
}


/*
|--------------------------------------------------------------------------
| FORM DATA
|--------------------------------------------------------------------------
*/

$formData = [

    'id' =>
        $editCategory['id']
        ??
        0,

    'name' =>
        $editCategory['name']
        ??
        '',

    'slug' =>
        $editCategory['slug']
        ??
        '',

    'description' =>
        $editCategory['description']
        ??
        '',

    'image' =>
        $editCategory['image']
        ??
        '',

    'sort_order' =>
        $editCategory['sort_order']
        ??
        0,

    'is_active' =>
        isset(
            $editCategory['is_active']
        )
            ? (int)
                $editCategory['is_active']
            : 1
];


$formImageUrl =
    $formData['image'] !== ''
        ? media_url(
            $formData['image']
        )
        : '';


$isForm =
    $action === 'add'
    ||
    (
        $action === 'edit'
        &&
        $editCategory
    )
    ||
    $error !== '';


$pageTitle =
    $isForm
        ? (
            $editCategory
                ? 'Edit Kategori'
                : 'Tambah Kategori'
        )
        : 'Kelola Kategori';


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalCategories =
    count(
        $categories
    );

$activeCategories =
    0;

$inactiveCategories =
    0;

$totalProductsInCategories =
    0;


foreach (
    $categories
    as $category
) {

    if (
        (int)
        (
            $category['is_active']
            ??
            0
        )
        ===
        1
    ) {

        $activeCategories++;

    } else {

        $inactiveCategories++;
    }


    $totalProductsInCategories +=
        (int)
        (
            $category['product_count']
            ??
            0
        );
}

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

    <meta
        name="robots"
        content="noindex,nofollow"
    >

    <title>
        <?= e(
            $pageTitle
        ); ?>
        | <?= e(
            $siteName
        ); ?>
    </title>


    <!-- ===================================================
         FONT
    ==================================================== -->

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


    <!-- ===================================================
         FONT AWESOME
    ==================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >


    <!-- ===================================================
         CATEGORY ADMIN CSS
    ==================================================== -->

    <style>

        :root {

            --red:#d90000;
            --red-dark:#b30000;

            --black:#111111;
            --black-soft:#1c1c1c;

            --white:#ffffff;

            --bg:#f5f6f8;

            --text:#191919;

            --muted:#777777;

            --line:#e5e5e5;

            --green:#138447;

            --blue:#2864d8;

            --orange:#b06c00;

            --shadow:
                0 8px 28px
                rgba(0,0,0,.055);

            --font:
                "Poppins",
                "Inter",
                Arial,
                sans-serif;

            --font-body:
                "Inter",
                Arial,
                sans-serif;
        }


        * {
            box-sizing:border-box;
        }


        html,
        body {
            margin:0;
            padding:0;
            min-height:100%;
        }


        body {

            background:
                var(--bg);

            color:
                var(--text);

            font-family:
                var(--font-body);

            overflow-x:hidden;
        }


        a {
            color:inherit;
            text-decoration:none;
        }


        button,
        input,
        textarea,
        select {
            font:inherit;
        }


        button {
            cursor:pointer;
        }


        /* =================================================
           LAYOUT
        ================================================== */

        .layout {
            min-height:100vh;
        }


        .sidebar {

            position:fixed;

            top:0;
            bottom:0;
            left:0;

            z-index:1100;

            width:255px;

            display:flex;
            flex-direction:column;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .70
                );

            background:
                linear-gradient(
                    180deg,
                    #101010,
                    #181818
                );

            border-right:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .05
                );

            transition:
                transform .3s ease;
        }


        .sidebar-brand {

            min-height:82px;

            display:flex;

            align-items:center;

            padding:
                0 22px;

            border-bottom:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .07
                );
        }


        .sidebar-brand img {

            width:auto;

            max-width:185px;

            max-height:52px;

            object-fit:contain;
        }


        .sidebar-fallback {

            display:flex;

            flex-direction:column;

            line-height:1;
        }


        .sidebar-fallback strong {

            color:#ffffff;

            font-family:var(--font);

            font-size:17px;

            font-weight:800;
        }


        .sidebar-fallback small {

            margin-top:6px;

            color:#ff3838;

            font-size:8px;

            font-weight:800;

            letter-spacing:.18em;
        }


        .sidebar-scroll {

            flex:1;

            overflow-y:auto;

            padding:
                18px 12px;
        }


        .sidebar-label {

            display:block;

            padding:
                0 12px 10px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .27
                );

            font-size:9px;

            font-weight:800;

            letter-spacing:.13em;

            text-transform:uppercase;
        }


        .sidebar-menu {

            display:flex;

            flex-direction:column;

            gap:4px;
        }


        .sidebar-link {

            min-height:46px;

            display:flex;

            align-items:center;

            gap:12px;

            padding:
                0 13px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .61
                );

            border-radius:10px;

            font-size:11px;

            font-weight:600;

            transition:
                background .2s ease,
                color .2s ease;
        }


        .sidebar-link i {

            width:18px;

            text-align:center;
        }


        .sidebar-link:hover {

            color:#ffffff;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .06
                );
        }


        .sidebar-link.active {

            color:#ffffff;

            background:
                var(--red);

            box-shadow:
                0 8px 20px
                rgba(
                    217,
                    0,
                    0,
                    .18
                );
        }


        .sidebar-user {

            padding:12px;

            border-top:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .07
                );
        }


        .sidebar-user-box {

            display:flex;

            align-items:center;

            gap:10px;

            padding:10px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );

            border-radius:10px;
        }


        .avatar {

            width:34px;
            height:34px;

            display:flex;

            align-items:center;

            justify-content:center;

            flex:0 0 auto;

            color:#ffffff;

            background:
                var(--red);

            border-radius:9px;

            font-size:11px;

            font-weight:800;
        }


        .user-text {

            min-width:0;
        }


        .user-text strong {

            display:block;

            max-width:150px;

            overflow:hidden;

            color:#ffffff;

            font-size:10px;

            white-space:nowrap;

            text-overflow:ellipsis;
        }


        .user-text span {

            display:block;

            margin-top:2px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .35
                );

            font-size:8px;
        }


        /* =================================================
           MAIN
        ================================================== */

        .main {

            width:
                calc(
                    100% - 255px
                );

            margin-left:
                255px;

            min-width:0;
        }


        /* =================================================
           TOPBAR
        ================================================== */

        .topbar {

            position:sticky;

            top:0;

            z-index:900;

            min-height:82px;

            display:flex;

            align-items:center;

            justify-content:space-between;

            gap:20px;

            padding:
                0 30px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .94
                );

            border-bottom:
                1px solid
                var(--line);

            backdrop-filter:
                blur(14px);
        }


        .topbar-left {

            display:flex;

            align-items:center;

            gap:14px;
        }


        .mobile-toggle {

            display:none;

            width:42px;

            height:42px;

            align-items:center;

            justify-content:center;

            color:var(--black);

            background:#ffffff;

            border:
                1px solid
                var(--line);

            border-radius:10px;
        }


        .topbar-title {

            display:flex;

            flex-direction:column;
        }


        .topbar-title span {

            color:var(--muted);

            font-size:8px;

            font-weight:700;

            letter-spacing:.10em;
        }


        .topbar-title strong {

            margin-top:3px;

            color:var(--black);

            font-family:var(--font);

            font-size:16px;

            font-weight:800;
        }


        .topbar-right {

            display:flex;

            align-items:center;

            gap:10px;
        }


        .view-site {

            min-height:40px;

            display:inline-flex;

            align-items:center;

            gap:7px;

            padding:
                0 12px;

            color:var(--black);

            background:white;

            border:
                1px solid
                var(--line);

            border-radius:9px;

            font-size:9px;

            font-weight:700;
        }


        .view-site:hover {

            color:var(--red);

            border-color:
                rgba(
                    217,
                    0,
                    0,
                    .25
                );
        }


        .user-pill {

            min-height:44px;

            display:flex;

            align-items:center;

            gap:8px;

            padding:
                4px 5px;

            background:white;

            border:
                1px solid
                var(--line);

            border-radius:11px;
        }


        .user-pill-name {

            display:flex;

            flex-direction:column;

            min-width:0;
        }


        .user-pill-name strong {

            max-width:120px;

            overflow:hidden;

            font-size:9px;

            font-weight:700;

            white-space:nowrap;

            text-overflow:ellipsis;
        }


        .user-pill-name span {

            color:var(--muted);

            font-size:7px;
        }


        .logout {

            width:34px;

            height:34px;

            display:flex;

            align-items:center;

            justify-content:center;

            color:#777;

            border:0;

            background:transparent;

            border-radius:8px;
        }


        .logout:hover {

            color:var(--red);

            background:
                rgba(
                    217,
                    0,
                    0,
                    .06
                );
        }


        /* =================================================
           CONTENT
        ================================================== */

        .content {

            padding:
                30px;
        }


        .page-header {

            display:flex;

            align-items:flex-end;

            justify-content:space-between;

            gap:20px;

            margin-bottom:22px;
        }


        .page-kicker {

            display:flex;

            align-items:center;

            gap:7px;

            margin-bottom:7px;

            color:var(--red);

            font-size:8px;

            font-weight:800;

            letter-spacing:.13em;

            text-transform:uppercase;
        }


        .page-kicker::before {

            content:"";

            width:20px;

            height:2px;

            background:
                var(--red);

            border-radius:999px;
        }


        .page-title {

            margin:0;

            color:var(--black);

            font-family:var(--font);

            font-size:26px;

            font-weight:800;
        }


        .page-description {

            margin:
                6px 0 0;

            color:var(--muted);

            font-size:10px;
        }


        .primary-button {

            min-height:43px;

            display:inline-flex;

            align-items:center;

            justify-content:center;

            gap:8px;

            padding:
                0 15px;

            color:white;

            background:
                var(--red);

            border:
                1px solid
                var(--red);

            border-radius:9px;

            font-size:9px;

            font-weight:800;

            box-shadow:
                0 9px 22px
                rgba(
                    217,
                    0,
                    0,
                    .18
                );

            transition:
                transform .2s ease,
                background .2s ease;
        }


        .primary-button:hover {

            transform:
                translateY(-2px);

            background:
                var(--red-dark);
        }


        /* =================================================
           ALERT
        ================================================== */

        .alert {

            display:flex;

            align-items:flex-start;

            gap:9px;

            margin-bottom:18px;

            padding:
                12px 14px;

            border-radius:10px;

            font-size:10px;

            font-weight:600;

            line-height:1.55;
        }


        .alert-success {

            color:#146d39;

            background:#ecfaf1;

            border:
                1px solid
                #c9ecd6;
        }


        .alert-error {

            color:#921212;

            background:#fff1f1;

            border:
                1px solid
                #f2caca;
        }


        /* =================================================
           MINI STATS
        ================================================== */

        .stats-grid {

            display:grid;

            grid-template-columns:
                repeat(
                    4,
                    1fr
                );

            gap:13px;

            margin-bottom:18px;
        }


        .stat-card {

            padding:
                18px;

            background:
                #ffffff;

            border:
                1px solid
                var(--line);

            border-radius:
                13px;

            box-shadow:
                0 5px 18px
                rgba(
                    0,
                    0,
                    0,
                    .03
                );
        }


        .stat-icon {

            width:37px;

            height:37px;

            display:flex;

            align-items:center;

            justify-content:center;

            color:
                var(--red);

            background:
                rgba(
                    217,
                    0,
                    0,
                    .07
                );

            border-radius:9px;

            font-size:13px;
        }


        .stat-icon.green {

            color:
                var(--green);

            background:
                rgba(
                    19,
                    132,
                    71,
                    .07
                );
        }


        .stat-icon.orange {

            color:
                var(--orange);

            background:
                rgba(
                    176,
                    108,
                    0,
                    .07
                );
        }


        .stat-icon.blue {

            color:
                var(--blue);

            background:
                rgba(
                    40,
                    100,
                    216,
                    .07
                );
        }


        .stat-card strong {

            display:block;

            margin-top:14px;

            color:var(--black);

            font-family:var(--font);

            font-size:24px;

            font-weight:800;

            line-height:1;
        }


        .stat-card span {

            display:block;

            margin-top:5px;

            color:var(--muted);

            font-size:8px;

            font-weight:600;
        }


        /* =================================================
           FILTER
        ================================================== */

        .filter-panel {

            margin-bottom:
                16px;

            padding:
                17px;

            background:
                #ffffff;

            border:
                1px solid
                var(--line);

            border-radius:
                13px;

            box-shadow:
                0 5px 18px
                rgba(
                    0,
                    0,
                    0,
                    .03
                );
        }


        .filter-form {

            display:grid;

            grid-template-columns:
                minmax(
                    230px,
                    1.5fr
                )
                1fr
                auto;

            gap:9px;
        }


        .filter-input,
        .filter-select {

            width:100%;

            height:41px;

            padding:
                0 11px;

            color:var(--black);

            background:#ffffff;

            border:
                1px solid
                #dfdfdf;

            border-radius:8px;

            outline:none;

            font-size:9px;
        }


        .filter-input:focus,
        .filter-select:focus {

            border-color:
                var(--red);

            box-shadow:
                0 0 0 3px
                rgba(
                    217,
                    0,
                    0,
                    .07
                );
        }


        .filter-button {

            height:41px;

            display:inline-flex;

            align-items:center;

            justify-content:center;

            gap:6px;

            padding:
                0 13px;

            color:white;

            background:
                var(--black);

            border:
                1px solid
                var(--black);

            border-radius:8px;

            font-size:9px;

            font-weight:800;
        }


        .filter-button:hover {

            background:
                #292929;
        }


        /* =================================================
           CATEGORY PANEL
        ================================================== */

        .panel {

            padding:
                20px;

            background:
                #ffffff;

            border:
                1px solid
                var(--line);

            border-radius:
                14px;

            box-shadow:
                var(--shadow);
        }


        .panel-header {

            display:flex;

            align-items:flex-end;

            justify-content:space-between;

            gap:15px;

            margin-bottom:17px;
        }


        .panel-header h2 {

            margin:0;

            color:var(--black);

            font-family:var(--font);

            font-size:15px;

            font-weight:800;
        }


        .panel-header p {

            margin:
                4px 0 0;

            color:var(--muted);

            font-size:9px;
        }


        .result-count {

            color:#999;

            font-size:8px;
        }


        .category-grid {

            display:grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:15px;
        }


        .category-card {

            position:relative;

            display:flex;

            flex-direction:column;

            min-width:0;

            background:
                #ffffff;

            border:
                1px solid
                #e7e7e7;

            border-radius:
                13px;

            overflow:hidden;

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }


        .category-card:hover {

            transform:
                translateY(-3px);

            border-color:
                rgba(
                    217,
                    0,
                    0,
                    .18
                );

            box-shadow:
                0 12px 30px
                rgba(
                    0,
                    0,
                    0,
                    .08
                );
        }


        .category-image {

            position:relative;

            width:100%;

            aspect-ratio:
                16 / 9;

            overflow:hidden;

            background:
                #f3f3f3;
        }


        .category-image img {

            width:100%;

            height:100%;

            object-fit:cover;

            transition:
                transform .45s ease;
        }


        .category-card:hover
        .category-image img {

            transform:
                scale(1.04);
        }


        .category-image-placeholder {

            width:100%;

            height:100%;

            display:flex;

            align-items:center;

            justify-content:center;

            color:#b9b9b9;

            background:
                linear-gradient(
                    135deg,
                    #eeeeee,
                    #f7f7f7
                );

            font-size:26px;
        }


        .category-status {

            position:absolute;

            left:9px;

            top:9px;

            display:inline-flex;

            align-items:center;

            gap:5px;

            padding:
                5px 7px;

            color:white;

            background:
                rgba(
                    0,
                    0,
                    0,
                    .66
                );

            border-radius:5px;

            font-size:7px;

            font-weight:800;

            text-transform:uppercase;
        }


        .category-status.active {

            background:
                rgba(
                    19,
                    132,
                    71,
                    .90
                );
        }


        .category-status.inactive {

            background:
                rgba(
                    70,
                    70,
                    70,
                    .86
                );
        }


        .category-order {

            position:absolute;

            right:9px;

            top:9px;

            width:27px;

            height:27px;

            display:flex;

            align-items:center;

            justify-content:center;

            color:white;

            background:
                rgba(
                    0,
                    0,
                    0,
                    .60
                );

            border-radius:7px;

            font-size:8px;

            font-weight:800;
        }


        .category-body {

            padding:
                16px;
        }


        .category-title {

            margin:0 0 5px;

            color:
                var(--black);

            font-family:
                var(--font);

            font-size:14px;

            font-weight:800;

            line-height:1.3;
        }


        .category-slug {

            margin-bottom:8px;

            color:#a1a1a1;

            font-size:8px;

            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                monospace;
        }


        .category-description {

            min-height:42px;

            margin:0;

            color:var(--muted);

            font-size:9px;

            line-height:1.6;
        }


        .category-meta {

            display:flex;

            align-items:center;

            justify-content:space-between;

            gap:10px;

            margin-top:14px;

            padding-top:12px;

            border-top:
                1px solid
                #eeeeee;
        }


        .product-count {

            display:inline-flex;

            align-items:center;

            gap:5px;

            color:#777;

            font-size:8px;

            font-weight:600;
        }


        .product-count i {

            color:
                var(--red);
        }


        .category-actions {

            display:flex;

            align-items:center;

            gap:5px;
        }


        .icon-button {

            width:31px;

            height:31px;

            display:flex;

            align-items:center;

            justify-content:center;

            color:#666;

            background:white;

            border:
                1px solid
                #dfdfdf;

            border-radius:7px;

            transition:
                color .2s ease,
                border-color .2s ease,
                background .2s ease;
        }


        .icon-button:hover {

            color:
                var(--red);

            border-color:
                rgba(
                    217,
                    0,
                    0,
                    .25
                );
        }


        .icon-button.danger:hover {

            color:white;

            background:
                #b81e1e;

            border-color:
                #b81e1e;
        }


        .inline-form {

            display:inline;

            margin:0;
        }


        /* =================================================
           FORM
        ================================================== */

        .form-panel {

            margin-bottom:18px;

            padding:
                21px;

            background:white;

            border:
                1px solid
                var(--line);

            border-radius:
                14px;

            box-shadow:
                var(--shadow);
        }


        .form-title {

            margin:
                0 0 18px;

            color:var(--black);

            font-family:
                var(--font);

            font-size:15px;

            font-weight:800;
        }


        .form-grid {

            display:grid;

            grid-template-columns:
                1fr
                1fr;

            gap:16px;
        }


        .form-group {

            min-width:0;
        }


        .form-group.full {

            grid-column:
                1 / -1;
        }


        .form-label {

            display:block;

            margin-bottom:7px;

            color:var(--black);

            font-size:9px;

            font-weight:700;
        }


        .form-label span {

            color:#999;

            font-size:8px;

            font-weight:500;
        }


        .form-control,
        .form-textarea {

            width:100%;

            color:var(--black);

            background:white;

            border:
                1px solid
                #dfdfdf;

            border-radius:9px;

            outline:none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;

            font-size:10px;
        }


        .form-control {

            min-height:
                45px;

            padding:
                0 12px;
        }


        .form-textarea {

            min-height:
                115px;

            padding:
                11px 12px;

            resize:
                vertical;

            line-height:
                1.7;
        }


        .form-control:focus,
        .form-textarea:focus {

            border-color:
                var(--red);

            box-shadow:
                0 0 0 4px
                rgba(
                    217,
                    0,
                    0,
                    .07
                );
        }


        .help-text {

            margin-top:
                5px;

            color:#999;

            font-size:8px;

            line-height:1.55;
        }


        .media-box {

            padding:
                15px;

            background:
                #fafafa;

            border:
                1px dashed
                #d8d8d8;

            border-radius:
                11px;
        }


        .media-box input[type=file] {

            width:100%;

            font-size:9px;
        }


        .current-image {

            width:
                min(
                    100%,
                    360px
                );

            margin-top:
                10px;

            padding:
                8px;

            background:white;

            border:
                1px solid
                #e7e7e7;

            border-radius:
                9px;
        }


        .current-image img {

            display:block;

            width:100%;

            aspect-ratio:
                16 / 9;

            object-fit:cover;

            background:
                #f3f3f3;

            border-radius:
                7px;
        }


        .current-image-name {

            margin-top:
                7px;

            overflow:hidden;

            color:#888;

            font-size:8px;

            white-space:nowrap;

            text-overflow:ellipsis;
        }


        .remove-image {

            display:inline-flex;

            align-items:center;

            gap:6px;

            margin-top:9px;

            color:#8f2b2b;

            font-size:8px;

            font-weight:600;
        }


        .remove-image input {

            width:14px;

            height:14px;

            accent-color:
                var(--red);
        }


        .options {

            display:flex;

            align-items:center;

            flex-wrap:wrap;

            gap:15px;

            padding-top:
                15px;

            border-top:
                1px solid
                #eeeeee;
        }


        .check-label {

            display:inline-flex;

            align-items:center;

            gap:7px;

            color:#666;

            font-size:9px;

            font-weight:600;
        }


        .check-label input {

            width:15px;

            height:15px;

            accent-color:
                var(--red);
        }


        .form-actions {

            display:flex;

            align-items:center;

            justify-content:flex-end;

            gap:8px;

            padding-top:
                18px;

            margin-top:
                6px;

            border-top:
                1px solid
                #eeeeee;
        }


        .secondary-button {

            min-height:
                42px;

            display:inline-flex;

            align-items:center;

            justify-content:center;

            gap:7px;

            padding:
                0 14px;

            color:#555;

            background:white;

            border:
                1px solid
                #dedede;

            border-radius:8px;

            font-size:9px;

            font-weight:800;
        }


        .secondary-button:hover {

            color:
                var(--red);

            border-color:
                rgba(
                    217,
                    0,
                    0,
                    .25
                );
        }


        /* =================================================
           EMPTY
        ================================================== */

        .empty {

            padding:
                55px 20px;

            text-align:center;

            color:#999;

            border:
                1px dashed
                #dcdcdc;

            border-radius:
                11px;
        }


        .empty i {

            display:block;

            margin-bottom:9px;

            color:#cccccc;

            font-size:28px;
        }


        .empty strong {

            display:block;

            margin-bottom:4px;

            color:#555;

            font-family:
                var(--font);

            font-size:12px;
        }


        .empty span {

            font-size:9px;
        }


        /* =================================================
           OVERLAY
        ================================================== */

        .overlay {

            position:fixed;

            inset:0;

            z-index:1090;

            background:
                rgba(
                    0,
                    0,
                    0,
                    .50
                );

            opacity:0;

            visibility:hidden;

            transition:
                opacity .25s ease,
                visibility .25s ease;
        }


        .overlay.show {

            opacity:1;

            visibility:visible;
        }


        /* =================================================
           RESPONSIVE
        ================================================== */

        @media (max-width:1200px) {

            .sidebar {
                width:230px;
            }


            .main {

                width:
                    calc(
                        100% - 230px
                    );

                margin-left:
                    230px;
            }


            .category-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }


            .stats-grid {

                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }

        }


        @media (max-width:900px) {

            .sidebar {

                transform:
                    translateX(
                        -100%
                    );
            }


            .sidebar.open {

                transform:
                    translateX(
                        0
                    );
            }


            .main {

                width:100%;

                margin-left:0;
            }


            .mobile-toggle {

                display:flex;
            }


            .topbar {

                padding:
                    0 20px;
            }


            .content {

                padding:
                    20px;
            }

        }


        @media (max-width:700px) {

            .page-header {

                align-items:flex-start;

                flex-direction:column;
            }


            .primary-button {

                width:100%;
            }


            .filter-form {

                grid-template-columns:
                    1fr;
            }


            .form-grid {

                grid-template-columns:
                    1fr;
            }


            .form-group.full {

                grid-column:auto;
            }


            .form-actions {

                flex-direction:
                    column-reverse;
            }


            .form-actions .primary-button,
            .form-actions .secondary-button {

                width:100%;
            }


            .category-grid {

                grid-template-columns:
                    1fr;
            }

        }


        @media (max-width:550px) {

            .topbar {

                min-height:
                    70px;

                padding:
                    0 13px;
            }


            .content {

                padding:
                    13px;
            }


            .topbar-title strong {

                font-size:
                    14px;
            }


            .view-site span,
            .user-pill-name {

                display:
                    none;
            }


            .panel,
            .form-panel,
            .filter-panel {

                padding:
                    15px;
            }


            .page-title {

                font-size:
                    22px;
            }


            .stats-grid {

                grid-template-columns:
                    1fr;
            }


            .category-body {

                padding:
                    14px;
            }

        }

    </style>

</head>


<body>
<?php render_admin_sidebar_assets(); ?>
<?php render_admin_sidebar('categories.php'); ?>


<div
    class="layout"
>


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >

        <div
            class="sidebar-brand"
        >

            <a
                href="<?= e(
                    base_url(
                        'admin/dashboard.php'
                    )
                ); ?>"
            >

                <?php if (
                    $siteLogoUrl !== ''
                ): ?>

                    <img
                        src="<?= e(
                            $siteLogoUrl
                        ); ?>"
                        alt="<?= e(
                            $siteName
                        ); ?>"
                    >

                <?php else: ?>

                    <div
                        class="sidebar-fallback"
                    >

                        <strong>
                            STAR PARTS
                        </strong>

                        <small>
                            MOTOR
                        </small>

                    </div>

                <?php endif; ?>

            </a>

        </div>


        <div
            class="sidebar-scroll"
        >

            <span
                class="sidebar-label"
            >
                MAIN MENU
            </span>


            <nav
                class="sidebar-menu"
            >

                <a
                    href="<?= e(
                        base_url(
                            'admin/dashboard.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-gauge-high"
                    ></i>

                    Dashboard

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/banners.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-regular fa-images"
                    ></i>

                    Banner

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/products.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-box-open"
                    ></i>

                    Produk

                </a>


                <a
                    href="<?= e(
                        $listUrl
                    ); ?>"
                    class="sidebar-link active"
                >

                    <i
                        class="fa-solid fa-layer-group"
                    ></i>

                    Kategori

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/brands.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-tags"
                    ></i>

                    Brand

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/posts.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-newspaper"
                    ></i>

                    Berita

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/media.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-photo-film"
                    ></i>

                    Media

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/locations.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-map-location-dot"
                    ></i>

                    Lokasi

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/messages.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-envelope"
                    ></i>

                    Pesan

                </a>


                <span
                    class="sidebar-label"
                    style="margin-top:18px;"
                >
                    WEBSITE
                </span>


                <a
                    href="<?= e(
                        base_url(
                            'admin/pages.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-regular fa-file-lines"
                    ></i>

                    Halaman

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/menus.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-bars"
                    ></i>

                    Menu

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/settings.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-gear"
                    ></i>

                    Pengaturan

                </a>

                <a href="<?= e(base_url('admin/careers.php')); ?>" class="sidebar-link">
                    <i class="fa-solid fa-briefcase"></i>
                    Lowongan Karir
                </a>

                <a href="<?= e(base_url('admin/security.php')); ?>" class="sidebar-link">
                    <i class="fa-solid fa-shield-halved"></i>
                    Security
                </a>

                <a href="<?= e(base_url('admin/testimonials.php')); ?>" class="sidebar-link">
                    <i class="fa-solid fa-comment-dots"></i>
                    Testimoni
                </a>

            </nav>

        </div>


        <div
            class="sidebar-user"
        >

            <div
                class="sidebar-user-box"
            >

                <div
                    class="avatar"
                >

                    <?= e(
                        strtoupper(
                            mb_substr(
                                $adminName,
                                0,
                                1
                            )
                        )
                    ); ?>

                </div>


                <div
                    class="user-text"
                >

                    <strong>
                        <?= e(
                            $adminName
                        ); ?>
                    </strong>

                    <span>
                        <?= e(
                            $admin['role']
                            ??
                            'admin'
                        ); ?>
                    </span>

                </div>

            </div>

        </div>

    </aside>


    <!-- =================================================
         OVERLAY
    ================================================== -->

    <div
        class="overlay"
        id="sidebar-overlay"
    ></div>


    <!-- =================================================
         MAIN
    ================================================== -->

    <div
        class="main"
    >


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header
            class="topbar"
        >

            <div
                class="topbar-left"
            >

                <button
                    type="button"
                    class="mobile-toggle"
                    id="mobile-toggle"
                    aria-label="Buka menu admin"
                >

                    <i
                        class="fa-solid fa-bars"
                    ></i>

                </button>


                <div
                    class="topbar-title"
                >

                    <span>
                        PANEL ADMINISTRATOR
                    </span>

                    <strong>
                        Manajemen Kategori
                    </strong>

                </div>

            </div>


            <div
                class="topbar-right"
            >

                <a
                    href="<?= e(
                        base_url(
                            'index.php'
                        )
                    ); ?>"
                    class="view-site"
                    target="_blank"
                    rel="noopener noreferrer"
                >

                    <i
                        class="fa-solid fa-arrow-up-right-from-square"
                    ></i>

                    <span>
                        Lihat Website
                    </span>

                </a>


                <div
                    class="user-pill"
                >

                    <div
                        class="avatar"
                    >

                        <?= e(
                            strtoupper(
                                mb_substr(
                                    $adminName,
                                    0,
                                    1
                                )
                            )
                        ); ?>

                    </div>


                    <div
                        class="user-pill-name"
                    >

                        <strong>
                            <?= e(
                                $adminName
                            ); ?>
                        </strong>

                        <span>
                            Administrator
                        </span>

                    </div>


                    <a
                        href="<?= e(
                            base_url(
                                'admin/logout.php'
                            )
                        ); ?>"
                        class="logout"
                        title="Logout"
                    >

                        <i
                            class="fa-solid fa-right-from-bracket"
                        ></i>

                    </a>

                </div>

            </div>

        </header>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <main
            class="content"
        >


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div
                class="page-header"
            >

                <div>

                    <div
                        class="page-kicker"
                    >
                        CATALOG MANAGEMENT
                    </div>


                    <h1
                        class="page-title"
                    >

                        <?= e(
                            $pageTitle
                        ); ?>

                    </h1>


                    <p
                        class="page-description"
                    >

                        Kelola kategori produk yang tampil
                        pada katalog dan website utama.

                    </p>

                </div>


                <?php if (
                    !$isForm
                ): ?>

                    <a
                        href="<?= e(
                            base_url(
                                'admin/categories.php?action=add'
                            )
                        ); ?>"
                        class="primary-button"
                    >

                        <i
                            class="fa-solid fa-plus"
                        ></i>

                        Tambah Kategori

                    </a>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 ALERT
            ================================================== -->

            <?php if (
                $flash
                &&
                !empty(
                    $flash['message']
                )
            ): ?>

                <div
                    class="alert <?= ($flash['type'] ?? '') === 'success'
                        ? 'alert-success'
                        : 'alert-error'; ?>"
                    role="alert"
                >

                    <i
                        class="fa-solid <?= ($flash['type'] ?? '') === 'success'
                            ? 'fa-circle-check'
                            : 'fa-circle-exclamation'; ?>"
                    ></i>

                    <span>

                        <?= e(
                            (string)
                            $flash['message']
                        ); ?>

                    </span>

                </div>

            <?php endif; ?>


            <?php if (
                $error !== ''
            ): ?>

                <div
                    class="alert alert-error"
                    role="alert"
                >

                    <i
                        class="fa-solid fa-circle-exclamation"
                    ></i>

                    <span>
                        <?= e(
                            $error
                        ); ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->

            <?php if (
                $isForm
            ): ?>

                <section
                    class="form-panel"
                >

                    <h2
                        class="form-title"
                    >

                        <?= $editCategory
                            ? 'Edit Kategori'
                            : 'Tambah Kategori Baru'; ?>

                    </h2>


                    <form
                        action=""
                        method="POST"
                        enctype="multipart/form-data"
                        id="category-form"
                    >

                        <?= csrf_input(); ?>


                        <input
                            type="hidden"
                            name="form_action"
                            value="save"
                        >


                        <input
                            type="hidden"
                            name="id"
                            value="<?= e(
                                (string)
                                $formData['id']
                            ); ?>"
                        >


                        <div
                            class="form-grid"
                        >


                            <!-- =====================================
                                 NAME
                            ====================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    for="name"
                                    class="form-label"
                                >

                                    Nama Kategori *

                                </label>


                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-control"
                                    value="<?= e(
                                        (string)
                                        $formData['name']
                                    ); ?>"
                                    maxlength="150"
                                    placeholder="Contoh: Ban Motor FDR"
                                    required
                                >

                            </div>


                            <!-- =====================================
                                 SORT
                            ====================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    for="sort_order"
                                    class="form-label"
                                >

                                    Urutan

                                    <span>
                                        angka kecil tampil lebih dahulu
                                    </span>

                                </label>


                                <input
                                    type="number"
                                    name="sort_order"
                                    id="sort_order"
                                    class="form-control"
                                    value="<?= e(
                                        (string)
                                        $formData['sort_order']
                                    ); ?>"
                                    min="0"
                                    max="9999"
                                >

                            </div>


                            <!-- =====================================
                                 SLUG
                            ====================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    for="slug"
                                    class="form-label"
                                >

                                    Slug

                                    <span>
                                        opsional
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="slug"
                                    id="slug"
                                    class="form-control"
                                    value="<?= e(
                                        (string)
                                        $formData['slug']
                                    ); ?>"
                                    maxlength="180"
                                    placeholder="ban-motor-fdr"
                                >


                                <div
                                    class="help-text"
                                >

                                    Akan dibuat otomatis berdasarkan nama
                                    apabila dibiarkan kosong.

                                </div>

                            </div>


                            <!-- =====================================
                                 DESCRIPTION
                            ====================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    for="description"
                                    class="form-label"
                                >

                                    Deskripsi

                                </label>


                                <textarea
                                    name="description"
                                    id="description"
                                    class="form-textarea"
                                    maxlength="1500"
                                    placeholder="Deskripsi singkat kategori..."
                                ><?= e(
                                    (string)
                                    $formData['description']
                                ); ?></textarea>

                            </div>


                            <!-- =====================================
                                 IMAGE
                            ====================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                >

                                    Foto Kategori

                                    <span>
                                        JPG / PNG / WEBP / GIF, maksimal 5 MB
                                    </span>

                                </label>


                                <div
                                    class="media-box"
                                >

                                    <input
                                        type="file"
                                        name="image"
                                        id="image"
                                        accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                    >


                                    <?php if (
                                        $formImageUrl !== ''
                                    ): ?>

                                        <div
                                            class="current-image"
                                        >

                                            <img
                                                src="<?= e(
                                                    $formImageUrl
                                                ); ?>"
                                                alt="<?= e(
                                                    (string)
                                                    $formData['name']
                                                ); ?>"
                                            >


                                            <div
                                                class="current-image-name"
                                            >

                                                <?= e(
                                                    basename(
                                                        (string)
                                                        $formData['image']
                                                    )
                                                ); ?>

                                            </div>


                                            <label
                                                class="remove-image"
                                            >

                                                <input
                                                    type="checkbox"
                                                    name="remove_image"
                                                    value="1"
                                                >

                                                Hapus foto kategori

                                            </label>

                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div
                                    class="help-text"
                                >

                                    Gunakan foto horizontal dengan kualitas
                                    baik agar tampil optimal pada kartu kategori.

                                </div>

                            </div>


                            <!-- =====================================
                                 STATUS
                            ====================================== -->

                            <div
                                class="form-group full"
                            >

                                <div
                                    class="options"
                                >

                                    <label
                                        class="check-label"
                                    >

                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            <?= (int)
                                                $formData['is_active']
                                                ===
                                                1
                                                    ? 'checked'
                                                    : ''; ?>
                                        >

                                        Kategori Aktif

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                             FORM ACTION
                        ====================================== -->

                        <div
                            class="form-actions"
                        >

                            <a
                                href="<?= e(
                                    $listUrl
                                ); ?>"
                                class="secondary-button"
                            >

                                <i
                                    class="fa-solid fa-xmark"
                                ></i>

                                Batal

                            </a>


                            <button
                                type="submit"
                                class="primary-button"
                                id="save-category-button"
                            >

                                <i
                                    class="fa-solid fa-floppy-disk"
                                ></i>

                                Simpan Kategori

                            </button>

                        </div>

                    </form>

                </section>

            <?php endif; ?>


            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <?php if (
                !$isForm
            ): ?>

                <section
                    class="stats-grid"
                >

                    <article
                        class="stat-card"
                    >

                        <div
                            class="stat-icon"
                        >

                            <i
                                class="fa-solid fa-layer-group"
                            ></i>

                        </div>


                        <strong>
                            <?= e(
                                (string)
                                $totalCategories
                            ); ?>
                        </strong>


                        <span>
                            Total Kategori
                        </span>

                    </article>


                    <article
                        class="stat-card"
                    >

                        <div
                            class="stat-icon green"
                        >

                            <i
                                class="fa-solid fa-circle-check"
                            ></i>

                        </div>


                        <strong>
                            <?= e(
                                (string)
                                $activeCategories
                            ); ?>
                        </strong>


                        <span>
                            Kategori Aktif
                        </span>

                    </article>


                    <article
                        class="stat-card"
                    >

                        <div
                            class="stat-icon orange"
                        >

                            <i
                                class="fa-solid fa-eye-slash"
                            ></i>

                        </div>


                        <strong>
                            <?= e(
                                (string)
                                $inactiveCategories
                            ); ?>
                        </strong>


                        <span>
                            Kategori Nonaktif
                        </span>

                    </article>


                    <article
                        class="stat-card"
                    >

                        <div
                            class="stat-icon blue"
                        >

                            <i
                                class="fa-solid fa-box-open"
                            ></i>

                        </div>


                        <strong>
                            <?= e(
                                (string)
                                $totalProductsInCategories
                            ); ?>
                        </strong>


                        <span>
                            Produk Terhubung
                        </span>

                    </article>

                </section>

            <?php endif; ?>


            <!-- =================================================
                 FILTER
            ================================================== -->

            <?php if (
                !$isForm
            ): ?>

                <section
                    class="filter-panel"
                >

                    <form
                        action=""
                        method="GET"
                        class="filter-form"
                    >

                        <input
                            type="text"
                            name="search"
                            class="filter-input"
                            value="<?= e(
                                $search
                            ); ?>"
                            placeholder="Cari nama kategori, slug, deskripsi..."
                        >


                        <select
                            name="status"
                            class="filter-select"
                        >

                            <option
                                value="all"
                                <?= $statusFilter === 'all'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Semua Status
                            </option>


                            <option
                                value="active"
                                <?= $statusFilter === 'active'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Aktif
                            </option>


                            <option
                                value="inactive"
                                <?= $statusFilter === 'inactive'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Nonaktif
                            </option>

                        </select>


                        <button
                            type="submit"
                            class="filter-button"
                        >

                            <i
                                class="fa-solid fa-magnifying-glass"
                            ></i>

                            Filter

                        </button>

                    </form>

                </section>

            <?php endif; ?>


            <!-- =================================================
                 CATEGORY LIST
            ================================================== -->

            <?php if (
                !$isForm
            ): ?>

                <section
                    class="panel"
                >

                    <div
                        class="panel-header"
                    >

                        <div>

                            <h2>
                                Daftar Kategori
                            </h2>

                            <p>
                                Kategori yang digunakan
                                pada katalog produk.
                            </p>

                        </div>


                        <span
                            class="result-count"
                        >

                            <?= e(
                                (string)
                                count(
                                    $categories
                                )
                            ); ?>

                            hasil

                        </span>

                    </div>


                    <?php if (
                        !empty(
                            $categories
                        )
                    ): ?>

                        <div
                            class="category-grid"
                        >

                            <?php foreach (
                                $categories
                                as $category
                            ): ?>

                                <?php

                                $categoryId =
                                    (int)
                                    (
                                        $category['id']
                                        ??
                                        0
                                    );


                                $categoryName =
                                    trim(
                                        (string)
                                        (
                                            $category['name']
                                            ??
                                            ''
                                        )
                                    );


                                $categorySlug =
                                    trim(
                                        (string)
                                        (
                                            $category['slug']
                                            ??
                                            ''
                                        )
                                    );


                                $categoryDescription =
                                    trim(
                                        (string)
                                        (
                                            $category['description']
                                            ??
                                            ''
                                        )
                                    );


                                $categoryImage =
                                    trim(
                                        (string)
                                        (
                                            $category['image']
                                            ??
                                            ''
                                        )
                                    );


                                $categoryImageUrl =
                                    $categoryImage !== ''
                                        ? media_url(
                                            $categoryImage
                                        )
                                        : '';


                                $categoryActive =
                                    (int)
                                    (
                                        $category['is_active']
                                        ??
                                        0
                                    )
                                    ===
                                    1;


                                $productCount =
                                    (int)
                                    (
                                        $category['product_count']
                                        ??
                                        0
                                    );

                                ?>


                                <article
                                    class="category-card"
                                >


                                    <!-- =================================
                                         IMAGE
                                    ================================== -->

                                    <div
                                        class="category-image"
                                    >

                                        <?php if (
                                            $categoryImageUrl
                                            !==
                                            ''
                                        ): ?>

                                            <img
                                                src="<?= e(
                                                    $categoryImageUrl
                                                ); ?>"
                                                alt="<?= e(
                                                    $categoryName
                                                ); ?>"
                                                loading="lazy"
                                            >

                                        <?php else: ?>

                                            <div
                                                class="category-image-placeholder"
                                            >

                                                <i
                                                    class="fa-solid fa-layer-group"
                                                ></i>

                                            </div>

                                        <?php endif; ?>


                                        <span
                                            class="category-status <?= $categoryActive
                                                ? 'active'
                                                : 'inactive'; ?>"
                                        >

                                            <i
                                                class="fa-solid fa-circle"
                                                style="font-size:4px;"
                                            ></i>

                                            <?= $categoryActive
                                                ? 'Aktif'
                                                : 'Nonaktif'; ?>

                                        </span>


                                        <span
                                            class="category-order"
                                            title="Urutan"
                                        >

                                            <?= e(
                                                (string)
                                                (
                                                    $category['sort_order']
                                                    ??
                                                    0
                                                )
                                            ); ?>

                                        </span>

                                    </div>


                                    <!-- =================================
                                         BODY
                                    ================================== -->

                                    <div
                                        class="category-body"
                                    >

                                        <h3
                                            class="category-title"
                                        >

                                            <?= e(
                                                $categoryName
                                            ); ?>

                                        </h3>


                                        <div
                                            class="category-slug"
                                        >

                                            /
                                            <?= e(
                                                $categorySlug
                                            ); ?>

                                        </div>


                                        <p
                                            class="category-description"
                                        >

                                            <?= e(
                                                $categoryDescription !== ''
                                                    ? excerpt(
                                                        $categoryDescription,
                                                        100
                                                    )
                                                    : 'Belum ada deskripsi kategori.'
                                            ); ?>

                                        </p>


                                        <div
                                            class="category-meta"
                                        >

                                            <span
                                                class="product-count"
                                            >

                                                <i
                                                    class="fa-solid fa-box-open"
                                                ></i>

                                                <?= e(
                                                    (string)
                                                    $productCount
                                                ); ?>

                                                produk

                                            </span>


                                            <div
                                                class="category-actions"
                                            >


                                                <!-- =======================
                                                     EDIT
                                                ======================== -->

                                                <a
                                                    href="<?= e(
                                                        base_url(
                                                            'admin/categories.php?action=edit&id='
                                                            .
                                                            $categoryId
                                                        )
                                                    ); ?>"
                                                    class="icon-button"
                                                    title="Edit kategori"
                                                >

                                                    <i
                                                        class="fa-solid fa-pen"
                                                    ></i>

                                                </a>


                                                <!-- =======================
                                                     TOGGLE
                                                ======================== -->

                                                <form
                                                    action=""
                                                    method="POST"
                                                    class="inline-form"
                                                >

                                                    <?= csrf_input(); ?>


                                                    <input
                                                        type="hidden"
                                                        name="form_action"
                                                        value="toggle"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= e(
                                                            (string)
                                                            $categoryId
                                                        ); ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="icon-button"
                                                        title="<?= $categoryActive
                                                            ? 'Nonaktifkan'
                                                            : 'Aktifkan'; ?>"
                                                    >

                                                        <i
                                                            class="fa-solid <?= $categoryActive
                                                                ? 'fa-eye-slash'
                                                                : 'fa-eye'; ?>"
                                                        ></i>

                                                    </button>

                                                </form>


                                                <!-- =======================
                                                     DELETE
                                                ======================== -->

                                                <?php if (
                                                    $productCount
                                                    ===
                                                    0
                                                ): ?>

                                                    <form
                                                        action=""
                                                        method="POST"
                                                        class="inline-form delete-form"
                                                    >

                                                        <?= csrf_input(); ?>


                                                        <input
                                                            type="hidden"
                                                            name="form_action"
                                                            value="delete"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= e(
                                                                (string)
                                                                $categoryId
                                                            ); ?>"
                                                        >


                                                        <button
                                                            type="submit"
                                                            class="icon-button danger"
                                                            title="Hapus kategori"
                                                        >

                                                            <i
                                                                class="fa-solid fa-trash"
                                                            ></i>

                                                        </button>

                                                    </form>

                                                <?php else: ?>

                                                    <button
                                                        type="button"
                                                        class="icon-button"
                                                        disabled
                                                        title="Kategori masih digunakan oleh produk"
                                                        style="
                                                            opacity:.35;
                                                            cursor:not-allowed;
                                                        "
                                                    >

                                                        <i
                                                            class="fa-solid fa-lock"
                                                        ></i>

                                                    </button>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div
                            class="empty"
                        >

                            <i
                                class="fa-solid fa-layer-group"
                            ></i>


                            <strong>
                                Belum ada kategori
                            </strong>


                            <span>
                                Tambahkan kategori pertama
                                melalui tombol "Tambah Kategori".
                            </span>

                        </div>

                    <?php endif; ?>

                </section>

            <?php endif; ?>


            <!-- =================================================
                 FOOTER NOTE
            ================================================== -->

            <div
                style="
                    padding:18px 0;
                    text-align:center;
                    color:#a0a0a0;
                    font-size:8px;
                "
            >

                <?= e(
                    $siteName
                ); ?>

                &nbsp;•&nbsp;

                Category Management

                &nbsp;•&nbsp;

                <?= e(
                    date('Y')
                ); ?>

            </div>

        </main>

    </div>

</div>


<!-- =======================================================
     JAVASCRIPT
======================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        /*
         * ================================================
         * MOBILE SIDEBAR
         * ================================================
         */

        const sidebar =
            document.getElementById(
                "sidebar"
            );

        const toggle =
            document.getElementById(
                "mobile-toggle"
            );

        const overlay =
            document.getElementById(
                "sidebar-overlay"
            );


        function openSidebar() {

            if (!sidebar) {
                return;
            }

            sidebar.classList.add(
                "open"
            );

            if (overlay) {

                overlay.classList.add(
                    "show"
                );

            }

            document.body.style.overflow =
                "hidden";
        }


        function closeSidebar() {

            if (!sidebar) {
                return;
            }

            sidebar.classList.remove(
                "open"
            );

            if (overlay) {

                overlay.classList.remove(
                    "show"
                );

            }

            document.body.style.overflow =
                "";
        }


        if (
            toggle
        ) {

            toggle.addEventListener(
                "click",
                function () {

                    if (
                        sidebar.classList.contains(
                            "open"
                        )
                    ) {

                        closeSidebar();

                    } else {

                        openSidebar();

                    }

                }
            );

        }


        if (
            overlay
        ) {

            overlay.addEventListener(
                "click",
                closeSidebar
            );

        }


        /*
         * ================================================
         * ESCAPE
         * ================================================
         */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape"
                ) {

                    closeSidebar();

                }

            }
        );


        /*
         * ================================================
         * DELETE CONFIRM
         * ================================================
         */

        document
            .querySelectorAll(
                ".delete-form"
            )
            .forEach(
                function (
                    form
                ) {

                    form.addEventListener(
                        "submit",
                        function (event) {

                            const confirmation =
                                window.confirm(
                                    "Yakin ingin menghapus kategori ini?"
                                );


                            if (
                                !confirmation
                            ) {

                                event.preventDefault();

                            }

                        }
                    );

                }
            );


        /*
         * ================================================
         * FORM DOUBLE SUBMIT
         * ================================================
         */

        const form =
            document.getElementById(
                "category-form"
            );

        const saveButton =
            document.getElementById(
                "save-category-button"
            );


        if (
            form
            &&
            saveButton
        ) {

            form.addEventListener(
                "submit",
                function () {

                    saveButton.disabled =
                        true;

                    saveButton.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

                }
            );

        }


        /*
         * ================================================
         * AUTO SLUG
         * ================================================
         */

        const nameInput =
            document.getElementById(
                "name"
            );

        const slugInput =
            document.getElementById(
                "slug"
            );


        if (
            nameInput
            &&
            slugInput
        ) {

            let slugManuallyEdited =
                slugInput.value.trim()
                !==
                "";


            slugInput.addEventListener(
                "input",
                function () {

                    slugManuallyEdited =
                        true;

                }
            );


            nameInput.addEventListener(
                "input",
                function () {

                    if (
                        slugManuallyEdited
                    ) {
                        return;
                    }


                    let value =
                        nameInput.value;


                    value =
                        value
                            .toLowerCase()
                            .normalize(
                                "NFD"
                            )
                            .replace(
                                /[\u0300-\u036f]/g,
                                ""
                            )
                            .replace(
                                /[^a-z0-9]+/g,
                                "-"
                            )
                            .replace(
                                /^-+|-+$/g,
                                ""
                            );


                    slugInput.value =
                        value;

                }
            );

        }


        /*
         * ================================================
         * IMAGE VALIDATION
         * ================================================
         */

        const imageInput =
            document.getElementById(
                "image"
            );


        if (
            imageInput
        ) {

            imageInput.addEventListener(
                "change",
                function () {

                    if (
                        !this.files
                        ||
                        !this.files[0]
                    ) {
                        return;
                    }


                    const file =
                        this.files[0];


                    const maxSize =
                        5 *
                        1024 *
                        1024;


                    if (
                        file.size
                        >
                        maxSize
                    ) {

                        alert(
                            "Ukuran gambar maksimal 5 MB."
                        );


                        this.value =
                            "";

                    }

                }
            );

        }


        /*
         * ================================================
         * ALERT AUTO HIDE
         * ================================================
         */

        setTimeout(
            function () {

                document
                    .querySelectorAll(
                        ".alert"
                    )
                    .forEach(
                        function (
                            alertElement
                        ) {

                            alertElement.style.transition =
                                "opacity .4s ease";

                            alertElement.style.opacity =
                                "0";


                            setTimeout(
                                function () {

                                    if (
                                        alertElement.parentNode
                                    ) {

                                        alertElement.parentNode
                                            .removeChild(
                                                alertElement
                                            );

                                    }

                                },
                                450
                            );

                        }
                    );

            },
            5000
        );

    }
);

</script>


</body>
</html>