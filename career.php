<?php
// =========================================================
// FILE   : career.php
// LOKASI : starparts/career.php
// Halaman Karir PT Star Parts Motor
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
    'career_page_title',
    'Karir | PT Star Parts Motor'
);

$pageDescription = get_setting(
    'career_page_description',
    get_setting(
        'site_description',
        'Bergabung dan berkembang bersama PT Star Parts Motor.'
    )
);

$pageImage = get_setting(
    'career_image',
    get_setting(
        'site_logo',
        ''
    )
);

$pageCanonicalUrl = site_url('career.php');
$bodyClass = 'career-page';


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

$globalCtaPrefix = trim((string) get_setting('cta_title_prefix', 'Siap Menjadi'));
$globalCtaHighlight = trim((string) get_setting('cta_title_highlight', 'Mitra Star Parts Motor?'));
$globalCtaText = trim((string) get_setting('cta_text', 'Mari bangun kerja sama jangka panjang dan tumbuh bersama dalam industri otomotif.'));
$globalCtaWhatsappText = trim((string) get_setting('cta_whatsapp_text', 'Hubungi via WhatsApp'));
$globalCtaButtonText = trim((string) get_setting('cta_button_text', 'Hubungi Kami'));
$globalCtaButtonUrl = trim((string) get_setting('cta_button_url', 'contact.php'));

$siteTagline = trim(
    (string) get_setting(
        'site_tagline',
        'Operational Excellence'
    )
);


/*
|--------------------------------------------------------------------------
| OPTIONAL CMS CONTENT
|--------------------------------------------------------------------------
|
| Jika admin suatu saat membuat halaman dengan slug "career" dan status
| published, konten tersebut akan dipakai. Jika belum ada, halaman memakai
| konten bawaan di bawah agar link Karir tidak menghasilkan 404.
|--------------------------------------------------------------------------
*/

$careerPage = null;

try {

    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT *
         FROM pages
         WHERE slug = ?
         AND status = 'published'
         LIMIT 1"
    );

    $stmt->execute([
        'career'
    ]);

    $careerPage = $stmt->fetch();

    if (is_array($careerPage)) {
        if (trim((string)($careerPage['meta_title'] ?? '')) !== '') $pageTitle = (string)$careerPage['meta_title'];
        if (trim((string)($careerPage['meta_description'] ?? '')) !== '') $pageDescription = (string)$careerPage['meta_description'];
        if (trim((string)($careerPage['featured_image'] ?? '')) !== '') $pageImage = (string)$careerPage['featured_image'];
    }


} catch (Throwable $e) {

    $careerPage = null;
}


$careerTitle = trim(
    (string) (
        $careerPage['title']
        ?? 'Tumbuh Bersama Star Parts Motor'
    )
);

$careerContent = trim(
    (string) (
        $careerPage['content']
        ?? ''
    )
);

$careerImage = trim(
    (string) (
        $careerPage['featured_image']
        ?? get_setting(
            'career_image',
            ''
        )
    )
);

$careerImageUrl =
    $careerImage !== ''
        ? media_url($careerImage)
        : '';

/*
|--------------------------------------------------------------------------
| CAREER JOB LISTINGS
|--------------------------------------------------------------------------
*/

$careerJobs = [];

try {
    $jobsStmt = $pdo->prepare(
        "SELECT id, title, slug, poster, excerpt, description,
                application_url, application_label, published_at
         FROM career_jobs
         WHERE status = 'published'
         ORDER BY sort_order ASC, COALESCE(published_at, created_at) DESC, id DESC"
    );
    $jobsStmt->execute();
    $careerJobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $careerJobs = [];
}


