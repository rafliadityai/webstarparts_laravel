<?php
// =========================================================
// index.php
// LOKASI : starparts/index.php
// Landing Page Utama PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PAGE META
|--------------------------------------------------------------------------
*/

$pageTitle = get_setting('home_page_title', get_setting('meta_title', 'PT Star Parts Motor | Distributor Produk Otomotif'));
$pageDescription = get_setting('home_page_description', get_setting('meta_description', 'Distributor dan partner distribusi produk otomotif berkualitas.'));
$pageImage = trim((string)get_setting('home_og_image', get_setting('site_logo', '')));
if ($pageImage === '') { $pageImage = 'uploads/general/gedung-star-parts-motor.webp'; }
$pageCanonicalUrl = site_url();
$bodyClass = 'home-page';


/*
|--------------------------------------------------------------------------
| BASIC SITE DATA
|--------------------------------------------------------------------------
*/

$siteName = trim(
    (string) get_setting(
        'site_name',
        'PT Star Parts Motor'
    )
);
if ($siteName === '') { $siteName = 'PT Star Parts Motor'; }

$siteDescription = trim(
    (string) get_setting(
        'site_description',
        'PT Star Parts Motor menghadirkan informasi produk otomotif dan layanan distribusi untuk kebutuhan retail, dealer, serta mitra bisnis.'
    )
);
if ($siteDescription === '') { $siteDescription = 'PT Star Parts Motor menghadirkan informasi produk otomotif dan layanan distribusi untuk kebutuhan retail, dealer, serta mitra bisnis.'; }

$phone = trim(
    (string) get_setting(
        'phone_primary',
        ''
    )
);

$whatsapp = trim(
    (string) get_setting(
        'whatsapp',
        ''
    )
);

$globalCtaPrefix = trim((string) get_setting('cta_title_prefix', 'Siap Menjadi'));
$globalCtaHighlight = trim((string) get_setting('cta_title_highlight', 'Mitra Star Parts Motor?'));
$globalCtaText = trim((string) get_setting('cta_text', 'Mari bangun kerja sama jangka panjang dan tumbuh bersama dalam industri otomotif.'));
$globalCtaWhatsappText = trim((string) get_setting('cta_whatsapp_text', 'Hubungi via WhatsApp'));
$globalCtaButtonText = trim((string) get_setting('cta_button_text', 'Hubungi Kami'));
$globalCtaButtonUrl = trim((string) get_setting('cta_button_url', 'contact.php'));

$email = trim(
    (string) get_setting(
        'email',
        ''
    )
);

$address = trim(
    (string) get_setting(
        'address',
        ''
    )
);

$aboutTitle = trim(
    (string) get_setting(
        'about_title',
        'Mitra Distribusi Otomotif untuk Kebutuhan Bisnis Anda'
    )
);
if ($aboutTitle === '') { $aboutTitle = 'Mitra Distribusi Otomotif untuk Kebutuhan Bisnis Anda'; }

$aboutContent = trim(
    (string) get_setting(
        'about_content',
        ''
    )
);

$aboutImage = trim(
    (string) get_setting(
        'about_image',
        ''
    )
);
if ($aboutImage === '') { $aboutImage = 'uploads/general/gedung-star-parts-motor.webp'; }

$heroTitle = trim(
    (string) get_setting(
        'hero_title',
        'Solusi Distribusi Produk Otomotif yang Andal'
    )
);
if ($heroTitle === '') { $heroTitle = 'Solusi Distribusi Produk Otomotif yang Andal'; }

$heroSubtitle = trim(
    (string) get_setting(
        'hero_subtitle',
        'Jelajahi pilihan produk ban dan kebutuhan otomotif dari brand pilihan. Tim kami siap membantu informasi produk dan peluang kerja sama.'
    )
);
if ($heroSubtitle === '') { $heroSubtitle = 'Jelajahi pilihan produk ban dan kebutuhan otomotif dari brand pilihan. Tim kami siap membantu informasi produk dan peluang kerja sama.'; }

$heroButtonText = trim(
    (string) get_setting(
        'hero_button_text',
        'Jelajahi Produk'
    )
);
if ($heroButtonText === '') { $heroButtonText = 'Jelajahi Produk'; }

$heroButtonUrl = trim(
    (string) get_setting(
        'hero_button_url',
        'products.php'
    )
);

$vision = trim(
    (string) get_setting(
        'vision',
        ''
    )
);

$mission = trim(
    (string) get_setting(
        'mission',
        ''
    )
);


/*
|--------------------------------------------------------------------------
| WHATSAPP LINK
|--------------------------------------------------------------------------
*/

$whatsappNumber = '';

if ($whatsapp !== '') {

    $whatsappNumber = preg_replace(
        '/[^0-9]/',
        '',
        $whatsapp
    );

    if (
        str_starts_with(
            $whatsappNumber,
            '0'
        )
    ) {

        $whatsappNumber =
            '62' .
            substr(
                $whatsappNumber,
                1
            );
    }
}

$whatsappLink = '#';

if ($whatsappNumber !== '') {

    $whatsappLink =
        'https://wa.me/' .
        $whatsappNumber .
        '?text=' .
        rawurlencode(
            get_whatsapp_message()
        );
}


/*
|--------------------------------------------------------------------------
| HERO BANNERS
|--------------------------------------------------------------------------
*/

$banners = [];

/*
|--------------------------------------------------------------------------
| FDR PROMO SLIDES (asset lokal dari folder gambar slide)
|--------------------------------------------------------------------------
| Gambar banner sudah memuat teks dan tombol Read More. Seluruh banner
| dibuat sebagai tautan ke detail produk yang sesuai agar area tombol
| pada gambar tetap dapat diklik tanpa menambahkan elemen yang mengubah
| desain asli banner.
*/
$fdrPromoSlides = [
    ['image' => 'gambar slide/1.png', 'name' => 'Sport XR Evo', 'slug' => 'sport-xr-evo'],
    ['image' => 'gambar slide/2.png', 'name' => 'Blaze MP Tourer', 'slug' => 'blaze-mp-tourer'],
    ['image' => 'gambar slide/3.png', 'name' => 'Blaze MP Road', 'slug' => 'blaze-mp-road'],
    ['image' => 'gambar slide/4.png', 'name' => 'Velogrip', 'slug' => 'velogrip'],
    ['image' => 'gambar slide/5.png', 'name' => 'Blaze MP Road', 'slug' => 'blaze-mp-road'],
    ['image' => 'gambar slide/6.png', 'name' => 'Blaze MP Road', 'slug' => 'blaze-mp-road'],
];
$fdrPromoSlides = array_values(array_filter(
    $fdrPromoSlides,
    static fn(array $slide): bool => is_file(__DIR__ . '/' . $slide['image'])
));
/* Reverse the DOM list so reverse-direction autoplay still follows 1.png -> 6.png. */
$fdrPromoSlidesForRender = array_reverse($fdrPromoSlides);

try {

    $bannerStmt = $pdo->query(
        "SELECT
            id,
            title,
            subtitle,
            button_text,
            button_url,
            image,
            image_alt,
            video,
            sort_order
         FROM banners
         WHERE is_active = 1
           AND placement = 'home'
         ORDER BY sort_order ASC, id DESC"
    );

    $banners =
        $bannerStmt->fetchAll();
    if ($pageImage === '') {
        foreach ($banners as $bannerCandidate) {
            $candidateImage = trim((string)($bannerCandidate['image'] ?? ''));
            if ($candidateImage !== '') { $pageImage = $candidateImage; break; }
        }
    }

} catch (Throwable $e) {

    $banners = [];
}

$catalogTopBanners = [];
try {
    $catalogBannerStmt = $pdo->prepare(
        "SELECT id, title, subtitle, button_text, button_url, image, image_alt, image_link, video
         FROM banners
         WHERE is_active = 1
           AND placement IN ('catalog_top', 'catalog_bottom')
         ORDER BY sort_order ASC, id DESC"
    );
    $catalogBannerStmt->execute();
    $catalogTopBanners = $catalogBannerStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $catalogTopBanners = [];
}


/*
|--------------------------------------------------------------------------
| PRODUCT DATA
|--------------------------------------------------------------------------
*/

$featuredProducts = [];

try {

    $featuredProducts =
        get_products(
            null,
            true,
            6
        );

    /*
     * Apabila belum ada produk featured,
     * ambil beberapa produk aktif.
     */

    if (
        empty($featuredProducts)
    ) {

        $featuredProducts =
            get_products(
                null,
                false,
                6
            );
    }

} catch (Throwable $e) {

    $featuredProducts = [];
}


