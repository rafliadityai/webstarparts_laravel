<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/fdr_catalog.php';

$slug = trim(get_string('slug'));
$product = $slug !== '' ? get_product_by_slug($slug) : null;
$siteName = trim((string)get_setting('site_name', 'PT Star Parts Motor'));
if ($siteName === '') $siteName = 'PT Star Parts Motor';

if (!$product) {
    http_response_code(404);
    $pageRobots = 'noindex, follow';
    $pageTitle = 'Produk Tidak Ditemukan | ' . $siteName;
    $pageDescription = 'Produk yang Anda cari tidak tersedia.';
    $pageImage = get_setting('site_logo', '');
    $pageCanonicalUrl = site_url('products.php');
    $bodyClass = 'product-detail-page';
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    ?>
    <main id="main-content"><section class="section section-white"><div class="container" style="padding:90px 0;text-align:center"><span class="eyebrow">404</span><h1 class="section-title">Produk Tidak Ditemukan</h1><p class="section-description">Produk mungkin sudah tidak aktif atau tautannya sudah berubah.</p><div style="margin-top:25px"><a class="btn btn-primary" href="<?= e(site_url('products.php')); ?>"><i class="fa-solid fa-arrow-left"></i> Kembali ke Produk</a></div></div></section></main>
    <?php require __DIR__ . '/includes/footer.php'; exit;
}

$productName = trim((string)$product['name']);
$productMetaTitle = trim((string)($product['meta_title'] ?? ''));
$productMetaDescription = trim((string)($product['meta_description'] ?? ''));
$pageDescription = seo_clean_text(trim((string)($product['short_description'] ?? '')), 160);
if ($pageDescription === '') $pageDescription = seo_clean_text((string)($product['description'] ?? ''), 160);
if ($productMetaDescription !== '') $pageDescription = seo_clean_text($productMetaDescription, 160);
if ($pageDescription === '') $pageDescription = get_setting('meta_description', get_setting('site_description', 'Produk otomotif PT Star Parts Motor.'));
$pageTitle = $productMetaTitle !== '' ? $productMetaTitle : ($productName . ' | ' . $siteName);
$pageImage = trim((string)($product['image'] ?? ''));
$prettyProductUrl = site_url('produk/' . rawurlencode((string)$product['slug']));
$pageCanonicalUrl = $prettyProductUrl;
$bodyClass = 'product-detail-page';
if (isset($_GET['slug']) && trim((string)$_GET['slug']) !== '' && !preg_match('~/produk/[^/?]+$~', (string)($_SERVER['REQUEST_URI'] ?? ''))) {
    header('Location: ' . $prettyProductUrl, true, 301); exit;
}

$detail = fdr_get_product_detail((int)$product['id']);
$categorySlug = (string)($product['category_slug'] ?? '');
$isFdrProduct = in_array($categorySlug, ['fdr-tire', 'fdr-ultimate', 'fdr-champion'], true);
$isOsramProduct = $categorySlug === 'osram';
if ($isOsramProduct) {
    $bodyClass = 'osram-product-detail-page';
    $extraHead = (string)($extraHead ?? '') . '<link rel="stylesheet" href="' . e(base_url('assets/css/osram-products.css')) . '">';
}
$line = fdr_line_presentation($categorySlug);
$heroDescription = trim((string)($detail['hero_description'] ?? ''));
if ($heroDescription === '') $heroDescription = trim((string)($product['short_description'] ?? ''));
if ($heroDescription === '') $heroDescription = $pageDescription;
$heroDescription = fdr_hide_price_amounts($heroDescription);
$pageDescription = fdr_hide_price_amounts($pageDescription);
$listingDescription = trim((string)($detail['listing_description'] ?? ''));
$logoPath = trim((string)($detail['logo_path'] ?? ''));
if ($logoPath === '') $logoPath = 'uploads/general/fdr-tire-logo-red.png';
$virtualTireUrl = fdr_normalize_http_url((string)($detail['virtual_tire_url'] ?? ''));
if ($virtualTireUrl === '') $virtualTireUrl = 'https://bengkelku.online';
$buyUrlConfigured = fdr_normalize_http_url((string)($product['product_url'] ?? ''));
$buyUrl = $buyUrlConfigured !== '' ? $buyUrlConfigured : 'https://bengkelku.online';

