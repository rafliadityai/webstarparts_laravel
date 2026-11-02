<?php
// ============================================================
// FILE   : header.php
// LOKASI : starparts/includes/header.php
// ============================================================
//
// Header global website Star Parts Motor.
//
// Dipakai oleh:
// - index.php
// - about.php
// - products.php
// - product.php
// - news.php
// - news-detail.php
// - contact.php
//
// Variabel yang dapat digunakan halaman:
// - $pageTitle
// - $pageDescription
// - $pageImage
// - $bodyClass
// - $extraHead
//
// Header hanya menangani bagian <head> dan pembukaan <body>.
// Navbar berada di includes/navbar.php.
// Footer berada di includes/footer.php.
// ============================================================

declare(strict_types=1);


/*
|------------------------------------------------------------------
| DEFAULT PAGE DATA
|------------------------------------------------------------------
*/

$pageTitle = trim((string)($pageTitle ?? ''));
if ($pageTitle === '') {
    $pageTitle = trim((string) get_setting('meta_title', get_setting('site_name', 'PT Star Parts Motor')));
}
if ($pageTitle === '') { $pageTitle = 'PT Star Parts Motor | Solusi Distribusi Produk Otomotif'; }

$pageDescription = trim((string)($pageDescription ?? ''));
if ($pageDescription === '') {
    $pageDescription = trim((string) get_setting('meta_description', get_setting('site_description', 'Distributor dan partner distribusi produk otomotif berkualitas.')));
}
$pageDescription = seo_clean_text($pageDescription, 160);
if ($pageDescription === '') { $pageDescription = 'Jelajahi informasi produk otomotif, materi katalog FDR 2026, dan kanal komunikasi PT Star Parts Motor.'; }


$pageImage = trim(
    (string)(
        $pageImage
        ?? get_setting(
            'site_logo',
            ''
        )
    )
);


$bodyClass = trim(
    (string)(
        $bodyClass
        ?? ''
    )
);


/*
|------------------------------------------------------------------
| SITE DATA
|------------------------------------------------------------------
*/

$headerSiteName = trim(
    (string)get_setting(
        'site_name',
        'PT Star Parts Motor'
    )
);
if ($headerSiteName === '') { $headerSiteName = 'PT Star Parts Motor'; }




$headerLogo = trim(
    (string)get_setting(
        'site_logo',
        ''
    )
);
if ($headerLogo === '') { $headerLogo = 'uploads/general/star-parts-motor-logo.png'; }


$headerFavicon = trim(
    (string)get_setting(
        'site_favicon',
        ''
    )
);
if ($headerFavicon === '') { $headerFavicon = 'uploads/general/star-parts-motor-favicon.png'; }


/*
|------------------------------------------------------------------
| MEDIA URL
|------------------------------------------------------------------
*/

$headerLogoUrl = '';

if (
    $headerLogo !== ''
    && function_exists('media_url')
) {

    $headerLogoUrl = media_url(
        $headerLogo
    );
}


$headerFaviconUrl = '';

if (
    $headerFavicon !== ''
    && function_exists('media_url')
) {

    $headerFaviconUrl = media_url(
        $headerFavicon
    );
}


if (
    $headerFaviconUrl === ''
    && $headerLogoUrl !== ''
) {

    $headerFaviconUrl =
        $headerLogoUrl;
}


/*
|------------------------------------------------------------------
| CANONICAL URL
|------------------------------------------------------------------
*/

$headerCanonicalUrl = '';
if (isset($pageCanonicalUrl) && is_string($pageCanonicalUrl) && trim($pageCanonicalUrl) !== '') {
    $headerCanonicalUrl = absolute_url($pageCanonicalUrl);
} else {
    $requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $headerBasePath = parse_url(BASE_URL, PHP_URL_PATH);
    $headerBasePath = is_string($headerBasePath) ? rtrim($headerBasePath, '/') : '';
    $headerRelativePath = is_string($requestPath) ? $requestPath : '';
    if ($headerBasePath !== '' && str_starts_with($headerRelativePath, $headerBasePath . '/')) {
        $headerRelativePath = substr($headerRelativePath, strlen($headerBasePath) + 1);
    } else {
        $headerRelativePath = ltrim($headerRelativePath, '/');
    }
    $headerCanonicalUrl = $headerRelativePath !== ''
        ? site_url($headerRelativePath)
        : site_url();
}


