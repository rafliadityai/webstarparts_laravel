<?php
// products.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/fdr_catalog.php';

$pageTitle = trim((string)get_setting('products_page_title', 'Katalog Produk | PT Star Parts Motor'));
if ($pageTitle === '') { $pageTitle = 'Katalog Produk | PT Star Parts Motor'; }
$metaDescription = trim((string)get_setting('products_page_description', get_setting('meta_description', 'Jelajahi katalog produk otomotif dan materi katalog FDR 2026 dari PT Star Parts Motor.')));
if ($metaDescription === '') { $metaDescription = 'Jelajahi katalog produk otomotif dan materi katalog FDR 2026 dari PT Star Parts Motor.'; }
$metaKeywords = get_setting('meta_keywords', 'produk, ban motor, FDR, distributor ban, Star Parts Motor');
$pageDescription = $metaDescription;
$pageImage = get_setting('products_og_image', get_setting('site_logo', ''));
$pageCanonicalUrl = site_url('products.php');

$siteName = trim((string)get_setting(
    'site_name',
    'PT Star Parts Motor'
));
if ($siteName === '') { $siteName = 'PT Star Parts Motor'; }

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/

$search = trim(
    get_string('q')
);

$categorySlug = trim(
    get_string('category')
);

$brandSlug = trim(
    get_string('brand')
);

$browseType = strtolower(trim(get_string('browse')));
if (!in_array($browseType, ['all', 'inner', 'tubetype', 'tubeless', 'function', 'size'], true)) {
    $browseType = '';
}
$hasFdrDetails = fdr_details_table_exists();

$page = max(
    1,
    get_int('page')
);

$pageRobots = ($search !== '' || $categorySlug !== '' || $brandSlug !== '' || $browseType !== '' || $page > 1)
    ? 'noindex, follow'
    : 'index, follow';

$perPage = 12;

/*
|--------------------------------------------------------------------------
| Category
|--------------------------------------------------------------------------
*/

$selectedCategory = null;

