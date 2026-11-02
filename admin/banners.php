<?php
// =========================================================
// admin/banners.php
// Management Banner / Hero Slider
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
| ADMIN DATA
|--------------------------------------------------------------------------
*/

$admin = current_admin();

$adminName =
    $admin['full_name']
    ??
    $_SESSION['admin_name']
    ??
    'Administrator';


/*
|--------------------------------------------------------------------------
| BASIC DATA
|--------------------------------------------------------------------------
*/

$siteName =
    trim(
        (string) get_setting(
            'site_name',
            'PT Star Parts Motor'
        )
    );

$siteLogo =
    trim(
        (string) get_setting(
            'site_logo',
            ''
        )
    );

$siteLogoUrl =
    $siteLogo !== ''
        ? media_url($siteLogo)
        : '';



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

$message =
    '';

$error =
    '';


/*
|--------------------------------------------------------------------------
| RETURN PATH
|--------------------------------------------------------------------------
*/

$listUrl =
    base_url(
        'admin/banners.php'
    );


/*
|--------------------------------------------------------------------------
| UPLOAD PATH
|--------------------------------------------------------------------------
*/

$bannerUploadFolder =
    'banners';


/*
|--------------------------------------------------------------------------
| DELETE LOCAL FILE
|--------------------------------------------------------------------------
*/

function delete_local_media_file(
    ?string $relativePath
): void {

    if (
        empty($relativePath)
    ) {
        return;
    }


    /*
     * Hanya hapus file lokal yang berada
     * di dalam folder uploads project.
     */

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


    $fullPath =
        dirname(__DIR__)
        . DIRECTORY_SEPARATOR
        . str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $relativePath
        );


    /*
     * Pastikan file benar-benar berada
     * dalam folder uploads.
     */

    $uploadsRoot =
        realpath(
            dirname(__DIR__)
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
        !str_starts_with(
            $fileRoot,
            $uploadsRoot .
            DIRECTORY_SEPARATOR
        )
        &&
        $fileRoot !== $uploadsRoot
    ) {

        return;
    }


    @unlink(
        $fileRoot
    );
}


/*
|--------------------------------------------------------------------------
| GET OLD BANNER
|--------------------------------------------------------------------------
*/

function get_banner_by_id(
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
             FROM banners
             WHERE id = ?
             LIMIT 1"
        );


    $stmt->execute([
        $id
    ]);


    $banner =
        $stmt->fetch();


    return $banner ?: null;
}


/*
|--------------------------------------------------------------------------
| POST ACTION
|--------------------------------------------------------------------------
*/

