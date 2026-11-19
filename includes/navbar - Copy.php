<?php
// ============================================================
// FILE   : navbar.php
// LOKASI : starparts/includes/navbar.php
// ============================================================
//
// Navigasi global website Star Parts Motor.
//
// Digunakan oleh halaman frontend:
// - index.php
// - about.php
// - products.php
// - product.php
// - news.php
// - news-detail.php
// - contact.php
//
// Fitur:
// - Top information bar
// - Desktop navigation
// - Dropdown Produk
// - Mobile navigation
// - CTA Hubungi Kami
// - WhatsApp shortcut
// - Active menu detection
//
// Data navigasi produk menggunakan kategori database apabila tersedia.
// Tidak mengubah alur halaman maupun struktur URL yang sudah digunakan.
// ============================================================

declare(strict_types=1);


/*
|------------------------------------------------------------------
| SITE DATA
|------------------------------------------------------------------
*/

$navSiteName = trim(
    (string)get_setting(
        'site_name',
        'PT Star Parts Motor'
    )
);
if ($navSiteName === '') { $navSiteName = 'PT Star Parts Motor'; }


// The top-bar tagline was intentionally removed in Phase 14 at the user's request.
$navTagline = '';


$navLogo = trim(
    (string)get_setting(
        'site_logo',
        ''
    )
);
if ($navLogo === '') { $navLogo = 'uploads/general/star-parts-motor-logo.png'; }


$navPhone = trim(
    (string)get_setting(
        'phone_primary',
        ''
    )
);


$navWhatsapp = trim(
    (string)get_setting(
        'whatsapp',
        ''
    )
);


$navEmail = trim(
    (string)get_setting(
        'email',
        ''
    )
);


/*
|------------------------------------------------------------------
| LOGO
|------------------------------------------------------------------
*/

$navLogoUrl = '';

if (
    $navLogo !== ''
    && function_exists('media_url')
) {

    $navLogoUrl = media_url(
        $navLogo
    );

    // When the site uses the default full logo, use a compact variant without the tagline in navigation.
    $navLogoFileName = strtolower(basename($navLogo));
    $navCompactLogoPath = 'uploads/general/star-parts-motor-logo-compact.webp';
    if (
        in_array($navLogoFileName, ['star-parts-motor-logo.png', 'star-parts-motor-logo.webp', 'star-parts-motor-logo-compact.png', 'star-parts-motor-logo-compact.webp'], true)
        && is_file(dirname(__DIR__) . '/' . $navCompactLogoPath)
    ) {
        $navLogoUrl = media_url($navCompactLogoPath);
    }
}


/*
|------------------------------------------------------------------
| WHATSAPP NUMBER
|------------------------------------------------------------------
*/

$navWhatsappNumber = preg_replace(
    '/[^0-9]/',
    '',
    $navWhatsapp
) ?? '';


if (
    str_starts_with(
        $navWhatsappNumber,
        '0'
    )
) {

    $navWhatsappNumber =
        '62' .
        substr(
            $navWhatsappNumber,
            1
        );
}


/*
|------------------------------------------------------------------
| WHATSAPP LINK
|------------------------------------------------------------------
*/

$navWhatsappLink = '#';


if (
    $navWhatsappNumber !== ''
) {

    $navWhatsappMessage =
        'Halo Star Parts Motor, saya ingin mendapatkan informasi mengenai produk dan kerja sama.';


    $navWhatsappLink =
        'https://wa.me/' .
        $navWhatsappNumber .
        '?text=' .
        rawurlencode(
            $navWhatsappMessage
        );
}


/*
|------------------------------------------------------------------
| CURRENT PAGE
|------------------------------------------------------------------
*/

$navCurrentPath = basename(
    (string)(
        parse_url(
            $_SERVER['REQUEST_URI']
            ?? '',
            PHP_URL_PATH
        )
        ?: 'index.php'
    )
);


if (
    $navCurrentPath === ''
) {

    $navCurrentPath =
        'index.php';
}


/*
|------------------------------------------------------------------
| ACTIVE HELPERS
|------------------------------------------------------------------
*/

$navIsActive = static function (
    array $pages
) use (
    $navCurrentPath
): bool {

    return in_array(
        $navCurrentPath,
        $pages,
        true
    );
};


/*
|------------------------------------------------------------------
| PRODUCT CATEGORIES
|------------------------------------------------------------------
*/