/*
|--------------------------------------------------------------------------
| CATEGORY DATA
|--------------------------------------------------------------------------
*/

$categories = [];

try {

    $categories =
        get_categories(true);

} catch (Throwable $e) {

    $categories = [];
}

$displayCategories = $categories;
if (empty($displayCategories)) {
    $displayCategories = [
        [
            'name' => 'Ban Motor FDR',
            'slug' => 'ban-motor-fdr',
            'description' => 'Jelajahi materi katalog ban FDR dan tanyakan tipe serta ukuran yang sesuai kebutuhan.',
            'image' => 'uploads/general/display-ban-fdr.webp',
            'href' => 'products.php#catalog'
        ],
        [
            'name' => 'Penerangan Otomotif',
            'slug' => 'penerangan-otomotif',
            'description' => 'Informasi produk penerangan otomotif dari materi brand OSRAM yang disediakan.',
            'image' => 'uploads/general/osram-category-tile.webp',
            'href' => 'contact.php'
        ],
        [
            'name' => 'Katalog FDR 2026',
            'slug' => 'katalog-fdr-2026',
            'description' => 'Buka katalog visual untuk melihat lini produk dan ukuran yang tercantum di materi FDR 2026.',
            'image' => 'uploads/general/katalog-fdr-2026-card.webp',
            'href' => 'products.php#catalog'
        ]
    ];
}


/*
|--------------------------------------------------------------------------
| BRAND DATA
|--------------------------------------------------------------------------
*/

$brands = [];

try {

    $brands =
        get_brands(true);

} catch (Throwable $e) {

    $brands = [];
}

$displayBrands = $brands;
if (empty($displayBrands)) {
    $displayBrands = [
        [
            'name' => 'FDR Tire',
            'logo' => 'uploads/general/fdr-tire-logo.webp',
            'description' => 'Materi katalog ban motor FDR 2026 yang disediakan.',
            'website_url' => site_url('products.php#catalog')
        ],
        [
            'name' => 'OSRAM',
            'logo' => 'uploads/general/logo-osram.png',
            'description' => 'Materi logo OSRAM yang disertakan untuk informasi produk penerangan otomotif.',
            'website_url' => site_url('contact.php')
        ]
    ];
}


/*
|--------------------------------------------------------------------------
| POST DATA
|--------------------------------------------------------------------------
*/

$latestPosts = [];

try {

    $latestPosts =
        get_posts(3);

} catch (Throwable $e) {

    $latestPosts = [];
}


/*
|--------------------------------------------------------------------------
| COMPANY STATS
|--------------------------------------------------------------------------
*/

$companyStats = [];

try {

    $companyStats =
        get_company_stats();

    // Content Center can update the displayed numbers while the existing
    // company_stats table remains the source for labels/order/icons.
    $statSettingKeys = [
        'stat_years',
        'stat_partners',
        'stat_products',
        'stat_regions',
    ];
    foreach ($statSettingKeys as $statIndex => $statKey) {
        $statOverride = trim((string) get_setting($statKey, ''));
        if ($statOverride !== '' && isset($companyStats[$statIndex])) {
            $companyStats[$statIndex]['number_value'] = $statOverride;
        }
    }

} catch (Throwable $e) {

    $companyStats = [];
}


/*
|--------------------------------------------------------------------------
| LOCATIONS
|--------------------------------------------------------------------------
*/

$locations = [];

try {

    $locations =
        get_locations(true);

} catch (Throwable $e) {

    $locations = [];
}


/*
|--------------------------------------------------------------------------
| COMPANY LOGO
|--------------------------------------------------------------------------
*/

$siteLogo =
    trim(
        (string) get_setting(
            'site_logo',
            ''
        )
    );
if ($siteLogo === '') { $siteLogo = 'uploads/general/star-parts-motor-logo.png'; }

$siteLogoUrl =
    $siteLogo !== ''
        ? media_url($siteLogo)
        : '';


/*
|--------------------------------------------------------------------------
| ABOUT IMAGE
|--------------------------------------------------------------------------
*/

$aboutImageIsCompanyLogo = preg_match('/(?:star[-_ ]?parts[-_ ]?motor[-_ ]?logo|site[-_ ]?logo)/i', basename($aboutImage)) === 1;
$aboutImageUrl = (!$aboutImageIsCompanyLogo && $aboutImage !== '')
    ? media_url($aboutImage)
    : '';


/*
|--------------------------------------------------------------------------
| SOCIAL MEDIA
|--------------------------------------------------------------------------
*/

$instagramUrl = trim(
    (string) get_setting(
        'instagram_url',
        ''
    )
);

$facebookUrl = trim(
    (string) get_setting(
        'facebook_url',
        ''
    )
);

$youtubeUrl = trim(
    (string) get_setting(
        'youtube_url',
        ''
    )
);

$tiktokUrl = trim(
    (string) get_setting(
        'tiktok_url',
        ''
    )
);


/*
|--------------------------------------------------------------------------
| EXTRA HEAD
|--------------------------------------------------------------------------
*/

$extraHead = '
<meta name="description" content="' .
    e($pageDescription) .
    '">
';


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';