/*
|--------------------------------------------------------------------------
| CONTACT / WHATSAPP
|--------------------------------------------------------------------------
*/

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
            'Halo Star Parts Motor, saya tertarik untuk mendapatkan informasi mengenai peluang karir di perusahaan.'
        );
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

    <section class="page-hero">

        <div class="container">

            <div
                class="page-hero-content"
                data-aos="fade-up"
            >

                <div class="breadcrumb">

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

                    <span class="current">
                        Karir
                    </span>

                </div>

                <span class="eyebrow">
                    KARIR
                </span>

                <h1 class="page-hero-title">
                    Kesempatan untuk Tumbuh
                </h1>

                <p class="page-hero-description">
                    Bangun perjalanan profesional Anda bersama
                    <?= e($siteName); ?> dan jadilah bagian dari
                    lingkungan kerja yang berorientasi pada
                    pertumbuhan serta operational excellence.
                </p>

            </div>

        </div>

    </section>


    <!-- =======================================================
         CAREER INTRO
    ======================================================== -->

    <section class="section section-white">

        <div class="container">

            <div
                class="section-header"
                data-aos="fade-up"
            >

                <span class="section-kicker">
                    BERGABUNG BERSAMA KAMI
                </span>

                <h2 class="section-title">
                    <?= e($careerTitle); ?>
                </h2>

                <p class="section-description">
                    <?= e($siteTagline); ?>
                </p>

            </div>


            <div
                class="features-grid"
                data-aos="fade-up"
                data-aos-delay="100"
            >

                <article class="feature-card">

                    <div class="feature-icon">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                    </div>

                    <h3 class="feature-title">
                        Ruang untuk Bertumbuh
                    </h3>

                    <p class="feature-description">
                        Kembangkan kemampuan dan pengalaman Anda
                        melalui perjalanan profesional yang terus
                        berkembang.
                    </p>

                </article>


                <article class="feature-card">

                    <div class="feature-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <h3 class="feature-title">
                        Kolaborasi Tim
                    </h3>

                    <p class="feature-description">
                        Berkontribusi bersama tim untuk mendukung
                        kebutuhan operasional dan pertumbuhan bisnis.
                    </p>

                </article>


                <article class="feature-card">

                    <div class="feature-icon">
                        <i class="fa-solid fa-bullseye"></i>
                    </div>

                    <h3 class="feature-title">
                        Berorientasi pada Hasil
                    </h3>

                    <p class="feature-description">
                        Bangun kebiasaan kerja yang disiplin,
                        terukur, dan berfokus pada pencapaian terbaik.
                    </p>

                </article>


                <article class="feature-card">

                    <div class="feature-icon">
                        <i class="fa-solid fa-handshake"></i>
                    </div>

                    <h3 class="feature-title">
                        Nilai Kebersamaan
                    </h3>

                    <p class="feature-description">
                        Tumbuhkan hubungan kerja yang profesional,
                        saling menghargai, dan berkelanjutan.
                    </p>

                </article>

            </div>

        </div>

    </section>


    <!-- =======================================================
         OPTIONAL CMS CONTENT
    ======================================================== -->

    <?php if ($careerContent !== ''): ?>

        <section class="section section-light">

            <div class="container">

                <?php if ($careerImageUrl !== ''): ?>

                    <div
                        class="product-main-image"
                        data-aos="fade-up"
                        style="margin-bottom: 30px;"
                    >

                        <img
                            src="<?= e(
                                $careerImageUrl
                            ); ?>"
                            <?= media_image_attrs($careerImage, $careerTitle, null, 'lazy', false, '(max-width: 767px) 100vw, 50vw'); ?>
                        >

                    </div>

                <?php endif; ?>

                <div
                    data-aos="fade-up"
                    style="max-width: 850px; margin: 0 auto;"
                >

                    <?= $careerContent; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>

    <?php if (!empty($careerJobs)): ?>
        <section class="section section-light career-vacancies">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <span class="section-kicker">LOWONGAN TERSEDIA</span>
                    <h2 class="section-title">Posisi yang Sedang Dibuka</h2>
                </div>

                <div class="career-job-grid">
                    <?php foreach ($careerJobs as $job): ?>
                        <?php
                        $jobTitle = trim((string) ($job['title'] ?? ''));
                        $jobExcerpt = trim((string) ($job['excerpt'] ?? ''));
                        $jobDescription = trim((string) ($job['description'] ?? ''));
                        $jobPoster = trim((string) ($job['poster'] ?? ''));
                        $jobPosterUrl = $jobPoster !== '' ? media_url($jobPoster) : '';
                        $applicationUrl = trim((string) ($job['application_url'] ?? ''));
                        $applicationLabel = trim((string) ($job['application_label'] ?? '')) ?: 'Lamar Sekarang';
                        ?>
                        <article class="career-job-card" data-aos="fade-up" tabindex="0" role="button" aria-haspopup="dialog" aria-label="Lihat detail lowongan <?= e($jobTitle); ?>">
                            <?php if ($jobPosterUrl !== ''): ?>
                                <div class="career-job-card-poster">
                                    <img src="<?= e($jobPosterUrl); ?>" alt="Poster lowongan <?= e($jobTitle); ?>" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="career-job-card-poster career-job-card-poster-empty" aria-hidden="true">
                                    <i class="fa-solid fa-briefcase"></i>
                                </div>
                            <?php endif; ?>
                            <div class="career-job-body">
                                <span class="career-job-kicker">LOWONGAN PEKERJAAN</span>
                                <h3><?= e($jobTitle); ?></h3>
                                <?php if ($jobExcerpt !== ''): ?>
                                    <p class="career-job-excerpt"><?= e($jobExcerpt); ?></p>
                                <?php endif; ?>
                                <span class="career-job-more">
                                    Lihat Selengkapnya
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </span>
                            </div>

                            <template class="career-job-detail-template">
                                <?php if ($jobPosterUrl !== ''): ?>
                                    <div class="career-job-poster">
                                        <img src="<?= e($jobPosterUrl); ?>" alt="<?= e($jobTitle); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="career-job-detail-content">
                                    <span class="career-job-kicker">LOWONGAN PEKERJAAN</span>
                                    <h2 id="careerJobModalTitle"><?= e($jobTitle); ?></h2>
                                    <?php if ($jobExcerpt !== ''): ?>
                                        <p class="career-job-detail-excerpt"><?= e($jobExcerpt); ?></p>
                                    <?php endif; ?>
                                    <?php if ($jobDescription !== ''): ?>
                                        <div class="career-job-description rich-content"><?= $jobDescription; ?></div>
                                    <?php endif; ?>
                                    <?php if ($applicationUrl !== ''): ?>
                                        <a class="btn btn-primary career-apply-button"
                                           href="<?= e($applicationUrl); ?>"
                                           target="_blank"
                                           rel="noopener noreferrer">
                                            <?= e($applicationLabel); ?>
                                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </template>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="career-job-modal" id="careerJobModal" role="dialog" aria-modal="true" aria-labelledby="careerJobModalTitle" hidden>
                    <div class="career-job-modal-backdrop" data-close-career-modal></div>
                    <div class="career-job-modal-dialog">
                        <button class="career-job-modal-close" type="button" aria-label="Tutup detail lowongan" data-close-career-modal>
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <div class="career-job-modal-content" id="careerJobModalContent"></div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>


    <!-- =======================================================
         CAREER CTA
    ======================================================== -->

    <section class="cta-section">

        <div class="container">

            <div class="cta-inner">

                <div
                    class="cta-content"
                    data-aos="fade-right"
                >

                    <h2 class="cta-title">
                        <?= e($globalCtaPrefix); ?>
                        <strong>
                            <?= e($globalCtaHighlight); ?>
                        </strong>
                    </h2>

                    <p class="cta-text">
                        <?= e($globalCtaText); ?>
                    </p>

                </div>


                <div
                    class="cta-actions"
                    data-aos="fade-left"
                >

                    <?php if ($whatsappNumber !== ''): ?>

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


                    <?php if ($email !== ''): ?>

                        <a
                            href="mailto:<?= e(
                                $email
                            ); ?>"
                            class="btn btn-dark"
                        >

                            Kirim Email

                            <i
                                class="fa-solid fa-arrow-right"
                            ></i>

                        </a>

                    <?php else: ?>

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

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('careerJobModal');
    var modalContent = document.getElementById('careerJobModalContent');
    var lastFocusedCard = null;
    if (!modal || !modalContent) return;

    function openCareerModal(card) {
        var template = card.querySelector('.career-job-detail-template');
        if (!template) return;
        lastFocusedCard = card;
        modalContent.replaceChildren(template.content.cloneNode(true));
        modal.hidden = false;
        document.body.style.overflow = 'hidden';

        if (window.innerWidth >= 768 && window.matchMedia('(orientation: landscape)').matches) {
            var dialog = modal.querySelector('.career-job-modal-dialog');
            var detailContent = modalContent.querySelector('.career-job-detail-content');
            var poster = modalContent.querySelector('.career-job-poster');
            var modalHeight = Math.min(720, window.innerHeight - 48);

            if (dialog) {
                dialog.style.height = modalHeight + 'px';
                dialog.style.maxHeight = 'calc(100vh - 48px)';
                dialog.style.overflow = 'hidden';
            }
            modalContent.style.height = '100%';
            if (poster) {
                poster.style.position = 'relative';
                poster.style.top = 'auto';
                poster.style.height = '100%';
                poster.style.minHeight = '420px';
            }
            if (detailContent) {
                detailContent.style.height = '100%';
                detailContent.style.overflowY = 'auto';
                detailContent.style.overscrollBehavior = 'contain';
            }
        }

        var closeButton = modal.querySelector('.career-job-modal-close');
        if (closeButton) closeButton.focus();
    }

    function closeCareerModal() {
        modal.hidden = true;
        modalContent.replaceChildren();
        document.body.style.overflow = '';
        var dialog = modal.querySelector('.career-job-modal-dialog');
        if (dialog) {
            dialog.style.removeProperty('height');
            dialog.style.removeProperty('max-height');
            dialog.style.removeProperty('overflow');
        }
        modalContent.style.removeProperty('height');
        if (lastFocusedCard) lastFocusedCard.focus();
        lastFocusedCard = null;
    }

    document.querySelectorAll('.career-job-card').forEach(function (card) {
        card.addEventListener('click', function () {
            openCareerModal(card);
        });
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openCareerModal(card);
            }
        });
    });

    modal.querySelectorAll('[data-close-career-modal]').forEach(function (element) {
        element.addEventListener('click', closeCareerModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) closeCareerModal();
    });
});
</script>

<?php
// =========================================================
// FOOTER
// =========================================================

require_once __DIR__ . '/includes/footer.php';
?>