$navCategories = [];

try {

    $navCategories =
        get_categories(true);

} catch (Throwable $e) {

    $navCategories = [];
}


/*
|------------------------------------------------------------------
| PRODUCT CATEGORY LIMIT
|------------------------------------------------------------------
*/

$navCategories = array_slice(
    is_array($navCategories)
        ? $navCategories
        : [],
    0,
    12
);

/*
|------------------------------------------------------------------
| MENU DATABASE
|------------------------------------------------------------------
|
| Apabila helper menu tersedia, gunakan data tersebut.
| Apabila tidak ada, gunakan menu default agar website tetap
| dapat berjalan.
|
*/

$navDatabaseMenus = [];

/*
|------------------------------------------------------------------
| NORMALIZE DATABASE MENU TREE
|------------------------------------------------------------------
| Ambil submenu dari tabel menus supaya Menu Manager benar-benar
| tercermin pada desktop dan mobile navigation.
|------------------------------------------------------------------
*/
$navBuildMenuTree = static function (array $rows, int $depth = 0) use (&$navBuildMenuTree): array {
    if ($depth > 3) return [];
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $id = (int)($row['id'] ?? 0);
        $label = trim((string)($row['title'] ?? $row['label'] ?? ''));
        if ($label === '') continue;
        $children = [];
        if ($id > 0 && function_exists('get_child_menus')) {
            try {
                $children = $navBuildMenuTree(get_child_menus($id), $depth + 1);
            } catch (Throwable $e) {
                $children = [];
            }
        }
        $out[] = [
            'label' => $label,
            'url' => trim((string)($row['menu_url'] ?? $row['url'] ?? '#')),
            'slug' => trim((string)($row['slug'] ?? '')),
            'target' => trim((string)($row['target'] ?? '_self')),
            'children' => $children,
        ];
    }
    return $out;
};

if (
    function_exists('get_menus')
) {

    try {

        // Ambil menu level utama. `get_menus()` tanpa parameter
        // mengembalikan parent_id IS NULL sesuai struktur tabel `menus`.
        $navDatabaseMenus =
            get_menus();

    } catch (Throwable $e) {

        $navDatabaseMenus = [];
    }
}


/*
|------------------------------------------------------------------
| DEFAULT MENUS
|------------------------------------------------------------------
*/

$navDefaultMenus = [

    [
        'label' => 'Home',
        'url' => 'index.php',
        'children' => [],
    ],

    [
        'label' => 'Tentang Kami',
        'url' => 'about.php',
        'children' => [],
    ],

    [
        'label' => 'Produk',
        'url' => 'products.php',
        'children' => [],
    ],

    [
        'label' => 'Berita',
        'url' => 'news.php',
        'children' => [],
    ],

    [
        'label' => 'Kontak',
        'url' => 'contact.php',
        'children' => [],
    ],

];


/*
|------------------------------------------------------------------
| BUILD MENUS
|------------------------------------------------------------------
*/

$navMenus = $navDefaultMenus;


/*
|--------------------------------------------------------------------------
| Normalisasi menu database jika tersedia.
|--------------------------------------------------------------------------
*/

if (
    is_array($navDatabaseMenus)
    && !empty($navDatabaseMenus)
) {

    $normalizedMenus = [];


    foreach (
        $navDatabaseMenus
        as $menu
    ) {

        if (
            !is_array($menu)
        ) {
            continue;
        }


        $label = trim(
            (string)(
                $menu['label']
                ?? $menu['title']
                ?? $menu['name']
                ?? ''
            )
        );


        $url = trim(
            (string)(
                $menu['url']
                ?? $menu['link']
                ?? $menu['menu_url']
                ?? ''
            )
        );


        if (
            $label === ''
        ) {
            continue;
        }


        $normalizedMenus[] = [
            'label' => $label,
            'url' => $url,
            'slug' => trim((string)($menu['slug'] ?? '')),
            'target' => trim((string)($menu['target'] ?? '_self')),
            'children' => is_array($menu['children'] ?? null)
                ? $menu['children']
                : ($navBuildMenuTree((int)($menu['id'] ?? 0) > 0 ? get_child_menus((int)$menu['id']) : []) ?: []),
        ];

    }


    if (
        !empty($normalizedMenus)
    ) {

        $navMenus =
            $normalizedMenus;
    }
}