/*
|--------------------------------------------------------------------------
| NAVBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/navbar.php';

?>


<!-- =======================================================
     MAIN CONTENT
======================================================== -->

<main id="main-content">


<!-- =======================================================
     HERO
======================================================== -->

<section
    id="home"
    class="hero<?= empty($banners) && !empty($fdrPromoSlides) ? ' fdr-promo-hero' : ''; ?>"
>

    <?php if (!empty($banners)): ?>

        <div
            class="swiper hero-slider hero-swiper"
            data-autoplay-delay="4500"
            data-autoplay-direction="forward"
            aria-label="Banner Utama PT Star Parts Motor"
        >

            <div class="swiper-wrapper">

                <?php foreach (
                    $banners
                    as $index => $banner
                ): ?>

                    <?php

                    $bannerTitle =
                        trim(
                            (string)
                            (
                                $banner['title']
                                ?? ''
                            )
                        );

                    $bannerSubtitle =
                        trim(
                            (string)
                            (
                                $banner['subtitle']
                                ?? ''
                            )
                        );

                    $bannerButtonText =
                        trim(
                            (string)
                            (
                                $banner['button_text']
                                ?? ''
                            )
                        );

                    $bannerButtonUrl =
                        trim(
                            (string)
                            (
                                $banner['button_url']
                                ?? ''
                            )
                        );

                    $bannerImage =
                        trim(
                            (string)
                            (
                                $banner['image']
                                ?? ''
                            )
                        );

                    $bannerVideo =
                        trim(
                            (string)
                            (
                                $banner['video']
                                ?? ''
                            )
                        );
                    if ($bannerImage === '' && $bannerVideo === '') {
                        $bannerImage = 'uploads/general/gedung-star-parts-motor.webp';
                    }

                    $bannerImageUrl =
                        $bannerImage !== ''
                            ? media_url(
                                $bannerImage
                            )
                            : '';

                    $bannerImageDimensions =
                        $bannerImage !== ''
                            ? get_image_dimensions($bannerImage)
                            : ['width'=>null,'height'=>null,'ratio'=>null];

                    $bannerFitClass = '';
                    if (!empty($bannerImageDimensions['ratio']) && ($bannerImageDimensions['ratio'] < 1.15 || $bannerImageDimensions['ratio'] > 2.60)) {
                        $bannerFitClass = ' hero-image-contain';
                    }

                    $bannerVideoUrl =
                        $bannerVideo !== ''
                            ? media_url(
                                $bannerVideo
                            )
                            : '';

                    ?>

                    <div
                        class="swiper-slide"
                    >

                        <div class="hero-media<?= e($bannerFitClass); ?>">

                            <?php if (
                                $bannerVideoUrl !== ''
                            ): ?>

                                <video
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                    preload="<?= $index === 0 ? 'auto' : 'metadata'; ?>"
                                >

                                    <source
                                        src="<?= e(
                                            $bannerVideoUrl
                                        ); ?>"
                                        type="video/mp4"
                                    >

                                </video>

                            <?php elseif (
                                $bannerImageUrl !== ''
                            ): ?>

                                <img src="<?= e($bannerImageUrl); ?>" <?= media_image_attrs($bannerImage, trim((string)($banner['image_alt'] ?? '')) !== '' ? trim((string)$banner['image_alt']) : ($bannerTitle ?: $siteName), null, $index > 0 ? 'lazy' : 'eager', $index === 0, '100vw'); ?>>

                            <?php endif; ?>

                            <div
                                class="hero-overlay"
                            ></div>

                        </div>


                        <?php if ($bannerTitle !== '' || $bannerSubtitle !== '' || $bannerButtonText !== ''): ?>
                            <div
                                class="hero-container container"
                            >

                                <div
                                    class="hero-content"
                                >

<?php $heroHeadingTag = ($index === 0) ? 'h1' : 'h2'; ?>
                                    <?php if ($bannerTitle !== ''): ?>
                                        <<?= $heroHeadingTag; ?>
                                            class="hero-title"
                                            data-aos="fade-up"
                                            data-aos-delay="100"
                                        >
                                            <?= nl2br(e($bannerTitle)); ?>
                                        </<?= $heroHeadingTag; ?>>
                                    <?php endif; ?>


                                    <?php if (
                                        $bannerSubtitle !== ''
                                    ): ?>

                                        <p
                                            class="hero-description"
                                            data-aos="fade-up"
                                            data-aos-delay="200"
                                        >

                                            <?= nl2br(
                                                e(
                                                    $bannerSubtitle
                                                )
                                            ); ?>

                                        </p>

                                    <?php endif; ?>


                                    <div
                                        class="hero-actions"
                                        data-aos="fade-up"
                                        data-aos-delay="300"
                                    >

                                        <?php
                                        $bannerFinalUrl = '';
                                        $bannerIsExternal = false;
                                        if ($bannerButtonUrl !== '') {
                                            $bannerIsExternal = (bool) preg_match('~^https?://~i', $bannerButtonUrl);
                                            $bannerFinalUrl = $bannerIsExternal ? $bannerButtonUrl : base_url(ltrim($bannerButtonUrl, '/'));
                                        } elseif ($bannerButtonText !== '') {
                                            $bannerFinalUrl = base_url('products.php');
                                        }
                                        ?>

                                        <?php if ($bannerButtonText !== ''): ?>

                                            <a
                                                href="<?= e($bannerFinalUrl !== '' ? $bannerFinalUrl : base_url('products.php')); ?>"
                                                class="btn btn-primary hero-btn-custom"
                                                <?= $bannerIsExternal ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                                            >

                                                <?= e($bannerButtonText); ?>

                                                <i
                                                    class="fa-solid fa-arrow-right"
                                                    aria-hidden="true"
                                                ></i>

                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>
                        <?php endif; ?>


                        <div
                            class="hero-accent"
                            aria-hidden="true"
                        ></div>

                    </div>

                <?php endforeach; ?>

            </div>


            <div
                class="swiper-button-prev"
                aria-label="Slide sebelumnya"
            ></div>

            <div
                class="swiper-button-next"
                aria-label="Slide berikutnya"
            ></div>

            <div
                class="swiper-pagination"
            ></div>

        </div>

    <?php elseif (!empty($fdrPromoSlides)): ?>

        <div
            class="swiper hero-slider hero-swiper fdr-promo-slider"
            data-autoplay-delay="4000"
            data-autoplay-direction="forward"
            data-initial-slide="<?= max(0, count($fdrPromoSlidesForRender) - 1); ?>"
            aria-label="Promosi produk ban FDR"
        >
            <div class="swiper-wrapper">
                <?php foreach ($fdrPromoSlidesForRender as $index => $slide): ?>
                  <div class="swiper-slide">
    <div class="hero-container container hero-split-grid">

        <!-- KIRI: TEKS & TOMBOL -->
        <?php if ($bannerTitle !== '' || $bannerSubtitle !== '' || $bannerButtonText !== ''): ?>
            <div class="hero-content">
                <?php $heroHeadingTag = ($index === 0) ? 'h1' : 'h2'; ?>
                <?php if ($bannerTitle !== ''): ?>
                    <<?= $heroHeadingTag; ?> class="hero-title" data-aos="fade-up" data-aos-delay="100">
                        <?= nl2br(e($bannerTitle)); ?>
                    </<?= $heroHeadingTag; ?>>
                <?php endif; ?>

                <?php if ($bannerSubtitle !== ''): ?>
                    <p class="hero-description" data-aos="fade-up" data-aos-delay="200">
                        <?= nl2br(e($bannerSubtitle)); ?>
                    </p>
                <?php endif; ?>

                <?php
                $bannerFinalUrl = '';
                $bannerIsExternal = false;
                if ($bannerButtonUrl !== '') {
                    $bannerIsExternal = (bool) preg_match('~^https?://~i', $bannerButtonUrl);
                    $bannerFinalUrl = $bannerIsExternal ? $bannerButtonUrl : base_url(ltrim($bannerButtonUrl, '/'));
                } elseif ($bannerButtonText !== '') {
                    $bannerFinalUrl = base_url('products.php');
                }
                ?>

                <?php if ($bannerButtonText !== ''): ?>
                    <div class="hero-actions" data-aos="fade-up" data-aos-delay="300">
                        <a
                            href="<?= e($bannerFinalUrl !== '' ? $bannerFinalUrl : base_url('products.php')); ?>"
                            class="btn btn-primary hero-btn-custom"
                            <?= $bannerIsExternal ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                        >
                            <?= e($bannerButtonText); ?>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- KANAN: GAMBAR BANNER UTUH (RASIO 2:1) -->
        <div class="hero-media<?= e($bannerFitClass); ?>">
            <?php if ($bannerVideoUrl !== ''): ?>
                <video autoplay muted loop playsinline preload="<?= $index === 0 ? 'auto' : 'metadata'; ?>">
                    <source src="<?= e($bannerVideoUrl); ?>" type="video/mp4">
                </video>
            <?php elseif ($bannerImageUrl !== ''): ?>
                <img src="<?= e($bannerImageUrl); ?>" <?= media_image_attrs($bannerImage, trim((string)($banner['image_alt'] ?? '')) !== '' ? trim((string)$banner['image_alt']) : ($bannerTitle ?: $siteName), null, $index > 0 ? 'lazy' : 'eager', $index === 0, '(max-width: 900px) 100vw, 58vw'); ?>>
            <?php endif; ?>
        </div>

    </div>
</div>
                <?php endforeach; ?>
            </div>

            <div class="swiper-button-prev" aria-label="Slide sebelumnya"></div>
            <div class="swiper-button-next" aria-label="Slide berikutnya"></div>
            <div class="swiper-pagination"></div>
        </div>

    <?php else: ?>


        <!-- =================================================
             DEFAULT HERO
        ================================================== -->

        <div
            class="hero-media"
        >

            <img
                src="<?= e(media_url('uploads/general/gedung-star-parts-motor.webp')); ?>"
                alt="Gedung dan area operasional PT Star Parts Motor"
                fetchpriority="high"
                decoding="async"
            >

            <div
                class="hero-overlay"
            ></div>

        </div>


        <div
            class="hero-container container"
        >

            <div
                class="hero-content"
            >

<h1
                    class="hero-title"
                    data-aos="fade-up"
                    data-aos-delay="100"
                >

                    <?= nl2br(
                        e(
                            $heroTitle
                        )
                    ); ?>

                </h1>


                <p
                    class="hero-description"
                    data-aos="fade-up"
                    data-aos-delay="200"
                >

                    <?= nl2br(
                        e(
                            $heroSubtitle
                        )
                    ); ?>

                </p>


                <div
                    class="hero-actions"
                    data-aos="fade-up"
                    data-aos-delay="300"
                >

                    <a
                        href="<?= e(
                            base_url(
                                ltrim(
                                    $heroButtonUrl,
                                    '/'
                                )
                            )
                        ); ?>"
                        class="btn btn-primary"
                    >

                        <?= e(
                            $heroButtonText
                        ); ?>

                        <i
                            class="fa-solid fa-arrow-right"
                            aria-hidden="true"
                        ></i>

                    </a>


                    <?php if (
                        $whatsappNumber !== ''
                    ): ?>

                        <a
                            href="<?= e(
                                $whatsappLink
                            ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-white"
                        >

                            <i
                                class="fa-brands fa-whatsapp"
                                aria-hidden="true"
                            ></i>

                            Konsultasi

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <div
            class="hero-accent"
            aria-hidden="true"
        ></div>

    <?php endif; ?>


    <div
        class="hero-scroll"
        aria-hidden="true"
    >

        <span>
            Scroll
        </span>

        <i
            class="fa-solid fa-chevron-down"
        ></i>

    </div>

</section>


<!-- Phase 16: obsolete brand ticker removed; statistics remain below hero. -->

<!-- =======================================================
     COMPANY STATS
======================================================== -->

<?php if (!empty($companyStats)): ?>

<section
    class="stats-section"
>

    <div class="container">

        <div
            class="stats-wrapper"
        >

            <?php foreach (
                $companyStats
                as $stat
            ): ?>

                <?php

                $number =
                    trim(
                        (string)
                        (
                            $stat['number_value']
                            ?? ''
                        )
                    );

                $label =
                    trim(
                        (string)
                        (
                            $stat['label']
                            ?? ''
                        )
                    );

                if (
                    $number === ''
                ) {
                    $number = '0';
                }

                ?>

                <div
                    class="stat-item"
                    data-aos="fade-up"
                >

                    <span
                        class="stat-number"
                        data-counter="<?= e(
                            $number
                        ); ?>"
                    >
                        <?= e(
                            $number
                        ); ?>
                    </span>

                    <span
                        class="stat-label"
                    >
                        <?= e(
                            $label
                        ); ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>

<?php endif; ?>


<!-- =======================================================
     ABOUT SECTION
======================================================== -->

<section
    id="about"
    class="section"
>

    <div class="container">

        <div
            class="about-grid<?= $aboutImageIsCompanyLogo ? ' about-grid-no-visual' : ''; ?>"
        >

            <div
                class="about-content"
                data-aos="fade-right"
            >

                <div
                    class="section-kicker"
                >
                    TENTANG KAMI
                </div>


                <h2
                    class="about-title"
                >

                    <?= e(
                        $aboutTitle
                    ); ?>

                </h2>


                <?php if (
                    $aboutContent !== ''
                ): ?>

                    <div
                        class="about-text"
                    >

                        <?= $aboutContent; ?>

                    </div>

                <?php else: ?>

                    <div
                        class="about-text"
                    >

                        <p>
                            <?= e(
                                $siteDescription
                            ); ?>
                        </p>

                        <p>
                            Star Parts Motor hadir untuk mendukung
                            kebutuhan distribusi produk otomotif
                            melalui jaringan, pelayanan dan
                            operasional yang profesional.
                        </p>

                    </div>

                <?php endif; ?>


                <?php if (
                    $vision !== ''
                    ||
                    $mission !== ''
                ): ?>

                    <div
                        style="
                            display:grid;
                            grid-template-columns:
                                repeat(
                                    2,
                                    minmax(0,1fr)
                                );
                            gap:16px;
                            margin-top:28px;
                        "
                    >

                        <?php if (
                            $vision !== ''
                        ): ?>

                            <div
                                style="
                                    padding:20px;
                                    background:#fafafa;
                                    border:1px solid rgba(0,0,0,.07);
                                    border-radius:14px;
                                "
                            >

                                <div
                                    style="
                                        color:var(--spm-red);
                                        font-family:var(--spm-font);
                                        font-size:12px;
                                        font-weight:800;
                                        margin-bottom:7px;
                                    "
                                >
                                    VISI
                                </div>

                                <div
                                    style="
                                        color:var(--spm-text-soft);
                                        font-size:12px;
                                        line-height:1.7;
                                    "
                                >
                                    <?= nl2br(
                                        e(
                                            $vision
                                        )
                                    ); ?>
                                </div>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $mission !== ''
                        ): ?>

                            <div
                                style="
                                    padding:20px;
                                    background:#fafafa;
                                    border:1px solid rgba(0,0,0,.07);
                                    border-radius:14px;
                                "
                            >

                                <div
                                    style="
                                        color:var(--spm-red);
                                        font-family:var(--spm-font);
                                        font-size:12px;
                                        font-weight:800;
                                        margin-bottom:7px;
                                    "
                                >
                                    MISI
                                </div>

                                <div
                                    style="
                                        color:var(--spm-text-soft);
                                        font-size:12px;
                                        line-height:1.7;
                                    "
                                >
                                    <?= nl2br(
                                        e(
                                            $mission
                                        )
                                    ); ?>
                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


                <div
                    style="
                        margin-top:28px;
                    "
                >

                    <a
                        href="<?= e(
                            base_url(
                                'about.php'
                            )
                        ); ?>"
                        class="btn btn-dark"
                    >

                        Selengkapnya Tentang Kami

                        <i
                            class="fa-solid fa-arrow-right"
                            aria-hidden="true"
                        ></i>

                    </a>

                </div>

            </div>


            <?php if (!$aboutImageIsCompanyLogo): ?>
            <div
                class="about-image-wrap"
                data-aos="fade-left"
            >

                <?php if (
                    $aboutImageUrl !== ''
                ): ?>

                    <div
                        class="about-image"
                    >

                        <img
                            src="<?= e(
                                $aboutImageUrl
                            ); ?>"
                            alt="<?= e(
                                $aboutTitle
                            ); ?>"
                            loading="lazy"
                        >

                    </div>

                <?php else: ?>

                    <div
                        class="about-image"
                    >

                        <div
                            style="
                                width:100%;
                                height:100%;
                                min-height:500px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                background:
                                    linear-gradient(
                                        135deg,
                                        #161616,
                                        #2a0000
                                    );
                                color:white;
                                padding:40px;
                            "
                        >

                            <div
                                style="
                                    text-align:center;
                                "
                            >

                                <div
                                    style="
                                        width:75px;
                                        height:75px;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        margin:0 auto 20px;
                                        background:var(--spm-red);
                                        border-radius:20px;
                                        font-size:29px;
                                    "
                                >

                                    <i
                                        class="fa-solid fa-building"
                                    ></i>

                                </div>

                                <strong
                                    style="
                                        display:block;
                                        font-family:var(--spm-font);
                                        font-size:23px;
                                        font-weight:800;
                                    "
                                >
                                    <?= e(
                                        $siteName
                                    ); ?>
                                </strong>


                            </div>

                        </div>

                    </div>

                <?php endif; ?>


                <div
                    class="about-badge"
                >

                    <strong>
                        <?= e(
                            get_setting(
                                'stat_years',
                                '20+'
                            )
                        ); ?>
                    </strong>

                    <span>
                        Tahun Pengalaman
                    </span>

                </div>

            </div>
            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =======================================================
     WHY US
======================================================== -->

<section
    class="section section-light"
>

    <div class="container">

        <div
            class="section-header"
            data-aos="fade-up"
        >

            <div
                class="section-kicker"
            >
                MENGAPA KAMI
            </div>

            <h2
                class="section-title"
            >
                Partner Distribusi yang
                <span>
                    Siap Mendukung Bisnis Anda
                </span>
            </h2>

            <p
                class="section-description"
            >
                Kami mengutamakan kualitas produk,
                kecepatan pelayanan dan hubungan bisnis
                jangka panjang dengan setiap partner.
            </p>

        </div>


        <div
            class="features-grid"
        >

            <article
                class="feature-card"
                data-aos="fade-up"
                data-aos-delay="50"
            >

                <div
                    class="feature-icon"
                >
                    <i
                        class="fa-solid fa-network-wired"
                    ></i>
                </div>

                <h3
                    class="feature-title"
                >
                    Jaringan Distribusi
                </h3>

                <p
                    class="feature-description"
                >
                    Sistem distribusi yang dirancang
                    untuk mendukung kebutuhan partner
                    secara konsisten.
                </p>

            </article>


            <article
                class="feature-card"
                data-aos="fade-up"
                data-aos-delay="100"
            >

                <div
                    class="feature-icon"
                >
                    <i
                        class="fa-solid fa-certificate"
                    ></i>
                </div>

                <h3
                    class="feature-title"
                >
                    Produk Berkualitas
                </h3>

                <p
                    class="feature-description"
                >
                    Mendukung kebutuhan otomotif dengan
                    produk yang terkurasi dan terpercaya.
                </p>

            </article>


            <article
                class="feature-card"
                data-aos="fade-up"
                data-aos-delay="150"
            >

                <div
                    class="feature-icon"
                >
                    <i
                        class="fa-solid fa-truck-fast"
                    ></i>
                </div>

                <h3
                    class="feature-title"
                >
                    Operasional Profesional
                </h3>

                <p
                    class="feature-description"
                >
                    Proses operasional dan distribusi
                    yang mengutamakan ketepatan dan efisiensi.
                </p>

            </article>


            <article
                class="feature-card"
                data-aos="fade-up"
                data-aos-delay="200"
            >

                <div
                    class="feature-icon"
                >
                    <i
                        class="fa-solid fa-handshake"
                    ></i>
                </div>

                <h3
                    class="feature-title"
                >
                    Kemitraan Jangka Panjang
                </h3>

                <p
                    class="feature-description"
                >
                    Membangun kerja sama yang saling
                    menguntungkan dan berkelanjutan.
                </p>

            </article>

        </div>

    </div>

</section>


<!-- =======================================================
     PRODUCT CATEGORIES
======================================================== -->

<?php
/* The homepage category showcase is hidden per request.
 * The products remain available through the main navigation/catalog.
 */
