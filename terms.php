<?php
// =========================================================
// FILE   : terms.php
// LOKASI : starparts/terms.php
// Halaman Terms & Conditions PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = get_setting('terms_page_title', 'Terms & Conditions | ' . get_setting('site_name', 'PT Star Parts Motor'));
$pageDescription = get_setting('terms_page_description', 'Syarat dan ketentuan penggunaan website PT Star Parts Motor.');
$pageImage = get_setting('site_logo', '');
$pageCanonicalUrl = site_url('terms.php');
$bodyClass = 'terms-page';

$siteName = trim((string) get_setting('site_name', 'PT Star Parts Motor'));
$policyContent = '';

try {
    $stmt = $pdo->prepare("\n        SELECT title, excerpt, content, featured_image, meta_title, meta_description\n        FROM pages\n        WHERE slug = ?\n          AND status = 'published'\n        LIMIT 1\n    ");
    $stmt->execute(['terms']);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($page)) {
        if (trim((string)($page['meta_title'] ?? '')) !== '') $pageTitle = (string)$page['meta_title'];
        if (trim((string)($page['meta_description'] ?? '')) !== '') $pageDescription = (string)$page['meta_description'];
        if (trim((string)($page['featured_image'] ?? '')) !== '') $pageImage = (string)$page['featured_image'];
    }

    if ($page) {
        $pageTitle = trim((string) ($page['title'] ?? '')) ?: $pageTitle;
        $policyContent = trim((string) ($page['content'] ?? ''));
    }
} catch (Throwable $e) {
    $policyContent = '';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main id="main-content">

    <section class="page-hero">
        <div class="container">
            <div class="page-hero-content" data-aos="fade-up">
                <div class="breadcrumb">
                    <a href="<?= e(base_url('index.php')); ?>">Beranda</a>
                    <span><i class="fa-solid fa-chevron-right"></i></span>
                    <span class="current">Terms &amp; Conditions</span>
                </div>

                <span class="eyebrow">TERMS &amp; CONDITIONS</span>

                <h1><?= e($pageTitle); ?></h1>

                <p>
                    Syarat dan ketentuan penggunaan website serta informasi umum
                    mengenai interaksi pengunjung dengan layanan digital kami.
                </p>
            </div>
        </div>
    </section>

    <section class="section section-white">
        <div class="container">
            <div class="section-header section-header-left">
                <span class="section-kicker">TERMS &amp; CONDITIONS</span>
                <h2 class="section-title">Ketentuan Penggunaan Website</h2>
                <p class="section-description">
                    Halaman ini menjelaskan ketentuan umum yang berlaku ketika pengunjung
                    menggunakan website <?= e($siteName); ?>.
                </p>
            </div>

            <div class="legal-content">
                <?php if ($policyContent !== ''): ?>
                    <?= $policyContent; ?>
                <?php else: ?>
                    <h3>Penggunaan Website</h3>
                    <p>
                        Pengunjung dapat menggunakan informasi yang tersedia di website untuk kebutuhan
                        informasi produk, distribusi, kerja sama, dan komunikasi yang sah.
                    </p>

                    <h3>Ketentuan Konten</h3>
                    <p>
                        Konten website disediakan sebagai informasi umum dan dapat diperbarui dari waktu
                        ke waktu untuk menjaga ketepatan dan relevansinya.
                    </p>

                    <h3>Penggunaan yang Wajar</h3>
                    <p>
                        Penggunaan website harus dilakukan secara wajar dan tidak mengganggu keamanan,
                        ketersediaan, atau fungsi layanan digital Star Parts Motor.
                    </p>

                    <h3>Hak atas Konten</h3>
                    <p>
                        Materi, merek, teks, foto, dan elemen visual pada website dapat memiliki hak
                        yang berlaku dan tidak boleh digunakan secara tidak semestinya.
                    </p>

                    <h3>Perubahan Ketentuan</h3>
                    <p>
                        Ketentuan dapat diperbarui dari waktu ke waktu sesuai perubahan layanan, proses
                        internal, atau kebutuhan pengelolaan website.
                    </p>

                    <h3>Hubungi Kami</h3>
                    <p>
                        Untuk pertanyaan mengenai ketentuan penggunaan ini, silakan hubungi
                        <?= e($siteName); ?> melalui halaman kontak yang tersedia di website.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