/*
|------------------------------------------------------------------
| URL HELPER
|------------------------------------------------------------------
*/

$navUrl = static function (
    string $url
): string {

    $url = trim(
        $url
    );


    if (
        $url === ''
    ) {

        return '#';
    }


    /*
    |--------------------------------------------------------------------------
    | External URL
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '#^(https?:)?//#i',
            $url
        )
    ) {

        return $url;
    }


    /*
    |--------------------------------------------------------------------------
    | Anchor
    |--------------------------------------------------------------------------
    */

    if (
        str_starts_with(
            $url,
            '#'
        )
    ) {

        return $url;
    }


    return function_exists('site_url')
        ? site_url(
            ltrim(
                $url,
                '/'
            )
        )
        : (function_exists('base_url') ? base_url(ltrim($url, '/')) : $url);
};


/*
|------------------------------------------------------------------
| ACTIVE CLASS HELPER
|------------------------------------------------------------------
*/

$navClassActive = static function (
    array $pages
) use (
    $navIsActive
): string {

    return $navIsActive(
        $pages
    )
        ? 'active'
        : '';
};


/*
|------------------------------------------------------------------
| ESCAPE HELPER
|------------------------------------------------------------------
*/

$navEscape = static function (
    mixed $value
): string {

    return function_exists('e')
        ? e(
            (string)$value
        )
        : htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
};

?>


<!-- =========================================================
     TOP INFORMATION BAR
========================================================= -->

<div
    class="top-bar"
>

    <div
        class="container"
    >

        <div
            class="top-bar-left"
        >

            <?php if (
                $navTagline !== ''
            ): ?>

                <span
                    class="top-bar-item"
                >

                    <i
                        class="fa-solid fa-circle-check"
                    ></i>

                    <?= $navEscape(
                        $navTagline
                    ); ?>

                </span>

            <?php endif; ?>


        </div>


        <div
            class="top-bar-right"
        >

            <?php if (
                $navPhone !== ''
            ): ?>

                <a
                    class="top-bar-item"
                    href="tel:<?= $navEscape(
                        preg_replace(
                            '/[^0-9+]/',
                            '',
                            $navPhone
                        )
                    ); ?>"
                >

                    <i
                        class="fa-solid fa-phone"
                    ></i>

                    <?= $navEscape(
                        $navPhone
                    ); ?>

                </a>

            <?php endif; ?>


            <?php if (
                $navEmail !== ''
            ): ?>

                <a
                    class="top-bar-item"
                    href="mailto:<?= $navEscape(
                        $navEmail
                    ); ?>"
                >

                    <i
                        class="fa-regular fa-envelope"
                    ></i>

                    <?= $navEscape(
                        $navEmail
                    ); ?>

                </a>

            <?php endif; ?>

        </div>

    </div>

</div>


<!-- =========================================================
     MAIN HEADER
========================================================= -->

<header
    id="site-header"
    class="main-header"