if (
    is_post()
) {

    $postAction =
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
         * SAVE BANNER
         * =====================================================
         */

        if (
            $postAction === 'save'
        ) {

            $id =
                (int)
                (
                    $_POST['id']
                    ??
                    0
                );


            $title =
                post_string(
                    'title'
                );


            $subtitle =
                post_string(
                    'subtitle'
                );


            $buttonText =
                post_string(
                    'button_text'
                );


            $buttonUrl =
                post_string(
                    'button_url'
                );


            $imageAlt = trim(post_string('image_alt'));


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
             * Existing file.
             */

            $existingBanner =
                $id > 0
                    ? get_banner_by_id(
                        $pdo,
                        $id
                    )
                    : null;


            if (
                $id > 0
                &&
                !$existingBanner
            ) {

                $error =
                    'Banner yang ingin diedit tidak ditemukan.';

            } elseif (
                $title === ''
            ) {

                $error =
                    'Judul banner wajib diisi.';

            } else {

                $imagePath =
                    $existingBanner['image']
                    ??
                    '';


                $videoPath =
                    $existingBanner['video']
                    ??
                    '';


                $newImageUploaded =
                    false;

                $newVideoUploaded =
                    false;


                /*
                 * =================================================
                 * IMAGE UPLOAD
                 * =================================================
                 */

                if (
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

                    $uploadImage =
                        upload_image(
                            $_FILES['image'],
                            $bannerUploadFolder
                        );


                    if (
                        !$uploadImage['success']
                    ) {

                        $error =
                            (string)
                            $uploadImage['message'];

                    } else {

                        $imagePath =
                            (string)
                            $uploadImage['path'];

                        $newImageUploaded =
                            true;
                    }
                }


                /*
                 * =================================================
                 * VIDEO UPLOAD
                 * =================================================
                 */

                if (
                    $error === ''
                    &&
                    isset(
                        $_FILES['video']
                    )
                    &&
                    (
                        $_FILES['video']['error']
                        ??
                        UPLOAD_ERR_NO_FILE
                    )
                    !==
                    UPLOAD_ERR_NO_FILE
                ) {

                    $uploadVideo =
                        upload_video(
                            $_FILES['video'],
                            $bannerUploadFolder
                        );


                    if (
                        !$uploadVideo['success']
                    ) {

                        /*
                         * Jika gambar baru sudah tersimpan
                         * tetapi video gagal, hapus gambar
                         * baru agar tidak meninggalkan file yatim.
                         */

                        if (
                            $newImageUploaded
                        ) {

                            delete_local_media_file(
                                $imagePath
                            );

                        }

                        $error =
                            (string)
                            $uploadVideo['message'];

                    } else {

                        $videoPath =
                            (string)
                            $uploadVideo['path'];

                        $newVideoUploaded =
                            true;
                    }
                }


                /*
                 * =================================================
                 * SAVE TO DATABASE
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
                                    "UPDATE banners
                                     SET
                                        title = ?,
                                        subtitle = ?,
                                        button_text = ?,
                                        button_url = ?,
                                        image = ?,
                                        image_alt = ?,
                                        video = ?,
                                        sort_order = ?,
                                        is_active = ?
                                     WHERE id = ?"
                                );


                            $stmt->execute([
                                $title,
                                $subtitle !== ''
                                    ? $subtitle
                                    : null,
                                $buttonText !== ''
                                    ? $buttonText
                                    : null,
                                $buttonUrl !== ''
                                    ? $buttonUrl
                                    : null,
                                $imagePath !== ''
                                    ? $imagePath
                                    : null,
                                $imageAlt !== '' ? $imageAlt : null,
                                $videoPath !== ''
                                    ? $videoPath
                                    : null,
                                $sortOrder,
                                $isActive,
                                $id
                            ]);


                            /*
                             * Hapus file lama jika ada
                             * file baru yang berhasil menggantikan.
                             */

                            if (
                                $newImageUploaded
                                &&
                                !empty(
                                    $existingBanner['image']
                                )
                                &&
                                $existingBanner['image']
                                !==
                                $imagePath
                            ) {

                                delete_local_media_file(
                                    $existingBanner['image']
                                );
                            }


                            if (
                                $newVideoUploaded
                                &&
                                !empty(
                                    $existingBanner['video']
                                )
                                &&
                                $existingBanner['video']
                                !==
                                $videoPath
                            ) {

                                delete_local_media_file(
                                    $existingBanner['video']
                                );
                            }


                            set_flash(
                                'success',
                                'Banner berhasil diperbarui.'
                            );

                        } else {

                            $stmt =
                                $pdo->prepare(
                                    "INSERT INTO banners
                                    (
                                        title,
                                        subtitle,
                                        button_text,
                                        button_url,
                                        image,
                                        image_alt,
                                        video,
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
                                        ?,
                                        ?,
                                        ?,
                                        ?
                                    )"
                                );


                            $stmt->execute([
                                $title,
                                $subtitle !== ''
                                    ? $subtitle
                                    : null,
                                $buttonText !== ''
                                    ? $buttonText
                                    : null,
                                $buttonUrl !== ''
                                    ? $buttonUrl
                                    : null,
                                $imagePath !== ''
                                    ? $imagePath
                                    : null,
                                $imageAlt !== '' ? $imageAlt : null,
                                $videoPath !== ''
                                    ? $videoPath
                                    : null,
                                $sortOrder,
                                $isActive
                            ]);


                            set_flash(
                                'success',
                                'Banner berhasil ditambahkan.'
                            );
                        }


                        redirect(
                            $listUrl
                        );


                    } catch (
                        Throwable $e
                    ) {

                        /*
                         * Jika database gagal menyimpan,
                         * hapus file baru yang terlanjur diupload.
                         */

                        if (
                            $newImageUploaded
                        ) {

                            delete_local_media_file(
                                $imagePath
                            );

                        }


                        if (
                            $newVideoUploaded
                        ) {

                            delete_local_media_file(
                                $videoPath
                            );

                        }


                        $error =
                            'Data banner gagal disimpan. Silakan coba kembali.';
                    }
                }
            }


            /*
             * Jika error,
             * arahkan halaman ke mode edit
             * untuk menampilkan form kembali.
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
         * DELETE BANNER
         * =====================================================
         */

        elseif (
            $postAction === 'delete'
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
                    'ID banner tidak valid.';

            } else {

                try {

                    $banner =
                        get_banner_by_id(
                            $pdo,
                            $id
                        );


                    if (
                        !$banner
                    ) {

                        $error =
                            'Banner tidak ditemukan.';

                    } else {

                        $stmt =
                            $pdo->prepare(
                                "DELETE FROM banners
                                 WHERE id = ?"
                            );


                        $stmt->execute([
                            $id
                        ]);


                        /*
                         * Hapus file terkait.
                         */

                        delete_local_media_file(
                            $banner['image']
                            ??
                            ''
                        );


                        delete_local_media_file(
                            $banner['video']
                            ??
                            ''
                        );


                        set_flash(
                            'success',
                            'Banner berhasil dihapus.'
                        );


                        redirect(
                            $listUrl
                        );
                    }


                } catch (
                    Throwable $e
                ) {

                    $error =
                        'Banner gagal dihapus.';
                }
            }
        }


        /*
         * =====================================================
         * TOGGLE STATUS
         * =====================================================
         */

        elseif (
            $postAction === 'toggle'
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
                    'ID banner tidak valid.';

            } else {

                try {

                    $banner =
                        get_banner_by_id(
                            $pdo,
                            $id
                        );


                    if (
                        !$banner
                    ) {

                        $error =
                            'Banner tidak ditemukan.';

                    } else {

                        $newStatus =
                            (int)
                            $banner['is_active']
                            ===
                            1
                                ? 0
                                : 1;


                        /*
                         * Jika mengaktifkan banner,
                         * batasi tidak terlalu banyak
                         * banner aktif bukan keharusan.
                         */

                        $stmt =
                            $pdo->prepare(
                                "UPDATE banners
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
                                ? 'Banner diaktifkan.'
                                : 'Banner dinonaktifkan.'
                        );


                        redirect(
                            $listUrl
                        );
                    }


                } catch (
                    Throwable $e
                ) {

                    $error =
                        'Status banner gagal diperbarui.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash =
    get_flash();


/*
|--------------------------------------------------------------------------
| GET BANNERS
|--------------------------------------------------------------------------
*/

$banners = [];

try {

    $stmt =
        $pdo->query(
            "SELECT *
             FROM banners
             ORDER BY
                sort_order ASC,
                id DESC"
        );


    $banners =
        $stmt->fetchAll();

} catch (
    Throwable $e
) {

    $banners = [];
}


/*
|--------------------------------------------------------------------------
| EDIT DATA
|--------------------------------------------------------------------------
*/

$editBanner = null;

if (
    $action === 'edit'
    &&
    $editId > 0
) {

    $editBanner =
        get_banner_by_id(
            $pdo,
            $editId
        );


    if (
        !$editBanner
        &&
        $error === ''
    ) {

        set_flash(
            'error',
            'Banner tidak ditemukan.'
        );


        redirect(
            $listUrl
        );
    }
}


/*
|--------------------------------------------------------------------------
| FORM DEFAULT
|--------------------------------------------------------------------------
*/

$formData = [

    'id' =>
        $editBanner['id']
        ??
        0,

    'title' =>
        $editBanner['title']
        ??
        '',

    'subtitle' =>
        $editBanner['subtitle']
        ??
        '',

    'button_text' =>
        $editBanner['button_text']
        ??
        'Lihat Selengkapnya',

    'button_url' =>
        $editBanner['button_url']
        ??
        'products.php',

    'image' =>
        $editBanner['image']
        ??
        '',

    'image_alt' =>
        $editBanner['image_alt']
        ??
        '',

    'video' =>
        $editBanner['video']
        ??
        '',

    'sort_order' =>
        $editBanner['sort_order']
        ??
        1,

    'is_active' =>
        isset(
            $editBanner['is_active']
        )
            ? (int)
                $editBanner['is_active']
            : 1

];


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$isEdit =
    $action === 'edit'
    &&
    $editBanner;

$pageHeading =
    $isEdit
        ? 'Edit Banner'
        : 'Kelola Banner';


/*
|--------------------------------------------------------------------------
| HELPER FOR FORM MEDIA URL
|--------------------------------------------------------------------------
*/

$formImageUrl =
    $formData['image'] !== ''
        ? media_url(
            $formData['image']
        )
        : '';

$formVideoUrl =
    $formData['video'] !== ''
        ? media_url(
            $formData['video']
        )
        : '';

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
            $pageHeading
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
         ICON
    ==================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >


    <!-- ===================================================
         ADMIN BANNER CSS
    ==================================================== -->

    <style>

        :root {

            --red:#d90000;
            --red-dark:#b30000;
            --black:#111111;
            --black-soft:#191919;
            --white:#ffffff;
            --bg:#f5f6f8;
            --text:#181818;
            --muted:#777777;
            --line:#e7e7e7;
            --green:#148343;
            --orange:#b96d00;
            --shadow:
                0 8px 30px rgba(0,0,0,.06);
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
            color:var(--text);
            background:var(--bg);
            font-family:var(--font-body);
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


        .admin-layout {
            min-height:100vh;
            display:flex;
        }


        /* =================================================
           SIDEBAR
        ================================================== */

        .sidebar {
            position:fixed;
            top:0;
            left:0;
            bottom:0;
            z-index:1000;

            width:255px;

            display:flex;
            flex-direction:column;

            color:rgba(255,255,255,.70);

            background:
                linear-gradient(
                    180deg,
                    #101010,
                    #181818
                );

            border-right:
                1px solid
                rgba(255,255,255,.05);

            transition:
                transform .3s ease;
        }


        .sidebar-brand {
            min-height:82px;
            display:flex;
            align-items:center;
            padding:0 22px;
            border-bottom:
                1px solid
                rgba(255,255,255,.07);
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
            color:white;
            font-family:var(--font);
            font-size:17px;
            font-weight:800;
        }


        .sidebar-fallback small {
            margin-top:6px;
            color:#ff3939;
            font-size:8px;
            font-weight:800;
            letter-spacing:.18em;
        }


        .sidebar-scroll {
            flex:1;
            overflow-y:auto;
            padding:18px 12px;
        }


        .sidebar-label {
            display:block;
            padding:
                0 12px 10px;

            color:
                rgba(255,255,255,.28);

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

            padding:0 13px;

            color:
                rgba(255,255,255,.60);

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
                rgba(255,255,255,.06);
        }


        .sidebar-link.active {
            color:#ffffff;
            background:var(--red);
            box-shadow:
                0 8px 20px
                rgba(217,0,0,.18);
        }


        .sidebar-user {
            padding:12px;
            border-top:
                1px solid
                rgba(255,255,255,.07);
        }


        .sidebar-user-box {
            display:flex;
            align-items:center;
            gap:10px;

            padding:10px;

            background:
                rgba(255,255,255,.045);

            border-radius:10px;
        }


        .sidebar-avatar {
            width:34px;
            height:34px;

            display:flex;
            align-items:center;
            justify-content:center;

            flex:0 0 auto;

            color:white;
            background:var(--red);
            border-radius:9px;

            font-size:11px;
            font-weight:800;
        }


        .sidebar-user-text {
            min-width:0;
        }


        .sidebar-user-text strong {
            display:block;

            max-width:150px;

            overflow:hidden;

            color:white;

            font-size:10px;
            white-space:nowrap;
            text-overflow:ellipsis;
        }


        .sidebar-user-text span {
            display:block;

            margin-top:2px;

            color:
                rgba(255,255,255,.36);

            font-size:8px;
        }


        /* =================================================
           MAIN
        ================================================== */

        .main {
            width:calc(100% - 255px);
            margin-left:255px;
            min-width:0;
        }


        .topbar {
            position:sticky;
            top:0;
            z-index:900;

            min-height:82px;

            display:flex;
            align-items:center;
            justify-content:space-between;

            gap:20px;

            padding:0 30px;

            background:
                rgba(255,255,255,.94);

            border-bottom:
                1px solid
                var(--line);

            backdrop-filter:
                blur(14px);
        }


        .topbar-left {
            display:flex;
            align-items:center;
            gap:15px;
        }


        .mobile-sidebar-button {
            display:none;

            width:42px;
            height:42px;

            align-items:center;
            justify-content:center;

            border:
                1px solid
                var(--line);

            border-radius:10px;

            background:white;
            color:var(--black);
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

            padding:0 12px;

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
                rgba(217,0,0,.25);
        }


        .user-pill {
            min-height:44px;

            display:flex;
            align-items:center;
            gap:9px;

            padding:
                4px 6px 4px 5px;

            background:white;

            border:
                1px solid
                var(--line);

            border-radius:11px;
        }


        .user-pill-avatar {
            width:33px;
            height:33px;

            display:flex;
            align-items:center;
            justify-content:center;

            color:white;
            background:var(--red);

            border-radius:9px;

            font-size:10px;
            font-weight:800;
        }


        .user-pill-name {
            display:flex;
            flex-direction:column;
        }


        .user-pill-name strong {
            font-size:9px;
            font-weight:700;
        }


        .user-pill-name span {
            margin-top:1px;
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
                rgba(217,0,0,.06);
        }


        /* =================================================
           CONTENT
        ================================================== */

        .content {
            padding:30px;
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

            background:var(--red);
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
            margin:6px 0 0;
            color:var(--muted);
            font-size:10px;
        }


        .add-button {
            min-height:43px;

            display:inline-flex;
            align-items:center;
            gap:8px;

            padding:0 15px;

            color:white;
            background:var(--red);

            border:0;
            border-radius:9px;

            font-size:10px;
            font-weight:800;

            box-shadow:
                0 9px 22px
                rgba(217,0,0,.18);

            transition:
                transform .2s ease,
                background .2s ease;
        }


        .add-button:hover {
            transform:translateY(-2px);
            background:var(--red-dark);
        }


        /* =================================================
           ALERT
        ================================================== */

        .alert {
            display:flex;
            align-items:flex-start;
            gap:9px;

            margin-bottom:18px;

            padding:12px 14px;

            border-radius:10px;

            font-size:10px;
            font-weight:600;
            line-height:1.55;
        }


        .alert-success {
            color:#146c38;
            background:#ecfaf1;
            border:1px solid #c9ecd6;
        }


        .alert-error {
            color:#921212;
            background:#fff1f1;
            border:1px solid #f2caca;
        }


        /* =================================================
           FORM PANEL
        ================================================== */

        .form-panel {
            margin-bottom:22px;
            padding:22px;

            background:white;

            border:
                1px solid
                var(--line);

            border-radius:15px;

            box-shadow:
                var(--shadow);
        }


        .panel-title {
            margin:0 0 18px;

            font-family:var(--font);
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
            margin-bottom:15px;
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


        .form-label small {
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

        }


        .form-control {
            min-height:45px;
            padding:0 12px;
            font-size:11px;
        }


        .form-textarea {
            min-height:105px;
            padding:11px 12px;
            resize:vertical;
            font-size:11px;
            line-height:1.7;
        }


        .form-control:focus,
        .form-textarea:focus {
            border-color:var(--red);

            box-shadow:
                0 0 0 4px
                rgba(217,0,0,.07);
        }


        .input-note {
            margin-top:5px;
            color:#999;
            font-size:8px;
            line-height:1.5;
        }


        .media-upload-grid {
            display:grid;

            grid-template-columns:
                1fr
                1fr;

            gap:15px;
        }


        .upload-box {
            padding:15px;

            background:#fafafa;

            border:
                1px dashed
                #d9d9d9;

            border-radius:11px;
        }


        .upload-title {
            display:flex;
            align-items:center;
            gap:7px;

            margin-bottom:9px;

            color:var(--black);

            font-size:10px;
            font-weight:800;
        }


        .upload-title i {
            color:var(--red);
        }


        .upload-box input[type="file"] {
            width:100%;
            font-size:9px;
        }


        .current-media {
            margin-top:9px;

            padding:9px;

            border:
                1px solid
                #eaeaea;

            border-radius:9px;

            background:white;
        }


        .current-media img {
            width:100%;
            max-height:180px;

            object-fit:cover;

            border-radius:7px;
        }


        .current-media video {
            width:100%;
            max-height:180px;

            display:block;

            background:black;

            border-radius:7px;
        }


        .current-media-name {
            margin-top:7px;

            overflow:hidden;

            color:#777;

            font-size:8px;

            white-space:nowrap;

            text-overflow:ellipsis;
        }


        .form-footer {
            display:flex;
            align-items:center;
            justify-content:space-between;

            gap:15px;

            margin-top:8px;
            padding-top:18px;

            border-top:
                1px solid
                #eeeeee;
        }


        .form-left-options {
            display:flex;
            align-items:center;
            flex-wrap:wrap;
            gap:15px;
        }


        .checkbox-label {
            display:inline-flex;
            align-items:center;
            gap:7px;

            color:#666;

            font-size:9px;
            font-weight:600;
        }


        .checkbox-label input {
            width:15px;
            height:15px;
            accent-color:var(--red);
        }


        .sort-box {
            width:110px;
        }


        .form-actions {
            display:flex;
            gap:8px;
        }


        .btn {
            min-height:42px;

            display:inline-flex;
            align-items:center;
            justify-content:center;

            gap:7px;

            padding:0 14px;

            border-radius:8px;

            font-size:9px;
            font-weight:800;

            transition:
                background .2s ease,
                color .2s ease,
                border-color .2s ease,
                transform .2s ease;
        }


        .btn:hover {
            transform:translateY(-1px);
        }


        .btn-primary {
            color:white;
            background:var(--red);
            border:1px solid var(--red);
        }


        .btn-primary:hover {
            background:var(--red-dark);
        }


        .btn-secondary {
            color:#555;
            background:white;
            border:1px solid #dedede;
        }


        .btn-secondary:hover {
            color:var(--red);
            border-color:
                rgba(217,0,0,.25);
        }


        /* =================================================
           BANNER LIST
        ================================================== */

        .list-panel {
            padding:22px;

            background:white;

            border:
                1px solid
                var(--line);

            border-radius:15px;

            box-shadow:
                var(--shadow);
        }


        .list-header {
            display:flex;
            align-items:flex-end;
            justify-content:space-between;

            gap:15px;

            margin-bottom:17px;
        }


        .list-header h2 {
            margin:0;

            font-family:var(--font);

            font-size:15px;

            font-weight:800;
        }


        .list-header p {
            margin:4px 0 0;

            color:var(--muted);

            font-size:9px;
        }


        .banner-list {
            display:flex;
            flex-direction:column;
            gap:10px;
        }


        .banner-item {
            display:grid;

            grid-template-columns:
                210px
                1fr
                auto;

            align-items:center;

            gap:17px;

            padding:12px;

            background:#fafafa;

            border:
                1px solid
                #ebebeb;

            border-radius:12px;

            transition:
                background .2s ease,
                border-color .2s ease,
                transform .2s ease;
        }


        .banner-item:hover {
            background:white;

            border-color:
                rgba(217,0,0,.15);

            transform:
                translateY(-1px);
        }


        .banner-preview {
            position:relative;

            aspect-ratio:
                16 / 8;

            overflow:hidden;

            background:#151515;

            border-radius:9px;
        }


        .banner-preview img {
            width:100%;
            height:100%;

            object-fit:cover;
        }


        .banner-preview video {
            width:100%;
            height:100%;

            object-fit:cover;
            background:#111;
        }


        .banner-media-type {
            position:absolute;

            top:8px;
            left:8px;

            display:inline-flex;
            align-items:center;
            gap:5px;

            padding:5px 7px;

            color:white;
            background:rgba(0,0,0,.60);

            border-radius:5px;

            font-size:7px;
            font-weight:800;

            text-transform:uppercase;
        }


        .banner-info {
            min-width:0;
        }


        .banner-title {
            margin:0 0 5px;

            color:var(--black);

            font-family:var(--font);

            font-size:13px;

            font-weight:800;

            line-height:1.35;
        }


        .banner-subtitle {
            margin:0;

            color:var(--muted);

            font-size:9px;

            line-height:1.65;

            display:
                -webkit-box;

            -webkit-line-clamp:2;

            -webkit-box-orient:vertical;

            overflow:hidden;
        }


        .banner-meta {
            display:flex;
            align-items:center;
            flex-wrap:wrap;
            gap:7px;

            margin-top:9px;
        }


        .badge {
            display:inline-flex;
            align-items:center;
            justify-content:center;

            padding:4px 7px;

            border-radius:5px;

            font-size:7px;

            font-weight:800;
        }


        .badge-green {
            color:#116c36;
            background:#eaf8ef;
        }


        .badge-gray {
            color:#757575;
            background:#eeeeee;
        }


        .badge-red {
            color:#9b1717;
            background:#fff0f0;
        }


        .banner-actions {
            display:flex;
            flex-direction:column;
            gap:6px;
        }


        .action-button {
            min-width:75px;
            min-height:32px;

            display:inline-flex;
            align-items:center;
            justify-content:center;

            gap:6px;

            padding:0 9px;

            background:white;

            border:
                1px solid
                #dfdfdf;

            border-radius:7px;

            font-size:8px;
            font-weight:700;

            transition:
                color .2s ease,
                border-color .2s ease,
                background .2s ease;
        }


        .action-button:hover {
            color:var(--red);

            border-color:
                rgba(217,0,0,.25);
        }


        .action-button.danger {
            color:#a51d1d;
        }


        .action-button.danger:hover {
            color:white;
            background:#bb1e1e;
            border-color:#bb1e1e;
        }


        .action-form {
            margin:0;
        }


        .empty-state {
            padding:50px 20px;

            text-align:center;

            color:#999;

            border:
                1px dashed
                #dcdcdc;

            border-radius:11px;
        }


        .empty-state i {
            display:block;

            margin-bottom:9px;

            color:#cecece;

            font-size:28px;
        }


        .empty-state strong {
            display:block;

            margin-bottom:5px;

            color:#555;

            font-family:var(--font);

            font-size:12px;
        }


        .empty-state span {
            font-size:9px;
        }


        /* =================================================
           OVERLAY
        ================================================== */

        .sidebar-overlay {
            position:fixed;
            inset:0;
            z-index:990;

            opacity:0;
            visibility:hidden;

            background:
                rgba(0,0,0,.50);

            transition:
                opacity .25s ease,
                visibility .25s ease;
        }


        .sidebar-overlay.show {
            opacity:1;
            visibility:visible;
        }


        /* =================================================
           RESPONSIVE
        ================================================== */

        @media (max-width:1000px) {

            .sidebar {
                transform:
                    translateX(-100%);
            }


            .sidebar.open {
                transform:
                    translateX(0);
            }


            .main {
                width:100%;
                margin-left:0;
            }


            .mobile-sidebar-button {
                display:flex;
            }


            .topbar {
                padding:0 20px;
            }


            .content {
                padding:20px;
            }

        }


        @media (max-width:760px) {

            .page-header {
                align-items:flex-start;
                flex-direction:column;
            }


            .form-grid,
            .media-upload-grid {
                grid-template-columns:1fr;
            }


            .form-group.full {
                grid-column:auto;
            }


            .form-footer {
                align-items:flex-start;
                flex-direction:column;
            }


            .form-actions {
                width:100%;
            }


            .form-actions .btn {
                flex:1;
            }


            .banner-item {
                grid-template-columns:1fr;
            }


            .banner-actions {
                flex-direction:row;
                flex-wrap:wrap;
            }


            .action-button {
                flex:1;
            }

        }


        @media (max-width:560px) {

            .topbar {
                min-height:70px;
                padding:0 13px;
            }


            .content {
                padding:13px;
            }


            .topbar-title strong {
                font-size:14px;
            }


            .view-site span {
                display:none;
            }


            .user-pill-name {
                display:none;
            }


            .user-pill {
                padding:4px;
            }


            .form-panel,
            .list-panel {
                padding:16px;
            }


            .page-title {
                font-size:23px;
            }


            .sort-box {
                width:100%;
            }

        }

    </style>

</head>


<body>


<div
    class="admin-layout"
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
                    class="sidebar-link active"
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
                        base_url(
                            'admin/categories.php'
                        )
                    ); ?>"
                    class="sidebar-link"
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

            </nav>

        </div>


        <div
            class="sidebar-user"
        >

            <div
                class="sidebar-user-box"
            >

                <div
                    class="sidebar-avatar"
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
                    class="sidebar-user-text"
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
        class="sidebar-overlay"
        id="sidebar-overlay"
    ></div>


    <!-- =================================================
         MAIN
    ================================================== -->

    <div
        class="main"
    >


        <!-- ===============================================
             TOPBAR
        ================================================ -->

        <header
            class="topbar"
        >

            <div
                class="topbar-left"
            >

                <button
                    type="button"
                    class="mobile-sidebar-button"
                    id="mobile-sidebar-button"
                    aria-label="Buka menu"
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
                        Banner / Hero Slider
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
                        class="user-pill-avatar"
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


        <!-- ===============================================
             CONTENT
        ================================================ -->

        <main
            class="content"
        >


            <!-- =========================================
                 PAGE HEADER
            ========================================== -->

            <div
                class="page-header"
            >

                <div>

                    <div
                        class="page-kicker"
                    >
                        CONTENT MANAGEMENT
                    </div>


                    <h1
                        class="page-title"
                    >
                        <?= e(
                            $pageHeading
                        ); ?>
                    </h1>


                    <p
                        class="page-description"
                    >
                        Kelola gambar dan video hero yang tampil
                        pada halaman utama website.
                    </p>

                </div>


                <?php if (
                    $action !== 'add'
                    &&
                    !$isEdit
                ): ?>

                    <a
                        href="<?= e(
                            base_url(
                                'admin/banners.php?action=add'
                            )
                        ); ?>"
                        class="add-button"
                    >

                        <i
                            class="fa-solid fa-plus"
                        ></i>

                        Tambah Banner

                    </a>

                <?php endif; ?>

            </div>


            <!-- =========================================
                 ALERT
            ========================================== -->

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


            <!-- =========================================
                 FORM
            ========================================== -->

            <?php if (
                $action === 'add'
                ||
                $isEdit
                ||
                $error !== ''
            ): ?>

                <section
                    class="form-panel"
                >

                    <h2
                        class="panel-title"
                    >

                        <?= $isEdit
                            ? 'Edit Banner'
                            : 'Tambah Banner Baru'; ?>

                    </h2>


                    <form
                        action=""
                        method="POST"
                        enctype="multipart/form-data"
                        id="banner-form"
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
                                 TITLE
                            ====================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="title"
                                >

                                    Judul Banner *

                                </label>


                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    class="form-control"
                                    value="<?= e(
                                        (string)
                                        $formData['title']
                                    ); ?>"
                                    maxlength="200"
                                    required
                                    placeholder="Contoh: Partner Distribusi Otomotif Terpercaya"
                                >

                            </div>


                            <!-- =====================================
                                 SORT
                            ====================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="sort_order"
                                >

                                    Urutan Banner

                                </label>


                                <input
                                    type="number"
                                    id="sort_order"
                                    name="sort_order"
                                    class="form-control"
                                    value="<?= e(
                                        (string)
                                        $formData['sort_order']
                                    ); ?>"
                                    min="0"
                                    max="9999"
                                >


                                <div
                                    class="input-note"
                                >
                                    Angka lebih kecil akan tampil lebih dahulu.
                                </div>

                            </div>


                            <!-- =====================================
                                 SUBTITLE
                            ====================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                    for="subtitle"
                                >

                                    Subtitle / Deskripsi

                                </label>


                                <textarea
                                    id="subtitle"
                                    name="subtitle"
                                    class="form-textarea"
                                    maxlength="2000"
                                    placeholder="Deskripsi singkat yang tampil di bawah judul hero."
                                ><?= e(
                                    (string)
                                    $formData['subtitle']
                                ); ?></textarea>

                            </div>


                            <!-- =====================================
                                 BUTTON TEXT
                            ====================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="button_text"
                                >

                                    Teks Tombol

                                </label>


                                <input
                                    type="text"
                                    id="button_text"
                                    name="button_text"
                                    class="form-control"
                                    value="<?= e(
                                        (string)
                                        $formData['button_text']
                                    ); ?>"
                                    maxlength="100"
                                    placeholder="Contoh: Lihat Produk"
                                >

                            </div>


                            <!-- =====================================
                                 BUTTON URL
                            ====================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="button_url"
                                >

                                    Link Tombol

                                </label>


                                <input
                                    type="text"
                                    id="button_url"
                                    name="button_url"
                                    class="form-control"
                                    value="<?= e(
                                        (string)
                                        $formData['button_url']
                                    ); ?>"
                                    maxlength="255"
                                    placeholder="Contoh: products.php"
                                >

                            </div>


                            <!-- =====================================
                                 MEDIA
                            ====================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                >

                                    Media Hero

                                    <small>
                                        Upload gambar, video, atau keduanya.
                                    </small>

                                </label>


                                <div
                                    class="media-upload-grid"
                                >


                                    <!-- IMAGE -->

                                    <div
                                        class="upload-box"
                                    >

                                        <div
                                            class="upload-title"
                                        >

                                            <i
                                                class="fa-regular fa-image"
                                            ></i>

                                            Gambar Banner

                                        </div>


                                        <input
                                            type="file"
                                            name="image"
                                            id="image"
                                            accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                        >


                                        <div
                                            class="input-note"
                                        >

                                            Format:
                                            JPG, PNG, WEBP, GIF.
                                            Maksimal 5 MB. Rasio gambar bebas; sistem menyesuaikan tampilan banner secara responsif.

                                        </div>

                                        <div style="margin-top:12px;">
                                            <label class="form-label" for="image_alt">ALT Text Banner</label>
                                            <input type="text" id="image_alt" name="image_alt" class="form-control" maxlength="255" value="<?= e((string)$formData['image_alt']); ?>" placeholder="Banner promosi produk otomotif Star Parts Motor">
                                            <div class="input-note">Deskripsikan isi banner secara natural agar aksesibilitas dan konteks gambar lebih baik.</div>
                                        </div>

                                        <?php if (
                                            $formImageUrl !== ''
                                        ): ?>

                                            <div
                                                class="current-media"
                                            >

                                                <img
                                                    src="<?= e(
                                                        $formImageUrl
                                                    ); ?>"
                                                    alt="Banner"
                                                >

                                                <div
                                                    class="current-media-name"
                                                >

                                                    <?= e(
                                                        basename(
                                                            (string)
                                                            $formData['image']
                                                        )
                                                    ); ?>

                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <!-- VIDEO -->

                                    <div
                                        class="upload-box"
                                    >

                                        <div
                                            class="upload-title"
                                        >

                                            <i
                                                class="fa-solid fa-video"
                                            ></i>

                                            Video Hero

                                        </div>


                                        <input
                                            type="file"
                                            name="video"
                                            id="video"
                                            accept=".mp4,.webm,.ogg,video/mp4,video/webm,video/ogg"
                                        >


                                        <div
                                            class="input-note"
                                        >

                                            Format:
                                            MP4, WEBM, OGG.
                                            Maksimal 100 MB.

                                        </div>


                                        <?php if (
                                            $formVideoUrl !== ''
                                        ): ?>

                                            <div
                                                class="current-media"
                                            >

                                                <video
                                                    controls
                                                    preload="metadata"
                                                >

                                                    <source
                                                        src="<?= e(
                                                            $formVideoUrl
                                                        ); ?>"
                                                    >

                                                </video>


                                                <div
                                                    class="current-media-name"
                                                >

                                                    <?= e(
                                                        basename(
                                                            (string)
                                                            $formData['video']
                                                        )
                                                    ); ?>

                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                             FORM FOOTER
                        ====================================== -->

                        <div
                            class="form-footer"
                        >

                            <div
                                class="form-left-options"
                            >

                                <label
                                    class="checkbox-label"
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

                                    Banner aktif

                                </label>


                                <?php if (
                                    $formData['id']
                                    >
                                    0
                                ): ?>

                                    <div
                                        class="sort-box"
                                    >

                                        <input
                                            type="number"
                                            name="sort_order"
                                            class="form-control"
                                            value="<?= e(
                                                (string)
                                                $formData['sort_order']
                                            ); ?>"
                                            min="0"
                                            max="9999"
                                        >

                                    </div>

                                <?php endif; ?>

                            </div>


                            <div
                                class="form-actions"
                            >

                                <a
                                    href="<?= e(
                                        $listUrl
                                    ); ?>"
                                    class="btn btn-secondary"
                                >

                                    <i
                                        class="fa-solid fa-xmark"
                                    ></i>

                                    Batal

                                </a>


                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    id="save-banner-button"
                                >

                                    <i
                                        class="fa-solid fa-floppy-disk"
                                    ></i>

                                    Simpan Banner

                                </button>

                            </div>

                        </div>

                    </form>

                </section>

            <?php endif; ?>


            <!-- =========================================
                 BANNER LIST
            ========================================== -->

            <section
                class="list-panel"
            >

                <div
                    class="list-header"
                >

                    <div>

                        <h2>
                            Daftar Banner
                        </h2>

                        <p>
                            <?= e(
                                (string)
                                count($banners)
                            ); ?>
                            banner tersimpan di database.
                        </p>

                    </div>

                </div>


                <?php if (
                    !empty($banners)
                ): ?>

                    <div
                        class="banner-list"
                    >

                        <?php foreach (
                            $banners
                            as $banner
                        ): ?>

                            <?php

                            $bannerId =
                                (int)
                                (
                                    $banner['id']
                                    ??
                                    0
                                );


                            $bannerTitle =
                                trim(
                                    (string)
                                    (
                                        $banner['title']
                                        ??
                                        ''
                                    )
                                );


                            $bannerSubtitle =
                                trim(
                                    (string)
                                    (
                                        $banner['subtitle']
                                        ??
                                        ''
                                    )
                                );


                            $bannerImage =
                                trim(
                                    (string)
                                    (
                                        $banner['image']
                                        ??
                                        ''
                                    )
                                );


                            $bannerVideo =
                                trim(
                                    (string)
                                    (
                                        $banner['video']
                                        ??
                                        ''
                                    )
                                );


                            $bannerActive =
                                (int)
                                (
                                    $banner['is_active']
                                    ??
                                    0
                                )
                                ===
                                1;


                            $bannerImageUrl =
                                $bannerImage !== ''
                                    ? media_url(
                                        $bannerImage
                                    )
                                    : '';


                            $bannerVideoUrl =
                                $bannerVideo !== ''
                                    ? media_url(
                                        $bannerVideo
                                    )
                                    : '';


                            ?>

                            <article
                                class="banner-item"
                            >

                                <!-- PREVIEW -->

                                <div
                                    class="banner-preview"
                                >

                                    <?php if (
                                        $bannerVideoUrl !== ''
                                    ): ?>

                                        <video
                                            muted
                                            preload="metadata"
                                        >

                                            <source
                                                src="<?= e(
                                                    $bannerVideoUrl
                                                ); ?>"
                                            >

                                        </video>


                                        <span
                                            class="banner-media-type"
                                        >

                                            <i
                                                class="fa-solid fa-video"
                                            ></i>

                                            VIDEO

                                        </span>

                                    <?php elseif (
                                        $bannerImageUrl !== ''
                                    ): ?>

                                        <img
                                            src="<?= e(
                                                $bannerImageUrl
                                            ); ?>"
                                            alt="<?= e(
                                                $bannerTitle
                                            ); ?>"
                                            loading="lazy"
                                        >


                                        <span
                                            class="banner-media-type"
                                        >

                                            <i
                                                class="fa-regular fa-image"
                                            ></i>

                                            IMAGE

                                        </span>

                                    <?php else: ?>

                                        <div
                                            style="
                                                width:100%;
                                                height:100%;
                                                display:flex;
                                                align-items:center;
                                                justify-content:center;
                                                color:#aaa;
                                                font-size:26px;
                                            "
                                        >

                                            <i
                                                class="fa-regular fa-images"
                                            ></i>

                                        </div>

                                        <span
                                            class="banner-media-type"
                                        >

                                            NO MEDIA

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- INFO -->

                                <div
                                    class="banner-info"
                                >

                                    <h3
                                        class="banner-title"
                                    >

                                        <?= e(
                                            $bannerTitle
                                        ); ?>

                                    </h3>


                                    <p
                                        class="banner-subtitle"
                                    >

                                        <?= e(
                                            $bannerSubtitle !== ''
                                                ? $bannerSubtitle
                                                : 'Tidak ada subtitle.'
                                        ); ?>

                                    </p>


                                    <div
                                        class="banner-meta"
                                    >

                                        <span
                                            class="badge <?= $bannerActive
                                                ? 'badge-green'
                                                : 'badge-gray'; ?>"
                                        >

                                            <i
                                                class="fa-solid fa-circle"
                                                style="font-size:5px;"
                                            ></i>

                                            <?= $bannerActive
                                                ? 'Aktif'
                                                : 'Nonaktif'; ?>

                                        </span>


                                        <span
                                            class="badge badge-red"
                                        >

                                            ORDER
                                            <?= e(
                                                (string)
                                                (
                                                    $banner['sort_order']
                                                    ??
                                                    0
                                                )
                                            ); ?>

                                        </span>


                                        <?php if (
                                            !empty(
                                                $banner['button_text']
                                            )
                                        ): ?>

                                            <span
                                                class="badge"
                                                style="
                                                    color:#555;
                                                    background:#eeeeee;
                                                "
                                            >

                                                <i
                                                    class="fa-solid fa-link"
                                                ></i>

                                                <?= e(
                                                    (string)
                                                    $banner['button_text']
                                                ); ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>


                                <!-- ACTIONS -->

                                <div
                                    class="banner-actions"
                                >

                                    <a
                                        href="<?= e(
                                            base_url(
                                                'admin/banners.php?action=edit&id='
                                                .
                                                $bannerId
                                            )
                                        ); ?>"
                                        class="action-button"
                                    >

                                        <i
                                            class="fa-solid fa-pen"
                                        ></i>

                                        Edit

                                    </a>


                                    <form
                                        method="POST"
                                        action=""
                                        class="action-form"
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
                                                $bannerId
                                            ); ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="action-button"
                                        >

                                            <i
                                                class="fa-solid <?= $bannerActive
                                                    ? 'fa-eye-slash'
                                                    : 'fa-eye'; ?>"
                                            ></i>

                                            <?= $bannerActive
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan'; ?>

                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        action=""
                                        class="action-form delete-form"
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
                                                $bannerId
                                            ); ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="action-button danger"
                                        >

                                            <i
                                                class="fa-solid fa-trash"
                                            ></i>

                                            Hapus

                                        </button>

                                    </form>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div
                        class="empty-state"
                    >

                        <i
                            class="fa-regular fa-images"
                        ></i>

                        <strong>
                            Belum ada banner
                        </strong>

                        <span>
                            Tambahkan banner pertama untuk Hero
                            landing page Star Parts Motor.
                        </span>

                    </div>

                <?php endif; ?>

            </section>


            <!-- =========================================
                 FOOTER NOTE
            ========================================== -->

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

                Banner Management

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
         * SIDEBAR MOBILE
         * ================================================
         */

        const sidebar =
            document.getElementById(
                "sidebar"
            );

        const sidebarButton =
            document.getElementById(
                "mobile-sidebar-button"
            );

        const sidebarOverlay =
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

            if (sidebarOverlay) {

                sidebarOverlay.classList.add(
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

            if (sidebarOverlay) {

                sidebarOverlay.classList.remove(
                    "show"
                );

            }

            document.body.style.overflow =
                "";
        }


        if (
            sidebarButton
        ) {

            sidebarButton.addEventListener(
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
            sidebarOverlay
        ) {

            sidebarOverlay.addEventListener(
                "click",
                closeSidebar
            );

        }


        /*
         * ================================================
         * ESC
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
         * DELETE CONFIRMATION
         * ================================================
         */

        document
            .querySelectorAll(
                ".delete-form"
            )
            .forEach(
                function (form) {

                    form.addEventListener(
                        "submit",
                        function (event) {

                            const confirmed =
                                window.confirm(
                                    "Yakin ingin menghapus banner ini? File gambar/video yang terkait juga akan dihapus."
                                );


                            if (
                                !confirmed
                            ) {

                                event.preventDefault();

                            }

                        }
                    );

                }
            );


        /*
         * ================================================
         * DOUBLE SUBMIT
         * ================================================
         */

        const bannerForm =
            document.getElementById(
                "banner-form"
            );

        const saveButton =
            document.getElementById(
                "save-banner-button"
            );


        if (
            bannerForm
            &&
            saveButton
        ) {

            bannerForm.addEventListener(
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
         * IMAGE PREVIEW BEFORE UPLOAD
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
         * VIDEO FILE VALIDATION
         * ================================================
         */

        const videoInput =
            document.getElementById(
                "video"
            );


        if (
            videoInput
        ) {

            videoInput.addEventListener(
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
                        100 *
                        1024 *
                        1024;


                    if (
                        file.size
                        >
                        maxSize
                    ) {

                        alert(
                            "Ukuran video maksimal 100 MB."
                        );


                        this.value =
                            "";

                    }

                }
            );

        }


        /*
         * ================================================
         * AUTO HIDE ALERT
         * ================================================
         */

        setTimeout(
            function () {

                document
                    .querySelectorAll(
                        ".alert"
                    )
                    .forEach(
                        function (alertElement) {

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