/*
|------------------------------------------------------------------
| OPEN GRAPH IMAGE
|------------------------------------------------------------------
*/

$headerOgImage = '';

if (
    $pageImage !== ''
    && function_exists('media_url')
) {

    $headerOgImage = absolute_url($pageImage);

} elseif (
    $headerLogoUrl !== ''
) {

    $headerOgImage =
        $headerLogoUrl;
}

$headerOgType = (isset($ogType) && is_string($ogType) && trim($ogType) !== '') ? trim($ogType) : 'website';
$headerRobots = (isset($pageRobots) && is_string($pageRobots) && trim($pageRobots) !== '') ? trim($pageRobots) : 'index, follow';
$headerMetaKeywords = trim((string)get_setting('meta_keywords', ''));
$headerAuthor = trim((string)get_setting('site_author', $headerSiteName));
$headerGoogleVerification = trim((string)get_setting('google_site_verification', ''));
$headerOgDimensions = $pageImage !== '' ? get_image_dimensions($pageImage) : ['width'=>null,'height'=>null];


/*
|------------------------------------------------------------------
| EXTRA HEAD
|------------------------------------------------------------------
*/

$extraHeadContent = '';

if (
    isset($extraHead)
    && is_string($extraHead)
) {

    $extraHeadContent =
        $extraHead;
}


/*
|------------------------------------------------------------------
| ESCAPE TITLE / DESCRIPTION
|------------------------------------------------------------------
*/

$headerSafeTitle =
    function_exists('e')
        ? e($pageTitle)
        : htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        );


$headerSafeDescription =
    function_exists('e')
        ? e($pageDescription)
        : htmlspecialchars(
            $pageDescription,
            ENT_QUOTES,
            'UTF-8'
        );


$headerSafeSiteName =
    function_exists('e')
        ? e($headerSiteName)
        : htmlspecialchars(
            $headerSiteName,
            ENT_QUOTES,
            'UTF-8'
        );




$headerSafeCanonical =
    function_exists('e')
        ? e($headerCanonicalUrl)
        : htmlspecialchars(
            $headerCanonicalUrl,
            ENT_QUOTES,
            'UTF-8'
        );


$headerSafeOgImage =
    function_exists('e')
        ? e($headerOgImage)
        : htmlspecialchars(
            $headerOgImage,
            ENT_QUOTES,
            'UTF-8'
        );


$headerSafeFavicon =
    function_exists('e')
        ? e($headerFaviconUrl)
        : htmlspecialchars(
            $headerFaviconUrl,
            ENT_QUOTES,
            'UTF-8'
        );

?>
<!DOCTYPE html>
<html
    lang="id"
