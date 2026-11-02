<?php
// =========================================================
// admin/dashboard.php
// Dashboard Panel Admin PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PROTECT ADMIN PAGE
|--------------------------------------------------------------------------
*/

require_admin();


/*
|--------------------------------------------------------------------------
| CURRENT ADMIN
|--------------------------------------------------------------------------
*/

$admin =
    current_admin();

$adminName =
    $admin['full_name']
    ??
    $_SESSION['admin_name']
    ??
    'Administrator';

$adminRole =
    $admin['role']
    ??
    $_SESSION['admin_role']
    ??
    'admin';


/*
|--------------------------------------------------------------------------
| HELPER QUERY
|--------------------------------------------------------------------------
*/

function dashboard_count(
    PDO $pdo,
    string $table,
    string $where = ''
): int {

    /*
     * Daftar tabel yang diizinkan.
     * Tidak menerima nama tabel bebas dari user.
     */

    $allowedTables = [
        'products',
        'product_categories',
        'brands',
        'posts',
        'banners',
        'media',
        'contact_messages',
        'locations',
        'pages',
        'testimonials',
        'admin_users'
    ];


    if (
        !in_array(
            $table,
            $allowedTables,
            true
        )
    ) {
        return 0;
    }


    $sql =
        "SELECT COUNT(*) FROM `{$table}`";


    if (
        $where !== ''
    ) {
        $sql .= " WHERE " . $where;
    }


    try {

        return (int) $pdo
            ->query($sql)
            ->fetchColumn();

    } catch (
        Throwable $e
    ) {

        return 0;
    }
}


/*
|--------------------------------------------------------------------------
| DATABASE STATISTICS
|--------------------------------------------------------------------------
*/

$totalProducts =
    dashboard_count(
        $pdo,
        'products',
        "is_active = 1"
    );

$totalCategories =
    dashboard_count(
        $pdo,
        'product_categories',
        "is_active = 1"
    );

$totalBrands =
    dashboard_count(
        $pdo,
        'brands',
        "is_active = 1"
    );

$totalPosts =
    dashboard_count(
        $pdo,
        'posts',
        "status = 'published'"
    );

$totalBanners =
    dashboard_count(
        $pdo,
        'banners',
        "is_active = 1"
    );

$totalMedia =
    dashboard_count(
        $pdo,
        'media'
    );

$totalMessages =
    dashboard_count(
        $pdo,
        'contact_messages'
    );

$newMessages =
    dashboard_count(
        $pdo,
        'contact_messages',
        "status = 'new'"
    );

$totalLocations =
    dashboard_count(
        $pdo,
        'locations',
        "is_active = 1"
    );

$totalPages =
    dashboard_count(
        $pdo,
        'pages',
        "status = 'published'"
    );


/*
|--------------------------------------------------------------------------
| RECENT MESSAGES
|--------------------------------------------------------------------------
*/

$recentMessages = [];

try {

    $stmt =
        $pdo->query(
            "SELECT
                id,
                name,
                company,
                email,
                phone,
                subject,
                status,
                created_at
             FROM contact_messages
             ORDER BY id DESC
             LIMIT 6"
        );

    $recentMessages =
        $stmt->fetchAll();

} catch (
    Throwable $e
) {

    $recentMessages = [];
}


/*
|--------------------------------------------------------------------------
| RECENT POSTS
|--------------------------------------------------------------------------
*/

$recentPosts = [];

try {

    $stmt =
        $pdo->query(
            "SELECT
                id,
                title,
                status,
                published_at,
                created_at
             FROM posts
             ORDER BY id DESC
             LIMIT 5"
        );

    $recentPosts =
        $stmt->fetchAll();

} catch (
    Throwable $e
) {

    $recentPosts = [];
}


/*
|--------------------------------------------------------------------------
| RECENT PRODUCTS
|--------------------------------------------------------------------------
*/

$recentProducts = [];

try {

    $stmt =
        $pdo->query(
            "SELECT
                p.id,
                p.name,
                p.image,
                p.is_active,
                p.created_at,
                c.name AS category_name
             FROM products p
             LEFT JOIN product_categories c
                ON c.id = p.category_id
             ORDER BY p.id DESC
             LIMIT 5"
        );

    $recentProducts =
        $stmt->fetchAll();

} catch (
    Throwable $e
) {

    $recentProducts = [];
}


/*
|--------------------------------------------------------------------------
| SITE INFORMATION
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

$siteTagline =
    trim(
        (string) get_setting(
            'site_tagline',
            'Operational Excellence'
        )
    );


/*
|--------------------------------------------------------------------------
| TODAY
|--------------------------------------------------------------------------
*/

$today =
    date('d F Y');


/*
|--------------------------------------------------------------------------
| GREETING
|--------------------------------------------------------------------------
*/

$currentHour =
    (int) date('H');

if (
    $currentHour < 11
) {

    $greeting =
        'Selamat pagi';

} elseif (
    $currentHour < 15
) {

    $greeting =
        'Selamat siang';

} elseif (
    $currentHour < 18
) {

    $greeting =
        'Selamat sore';

} else {

    $greeting =
        'Selamat malam';
}


?>
<!DOCTYPE html>
<html lang="id">

