<?php
// =========================================================
// includes/footer.php
// Footer & JavaScript loader PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/functions.php';


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

$siteDescription = trim(
    (string) get_setting(
        'site_description',
        'PT Star Parts Motor menghadirkan informasi produk otomotif dan layanan distribusi untuk kebutuhan retail, dealer, serta mitra bisnis.'
    )
);
if ($siteDescription === '') { $siteDescription = 'PT Star Parts Motor menghadirkan informasi produk otomotif dan layanan distribusi untuk kebutuhan retail, dealer, serta mitra bisnis.'; }

$logo = trim(
    (string) get_setting(
        'site_logo',
        ''
    )
);
if ($logo === '') { $logo = 'uploads/general/star-parts-motor-logo.png'; }

$address = trim(
    (string) get_setting(
        'address',
        ''
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

$footerText = trim(
    (string) get_setting(
        'footer_text',
        '© PT Star Parts Motor'
    )
);


/*
|--------------------------------------------------------------------------
| LOGO URL
|--------------------------------------------------------------------------
*/

$logoUrl = '';

if ($logo !== '') {

    $logoUrl =
        media_url($logo);
}


/*
|--------------------------------------------------------------------------
| WHATSAPP
|--------------------------------------------------------------------------
*/

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
            'Halo Star Parts Motor, saya ingin mendapatkan informasi lebih lanjut.'
        );
}


/*
|--------------------------------------------------------------------------
| SOCIAL MEDIA
|--------------------------------------------------------------------------
*/

$facebookUrl = trim(
    (string) get_setting(
        'facebook_url',
        ''
    )
);

