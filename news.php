<?php
// =========================================================
// FILE   : news.php
// LOKASI : starparts/news.php
// Halaman Berita & Aktivitas PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PAGE META
|--------------------------------------------------------------------------
*/

$pageTitle = get_setting('news_page_title', 'Berita & Aktivitas | PT Star Parts Motor');
$pageDescription = get_setting('news_page_description', get_setting('meta_description', 'Berita, informasi produk, aktivitas dan perkembangan terbaru PT Star Parts Motor.'));
$pageImage = get_setting('news_og_image', get_setting('site_logo', ''));
$pageCanonicalUrl = site_url('news.php');
$bodyClass = 'news-page';


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

$siteDescription = trim(
    (string) get_setting(
        'site_description',
        'Distributor dan partner distribusi produk otomotif berkualitas.'
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
            get_whatsapp_message()
        );
}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$search = trim(
    (string) (
        $_GET['q']
        ?? ''
    )
);


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 9;

$currentPage = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);

if (
    $currentPage === false ||
    $currentPage === null
) {
    $currentPage = 1;
}


/*
|--------------------------------------------------------------------------
| DATABASE STRUCTURE
|--------------------------------------------------------------------------
*/

$postsTable = 'posts';

$tableExists = false;

$postColumns = [];
$postColumnNames = [];

try {

    $tableCheck = $pdo->query(
        "SHOW TABLES LIKE " .
        $pdo->quote($postsTable)
    );

    $tableExists =
        $tableCheck &&
        (bool) $tableCheck->fetchColumn();

    if ($tableExists) {

        $columnStmt = $pdo->query(
            "SHOW COLUMNS FROM `{$postsTable}`"
        );

        $postColumns =
            $columnStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        foreach (
            $postColumns
            as $postColumn
        ) {

            if (
                !empty(
                    $postColumn['Field']
                )
            ) {

                $postColumnNames[] =
                    (string) $postColumn['Field'];
            }
        }
    }

} catch (Throwable $e) {

    $tableExists = false;

    error_log(
        'Star Parts news table check error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| COLUMN FINDER
|--------------------------------------------------------------------------
*/

function starparts_news_column(
    array $columns,
    array $candidates
): ?string {

    foreach (
        $candidates
        as $candidate
    ) {

        if (
            in_array(
                $candidate,
                $columns,
                true
            )
        ) {

            return $candidate;
        }
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| COLUMN MAPPING
|--------------------------------------------------------------------------
*/

$postIdColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'id',
            'post_id'
        ]
    );

$postTitleColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'title',
            'judul',
            'name'
        ]
    );

$postSlugColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'slug',
            'post_slug'
        ]
    );

$postExcerptColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'excerpt',
            'summary',
            'short_description'
        ]
    );

$postContentColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'content',
            'body',
            'article',
            'description'
        ]
    );

$postImageColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'featured_image',
            'image',
            'thumbnail',
            'featured_photo'
        ]
    );

$postAuthorColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'author_name',
            'author',
            'writer'
        ]
    );

$postPublishedColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'is_published',
            'published',
            'status'
        ]
    );

$postFeaturedColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'is_featured',
            'featured'
        ]
    );

$postPublishedAtColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'published_at',
            'publication_date',
            'publish_at'
        ]
    );

$postCreatedAtColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'created_at',
            'created',
            'date_created'
        ]
    );

$postUpdatedAtColumn =
    starparts_news_column(
        $postColumnNames,
        [
            'updated_at',
            'updated'
        ]
    );


/*
|--------------------------------------------------------------------------
| NEWS DATA
|--------------------------------------------------------------------------
*/

$posts = [];

$totalPosts = 0;

$totalPages = 1;

$queryError = '';


