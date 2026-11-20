<?php
// =========================================================
// FILE   : about.php
// LOKASI : starparts/about.php
// Halaman Tentang Kami PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PAGE META
|--------------------------------------------------------------------------
*/

$pageTitle = get_setting(
    'about_page_title',
    'Tentang Kami | PT Star Parts Motor'
);

$pageDescription = get_setting(
    'about_page_description',
    get_setting(
        'site_description',
        'Mengenal PT Star Parts Motor sebagai partner distribusi produk otomotif.'
    )
);

$pageImage = trim((string)get_setting('about_image', get_setting('site_logo', '')));
if ($pageImage === '') { $pageImage = 'uploads/general/gedung-star-parts-motor.webp'; }
$pageCanonicalUrl = site_url('about.php');
$bodyClass = 'about-page';


/*
|--------------------------------------------------------------------------
| SITE DATA
|--------------------------------------------------------------------------
*/

$siteName = trim(
    (string) get_setting(
        'site_name',
        'PT Star Parts Motor'
    )
);
if ($siteName === '') { $siteName = 'PT Star Parts Motor'; }

$siteTagline = trim(
    (string) get_setting(
        'site_tagline',
        'Operational Excellence'
    )
);
if ($siteTagline === '') { $siteTagline = 'Operational Excellence'; }

$siteDescription = trim(
    (string) get_setting(
        'site_description',
        'PT Star Parts Motor menghadirkan informasi produk otomotif dan layanan distribusi untuk kebutuhan retail, dealer, serta mitra bisnis.'
    )
);
if ($siteDescription === '') { $siteDescription = 'PT Star Parts Motor menghadirkan informasi produk otomotif dan layanan distribusi untuk kebutuhan retail, dealer, serta mitra bisnis.'; }


/*
|--------------------------------------------------------------------------
| ABOUT DATA
|--------------------------------------------------------------------------
*/

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
if ($aboutContent === '') {
    $aboutContent = '<p>PT Star Parts Motor berfokus pada penyediaan informasi produk otomotif dan dukungan distribusi bagi pelanggan, retailer, dealer, serta mitra bisnis.</p><p>Melalui katalog produk dan kanal komunikasi yang tersedia, kami membantu calon pelanggan mendapatkan informasi awal sebelum melakukan pemesanan atau menjajaki kerja sama.</p>';
}

$aboutImage = trim(
    (string) get_setting(
        'about_image',
        ''
    )
);
if ($aboutImage === '') { $aboutImage = 'uploads/general/gedung-star-parts-motor.webp'; }

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
| ADDITIONAL CONTENT
|--------------------------------------------------------------------------
*/

$historyTitle = trim(
    (string) get_setting(
        'about_history_title',
        'Membangun Distribusi yang Berkelanjutan'
    )
);

$historyContent = trim(
    (string) get_setting(
        'about_history_content',
        ''
    )
);
if ($historyContent === '') {
    $historyContent = '<p>Kami terus membangun informasi produk yang mudah dijelajahi dan komunikasi yang jelas untuk mendukung kebutuhan otomotif pelanggan serta mitra bisnis.</p>';
}

$commitmentTitle = trim(
    (string) get_setting(
        'about_commitment_title',
        'Komitmen Kami'
    )
);