$instagramUrl = trim(
    (string) get_setting(
        'instagram_url',
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
| FOOTER MENUS
|--------------------------------------------------------------------------
*/

try {

    $footerMenus =
        get_menus();

} catch (Throwable $e) {

    $footerMenus = [];
}


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

try {

    $footerCategories =
        get_categories(true);

} catch (Throwable $e) {

    $footerCategories = [];
}

?>

<!-- =======================================================
     FOOTER
======================================================== -->

<footer
    class="site-footer"
    id="site-footer"
>

    <!-- ===================================================
         FOOTER MAIN
    ==================================================== -->

    <div
        class="footer-main"
    >

        <div
            class="container"
        >

            <div
                class="footer-grid"
            >

                <!-- =========================================
                     COMPANY
                ========================================== -->

                <div
                    class="footer-company"
                >

                    <div
                        class="footer-logo"
                    >

                        <a
                            href="<?= e(
                                base_url(
                                    'index.php'
                                )
                            ); ?>"
                            aria-label="<?= e(
                                $siteName
                            ); ?>"
                        >

                            <?php if (
                                $logoUrl !== ''
                            ): ?>

                                <img
                                    src="<?= e(
                                        $logoUrl
                                    ); ?>"
                                    alt="<?= e(
                                        $siteName
                                    ); ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div
                                    style="
                                        display:flex;
                                        flex-direction:column;
                                        line-height:1;
                                    "
                                >

                                    <strong
                                        style="
                                            color:#fff;
                                            font-family:var(--spm-font);
                                            font-size:20px;
                                            font-weight:800;
                                        "
                                    >
                                        STAR PARTS
                                    </strong>

                                    <span
                                        style="
                                            margin-top:6px;
                                            color:var(--spm-red-light);
                                            font-size:9px;
                                            font-weight:800;
                                            letter-spacing:.18em;
                                        "
                                    >
                                        MOTOR
                                    </span>

                                </div>

                            <?php endif; ?>

                        </a>

                    </div>


                    <p
                        class="footer-description"
                    >

                        <?= e(
                            $siteDescription
                        ); ?>

                    </p>


                    <!-- =====================================
                         SOCIAL
                    ====================================== -->

                    <div
                        class="footer-social"
                    >

                        <?php if (
                            $facebookUrl !== ''
                        ): ?>

                            <a
                                href="<?= e(
                                    $facebookUrl
                                ); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="footer-social-link"
                                aria-label="Facebook"
                            >

                                <i
                                    class="fa-brands fa-facebook-f"
                                ></i>

                            </a>

                        <?php endif; ?>


                        <?php if (
                            $instagramUrl !== ''
                        ): ?>

                            <a
                                href="<?= e(
                                    $instagramUrl
                                ); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="footer-social-link"
                                aria-label="Instagram"
                            >

                                <i
                                    class="fa-brands fa-instagram"
                                ></i>

                            </a>

                        <?php endif; ?>


                        <?php if (
                            $youtubeUrl !== ''
                        ): ?>

                            <a
                                href="<?= e(
                                    $youtubeUrl
                                ); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="footer-social-link"
                                aria-label="YouTube"
                            >

                                <i
                                    class="fa-brands fa-youtube"
                                ></i>

                            </a>

                        <?php endif; ?>


                        <?php if (
                            $tiktokUrl !== ''
                        ): ?>

                            <a
                                href="<?= e(
                                    $tiktokUrl
                                ); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="footer-social-link"
                                aria-label="TikTok"
                            >

                                <i
                                    class="fa-brands fa-tiktok"
                                ></i>

                            </a>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =========================================
                     QUICK LINKS
                ========================================== -->

                <div
                    class="footer-column"
                >

                    <h3
                        class="footer-title"
                    >
                        QUICK LINKS
                    </h3>


                    <nav
                        class="footer-links"
                        aria-label="Quick links"
                    >

                        <?php if (
                            !empty(
                                $footerMenus
                            )
                        ): ?>

                            <?php foreach (
                                array_slice(
                                    $footerMenus,
                                    0,
                                    7
                                )
                                as $menu
                            ): ?>

                                <?php

                                $menuTitle =
                                    trim(
                                        (string)
                                        (
                                            $menu['title']
                                            ?? ''
                                        )
                                    );

                                $menuUrl =
                                    trim(
                                        (string)
                                        (
                                            $menu['menu_url']
                                            ?? ''
                                        )
                                    );

                                if (
                                    $menuTitle === ''
                                ) {
                                    continue;
                                }

                                ?>

                                <a
                                    href="<?= e(
                                        base_url(
                                            ltrim(
                                                $menuUrl !== ''
                                                    ? $menuUrl
                                                    : '#',
                                                '/'
                                            )
                                        )
                                    ); ?>"
                                >

                                    <?= e(
                                        $menuTitle
                                    ); ?>

                                </a>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <a
                                href="<?= e(
                                    base_url(
                                        'index.php'
                                    )
                                ); ?>"
                            >
                                Home
                            </a>

                            <a
                                href="<?= e(
                                    base_url(
                                        'about.php'
                                    )
                                ); ?>"
                            >
                                Tentang Kami
                            </a>

                            <a
                                href="<?= e(
                                    base_url(
                                        'products.php'
                                    )
                                ); ?>"
                            >
                                Produk
                            </a>

                            <a
                                href="<?= e(
                                    base_url(
                                        'news.php'
                                    )
                                ); ?>"
                            >
                                Berita
                            </a>

                            <a
                                href="<?= e(
                                    base_url(
                                        'career.php'
                                    )
                                ); ?>"
                            >
                                Karir
                            </a>

                            <a
                                href="<?= e(
                                    base_url(
                                        'contact.php'
                                    )
                                ); ?>"
                            >
                                Kontak
                            </a>

                        <?php endif; ?>

                    </nav>

                </div>


                <!-- =========================================
                     PRODUCTS
                ========================================== -->

                <div
                    class="footer-column"
                >

                    <h3
                        class="footer-title"
                    >
                        PRODUK
                    </h3>


                    <nav
                        class="footer-links"
                        aria-label="Produk"
                    >

                        <?php if (
                            !empty(
                                $footerCategories
                            )
                        ): ?>

                            <?php foreach (
                                array_slice(
                                    $footerCategories,
                                    0,
                                    6
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

                                if (
                                    $categoryName === ''
                                ) {
                                    continue;
                                }

                                ?>

                                <a
                                    href="<?= e(
                                        base_url(
                                            'products.php?category='
                                            .
                                            rawurlencode(
                                                $categorySlug
                                            )
                                        )
                                    ); ?>"
                                >

                                    <?= e(
                                        $categoryName
                                    ); ?>

                                </a>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <a
                                href="<?= e(
                                    base_url(
                                        'products.php'
                                    )
                                ); ?>"
                            >
                                Ban Motor FDR
                            </a>

                            <a
                                href="<?= e(
                                    base_url(
                                        'products.php'
                                    )
                                ); ?>"
                            >
                                Lampu OSRAM
                            </a>

                            <a
                                href="<?= e(
                                    base_url(
                                        'products.php'
                                    )
                                ); ?>"
                            >
                                Tayori
                            </a>

                            <a
                                href="<?= e(
                                    base_url(
                                        'products.php'
                                    )
                                ); ?>"
                            >
                                Rakuda
                            </a>

                        <?php endif; ?>

                    </nav>

                </div>


                <!-- =========================================
                     CONTACT
                ========================================== -->

                <div
                    class="footer-column"
                >

                    <h3
                        class="footer-title"
                    >
                        HUBUNGI KAMI
                    </h3>


                    <div
                        class="footer-contact-list"
                    >

                        <?php if (
                            $address !== ''
                        ): ?>

                            <div
                                class="footer-contact-item"
                            >

                                <i
                                    class="fa-solid fa-location-dot"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    <?= e(
                                        $address
                                    ); ?>
                                </span>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $phone !== ''
                        ): ?>

                            <div
                                class="footer-contact-item"
                            >

                                <i
                                    class="fa-solid fa-phone"
                                    aria-hidden="true"
                                ></i>

                                <a
                                    href="tel:<?= e(
                                        preg_replace(
                                            '/[^0-9+]/',
                                            '',
                                            $phone
                                        )
                                    ); ?>"
                                >

                                    <?= e(
                                        $phone
                                    ); ?>

                                </a>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $whatsappNumber !== ''
                        ): ?>

                            <div
                                class="footer-contact-item"
                            >

                                <i
                                    class="fa-brands fa-whatsapp"
                                    aria-hidden="true"
                                ></i>

                                <a
                                    href="<?= e(
                                        $whatsappLink
                                    ); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >

                                    WhatsApp

                                </a>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $email !== ''
                        ): ?>

                            <div
                                class="footer-contact-item"
                            >

                                <i
                                    class="fa-solid fa-envelope"
                                    aria-hidden="true"
                                ></i>

                                <a
                                    href="mailto:<?= e(
                                        $email
                                    ); ?>"
                                >

                                    <?= e(
                                        $email
                                    ); ?>

                                </a>

                            </div>

                        <?php endif; ?>

                    </div>


                    <?php if (
                        $whatsappNumber !== ''
                    ): ?>

                        <a
                            href="<?= e(
                                $whatsappLink
                            ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-primary btn-sm"
                            style="margin-top:20px;"
                        >

                            <i
                                class="fa-brands fa-whatsapp"
                            ></i>

                            Chat Sekarang

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>


    <!-- ===================================================
         FOOTER BOTTOM
    ==================================================== -->

    <div
        class="footer-bottom"
    >

        <div
            class="container"
        >

            <div
                class="footer-bottom-inner"
            >

                <p
                    class="footer-copyright"
                >

                    <?= e(
                        $footerText
                    ); ?>

                    <?php if (
                        !str_contains(
                            $footerText,
                            (string) date('Y')
                        )
                    ): ?>

                        <?= ' ' . e(
                            (string) date('Y')
                        ); ?>

                    <?php endif; ?>

                </p>


                <div
                    class="footer-bottom-links"
                >

                    <a
                        href="<?= e(
                            base_url(
                                'privacy.php'
                            )
                        ); ?>"
                    >
                        Privacy Policy
                    </a>

                    <a
                        href="<?= e(
                            base_url(
                                'terms.php'
                            )
                        ); ?>"
                    >
                        Terms & Conditions
                    </a>

                </div>

            </div>

        </div>

    </div>