if ($categorySlug !== '') {

    $stmtCategory = $pdo->prepare("
        SELECT *
        FROM product_categories
        WHERE slug = ?
          AND is_active = 1
        LIMIT 1
    ");

    $stmtCategory->execute([
        $categorySlug
    ]);

    $selectedCategory =
        $stmtCategory->fetch(
            PDO::FETCH_ASSOC
        );
}

/*
|--------------------------------------------------------------------------
| Brand
|--------------------------------------------------------------------------
*/

$selectedBrand = null;

/*
 * Catatan kompatibilitas database:
 * tabel `brands` pada database yang diberikan tidak memiliki kolom `slug`,
 * sedangkan halaman produk sebelumnya menggunakannya.
 * Slug brand sekarang dibentuk dari `name` sehingga UI/filter lama tetap sama.
 */
$brandRows = $pdo->query("
    SELECT *
    FROM brands
    WHERE is_active = 1
    ORDER BY sort_order ASC, name ASC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($brandRows as $brandRow) {

    $brandRow['slug'] = slugify(
        (string) ($brandRow['name'] ?? '')
    );

    if (
        $brandSlug !== ''
        && $brandSlug === $brandRow['slug']
    ) {

        $selectedBrand = $brandRow;
        break;
    }
}

/*
 * Database saat ini tidak memiliki `products.brand_id`.
 * Relasi brand yang tersedia dapat diturunkan dari kategori produk
 * karena data seed menggunakan nama/slug kategori yang mengikuti brand.
 */
$selectedBrandCategoryId = null;

if ($selectedBrand) {

    $selectedBrandCategoryStmt = $pdo->prepare("
        SELECT id
        FROM product_categories
        WHERE is_active = 1
          AND (
              slug = ?
              OR LOWER(TRIM(name)) = LOWER(TRIM(?))
              OR LOWER(TRIM(name)) LIKE CONCAT('% ', LOWER(TRIM(?)))
          )
        ORDER BY id ASC
        LIMIT 1
    ");

    $selectedBrandCategoryStmt->execute([
        $selectedBrand['slug'],
        (string) ($selectedBrand['name'] ?? ''),
        (string) ($selectedBrand['name'] ?? '')
    ]);

    $selectedBrandCategoryId =
        $selectedBrandCategoryStmt->fetchColumn();

    if ($selectedBrandCategoryId !== false) {
        $selectedBrandCategoryId = (int) $selectedBrandCategoryId;
    } else {
        $selectedBrandCategoryId = null;
    }
}

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

$stmtCategories = $pdo->query("
    SELECT
        c.*,
        COUNT(p.id) AS product_count
    FROM product_categories c
    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY
        c.sort_order ASC,
        c.name ASC
");

$categories =
    $stmtCategories->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| Brands
|--------------------------------------------------------------------------
*/

$stmtBrands = $pdo->query("
    SELECT *
    FROM brands
    WHERE is_active = 1
    ORDER BY
        sort_order ASC,
        name ASC
");

$brands =
    $stmtBrands->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
 * Tambahkan slug dan jumlah produk tanpa membutuhkan kolom brand_id.
 */
$brandProductCounts = [];

$countStmt = $pdo->query("
    SELECT
        c.id AS category_id,
        c.slug AS category_slug,
        c.name AS category_name,
        COUNT(p.id) AS product_count
    FROM product_categories c
    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.is_active = 1
    GROUP BY
        c.id,
        c.slug,
        c.name
");

$categoryProductCounts =
    $countStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($brands as &$brand) {

    $brand['slug'] = slugify(
        (string) ($brand['name'] ?? '')
    );

    $brandName = strtolower(
        trim((string) ($brand['name'] ?? ''))
    );

    $productCount = 0;

    foreach ($categoryProductCounts as $countRow) {

        $categorySlug = slugify(
            (string) ($countRow['category_slug'] ?? '')
        );

        $categoryName = strtolower(
            trim((string) ($countRow['category_name'] ?? ''))
        );

        if (
            $categorySlug === $brand['slug']
            || $categoryName === $brandName
            || str_ends_with(
                $categoryName,
                ' ' . $brandName
            )
        ) {

            $productCount +=
                (int) ($countRow['product_count'] ?? 0);
        }
    }

    $brand['product_count'] = $productCount;
}

unset($brand);

/*
|--------------------------------------------------------------------------
| Product Query
|--------------------------------------------------------------------------
*/

$where = [
    'p.is_active = 1'
];

$params = [];

if ($search !== '') {

    $where[] = "
        (
            p.name LIKE ?
            OR p.short_description LIKE ?
            OR p.description LIKE ?
            OR p.slug LIKE ?
        )
    ";

    $like =
        '%' .
        $search .
        '%';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($selectedCategory) {

    $where[] =
        'p.category_id = ?';

    $params[] =
        (int) $selectedCategory['id'];
}

if ($selectedBrand) {

    if ($selectedBrandCategoryId !== null) {

        $where[] =
            'p.category_id = ?';

        $params[] =
            $selectedBrandCategoryId;

    } else {

        /*
         * Brand valid tetapi belum memiliki padanan kategori.
         * Kembalikan hasil kosong daripada menjalankan query yang rusak.
         */
        $where[] = '1 = 0';
    }
}

/*
 * Menu Browse pada mega menu FDR. Jenis ban dapat diubah dari Admin > Konten Detail Ban.
 * Function/Size hanya mengarahkan pengguna ke area katalog tanpa memfilter data secara keliru.
 */
if (in_array($browseType, ['all', 'function'], true)) {
    $where[] = "EXISTS (SELECT 1 FROM product_categories cf_all_tires WHERE cf_all_tires.id = p.category_id AND cf_all_tires.slug IN ('fdr-tire','fdr-ultimate','fdr-champion'))";
}

if (in_array($browseType, ['inner', 'tubetype', 'tubeless'], true)) {
    if ($hasFdrDetails) {
        $where[] = "EXISTS (SELECT 1 FROM fdr_product_details fd_filter WHERE fd_filter.product_id = p.id AND fd_filter.tire_type = ?)";
        $params[] = $browseType;
    } else {
        $fallbackTerms = [
            'inner' => "(LOWER(p.name) LIKE '%tube%' OR LOWER(COALESCE(p.short_description,'')) LIKE '%inner tire%')",
            'tubetype' => "(LOWER(COALESCE(p.description,'')) LIKE '%tubetype%' OR LOWER(COALESCE(p.description,'')) LIKE '%tube type%' OR LOWER(COALESCE(p.description,'')) LIKE '% tt %')",
            'tubeless' => "(LOWER(COALESCE(p.description,'')) LIKE '%tubeless%' OR LOWER(COALESCE(p.description,'')) LIKE '% tl %')",
        ];
        $where[] = $fallbackTerms[$browseType];
    }
}

/*
|--------------------------------------------------------------------------
| Count
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM products p
    WHERE " .
    implode(
        ' AND ',
        $where
    );

$stmtCount =
    $pdo->prepare(
        $countSql
    );

$stmtCount->execute(
    $params
);

$totalProducts =
    (int) $stmtCount->fetchColumn();

$totalPages =
    max(
        1,
        (int) ceil(
            $totalProducts /
            $perPage
        )
    );

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset =
    ($page - 1) *
    $perPage;

/*
|--------------------------------------------------------------------------
| Product List
|--------------------------------------------------------------------------
*/

$fdrDetailSelect = $hasFdrDetails
    ? ", fd.listing_description AS fdr_listing_description, fd.logo_path AS fdr_logo_path, fd.tire_type AS fdr_tire_type, fd.sizes_text AS fdr_sizes_text"
    : '';
$fdrDetailJoin = $hasFdrDetails
    ? " LEFT JOIN fdr_product_details fd ON fd.product_id = p.id "
    : '';

$productSql = "
    SELECT
        p.*,
        c.name AS category_name,
        c.slug AS category_slug,
        b.name AS brand_name" . $fdrDetailSelect . "
    FROM products p

    LEFT JOIN product_categories c
        ON c.id = p.category_id

    " . $fdrDetailJoin . "

    LEFT JOIN brands b
        ON b.is_active = 1
        AND (
            LOWER(TRIM(b.name)) = LOWER(TRIM(c.name))
            OR LOWER(TRIM(c.name)) LIKE CONCAT('% ', LOWER(TRIM(b.name)))
        )

    WHERE " .
    implode(
        ' AND ',
        $where
    ) .
    "
    ORDER BY
        p.is_featured DESC,
        p.sort_order ASC,
        p.created_at DESC,
        p.id DESC

    LIMIT " .
    (int) $perPage .
    "
    OFFSET " .
    (int) $offset;

$stmtProducts =
    $pdo->prepare(
        $productSql
    );

$stmtProducts->execute(
    $params
);

$products =
    $stmtProducts->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| Page Description
|--------------------------------------------------------------------------
*/

if ($selectedCategory) {

    $pageTitle =
        $selectedCategory['name'] .
        ' - Produk';

    $metaDescription =
        'Daftar produk kategori ' .
        $selectedCategory['name'] .
        ' dari ' .
        $siteName .
        '.';

} elseif ($selectedBrand) {

    $pageTitle =
        $selectedBrand['name'] .
        ' - Produk';

    $metaDescription =
        'Daftar produk brand ' .
        $selectedBrand['name'] .
        ' dari ' .
        $siteName .
        '.';

}

if (!$selectedCategory && !$selectedBrand && $browseType !== '') {
    $browseTitles = [
        'all' => 'All Tire',
        'inner' => 'Inner Tire',
        'tubetype' => 'Tubetype Tire',
        'tubeless' => 'Tubeless Tire',
        'function' => 'Produk Berdasarkan Fungsi',
        'size' => 'Produk Berdasarkan Ukuran',
    ];
    $pageTitle = ($browseTitles[$browseType] ?? 'Katalog Ban') . ' | ' . $siteName;
    $metaDescription = 'Jelajahi ' . strtolower($browseTitles[$browseType] ?? 'katalog ban') . ' dalam katalog PT Star Parts Motor.';
}

$isFdrCatalogPage = ($selectedCategory && in_array((string)($selectedCategory['slug'] ?? ''), ['fdr-tire','fdr-ultimate','fdr-champion'], true))
    || in_array($browseType, ['all','inner','tubetype','tubeless','function','size'], true);
$fdrCatalogLine = $selectedCategory ? fdr_line_presentation((string)($selectedCategory['slug'] ?? '')) : fdr_line_presentation('fdr-tire');
$isOsramCatalogPage = $selectedCategory && (string)($selectedCategory['slug'] ?? '') === 'osram';
if ($isOsramCatalogPage) {
    $pageTitle = 'OSRAM Automotive Lighting | ' . $siteName;
    $metaDescription = 'Katalog lampu OSRAM di PT Star Parts Motor. Lihat model produk dan lengkapi informasi produk melalui admin.';
    $pageDescription = $metaDescription;
    $bodyClass = 'osram-catalog-page';
    $extraHead = (string)($extraHead ?? '') . '<link rel="stylesheet" href="' . e(base_url('assets/css/osram-products.css')) . '">';
}

/*
|--------------------------------------------------------------------------
| Breadcrumb
|--------------------------------------------------------------------------
*/

$breadcrumbLabel =
    'Produk';

if ($selectedCategory) {
    $breadcrumbLabel =
        'Produk / ' .
        $selectedCategory['name'];
}

if ($selectedBrand) {
    $breadcrumbLabel =
        'Produk / ' .
        $selectedBrand['name'];
}

/*
|--------------------------------------------------------------------------
| Pagination URL
|--------------------------------------------------------------------------
*/

function products_page_url(
    int $pageNumber
): string {

    global
        $search,
        $categorySlug,
        $brandSlug,
        $browseType;

    $params = [];

    if ($search !== '') {
        $params['q'] =
            $search;
    }

    if ($categorySlug !== '') {
        $params['category'] =
            $categorySlug;
    }

    if ($brandSlug !== '') {
        $params['brand'] =
            $brandSlug;
    }
    if ($browseType !== '') {
        $params['browse'] = $browseType;
    }

    if ($pageNumber > 1) {
        $params['page'] =
            $pageNumber;
    }

    return
        base_url('products.php') .
        (
            $params
                ? '?' .
                    http_build_query(
                        $params
                    )
                : ''
        );
}

include __DIR__ .
    '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

?>

<main>

    <!--
    |--------------------------------------------------------------------------
    | PAGE HERO
    |--------------------------------------------------------------------------
    -->

    <?php if ($isFdrCatalogPage): ?>
        <section class="fdr-catalog-hero">
            <div class="container">
                <div class="breadcrumb fdr-catalog-breadcrumb">
                    <a href="<?= e(site_url()); ?>">Beranda</a><span>/</span><a href="<?= e(site_url('products.php')); ?>">Produk</a><span>/</span><span><?= e($selectedCategory['name'] ?? ($browseType === 'all' ? 'All Tire' : ($browseType === 'function' ? 'By Function' : ($browseType === 'size' ? 'By Size' : ($browseType === 'inner' ? 'Inner Tire' : ($browseType === 'tubetype' ? 'Tubetype Tire' : 'Tubeless Tire')))))); ?></span>
                </div>
                <img class="fdr-catalog-logo" src="<?= e(media_url('uploads/general/fdr-tire-logo-red.png')); ?>" alt="FDR Tire" loading="eager">
                <p><?= e(trim((string)($selectedCategory['description'] ?? 'Varian ban harian FDR yang tersedia dalam beragam kembangan dan ukuran serta dirancang untuk kenyamanan berkendara.'))); ?></p>
            </div>
        </section>
    <?php elseif ($isOsramCatalogPage): ?>
        <section class="osram-catalog-hero">
            <div class="container osram-catalog-hero-inner">
                <div class="osram-catalog-copy">
                    <div class="osram-breadcrumb"><a href="<?= e(site_url()); ?>">Beranda</a><span>/</span><a href="<?= e(site_url('products.php')); ?>">Produk</a><span>/</span><span>OSRAM</span></div>
                    <span class="osram-eyebrow"><span></span> AUTOMOTIVE LIGHTING</span>
                    <h1>OSRAM <em>Lighting</em></h1>
                    <p>Katalog lampu OSRAM yang tersedia di PT Star Parts Motor. Detail dan spesifikasi setiap model dapat dilengkapi melalui panel admin.</p>
                    <a class="osram-hero-button" href="#product-list">Jelajahi Produk <i class="fa-solid fa-arrow-down"></i></a>
                </div>
                <div class="osram-hero-art" aria-label="Foto produk lampu OSRAM">
                    <div class="osram-art-glow"></div>
                    <img src="<?= e(media_url('https://beta-career.starpartsmotor.com/OHS70.png')); ?>" alt="Produk lampu OSRAM" fetchpriority="high" decoding="async">
                    <span class="osram-art-chip"><i class="fa-solid fa-lightbulb"></i> OSRAM</span>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="page-hero">
            <div class="container">
                <div class="page-hero-content">
                    <div class="breadcrumb"><a href="<?= e(BASE_URL) ?>">Home</a><span>/</span><span><?= e($breadcrumbLabel) ?></span></div>
                    <div class="section-kicker">PRODUCT CATALOG</div>
                    <h1><?= e($pageTitle) ?></h1>
                    <p>Temukan berbagai produk pilihan Star Parts Motor untuk memenuhi kebutuhan kendaraan roda dua.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!--
    |--------------------------------------------------------------------------
    | PRODUCTS AREA
    |--------------------------------------------------------------------------
    -->

    <section
        class="section"
        id="product-list"
        style="padding-top:45px;"
    >

        <div class="container">

            <div
                class="products-layout<?= $isFdrCatalogPage ? ' fdr-catalog-layout' : ($isOsramCatalogPage ? ' osram-catalog-layout' : ''); ?>"
                style="
                    display:grid;
                    grid-template-columns:
                        250px
                        minmax(0,1fr);
                    gap:28px;
                    align-items:start;
                "
            >

                <!--
                |--------------------------------------------------------------------------
                | SIDEBAR FILTER
                |--------------------------------------------------------------------------
                -->

                <aside
                    class="product-filter-sidebar"
                    style="
                        background:#fff;
                        border:1px solid #e9e9e9;
                        border-radius:18px;
                        padding:20px;
                        position:sticky;
                        top:100px;
                    "
                >

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            justify-content:space-between;
                            margin-bottom:18px;
                        "
                    >

                        <h3
                            style="
                                margin:0;
                                font:700 16px Poppins,sans-serif;
                            "
                        >
                            Filter
                        </h3>

                        <a
                            href="<?= e(
                                base_url('products.php')
                            ) ?>"
                            style="
                                font-size:10px;
                                color:#d90000;
                                font-weight:700;
                            "
                        >
                            Reset
                        </a>

                    </div>

                    <form
                        method="get"
                        action="<?= e(
                            base_url('products.php')
                        ) ?>"
                    >

                        <div
                            style="
                                display:flex;
                                flex-direction:column;
                                gap:9px;
                                margin-bottom:20px;
                            "
                        >

                            <label
                                style="
                                    font-size:10px;
                                    font-weight:700;
                                    color:#666;
                                "
                            >
                                Cari Produk
                            </label>

                            <input
                                type="text"
                                name="q"
                                value="<?= e(
                                    $search
                                ) ?>"
                                placeholder="Nama produk..."
                                style="
                                    width:100%;
                                    min-height:43px;
                                    padding:10px 12px;
                                    border:1px solid #ddd;
                                    border-radius:10px;
                                    outline:none;
                                    font-size:12px;
                                "
                            >

                        </div>

                        <div
                            style="
                                display:flex;
                                flex-direction:column;
                                gap:9px;
                                margin-bottom:20px;
                            "
                        >

                            <label
                                style="
                                    font-size:10px;
                                    font-weight:700;
                                    color:#666;
                                "
                            >
                                Kategori
                            </label>

                            <select
                                name="category"
                                onchange="this.form.submit()"
                                style="
                                    width:100%;
                                    min-height:43px;
                                    padding:10px 12px;
                                    border:1px solid #ddd;
                                    border-radius:10px;
                                    background:#fff;
                                    font-size:12px;
                                    outline:none;
                                "
                            >

                                <option value="">
                                    Semua Kategori
                                </option>

                                <?php foreach (
                                    $categories
                                    as $category
                                ): ?>

                                    <option
                                        value="<?= e(
                                            $category['slug']
                                        ) ?>"
                                        <?= (
                                            $categorySlug ===
                                            $category['slug']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        <?= e(
                                            $category['name']
                                        ) ?>

                                        (
                                        <?= number_format(
                                            (int) $category['product_count'],
                                            0,
                                            ',',
                                            '.'
                                        ) ?>
                                        )

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div
                            style="
                                display:flex;
                                flex-direction:column;
                                gap:9px;
                                margin-bottom:20px;
                            "
                        >

                            <label
                                style="
                                    font-size:10px;
                                    font-weight:700;
                                    color:#666;
                                "
                            >
                                Brand
                            </label>

                            <select
                                name="brand"
                                onchange="this.form.submit()"
                                style="
                                    width:100%;
                                    min-height:43px;
                                    padding:10px 12px;
                                    border:1px solid #ddd;
                                    border-radius:10px;
                                    background:#fff;
                                    font-size:12px;
                                    outline:none;
                                "
                            >

                                <option value="">
                                    Semua Brand
                                </option>

                                <?php foreach (
                                    $brands
                                    as $brand
                                ): ?>

                                    <option
                                        value="<?= e(
                                            $brand['slug']
                                        ) ?>"
                                        <?= (
                                            $brandSlug ===
                                            $brand['slug']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        <?= e(
                                            $brand['name']
                                        ) ?>

                                        (
                                        <?= number_format(
                                            (int) $brand['product_count'],
                                            0,
                                            ',',
                                            '.'
                                        ) ?>
                                        )

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            style="width:100%;"
                        >
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Terapkan Filter
                        </button>

                    </form>

                </aside>

                <!--
                |--------------------------------------------------------------------------
                | PRODUCT RESULTS
                |--------------------------------------------------------------------------
                -->

                <div>

                    <!-- Result Header -->

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            justify-content:space-between;
                            gap:15px;
                            margin-bottom:20px;
                        "
                    >

                        <div>

                            <h2
                                style="
                                    margin:0;
                                    font:700 21px Poppins,sans-serif;
                                "
                            >
                                Produk
                            </h2>

                            <p
                                style="
                                    margin:5px 0 0;
                                    color:#888;
                                    font-size:11px;
                                "
                            >
                                Menampilkan

                                <?= number_format(
                                    count($products),
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                                dari

                                <?= number_format(
                                    $totalProducts,
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                                produk
                            </p>

                        </div>

                        <?php if (
                            $selectedCategory ||
                            $selectedBrand ||
                            $search !== ''
                        ): ?>

                            <div
                                style="
                                    display:flex;
                                    flex-wrap:wrap;
                                    gap:6px;
                                    justify-content:flex-end;
                                "
                            >

                                <?php if (
                                    $search !== ''
                                ): ?>

                                    <span
                                        style="
                                            padding:6px 9px;
                                            border-radius:999px;
                                            background:#fff0f0;
                                            color:#d90000;
                                            font-size:10px;
                                            font-weight:700;
                                        "
                                    >
                                        Search:
                                        <?= e(
                                            $search
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                                <?php if (
                                    $selectedCategory
                                ): ?>

                                    <span
                                        style="
                                            padding:6px 9px;
                                            border-radius:999px;
                                            background:#f3f3f3;
                                            color:#666;
                                            font-size:10px;
                                            font-weight:700;
                                        "
                                    >
                                        <?= e(
                                            $selectedCategory['name']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                                <?php if (
                                    $selectedBrand
                                ): ?>

                                    <span
                                        style="
                                            padding:6px 9px;
                                            border-radius:999px;
                                            background:#f3f3f3;
                                            color:#666;
                                            font-size:10px;
                                            font-weight:700;
                                        "
                                    >
                                        <?= e(
                                            $selectedBrand['name']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <?php if (
                        !$products
                    ): ?>

                        <div
                            class="empty-state"
                            style="
                                background:#fff;
                                border:1px solid #e9e9e9;
                                border-radius:18px;
                                padding:70px 25px;
                            "
                        >

                            <i
                                class="fa-solid fa-box-open"
                                style="
                                    display:block;
                                    font-size:42px;
                                    color:#ccc;
                                    margin-bottom:15px;
                                "
                            ></i>

                            <h3
                                style="
                                    margin:0 0 6px;
                                    font:700 17px Poppins,sans-serif;
                                    color:#555;
                                "
                            >
                                <?= ($search !== '' || $categorySlug !== '' || $brandSlug !== '' || in_array($browseType, ['inner','tubetype','tubeless'], true)) ? 'Produk tidak ditemukan' : 'Katalog produk sedang disiapkan'; ?>
                            </h3>

                            <p
                                style="
                                    margin:0 auto;
                                    max-width:460px;
                                    color:#777;
                                    font-size:12px;
                                    line-height:1.8;
                                "
                            >
                                <?php if ($search !== '' || $categorySlug !== '' || $brandSlug !== '' || in_array($browseType, ['inner','tubetype','tubeless'], true)): ?>
                                    Belum ada produk yang sesuai dengan pencarian atau filter yang dipilih. Anda tetap dapat melihat materi katalog FDR 2026 atau menghubungi tim kami untuk informasi produk.
                                <?php else: ?>
                                    Daftar produk digital sedang dilengkapi. Sementara itu, buka materi katalog FDR 2026 atau hubungi tim kami untuk menanyakan tipe, ukuran, dan ketersediaan produk.
                                <?php endif; ?>
                            </p>

                            <a
                                href="<?= e(base_url('products.php#catalog')) ?>"
                                class="btn btn-primary"
                                style="margin-top:18px;"
                            >
                                <i class="fa-solid fa-book-open"></i>
                                Lihat Katalog FDR 2026
                            </a>

                        </div>

                    <?php else: ?>

                        <!-- Product Grid -->

                        <div
                            class="product-grid<?= $isFdrCatalogPage ? ' fdr-product-grid' : ($isOsramCatalogPage ? ' osram-product-grid' : ''); ?>"
                            style="
                                display:grid;
                                grid-template-columns:
                                    repeat(<?= ($isFdrCatalogPage || $isOsramCatalogPage) ? 4 : 3; ?>,minmax(0,1fr));
                                gap:20px;
                            "
                        >

                            <?php foreach (
                                $products
                                as $product
                            ): ?>

                                <?php

                                $productImage =
                                    $product['image']
                                    ??
                                    '';

                                $productUrl =
                                    product_url((string)$product['slug']);

                                ?>

                                <article
                                    class="product-card<?= $isOsramCatalogPage ? ' osram-product-card' : ''; ?>"
                                >

                                    <?php if ($isFdrCatalogPage): ?>
                                    <div class="product-card-image fdr-card-image">
                                    <?php else: ?>
                                    <a href="<?= e($productUrl); ?>" class="product-card-image<?= $isOsramCatalogPage ? ' osram-product-card-image' : ''; ?>">
                                    <?php endif; ?>

                                        <?php if (
                                            $productImage !== ''
                                        ): ?>

                                            <img
                                                src="<?= e(
                                                    media_url(
                                                        $productImage
                                                    )
                                                ) ?>"
                                                alt="<?= e(
                                                    $product['name']
                                                ) ?>"
                                                loading="lazy"
                                            >

                                        <?php else: ?>

                                            <div
                                                style="
                                                    width:100%;
                                                    height:100%;
                                                    min-height:230px;
                                                    display:grid;
                                                    place-items:center;
                                                    color:#ccc;
                                                "
                                            >
                                                <i
                                                    class="fa-solid fa-box-open"
                                                    style="font-size:38px;"
                                                ></i>
                                            </div>

                                        <?php endif; ?>

                                        <?php if (
                                            (int) (
                                                $product[
                                                    'is_featured'
                                                ]
                                                ??
                                                0
                                            ) === 1
                                        ): ?>

                                            <span
                                                style="
                                                    position:absolute;
                                                    top:12px;
                                                    left:12px;
                                                    z-index:2;
                                                    padding:6px 9px;
                                                    background:#d90000;
                                                    color:#fff;
                                                    border-radius:999px;
                                                    font-size:9px;
                                                    font-weight:700;
                                                "
                                            >
                                                <i class="fa-solid fa-star"></i>
                                                Unggulan
                                            </span>

                                        <?php endif; ?>

                                        <?php if ($isFdrCatalogPage): ?>
                                            <?php $fdrExternalBuyUrl = fdr_normalize_http_url((string)($product['product_url'] ?? '')); if ($fdrExternalBuyUrl === '') $fdrExternalBuyUrl = 'https://bengkelku.online'; ?>
                                            <a class="fdr-card-image-hitarea" href="<?= e($productUrl); ?>" aria-label="Detail <?= e((string)$product['name']); ?>"></a>
                                            <div class="fdr-card-hover-actions">
                                                <a href="<?= e($fdrExternalBuyUrl !== '' ? $fdrExternalBuyUrl : $productUrl); ?>" class="fdr-card-action-buy" target="_blank" rel="noopener noreferrer">BUY NOW</a>
                                                <a href="<?= e($productUrl); ?>" class="fdr-card-action-details">DETAILS</a>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                    </a>
                                        <?php endif; ?>

                                    <div
                                        class="product-card-content<?= $isFdrCatalogPage ? ' fdr-product-card-content' : ($isOsramCatalogPage ? ' osram-product-card-content' : ''); ?>"
                                    >
                                        <?php if ($isFdrCatalogPage && (str_starts_with((string)($product['category_slug'] ?? ''), 'fdr-') || $browseType !== '')): ?>
                                            <img class="fdr-card-logo" src="<?= e(media_url(trim((string)($product['fdr_logo_path'] ?? '')) !== '' ? (string)$product['fdr_logo_path'] : 'uploads/general/fdr-tire-logo-red.png')); ?>" alt="FDR Tire" loading="lazy">
                                        <?php endif; ?>

                                        <?php if ($isOsramCatalogPage): ?>
                                            <div class="osram-product-brand">OSRAM AUTOMOTIVE LIGHTING</div>
                                        <?php elseif (
                                            !empty(
                                                $product[
                                                    'brand_name'
                                                ]
                                            )
                                        ): ?>

                                            <div
                                                style="
                                                    margin-bottom:6px;
                                                    color:#d90000;
                                                    font-size:9px;
                                                    font-weight:800;
                                                    text-transform:uppercase;
                                                    letter-spacing:.6px;
                                                "
                                            >
                                                <?= e(
                                                    $product[
                                                        'brand_name'
                                                    ]
                                                ) ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if (!$isOsramCatalogPage && !empty($product['category_name'])): ?>

                                            <div
                                                style="
                                                    margin-bottom:7px;
                                                    color:#999;
                                                    font-size:9px;
                                                    font-weight:600;
                                                "
                                            >
                                                <?= e(
                                                    $product[
                                                        'category_name'
                                                    ]
                                                ) ?>
                                            </div>

                                        <?php endif; ?>

                                        <h3
                                            style="
                                                margin:0;
                                                font:700 16px/1.45 Poppins,sans-serif;
                                            "
                                        >

                                            <a
                                                href="<?= e(
                                                    $productUrl
                                                ) ?>"
                                            >
                                                <?= e(
                                                    $product['name']
                                                ) ?>
                                            </a>

                                        </h3>

                                        <?php if (
                                            trim((string)($product['fdr_listing_description'] ?? '')) !== ''
                                            || !empty($product['short_description'])
                                        ): ?>

                                            <p
                                                style="
                                                    margin:8px 0 0;
                                                    color:#777;
                                                    font-size:11px;
                                                    line-height:1.7;
                                                "
                                            >
                                                <?= e(
                                                    excerpt(
                                                        fdr_hide_price_amounts(
                                                            trim((string)($product['fdr_listing_description'] ?? '')) !== ''
                                                                ? (string)$product['fdr_listing_description']
                                                                : (string)($product['short_description'] ?? '')
                                                        ),
                                                        130
                                                    )
                                                ) ?>
                                            </p>

                                        <?php elseif ($isOsramCatalogPage): ?>
                                            <p class="osram-product-placeholder">Deskripsi produk dapat Anda lengkapi melalui Admin &rarr; Produk.</p>
                                        <?php endif; ?>

                                        <?php /* Harga katalog disembunyikan sementara sesuai permintaan; data sumber tidak dihapus. */ ?>

                                        <?php if ($isOsramCatalogPage): ?>
                                            <?php $osramBuyUrl = trim((string)($product['product_url'] ?? '')); if (!preg_match('~^https?://~i', $osramBuyUrl)) $osramBuyUrl = ''; ?>
                                            <div class="osram-product-actions">
                                                <a href="<?= e($productUrl); ?>" class="osram-details-button">DETAILS <i class="fa-solid fa-arrow-right"></i></a>
                                                <?php if ($osramBuyUrl !== ''): ?><a href="<?= e($osramBuyUrl); ?>" class="osram-buy-button" target="_blank" rel="noopener noreferrer">BUY NOW <i class="fa-solid fa-arrow-up-right-from-square"></i></a><?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:15px;">
                                                <a href="<?= e($productUrl); ?>" class="btn btn-primary" style="min-height:36px;padding:0 13px;font-size:10px;">Lihat Produk <i class="fa-solid fa-arrow-right"></i></a>
                                            </div>
                                        <?php endif; ?>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                        <!-- Pagination -->

                        <?php if (
                            $totalPages > 1
                        ): ?>

                            <nav
                                aria-label="Pagination produk"
                                style="
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    gap:6px;
                                    flex-wrap:wrap;
                                    margin-top:30px;
                                "
                            >

                                <?php if (
                                    $page > 1
                                ): ?>

                                    <a
                                        href="<?= e(
                                            products_page_url(
                                                $page - 1
                                            )
                                        ) ?>"
                                        style="
                                            min-width:38px;
                                            height:38px;
                                            padding:0 11px;
                                            border-radius:10px;
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            background:#fff;
                                            border:1px solid #e5e5e5;
                                            color:#555;
                                            font-size:11px;
                                            font-weight:700;
                                        "
                                    >
                                        <i
                                            class="fa-solid fa-chevron-left"
                                        ></i>
                                    </a>

                                <?php endif; ?>

                                <?php

                                $startPage =
                                    max(
                                        1,
                                        $page - 2
                                    );

                                $endPage =
                                    min(
                                        $totalPages,
                                        $page + 2
                                    );

                                if (
                                    $startPage > 1
                                ):

                                ?>

                                    <a
                                        href="<?= e(
                                            products_page_url(1)
                                        ) ?>"
                                        style="
                                            min-width:38px;
                                            height:38px;
                                            padding:0 11px;
                                            border-radius:10px;
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            background:#fff;
                                            border:1px solid #e5e5e5;
                                            color:#555;
                                            font-size:11px;
                                            font-weight:700;
                                        "
                                    >
                                        1
                                    </a>

                                    <?php if (
                                        $startPage > 2
                                    ): ?>

                                        <span
                                            style="
                                                color:#aaa;
                                                padding:0 3px;
                                            "
                                        >
                                            ...
                                        </span>

                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php for (
                                    $pageNumber =
                                        $startPage;
                                    $pageNumber <=
                                        $endPage;
                                    $pageNumber++
                                ): ?>

                                    <a
                                        href="<?= e(
                                            products_page_url(
                                                $pageNumber
                                            )
                                        ) ?>"
                                        style="
                                            min-width:38px;
                                            height:38px;
                                            padding:0 11px;
                                            border-radius:10px;
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            font-size:11px;
                                            font-weight:700;
                                            <?= $pageNumber === $page
                                                ? 'background:#d90000;color:#fff;border:1px solid #d90000;box-shadow:0 6px 15px rgba(217,0,0,.18);'
                                                : 'background:#fff;color:#555;border:1px solid #e5e5e5;'
                                            ?>
                                        "
                                    >
                                        <?= $pageNumber ?>
                                    </a>

                                <?php endfor; ?>

                                <?php if (
                                    $endPage <
                                    $totalPages
                                ): ?>

                                    <?php if (
                                        $endPage <
                                        $totalPages - 1
                                    ): ?>

                                        <span
                                            style="
                                                color:#aaa;
                                                padding:0 3px;
                                            "
                                        >
                                            ...
                                        </span>

                                    <?php endif; ?>

                                    <a
                                        href="<?= e(
                                            products_page_url(
                                                $totalPages
                                            )
                                        ) ?>"
                                        style="
                                            min-width:38px;
                                            height:38px;
                                            padding:0 11px;
                                            border-radius:10px;
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            background:#fff;
                                            border:1px solid #e5e5e5;
                                            color:#555;
                                            font-size:11px;
                                            font-weight:700;
                                        "
                                    >
                                        <?= $totalPages ?>
                                    </a>

                                <?php endif; ?>

                                <?php if (
                                    $page <
                                    $totalPages
                                ): ?>

                                    <a
                                        href="<?= e(
                                            products_page_url(
                                                $page + 1
                                            )
                                        ) ?>"
                                        style="
                                            min-width:38px;
                                            height:38px;
                                            padding:0 11px;
                                            border-radius:10px;
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            background:#fff;
                                            border:1px solid #e5e5e5;
                                            color:#555;
                                            font-size:11px;
                                            font-weight:700;
                                        "
                                    >
                                        <i
                                            class="fa-solid fa-chevron-right"
                                        ></i>
                                    </a>

                                <?php endif; ?>

                            </nav>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

    <section class="section spm-catalog-page-section" id="catalog">
        <div class="container">
            <div class="spm-catalog-page-panel">
                <div class="spm-catalog-grid">
                    <div class="spm-catalog-copy">
                        <div class="section-kicker">MATERI KATALOG</div>
                        <h2 class="section-title">Katalog <span>FDR 2026</span></h2>
                        <p class="section-description" style="margin-left:0;text-align:left;">Buka gambar katalog beresolusi penuh untuk menelusuri tipe dan ukuran yang tercantum pada materi FDR 2026. Informasi harga, stok, dan ketersediaan dapat dikonfirmasi langsung kepada tim Star Parts Motor.</p>
                        <div class="spm-catalog-actions">
                            <a class="btn btn-primary" href="<?= e(base_url('uploads/general/katalog-fdr-2026.pdf')); ?>" download="Katalog-FDR-2026.pdf">Unduh Katalog PDF <i class="fa-solid fa-file-pdf"></i></a>
                            <a class="btn btn-outline" href="<?= e(base_url('uploads/general/katalog-fdr-2026.jpg')); ?>" target="_blank" rel="noopener noreferrer">Lihat JPG Resolusi Tinggi <i class="fa-solid fa-up-right-from-square"></i></a>
                            <a class="btn btn-outline" href="<?= e(base_url('contact.php')); ?>">Tanyakan Produk <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                    <a class="spm-catalog-cover-link" href="<?= e(base_url('uploads/general/katalog-fdr-2026.pdf')); ?>" aria-label="Buka PDF katalog FDR 2026">
                        <img class="spm-catalog-cover spm-catalog-cover-light" src="<?= e(media_url('uploads/general/katalog-fdr-2026-preview.webp')); ?>" alt="Pratinjau katalog ban FDR 2026" loading="lazy">
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!--
    |--------------------------------------------------------------------------
    | CTA
    |--------------------------------------------------------------------------
    -->

    <section
        class="section section-dark"
        style="
            padding-top:65px;
            padding-bottom:65px;
        "
    >

        <div class="container">

            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:30px;
                    flex-wrap:wrap;
                "
            >

                <div>

                    <div class="section-kicker">
                        PARTNER WITH US
                    </div>

                    <h2
                        style="
                            margin:7px 0 0;
                            color:#fff;
                            font:700 28px Poppins,sans-serif;
                        "
                    >
                        Butuh informasi produk lebih lengkap?
                    </h2>

                    <p
                        style="
                            margin:8px 0 0;
                            color:rgba(255,255,255,.65);
                            font-size:12px;
                            line-height:1.7;
                            max-width:600px;
                        "
                    >
                        Hubungi tim Star Parts Motor untuk informasi
                        produk, kebutuhan distribusi, dan kerja sama.
                    </p>

                </div>

                <?php

                $whatsapp =
                    get_setting(
                        'whatsapp',
                        ''
                    );

                $whatsappNumber =
                    preg_replace(
                        '/[^0-9]/',
                        '',
                        (string) $whatsapp
                    );

                $whatsappUrl = '';

                if (
                    $whatsappNumber !== ''
                ) {

                    $whatsappUrl =
                        'https://wa.me/' .
                        $whatsappNumber .
                        '?text=' .
                        rawurlencode(
                            get_whatsapp_message()
                        );
                }

                ?>

                <?php if (
                    $whatsappUrl !== ''
                ): ?>

                    <a
                        href="<?= e(
                            $whatsappUrl
                        ) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-primary"
                    >
                        <i class="fa-brands fa-whatsapp"></i>
                        Hubungi Kami
                    </a>

                <?php else: ?>

                    <a
                        href="<?= e(
                            base_url('contact.php')
                        ) ?>"
                        class="btn btn-primary"
                    >
                        <i class="fa-solid fa-envelope"></i>
                        Hubungi Kami
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>

<?php

include __DIR__ .
    '/includes/footer.php';

?>