$gallery = [];
$decodedGallery = json_decode((string)($product['gallery'] ?? ''), true);
if (is_array($decodedGallery)) $gallery = array_values(array_filter(array_map(static fn($v) => trim((string)$v), $decodedGallery)));
elseif (trim((string)($product['gallery'] ?? '')) !== '') $gallery = array_values(array_filter(array_map('trim', explode(',', (string)$product['gallery']))));

$specs = [];
$rawSpecs = trim((string)($product['specifications'] ?? ''));
if ($rawSpecs !== '') {
    $decoded = json_decode($rawSpecs, true);
    if (is_array($decoded)) {
        foreach ($decoded as $k => $v) {
            if (is_array($v) && isset($v['label'], $v['value'])) $specs[] = [trim((string)$v['label']), trim((string)$v['value'])];
            elseif (!is_array($v)) $specs[] = [trim((string)$k), trim((string)$v)];
        }
    }
    if (!$specs) foreach (preg_split('/\r\n|\r|\n/', $rawSpecs) as $specLine) {
        $specLine = trim($specLine);
        if ($specLine === '' || !str_contains($specLine, ':')) continue;
        [$a, $b] = array_map('trim', explode(':', $specLine, 2)); $specs[] = [$a, $b];
    }
}

$legacySections = fdr_legacy_description_sections((string)($product['description'] ?? ''));

// Sembunyikan nominal harga dari konten teks spesifikasi publik tanpa mengubah data database.
$specs = array_values(array_filter(array_map(static function (array $spec): array {
    $label = trim((string)($spec[0] ?? ''));
    $value = trim((string)($spec[1] ?? ''));

    if (preg_match('/\b(?:harga|price|retail price|selling price)\b/i', $label)) {
        return [];
    }

    $value = preg_replace('/\b(?:Rp\.?|IDR)\s*[0-9][0-9.,]*/iu', '', $value) ?? $value;
    $value = trim(preg_replace('/\s{2,}/u', ' ', $value) ?? $value);

    return ($label !== '' && $value !== '') ? [$label, $value] : [];
}, $specs), static fn(array $spec): bool => count($spec) === 2));

$benefitRows = fdr_parse_text_rows((string)($detail['benefits_text'] ?? ''), 2);
$sizeRows = fdr_parse_text_rows((string)($detail['sizes_text'] ?? ''), 2);
$legacySizeRows = fdr_parse_legacy_size_rows($legacySections['sizes']);
$displaySizeRows = $sizeRows ?: $legacySizeRows;
$performanceRows = fdr_parse_text_rows((string)($detail['performance_text'] ?? ''), 2);
$technologyRows = fdr_parse_text_rows((string)($detail['technologies_text'] ?? ''), 2);