if (false && !empty($displayCategories)): ?>

<section
    id="categories"
    class="section"
>

    <div class="container">

        <div
            class="section-header"
            data-aos="fade-up"
        >

            <div
                class="section-kicker"
            >
                KATEGORI PRODUK
            </div>

            <h2
                class="section-title"
            >
                Solusi Produk untuk
                <span>
                    Kebutuhan Otomotif
                </span>
            </h2>

            <p
                class="section-description"
            >
                Jelajahi berbagai kategori produk yang
                tersedia melalui jaringan Star Parts Motor.
            </p>

        </div>


        <div
            class="categories-grid"
        >

            <?php foreach (
                array_slice(
                    $displayCategories,
                    0,
                    4
                )
                as $category
            ): ?>

                <?php

                $categoryName =
                    trim(
                        (string)
                        (
                            $category['name']
                            ?? ''
                        )
                    );

                $categorySlug =
                    trim(
                        (string)
                        (
                            $category['slug']
                            ?? ''
                        )
                    );

                $categoryDescription =
                    trim(
                        (string)
                        (
                            $category['description']
                            ?? ''
                        )
                    );

                $categoryImage =
                    trim(
                        (string)
                        (
                            $category['image']
                            ?? ''
                        )
                    );
                $categoryHref = trim((string)($category['href'] ?? ''));
                $categoryUrl = $categoryHref !== ''
                    ? base_url($categoryHref)
                    : base_url('products.php?category=' . rawurlencode($categorySlug));

                ?>

                <a
                    href="<?= e($categoryUrl); ?>"
                    class="category-card"
                    data-aos="fade-up"
                >

                    <?php if (
                        $categoryImage !== ''
                    ): ?>

                        <img
                            src="<?= e(
                                media_url(
                                    $categoryImage
                                )
                            ); ?>"
                            alt="<?= e(
                                $categoryName
                            ); ?>"
                            loading="lazy"
                        >

                    <?php endif; ?>


                    <div
                        class="category-content"
                    >

                        <h3
                            class="category-title"
                        >
                            <?= e(
                                $categoryName
                            ); ?>
                        </h3>

                        <p
                            class="category-text"
                        >

                            <?= e(
                                excerpt(
                                    $categoryDescription,
                                    90
                                )
                            ); ?>

                        </p>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>


        <div
            class="text-center"
            style="margin-top:32px;"
        >

            <a
                href="<?= e(
                    base_url(
                        'products.php'
                    )
                ); ?>"
                class="btn btn-outline"
            >

                Lihat Semua Kategori

                <i
                    class="fa-solid fa-arrow-right"
                ></i>

            </a>

        </div>

    </div>

