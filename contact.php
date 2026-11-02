<?php
// =========================================================
// FILE   : contact.php
// LOKASI : starparts/contact.php
// Halaman Kontak / Menjadi Mitra PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

/*
|--------------------------------------------------------------------------
| PAGE META
|--------------------------------------------------------------------------
*/

$pageTitle = get_setting('contact_page_title', get_setting('site_name', 'PT Star Parts Motor') . ' | Hubungi Kami');
$pageDescription = get_setting('contact_page_description', get_setting('site_description', 'Hubungi PT Star Parts Motor untuk informasi produk, distribusi dan peluang kerja sama.'));
$pageImage = get_setting('contact_image', get_setting('site_logo', ''));
$pageCanonicalUrl = site_url('contact.php');
$bodyClass = 'contact-page';


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

$siteTagline = trim(
    (string) get_setting(
        'site_tagline',
        'Operational Excellence'
    )
);

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
| WHATSAPP
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
            'Halo Star Parts Motor, saya ingin mendapatkan informasi mengenai produk dan kerja sama.'
        );
}


/*
|--------------------------------------------------------------------------
| LOCATION
|--------------------------------------------------------------------------
*/

$locations = [];

try {

    $locations = get_locations(true);

} catch (Throwable $e) {

    $locations = [];
}

$primaryLocation = [];

if (!empty($locations)) {
    $primaryLocation = $locations[0];
}

$mapsUrl = trim(
    (string) (
        $primaryLocation['maps_url']
        ?? ''
    )
);

$locationName = trim(
    (string) (
        $primaryLocation['name']
        ?? $siteName
    )
);

$locationAddress = trim(
    (string) (
        $primaryLocation['address']
        ?? $address
    )
);

$latitude = trim(
    (string) (
        $primaryLocation['latitude']
        ?? ''
    )
);

$longitude = trim(
    (string) (
        $primaryLocation['longitude']
        ?? ''
    )
);


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash = null;