$pageStructuredData = [[
    '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $productName,
    'description' => $pageDescription, 'url' => $pageCanonicalUrl,
]];
if ($pageImage !== '') $pageStructuredData[0]['image'] = [absolute_url($pageImage)];
if (!empty($product['category_name'])) $pageStructuredData[0]['category'] = (string)$product['category_name'];
$pageStructuredData[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => site_url()],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Produk', 'item' => site_url('products.php')],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $productName, 'item' => $pageCanonicalUrl],
]];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main id="main-content">
<?php if ($isFdrProduct): ?>
<section class="fdr-product-experience <?= e($line['class']); ?>" style="--fdr-line-color:<?= e($line['color']); ?>">
    <div class="fdr-product-visual">
        <div class="fdr-product-visual-top"><a href="<?= e(site_url('products.php?category=' . rawurlencode($categorySlug))); ?>" aria-label="Kembali ke <?= e($line['title']); ?>"><i class="fa-solid fa-arrow-left"></i> <?= e($line['title']); ?></a><img src="<?= e(media_url('uploads/general/fdr-tire-logo-white.png')); ?>" alt="FDR Tire" class="fdr-detail-logo-white"></div>
        <div class="fdr-product-hero-image">
            <?php if ($pageImage !== ''): ?><img src="<?= e(media_url($pageImage)); ?>" alt="<?= e(trim((string)($product['image_alt'] ?? '')) !== '' ? (string)$product['image_alt'] : $productName); ?>" loading="eager"><?php else: ?><i class="fa-solid fa-circle-notch"></i><?php endif; ?>
        </div>
        <div class="fdr-product-visual-caption"><span><?= e($line['title']); ?></span><strong><?= e($productName); ?></strong></div>
    </div>
    <div class="fdr-product-information">
        <div class="fdr-product-topbar"><div class="fdr-language-label"><span>Indonesia</span><span aria-hidden="true">|</span><span>English</span></div><a href="<?= e($virtualTireUrl); ?>" class="fdr-virtual-button" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrows-rotate"></i><span>Ganti Ban<br>Virtual</span><i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
        <div class="fdr-product-heading">
            <div class="fdr-breadcrumb"><a href="<?= e(site_url()); ?>">Beranda</a><span>/</span><a href="<?= e(site_url('products.php?category=' . rawurlencode($categorySlug))); ?>"><?= e($line['title']); ?></a><span>/</span><span><?= e($productName); ?></span></div>
            <div class="fdr-category-pill"><?= e($line['title']); ?></div>
            <h1><?= e($productName); ?></h1>
            <p><?= e($heroDescription); ?></p>
            <div class="fdr-detail-actions"><a class="fdr-action-secondary" href="#fdr-sizes"><i class="fa-solid fa-ruler-combined"></i> Available Sizes</a><a class="fdr-action-buy" href="<?= e($buyUrl); ?>" target="_blank" rel="noopener noreferrer" <?= $buyUrlConfigured === '' ? 'title="Link detail spesifik belum diisi; saat ini menuju bengkelku.online"' : ''; ?>><span>Buy Now</span><i class="fa-solid fa-cart-shopping"></i></a></div>
        </div>

        <section class="fdr-detail-section fdr-benefits-section" id="fdr-benefits"><div class="fdr-section-heading"><h2>Keunggulan</h2><span></span></div>
            <?php if ($benefitRows): ?><div class="fdr-benefit-grid"><?php foreach ($benefitRows as $row): ?><article class="fdr-benefit-card"><strong><?= e((string)($row[0] ?? '')); ?></strong><p><?= e((string)($row[1] ?? '')); ?></p></article><?php endforeach; ?></div>
            <?php elseif (trim($legacySections['benefits']) !== ''): ?><div class="fdr-legacy-content fdr-benefit-legacy"><?= fdr_safe_content_html($legacySections['benefits']); ?></div><?php else: ?><div class="fdr-legacy-content">Keunggulan produk belum dilengkapi. Tambahkan informasi resmi melalui Admin → Konten Detail Ban.</div><?php endif; ?>
        </section>

        <section class="fdr-detail-section" id="fdr-sizes"><div class="fdr-section-heading"><h2>Size &amp; Rims</h2><span></span></div>
            <?php if ($displaySizeRows): ?><div class="fdr-size-list"><?php foreach ($displaySizeRows as $row): ?><article class="fdr-size-item"><strong class="fdr-size-name"><?= e((string)($row[0] ?? '')); ?></strong><?php if (trim((string)($row[3] ?? '')) !== ''): ?><span class="fdr-size-tag"><?= e((string)$row[3]); ?></span><?php endif; ?><?php if ($buyUrl !== ''): ?><a class="fdr-size-buy" href="<?= e($buyUrl); ?>" target="_blank" rel="noopener noreferrer" aria-label="Beli <?= e((string)($row[0] ?? $productName)); ?>"><i class="fa-solid fa-cart-shopping"></i></a><?php endif; ?></article><?php endforeach; ?></div>
            <?php elseif (trim($legacySections['sizes']) !== ''): ?><div class="fdr-legacy-content"><?= fdr_safe_content_html(preg_replace('/\b(?:Rp\.?|IDR)\s*[0-9][0-9.,]*/iu', '', $legacySections['sizes']) ?? $legacySections['sizes']); ?></div>
            <?php elseif ($specs): ?><div class="fdr-size-list"><?php foreach ($specs as [$label, $value]): ?><article class="fdr-size-item"><strong class="fdr-size-name"><?= e($label); ?></strong><span><?= e($value); ?></span></article><?php endforeach; ?></div><?php else: ?><div class="fdr-legacy-content">Ukuran dan harga belum dilengkapi. Silakan hubungi tim kami atau lengkapi datanya melalui Admin → Konten Detail Ban.</div><?php endif; ?>
        </section>

        <section class="fdr-detail-section" id="fdr-performance"><div class="fdr-section-heading"><h2>Performance Ratings</h2><span></span></div>
            <?php if ($performanceRows): ?><div class="fdr-performance-grid"><?php foreach ($performanceRows as $row): $score = is_numeric($row[1] ?? null) ? max(0, min(5, (float)$row[1])) : null; ?><article class="fdr-performance-item"><span><?= e((string)($row[0] ?? '')); ?></span><?php if ($score !== null): ?><strong><?= e(rtrim(rtrim(number_format($score, 1, '.', ''), '0'), '.')); ?>/5</strong><div class="fdr-rating-track"><i style="width:<?= e((string)($score * 20)); ?>%"></i></div><?php endif; ?></article><?php endforeach; ?></div><p class="fdr-rating-note">Nilai rating ditampilkan hanya jika diisi melalui admin berdasarkan sumber penilaian yang tersedia.</p>
            <?php elseif (trim($legacySections['performance']) !== ''): ?><div class="fdr-legacy-content"><?= fdr_safe_content_html($legacySections['performance']); ?></div><?php else: ?><div class="fdr-legacy-content">Penilaian performa belum tersedia. Nilai tidak dibuat otomatis; lengkapi bila data resmi sudah ada melalui Admin → Konten Detail Ban.</div><?php endif; ?>
        </section>

        <section class="fdr-detail-section" id="fdr-technology"><div class="fdr-section-heading"><h2>Technology</h2><span></span></div>
            <?php if ($technologyRows): ?><div class="fdr-technology-list"><?php foreach ($technologyRows as $row): ?><article class="fdr-technology-item"><div class="fdr-technology-copy"><strong><?= e((string)($row[0] ?? '')); ?></strong><p><?= e((string)($row[1] ?? '')); ?></p></div><div class="fdr-technology-mark"><i class="fa-solid fa-gears"></i></div></article><?php endforeach; ?></div>
            <?php elseif (trim($legacySections['technology']) !== ''): ?><div class="fdr-legacy-content"><?= fdr_safe_content_html($legacySections['technology']); ?></div><?php else: ?><div class="fdr-legacy-content">Informasi teknologi belum dilengkapi. Tambahkan keterangan resmi melalui Admin → Konten Detail Ban.</div><?php endif; ?>
        </section>

        <?php if (trim($legacySections['other']) !== ''): ?><section class="fdr-detail-section"><div class="fdr-legacy-content"><?= fdr_safe_content_html($legacySections['other']); ?></div></section><?php endif; ?>
        <?php if (trim($legacySections['intro']) !== '' && trim(strip_tags((string)$legacySections['intro'])) !== trim(strip_tags((string)$heroDescription))): ?><section class="fdr-detail-section"><div class="fdr-legacy-content"><?= fdr_safe_content_html($legacySections['intro']); ?></div></section><?php endif; ?>
        <footer class="fdr-detail-footer"><span>Informasi produk dari PT Star Parts Motor.</span><div><a href="<?= e($buyUrl); ?>" class="fdr-action-buy" target="_blank" rel="noopener noreferrer">Buy Now <i class="fa-solid fa-cart-shopping"></i></a><a href="<?= e(site_url('contact.php')); ?>" class="fdr-action-secondary"><i class="fa-solid fa-comments"></i> Hubungi Kami</a></div></footer>
    </div>