</section>

<?php endif; ?>


<!-- =======================================================
     FEATURED PRODUCTS
======================================================== -->

<section
    id="products"
    class="section section-light"
>

    <div class="container">

        <div
            class="section-header"
            data-aos="fade-up"
        >

            <div
                class="section-kicker"
            >
                PRODUK UNGGULAN
            </div>

            <h2
                class="section-title"
            >
                Pilihan Produk
                <span>
                    Star Parts Motor
                </span>
            </h2>

            <p
                class="section-description"
            >
                Temukan pilihan produk otomotif untuk
                kebutuhan retail, dealer maupun partner bisnis.
            </p>

        </div>


        <?php if (
            !empty($featuredProducts)
        ): ?>

            <div
                class="products-grid"
            >

                <?php foreach (
                    $featuredProducts
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

                    $productSlug =
                        trim(
                            (string)
                            (
                                $product['slug']
                                ?? ''
                            )
                        );

                    $productDescription =
                        trim(
                            (string)
                            (
                                $product['short_description']
                                ?? ''
                            )
                        );

                    $productImage =
                        trim(
                            (string)
                            (
                                $product['image']
                                ?? ''
                            )
                        );

                    $categoryName =
                        trim(
                            (string)
                            (
                                $product['category_name']
                                ?? ''
                            )
                        );

                    ?>

                    <article
                        class="product-card"
                        data-aos="fade-up"
                    >

                        <a
                            href="<?= e(
                                product_url($productSlug)
                            ); ?>"
                            class="product-image"
                            aria-label="<?= e(
                                $productName
                            ); ?>"
                        >

                            <?php if (
                                $productImage !== ''
                            ): ?>

                                <img
                                    src="<?= e(
                                        media_url(
                                            $productImage
                                        )
                                    ); ?>"
                                    alt="<?= e(
                                        $productName
                                    ); ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div
                                    style="
                                        width:100%;
                                        height:100%;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        color:#999;
                                        font-size:35px;
                                    "
                                >

                                    <i
                                        class="fa-solid fa-box-open"
                                    ></i>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                $categoryName !== ''
                            ): ?>

                                <span
                                    class="product-category"
                                >
                                    <?= e(
                                        $categoryName
                                    ); ?>
                                </span>

                            <?php endif; ?>

                        </a>


                        <div
                            class="product-content"
                        >

                            <h3
                                class="product-title"
                            >

                                <a
                                    href="<?= e(
                                        product_url($productSlug)
                                    ); ?>"
                                >

                                    <?= e(
                                        $productName
                                    ); ?>

                                </a>

                            </h3>


                            <?php if (
                                $productDescription !== ''
                            ): ?>

                                <p
                                    class="product-description"
                                >
                                    <?= e(
                                        excerpt(
                                            $productDescription,
                                            125
                                        )
                                    ); ?>
                                </p>

                            <?php endif; ?>


                            <div
                                class="product-meta"
                            >

                                <span>

                                    <i
                                        class="fa-solid fa-circle-check"
                                    ></i>

                                    Product Information

                                </span>

                            </div>


                            <div
                                class="product-button"
                            >

                                <a
                                    href="<?= e(
                                        product_url($productSlug)
                                    ); ?>"
                                    class="btn btn-outline btn-sm"
                                >

                                    Lihat Detail

                                    <i
                                        class="fa-solid fa-arrow-right"
                                    ></i>

                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <div
                class="text-center"
                style="margin-top:35px;"
            >

                <a
                    href="<?= e(
                        base_url(
                            'products.php'
                        )
                    ); ?>"
                    class="btn btn-dark"
                >

                    Lihat Semua Produk

                    <i
                        class="fa-solid fa-arrow-right"
                    ></i>

                </a>

            </div>

        <?php else: ?>

            <div class="products-grid">
                <article class="product-card" data-aos="fade-up">
                    <a href="<?= e(base_url('products.php#catalog')); ?>" class="product-image" aria-label="Lihat katalog ban FDR">
                        <img src="<?= e(media_url('uploads/general/display-ban-fdr.webp')); ?>" alt="Display ban FDR di toko Star Parts Motor" loading="lazy">
                        <span class="product-category">BAN MOTOR</span>
                    </a>
                    <div class="product-content">
                        <h3 class="product-title">Ban Motor FDR</h3>
                        <p class="product-description">Jelajahi materi katalog FDR dan konsultasikan tipe serta ukuran yang sesuai kebutuhan Anda.</p>
                        <div class="product-button"><a href="<?= e(base_url('products.php#catalog')); ?>" class="btn btn-outline btn-sm">Lihat Katalog <i class="fa-solid fa-arrow-right"></i></a></div>
                    </div>
                </article>
                <article class="product-card" data-aos="fade-up" data-aos-delay="75">
                    <a href="<?= e(base_url('contact.php')); ?>" class="product-image" aria-label="Tanyakan produk penerangan otomotif OSRAM">
                        <img src="<?= e(media_url('uploads/general/logo-osram.png')); ?>" alt="Logo OSRAM" loading="lazy" style="object-fit:contain;padding:32px;background:#fff;">
                        <span class="product-category">PENERANGAN</span>
                    </a>
                    <div class="product-content">
                        <h3 class="product-title">Penerangan Otomotif OSRAM</h3>
                        <p class="product-description">Hubungi tim kami untuk menanyakan informasi produk penerangan otomotif dan ketersediaannya.</p>
                        <div class="product-button"><a href="<?= e(base_url('contact.php')); ?>" class="btn btn-outline btn-sm">Tanya Produk <i class="fa-solid fa-arrow-right"></i></a></div>
                    </div>
                </article>
                <article class="product-card" data-aos="fade-up" data-aos-delay="150">
                    <a href="<?= e(base_url('products.php#catalog')); ?>" class="product-image" aria-label="Buka katalog FDR 2026">
                        <img src="<?= e(media_url('uploads/general/katalog-fdr-2026-preview.webp')); ?>" alt="Pratinjau katalog FDR 2026" loading="lazy">
                        <span class="product-category">KATALOG 2026</span>
                    </a>
                    <div class="product-content">
                        <h3 class="product-title">Katalog FDR 2026</h3>
                        <p class="product-description">Buka materi katalog beresolusi penuh untuk melihat tipe dan ukuran yang tercantum.</p>
                        <div class="product-button"><a href="<?= e(base_url('uploads/general/katalog-fdr-2026.pdf')); ?>" download="Katalog-FDR-2026.pdf" class="btn btn-outline btn-sm">Unduh Katalog PDF <i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
                    </div>
                </article>
            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =======================================================
     FDR PRODUCT CATALOG
