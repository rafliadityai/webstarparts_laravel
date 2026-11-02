<?php
// =========================================================
// FILE   : privacy.php
// LOKASI : starparts/privacy.php
// Halaman Privacy Policy PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = get_setting('privacy_page_title', 'Privacy Policy | ' . get_setting('site_name', 'PT Star Parts Motor'));
$pageDescription = get_setting('privacy_page_description', 'Kebijakan privasi dan penggunaan data pada website PT Star Parts Motor.');
$pageImage = get_setting('site_logo', '');
$pageCanonicalUrl = site_url('privacy.php');
$bodyClass = 'privacy-page';

$siteName = trim((string) get_setting('site_name', 'PT Star Parts Motor'));
$policyContent = '';

try {
    $stmt = $pdo->prepare("\n        SELECT title, excerpt, content, featured_image, meta_title, meta_description\n        FROM pages\n        WHERE slug = ?\n          AND status = 'published'\n        LIMIT 1\n    ");
    $stmt->execute(['privacy']);
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
                    <span class="current">Privacy Policy</span>
                </div>

                <span class="eyebrow">PRIVASI</span>

                <h1><?= e($pageTitle); ?></h1>

                <p>
                    Informasi mengenai cara Star Parts Motor menggunakan,
                    menjaga, dan menangani data yang diberikan melalui website.
                </p>
            </div>
        </div>
    </section>

    <section class="section section-white">
        <div class="container">
            <div class="section-header section-header-left">
                <span class="section-kicker">PRIVACY POLICY</span>
                <h2 class="section-title">Perlindungan Informasi Anda</h2>
                <p class="section-description">
                    Kebijakan ini menjelaskan prinsip umum pengelolaan informasi
                    yang diberikan pengunjung ketika menggunakan website <?= e($siteName); ?>.
                </p>
            </div>

            <div class="legal-content">
                <?php if ($policyContent !== ''): ?>
                    <?= $policyContent; ?>
                <?php else: ?>
                    <h3>Informasi yang Kami Terima</h3>
                    <p>
                        Informasi dapat diberikan secara sukarela ketika Anda menghubungi
                        kami melalui formulir kontak, seperti nama, perusahaan, email,
                        nomor telepon, subjek, dan isi pesan.
                    </p>

                    <h3>Penggunaan Informasi</h3>
                    <p>
                        Informasi tersebut digunakan untuk menanggapi pertanyaan,
                        memberikan informasi produk atau layanan, menindaklanjuti
                        kebutuhan komunikasi, dan menjalankan layanan website.
                    </p>

                    <h3>Penyimpanan dan Perlindungan</h3>
                    <p>
                        Kami berupaya menjaga informasi yang diterima melalui website
                        dengan langkah teknis dan operasional yang wajar. Akses terhadap
                        data internal dibatasi sesuai kebutuhan pengelolaan website.
                    </p>

                    <h3>Berbagi Informasi</h3>
                    <p>
                        Informasi kontak tidak digunakan untuk tujuan yang tidak berkaitan
                        dengan kebutuhan komunikasi website. Informasi dapat diberikan
                        apabila diperlukan untuk memenuhi kewajiban hukum atau kebutuhan
                        operasional yang sah.
                    </p>

                    <h3>Perubahan Kebijakan</h3>
                    <p>
                        Isi kebijakan ini dapat diperbarui dari waktu ke waktu untuk
                        menyesuaikan perubahan layanan, proses internal, atau kebutuhan
                        pengelolaan website.
                    </p>

                    <h3>Hubungi Kami</h3>
                    <p>
                        Untuk pertanyaan mengenai kebijakan privasi ini, silakan hubungi
                        <?= e($siteName); ?> melalui halaman kontak yang tersedia di website.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