</section>
<?php elseif ($isOsramProduct): ?>
<section class="osram-detail-shell">
    <div class="container">
        <div class="osram-detail-breadcrumb"><a href="<?= e(site_url()); ?>">Beranda</a><span>/</span><a href="<?= e(site_url('products.php')); ?>">Produk</a><span>/</span><a href="<?= e(site_url('products.php?category=osram')); ?>">OSRAM</a><span>/</span><span><?= e($productName); ?></span></div>
        <div class="osram-detail-grid">
            <div class="osram-detail-media">
                <div class="osram-detail-orbit osram-orbit-one"></div><div class="osram-detail-orbit osram-orbit-two"></div>
                <?php if ($pageImage !== ''): ?><img src="<?= e(media_url($pageImage)); ?>" alt="<?= e(trim((string)($product['image_alt'] ?? '')) !== '' ? (string)$product['image_alt'] : $productName); ?>" loading="eager" fetchpriority="high" decoding="async"><?php else: ?><i class="fa-solid fa-lightbulb osram-detail-placeholder-icon"></i><?php endif; ?>
                <span class="osram-media-caption">OSRAM <small>AUTOMOTIVE LIGHTING</small></span>
            </div>
            <div class="osram-detail-copy">
                <span class="osram-eyebrow"><span></span> OSRAM AUTOMOTIVE LIGHTING</span>
                <p class="osram-model-label">MODEL / PRODUCT CODE</p>
                <h1><?= e($productName); ?></h1>
                <?php if (trim((string)($product['short_description'] ?? '')) !== ''): ?><p class="osram-detail-short-description"><?= e(fdr_hide_price_amounts((string)$product['short_description'])); ?></p><?php else: ?><p class="osram-detail-placeholder">Deskripsi produk belum dilengkapi. Anda dapat menambahkannya melalui Admin &rarr; Produk.</p><?php endif; ?>
                <div class="osram-detail-status"><span><i class="fa-solid fa-pen-to-square"></i> Informasi produk dapat diperbarui melalui admin</span></div>
                <?php if (trim((string)($product['description'] ?? '')) !== ''): ?><section class="osram-detail-info"><h2>Deskripsi Produk</h2><div><?= fdr_safe_content_html((string)$product['description']); ?></div></section><?php endif; ?>
                <?php if ($specs): ?><section class="osram-detail-info"><h2>Spesifikasi</h2><div class="osram-spec-grid"><?php foreach ($specs as [$a, $b]): ?><div><span><?= e($a); ?></span><strong><?= e($b); ?></strong></div><?php endforeach; ?></div></section><?php else: ?><section class="osram-detail-info"><h2>Spesifikasi</h2><p class="osram-detail-placeholder">Spesifikasi belum diisi. Lengkapi melalui Admin &rarr; Produk.</p></section><?php endif; ?>
                <div class="osram-detail-actions">
                    <?php if ($buyUrlConfigured !== ''): ?><a class="osram-buy-button" href="<?= e($buyUrlConfigured); ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-cart-shopping"></i> Buy Now</a><?php endif; ?>
                    <a class="osram-contact-button" href="<?= e(site_url('contact.php')); ?>"><i class="fa-solid fa-comments"></i> Hubungi Kami</a>
                </div>
                <p class="osram-detail-footnote">Deskripsi, spesifikasi, dan tautan pembelian dapat diatur melalui panel admin.</p>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<section class="page-hero"><div class="container"><div class="page-hero-content" data-aos="fade-up"><div class="breadcrumb"><a href="<?= e(site_url()); ?>">Beranda</a><span><i class="fa-solid fa-chevron-right"></i></span><a href="<?= e(site_url('products.php')); ?>">Produk</a><span><i class="fa-solid fa-chevron-right"></i></span><span class="current"><?= e($productName); ?></span></div><span class="eyebrow">PRODUCT DETAIL</span><h1><?= e($productName); ?></h1><p><?= e($pageDescription); ?></p></div></div></section>