if (
    $tableExists &&
    $postIdColumn !== null &&
    $postTitleColumn !== null &&
    $postSlugColumn !== null
) {

    /*
    |--------------------------------------------------------------------------
    | SELECT
    |--------------------------------------------------------------------------
    */

    $selectParts = [
        "`{$postIdColumn}` AS post_id",
        "`{$postTitleColumn}` AS post_title",
        "`{$postSlugColumn}` AS post_slug"
    ];


    if (
        $postExcerptColumn !== null
    ) {

        $selectParts[] =
            "`{$postExcerptColumn}` AS post_excerpt";

    } else {

        $selectParts[] =
            "'' AS post_excerpt";
    }


    if (
        $postContentColumn !== null
    ) {

        $selectParts[] =
            "`{$postContentColumn}` AS post_content";

    } else {

        $selectParts[] =
            "'' AS post_content";
    }


    if (
        $postImageColumn !== null
    ) {

        $selectParts[] =
            "`{$postImageColumn}` AS post_image";

    } else {

        $selectParts[] =
            "'' AS post_image";
    }


    if (
        $postAuthorColumn !== null
    ) {

        $selectParts[] =
            "`{$postAuthorColumn}` AS post_author";

    } else {

        $selectParts[] =
            "'' AS post_author";
    }


    if (
        $postFeaturedColumn !== null
    ) {

        $selectParts[] =
            "`{$postFeaturedColumn}` AS post_featured";

    } else {

        $selectParts[] =
            "0 AS post_featured";
    }


    if (
        $postPublishedAtColumn !== null
    ) {

        $selectParts[] =
            "`{$postPublishedAtColumn}` AS post_published_at";

    } else {

        $selectParts[] =
            "NULL AS post_published_at";
    }


    if (
        $postCreatedAtColumn !== null
    ) {

        $selectParts[] =
            "`{$postCreatedAtColumn}` AS post_created_at";

    } else {

        $selectParts[] =
            "NULL AS post_created_at";
    }


    /*
    |--------------------------------------------------------------------------
    | WHERE
    |--------------------------------------------------------------------------
    */

    $where = [];

    $params = [];


    /*
     * Hanya berita yang sudah dipublikasikan.
     */
    if (
        $postPublishedColumn !== null
    ) {

        if ($postPublishedColumn === 'status') {

            $where[] =
                "`{$postPublishedColumn}` = :published_status";

            $params[':published_status'] =
                'published';

        } else {

            $where[] =
                "`{$postPublishedColumn}` = 1";
        }
    }


    /*
     * SEARCH
     */
    if (
        $search !== ''
    ) {

        $searchParts = [
            "`{$postTitleColumn}` LIKE :search"
        ];

        if (
            $postExcerptColumn !== null
        ) {

            $searchParts[] =
                "`{$postExcerptColumn}` LIKE :search";
        }

        if (
            $postContentColumn !== null
        ) {

            $searchParts[] =
                "`{$postContentColumn}` LIKE :search";
        }

        $where[] =
            '(' .
            implode(
                ' OR ',
                $searchParts
            ) .
            ')';

        $params[':search'] =
            '%' .
            $search .
            '%';
    }


    $whereSql =
        !empty($where)
            ? 'WHERE ' .
              implode(
                  ' AND ',
                  $where
              )
            : '';


    /*
    |--------------------------------------------------------------------------
    | COUNT
    |--------------------------------------------------------------------------
    */

    try {

        $countSql =
            "SELECT COUNT(*)
             FROM `{$postsTable}`
             {$whereSql}";

        $countStmt =
            $pdo->prepare(
                $countSql
            );

        $countStmt->execute(
            $params
        );

        $totalPosts =
            (int) $countStmt->fetchColumn();

    } catch (Throwable $e) {

        $totalPosts = 0;

        $queryError =
            'Data berita belum dapat dimuat.';

        error_log(
            'Star Parts news count error: ' .
            $e->getMessage()
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PAGINATION CALCULATION
    |--------------------------------------------------------------------------
    */

    $totalPages =
        max(
            1,
            (int) ceil(
                $totalPosts /
                $perPage
            )
        );


    if (
        $currentPage > $totalPages
    ) {

        $currentPage =
            $totalPages;
    }


    $offset =
        (
            $currentPage - 1
        ) *
        $perPage;


    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    |
    | Gunakan Featured hanya bila kolom tersebut benar-benar ada.
    | Schema Starparts saat ini tidak mempunyai kolom Featured pada posts.
    |--------------------------------------------------------------------------
    */

    $orderParts = [];

    if (
        $postFeaturedColumn !== null
    ) {

        $orderParts[] =
            "CASE
                WHEN `{$postFeaturedColumn}` = 1
                THEN 0
                ELSE 1
            END";
    }

    if (
        $postPublishedAtColumn !== null
    ) {

        $orderParts[] =
            "`{$postPublishedAtColumn}` DESC";
    }

    if (
        $postCreatedAtColumn !== null
    ) {

        $orderParts[] =
            "`{$postCreatedAtColumn}` DESC";
    }

    $orderParts[] =
        "`{$postIdColumn}` DESC";

    $orderSql =
        "ORDER BY " .
        implode(
            ",\n                ",
            $orderParts
        );



    /*
    |--------------------------------------------------------------------------
    | LIST QUERY
    |--------------------------------------------------------------------------
    */

    try {

        $listSql =
            "SELECT " .
            implode(
                ', ',
                $selectParts
            ) .
            "
             FROM `{$postsTable}`
             {$whereSql}
             {$orderSql}
             LIMIT :limit OFFSET :offset";


        $listStmt =
            $pdo->prepare(
                $listSql
            );

        foreach (
            $params
            as $paramKey => $paramValue
        ) {

            $listStmt->bindValue(
                $paramKey,
                $paramValue,
                PDO::PARAM_STR
            );
        }


        $listStmt->bindValue(
            ':limit',
            $perPage,
            PDO::PARAM_INT
        );


        $listStmt->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );


        $listStmt->execute();


        $posts =
            $listStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    } catch (Throwable $e) {

        $posts = [];

        if (
            $queryError === ''
        ) {

            $queryError =
                'Data berita belum dapat dimuat.';
        }

        error_log(
            'Star Parts news list error: ' .
            $e->getMessage()
        );
    }

} else {

    $queryError =
        'Tabel berita belum tersedia atau struktur database belum lengkap.';
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function starparts_news_date(
    mixed $value
): string {

    if (
        empty($value)
    ) {

        return '-';
    }

    $timestamp =
        strtotime(
            (string) $value
        );

    if (
        $timestamp === false
    ) {

        return '-';
    }

    if (
        function_exists('format_date_id')
    ) {

        try {

            return (string) format_date_id(
                (string) $value
            );

        } catch (Throwable $e) {
            // lanjut format fallback
        }
    }

    return date(
        'd M Y',
        $timestamp
    );
}


function starparts_news_excerpt(
    mixed $excerpt,
    mixed $content,
    int $length = 150
): string {

    $text =
        trim(
            strip_tags(
                (string) $excerpt
            )
        );

    if (
        $text === ''
    ) {

        $text =
            trim(
                strip_tags(
                    (string) $content
                )
            );
    }

    if (
        $text === ''
    ) {

        return '';
    }

    if (
        function_exists('excerpt')
    ) {

        try {

            return (string) excerpt(
                $text,
                $length
            );

        } catch (Throwable $e) {
            // fallback
        }
    }

    if (
        mb_strlen($text) <= $length
    ) {

        return $text;
    }

    return
        mb_substr(
            $text,
            0,
            max(
                1,
                $length - 3
            )
        ) .
        '...';
}


/*
|--------------------------------------------------------------------------
| PAGINATION URL
|--------------------------------------------------------------------------
*/

function starparts_news_page_url(
    int $page,
    string $search = ''
): string {

    $params = [
        'page' => $page
    ];

    if (
        $search !== ''
    ) {

        $params['q'] =
            $search;
    }

    return base_url(
        'news.php?' .
        http_build_query(
            $params
        )
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
                        Berita
                    </span>

                </div>


                <span
                    class="eyebrow"
                >
                    NEWS & UPDATE
                </span>


                <h1>
                    Berita & Aktivitas
                </h1>


                <p>
                    Simak informasi, aktivitas dan perkembangan
                    terbaru dari Star Parts Motor.
                </p>

            </div>

        </div>

    </section>


    <!-- =======================================================
         NEWS LIST
    ======================================================== -->

    <section
        class="section section-white"
    >

        <div class="container">


            <!-- SEARCH -->

            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:15px;
                    flex-wrap:wrap;
                    margin-bottom:30px;
                "
                data-aos="fade-up"
            >

                <div>

                    <div
                        class="section-kicker"
                    >
                        BERITA TERBARU
                    </div>


                    <h2
                        style="
                            margin:5px 0 0;
                            font-family:var(--spm-font);
                            font-size:25px;
                            font-weight:800;
                        "
                    >
                        Update Star Parts Motor
                    </h2>

                </div>


                <form
                    method="GET"
                    action="<?= e(
                        base_url(
                            'news.php'
                        )
                    ); ?>"
                    style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                        width:min(100%,420px);
                    "
                >

                    <input
                        type="text"
                        name="q"
                        value="<?= e(
                            $search
                        ); ?>"
                        class="form-control"
                        placeholder="Cari berita..."
                        aria-label="Cari berita"
                    >


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i
                            class="fa-solid fa-search"
                        ></i>

                        Cari

                    </button>

                </form>

            </div>


            <?php if (
                $search !== ''
            ): ?>

                <div
                    style="
                        margin-bottom:22px;
                        padding:12px 15px;
                        border:1px solid rgba(217,0,0,.12);
                        border-radius:12px;
                        background:#fff8f8;
                        color:var(--spm-text-soft);
                        font-size:11px;
                    "
                >

                    Menampilkan hasil pencarian untuk:

                    <strong
                        style="color:var(--spm-red);"
                    >
                        <?= e(
                            $search
                        ); ?>
                    </strong>


                    <a
                        href="<?= e(
                            base_url(
                                'news.php'
                            )
                        ); ?>"
                        style="
                            float:right;
                            color:var(--spm-red);
                            font-weight:800;
                        "
                    >
                        Reset
                    </a>

                </div>

            <?php endif; ?>


            <?php if (
                $queryError !== ''
            ): ?>

                <div
                    class="empty-state"
                    data-aos="fade-up"
                >

                    <div
                        class="empty-state-icon"
                    >

                        <i
                            class="fa-solid fa-database"
                        ></i>

                    </div>


                    <h3>
                        Berita belum tersedia
                    </h3>


                    <p>
                        <?= e(
                            $queryError
                        ); ?>
                    </p>

                </div>


            <?php elseif (
                empty($posts)
            ): ?>


                <div
                    class="empty-state"
                    data-aos="fade-up"
                >

                    <div
                        class="empty-state-icon"
                    >

                        <i
                            class="fa-regular fa-newspaper"
                        ></i>

                    </div>


                    <?php if (
                        $search !== ''
                    ): ?>

                        <h3>
                            Berita tidak ditemukan
                        </h3>


                        <p>
                            Tidak ada berita yang sesuai
                            dengan pencarian Anda.
                        </p>


                        <a
                            href="<?= e(
                                base_url(
                                    'news.php'
                                )
                            ); ?>"
                            class="btn btn-outline"
                        >

                            <i
                                class="fa-solid fa-arrow-left"
                            ></i>

                            Tampilkan Semua Berita

                        </a>

                    <?php else: ?>

                        <h3>
                            Belum ada berita
                        </h3>


                        <p>
                            Berita dan aktivitas perusahaan
                            dapat ditambahkan melalui panel admin.
                        </p>

                    <?php endif; ?>

                </div>


            <?php else: ?>


                <div
                    class="posts-grid"
                >

                    <?php foreach (
                        $posts
                        as $index => $post
                    ): ?>

                        <?php

                        $postId =
                            (int) (
                                $post['post_id']
                                ?? 0
                            );

                        $postTitle =
                            trim(
                                (string) (
                                    $post['post_title']
                                    ?? ''
                                )
                            );

                        $postSlug =
                            trim(
                                (string) (
                                    $post['post_slug']
                                    ?? ''
                                )
                            );

                        $postExcerpt =
                            trim(
                                (string) (
                                    $post['post_excerpt']
                                    ?? ''
                                )
                            );

                        $postContent =
                            trim(
                                (string) (
                                    $post['post_content']
                                    ?? ''
                                )
                            );

                        $postImage =
                            trim(
                                (string) (
                                    $post['post_image']
                                    ?? ''
                                )
                            );

                        $postAuthor =
                            trim(
                                (string) (
                                    $post['post_author']
                                    ?? ''
                                )
                            );

                        $postFeatured =
                            (int) (
                                $post['post_featured']
                                ?? 0
                            );

                        $postPublishedAt =
                            $post['post_published_at']
                            ??
                            $post['post_created_at']
                            ??
                            '';

                        $postDate =
                            starparts_news_date(
                                $postPublishedAt
                            );

                        $postSummary =
                            starparts_news_excerpt(
                                $postExcerpt,
                                $postContent,
                                130
                            );

                        $detailUrl =
                            post_url($postSlug, $postId);

                        ?>

                        <article
                            class="post-card"
                            data-aos="fade-up"
                            data-aos-delay="<?= (int) (
                                min(
                                    $index,
                                    2
                                ) * 70
                            ); ?>"
                        >

                            <!-- IMAGE -->

                            <a
                                href="<?= e(
                                    $detailUrl
                                ); ?>"
                                class="post-image"
                                aria-label="<?= e(
                                    $postTitle
                                ); ?>"
                            >

                                <?php if (
                                    $postImage !== ''
                                ): ?>

                                    <img src="<?= e(media_url($postImage)); ?>" <?= media_image_attrs($postImage, $postTitle, null, 'lazy', false, '(max-width: 767px) 100vw, 33vw'); ?>>

                                <?php else: ?>

                                    <div
                                        style="
                                            width:100%;
                                            height:100%;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            color:#aaa;
                                            background:
                                                linear-gradient(
                                                    135deg,
                                                    #eeeeee,
                                                    #dcdcdc
                                                );
                                            font-size:34px;
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
                                    <?= $postFeatured === 1
                                        ? 'FEATURED'
                                        : 'NEWS'; ?>
                                </span>

                            </a>


                            <!-- CONTENT -->

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
                                        $postDate
                                    ); ?>

                                    <?php if (
                                        $postAuthor !== ''
                                    ): ?>

                                        <span
                                            style="margin:0 5px;"
                                        >
                                            •
                                        </span>

                                        <i
                                            class="fa-regular fa-user"
                                        ></i>

                                        <?= e(
                                            $postAuthor
                                        ); ?>

                                    <?php endif; ?>

                                </div>


                                <h3
                                    class="post-title"
                                >

                                    <a
                                        href="<?= e(
                                            $detailUrl
                                        ); ?>"
                                    >
                                        <?= e(
                                            $postTitle
                                        ); ?>
                                    </a>

                                </h3>


                                <?php if (
                                    $postSummary !== ''
                                ): ?>

                                    <p
                                        class="post-excerpt"
                                    >
                                        <?= e(
                                            $postSummary
                                        ); ?>
                                    </p>

                                <?php endif; ?>


                                <a
                                    href="<?= e(
                                        $detailUrl
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


                <!-- =================================================
                     PAGINATION
                ================================================== -->

                <?php if (
                    $totalPages > 1
                ): ?>

                    <div
                        style="
                            display:flex;
                            justify-content:center;
                            align-items:center;
                            flex-wrap:wrap;
                            gap:7px;
                            margin-top:38px;
                        "
                        aria-label="Pagination"
                    >

                        <?php if (
                            $currentPage > 1
                        ): ?>

                            <a
                                href="<?= e(
                                    starparts_news_page_url(
                                        $currentPage - 1,
                                        $search
                                    )
                                ); ?>"
                                class="btn btn-outline btn-sm"
                            >

                                <i
                                    class="fa-solid fa-chevron-left"
                                ></i>

                                Sebelumnya

                            </a>

                        <?php endif; ?>


                        <?php

                        $startPage =
                            max(
                                1,
                                $currentPage - 2
                            );

                        $endPage =
                            min(
                                $totalPages,
                                $currentPage + 2
                            );

                        ?>


                        <?php if (
                            $startPage > 1
                        ): ?>

                            <a
                                href="<?= e(
                                    starparts_news_page_url(
                                        1,
                                        $search
                                    )
                                ); ?>"
                                class="btn btn-outline btn-sm"
                            >
                                1
                            </a>


                            <?php if (
                                $startPage > 2
                            ): ?>

                                <span
                                    style="
                                        padding:0 4px;
                                        color:#999;
                                        font-size:12px;
                                    "
                                >
                                    ...
                                </span>

                            <?php endif; ?>

                        <?php endif; ?>


                        <?php for (
                            $page = $startPage;
                            $page <= $endPage;
                            $page++
                        ): ?>

                            <?php if (
                                $page === $currentPage
                            ): ?>

                                <span
                                    class="btn btn-primary btn-sm"
                                    aria-current="page"
                                >
                                    <?= $page; ?>
                                </span>

                            <?php else: ?>

                                <a
                                    href="<?= e(
                                        starparts_news_page_url(
                                            $page,
                                            $search
                                        )
                                    ); ?>"
                                    class="btn btn-outline btn-sm"
                                >
                                    <?= $page; ?>
                                </a>

                            <?php endif; ?>

                        <?php endfor; ?>


                        <?php if (
                            $endPage < $totalPages
                        ): ?>

                            <?php if (
                                $endPage < (
                                    $totalPages - 1
                                )
                            ): ?>

                                <span
                                    style="
                                        padding:0 4px;
                                        color:#999;
                                        font-size:12px;
                                    "
                                >
                                    ...
                                </span>

                            <?php endif; ?>


                            <a
                                href="<?= e(
                                    starparts_news_page_url(
                                        $totalPages,
                                        $search
                                    )
                                ); ?>"
                                class="btn btn-outline btn-sm"
                            >
                                <?= $totalPages; ?>
                            </a>

                        <?php endif; ?>


                        <?php if (
                            $currentPage < $totalPages
                        ): ?>

                            <a
                                href="<?= e(
                                    starparts_news_page_url(
                                        $currentPage + 1,
                                        $search
                                    )
                                ); ?>"
                                class="btn btn-outline btn-sm"
                            >

                                Berikutnya

                                <i
                                    class="fa-solid fa-chevron-right"
                                ></i>

                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


                <!-- TOTAL -->

                <div
                    style="
                        margin-top:18px;
                        text-align:center;
                        color:var(--spm-text-soft);
                        font-size:10px;
                    "
                >

                    Menampilkan
                    <?= number_format(
                        count($posts),
                        0,
                        ',',
                        '.'
                    ); ?>
                    dari
                    <?= number_format(
                        $totalPosts,
                        0,
                        ',',
                        '.'
                    ); ?>
                    berita

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- =======================================================
         CTA
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

</main>


<?php
// =========================================================
// FOOTER
// =========================================================

require_once __DIR__ . '/includes/footer.php';
?>