>

    <div
        class="container navbar"
    >


        <!-- ==================================================
             LOGO
        =================================================== -->

        <a
            href="<?= $navEscape(site_url('index.php')); ?>"
            class="brand-logo brand-logo-compact"
            aria-label="<?= $navEscape($navSiteName); ?>"
        >
            <?php if ($navLogoUrl !== ''): ?>
                <img
                    class="brand-logo-image brand-logo-header-image"
                    src="<?= $navEscape($navLogoUrl); ?>"
                    alt="<?= $navEscape($navSiteName); ?>"
                    width="220"
                    height="35"
                    fetchpriority="high"
                    decoding="async"
                >
            <?php else: ?>
                <span class="brand-logo-text-only">
                    <strong>PT STAR PARTS</strong>
                    <small>MOTOR</small>
                </span>
            <?php endif; ?>
        </a>


        <!-- ==================================================
             DESKTOP NAVIGATION
        =================================================== -->

        <nav
            class="desktop-navigation"
            aria-label="Navigasi utama"
        >

            <div
                class="navigation-list"
            >

                <?php foreach (
                $navMenus
                as $menu
            ): ?>

                <?php

                if (
                    !is_array($menu)
                ) {
                    continue;
                }


                $menuLabel =
                    trim(
                        (string)(
                            $menu['label']
                            ?? ''
                        )
                    );


                $menuUrl =
                    trim(
                        (string)(
                            $menu['url']
                            ?? '#'
                        )
                    );


                $menuChildren =
                    is_array(
                        $menu['children']
                        ?? null
                    )
                        ? $menu['children']
                        : [];


                /*
                |--------------------------------------------------------------------------
                | Produk default dropdown
                |--------------------------------------------------------------------------
                */

                $isProductMenu =
                    mb_strtolower(
                        $menuLabel
                    ) === 'produk'
                    ||
                    str_contains(
                        mb_strtolower(
                            $menuUrl
                        ),
                        'products.php'
                    );


                if (
                    $isProductMenu
                    && empty($menuChildren)
                ) {

                    $menuChildren =
                        $navCategories;
                }


                $hasChildren =
                    $isProductMenu
                    || !empty(
                        $menuChildren
                    );


                $menuActive = false;


                if (
                    $isProductMenu
                ) {

                    $menuActive =
                        $navIsActive(
                            [
                                'products.php',
                                'product.php'
                            ]
                        );

                } else {

                    $menuActive =
                        $navIsActive(
                            [
                                basename(
                                    parse_url(
                                        $menuUrl,
                                        PHP_URL_PATH
                                    )
                                    ?: $menuUrl
                                )
                            ]
                        );
                }

                ?>


                <div
                    class="navigation-item <?= $hasChildren
                        ? 'has-dropdown'
                        : ''; ?> <?= $menuActive
                        ? 'active'
                        : ''; ?> <?= $isProductMenu ? 'fdr-mega-parent' : ''; ?>"
                >

                    <a
                        href="<?= $navEscape(
                            $navUrl(
                                $menuUrl
                            )
                        ); ?>"
                        class="navigation-link"
                        <?= (
                            preg_match(
                                '#^(https?:)?//#i',
                                $menuUrl
                            )
                        )
                            ? 'target="_blank" rel="noopener noreferrer"'
                            : ''; ?>
                    >

                        <span>
                            <?= $navEscape(
                                $menuLabel
                            ); ?>
                        </span>


                        <?php if (
                            $hasChildren
                        ): ?>

                            <i
                                class="fa-solid fa-chevron-down dropdown-icon"
                            ></i>

                        <?php endif; ?>

                    </a>


                    <?php if (
                        $hasChildren
                    ): ?>

                        <?php if ($isProductMenu): ?>
                            <div class="fdr-mega-menu" aria-label="Menu produk ban FDR">
                                <div class="fdr-mega-menu-grid">
                                    <section class="fdr-mega-column fdr-mega-search-column">
                                        <h2>SEARCH</h2>
                                        <form class="fdr-mega-search-form" action="<?= $navEscape(base_url('products.php')); ?>" method="get" role="search">
                                            <label class="sr-only" for="fdr-mega-search">Cari produk otomotif</label>
                                            <input id="fdr-mega-search" type="search" name="q" placeholder="Cari ban atau lampu..." autocomplete="off">
                                            <button type="submit" aria-label="Cari produk"><i class="fa-solid fa-magnifying-glass"></i></button>
                                        </form>
                                        <a class="fdr-mega-link" href="<?= $navEscape(base_url('products.php?browse=function#product-list')); ?>"><span>By Function</span><i class="fa-solid fa-chevron-right"></i></a>
                                        <a class="fdr-mega-link" href="<?= $navEscape(base_url('products.php?browse=size#catalog')); ?>"><span>By Size</span><i class="fa-solid fa-chevron-right"></i></a>
                                        <p class="fdr-mega-note">Cari produk berdasarkan nama, fungsi, atau informasi ukuran yang tercantum.</p>
                                    </section>

                                    <section class="fdr-mega-column fdr-mega-browse-column">
                                        <h2>BROWSE</h2>
                                        <a class="fdr-mega-link" href="<?= $navEscape(base_url('products.php?browse=all#product-list')); ?>"><span>All Tire</span><i class="fa-solid fa-chevron-right"></i></a>
                                        <a class="fdr-mega-link" href="<?= $navEscape(base_url('products.php?browse=inner#product-list')); ?>"><span>Inner Tire</span><i class="fa-solid fa-chevron-right"></i></a>
                                        <a class="fdr-mega-view-all" href="<?= $navEscape(base_url('products.php?browse=all#product-list')); ?>">Lihat seluruh katalog <i class="fa-solid fa-arrow-right"></i></a>
                                    </section>

                                    <section class="fdr-mega-column fdr-mega-type-column" aria-label="Jenis ban">
                                        <div class="fdr-mega-column-spacer" aria-hidden="true"></div>
                                        <a class="fdr-mega-link" href="<?= $navEscape(base_url('products.php?browse=tubetype#product-list')); ?>"><span>Tubetype Tire</span><i class="fa-solid fa-chevron-right"></i></a>
                                        <a class="fdr-mega-link" href="<?= $navEscape(base_url('products.php?browse=tubeless#product-list')); ?>"><span>Tubeless Tire</span><i class="fa-solid fa-chevron-right"></i></a>
                                        <div class="osram-mega-divider"><span>LIGHTING</span></div>
                                        <a class="fdr-mega-link osram-mega-link osram-mega-logo-link" href="<?= $navEscape(base_url('products.php?category=osram#product-list')); ?>"><span><img src="<?= $navEscape(media_url('uploads/general/logo-osram.png')); ?>" alt="OSRAM" loading="lazy"><small>Automotive Lighting</small></span><i class="fa-solid fa-chevron-right"></i></a>
                                    </section>

                                    <section class="fdr-mega-column fdr-mega-brands-column" aria-label="Lini produk FDR">
                                        <h2>PILIH LINI PRODUK</h2>
                                        <div class="fdr-mega-brand-grid">
                                            <a class="fdr-brand-card fdr-brand-tire" href="<?= $navEscape(base_url('products.php?category=fdr-tire#product-list')); ?>">
                                                <img src="<?= $navEscape(media_url('uploads/general/fdr-tire-logo-white.png')); ?>" alt="FDR Tire" loading="lazy">
                                                <span>FDR TIRE</span><i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                            <a class="fdr-brand-card fdr-brand-ultimate" href="<?= $navEscape(base_url('products.php?category=fdr-ultimate#product-list')); ?>">
                                                <img src="<?= $navEscape(media_url('uploads/general/fdr-tire-logo-white.png')); ?>" alt="FDR" loading="lazy">
                                                <span>ULTIMATE</span><i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                            <a class="fdr-brand-card fdr-brand-champion" href="<?= $navEscape(base_url('products.php?category=fdr-champion#product-list')); ?>">
                                                <img src="<?= $navEscape(media_url('uploads/general/fdr-tire-logo-white.png')); ?>" alt="FDR" loading="lazy">
                                                <span>CHAMPION</span><i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        <?php else: ?>

                        <div
                            class="dropdown-menu"
                        >

                            <?php

                            $displayedCategoryIds = [];

                            foreach (
                                $menuChildren
                                as $child
                            ):

                                if (
                                    !is_array(
                                        $child
                                    )
                                ) {
                                    continue;
                                }


                                $childName =
                                    trim(
                                        (string)(
                                            $child['name']
                                            ?? $child['label']
                                            ?? $child['title']
                                            ?? ''
                                        )
                                    );


                                $childSlug =
                                    trim(
                                        (string)(
                                            $child['slug']
                                            ?? ''
                                        )
                                    );


                                $childUrl =
                                    trim(
                                        (string)(
                                            $child['url']
                                            ?? $child['link']
                                            ?? ''
                                        )
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | Category database
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $childUrl === ''
                                    && $childSlug !== ''
                                    && $isProductMenu
                                ) {

                                    $childUrl =
                                        'products.php?category=' .
                                        rawurlencode(
                                            $childSlug
                                        );
                                }


                                if (
                                    $childName === ''
                                ) {
                                    continue;
                                }


                                $childKey =
                                    strtolower(
                                        $childName
                                    );


                                if (
                                    isset(
                                        $displayedCategoryIds[
                                            $childKey
                                        ]
                                    )
                                ) {
                                    continue;
                                }


                                $displayedCategoryIds[
                                    $childKey
                                ] = true;

                            ?>

                                <a
                                    href="<?= $navEscape(
                                        $navUrl(
                                            $childUrl !== ''
                                                ? $childUrl
                                                : '#'
                                        )
                                    ); ?>"
                                    class="dropdown-link"
                                    <?= (($child['target'] ?? '_self') === '_blank') ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                                >

                                    <span
                                        class="dropdown-icon-box"
                                    >

                                        <i
                                            class="fa-solid fa-chevron-right"
                                        ></i>

                                    </span>

                                    <span
                                        class="dropdown-link-content"
                                    >

                                        <strong>
                                            <?= $navEscape(
                                                $childName
                                            ); ?>
                                        </strong>

                                    </span>

                                    <i
                                        class="fa-solid fa-arrow-right"
                                        aria-hidden="true"
                                    ></i>

                                </a>


                            <?php endforeach; ?>


                            <?php if (
                                $isProductMenu
                            ): ?>

                                <div
                                    class="dropdown-footer"
                                >

                                    <a
                                        href="<?= $navEscape(
                                            base_url(
                                                'products.php'
                                            )
                                        ); ?>"
                                        class="dropdown-footer-link"
                                    >

                                        <span>
                                            Semua Produk
                                        </span>

                                        <i
                                            class="fa-solid fa-arrow-right"
                                        ></i>

                                    </a>

                                </div>

                            <?php endif; ?>


                        </div>

                        <?php endif; // end normal dropdown branch ?>
                    <?php endif; // end has-children branch ?>

                </div>


                <?php endforeach; ?>

            </div>


        </nav>


        <!-- ==================================================
             HEADER ACTIONS
        =================================================== -->

        <div
            class="navbar-actions"
        >