<section class="section section-white"><div class="container"><div class="product-detail-grid"><div class="product-detail-gallery"><div class="product-main-image"><?php if ($pageImage !== ''): ?><img src="<?= e(media_url($pageImage)); ?>" <?= media_image_attrs($pageImage, trim((string)($product['image_alt'] ?? '')) !== '' ? trim((string)$product['image_alt']) : $productName, null, 'eager', true, '(max-width: 767px) 100vw, 55vw'); ?>><?php else: ?><div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;font-size:50px"><i class="fa-solid fa-box-open"></i></div><?php endif; ?></div><?php if ($gallery): ?><div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:12px"><?php foreach ($gallery as $g): ?><div style="aspect-ratio:1;background:#f5f5f5;border-radius:12px;overflow:hidden"><img src="<?= e(media_url($g)); ?>" <?= media_image_attrs($g, $productName, null, 'lazy'); ?> style="width:100%;height:100%;object-fit:contain;padding:8px"></div><?php endforeach; ?></div><?php endif; ?></div>
<div class="product-detail-content"><?php if (!empty($product['category_name'])): ?><div class="product-detail-category"><?= e((string)$product['category_name']); ?></div><?php endif; ?><h2 class="product-detail-title"><?= e($productName); ?></h2><?php if (trim((string)($product['description'] ?? '')) !== ''): ?><div class="product-detail-description"><?= fdr_safe_content_html((string)$product['description']); ?></div><?php endif; ?><?php if ($specs): ?><div class="product-specifications"><h3>Spesifikasi Produk</h3><div class="product-specification-list"><?php foreach ($specs as [$a, $b]): ?><div class="product-specification-item"><span class="product-specification-label"><?= e($a); ?></span><span class="product-specification-value"><?= e($b); ?></span></div><?php endforeach; ?></div></div><?php endif; ?><div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:28px"><?php if ($buyUrlConfigured !== ''): ?><a class="btn btn-primary" href="<?= e($buyUrlConfigured); ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-cart-shopping"></i> Buy Now</a><?php endif; ?><a class="btn btn-outline" href="<?= e(site_url('contact.php')); ?>"><i class="fa-solid fa-comments"></i> Hubungi Kami</a></div></div></div></div></section>
<?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