try {

    $flash = get_flash();

} catch (Throwable $e) {

    $flash = null;
}


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
                        Hubungi Kami
                    </span>

                </div>


                <span
                    class="eyebrow"
                >
                    KONTAK
                </span>


                <h1>
                    Mari Terhubung
                </h1>


                <p>
                    Hubungi tim Star Parts Motor untuk
                    mendapatkan informasi produk, distribusi
                    maupun peluang kerja sama.
                </p>

            </div>

        </div>

    </section>


    <!-- =======================================================
         CONTACT MAIN
    ======================================================== -->

    <section
        class="section section-white"
    >

        <div class="container">

            <?php if (
                is_array($flash)
                &&
                !empty($flash['message'])
            ): ?>

                <div
                    class="alert
                        <?= (
                            ($flash['type'] ?? 'success') === 'error'
                            ? 'alert-danger'
                            : 'alert-success'
                        ); ?>"
                    role="alert"
                    data-aos="fade-down"
                >

                    <div>
                        <?php if (
                            ($flash['type'] ?? 'success') === 'error'
                        ): ?>

                            <i
                                class="fa-solid fa-circle-exclamation"
                            ></i>

                        <?php else: ?>

                            <i
                                class="fa-solid fa-circle-check"
                            ></i>

                        <?php endif; ?>
                    </div>

                    <div>
                        <?= e(
                            (string) $flash['message']
                        ); ?>
                    </div>

                </div>

            <?php endif; ?>


            <div
                class="contact-grid"
            >

                <!-- =================================================
                     CONTACT INFORMATION
                ================================================== -->

                <div
                    class="contact-info"
                    data-aos="fade-right"
                >

                    <div
                        class="section-kicker"
                    >
                        INFORMASI KONTAK
                    </div>


                    <h2>
                        Partner Bisnis Anda
                    </h2>


                    <p>
                        Kami terbuka untuk berbagai kebutuhan
                        informasi, distribusi produk dan
                        pengembangan kerja sama bisnis.
                    </p>

                    <?php if ($pageImage !== ''): ?>
                        <div class="contact-featured-image" data-aos="fade-up">
                            <img
                                src="<?= e(media_url($pageImage)); ?>"
                                <?= media_image_attrs($pageImage, $pageTitle, null, 'lazy', false, '(max-width: 767px) 100vw, 50vw'); ?>
                            >
                        </div>
                    <?php endif; ?>


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
                                        <?= nl2br(
                                            e($address)
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

                                    <a
                                        href="tel:<?= e($phone); ?>"
                                        class="contact-item-value"
                                    >
                                        <?= e($phone); ?>
                                    </a>

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

                                    <a
                                        href="mailto:<?= e($email); ?>"
                                        class="contact-item-value"
                                    >
                                        <?= e($email); ?>
                                    </a>

                                </div>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $whatsappNumber !== ''
                        ): ?>

                            <div
                                class="contact-item"
                            >

                                <div
                                    class="contact-icon"
                                >

                                    <i
                                        class="fa-brands fa-whatsapp"
                                    ></i>

                                </div>


                                <div
                                    class="contact-item-content"
                                >

                                    <span
                                        class="contact-item-label"
                                    >
                                        WhatsApp
                                    </span>

                                    <a
                                        href="<?= e(
                                            $whatsappLink
                                        ); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="contact-item-value"
                                    >
                                        Chat dengan Tim Kami
                                    </a>

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


                    <!-- SOCIAL MEDIA -->

                    <?php if (
                        $instagramUrl !== ''
                        ||
                        $facebookUrl !== ''
                        ||
                        $youtubeUrl !== ''
                        ||
                        $tiktokUrl !== ''
                    ): ?>

                        <div
                            style="
                                margin-top:35px;
                                padding-top:25px;
                                border-top:1px solid rgba(0,0,0,.08);
                            "
                        >

                            <div
                                class="section-kicker"
                                style="margin-bottom:13px;"
                            >
                                SOCIAL MEDIA
                            </div>


                            <div
                                style="
                                    display:flex;
                                    flex-wrap:wrap;
                                    gap:10px;
                                "
                            >

                                <?php if (
                                    $instagramUrl !== ''
                                ): ?>

                                    <a
                                        href="<?= e(
                                            $instagramUrl
                                        ); ?>"
                                        class="btn btn-outline btn-sm"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Instagram"
                                    >

                                        <i
                                            class="fa-brands fa-instagram"
                                        ></i>

                                        Instagram

                                    </a>

                                <?php endif; ?>


                                <?php if (
                                    $facebookUrl !== ''
                                ): ?>

                                    <a
                                        href="<?= e(
                                            $facebookUrl
                                        ); ?>"
                                        class="btn btn-outline btn-sm"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Facebook"
                                    >

                                        <i
                                            class="fa-brands fa-facebook-f"
                                        ></i>

                                        Facebook

                                    </a>

                                <?php endif; ?>


                                <?php if (
                                    $youtubeUrl !== ''
                                ): ?>

                                    <a
                                        href="<?= e(
                                            $youtubeUrl
                                        ); ?>"
                                        class="btn btn-outline btn-sm"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="YouTube"
                                    >

                                        <i
                                            class="fa-brands fa-youtube"
                                        ></i>

                                        YouTube

                                    </a>

                                <?php endif; ?>


                                <?php if (
                                    $tiktokUrl !== ''
                                ): ?>

                                    <a
                                        href="<?= e(
                                            $tiktokUrl
                                        ); ?>"
                                        class="btn btn-outline btn-sm"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="TikTok"
                                    >

                                        <i
                                            class="fa-brands fa-tiktok"
                                        ></i>

                                        TikTok

                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                     CONTACT FORM
                ================================================== -->

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


                        <h2
                            style="
                                margin-bottom:8px;
                                font-size:28px;
                                font-weight:800;
                            "
                        >
                            Kirim Pesan
                        </h2>


                        <p
                            style="
                                margin:0;
                                color:var(--spm-text-soft);
                                font-size:12px;
                                line-height:1.7;
                            "
                        >
                            Sampaikan kebutuhan Anda melalui formulir
                            berikut. Tim kami akan menindaklanjutinya.
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
                        novalidate
                    >

                        <?= csrf_input(); ?>


                        <!-- Honeypot -->

                        <div
                            style="
                                position:absolute;
                                left:-10000px;
                                width:1px;
                                height:1px;
                                overflow:hidden;
                            "
                            aria-hidden="true"
                        >

                            <label
                                for="contact_website"
                            >
                                Website
                            </label>

                            <input
                                type="text"
                                id="contact_website"
                                name="website"
                                value=""
                                tabindex="-1"
                                autocomplete="off"
                            >

                        </div>


                        <!-- NAME + COMPANY -->

                        <div
                            class="form-row"
                        >

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="contact_name"
                                >
                                    Nama *
                                </label>

                                <input
                                    type="text"
                                    id="contact_name"
                                    name="name"
                                    class="form-control"
                                    placeholder="Nama lengkap"
                                    required
                                    maxlength="150"
                                    autocomplete="name"
                                >

                            </div>


                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="contact_company"
                                >
                                    Perusahaan
                                </label>

                                <input
                                    type="text"
                                    id="contact_company"
                                    name="company"
                                    class="form-control"
                                    placeholder="Nama perusahaan"
                                    maxlength="200"
                                    autocomplete="organization"
                                >

                            </div>

                        </div>


                        <!-- EMAIL + PHONE -->

                        <div
                            class="form-row"
                        >

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="contact_email"
                                >
                                    Email
                                </label>

                                <input
                                    type="email"
                                    id="contact_email"
                                    name="email"
                                    class="form-control"
                                    placeholder="nama@email.com"
                                    maxlength="190"
                                    autocomplete="email"
                                >

                            </div>


                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="contact_phone"
                                >
                                    Nomor WhatsApp
                                </label>

                                <input
                                    type="tel"
                                    id="contact_phone"
                                    name="phone"
                                    class="form-control"
                                    placeholder="08xxxxxxxxxx"
                                    maxlength="50"
                                    autocomplete="tel"
                                >

                            </div>

                        </div>


                        <!-- SUBJECT -->

                        <div
                            class="form-group"
                        >

                            <label
                                class="form-label"
                                for="contact_subject"
                            >
                                Subjek
                            </label>

                            <select
                                id="contact_subject"
                                name="subject"
                                class="form-control"
                            >

                                <option value="">
                                    Pilih kebutuhan
                                </option>

                                <option value="Informasi Produk">
                                    Informasi Produk
                                </option>

                                <option value="Distribusi">
                                    Distribusi
                                </option>

                                <option value="Kerja Sama">
                                    Kerja Sama
                                </option>

                                <option value="Menjadi Mitra">
                                    Menjadi Mitra
                                </option>

                                <option value="Lainnya">
                                    Lainnya
                                </option>

                            </select>

                        </div>


                        <!-- MESSAGE -->

                        <div
                            class="form-group"
                        >

                            <label
                                class="form-label"
                                for="contact_message"
                            >
                                Pesan *
                            </label>

                            <textarea
                                id="contact_message"
                                name="message"
                                class="form-textarea"
                                rows="7"
                                placeholder="Tuliskan kebutuhan atau pertanyaan Anda..."
                                required
                                maxlength="10000"
                            ></textarea>

                        </div>


                        <!-- SUBMIT -->

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg"
                        >

                            <i
                                class="fa-solid fa-paper-plane"
                            ></i>

                            Kirim Pesan

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </section>


    <!-- =======================================================
         LOCATION
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
                    LOKASI
                </div>


                <h2
                    class="section-title"
                >

                    Temukan
                    <span>
                        Star Parts Motor
                    </span>

                </h2>


                <p
                    class="section-description"
                >
                    Kunjungi lokasi kami atau hubungi tim
                    untuk mendapatkan informasi lebih lanjut.
                </p>

            </div>


            <div
                class="about-grid"
            >

                <!-- LOCATION INFO -->

                <div
                    data-aos="fade-right"
                >

                    <div
                        class="contact-form"
                        style="height:100%;"
                    >

                        <div
                            class="section-kicker"
                        >
                            HEAD OFFICE / DISTRIBUTION
                        </div>


                        <h3
                            style="
                                margin-top:8px;
                                margin-bottom:12px;
                                font-size:23px;
                                font-weight:800;
                            "
                        >
                            <?= e(
                                $locationName
                            ); ?>
                        </h3>


                        <?php if (
                            $locationAddress !== ''
                        ): ?>

                            <div
                                style="
                                    display:flex;
                                    gap:12px;
                                    align-items:flex-start;
                                    margin-top:20px;
                                "
                            >

                                <div
                                    class="contact-icon"
                                    style="
                                        width:44px;
                                        height:44px;
                                        flex:0 0 44px;
                                    "
                                >

                                    <i
                                        class="fa-solid fa-location-dot"
                                    ></i>

                                </div>


                                <div>

                                    <div
                                        style="
                                            font-family:var(--spm-font);
                                            font-size:12px;
                                            font-weight:800;
                                            margin-bottom:5px;
                                        "
                                    >
                                        Alamat
                                    </div>

                                    <div
                                        style="
                                            font-size:12px;
                                            line-height:1.7;
                                            color:var(--spm-text-soft);
                                        "
                                    >
                                        <?= nl2br(
                                            e($locationAddress)
                                        ); ?>
                                    </div>

                                </div>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $phone !== ''
                        ): ?>

                            <div
                                style="
                                    display:flex;
                                    gap:12px;
                                    align-items:center;
                                    margin-top:18px;
                                "
                            >

                                <div
                                    class="contact-icon"
                                    style="
                                        width:44px;
                                        height:44px;
                                        flex:0 0 44px;
                                    "
                                >

                                    <i
                                        class="fa-solid fa-phone"
                                    ></i>

                                </div>


                                <div>

                                    <div
                                        style="
                                            font-family:var(--spm-font);
                                            font-size:12px;
                                            font-weight:800;
                                            margin-bottom:3px;
                                        "
                                    >
                                        Telepon
                                    </div>

                                    <a
                                        href="tel:<?= e($phone); ?>"
                                        style="
                                            font-size:12px;
                                            color:var(--spm-text-soft);
                                        "
                                    >
                                        <?= e($phone); ?>
                                    </a>

                                </div>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $email !== ''
                        ): ?>

                            <div
                                style="
                                    display:flex;
                                    gap:12px;
                                    align-items:center;
                                    margin-top:18px;
                                "
                            >

                                <div
                                    class="contact-icon"
                                    style="
                                        width:44px;
                                        height:44px;
                                        flex:0 0 44px;
                                    "
                                >

                                    <i
                                        class="fa-solid fa-envelope"
                                    ></i>

                                </div>


                                <div>

                                    <div
                                        style="
                                            font-family:var(--spm-font);
                                            font-size:12px;
                                            font-weight:800;
                                            margin-bottom:3px;
                                        "
                                    >
                                        Email
                                    </div>

                                    <a
                                        href="mailto:<?= e($email); ?>"
                                        style="
                                            font-size:12px;
                                            color:var(--spm-text-soft);
                                        "
                                    >
                                        <?= e($email); ?>
                                    </a>

                                </div>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $mapsUrl !== ''
                        ): ?>

                            <div
                                style="margin-top:28px;"
                            >

                                <a
                                    href="<?= e($mapsUrl); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-dark"
                                >

                                    <i
                                        class="fa-solid fa-map-location-dot"
                                    ></i>

                                    Buka Google Maps

                                </a>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- MAP / LOCATION -->

                <div
                    data-aos="fade-left"
                >

                    <div
                        style="
                            min-height:440px;
                            height:100%;
                            overflow:hidden;
                            border-radius:20px;
                            background:#1b1b1b;
                            border:1px solid rgba(0,0,0,.08);
                        "
                    >

                        <?php if (
                            $latitude !== ''
                            &&
                            $longitude !== ''
                            &&
                            is_numeric($latitude)
                            &&
                            is_numeric($longitude)
                        ): ?>

                            <iframe
                                src="https://www.google.com/maps?q=<?= e($latitude); ?>,<?= e($longitude); ?>&z=16&output=embed"
                                width="100%"
                                height="100%"
                                style="
                                    min-height:440px;
                                    border:0;
                                    display:block;
                                "
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Lokasi <?= e($locationName); ?>"
                            ></iframe>

                        <?php elseif (
                            $mapsUrl !== ''
                        ): ?>

                            <iframe
                                src="https://www.google.com/maps?q=<?= rawurlencode($locationAddress); ?>&output=embed"
                                width="100%"
                                height="100%"
                                style="
                                    min-height:440px;
                                    border:0;
                                    display:block;
                                "
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Lokasi <?= e($locationName); ?>"
                            ></iframe>

                        <?php else: ?>

                            <div
                                style="
                                    min-height:440px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    padding:40px;
                                    text-align:center;
                                    background:
                                        radial-gradient(
                                            circle at center,
                                            rgba(217,0,0,.20),
                                            transparent 42%
                                        ),
                                        #1b1b1b;
                                    color:white;
                                "
                            >

                                <div>

                                    <div
                                        style="
                                            width:72px;
                                            height:72px;
                                            margin:0 auto 20px;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            background:var(--spm-red);
                                            border-radius:20px;
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
                                            font-size:22px;
                                            font-weight:800;
                                        "
                                    >
                                        Lokasi Kami
                                    </h3>


                                    <p
                                        style="
                                            max-width:420px;
                                            margin:0 auto;
                                            color:rgba(255,255,255,.60);
                                            font-size:12px;
                                            line-height:1.7;
                                        "
                                    >
                                        Informasi lokasi dapat
                                        diperbarui melalui panel
                                        admin Star Parts Motor.
                                    </p>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =======================================================
         PARTNERSHIP CTA
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

                        Mari Tumbuh
                        <strong>
                            Bersama
                        </strong>

                    </h2>


                    <p
                        class="cta-text"
                    >
                        Bangun kerja sama distribusi dan bisnis
                        otomotif bersama Star Parts Motor.
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
                                'products.php'
                            )
                        ); ?>"
                        class="btn btn-dark"
                    >

                        Lihat Produk

                        <i
                            class="fa-solid fa-arrow-right"
                        ></i>

                    </a>

                </div>

            </div>

        </div>

    </section>