<head>

    <!-- ===================================================
         META
    ==================================================== -->

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
        Dashboard Admin | <?= e($siteName); ?>
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
         DASHBOARD CSS
    ==================================================== -->

    <style>

        :root {

            --red:
                #d90000;

            --red-dark:
                #b30000;

            --black:
                #111111;

            --black-2:
                #1b1b1b;

            --white:
                #ffffff;

            --bg:
                #f5f6f8;

            --text:
                #191919;

            --muted:
                #727272;

            --line:
                #e9e9e9;

            --green:
                #139447;

            --orange:
                #d97b00;

            --blue:
                #2563eb;

            --shadow:
                0 8px 28px
                rgba(0, 0, 0, .07);

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
            box-sizing:
                border-box;
        }


        html,
        body {
            margin:
                0;

            padding:
                0;

            min-height:
                100%;
        }


        body {

            font-family:
                var(--font-body);

            color:
                var(--text);

            background:
                var(--bg);

            overflow-x:
                hidden;
        }


        a {
            color:
                inherit;

            text-decoration:
                none;
        }


        button,
        input {
            font:
                inherit;
        }


        button {
            cursor:
                pointer;
        }


        /* ===============================================
           APP LAYOUT
        ================================================ */

        .admin-app {

            min-height:
                100vh;

            display:
                flex;
        }


        /* ===============================================
           SIDEBAR
        ================================================ */

        .admin-sidebar {

            position:
                fixed;

            top:
                0;

            left:
                0;

            bottom:
                0;

            z-index:
                1000;

            width:
                255px;

            display:
                flex;

            flex-direction:
                column;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .74
                );

            background:
                linear-gradient(
                    180deg,
                    #111111 0%,
                    #181818 100%
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

            min-height:
                82px;

            display:
                flex;

            align-items:
                center;

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

            width:
                auto;

            max-width:
                185px;

            max-height:
                52px;

            object-fit:
                contain;
        }


        .sidebar-fallback {

            display:
                flex;

            flex-direction:
                column;

            line-height:
                1;
        }


        .sidebar-fallback strong {

            color:
                #ffffff;

            font-family:
                var(--font);

            font-size:
                17px;

            font-weight:
                800;
        }


        .sidebar-fallback small {

            margin-top:
                6px;

            color:
                #ff3636;

            font-size:
                8px;

            font-weight:
                800;

            letter-spacing:
                .18em;
        }


        .sidebar-menu {

            flex:
                1 1 auto;

            overflow-y:
                auto;

            padding:
                18px 12px;
        }


        .sidebar-label {

            display:
                block;

            padding:
                0 12px 10px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .27
                );

            font-size:
                9px;

            font-weight:
                800;

            letter-spacing:
                .12em;

            text-transform:
                uppercase;
        }


        .sidebar-nav {

            display:
                flex;

            flex-direction:
                column;

            gap:
                4px;
        }


        .sidebar-link {

            position:
                relative;

            min-height:
                46px;

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            padding:
                0 13px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .62
                );

            border-radius:
                10px;

            font-size:
                11px;

            font-weight:
                600;

            transition:
                background .2s ease,
                color .2s ease,
                transform .2s ease;
        }


        .sidebar-link i {

            width:
                18px;

            text-align:
                center;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .42
                );

            transition:
                color .2s ease;
        }


        .sidebar-link:hover {

            color:
                #ffffff;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .06
                );
        }


        .sidebar-link:hover i {

            color:
                #ff3737;
        }


        .sidebar-link.active {

            color:
                #ffffff;

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


        .sidebar-link.active i {

            color:
                #ffffff;
        }


        .sidebar-link .notification {

            margin-left:
                auto;

            min-width:
                19px;

            height:
                19px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:
                var(--red);

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;
        }


        .sidebar-link.active .notification {

            color:
                var(--red);

            background:
                #ffffff;
        }


        .sidebar-bottom {

            padding:
                12px;

            border-top:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .07
                );
        }


        .sidebar-user-mini {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            padding:
                10px;

            border-radius:
                10px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );
        }


        .sidebar-user-avatar {

            width:
                34px;

            height:
                34px;

            flex:
                0 0 auto;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:
                var(--red);

            border-radius:
                9px;

            font-size:
                12px;

            font-weight:
                800;
        }


        .sidebar-user-info {

            min-width:
                0;
        }


        .sidebar-user-info strong {

            display:
                block;

            max-width:
                155px;

            overflow:
                hidden;

            color:
                #ffffff;

            font-size:
                10px;

            font-weight:
                700;

            white-space:
                nowrap;

            text-overflow:
                ellipsis;
        }


        .sidebar-user-info span {

            display:
                block;

            margin-top:
                2px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .35
                );

            font-size:
                8px;

            text-transform:
                capitalize;
        }


        /* ===============================================
           MAIN
        ================================================ */

        .admin-main {

            min-width:
                0;

            width:
                calc(
                    100% - 255px
                );

            margin-left:
                255px;
        }


        /* ===============================================
           TOP HEADER
        ================================================ */

        .admin-topbar {

            position:
                sticky;

            top:
                0;

            z-index:
                900;

            min-height:
                82px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

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

            display:
                flex;

            align-items:
                center;

            gap:
                15px;
        }


        .mobile-sidebar-toggle {

            display:
                none;

            width:
                42px;

            height:
                42px;

            align-items:
                center;

            justify-content:
                center;

            color:
                var(--black);

            background:
                #ffffff;

            border:
                1px solid
                var(--line);

            border-radius:
                10px;
        }


        .topbar-title {

            display:
                flex;

            flex-direction:
                column;
        }


        .topbar-title span {

            color:
                var(--muted);

            font-size:
                9px;

            font-weight:
                600;
        }


        .topbar-title strong {

            margin-top:
                3px;

            color:
                var(--black);

            font-family:
                var(--font);

            font-size:
                16px;

            font-weight:
                800;
        }


        .topbar-right {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;
        }


        .view-site-button {

            min-height:
                40px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                8px;

            padding:
                0 13px;

            color:
                var(--black);

            background:
                #ffffff;

            border:
                1px solid
                var(--line);

            border-radius:
                9px;

            font-size:
                10px;

            font-weight:
                700;

            transition:
                color .2s ease,
                border-color .2s ease;
        }


        .view-site-button:hover {

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


        .topbar-user {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            min-height:
                44px;

            padding:
                4px 10px 4px 5px;

            border:
                1px solid
                var(--line);

            background:
                #ffffff;

            border-radius:
                11px;
        }


        .topbar-avatar {

            width:
                33px;

            height:
                33px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:
                var(--red);

            border-radius:
                9px;

            font-size:
                10px;

            font-weight:
                800;
        }


        .topbar-user-info {

            display:
                flex;

            flex-direction:
                column;
        }


        .topbar-user-info strong {

            font-size:
                10px;

            font-weight:
                700;
        }


        .topbar-user-info small {

            margin-top:
                1px;

            color:
                var(--muted);

            font-size:
                8px;
        }


        .topbar-logout {

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            width:
                35px;

            height:
                35px;

            margin-left:
                3px;

            color:
                #777777;

            border:
                0;

            background:
                transparent;

            border-radius:
                8px;

            transition:
                color .2s ease,
                background .2s ease;
        }


        .topbar-logout:hover {

            color:
                var(--red);

            background:
                rgba(
                    217,
                    0,
                    0,
                    .06
                );
        }


        /* ===============================================
           CONTENT
        ================================================ */

        .admin-content {

            padding:
                30px;
        }


        /* ===============================================
           WELCOME
        ================================================ */

        .welcome-card {

            position:
                relative;

            min-height:
                175px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                30px;

            padding:
                32px;

            color:
                #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #111111 0%,
                    #1c1c1c 65%,
                    #320000 100%
                );

            border-radius:
                20px;

            overflow:
                hidden;

            box-shadow:
                var(--shadow);
        }


        .welcome-card::before {

            content:
                "";

            position:
                absolute;

            width:
                280px;

            height:
                280px;

            right:
                -105px;

            top:
                -175px;

            border:
                52px solid
                rgba(
                    217,
                    0,
                    0,
                    .18
                );

            border-radius:
                50%;
        }


        .welcome-card::after {

            content:
                "";

            position:
                absolute;

            right:
                35px;

            bottom:
                -100px;

            width:
                210px;

            height:
                210px;

            border:
                2px solid
                rgba(
                    255,
                    255,
                    255,
                    .05
                );

            border-radius:
                50%;
        }


        .welcome-content {

            position:
                relative;

            z-index:
                2;

            max-width:
                800px;
        }


        .welcome-kicker {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                8px;

            margin-bottom:
                10px;

            color:
                #ff3b3b;

            font-size:
                9px;

            font-weight:
                800;

            letter-spacing:
                .14em;

            text-transform:
                uppercase;
        }


        .welcome-kicker::before {

            content:
                "";

            width:
                26px;

            height:
                3px;

            background:
                var(--red);

            border-radius:
                999px;
        }


        .welcome-title {

            margin:
                0 0 8px;

            color:
                #ffffff;

            font-family:
                var(--font);

            font-size:
                clamp(
                    25px,
                    3vw,
                    36px
                );

            line-height:
                1.15;

            font-weight:
                800;
        }


        .welcome-title span {

            color:
                #ff3434;
        }


        .welcome-description {

            margin:
                0;

            max-width:
                650px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .58
                );

            font-size:
                11px;

            line-height:
                1.7;
        }


        .welcome-date {

            position:
                relative;

            z-index:
                2;

            flex:
                0 0 auto;

            padding:
                12px 15px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .72
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .10
                );

            border-radius:
                10px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .05
                );

            font-size:
                9px;

            font-weight:
                600;

            white-space:
                nowrap;
        }


        /* ===============================================
           SECTION TITLE
        ================================================ */

        .section-heading {

            display:
                flex;

            align-items:
                flex-end;

            justify-content:
                space-between;

            gap:
                20px;

            margin:
                34px 0 18px;
        }


        .section-heading-left {

            min-width:
                0;
        }


        .section-heading-kicker {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

            margin-bottom:
                5px;

            color:
                var(--red);

            font-size:
                8px;

            font-weight:
                800;

            letter-spacing:
                .13em;

            text-transform:
                uppercase;
        }


        .section-heading-kicker::before {

            content:
                "";

            width:
                20px;

            height:
                2px;

            background:
                var(--red);

            border-radius:
                999px;
        }


        .section-heading h2 {

            margin:
                0;

            color:
                var(--black);

            font-family:
                var(--font);

            font-size:
                19px;

            font-weight:
                800;
        }


        .section-heading p {

            margin:
                5px 0 0;

            color:
                var(--muted);

            font-size:
                10px;
        }


        .section-action {

            min-height:
                37px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                7px;

            padding:
                0 11px;

            color:
                var(--black);

            background:
                #ffffff;

            border:
                1px solid
                var(--line);

            border-radius:
                8px;

            font-size:
                9px;

            font-weight:
                700;

            white-space:
                nowrap;

            transition:
                color .2s ease,
                border-color .2s ease;
        }


        .section-action:hover {

            color:
                var(--red);

            border-color:
                rgba(
                    217,
                    0,
                    0,
                    .24
                );
        }


        /* ===============================================
           STAT CARDS
        ================================================ */

        .stats-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    4,
                    1fr
                );

            gap:
                16px;
        }


        .stat-card {

            position:
                relative;

            padding:
                20px;

            background:
                #ffffff;

            border:
                1px solid
                var(--line);

            border-radius:
                15px;

            box-shadow:
                0 5px 20px
                rgba(
                    0,
                    0,
                    0,
                    .035
                );

            overflow:
                hidden;

            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }


        .stat-card:hover {

            transform:
                translateY(
                    -3px
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


        .stat-card-top {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                12px;
        }


        .stat-icon {

            width:
                41px;

            height:
                41px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                var(--red);

            background:
                rgba(
                    217,
                    0,
                    0,
                    .08
                );

            border-radius:
                10px;

            font-size:
                15px;
        }


        .stat-icon.black {

            color:
                var(--black);

            background:
                rgba(
                    0,
                    0,
                    0,
                    .06
                );
        }


        .stat-icon.green {

            color:
                var(--green);

            background:
                rgba(
                    19,
                    148,
                    71,
                    .08
                );
        }


        .stat-icon.blue {

            color:
                var(--blue);

            background:
                rgba(
                    37,
                    99,
                    235,
                    .08
                );
        }


        .stat-body {

            margin-top:
                17px;
        }


        .stat-body strong {

            display:
                block;

            color:
                var(--black);

            font-family:
                var(--font);

            font-size:
                27px;

            font-weight:
                800;

            line-height:
                1;
        }


        .stat-body span {

            display:
                block;

            margin-top:
                6px;

            color:
                var(--muted);

            font-size:
                9px;

            font-weight:
                600;
        }


        .stat-card-footer {

            display:
                flex;

            align-items:
                center;

            gap:
                5px;

            margin-top:
                18px;

            color:
                #9a9a9a;

            font-size:
                8px;
        }


        .stat-card-footer i {

            color:
                var(--red);
        }


        /* ===============================================
           MAIN GRID
        ================================================ */

        .dashboard-grid {

            display:
                grid;

            grid-template-columns:
                1.3fr
                .7fr;

            gap:
                16px;
        }


        .panel {

            min-width:
                0;

            padding:
                21px;

            background:
                #ffffff;

            border:
                1px solid
                var(--line);

            border-radius:
                15px;

            box-shadow:
                0 5px 20px
                rgba(
                    0,
                    0,
                    0,
                    .035
                );
        }


        .panel-heading {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            margin-bottom:
                17px;
        }


        .panel-heading h3 {

            margin:
                0;

            font-family:
                var(--font);

            font-size:
                14px;

            font-weight:
                800;
        }


        .panel-heading a {

            color:
                var(--red);

            font-size:
                9px;

            font-weight:
                700;
        }


        /* ===============================================
           QUICK ACTIONS
        ================================================ */

        .quick-actions {

            display:
                grid;

            grid-template-columns:
                repeat(
                    2,
                    1fr
                );

            gap:
                10px;
        }


        .quick-action {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            padding:
                12px;

            color:
                var(--black);

            background:
                #fafafa;

            border:
                1px solid
                #eeeeee;

            border-radius:
                10px;

            transition:
                transform .2s ease,
                background .2s ease,
                border-color .2s ease;
        }


        .quick-action:hover {

            transform:
                translateY(
                    -2px
                );

            background:
                #ffffff;

            border-color:
                rgba(
                    217,
                    0,
                    0,
                    .20
                );
        }


        .quick-action-icon {

            width:
                36px;

            height:
                36px;

            flex:
                0 0 auto;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                var(--red);

            background:
                rgba(
                    217,
                    0,
                    0,
                    .07
                );

            border-radius:
                9px;
        }


        .quick-action-content {

            min-width:
                0;

            display:
                flex;

            flex-direction:
                column;
        }


        .quick-action-content strong {

            font-size:
                10px;

            font-weight:
                700;
        }


        .quick-action-content span {

            margin-top:
                2px;

            color:
                var(--muted);

            font-size:
                8px;
        }


        /* ===============================================
           MESSAGE LIST
        ================================================ */

        .message-list {

            display:
                flex;

            flex-direction:
                column;

            gap:
                1px;
        }


        .message-item {

            display:
                flex;

            align-items:
                flex-start;

            gap:
                11px;

            padding:
                12px 0;

            border-bottom:
                1px solid
                #f0f0f0;
        }


        .message-item:last-child {

            border-bottom:
                0;

            padding-bottom:
                0;
        }


        .message-avatar {

            width:
                35px;

            height:
                35px;

            flex:
                0 0 auto;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:
                var(--black);

            border-radius:
                10px;

            font-size:
                10px;

            font-weight:
                800;
        }


        .message-item.unread
        .message-avatar {

            background:
                var(--red);
        }


        .message-content {

            min-width:
                0;

            flex:
                1 1 auto;
        }


        .message-name-row {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                10px;
        }


        .message-name {

            overflow:
                hidden;

            color:
                var(--black);

            font-size:
                10px;

            font-weight:
                700;

            white-space:
                nowrap;

            text-overflow:
                ellipsis;
        }


        .message-date {

            flex:
                0 0 auto;

            color:
                #a2a2a2;

            font-size:
                8px;
        }


        .message-subject {

            margin-top:
                3px;

            overflow:
                hidden;

            color:
                var(--muted);

            font-size:
                8px;

            white-space:
                nowrap;

            text-overflow:
                ellipsis;
        }


        .message-status {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                5px;

            margin-top:
                6px;

            padding:
                4px 7px;

            border-radius:
                5px;

            font-size:
                7px;

            font-weight:
                800;

            text-transform:
                uppercase;
        }


        .status-new {

            color:
                #9d1d1d;

            background:
                #fff0f0;
        }


        .status-read {

            color:
                #2255a7;

            background:
                #edf4ff;
        }


        .status-replied {

            color:
                #11753b;

            background:
                #ecf9f1;
        }


        .status-closed {

            color:
                #707070;

            background:
                #f1f1f1;
        }


        /* ===============================================
           TABLE
        ================================================ */

        .table-wrapper {

            width:
                100%;

            overflow-x:
                auto;
        }


        .admin-table {

            width:
                100%;

            border-collapse:
                collapse;

            min-width:
                550px;
        }


        .admin-table th {

            padding:
                10px 8px;

            color:
                #909090;

            background:
                #fafafa;

            border-bottom:
                1px solid
                var(--line);

            text-align:
                left;

            font-size:
                8px;

            font-weight:
                800;

            letter-spacing:
                .06em;

            text-transform:
                uppercase;
        }


        .admin-table td {

            padding:
                11px 8px;

            color:
                var(--text);

            border-bottom:
                1px solid
                #f0f0f0;

            font-size:
                9px;

            vertical-align:
                middle;
        }


        .admin-table tr:last-child td {

            border-bottom:
                0;
        }


        .product-cell {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;
        }


        .product-thumb {

            width:
                37px;

            height:
                37px;

            flex:
                0 0 auto;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            overflow:
                hidden;

            color:
                #aaaaaa;

            background:
                #f2f2f2;

            border-radius:
                7px;
        }


        .product-thumb img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;
        }


        .product-cell-info {

            min-width:
                0;
        }


        .product-cell-info strong {

            display:
                block;

            max-width:
                210px;

            overflow:
                hidden;

            font-size:
                9px;

            font-weight:
                700;

            white-space:
                nowrap;

            text-overflow:
                ellipsis;
        }


        .product-cell-info span {

            display:
                block;

            margin-top:
                2px;

            color:
                var(--muted);

            font-size:
                7px;
        }


        .status-badge {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            min-width:
                54px;

            padding:
                4px 7px;

            border-radius:
                5px;

            font-size:
                7px;

            font-weight:
                800;
        }


        .status-active {

            color:
                #11753b;

            background:
                #ecf9f1;
        }


        .status-inactive {

            color:
                #8a8a8a;

            background:
                #f2f2f2;
        }


        .status-published {

            color:
                #11753b;

            background:
                #ecf9f1;
        }


        .status-draft {

            color:
                #8d5c0b;

            background:
                #fff7df;
        }


        /* ===============================================
           EMPTY STATE
        ================================================ */

        .empty-dashboard {

            padding:
                30px 15px;

            text-align:
                center;

            color:
                #999999;
        }


        .empty-dashboard i {

            display:
                block;

            margin-bottom:
                9px;

            color:
                #cccccc;

            font-size:
                25px;
        }


        .empty-dashboard span {

            font-size:
                9px;

        }


        /* ===============================================
           SITE STATUS
        ================================================ */

        .status-summary {

            display:
                grid;

            grid-template-columns:
                repeat(
                    2,
                    1fr
                );

            gap:
                10px;
        }


        .status-summary-item {

            padding:
                14px;

            background:
                #fafafa;

            border:
                1px solid
                #eeeeee;

            border-radius:
                10px;
        }


        .status-summary-item span {

            display:
                block;

            color:
                var(--muted);

            font-size:
                8px;
        }


        .status-summary-item strong {

            display:
                block;

            margin-top:
                5px;

            color:
                var(--black);

            font-family:
                var(--font);

            font-size:
                20px;

            font-weight:
                800;
        }


        /* ===============================================
           OVERLAY
        ================================================ */

        .sidebar-overlay {

            position:
                fixed;

            inset:
                0;

            z-index:
                990;

            background:
                rgba(
                    0,
                    0,
                    0,
                    .50
                );

            opacity:
                0;

            visibility:
                hidden;

            transition:
                opacity .25s ease,
                visibility .25s ease;
        }


        .sidebar-overlay.is-visible {

            opacity:
                1;

            visibility:
                visible;
        }


        /* ===============================================
           RESPONSIVE
        ================================================ */

        @media (max-width: 1200px) {

            .admin-sidebar {

                width:
                    230px;
            }


            .admin-main {

                width:
                    calc(
                        100% - 230px
                    );

                margin-left:
                    230px;
            }


            .stats-grid {

                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }


            .dashboard-grid {

                grid-template-columns:
                    1fr;
            }

        }


        @media (max-width: 900px) {

            .admin-sidebar {

                transform:
                    translateX(
                        -100%
                    );
            }


            .admin-sidebar.is-open {

                transform:
                    translateX(
                        0
                    );
            }


            .admin-main {

                width:
                    100%;

                margin-left:
                    0;
            }


            .mobile-sidebar-toggle {

                display:
                    flex;
            }


            .admin-topbar {

                padding:
                    0 20px;
            }


            .admin-content {

                padding:
                    20px;
            }

        }


        @media (max-width: 650px) {

            .stats-grid {

                grid-template-columns:
                    1fr;
            }


            .welcome-card {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                padding:
                    24px;
            }


            .welcome-date {

                white-space:
                    normal;
            }


            .section-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .topbar-user-info,
            .view-site-button span {

                display:
                    none;
            }


            .topbar-user {

                padding:
                    4px;
            }


            .topbar-logout {

                margin:
                    0;
            }


            .quick-actions {

                grid-template-columns:
                    1fr;
            }


            .status-summary {

                grid-template-columns:
                    1fr;
            }

        }


        @media (max-width: 450px) {

            .admin-topbar {

                min-height:
                    70px;

                padding:
                    0 13px;
            }


            .admin-content {

                padding:
                    13px;
            }


            .topbar-title strong {

                font-size:
                    14px;
            }


            .welcome-card {

                border-radius:
                    15px;
            }


            .panel {

                padding:
                    16px;
            }


            .stat-card {

                padding:
                    17px;
            }

        }

    </style>

</head>


<body>


<div
    class="admin-app"
>


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside
        class="admin-sidebar"
        id="admin-sidebar"
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
            class="sidebar-menu"
        >

            <span
                class="sidebar-label"
            >
                MAIN MENU
            </span>


            <nav
                class="sidebar-nav"
            >

                <a
                    href="<?= e(
                        base_url(
                            'admin/dashboard.php'
                        )
                    ); ?>"
                    class="sidebar-link active"
                >

                    <i
                        class="fa-solid fa-gauge-high"
                    ></i>

                    <span>
                        Dashboard
                    </span>

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

                    <span>
                        Banner
                    </span>

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

                    <span>
                        Produk
                    </span>

                    <span class="notification">
                        <?= e(
                            (string)
                            $totalProducts
                        ); ?>
                    </span>

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

                    <span>
                        Kategori
                    </span>

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

                    <span>
                        Brand
                    </span>

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

                    <span>
                        Berita
                    </span>

                    <span class="notification">
                        <?= e(
                            (string)
                            $totalPosts
                        ); ?>
                    </span>

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

                    <span>
                        Media
                    </span>

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

                    <span>
                        Lokasi
                    </span>

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

                    <span>
                        Pesan
                    </span>

                    <?php if (
                        $newMessages > 0
                    ): ?>

                        <span
                            class="notification"
                        >
                            <?= e(
                                (string)
                                $newMessages
                            ); ?>
                        </span>

                    <?php endif; ?>

                </a>


                <span
                    class="sidebar-label"
                    style="
                        margin-top:16px;
                    "
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

                    <span>
                        Halaman
                    </span>

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

                    <span>
                        Menu
                    </span>

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

                    <span>
                        Pengaturan
                    </span>

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/security.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-shield-halved"
                    ></i>

                    <span>
                        Security
                    </span>

                </a>

                <?php if (strtolower((string) $adminRole) === 'superadmin'): ?>

                    <a
                        href="<?= e(
                            base_url(
                                'admin/users.php'
                            )
                        ); ?>"
                        class="sidebar-link"
                    >

                        <i
                            class="fa-solid fa-users-gear"
                        ></i>

                        <span>
                            Admin Users
                        </span>

                    </a>

                <?php endif; ?>

            </nav>

        </div>


        <div
            class="sidebar-bottom"
        >

            <div
                class="sidebar-user-mini"
            >

                <div
                    class="sidebar-user-avatar"
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
                    class="sidebar-user-info"
                >

                    <strong>
                        <?= e(
                            $adminName
                        ); ?>
                    </strong>

                    <span>
                        <?= e(
                            $adminRole
                        ); ?>
                    </span>

                </div>

            </div>

        </div>

    </aside>


    <!-- =================================================
         SIDEBAR OVERLAY
    ================================================== -->

    <div
        class="sidebar-overlay"
        id="sidebar-overlay"
    ></div>


    <!-- =================================================
         MAIN
    ================================================== -->

    <div
        class="admin-main"
    >


        <!-- ===============================================
             TOPBAR
        ================================================ -->

        <header
            class="admin-topbar"
        >

            <div
                class="topbar-left"
            >

                <button
                    type="button"
                    class="mobile-sidebar-toggle"
                    id="mobile-sidebar-toggle"
                    aria-label="Buka menu admin"
                    aria-expanded="false"
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
                        Dashboard
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
                    class="view-site-button"
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
                    class="topbar-user"
                >

                    <div
                        class="topbar-avatar"
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
                        class="topbar-user-info"
                    >

                        <strong>
                            <?= e(
                                $adminName
                            ); ?>
                        </strong>

                        <small>
                            <?= e(
                                $adminRole
                            ); ?>
                        </small>

                    </div>


                    <a
                        href="<?= e(
                            base_url(
                                'admin/logout.php'
                            )
                        ); ?>"
                        class="topbar-logout"
                        aria-label="Logout"
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
            class="admin-content"
        >


            <!-- =========================================
                 WELCOME
            ========================================== -->

            <section
                class="welcome-card"
            >

                <div
                    class="welcome-content"
                >

                    <div
                        class="welcome-kicker"
                    >
                        <?= e(
                            $siteTagline
                        ); ?>
                    </div>


                    <h1
                        class="welcome-title"
                    >

                        <?= e(
                            $greeting
                        ); ?>,
                        <span>
                            <?= e(
                                $adminName
                            ); ?>
                        </span>

                    </h1>


                    <p
                        class="welcome-description"
                    >

                        Selamat datang di Panel Admin
                        <?= e(
                            $siteName
                        ); ?>.
                        Kelola konten website, produk,
                        berita dan informasi perusahaan
                        dari satu tempat.

                    </p>

                </div>


                <div
                    class="welcome-date"
                >

                    <i
                        class="fa-regular fa-calendar"
                    ></i>

                    <?= e(
                        $today
                    ); ?>

                </div>

            </section>


            <!-- =========================================
                 STATISTICS
            ========================================== -->

            <div
                class="section-heading"
            >

                <div
                    class="section-heading-left"
                >

                    <div
                        class="section-heading-kicker"
                    >
                        OVERVIEW
                    </div>

                    <h2>
                        Ringkasan Website
                    </h2>

                    <p>
                        Data utama dari database website.
                    </p>

                </div>

            </div>


            <section
                class="stats-grid"
            >


                <!-- PRODUCTS -->

                <article
                    class="stat-card"
                >

                    <div
                        class="stat-card-top"
                    >

                        <span
                            class="stat-icon"
                        >

                            <i
                                class="fa-solid fa-box-open"
                            ></i>

                        </span>

                    </div>


                    <div
                        class="stat-body"
                    >

                        <strong>
                            <?= e(
                                (string)
                                $totalProducts
                            ); ?>
                        </strong>

                        <span>
                            Produk Aktif
                        </span>

                    </div>


                    <div
                        class="stat-card-footer"
                    >

                        <i
                            class="fa-solid fa-circle-check"
                        ></i>

                        Terdaftar di katalog

                    </div>

                </article>


                <!-- POSTS -->

                <article
                    class="stat-card"
                >

                    <div
                        class="stat-card-top"
                    >

                        <span
                            class="stat-icon black"
                        >

                            <i
                                class="fa-solid fa-newspaper"
                            ></i>

                        </span>

                    </div>


                    <div
                        class="stat-body"
                    >

                        <strong>
                            <?= e(
                                (string)
                                $totalPosts
                            ); ?>
                        </strong>

                        <span>
                            Berita Published
                        </span>

                    </div>


                    <div
                        class="stat-card-footer"
                    >

                        <i
                            class="fa-solid fa-circle-check"
                        ></i>

                        Konten publik

                    </div>

                </article>


                <!-- BRANDS -->

                <article
                    class="stat-card"
                >

                    <div
                        class="stat-card-top"
                    >

                        <span
                            class="stat-icon blue"
                        >

                            <i
                                class="fa-solid fa-tags"
                            ></i>

                        </span>

                    </div>


                    <div
                        class="stat-body"
                    >

                        <strong>
                            <?= e(
                                (string)
                                $totalBrands
                            ); ?>
                        </strong>

                        <span>
                            Brand Partner
                        </span>

                    </div>


                    <div
                        class="stat-card-footer"
                    >

                        <i
                            class="fa-solid fa-layer-group"
                        ></i>

                        Portofolio brand

                    </div>

                </article>


                <!-- MESSAGES -->

                <article
                    class="stat-card"
                >

                    <div
                        class="stat-card-top"
                    >

                        <span
                            class="stat-icon green"
                        >

                            <i
                                class="fa-solid fa-envelope"
                            ></i>

                        </span>

                    </div>


                    <div
                        class="stat-body"
                    >

                        <strong>
                            <?= e(
                                (string)
                                $newMessages
                            ); ?>
                        </strong>

                        <span>
                            Pesan Baru
                        </span>

                    </div>


                    <div
                        class="stat-card-footer"
                    >

                        <i
                            class="fa-solid fa-bell"
                        ></i>

                        Perlu ditindaklanjuti

                    </div>

                </article>


            </section>


            <!-- =========================================
                 SECONDARY STAT
            ========================================== -->

            <section
                class="dashboard-grid"
                style="margin-top:16px;"
            >

                <div
                    class="panel"
                >

                    <div
                        class="panel-heading"
                    >

                        <div>

                            <h3>
                                Statistik Konten
                            </h3>

                        </div>

                    </div>


                    <div
                        class="status-summary"
                    >

                        <div
                            class="status-summary-item"
                        >

                            <span>
                                Kategori Produk
                            </span>

                            <strong>
                                <?= e(
                                    (string)
                                    $totalCategories
                                ); ?>
                            </strong>

                        </div>


                        <div
                            class="status-summary-item"
                        >

                            <span>
                                Banner Aktif
                            </span>

                            <strong>
                                <?= e(
                                    (string)
                                    $totalBanners
                                ); ?>
                            </strong>

                        </div>


                        <div
                            class="status-summary-item"
                        >

                            <span>
                                Media
                            </span>

                            <strong>
                                <?= e(
                                    (string)
                                    $totalMedia
                                ); ?>
                            </strong>

                        </div>


                        <div
                            class="status-summary-item"
                        >

                            <span>
                                Halaman
                            </span>

                            <strong>
                                <?= e(
                                    (string)
                                    $totalPages
                                ); ?>
                            </strong>

                        </div>


                        <div
                            class="status-summary-item"
                        >

                            <span>
                                Lokasi
                            </span>

                            <strong>
                                <?= e(
                                    (string)
                                    $totalLocations
                                ); ?>
                            </strong>

                        </div>


                        <div
                            class="status-summary-item"
                        >

                            <span>
                                Total Pesan
                            </span>

                            <strong>
                                <?= e(
                                    (string)
                                    $totalMessages
                                ); ?>
                            </strong>

                        </div>

                    </div>

                </div>


                <div
                    class="panel"
                >

                    <div
                        class="panel-heading"
                    >

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div
                        class="quick-actions"
                    >

                        <a
                            href="<?= e(
                                base_url(
                                    'admin/content.php'
                                )
                            ); ?>"
                            class="quick-action"
                        >

                            <span class="quick-action-icon">
                                <i class="fa-solid fa-pen-ruler"></i>
                            </span>

                            <span class="quick-action-content">
                                <strong>
                                    Kelola Konten Website
                                </strong>
                                <span>
                                    Edit halaman & gambar
                                </span>
                            </span>

                        </a>

                        <a
                            href="<?= e(
                                base_url(
                                    'admin/content.php?section=seo#seo-health'
                                )
                            ); ?>"
                            class="quick-action"
                        >
                            <span class="quick-action-icon">
                                <i class="fa-solid fa-gauge-high"></i>
                            </span>
                            <span class="quick-action-content">
                                <strong>SEO Health Check</strong>
                                <span>Cek kesiapan Google</span>
                            </span>
                        </a>


                        <a
                            href="<?= e(
                                base_url(
                                    'admin/security.php'
                                )
                            ); ?>"
                            class="quick-action"
                        >
                            <span class="quick-action-icon">
                                <i class="fa-solid fa-shield-halved"></i>
                            </span>
                            <span class="quick-action-content">
                                <strong>Security Center</strong>
                                <span>Kelola keamanan admin</span>
                            </span>
                        </a>


                        <a
                            href="<?= e(
                                base_url(
                                    'admin/products.php?action=add'
                                )
                            ); ?>"
                            class="quick-action"
                        >

                            <span
                                class="quick-action-icon"
                            >

                                <i
                                    class="fa-solid fa-plus"
                                ></i>

                            </span>


                            <span
                                class="quick-action-content"
                            >

                                <strong>
                                    Tambah Produk
                                </strong>

                                <span>
                                    Buat katalog baru
                                </span>

                            </span>

                        </a>


                        <a
                            href="<?= e(
                                base_url(
                                    'admin/posts.php?action=add'
                                )
                            ); ?>"
                            class="quick-action"
                        >

                            <span
                                class="quick-action-icon"
                            >

                                <i
                                    class="fa-solid fa-pen"
                                ></i>

                            </span>


                            <span
                                class="quick-action-content"
                            >

                                <strong>
                                    Tulis Berita
                                </strong>

                                <span>
                                    Publikasikan update
                                </span>

                            </span>

                        </a>


                        <a
                            href="<?= e(
                                base_url(
                                    'admin/banners.php?action=add'
                                )
                            ); ?>"
                            class="quick-action"
                        >

                            <span
                                class="quick-action-icon"
                            >

                                <i
                                    class="fa-solid fa-image"
                                ></i>

                            </span>


                            <span
                                class="quick-action-content"
                            >

                                <strong>
                                    Tambah Banner
                                </strong>

                                <span>
                                    Hero homepage
                                </span>

                            </span>

                        </a>


                        <a
                            href="<?= e(
                                base_url(
                                    'admin/settings.php'
                                )
                            ); ?>"
                            class="quick-action"
                        >

                            <span
                                class="quick-action-icon"
                            >

                                <i
                                    class="fa-solid fa-gear"
                                ></i>

                            </span>


                            <span
                                class="quick-action-content"
                            >

                                <strong>
                                    Pengaturan
                                </strong>

                                <span>
                                    Konfigurasi website
                                </span>

                            </span>

                        </a>

                    </div>

                </div>

            </section>


            <!-- =========================================
                 RECENT MESSAGES
            ========================================== -->

            <div
                class="section-heading"
            >

                <div
                    class="section-heading-left"
                >

                    <div
                        class="section-heading-kicker"
                    >
                        INBOX
                    </div>

                    <h2>
                        Pesan Terbaru
                    </h2>

                    <p>
                        Inquiry dari pengunjung website.
                    </p>

                </div>


                <a
                    href="<?= e(
                        base_url(
                            'admin/messages.php'
                        )
                    ); ?>"
                    class="section-action"
                >

                    Lihat Semua

                    <i
                        class="fa-solid fa-arrow-right"
                    ></i>

                </a>

            </div>


            <section
                class="panel"
            >

                <?php if (
                    !empty(
                        $recentMessages
                    )
                ): ?>

                    <div
                        class="message-list"
                    >

                        <?php foreach (
                            $recentMessages
                            as $message
                        ): ?>

                            <?php

                            $messageName =
                                trim(
                                    (string)
                                    (
                                        $message['name']
                                        ?? 'Visitor'
                                    )
                                );

                            $messageSubject =
                                trim(
                                    (string)
                                    (
                                        $message['subject']
                                        ?? 'Inquiry'
                                    )
                                );

                            $messageStatus =
                                trim(
                                    (string)
                                    (
                                        $message['status']
                                        ?? 'new'
                                    )
                                );

                            $messageDate =
                                $message['created_at']
                                ??
                                '';

                            $initial =
                                strtoupper(
                                    mb_substr(
                                        $messageName,
                                        0,
                                        1
                                    )
                                );

                            ?>

                            <a
                                href="<?= e(
                                    base_url(
                                        'admin/messages.php?id=' .
                                        (int)
                                        (
                                            $message['id']
                                            ?? 0
                                        )
                                    )
                                ); ?>"
                                class="message-item <?= $messageStatus === 'new' ? 'unread' : ''; ?>"
                            >

                                <div
                                    class="message-avatar"
                                >
                                    <?= e(
                                        $initial
                                    ); ?>
                                </div>


                                <div
                                    class="message-content"
                                >

                                    <div
                                        class="message-name-row"
                                    >

                                        <span
                                            class="message-name"
                                        >
                                            <?= e(
                                                $messageName
                                            ); ?>
                                        </span>


                                        <span
                                            class="message-date"
                                        >
                                            <?= e(
                                                format_date_id(
                                                    $messageDate
                                                )
                                            ); ?>
                                        </span>

                                    </div>


                                    <div
                                        class="message-subject"
                                    >

                                        <?= e(
                                            $messageSubject !== ''
                                                ? $messageSubject
                                                : 'Inquiry pengunjung'
                                        ); ?>

                                    </div>


                                    <span
                                        class="message-status status-<?= e(
                                            $messageStatus
                                        ); ?>"
                                    >

                                        <?= e(
                                            $messageStatus
                                        ); ?>

                                    </span>

                                </div>

                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div
                        class="empty-dashboard"
                    >

                        <i
                            class="fa-regular fa-envelope"
                        ></i>

                        <span>
                            Belum ada pesan masuk.
                        </span>

                    </div>

                <?php endif; ?>

            </section>


            <!-- =========================================
                 RECENT PRODUCTS / POSTS
            ========================================== -->

            <section
                class="dashboard-grid"
                style="margin-top:16px;"
            >


                <!-- =====================================
                     RECENT PRODUCTS
                ====================================== -->

                <div
                    class="panel"
                >

                    <div
                        class="panel-heading"
                    >

                        <h3>
                            Produk Terbaru
                        </h3>


                        <a
                            href="<?= e(
                                base_url(
                                    'admin/products.php'
                                )
                            ); ?>"
                        >

                            Kelola Produk

                            <i
                                class="fa-solid fa-arrow-right"
                            ></i>

                        </a>

                    </div>


                    <?php if (
                        !empty(
                            $recentProducts
                        )
                    ): ?>

                        <div
                            class="table-wrapper"
                        >

                            <table
                                class="admin-table"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            Produk
                                        </th>

                                        <th>
                                            Kategori
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Dibuat
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $recentProducts
                                        as $product
                                    ): ?>

                                        <?php

                                        $productName =
                                            trim(
                                                (string)
                                                (
                                                    $product['name']
                                                    ?? ''
                                                )
                                            );

                                        $category =
                                            trim(
                                                (string)
                                                (
                                                    $product['category_name']
                                                    ?? '-'
                                                )
                                            );

                                        $image =
                                            trim(
                                                (string)
                                                (
                                                    $product['image']
                                                    ?? ''
                                                )
                                            );

                                        $isActive =
                                            (int)
                                            (
                                                $product['is_active']
                                                ?? 0
                                            ) === 1;

                                        ?>

                                        <tr>

                                            <td>

                                                <div
                                                    class="product-cell"
                                                >

                                                    <div
                                                        class="product-thumb"
                                                    >

                                                        <?php if (
                                                            $image !== ''
                                                        ): ?>

                                                            <img
                                                                src="<?= e(
                                                                    media_url(
                                                                        $image
                                                                    )
                                                                ); ?>"
                                                                alt="<?= e(
                                                                    $productName
                                                                ); ?>"
                                                                loading="lazy"
                                                            >

                                                        <?php else: ?>

                                                            <i
                                                                class="fa-solid fa-box-open"
                                                            ></i>

                                                        <?php endif; ?>

                                                    </div>


                                                    <div
                                                        class="product-cell-info"
                                                    >

                                                        <strong>
                                                            <?= e(
                                                                $productName
                                                            ); ?>
                                                        </strong>

                                                        <span>
                                                            ID #<?= e(
                                                                (string)
                                                                (
                                                                    $product['id']
                                                                    ?? 0
                                                                )
                                                            ); ?>
                                                        </span>

                                                    </div>

                                                </div>

                                            </td>


                                            <td>

                                                <?= e(
                                                    $category !== ''
                                                        ? $category
                                                        : '-'
                                                ); ?>

                                            </td>


                                            <td>

                                                <span
                                                    class="status-badge <?= $isActive ? 'status-active' : 'status-inactive'; ?>"
                                                >

                                                    <?= $isActive
                                                        ? 'Aktif'
                                                        : 'Nonaktif'; ?>

                                                </span>

                                            </td>


                                            <td>

                                                <?= e(
                                                    format_date_id(
                                                        $product['created_at']
                                                        ??
                                                        ''
                                                    )
                                                ); ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div
                            class="empty-dashboard"
                        >

                            <i
                                class="fa-solid fa-box-open"
                            ></i>

                            <span>
                                Belum ada produk.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- =====================================
                     RECENT POSTS
                ====================================== -->

                <div
                    class="panel"
                >

                    <div
                        class="panel-heading"
                    >

                        <h3>
                            Berita Terbaru
                        </h3>


                        <a
                            href="<?= e(
                                base_url(
                                    'admin/posts.php'
                                )
                            ); ?>"
                        >

                            Kelola Berita

                            <i
                                class="fa-solid fa-arrow-right"
                            ></i>

                        </a>

                    </div>


                    <?php if (
                        !empty(
                            $recentPosts
                        )
                    ): ?>

                        <div
                            class="message-list"
                        >

                            <?php foreach (
                                $recentPosts
                                as $post
                            ): ?>

                                <?php

                                $postTitle =
                                    trim(
                                        (string)
                                        (
                                            $post['title']
                                            ?? ''
                                        )
                                    );

                                $postStatus =
                                    trim(
                                        (string)
                                        (
                                            $post['status']
                                            ?? 'draft'
                                        )
                                    );

                                $postDate =
                                    $post['published_at']
                                    ??
                                    $post['created_at']
                                    ??
                                    '';

                                ?>

                                <div
                                    class="message-item"
                                >

                                    <div
                                        class="message-avatar"
                                    >

                                        <i
                                            class="fa-solid fa-newspaper"
                                        ></i>

                                    </div>


                                    <div
                                        class="message-content"
                                    >

                                        <div
                                            class="message-name-row"
                                        >

                                            <span
                                                class="message-name"
                                            >
                                                <?= e(
                                                    $postTitle
                                                ); ?>
                                            </span>

                                            <span
                                                class="message-date"
                                            >

                                                <?= e(
                                                    format_date_id(
                                                        $postDate
                                                    )
                                                ); ?>

                                            </span>

                                        </div>


                                        <span
                                            class="message-status status-<?= e(
                                                $postStatus === 'published'
                                                    ? 'published'
                                                    : 'draft'
                                            ); ?>"
                                        >

                                            <?= $postStatus === 'published'
                                                ? 'Published'
                                                : 'Draft'; ?>

                                        </span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div
                            class="empty-dashboard"
                        >

                            <i
                                class="fa-solid fa-newspaper"
                            ></i>

                            <span>
                                Belum ada berita.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- =========================================
                 FOOTER NOTE
            ========================================== -->

            <div
                style="
                    margin-top:22px;
                    padding:15px 0;
                    text-align:center;
                    color:#a0a0a0;
                    font-size:8px;
                "
            >

                <?= e(
                    $siteName
                ); ?>

                &nbsp;•&nbsp;

                Panel Administrator

                &nbsp;•&nbsp;

                <?= e(
                    date('Y')
                ); ?>

            </div>


        </main>

    </div>

</div>


<!-- =======================================================
     DASHBOARD JAVASCRIPT
======================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const sidebar =
            document.getElementById(
                "admin-sidebar"
            );

        const toggle =
            document.getElementById(
                "mobile-sidebar-toggle"
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
                "is-open"
            );

            if (overlay) {

                overlay.classList.add(
                    "is-visible"
                );

            }

            if (toggle) {

                toggle.setAttribute(
                    "aria-expanded",
                    "true"
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
                "is-open"
            );

            if (overlay) {

                overlay.classList.remove(
                    "is-visible"
                );

            }

            if (toggle) {

                toggle.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

            document.body.style.overflow =
                "";
        }


        if (toggle) {

            toggle.addEventListener(
                "click",
                function () {

                    if (
                        sidebar.classList.contains(
                            "is-open"
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
                "click",
                closeSidebar
            );

        }


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


        window.addEventListener(
            "resize",
            function () {

                if (
                    window.innerWidth > 900
                ) {

                    closeSidebar();

                }

            }
        );

    }
);

</script>

</body>
</html>