<?php if (
                $navWhatsappLink !== '#'
            ): ?>

                <a
                    href="<?= $navEscape(
                        $navWhatsappLink
                    ); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display: inline-flex; align-items: center; gap: 8px; background-color: #25D366; color: #ffffff; padding: 8px 18px; border-radius: 50px; font-size: 14px; font-weight: 600; text-decoration: none;"
                    aria-label="WhatsApp Star Parts Motor"
                >

                    <i 
                        class="fa-brands fa-whatsapp" 
                        style="font-size: 18px;"
                    ></i>

                    <span>WhatsApp</span>

                </a>

            <?php endif; ?>

            <a
                href="http://bengkelku.online/"
                class="navbar-partner-button"
                aria-label="Ingin Menjadi Mitra Dengan Kami?"
                title="Ingin Menjadi Mitra Dengan Kami?"
            >
                <i class="fa-solid fa-handshake-angle" aria-hidden="true"></i>
                <span>Ingin Menjadi Mitra Dengan Kami?</span>
            </a>


            <a
                href="<?= $navEscape(
                    base_url(
                        'contact.php'
                    )
                ); ?>"
                class="navbar-contact-button"
            >

                <span
                    class="navbar-contact-icon"
                    aria-hidden="true"
                >

                    <i
                        class="fa-solid fa-arrow-right"
                    ></i>

                </span>

                <span
                    class="navbar-contact-text"
                >

                    <small>
                        HUBUNGI
                    </small>

                    <strong>
                        Hubungi Kami
                    </strong>

                </span>

            </a>


            <!-- =============================================
                 MOBILE TOGGLE
            ============================================== -->

            <button
                type="button"
                class="mobile-menu-toggle"
                id="mobile-menu-toggle"
                aria-label="Buka menu"
                aria-controls="mobile-navigation"
                aria-expanded="false"
            >

                <span></span>
                <span></span>
                <span></span>

            </button>


        </div>


    </div>