>
<head>

    <meta
        charset="UTF-8"
    >


    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <meta
        name="format-detection"
        content="telephone=no"
    >


    <meta
        name="theme-color"
        content="#d90000"
    >


    <title>
        <?= $headerSafeTitle; ?>
    </title>


    <meta
        name="description"
        content="<?= $headerSafeDescription; ?>"
    >


    <meta
        name="robots"
        content="<?= e($headerRobots . ', max-image-preview:large'); ?>"
    >

    <?php if ($headerGoogleVerification !== ""): ?>
        <meta name="google-site-verification" content="<?= e($headerGoogleVerification); ?>">
    <?php endif; ?>

    <meta
        name="author"
        content="<?= e($headerAuthor); ?>"
    >



    <?php if (
        $headerCanonicalUrl !== ''
    ): ?>

        <link
            rel="canonical"
            href="<?= $headerSafeCanonical; ?>"
        >

    <?php endif; ?>


    <!-- ======================================================
         OPEN GRAPH
    ======================================================= -->

    <meta
        property="og:type"
        content="<?= e($headerOgType); ?>"
    >


    <meta
        property="og:site_name"
        content="<?= $headerSafeSiteName; ?>"
    >

    <meta
        property="og:locale"
        content="id_ID"
    >


    <meta
        property="og:title"
        content="<?= $headerSafeTitle; ?>"
    >


    <meta
        property="og:description"
        content="<?= $headerSafeDescription; ?>"
    >


    <?php if (
        $headerCanonicalUrl !== ''
    ): ?>

        <meta
            property="og:url"
            content="<?= $headerSafeCanonical; ?>"
        >

    <?php endif; ?>


    <?php if (
        $headerOgImage !== ''
    ): ?>

        <meta
            property="og:image"
            content="<?= $headerSafeOgImage; ?>"
        >

        <meta
            property="og:image:alt"
            content="<?= $headerSafeTitle; ?>"
        >

        <?php if (!empty($headerOgDimensions['width']) && !empty($headerOgDimensions['height'])): ?>
            <meta property="og:image:width" content="<?= (int)$headerOgDimensions['width']; ?>">
            <meta property="og:image:height" content="<?= (int)$headerOgDimensions['height']; ?>">
        <?php endif; ?>

    <?php endif; ?>


    <!-- ======================================================
         TWITTER CARD
    ======================================================= -->

    <meta
        name="twitter:card"
        content="summary_large_image"
    >


    <meta
        name="twitter:title"
        content="<?= $headerSafeTitle; ?>"
    >


    <meta
        name="twitter:description"
        content="<?= $headerSafeDescription; ?>"
    >


    <?php if (
        $headerOgImage !== ''
    ): ?>

        <meta
            name="twitter:image"
            content="<?= $headerSafeOgImage; ?>"
        >

    <?php endif; ?>


    <!-- ======================================================
         FAVICON
    ======================================================= -->

    <?php if (
        $headerFaviconUrl !== ''
    ): ?>

        <link
            rel="icon"
            href="<?= $headerSafeFavicon; ?>"
        >

        <link
            rel="apple-touch-icon"
            href="<?= $headerSafeFavicon; ?>"
        >

    <?php endif; ?>


    <!-- ======================================================
         FONT
    ======================================================= -->

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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ======================================================
         FONT AWESOME
    ======================================================= -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        referrerpolicy="no-referrer"
    >


    <!-- ======================================================
         SWIPER
    ======================================================= -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
    >


    <!-- ======================================================
         AOS
    ======================================================= -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/aos@2.3.4/dist/aos.css"
    >


    <!-- ======================================================
         MAIN CSS
    ======================================================= -->

    <?php
    /*
     * Main stylesheet
     *
     * Gunakan URL absolut berdasarkan BASE_URL agar asset tetap
     * ditemukan dengan benar pada semua halaman, termasuk halaman
     * yang berada di subfolder atau memiliki query string.
     * Query version menggunakan filemtime() untuk mencegah browser
     * mempertahankan cache CSS lama setelah file diperbarui.
     */

    $headerStylePath =
        __DIR__ . '/../assets/css/style.css';

    $headerStyleVersion =
        is_file($headerStylePath)
            ? (string) filemtime($headerStylePath)
            : (string) time();

    $headerStyleUrl =
        function_exists('base_url')
            ? base_url('assets/css/style.css')
            : 'assets/css/style.css';

    $headerStyleUrl .=
        (str_contains($headerStyleUrl, '?') ? '&' : '?')
        . 'v='
        . rawurlencode($headerStyleVersion);
    ?>

    <link
        rel="stylesheet"
        type="text/css"
        href="<?= e($headerStyleUrl); ?>"
        media="all"
    >


    <!-- ======================================================
         STRUCTURED DATA
    ======================================================= -->

    <?php
    $headerStructuredData = [];
    $headerOrganization = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $headerSiteName,
        'url' => site_url(),
    ];
    if ($headerLogo !== '') $headerOrganization['logo'] = absolute_url($headerLogo);
    $headerPhone = trim((string)get_setting('phone_primary', ''));
    $headerEmail = trim((string)get_setting('email', ''));
    if ($headerPhone !== '') $headerOrganization['telephone'] = $headerPhone;
    if ($headerEmail !== '') $headerOrganization['email'] = $headerEmail;
    $headerAddress = trim((string)get_setting('address', ''));
    if ($headerAddress !== '') {
        $headerOrganization['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $headerAddress,
            'addressLocality' => 'Bandung',
            'addressRegion' => 'Jawa Barat',
            'addressCountry' => 'ID',
        ];
    }
    $headerSameAs = [];
    foreach (['facebook_url','instagram_url','youtube_url','tiktok_url'] as $socialKey) {
        $social = trim((string)get_setting($socialKey, ''));
        if ($social !== '') $headerSameAs[] = $social;
    }
    if ($headerSameAs) $headerOrganization['sameAs'] = $headerSameAs;
    if ($headerPhone !== '' || $headerEmail !== '') {
        $headerOrganization['contactPoint'] = [
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
        ];
        if ($headerPhone !== '') $headerOrganization['contactPoint']['telephone'] = $headerPhone;
        if ($headerEmail !== '') $headerOrganization['contactPoint']['email'] = $headerEmail;
        $headerOrganization['contactPoint']['availableLanguage'] = ['id'];
    }
    $headerStructuredData[] = $headerOrganization;

    $headerShowLocalBusiness = (($bodyClass ?? '') === 'home-page') || (($bodyClass ?? '') === 'contact-page');
    if ($headerShowLocalBusiness) {
        try {
        $headerLocations = get_locations(true);
        foreach ($headerLocations as $headerLocation) {
            $localName = trim((string)($headerLocation['name'] ?? $headerSiteName));
            $local = [
                '@context' => 'https://schema.org',
                '@type' => 'LocalBusiness',
                'name' => $localName,
                'url' => site_url(),
            ];
            $localAddress = trim((string)($headerLocation['address'] ?? ''));
            if ($localAddress !== '') {
                $local['address'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $localAddress,
                    'addressLocality' => 'Bandung',
                    'addressRegion' => 'Jawa Barat',
                    'addressCountry' => 'ID',
                ];
                }
            $localPhone = trim((string)($headerLocation['phone'] ?? $headerPhone));
            if ($localPhone !== '') $local['telephone'] = $localPhone;
            $lat = isset($headerLocation['latitude']) ? trim((string)$headerLocation['latitude']) : '';
            $lng = isset($headerLocation['longitude']) ? trim((string)$headerLocation['longitude']) : '';
            if ($lat !== '' && $lng !== '') {
                $local['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => (float)$lat,
                    'longitude' => (float)$lng,
                ];
            }
            $maps = trim((string)($headerLocation['maps_url'] ?? ''));
            if ($maps !== '') $local['hasMap'] = $maps;
            if ($headerLogo !== '') $local['image'] = absolute_url($headerLogo);
            $headerStructuredData[] = $local;
        }
        } catch (Throwable $e) {
            // Lokasi opsional; jangan hentikan rendering website bila tabel/data belum tersedia.
    }
    }
    $headerStructuredData[] = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $pageTitle,
        'description' => $pageDescription,
        'url' => $headerCanonicalUrl,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => $headerSiteName,
            'url' => site_url(),
        ],
    ];
    if (($bodyClass ?? '') === 'home-page' || $headerCanonicalUrl === site_url()) {
        $headerStructuredData[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $headerSiteName,
            'url' => site_url(),
        ];
    }
    if (isset($pageStructuredData) && is_array($pageStructuredData)) {
        foreach ($pageStructuredData as $schemaItem) if (is_array($schemaItem) && $schemaItem) $headerStructuredData[] = $schemaItem;
    }
    ?>
    <?php foreach ($headerStructuredData as $schemaItem): ?>
        <script type="application/ld+json"><?= json_encode($schemaItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <?php endforeach; ?>


    <!-- ======================================================
         EXTRA HEAD CONTENT
    ======================================================= -->

    <?= $extraHeadContent; ?>


</head>


<body
    class="<?= function_exists('e')
        ? e($bodyClass)
        : htmlspecialchars(
            $bodyClass,
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
>


<!-- Phase 16: oversized logo/tagline preloader removed. -->