</main>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        const form =
            document.querySelector(
                '[data-contact-form]'
            );

        if (!form) {
            return;
        }

        form.addEventListener(
            'submit',
            function (event) {

                const name =
                    document.getElementById(
                        'contact_name'
                    );

                const message =
                    document.getElementById(
                        'contact_message'
                    );

                const email =
                    document.getElementById(
                        'contact_email'
                    );

                if (
                    name &&
                    name.value.trim() === ''
                ) {

                    event.preventDefault();

                    alert(
                        'Nama wajib diisi.'
                    );

                    name.focus();

                    return;
                }

                if (
                    email &&
                    email.value.trim() !== ''
                ) {

                    const emailPattern =
                        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if (
                        !emailPattern.test(
                            email.value.trim()
                        )
                    ) {

                        event.preventDefault();

                        alert(
                            'Format email tidak valid.'
                        );

                        email.focus();

                        return;
                    }
                }

                if (
                    message &&
                    message.value.trim().length < 5
                ) {

                    event.preventDefault();

                    alert(
                        'Pesan terlalu singkat.'
                    );

                    message.focus();

                    return;
                }

                const submitButton =
                    form.querySelector(
                        'button[type="submit"]'
                    );

                if (submitButton) {

                    submitButton.disabled = true;

                    submitButton.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';

                }

            }
        );

    }
);
</script>


<?php
// =========================================================
// FOOTER
// =========================================================

require_once __DIR__ . '/includes/footer.php';
?>