======================================================== -->
<?php if (!empty($catalogTopBanners)): ?>
<section id="catalog-preview" class="section section-dark spm-catalog-section">
    <div class="container">
            <?php
            $firstCatalogBanner = $catalogTopBanners[0];
            $firstTitle = trim((string)($firstCatalogBanner['title'] ?? ''));
            $firstSubtitle = trim((string)($firstCatalogBanner['subtitle'] ?? ''));
            $firstButtonText = trim((string)($firstCatalogBanner['button_text'] ?? ''));
            $firstButtonUrl = trim((string)($firstCatalogBanner['button_url'] ?? ''));
            $firstButtonFinalUrl = $firstButtonUrl !== '' ? (preg_match('~^https?://~i', $firstButtonUrl) ? $firstButtonUrl : base_url(ltrim($firstButtonUrl, '/'))) : '';
            ?>
            <div class="spm-catalog-grid spm-catalog-dynamic-banner" data-catalog-copy>
                <div class="spm-catalog-copy" data-aos="fade-right">
                    <div class="section-kicker">KATALOG PRODUK</div>
                    <h2 class="section-title" style="color:#fff;" data-catalog-title><?= nl2br(e($firstTitle !== '' ? $firstTitle : 'Jelajahi Katalog FDR 2026')); ?></h2>
                    <p class="section-description" data-catalog-subtitle style="margin-left:0;color:rgba(255,255,255,.72);"><?= nl2br(e($firstSubtitle)); ?></p>
                    <div class="spm-catalog-actions" data-catalog-action <?= $firstButtonText === '' || $firstButtonFinalUrl === '' ? 'hidden' : ''; ?>>
                        <a class="btn btn-primary" data-catalog-button href="<?= e($firstButtonFinalUrl); ?>"><?= e($firstButtonText); ?> <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="swiper spm-catalog-right-swiper" data-autoplay-delay="4500" data-autoplay-direction="forward">
                    <div class="swiper-wrapper">
                        <?php foreach ($catalogTopBanners as $bannerIndex => $catalogBanner): ?>
                            <?php
                            $catalogTitle = trim((string)($catalogBanner['title'] ?? ''));
                            $catalogSubtitle = trim((string)($catalogBanner['subtitle'] ?? ''));
                            $catalogButtonText = trim((string)($catalogBanner['button_text'] ?? ''));
                            $catalogButtonUrl = trim((string)($catalogBanner['button_url'] ?? ''));
                            $catalogImage = trim((string)($catalogBanner['image'] ?? ''));
                            $catalogVideo = trim((string)($catalogBanner['video'] ?? ''));
                            $catalogImageLink = trim((string)($catalogBanner['image_link'] ?? ''));
                            $catalogButtonFinalUrl = $catalogButtonUrl !== '' ? (preg_match('~^https?://~i', $catalogButtonUrl) ? $catalogButtonUrl : base_url(ltrim($catalogButtonUrl, '/'))) : '';
                            $catalogImageFinalUrl = $catalogImageLink !== '' ? (preg_match('~^https?://~i', $catalogImageLink) ? $catalogImageLink : base_url(ltrim($catalogImageLink, '/'))) : '';
                            ?>
                            <div class="swiper-slide"
                                data-catalog-title="<?= e($catalogTitle); ?>"
                                data-catalog-subtitle="<?= e($catalogSubtitle); ?>"
                                data-catalog-button="<?= e($catalogButtonText); ?>"
                                data-catalog-url="<?= e($catalogButtonFinalUrl); ?>">
                                <?php if ($catalogImage !== '' || $catalogVideo !== ''): ?>
                                    <?php if ($catalogImageFinalUrl !== ''): ?><a class="spm-catalog-cover-link" href="<?= e($catalogImageFinalUrl); ?>" aria-label="<?= e($catalogTitle !== '' ? $catalogTitle : 'Buka banner katalog'); ?>"><?php else: ?><div class="spm-catalog-cover-link"><?php endif; ?>
                                        <?php if ($catalogVideo !== ''): ?>
                                            <video class="spm-catalog-cover" autoplay muted loop playsinline preload="<?= $bannerIndex === 0 ? 'auto' : 'metadata'; ?>"><source src="<?= e(media_url($catalogVideo)); ?>" type="video/mp4"></video>
                                        <?php elseif ($catalogImage !== ''): ?>
                                            <img class="spm-catalog-cover" src="<?= e(media_url($catalogImage)); ?>" alt="<?= e(trim((string)($catalogBanner['image_alt'] ?? '')) !== '' ? trim((string)$catalogBanner['image_alt']) : ($catalogTitle ?: 'Banner katalog')); ?>" loading="<?= $bannerIndex === 0 ? 'eager' : 'lazy'; ?>">
                                        <?php endif; ?>
                                    <?php if ($catalogImageFinalUrl !== ''): ?></a><?php else: ?></div><?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-button-prev" aria-label="Banner sebelumnya"></div>
                    <div class="swiper-button-next" aria-label="Banner berikutnya"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
    </div>
</section>
<?php endif; ?>


<!-- =======================================================
     BRAND PARTNERS
======================================================== -->

<?php if (
    !empty($displayBrands)
): ?>

<section
    id="brands"
    class="section"
>

    <div class="container">

        <div
            class="section-header"
            data-aos="fade-up"
        >

            <div
                class="section-kicker"
            >
                BRAND PARTNER
            </div>

            <h2
                class="section-title"
            >
                Brand yang
                <span>
                    Kami Dukung
                </span>
            </h2>

            <p
                class="section-description"
            >
                Portofolio brand akan terus dikembangkan
                untuk mendukung kebutuhan bisnis otomotif.
            </p>

        </div>


        <div
            class="brands-grid"
        >

            <?php foreach (
                $displayBrands
                as $brand
            ): ?>

                <?php

                $brandName =
                    trim(
                        (string)
                        (
                            $brand['name']
                            ?? ''
                        )
                    );

                $brandDescription =
                    trim(
                        (string)
                        (
                            $brand['description']
                            ?? ''
                        )
                    );

                $brandNameKey = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $brandName) ?? '');
                $brandLogo = '';
                $brandWordmarkClass = 'brand-wordmark-generic';

                // Use approved logo assets for known brands; do not display product photography as a brand logo.
                if (str_contains($brandNameKey, 'OSRAM')) {
                    $brandLogo = 'uploads/general/logo-osram.webp';
                } elseif (str_contains($brandNameKey, 'FDR')) {
                    $brandLogo = 'uploads/general/fdr-tire-logo-red.webp';
                } elseif (str_contains($brandNameKey, 'HQFEDERAL')) {
                     $brandLogo = 'uploads/general/logo-hqfederal.png';
                } elseif (str_contains($brandNameKey, 'ROTARY')) {
                     $brandLogo = 'uploads/general/logo-rotary.png';
                } elseif (str_contains($brandNameKey, 'TAYORI')) {
                    $brandWordmarkClass = 'brand-wordmark-tayori';
                } elseif (str_contains($brandNameKey, 'RAKUDA')) {
                    $brandWordmarkClass = 'brand-wordmark-rakuda';                
                }

                ?>

                <article
                    class="brand-card brand-card-logo-only"
                    data-aos="fade-up"
                >

                    <?php if ($brandLogo !== ''): ?>
                        <img
                            class="brand-card-logo"
                            src="<?= e(media_url($brandLogo)); ?>"
                            alt="Logo <?= e($brandName); ?>"
                            loading="lazy"
                            decoding="async"
                        >
                    <?php else: ?>
                        <div class="brand-card-wordmark <?= e($brandWordmarkClass); ?>" role="img" aria-label="Logo <?= e($brandName); ?>">
                            <?= e($brandName); ?>
                        </div>
                    <?php endif; ?>


                    <?php if (
                        $brandDescription !== ''
                    ): ?>

                        <div
                            style="
                                margin-top:7px;
                                color:var(--spm-gray-4);
                                font-size:10px;
                                text-align:center;
                                line-height:1.5;
                            "
                        >

                            <?= e(
                                excerpt(
                                    $brandDescription,
                                    75
                                )
                            ); ?>

                        </div>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    </div>