</header>


<!-- =========================================================
     MOBILE NAVIGATION
========================================================= -->

<div
    class="mobile-menu"
    id="mobile-navigation"
    aria-hidden="true"
>


        <div
            class="mobile-menu-header"
        >

            <div class="mobile-menu-brand mobile-menu-brand-compact">
                <?php if ($navLogoUrl !== ''): ?>
                    <img
                        class="brand-logo-image brand-logo-header-image"
                        src="<?= $navEscape($navLogoUrl); ?>"
                        alt="<?= $navEscape($navSiteName); ?>"
                        width="200"
                        height="32"
                        decoding="async"
                    >
                <?php else: ?>
                    <span class="brand-logo-text-only">
                        <strong>PT STAR PARTS</strong>
                        <small>MOTOR</small>
                    </span>
                <?php endif; ?>
            </div>


            <button
                type="button"
                class="mobile-menu-close"
                id="mobile-navigation-close"
                aria-label="Tutup menu"
            >

                <i
                    class="fa-solid fa-xmark"
                ></i>

            </button>

        </div>


    <div
        class="mobile-menu-content"
    >

        <nav
            class="mobile-navigation"
            aria-label="Navigasi mobile"
        >


            <?php foreach (
                $navMenus
                as $mobileMenu
            ): ?>

                <?php

                if (
                    !is_array(
                        $mobileMenu
                    )
                ) {
                    continue;
                }


                $mobileLabel =
                    trim(
                        (string)(
                            $mobileMenu['label']
                            ?? ''
                        )
                    );


                $mobileUrl =
                    trim(
                        (string)(
                            $mobileMenu['url']
                            ?? '#'
                        )
                    );


                $mobileChildren =
                    is_array(
                        $mobileMenu['children']
                        ?? null
                    )
                        ? $mobileMenu['children']
                        : [];


                $mobileIsProduct =
                    mb_strtolower(
                        $mobileLabel
                    ) === 'produk'
                    ||
                    str_contains(
                        mb_strtolower(
                            $mobileUrl
                        ),
                        'products.php'
                    );


                if (
                    $mobileIsProduct
                    && empty(
                        $mobileChildren
                    )
                ) {

                    $mobileChildren =
                        $navCategories;
                }


                $mobileHasChildren =
                    !empty(
                        $mobileChildren
                    );


                $mobileActive =
                    $mobileIsProduct
                        ? $navIsActive(
                            [
                                'products.php',
                                'product.php'
                            ]
                        )
                        : $navIsActive(
                            [
                                basename(
                                    parse_url(
                                        $mobileUrl,
                                        PHP_URL_PATH
                                    )
                                    ?: $mobileUrl
                                )
                            ]
                        );

                ?>


                <div
                    class="mobile-navigation-item<?= $mobileActive
                        ? ' active'
                        : ''; ?>"
                >

                    <div
                        class="mobile-navigation-main"
                    >

                        <a
                            href="<?= $navEscape(
                                $navUrl(
                                    $mobileUrl
                                )
                            ); ?>"
                            class="mobile-navigation-link"
                            <?= (($mobileMenu['target'] ?? '_self') === '_blank') ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                        >

                            <?= $navEscape(
                                $mobileLabel
                            ); ?>

                        </a>


                        <?php if (
                            $mobileHasChildren
                        ): ?>

                            <button
                                type="button"
                                class="mobile-submenu-toggle"
                                aria-label="Buka submenu <?= $navEscape(
                                    $mobileLabel
                                ); ?>"
                                aria-expanded="false"
                            >

                                <i
                                    class="fa-solid fa-chevron-down"
                                ></i>

                            </button>

                        <?php endif; ?>

                    </div>


                    <?php if (
                        $mobileHasChildren
                    ): ?>

                        <div
                            class="mobile-submenu"
                        >

                            <?php

                            $mobileShown = [];
                            // Product submenu uses the curated browse links and branded tiles below.
                            // Suppress database child entries here so individual model names do not flood the menu.
                            if ($mobileIsProduct) {
                                $mobileChildren = [];
                            }

                            foreach (
                                $mobileChildren
                                as $mobileChild
                            ):

                                if (
                                    !is_array(
                                        $mobileChild
                                    )
                                ) {
                                    continue;
                                }


                                $mobileChildName =
                                    trim(
                                        (string)(
                                            $mobileChild['name']
                                            ?? $mobileChild['label']
                                            ?? $mobileChild['title']
                                            ?? ''
                                        )
                                    );


                                $mobileChildSlug =
                                    trim(
                                        (string)(
                                            $mobileChild['slug']
                                            ?? ''
                                        )
                                    );


                                $mobileChildUrl =
                                    trim(
                                        (string)(
                                            $mobileChild['url']
                                            ?? $mobileChild['link']
                                            ?? ''
                                        )
                                    );


                                if (
                                    $mobileChildUrl === ''
                                    && $mobileChildSlug !== ''
                                    && $mobileIsProduct
                                ) {

                                    $mobileChildUrl =
                                        'products.php?category=' .
                                        rawurlencode(
                                            $mobileChildSlug
                                        );
                                }


                                if (
                                    $mobileChildName === ''
                                ) {
                                    continue;
                                }


                                $mobileChildKey =
                                    strtolower(
                                        $mobileChildName
                                    );


                                if (
                                    isset(
                                        $mobileShown[
                                            $mobileChildKey
                                        ]
                                    )
                                ) {
                                    continue;
                                }


                                $mobileShown[
                                    $mobileChildKey
                                ] = true;

                            ?>

                                <a
                                    href="<?= $navEscape(
                                        $navUrl(
                                            $mobileChildUrl !== ''
                                                ? $mobileChildUrl
                                                : '#'
                                        )
                                    ); ?>"
                                    class="mobile-submenu-link"
                                >

                                    <i
                                        class="fa-solid fa-chevron-right"
                                    ></i>

                                    <?= $navEscape(
                                        $mobileChildName
                                    ); ?>

                                </a>

                            <?php endforeach; ?>

                            <?php if ($mobileIsProduct): ?>
                                <div class="mobile-fdr-browse-links" aria-label="Browse produk ban">
                                    <a href="<?= $navEscape(base_url('products.php?browse=all#product-list')); ?>">All Tire <i class="fa-solid fa-chevron-right"></i></a>
                                    <a href="<?= $navEscape(base_url('products.php?browse=inner#product-list')); ?>">Inner Tire <i class="fa-solid fa-chevron-right"></i></a>
                                    <a href="<?= $navEscape(base_url('products.php?browse=tubetype#product-list')); ?>">Tubetype Tire <i class="fa-solid fa-chevron-right"></i></a>
                                    <a href="<?= $navEscape(base_url('products.php?browse=tubeless#product-list')); ?>">Tubeless Tire <i class="fa-solid fa-chevron-right"></i></a>
                                    <a class="mobile-osram-lighting-link mobile-osram-logo-link" href="<?= $navEscape(base_url('products.php?category=osram#product-list')); ?>"><span><img src="<?= $navEscape(media_url('uploads/general/logo-osram.png')); ?>" alt="OSRAM" loading="lazy"><small>Automotive Lighting</small></span><i class="fa-solid fa-chevron-right"></i></a>
                                </div>
                            <?php endif; ?>

                            <?php if ($mobileIsProduct): ?>
                                <div class="mobile-fdr-brand-grid" aria-label="Pilih lini produk FDR">
                                    <a class="fdr-brand-card fdr-brand-tire" href="<?= $navEscape(base_url('products.php?category=fdr-tire#product-list')); ?>">
                                        <img src="<?= $navEscape(media_url('uploads/general/fdr-tire-logo-white.png')); ?>" alt="FDR Tire" loading="lazy">
                                        <span>FDR TIRE</span><i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                    <a class="fdr-brand-card fdr-brand-ultimate" href="<?= $navEscape(base_url('products.php?category=fdr-ultimate#product-list')); ?>">
                                        <img src="<?= $navEscape(media_url('uploads/general/fdr-tire-logo-white.png')); ?>" alt="FDR" loading="lazy">
                                        <span>ULTIMATE</span><i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                    <a class="fdr-brand-card fdr-brand-champion" href="<?= $navEscape(base_url('products.php?category=fdr-champion#product-list')); ?>">
                                        <img src="<?= $navEscape(media_url('uploads/general/fdr-tire-logo-white.png')); ?>" alt="FDR" loading="lazy">
                                        <span>CHAMPION</span><i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (
                                $mobileIsProduct
                            ): ?>

                                <a
                                    href="<?= $navEscape(
                                        base_url(
                                            'products.php'
                                        )
                                    ); ?>"
                                    class="mobile-submenu-link mobile-submenu-all"
                                >

                                    <i
                                        class="fa-solid fa-layer-group"
                                    ></i>

                                    Semua Produk

                                </a>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>


            <?php endforeach; ?>


            <!-- ==================================================
                 MOBILE CTA
            =================================================== -->

            <div
                class="cta-actions"
            >

                <?php if (
                    $navWhatsappLink !== '#'
                ): ?>

                    <a
                        href="<?= $navEscape(
                            $navWhatsappLink
                        ); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-outline"
                    >

                        <i
                            class="fa-brands fa-whatsapp"
                        ></i>

                        WhatsApp

                    </a>

                <?php endif; ?>


                <a
                    href="http://bengkelku.online/"
                    class="btn btn-outline mobile-partner-cta"
                >
                    <i class="fa-solid fa-handshake-angle" aria-hidden="true"></i>
                    Ingin Menjadi Mitra Dengan Kami?
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>

                <a
                    href="<?= $navEscape(
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


        </nav>

    </div>

</div>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    class="mobile-menu-overlay"
    id="mobile-nav-overlay"
    aria-hidden="true"
></div>