</footer>


<!-- =======================================================
     VUE.JS CDN
======================================================== -->

<script
    src="https://unpkg.com/vue@3/dist/vue.global.prod.js"
></script>


<!-- =======================================================
     SWIPER JS
======================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"
></script>


<!-- =======================================================
     AOS JS
======================================================== -->

<script
    src="https://unpkg.com/aos@2.3.4/dist/aos.js"
></script>


<!-- =======================================================
     APPLICATION JS
======================================================== -->

<?php
    $footerAppScriptPath = __DIR__ . '/../assets/js/app.js';
    $footerAppScriptVersion = is_file($footerAppScriptPath)
        ? (string) filemtime($footerAppScriptPath)
        : (string) time();
    $footerAppScriptUrl = base_url('assets/js/app.js');
    $footerAppScriptUrl .= (str_contains($footerAppScriptUrl, '?') ? '&' : '?')
        . 'v=' . rawurlencode($footerAppScriptVersion);
?>
<script src="<?= e($footerAppScriptUrl); ?>"></script>


<!-- =======================================================
     OPTIONAL PAGE SCRIPT
======================================================== -->

<?php if (
    isset($pageScript)
    &&
    trim((string) $pageScript) !== ''
): ?>

    <?= $pageScript; ?>

<?php endif; ?>


<!-- =======================================================
     CONTACT FORM ENHANCEMENT
======================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const forms =
            document.querySelectorAll(
                "[data-contact-form]"
            );


        if (!forms.length) {
            return;
        }


        forms.forEach(
            function (form) {

                form.addEventListener(
                    "submit",
                    function (event) {

                        /*
                         * Untuk sementara form tetap
                         * menggunakan POST biasa ke API.
                         *
                         * Handler ini hanya memberikan
                         * feedback visual ketika API
                         * mengembalikan halaman secara
                         * normal.
                         */

                        const button =
                            form.querySelector(
                                'button[type="submit"]'
                            );


                        if (!button) {
                            return;
                        }


                        button.classList.add(
                            "is-loading"
                        );

                    }
                );

            }
        );

    }
);

</script>


<!-- =======================================================
     END PAGE
======================================================== -->

</body>
</html>