</section>

<?php endif; ?>


<!-- =======================================================
     DISTRIBUTION / LOCATION
======================================================== -->

<section
    class="section section-dark distribution-section"
>

    <div class="container">

        <div
            class="distribution-grid"
        >

            <div
                class="distribution-content"
                data-aos="fade-right"
            >

                <div
                    class="section-kicker distribution-kicker"
                >
                    JARINGAN & DISTRIBUSI
                </div>


                <h2
                    class="section-title distribution-title"
                >

                    Melayani Kebutuhan
                    <span>
                        Partner Bisnis
                    </span>

                </h2>


                <p
                    class="section-description distribution-description"
                >

                    Star Parts Motor membangun jaringan
                    distribusi yang mendukung kebutuhan
                    partner dan pelanggan secara berkelanjutan.

                </p>


                <div
                    class="distribution-features-list"
                >

                    <div
                        class="distribution-feature-row"
                    >

                        <div
                            class="distribution-feature-icon"
                        >

                            <i
                                class="fa-solid fa-location-dot"
                            ></i>

                        </div>

                        <div class="distribution-feature-text">

                            <strong>
                                Lokasi
                            </strong>

                            <span>

                                <?= e(
                                    $address !== ''
                                        ? $address
                                        : 'Informasi lokasi akan diperbarui.'
                                ); ?>

                            </span>

                        </div>

                    </div>


                    <div
                        class="distribution-feature-row"
                    >

                        <div
                            class="distribution-feature-icon"
                        >

                            <i
                                class="fa-solid fa-truck"
                            ></i>

                        </div>

                        <div class="distribution-feature-text">

                            <strong>
                                Distribusi
                            </strong>

                            <span>
                                Mendukung kebutuhan partner bisnis otomotif.
                            </span>

                        </div>

                    </div>

                </div>


                <div
                    class="distribution-cta"
                >

                    <a
                        href="<?= e(
                            base_url(
                                'contact.php'
                            )
                        ); ?>"
                        class="btn btn-primary"
                    >

                        Hubungi Kami

                        <i
                            class="fa-solid fa-arrow-right"
                        ></i>

                    </a>

                </div>

            </div>


            <div
                class="distribution-visual"
                data-aos="fade-left"
            >

                <?php if (
                    !empty($locations)
                    &&
                    isset(
                        $locations[0]
                    )
                ): ?>

                    <?php

                    $location =
                        $locations[0];

                    $latitude =
                        trim(
                            (string)
                            (
                                $location['latitude']
                                ?? ''
                            )
                        );

                    $longitude =
                        trim(
                            (string)
                            (
                                $location['longitude']
                                ?? ''
                            )
                        );

                    $mapsUrl =
                        trim(
                            (string)
                            (
                                $location['maps_url']
                                ?? ''
                            )
                        );

                    $locationAddress =
                        trim(
                            (string)
                            (
                                $location['address']
                                ?? ''
                            )
                        );

                    ?>


                    <div
                        class="distribution-card"
                    >

                        <div
                            class="distribution-card-icon"
                        >

                            <i
                                class="fa-solid fa-location-dot"
                            ></i>

                        </div>


                        <h3
                            class="distribution-card-title"
                        >

                            <?= e(
                                $location['name']
                                ?? 'Star Parts Motor'
                            ); ?>

                        </h3>


                        <p
                            class="distribution-card-address"
                        >

                            <?= e(
                                $locationAddress
                            ); ?>

                        </p>


                        <?php if (
                            $mapsUrl !== ''
                        ): ?>

                            <a
                                href="<?= e(
                                    $mapsUrl
                                ); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-primary distribution-card-btn"
                            >

                                Buka Google Maps

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square"
                                ></i>

                            </a>

                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <div
                        class="distribution-card distribution-card-fallback"
                    >

                        <div
                            class="distribution-card-icon"
                        >

                            <i
                                class="fa-solid fa-map-location-dot"
                            ></i>

                        </div>


                        <h3
                            class="distribution-card-title"
                        >
                            Area Distribusi
                        </h3>


                        <p
                            class="distribution-card-address"
                        >
                            Informasi jaringan dan lokasi distribusi
                            dapat dikelola melalui panel admin.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>


<!-- =======================================================
     LATEST NEWS
======================================================== -->

<section
    id="news"
    class="section"
>

    <div class="container">

        <div
            class="section-header"
            data-aos="fade-up"
        >

            <div
                class="section-kicker"
            >
                BERITA & AKTIVITAS
            </div>


            <h2
                class="section-title"
            >

                Update Terbaru
                <span>
                    Star Parts Motor
                </span>

            </h2>


            <p
                class="section-description"
            >

                Simak aktivitas, informasi produk dan
                perkembangan terbaru dari Star Parts Motor.

            </p>

        </div>


        <?php if (
            !empty($latestPosts)
        ): ?>

            <div
                class="posts-grid"
            >

                <?php foreach (
                    $latestPosts
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

                    $postSlug =
                        trim(
                            (string)
                            (
                                $post['slug']
                                ?? ''
                            )
                        );

                    $postId = (int) (
                        $post['id']
                        ?? $post['post_id']
                        ?? 0
                    );

                    $postExcerpt =
                        trim(
                            (string)
                            (
                                $post['excerpt']
                                ?? ''
                            )
                        );

                    $postImage =
                        trim(
                            (string)
                            (
                                $post['featured_image']
                                ?? ''
                            )
                        );

                    $publishedAt =
                        $post['published_at']
                        ??
                        $post['created_at']
                        ??
                        '';

                    ?>

                    <article
                        class="post-card"
                        data-aos="fade-up"
                    >

                        <a
                            href="<?= e(
                                post_url($postSlug, $postId)
                            ); ?>"
                            class="post-image"
                        >

                            <?php if (
                                $postImage !== ''
                            ): ?>

                                <img
                                    src="<?= e(
                                        media_url(
                                            $postImage
                                        )
                                    ); ?>"
                                    alt="<?= e(
                                        $postTitle
                                    ); ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div
                                    style="
                                        width:100%;
                                        height:100%;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        color:#aaa;
                                        background:#eeeeee;
                                        font-size:33px;
                                    "
                                >

                                    <i
                                        class="fa-solid fa-newspaper"
                                    ></i>

                                </div>

                            <?php endif; ?>


                            <span
                                class="post-category"
                            >
                                NEWS
                            </span>

                        </a>


                        <div
                            class="post-content"
                        >

                            <div
                                class="post-date"
                            >

                                <i
                                    class="fa-regular fa-calendar"
                                ></i>

                                <?= e(
                                    format_date_id(
                                        $publishedAt
                                    )
                                ); ?>

                            </div>


                            <h3
                                class="post-title"
                            >

                                <a
                                    href="<?= e(
                                        post_url($postSlug, $postId)
                                    ); ?>"
                                >

                                    <?= e(
                                        $postTitle
                                    ); ?>

                                </a>

                            </h3>


                            <?php if (
                                $postExcerpt !== ''
                            ): ?>

                                <p
                                    class="post-excerpt"
                                >

                                    <?= e(
                                        excerpt(
                                            $postExcerpt,
                                            120
                                        )
                                    ); ?>

                                </p>

                            <?php endif; ?>


                            <a
                                href="<?= e(
                                    post_url($postSlug, $postId)
                                ); ?>"
                                class="post-read-more"
                            >

                                Baca Selengkapnya

                                <i
                                    class="fa-solid fa-arrow-right"
                                ></i>

                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <div
                class="text-center"
                style="margin-top:35px;"
            >

                <a
                    href="<?= e(
                        base_url(
                            'news.php'
                        )
                    ); ?>"
                    class="btn btn-outline"
                >

                    Lihat Semua Berita

                    <i
                        class="fa-solid fa-arrow-right"
                    ></i>

                </a>

            </div>

        <?php else: ?>


            <div
                class="empty-state"
                data-aos="fade-up"
            >

                <div
                    class="empty-state-icon"
                >

                    <i
                        class="fa-solid fa-newspaper"
                    ></i>

                </div>


                <h3>
                    Belum ada berita
                </h3>


                <p>
                    Berita dan aktivitas perusahaan
                    dapat ditambahkan dari panel admin.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =======================================================
     PARTNER CTA