$commitmentContent = trim(
    (string) get_setting(
        'about_commitment_content',
        ''
    )
);
if ($commitmentContent === '') {
    $commitmentContent = '<p>Kami berupaya menyampaikan informasi produk secara jelas, membantu pertanyaan pelanggan, dan membuka komunikasi untuk kebutuhan distribusi serta kerja sama.</p>';
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
| BRANDS
|--------------------------------------------------------------------------
*/

$brands = [];

try {

    $brands =
        get_brands(true);

} catch (Throwable $e) {

    $brands = [];
}


/*
|--------------------------------------------------------------------------
| IMAGE URL
|--------------------------------------------------------------------------
*/

$aboutImageUrl =
    $aboutImage !== ''
        ? media_url($aboutImage)
        : '';

$pageImageUrl =
    $pageImage !== ''
        ? media_url($pageImage)
        : '';


/*
|--------------------------------------------------------------------------
| WHATSAPP
|--------------------------------------------------------------------------
*/

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

$whatsappNumber = '';

if ($whatsapp !== '') {

    $whatsappNumber =
        preg_replace(
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
| CONTACT
|--------------------------------------------------------------------------
*/

$phone = trim(
    (string) get_setting(
        'phone_primary',
        ''
    )
);

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

<main id="main-content">


    <!-- =======================================================
         PAGE HERO
    ======================================================== -->

    <section
        class="page-hero"
    >

        <div class="container">

            <div
                class="page-hero-content"
                data-aos="fade-up"
            >

                <div
                    class="breadcrumb"
                >

                    <a
                        href="<?= e(
                            base_url()
                        ); ?>"
                    >
                        Beranda
                    </a>


                    <span>
                        <i
                            class="fa-solid fa-chevron-right"
                        ></i>
                    </span>


                    <span
                        class="current"
                    >
                        Tentang Kami
                    </span>

                </div>


                <span
                    class="eyebrow"
                >
                    TENTANG KAMI
                </span>


                <h1>
                    <?= e(
                        get_setting(
                            'about_page_heading',
                            'Membangun Distribusi Otomotif yang Lebih Baik'
                        )
                    ); ?>
                </h1>


                <p>
                    <?= e(
                        get_setting(
                            'about_page_intro',
                            $siteDescription
                        )
                    ); ?>
                </p>

            </div>

        </div>

    </section>


    <!-- =======================================================
         ABOUT INTRO
    ======================================================== -->

    <section
        class="section section-white"
    >

        <div class="container">

            <div
                class="about-grid"
            >

                <!-- CONTENT -->

                <div
                    class="about-content"
                    data-aos="fade-right"
                >

                    <div
                        class="section-kicker"
                    >
                        PROFIL PERUSAHAAN
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
                                <?= e(
                                    $siteName
                                ); ?>
                                hadir untuk mendukung kebutuhan
                                distribusi produk otomotif melalui
                                jaringan, pelayanan dan operasional
                                yang profesional.
                            </p>


                            <p>
                                Kami terus mengembangkan hubungan
                                jangka panjang dengan partner,
                                pelanggan dan jaringan distribusi
                                untuk menciptakan pertumbuhan bisnis
                                yang berkelanjutan.
                            </p>

                        </div>

                    <?php endif; ?>


                    <div
                        style="
                            display:flex;
                            flex-wrap:wrap;
                            gap:10px;
                            margin-top:28px;
                        "
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
                                class="btn btn-primary"
                            >

                                <i
                                    class="fa-brands fa-whatsapp"
                                ></i>

                                Hubungi Kami

                            </a>

                        <?php endif; ?>


                        <a
                            href="<?= e(
                                base_url(
                                    'products.php'
                                )
                            ); ?>"
                            class="btn btn-outline"
                        >

                            Lihat Produk

                            <i
                                class="fa-solid fa-arrow-right"
                            ></i>

                        </a>

                    </div>

                </div>


                <!-- IMAGE -->

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

                            <img src="<?= e($aboutImageUrl); ?>" <?= media_image_attrs($aboutImage, $aboutTitle, null, 'lazy', false, '(max-width: 767px) 100vw, 50vw'); ?>>

                        </div>

                    <?php elseif (
                        $pageImageUrl !== ''
                    ): ?>

                        <div
                            class="about-image"
                        >

                            <img
                                src="<?= e(
                                    $pageImageUrl
                                ); ?>"
                                alt="<?= e(
                                    $siteName
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
                                    min-height:500px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    padding:40px;
                                    background:
                                        radial-gradient(
                                            circle at 75% 35%,
                                            rgba(217,0,0,.32),
                                            transparent 28%
                                        ),
                                        linear-gradient(
                                            135deg,
                                            #121212 0%,
                                            #252525 58%,
                                            #3a0000 100%
                                        );
                                    color:#fff;
                                "
                            >

                                <div
                                    style="
                                        text-align:center;
                                        max-width:330px;
                                    "
                                >

                                    <div
                                        style="
                                            width:78px;
                                            height:78px;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            margin:0 auto 22px;
                                            background:var(--spm-red);
                                            border-radius:22px;
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
                                            font-size:25px;
                                            font-weight:800;
                                        "
                                    >
                                        <?= e(
                                            $siteName
                                        ); ?>
                                    </strong>


                                    <span
                                        style="
                                            display:block;
                                            margin-top:8px;
                                            color:rgba(255,255,255,.60);
                                            font-size:11px;
                                        "
                                    >
                                        <?= e(
                                            $siteTagline
                                        ); ?>
                                    </span>

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

            </div>

        </div>

    </section>


    <!-- =======================================================
         COMPANY STATS
    ======================================================== -->

    <?php if (
        !empty($companyStats)
    ): ?>

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
                                (string) (
                                    $stat['number_value']
                                    ?? ''
                                )
                            );

                        $label =
                            trim(
                                (string) (
                                    $stat['label']
                                    ?? ''
                                )
                            );

                        if (
                            $number === ''
                        ) {

                            $number = '0';
                        }

                        if (
                            $label === ''
                        ) {
                            $label = 'Statistik';
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
         VISION & MISSION
    ======================================================== -->

    <?php if (
        $vision !== ''
        ||
        $mission !== ''
    ): ?>

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
                        ARAH PERUSAHAAN
                    </div>


                    <h2
                        class="section-title"
                    >
                        Visi dan
                        <span>
                            Misi Kami
                        </span>
                    </h2>


                    <p
                        class="section-description"
                    >
                        Menjadi partner distribusi yang
                        dapat memberikan nilai jangka panjang.
                    </p>

                </div>


                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(
                                2,
                                minmax(0,1fr)
                            );
                        gap:22px;
                    "
                >

                    <?php if (
                        $vision !== ''
                    ): ?>

                        <article
                            class="feature-card"
                            data-aos="fade-up"
                        >

                            <div
                                class="feature-icon"
                            >

                                <i
                                    class="fa-solid fa-eye"
                                ></i>

                            </div>


                            <h3
                                class="feature-title"
                            >
                                Visi
                            </h3>


                            <p
                                class="feature-description"
                            >
                                <?= nl2br(
                                    e(
                                        $vision
                                    )
                                ); ?>
                            </p>

                        </article>

                    <?php endif; ?>


                    <?php if (
                        $mission !== ''
                    ): ?>

                        <article
                            class="feature-card"
                            data-aos="fade-up"
                            data-aos-delay="100"
                        >

                            <div
                                class="feature-icon"
                            >

                                <i
                                    class="fa-solid fa-bullseye"
                                ></i>

                            </div>


                            <h3
                                class="feature-title"
                            >
                                Misi
                            </h3>


                            <p
                                class="feature-description"
                            >
                                <?= nl2br(
                                    e(
                                        $mission
                                    )
                                ); ?>
                            </p>

                        </article>

                    <?php endif; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>


    <!-- =======================================================
         HISTORY / DEVELOPMENT
    ======================================================== -->

    <section
        class="section section-white"
    >

        <div class="container">

            <div
                class="about-grid"
            >

                <div
                    data-aos="fade-right"
                >

                    <div
                        class="section-kicker"
                    >
                        PERKEMBANGAN KAMI
                    </div>


                    <h2
                        class="section-title"
                        style="margin-left:0;"
                    >

                        <?= e(
                            $historyTitle
                        ); ?>

                    </h2>


                    <?php if (
                        $historyContent !== ''
                    ): ?>

                        <div
                            class="about-text"
                        >
                            <?= $historyContent; ?>
                        </div>

                    <?php else: ?>

                        <div
                            class="about-text"
                        >

                            <p>
                                Star Parts Motor terus
                                mengembangkan kemampuan distribusi
                                dan operasional untuk menjawab
                                kebutuhan partner bisnis otomotif.
                            </p>


                            <p>
                                Pendekatan kami berfokus pada
                                pelayanan, ketepatan operasional,
                                ketersediaan produk dan hubungan
                                bisnis jangka panjang.
                            </p>


                            <p>
                                Dengan pengembangan jaringan secara
                                berkelanjutan, perusahaan diarahkan
                                untuk menjadi partner distribusi
                                yang dapat diandalkan.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>


                <div
                    data-aos="fade-left"
                >

                    <div
                        style="
                            height:100%;
                            min-height:380px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            padding:40px;
                            border-radius:20px;
                            background:
                                linear-gradient(
                                    135deg,
                                    #141414,
                                    #220000
                                );
                            color:#fff;
                            position:relative;
                            overflow:hidden;
                        "
                    >

                        <div
                            style="
                                position:absolute;
                                width:220px;
                                height:220px;
                                right:-70px;
                                top:-70px;
                                border-radius:50%;
                                background:rgba(217,0,0,.16);
                            "
                        ></div>


                        <div
                            style="
                                position:absolute;
                                width:150px;
                                height:150px;
                                left:-45px;
                                bottom:-45px;
                                border-radius:50%;
                                background:rgba(255,255,255,.04);
                            "
                        ></div>


                        <div
                            style="
                                position:relative;
                                z-index:2;
                                max-width:360px;
                                text-align:center;
                            "
                        >

                            <div
                                style="
                                    width:72px;
                                    height:72px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    margin:0 auto 20px;
                                    background:var(--spm-red);
                                    border-radius:20px;
                                    font-size:27px;
                                "
                            >

                                <i
                                    class="fa-solid fa-chart-line"
                                ></i>

                            </div>


                            <h3
                                style="
                                    margin-bottom:10px;
                                    color:#fff;
                                    font-family:var(--spm-font);
                                    font-size:23px;
                                    font-weight:800;
                                "
                            >
                                Operational Excellence
                            </h3>


                            <p
                                style="
                                    margin:0;
                                    color:rgba(255,255,255,.60);
                                    font-size:12px;
                                    line-height:1.7;
                                "
                            >
                                <?= e(
                                    $siteTagline
                                ); ?>
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =======================================================
         COMMITMENT
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
                    KOMITMEN
                </div>


                <h2
                    class="section-title"
                >
                    <?= e(
                        $commitmentTitle
                    ); ?>
                </h2>


                <?php if (
                    $commitmentContent !== ''
                ): ?>

                    <p
                        class="section-description"
                    >
                        <?= e(
                            excerpt(
                                strip_tags(
                                    $commitmentContent
                                ),
                                220
                            )
                        ); ?>
                    </p>

                <?php endif; ?>

            </div>


            <div
                class="features-grid"
            >

                <article
                    class="feature-card"
                    data-aos="fade-up"
                >

                    <div
                        class="feature-icon"
                    >

                        <i
                            class="fa-solid fa-shield-halved"
                        ></i>

                    </div>


                    <h3
                        class="feature-title"
                    >
                        Kualitas
                    </h3>


                    <p
                        class="feature-description"
                    >
                        Mengutamakan produk dan proses
                        yang mendukung kebutuhan partner
                        secara konsisten.
                    </p>

                </article>


                <article
                    class="feature-card"
                    data-aos="fade-up"
                    data-aos-delay="70"
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
                        Distribusi
                    </h3>


                    <p
                        class="feature-description"
                    >
                        Mendukung pergerakan produk dengan
                        proses operasional yang terstruktur
                        dan efisien.
                    </p>

                </article>


                <article
                    class="feature-card"
                    data-aos="fade-up"
                    data-aos-delay="140"
                >

                    <div
                        class="feature-icon"
                    >

                        <i
                            class="fa-solid fa-headset"
                        ></i>

                    </div>


                    <h3
                        class="feature-title"
                    >
                        Pelayanan
                    </h3>


                    <p
                        class="feature-description"
                    >
                        Memberikan komunikasi dan pelayanan
                        yang responsif kepada setiap partner.
                    </p>

                </article>


                <article
                    class="feature-card"
                    data-aos="fade-up"
                    data-aos-delay="210"
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
                        Kemitraan
                    </h3>


                    <p
                        class="feature-description"
                    >
                        Membangun hubungan bisnis yang
                        saling menguntungkan dan berkelanjutan.
                    </p>

                </article>

            </div>

        </div>

    </section>


    <!-- =======================================================
         BRAND PORTFOLIO
    ======================================================== -->

    <?php if (
        !empty($brands)
    ): ?>

        <section
            class="section section-white"
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
                        $brands
                        as $brand
                    ): ?>

                        <?php

                        $brandName =
                            trim(
                                (string) (
                                    $brand['name']
                                    ?? ''
                                )
                            );

                        $brandDescription =
                            trim(
                                (string) (
                                    $brand['description']
                                    ?? ''
                                )
                            );

                        $brandNameKey = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $brandName) ?? '');
                        $brandLogo = '';
                        $brandWordmarkClass = 'brand-wordmark-generic';

                        // Gunakan logo resmi yang disetujui, bukan foto produk sebagai logo brand.
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
                        } else {
                            $brandLogo = trim((string) (
                                $brand['logo']
                                ?? $brand['image']
                                ?? ''
                            ));
                        }

                        if (
                            $brandName === ''
                        ) {
                            continue;
                        }

                        ?>

                        <article
                            class="brand-card brand-card-logo-only"
                            data-aos="fade-up"
                        >

                            <?php if (
                                $brandLogo !== ''
                            ): ?>

                                <img
                                    class="brand-card-logo"
                                    src="<?= e(media_url($brandLogo)); ?>"
                                    alt="Logo <?= e($brandName); ?>"
                                    loading="lazy"
                                    decoding="async"
                                >

                            <?php else: ?>

                                <div
                                    class="brand-card-wordmark <?= e($brandWordmarkClass); ?>"
                                    role="img"
                                    aria-label="Logo <?= e($brandName); ?>"
                                >
                                    <?= e(
                                        $brandName
                                    ); ?>
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
                                        line-height:1.5;
                                        text-align:center;
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
         LOCATION
    ======================================================== -->

    <?php if (
        !empty($locations)
    ): ?>

        <section
            class="section section-dark"
        >

            <div class="container">

                <div
                    class="about-grid distribution-grid"
                >

                    <div
                        data-aos="fade-right"
                    >

                        <div
                            class="section-kicker"
                            style="color:#ff3a3a;"
                        >
                            JARINGAN
                        </div>


                        <h2
                            class="section-title"
                            style="color:#fff;"
                        >
                            Jangkauan
                            <span>
                                Distribusi
                            </span>
                        </h2>


                        <p
                            class="section-description"
                            style="
                                margin-left:0;
                                color:rgba(255,255,255,.60);
                            "
                        >
                            Star Parts Motor terus
                            mengembangkan jaringan untuk
                            mendukung kebutuhan partner
                            dan pelanggan.
                        </p>


                        <div
                            style="
                                display:flex;
                                flex-direction:column;
                                gap:12px;
                                margin-top:25px;
                            "
                        >

                            <?php foreach (
                                array_slice(
                                    $locations,
                                    0,
                                    4
                                )
                                as $location
                            ): ?>

                                <?php

                                $locationName =
                                    trim(
                                        (string) (
                                            $location['name']
                                            ?? ''
                                        )
                                    );

                                $locationAddress =
                                    trim(
                                        (string) (
                                            $location['address']
                                            ?? ''
                                        )
                                    );

                                ?>

                                <div
                                    style="
                                        display:flex;
                                        align-items:flex-start;
                                        gap:12px;
                                    "
                                >

                                    <div
                                        style="
                                            width:40px;
                                            height:40px;
                                            flex:0 0 40px;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            border-radius:11px;
                                            background:var(--spm-red);
                                        "
                                    >

                                        <i
                                            class="fa-solid fa-location-dot"
                                        ></i>

                                    </div>


                                    <div>

                                        <strong
                                            style="
                                                display:block;
                                                color:#fff;
                                                font-family:var(--spm-font);
                                                font-size:12px;
                                            "
                                        >
                                            <?= e(
                                                $locationName !== ''
                                                    ? $locationName
                                                    : $siteName
                                            ); ?>
                                        </strong>


                                        <?php if (
                                            $locationAddress !== ''
                                        ): ?>

                                            <span
                                                style="
                                                    display:block;
                                                    margin-top:3px;
                                                    color:rgba(255,255,255,.54);
                                                    font-size:10px;
                                                    line-height:1.6;
                                                "
                                            >
                                                <?= e(
                                                    $locationAddress
                                                ); ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <div
                        data-aos="fade-left"
                    >

                        <div
                            style="
                                min-height:400px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                padding:40px;
                                border:1px solid rgba(255,255,255,.08);
                                border-radius:20px;
                                background:#1b1b1b;
                                text-align:center;
                            "
                        >

                            <div>

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
                                        color:#fff;
                                        font-size:28px;
                                    "
                                >

                                    <i
                                        class="fa-solid fa-map-location-dot"
                                    ></i>

                                </div>


                                <h3
                                    style="
                                        margin-bottom:8px;
                                        color:#fff;
                                        font-family:var(--spm-font);
                                        font-size:23px;
                                        font-weight:800;
                                    "
                                >
                                    Jaringan Distribusi
                                </h3>


                                <p
                                    style="
                                        max-width:390px;
                                        margin:0 auto;
                                        color:rgba(255,255,255,.56);
                                        font-size:11px;
                                        line-height:1.7;
                                    "
                                >
                                    Hubungi tim Star Parts Motor
                                    untuk mendapatkan informasi
                                    mengenai jaringan dan kebutuhan
                                    distribusi.
                                </p>


                                <?php if (
                                    $whatsappNumber !== ''
                                ): ?>

                                    <a
                                        href="<?= e(
                                            $whatsappLink
                                        ); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-primary"
                                        style="margin-top:22px;"
                                    >

                                        <i
                                            class="fa-brands fa-whatsapp"
                                        ></i>

                                        Hubungi Tim Kami

                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    <?php endif; ?>


    <!-- =======================================================
         CONTACT CTA
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

                            Hubungi via WhatsApp

                        </a>

                    <?php endif; ?>


                    <a
                        href="<?= e(
                            base_url(
                                'contact.php'
                            )
                        ); ?>"
                        class="btn btn-dark"
                    >

                        Hubungi Kami

                        <i
                            class="fa-solid fa-arrow-right"
                        ></i>

                    </a>

                </div>

            </div>

        </div>

    </section>


<section class="section section-light" id="leadership">
    <div class="container">
        <div class="spm-leadership-grid">
            <div>
                <div class="section-kicker">KEPEMIMPINAN</div>
                <h2 class="section-title">Dewan <span>Direksi</span></h2>
                <p class="section-description" style="margin-left:0;text-align:left;">Dokumentasi jajaran direksi yang disertakan dalam materi profil perusahaan PT Star Parts Motor.</p>
            </div>
            <figure class="spm-leadership-figure">
                <img src="<?= e(media_url('uploads/general/direksi-star-parts-motor.webp')); ?>" alt="Foto jajaran direksi PT Star Parts Motor" loading="lazy">
                <figcaption>Dewan Direksi PT Star Parts Motor</figcaption>
            </figure>
        </div>
    </div>
</section>

</main>


<?php
// =========================================================
// FOOTER
// =========================================================

require_once __DIR__ . '/includes/footer.php';
?>