======================================================== -->

<section
    class="cta-section"
>

    <div class="container">

        <div
            class="cta-inner"
        >

            <div
                class="cta-content"
                data-aos="fade-right"
            >

                <h2
                    class="cta-title"
                >

                    <?= e($globalCtaPrefix); ?>
                    <strong>
                        <?= e($globalCtaHighlight); ?>
                    </strong>

                </h2>


                <p
                    class="cta-text"
                >

                    <?= e($globalCtaText); ?>

                </p>

            </div>


            <div
                class="cta-actions"
                data-aos="fade-left"
            >

                <?php if (
                    $whatsappNumber !== ''
                ): ?>

                    <a
                        href="<?= e(
                            $whatsappLink
                        ); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-white"
                    >

                        <i
                            class="fa-brands fa-whatsapp"
                        ></i>

                        <?= e($globalCtaWhatsappText); ?>

                    </a>

                <?php endif; ?>


                <a
                    href="<?= e(
                        site_url($globalCtaButtonUrl)
                    ); ?>"
                    class="btn btn-dark"
                >

                    <?= e($globalCtaButtonText); ?>

                    <i
                        class="fa-solid fa-arrow-right"
                    ></i>

                </a>

            </div>

        </div>

    </div>

</section>


<!-- =======================================================
     CONTACT QUICK SECTION
======================================================== -->

<section
    class="section section-light"
>

    <div class="container">

        <div
            class="contact-grid"
        >

            <div
                class="contact-info"
                data-aos="fade-right"
            >

                <div
                    class="section-kicker"
                    style="color:#ff3a3a;"
                >
                    KONTAK
                </div>


                <h2>
                    Mari Terhubung
                </h2>


                <p>

                    Hubungi tim Star Parts Motor
                    untuk informasi produk, distribusi
                    maupun peluang kerja sama.

                </p>


                <div
                    class="contact-list"
                >

                    <?php if (
                        $address !== ''
                    ): ?>

                        <div
                            class="contact-item"
                        >

                            <div
                                class="contact-icon"
                            >

                                <i
                                    class="fa-solid fa-location-dot"
                                ></i>

                            </div>


                            <div
                                class="contact-item-content"
                            >

                                <span
                                    class="contact-item-label"
                                >
                                    Alamat
                                </span>

                                <span
                                    class="contact-item-value"
                                >
                                    <?= e(
                                        $address
                                    ); ?>
                                </span>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $phone !== ''
                    ): ?>

                        <div
                            class="contact-item"
                        >

                            <div
                                class="contact-icon"
                            >

                                <i
                                    class="fa-solid fa-phone"
                                ></i>

                            </div>


                            <div
                                class="contact-item-content"
                            >

                                <span
                                    class="contact-item-label"
                                >
                                    Telepon
                                </span>

                                <span
                                    class="contact-item-value"
                                >
                                    <?= e(
                                        $phone
                                    ); ?>
                                </span>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $email !== ''
                    ): ?>

                        <div
                            class="contact-item"
                        >

                            <div
                                class="contact-icon"
                            >

                                <i
                                    class="fa-solid fa-envelope"
                                ></i>

                            </div>


                            <div
                                class="contact-item-content"
                            >

                                <span
                                    class="contact-item-label"
                                >
                                    Email
                                </span>

                                <span
                                    class="contact-item-value"
                                >
                                    <?= e(
                                        $email
                                    ); ?>
                                </span>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <?php if (
                    $whatsappNumber !== ''
                ): ?>

                    <div
                        style="margin-top:28px;"
                    >

                        <a
                            href="<?= e(
                                $whatsappLink
                            ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-primary"
                        >

                            <i
                                class="fa-brands fa-whatsapp"
                            ></i>

                            Chat WhatsApp

                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <div
                class="contact-form"
                data-aos="fade-left"
            >

                <div
                    style="margin-bottom:24px;"
                >

                    <div
                        class="section-kicker"
                    >
                        QUICK CONTACT
                    </div>


                    <h3
                        style="
                            margin-bottom:7px;
                            font-size:26px;
                            font-weight:800;
                        "
                    >
                        Butuh Informasi?
                    </h3>


                    <p
                        style="
                            margin:0;
                            color:var(--spm-text-soft);
                            font-size:12px;
                        "
                    >
                        Kirim kebutuhan Anda dan tim kami
                        akan menghubungi kembali.
                    </p>

                </div>


                <form
                    action="<?= e(
                        base_url(
                            'api/contact.php'
                        )
                    ); ?>"
                    method="POST"
                    data-contact-form
                >

                    <?= csrf_input(); ?>

                    <!-- Honeypot anti-spam: field ini sengaja disembunyikan dari pengguna. -->
                    <div
                        aria-hidden="true"
                        style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;"
                    >
                        <label for="home_website">Website</label>
                        <input
                            type="text"
                            id="home_website"
                            name="website"
                            tabindex="-1"
                            autocomplete="off"
                        >
                    </div>

                    <div
                        class="form-row"
                    >

                        <div
                            class="form-group"
                        >

                            <label
                                class="form-label"
                                for="home_name"
                            >
                                Nama *
                            </label>

                            <input
                                type="text"
                                id="home_name"
                                name="name"
                                class="form-control"
                                placeholder="Nama lengkap"
                                required
                                maxlength="150"
                            >

                        </div>


                        <div
                            class="form-group"
                        >

                            <label
                                class="form-label"
                                for="home_company"
                            >
                                Perusahaan
                            </label>

                            <input
                                type="text"
                                id="home_company"
                                name="company"
                                class="form-control"
                                placeholder="Nama perusahaan"
                                maxlength="200"
                            >

                        </div>

                    </div>


                    <div
                        class="form-row"
                    >

                        <div
                            class="form-group"
                        >

                            <label
                                class="form-label"
                                for="home_email"
                            >
                                Email
                            </label>

                            <input
                                type="email"
                                id="home_email"
                                name="email"
                                class="form-control"
                                placeholder="nama@email.com"
                                maxlength="150"
                            >

                        </div>


                        <div
                            class="form-group"
                        >

                            <label
                                class="form-label"
                                for="home_phone"
                            >
                                Nomor WhatsApp
                            </label>

                            <input
                                type="tel"
                                id="home_phone"
                                name="phone"
                                class="form-control"
                                placeholder="08xxxxxxxxxx"
                                maxlength="50"
                            >

                        </div>

                    </div>


                    <div
                        class="form-group"
                    >

                        <label
                            class="form-label"
                            for="home_subject"
                        >
                            Subjek
                        </label>

                        <input
                            type="text"
                            id="home_subject"
                            name="subject"
                            class="form-control"
                            placeholder="Kebutuhan / kerja sama / informasi produk"
                            maxlength="255"
                        >

                    </div>


                    <div
                        class="form-group"
                    >

                        <label
                            class="form-label"
                            for="home_message"
                        >
                            Pesan *
                        </label>

                        <textarea
                            id="home_message"
                            name="message"
                            class="form-textarea"
                            placeholder="Tuliskan kebutuhan Anda..."
                            required
                        ></textarea>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        Kirim Pesan

                        <i
                            class="fa-solid fa-paper-plane"
                        ></i>

                    </button>

                </form>

            </div>

        </div>

    </div>

</section>


<!-- =======================================================
     CONTACT FORM AJAX HANDLER
======================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-contact-form]');

    if (!form || form.dataset.ajaxReady === '1') {
        return;
    }

    form.dataset.ajaxReady = '1';

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (form.dataset.submitting === '1') {
            return;
        }

        form.dataset.submitting = '1';

        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonHtml = submitButton ? submitButton.innerHTML : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data && data.success) {
                alert(data.message || 'Pesan Anda berhasil dikirim.');
                form.reset();
            } else {
                alert((data && data.message) ? data.message : 'Pesan belum dapat dikirim.');
            }
        } catch (error) {
            console.error('Star Parts Motor contact form:', error);
            alert('Terjadi kesalahan koneksi. Silakan coba lagi.');
        } finally {
            form.dataset.submitting = '0';

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonHtml;
            }
        }
    });
});
</script>

</main>


<!-- =======================================================
     FOOTER
======================================================== -->

<?php
/*
 * Footer akan kita pisahkan ke:
 *
 * includes/footer.php
 *
 * pada tahap berikutnya.
 *
 * Untuk saat ini kita panggil file tersebut
 * agar struktur homepage sudah modular.
 */

require_once __DIR__ . '/includes/footer.php';

?>