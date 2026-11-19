<?php
// =========================================================
// FILE   : news-detail.php
// LOKASI : starparts/news-detail.php
// Detail Berita PT Star Parts Motor
// =========================================================

declare(strict_types=1);

// Log fatal runtime errors for this public endpoint to the hosting PHP error log.
// Details are never printed to website visitors.
register_shutdown_function(static function (): void {
    $lastError = error_get_last();
    if (!is_array($lastError)) {
        return;
    }

    $fatalTypes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int) ($lastError['type'] ?? 0), $fatalTypes, true)) {
        return;
    }

    error_log(sprintf(
        '[Star Parts Motor][news-detail.php] Fatal PHP error: %s in %s:%d',
        (string) ($lastError['message'] ?? 'Unknown fatal error'),
        basename((string) ($lastError['file'] ?? __FILE__)),
        (int) ($lastError['line'] ?? 0)
    ));
});

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';


if (!function_exists('starparts_news_detail_substr')) {
    function starparts_news_detail_substr(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, $start, $length, 'UTF-8')
            : substr($value, $start, $length);
    }
}

if (!function_exists('starparts_news_detail_strlen')) {
    function starparts_news_detail_strlen(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);
    }
}


/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/

$slug = trim(
    (string) (
        $_GET['slug']
        ?? ''
    )
);

$requestedPostId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($requestedPostId === false || $requestedPostId === null) {
    $requestedPostId = 0;
}

if ((int) $requestedPostId <= 0 && $slug === '') {
    header('Location: ' . site_url('news.php'));
    exit;
}


/*
|--------------------------------------------------------------------------
| PAGE DATA
|--------------------------------------------------------------------------
*/

$siteName = trim(
    (string) get_setting(
        'site_name',
        'PT Star Parts Motor'
    )
);

$siteDescription = trim(
    (string) get_setting(
        'site_description',
        'Distributor dan partner distribusi produk otomotif berkualitas.'
    )
);


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$postsTable = 'posts';

$post = null;

$postColumns = [];
$postColumnNames = [];

$tableExists = false;


try {

    /*
    |--------------------------------------------------------------------------
    | CHECK TABLE
    |--------------------------------------------------------------------------
    */

    $tableCheck = $pdo->query(
        "SHOW TABLES LIKE " .
        $pdo->quote($postsTable)
    );

    $tableExists =
        $tableCheck &&
        (bool) $tableCheck->fetchColumn();


    if (!$tableExists) {

        http_response_code(404);

    } else {

        /*
        |--------------------------------------------------------------------------
        | READ COLUMNS
        |--------------------------------------------------------------------------
        */

        $columnStmt = $pdo->query(
            "SHOW COLUMNS FROM `{$postsTable}`"
        );

        $postColumns =
            $columnStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        foreach (
            $postColumns
            as $column
        ) {

            if (
                !empty(
                    $column['Field']
                )
            ) {

                $postColumnNames[] =
                    (string) $column['Field'];
            }
        }
    }

} catch (Throwable $e) {

    error_log(
        'Star Parts news detail table error: ' .
        $e->getMessage()
    );

    $tableExists = false;
}


/*
|--------------------------------------------------------------------------
| COLUMN HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('starparts_news_detail_column')) {
    function starparts_news_detail_column(
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
}


/*
|--------------------------------------------------------------------------
| COLUMN MAPPING
|--------------------------------------------------------------------------
*/

$postIdColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'id',
            'post_id'
        ]
    );

$postTitleColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'title',
            'judul',
            'name'
        ]
    );

$postSlugColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'slug',
            'post_slug'
        ]
    );

$postExcerptColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'excerpt',
            'summary',
            'short_description'
        ]
    );

$postContentColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'content',
            'body',
            'article',
            'description'
        ]
    );

$postImageColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'featured_image',
            'image',
            'thumbnail',
            'featured_photo'
        ]
    );

$postAuthorColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'author_name',
            'author',
            'writer'
        ]
    );

$postPublishedColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'is_published',
            'published',
            'status'
        ]
    );

$postFeaturedColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'is_featured',
            'featured'
        ]
    );

$postPublishedAtColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'published_at',
            'publication_date',
            'publish_at'
        ]
    );

$postCreatedAtColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'created_at',
            'created',
            'date_created'
        ]
    );

$postUpdatedAtColumn =
    starparts_news_detail_column(
        $postColumnNames,
        [
            'updated_at',
            'updated',
            'modified_at',
            'modified'
        ]
    );


/*
|--------------------------------------------------------------------------
| LOAD POST
|--------------------------------------------------------------------------
*/

if (
    $tableExists &&
    $postIdColumn !== null &&
    $postTitleColumn !== null &&
    $postSlugColumn !== null
) {

    try {

        $selectParts = [
            "`{$postIdColumn}` AS post_id",
            "`{$postTitleColumn}` AS post_title",
            "`{$postSlugColumn}` AS post_slug"
        ];


        /*
        |--------------------------------------------------------------------------
        | EXCERPT
        |--------------------------------------------------------------------------
        */

        if (
            $postExcerptColumn !== null
        ) {

            $selectParts[] =
                "`{$postExcerptColumn}` AS post_excerpt";

        } else {

            $selectParts[] =
                "'' AS post_excerpt";
        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        if (
            $postContentColumn !== null
        ) {

            $selectParts[] =
                "`{$postContentColumn}` AS post_content";

        } else {

            $selectParts[] =
                "'' AS post_content";
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            $postImageColumn !== null
        ) {

            $selectParts[] =
                "`{$postImageColumn}` AS post_image";

        } else {

            $selectParts[] =
                "'' AS post_image";
        }


        /*
        |--------------------------------------------------------------------------
        | AUTHOR
        |--------------------------------------------------------------------------
        */

        if (
            $postAuthorColumn !== null
        ) {

            $selectParts[] =
                "`{$postAuthorColumn}` AS post_author";

        } else {

            $selectParts[] =
                "'' AS post_author";
        }


        /*
        |--------------------------------------------------------------------------
        | FEATURED
        |--------------------------------------------------------------------------
        */

        if (
            $postFeaturedColumn !== null
        ) {

            $selectParts[] =
                "`{$postFeaturedColumn}` AS post_featured";

        } else {

            $selectParts[] =
                "0 AS post_featured";
        }


        /*
        |--------------------------------------------------------------------------
        | PUBLISHED AT
        |--------------------------------------------------------------------------
        */

        if (
            $postPublishedAtColumn !== null
        ) {

            $selectParts[] =
                "`{$postPublishedAtColumn}` AS post_published_at";

        } else {

            $selectParts[] =
                "NULL AS post_published_at";
        }


        /*
        |--------------------------------------------------------------------------
        | CREATED AT
        |--------------------------------------------------------------------------
        */

        if (
            $postCreatedAtColumn !== null
        ) {

            $selectParts[] =
                "`{$postCreatedAtColumn}` AS post_created_at";

        } else {

            $selectParts[] =
                "NULL AS post_created_at";
        }

        if ($postUpdatedAtColumn !== null) {
            $selectParts[] =
                "`{$postUpdatedAtColumn}` AS post_updated_at";
        } else {
            $selectParts[] =
                "NULL AS post_updated_at";
        }


        /*
        |--------------------------------------------------------------------------
        | WHERE
        |--------------------------------------------------------------------------
        */

        $where = [];
        $params = [];

        // ID URL is the primary route: it remains functional even when
        // Apache mod_rewrite is unavailable or .htaccess rules differ.
        if ((int) $requestedPostId > 0 && $postIdColumn !== null) {
            $where[] = "`{$postIdColumn}` = :post_id";
            $params[':post_id'] = (int) $requestedPostId;
        } else {
            $where[] = "`{$postSlugColumn}` = :slug";
            $params[':slug'] = $slug;
        }

        /*
        |--------------------------------------------------------------------------
        | ONLY PUBLISHED
        |--------------------------------------------------------------------------
        */

        if ($postPublishedColumn !== null) {
            if ($postPublishedColumn === 'status') {
                $where[] = "`{$postPublishedColumn}` = 'published'";
            } else {
                $where[] = "`{$postPublishedColumn}` = 1";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | QUERY
        |--------------------------------------------------------------------------
        */

        $detailSql =
            "SELECT " .
            implode(
                ', ',
                $selectParts
            ) .
            "
             FROM `{$postsTable}`
             WHERE " .
            implode(
                ' AND ',
                $where
            ) .
            "
             LIMIT 1";


        $detailStmt =
            $pdo->prepare(
                $detailSql
            );

        $detailStmt->execute(
            $params
        );

        $post =
            $detailStmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: null;

    } catch (Throwable $e) {

        error_log(
            'Star Parts news detail query error: ' .
            $e->getMessage()
        );

        $post = null;
    }
}


/*
|--------------------------------------------------------------------------
| NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$post) {

    http_response_code(404);

    $pageRobots = 'noindex, follow';

    $pageTitle =
        'Berita Tidak Ditemukan | ' .
        $siteName;

    $pageDescription =
        'Berita yang Anda cari tidak ditemukan atau sudah tidak tersedia.';

    $bodyClass =
        'news-detail-page';

    require_once __DIR__ . '/includes/header.php';

    require_once __DIR__ . '/includes/navbar.php';

    ?>

    <main id="main-content">

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


                        <a
                            href="<?= e(
                                base_url('news.php')
                            ); ?>"
                        >
                            Berita
                        </a>


                        <span>
                            <i
                                class="fa-solid fa-chevron-right"
                            ></i>
                        </span>


                        <span class="current">
                            Tidak Ditemukan
                        </span>

                    </div>


                    <span
                        class="eyebrow"
                    >
                        404
                    </span>


                    <h1>
                        Berita Tidak Ditemukan
                    </h1>


                    <p>
                        Berita yang Anda cari tidak tersedia
                        atau mungkin sudah tidak dipublikasikan.
                    </p>

                </div>

            </div>

        </section>


        <section
            class="section section-white"
        >

            <div class="container">

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


                    <h3>
                        Berita tidak tersedia
                    </h3>


                    <p>
                        Silakan kembali ke halaman berita
                        untuk melihat artikel lainnya.
                    </p>


                    <div
                        style="
                            display:flex;
                            justify-content:center;
                            flex-wrap:wrap;
                            gap:10px;
                        "
                    >

                        <a
                            href="<?= e(
                                base_url('news.php')
                            ); ?>"
                            class="btn btn-primary"
                        >

                            <i
                                class="fa-solid fa-arrow-left"
                            ></i>

                            Kembali ke Berita

                        </a>


                        <a
                            href="<?= e(
                                base_url()
                            ); ?>"
                            class="btn btn-outline"
                        >

                            <i
                                class="fa-solid fa-house"
                            ></i>

                            Beranda

                        </a>

                    </div>

                </div>

            </div>

        </section>

    </main>


    <?php

    require_once __DIR__ . '/includes/footer.php';

    exit;
}


/*
|--------------------------------------------------------------------------
| NORMALIZE DATA
|--------------------------------------------------------------------------
*/

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

$postModifiedAt =
    $post['post_updated_at']
    ??
    '';

$postDate = '-';

if (
    $postPublishedAt !== ''
) {

    $timestamp =
        strtotime(
            (string) $postPublishedAt
        );

    if (
        $timestamp !== false
    ) {

        if (
            function_exists(
                'format_date_id'
            )
        ) {

            try {

                $postDate =
                    (string) format_date_id(
                        (string) $postPublishedAt
                    );

            } catch (Throwable $e) {

                $postDate =
                    date(
                        'd M Y',
                        $timestamp
                    );
            }

        } else {

            $postDate =
                date(
                    'd M Y',
                    $timestamp
                );
        }
    }
}


/*
|--------------------------------------------------------------------------
| META
|--------------------------------------------------------------------------
*/

$postMetaTitle = trim((string)($post['meta_title'] ?? ''));
$postMetaDescription = trim((string)($post['meta_description'] ?? ''));
$pageTitle =
    $postMetaTitle !== ''
        ? $postMetaTitle
        : (
            ($postTitle !== '' ? $postTitle : 'Berita') .
            ' | ' .
            $siteName
        );


$plainExcerpt =
    trim(
        strip_tags(
            $postExcerpt !== ''
                ? $postExcerpt
                : $postContent
        )
    );


if (
    $plainExcerpt === ''
) {

    $plainExcerpt =
        $siteDescription;
}

if ($postMetaDescription !== '') {
    $pageDescription = seo_clean_text($postMetaDescription, 160);
}


if (
    function_exists('excerpt')
) {

    try {

        $pageDescription =
            (string) excerpt(
                $plainExcerpt,
                160
            );

    } catch (Throwable $e) {

        $pageDescription =
            starparts_news_detail_substr(
                $plainExcerpt,
                0,
                157
            ) .
            (
                starparts_news_detail_strlen($plainExcerpt) > 160
                    ? '...'
                    : ''
            );
    }

} else {

    $pageDescription =
        starparts_news_detail_substr(
            $plainExcerpt,
            0,
            157
        ) .
        (
            starparts_news_detail_strlen($plainExcerpt) > 160
                ? '...'
                : ''
        );
}


if ($postMetaDescription !== '') {
    $pageDescription = seo_clean_text($postMetaDescription, 160);
}

$pageImage =
    $postImage;

$postImageAlt = trim((string)($post['featured_image_alt'] ?? ''));
if ($postImageAlt === '') $postImageAlt = $postTitle !== '' ? $postTitle : 'Gambar berita Star Parts Motor';

// Canonical article URLs intentionally use the direct PHP endpoint and ID.
// This avoids a dependency on Apache rewrite rules on shared hosting.
$pageCanonicalUrl = site_url('news-detail.php?id=' . (int) $postId);
$ogType = 'article';
$articlePublishedAt = trim((string)$postPublishedAt) !== '' ? (string)$postPublishedAt : 'now';
$articleModifiedAt = trim((string)$postModifiedAt);
$articlePublishedTimestamp = strtotime($articlePublishedAt);
if ($articlePublishedTimestamp === false) $articlePublishedTimestamp = time();
$articleModifiedTimestamp = $articleModifiedAt !== '' ? strtotime($articleModifiedAt) : false;
$articleAuthorName = trim((string)$postAuthor) ?: 'Star Parts Motor';
$pageStructuredData = [[
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $postTitle,
    'description' => $pageDescription,
    'mainEntityOfPage' => $pageCanonicalUrl,
    'datePublished' => date(DATE_ATOM, $articlePublishedTimestamp),
    'author' => ['@type'=>'Person','name'=>$articleAuthorName],
    'publisher' => ['@type'=>'Organization','name'=>$siteName],
]];
if ($articleModifiedTimestamp !== false) $pageStructuredData[0]['dateModified'] = date(DATE_ATOM, $articleModifiedTimestamp);
if ($pageImage !== '') $pageStructuredData[0]['image'] = [absolute_url($pageImage)];
$pageStructuredData[] = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>'Beranda','item'=>site_url()],
        ['@type'=>'ListItem','position'=>2,'name'=>'Berita','item'=>site_url('news.php')],
        ['@type'=>'ListItem','position'=>3,'name'=>$postTitle,'item'=>$pageCanonicalUrl],
    ],
];

$bodyClass =
    'news-detail-page';


/*
|--------------------------------------------------------------------------
| SAFE CONTENT
|--------------------------------------------------------------------------
|
| Konten berasal dari panel admin. Tag HTML yang umum digunakan
| untuk artikel tetap dipertahankan agar formatting artikel tampil.
|--------------------------------------------------------------------------
*/

$allowedContentTags =
    '<p><br><strong><b><em><i><u><s><ul><ol><li><blockquote><h2><h3><h4><h5><h6><hr><a><span><div><img><figure><figcaption><iframe><video><audio><source><table><thead><tbody><tr><th><td>';


$renderedContent =
    $postContent !== ''
        ? strip_tags(
            $postContent,
            $allowedContentTags
        )
        : '';

// Jika teks artikel polos tanpa tag paragraf (misal artikel lama), otomatis konversi newlines ke <p>
if ($renderedContent !== '' && !preg_match('/<\s*(?:p|div|h[1-6]|ul|ol|blockquote|table|figure)\b/i', $renderedContent)) {
    $renderedContent = '<p>' . nl2br($renderedContent) . '</p>';
}


/*
|--------------------------------------------------------------------------
| RELATED POSTS
|--------------------------------------------------------------------------
*/

$relatedPosts = [];


if (
    $tableExists &&
    $postIdColumn !== null &&
    $postTitleColumn !== null &&
    $postSlugColumn !== null
) {

    try {

        $relatedSelect = [
            "`{$postIdColumn}` AS post_id",
            "`{$postTitleColumn}` AS post_title",
            "`{$postSlugColumn}` AS post_slug"
        ];


        if (
            $postExcerptColumn !== null
        ) {

            $relatedSelect[] =
                "`{$postExcerptColumn}` AS post_excerpt";

        } else {

            $relatedSelect[] =
                "'' AS post_excerpt";
        }


        if (
            $postContentColumn !== null
        ) {

            $relatedSelect[] =
                "`{$postContentColumn}` AS post_content";

        } else {

            $relatedSelect[] =
                "'' AS post_content";
        }


        if (
            $postImageColumn !== null
        ) {

            $relatedSelect[] =
                "`{$postImageColumn}` AS post_image";

        } else {

            $relatedSelect[] =
                "'' AS post_image";
        }


        if (
            $postPublishedAtColumn !== null
        ) {

            $relatedSelect[] =
                "`{$postPublishedAtColumn}` AS post_published_at";

        } else {

            $relatedSelect[] =
                "NULL AS post_published_at";
        }


        if (
            $postCreatedAtColumn !== null
        ) {

            $relatedSelect[] =
                "`{$postCreatedAtColumn}` AS post_created_at";

        } else {

            $relatedSelect[] =
                "NULL AS post_created_at";
        }


        $relatedWhere = [
            "`{$postIdColumn}` <> :current_id"
        ];

        $relatedParams = [
            ':current_id' => $postId
        ];


        if (
            $postPublishedColumn !== null
        ) {

            $relatedWhere[] =
                "`{$postPublishedColumn}` = 'published'";
        }


        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        if (
            $postPublishedAtColumn !== null &&
            $postCreatedAtColumn !== null
        ) {

            $relatedOrder =
                "`{$postPublishedAtColumn}` DESC,
                 `{$postCreatedAtColumn}` DESC,
                 `{$postIdColumn}` DESC";

        } elseif (
            $postPublishedAtColumn !== null
        ) {

            $relatedOrder =
                "`{$postPublishedAtColumn}` DESC,
                 `{$postIdColumn}` DESC";

        } elseif (
            $postCreatedAtColumn !== null
        ) {

            $relatedOrder =
                "`{$postCreatedAtColumn}` DESC,
                 `{$postIdColumn}` DESC";

        } else {

            $relatedOrder =
                "`{$postIdColumn}` DESC";
        }


        $relatedSql =
            "SELECT " .
            implode(
                ', ',
                $relatedSelect
            ) .
            "
             FROM `{$postsTable}`
             WHERE " .
            implode(
                ' AND ',
                $relatedWhere
            ) .
            "
             ORDER BY {$relatedOrder}
             LIMIT 3";


        $relatedStmt =
            $pdo->prepare(
                $relatedSql
            );

        $relatedStmt->execute(
            $relatedParams
        );

        $relatedPosts =
            $relatedStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    } catch (Throwable $e) {

        error_log(
            'Star Parts related news error: ' .
            $e->getMessage()
        );

        $relatedPosts = [];
    }
}


/*
|--------------------------------------------------------------------------
| WHATSAPP
|--------------------------------------------------------------------------
*/

$whatsapp =
    trim(
        (string) get_setting(
            'whatsapp',
            ''
        )
    );


$whatsappNumber = '';

if (
    $whatsapp !== ''
) {

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


$shareWhatsAppLink = '#';


if (
    $whatsappNumber !== ''
) {

    $shareMessage =
        "Halo Star Parts Motor, saya ingin mendapatkan informasi mengenai berita: " .
        $postTitle;

    $shareWhatsAppLink =
        'https://wa.me/' .
        $whatsappNumber .
        '?text=' .
        rawurlencode(
            $shareMessage
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
         ARTICLE HERO
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


                    <a
                        href="<?= e(
                            base_url(
                                'news.php'
                            )
                        ); ?>"
                    >
                        Berita
                    </a>


                    <span>
                        <i
                            class="fa-solid fa-chevron-right"
                        ></i>
                    </span>


                    <span
                        class="current"
                    >
                        Artikel
                    </span>

                </div>


                <span
                    class="eyebrow"
                >
                    <?= $postFeatured === 1
                        ? 'FEATURED NEWS'
                        : 'NEWS & UPDATE'; ?>
                </span>


                <h1
                    class="news-detail-main-title"
                    style="color: #ffffff !important; text-shadow: 0 1px 8px rgba(0, 0, 0, 0.28);"
                >
                    <?= e(
                        $postTitle
                    ); ?>
                </h1>


                <?php if (
                    $postExcerpt !== ''
                ): ?>

                    <p>
                        <?= e(
                            function_exists('excerpt')
                                ? excerpt(
                                    strip_tags(
                                        $postExcerpt
                                    ),
                                    220
                                )
                                : starparts_news_detail_substr(
                                    strip_tags(
                                        $postExcerpt
                                    ),
                                    0,
                                    220
                                )
                        ); ?>
                    </p>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <!-- =======================================================
         ARTICLE
    ======================================================== -->

    <section
        class="section section-white"
    >

        <div class="container">

            <div
                style="
                    max-width:900px;
                    margin:0 auto;
                "
            >


                <!-- META -->

                <div
                    style="
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        flex-wrap:wrap;
                        gap:10px;
                        margin-bottom:28px;
                        color:var(--spm-text-soft);
                        font-size:11px;
                    "
                >

                    <span>

                        <i
                            class="fa-regular fa-calendar"
                            style="margin-right:4px;"
                        ></i>

                        <?= e(
                            $postDate
                        ); ?>

                    </span>


                    <?php if (
                        $postAuthor !== ''
                    ): ?>

                        <span>
                            •
                        </span>

                        <span>

                            <i
                                class="fa-regular fa-user"
                                style="margin-right:4px;"
                            ></i>

                            <?= e(
                                $postAuthor
                            ); ?>

                        </span>

                    <?php endif; ?>

                </div>


                <!-- FEATURED IMAGE -->

                <?php if (
                    $postImage !== ''
                ): ?>

                    <figure
                        style="
                            margin:0 0 35px;
                            overflow:hidden;
                            border-radius:20px;
                            background:#f1f2f4;
                            box-shadow:
                                0 14px 45px
                                rgba(15,23,42,.08);
                        "
                        data-aos="fade-up"
                    >

                        <img
                            src="<?= e(
                                media_url(
                                    $postImage
                                )
                            ); ?>"
                            alt="<?= e(
                                $postImageAlt
                            ); ?>"
                            style="
                                width:100%;
                                max-height:570px;
                                display:block;
                                object-fit:cover;
                            "
                            loading="eager"
                        >

                    </figure>

                <?php endif; ?>


                <!-- ARTICLE BODY -->

                <article
                    class="about-text news-article-body"
                    data-aos="fade-up"
                    style="
                        max-width:820px;
                        margin:0 auto;
                        font-size:15px;
                        line-height:1.9;
                    "
                >

                    <?php if (
                        $renderedContent !== ''
                    ): ?>

                        <?= $renderedContent; ?>

                    <?php elseif (
                        $postExcerpt !== ''
                    ): ?>

                        <p>
                            <?= nl2br(
                                e(
                                    $postExcerpt
                                )
                            ); ?>
                        </p>

                    <?php else: ?>

                        <div
                            class="empty-inline"
                        >

                            <i
                                class="fa-regular fa-file-lines"
                            ></i>

                            <span>
                                Isi artikel belum tersedia.
                            </span>

                        </div>

                    <?php endif; ?>

                </article>


                <!-- SHARE / ACTION -->

                <div
                    style="
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        flex-wrap:wrap;
                        gap:12px;
                        margin-top:40px;
                        padding-top:24px;
                        border-top:1px solid rgba(0,0,0,.08);
                    "
                >

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

                        Kembali ke Berita

                    </a>


                    <?php if (
                        $whatsappNumber !== ''
                    ): ?>

                        <a
                            href="<?= e(
                                $shareWhatsAppLink
                            ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-primary"
                        >

                            <i
                                class="fa-brands fa-whatsapp"
                            ></i>

                            Tanya via WhatsApp

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>


    <!-- =======================================================
         RELATED NEWS
    ======================================================== -->

    <?php if (
        !empty($relatedPosts)
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
                        NEWS LAINNYA
                    </div>


                    <h2
                        class="section-title"
                    >
                        Berita
                        <span>
                            Terkait
                        </span>
                    </h2>


                    <p
                        class="section-description"
                    >
                        Baca informasi dan aktivitas
                        terbaru lainnya dari Star Parts Motor.
                    </p>

                </div>


                <div
                    class="posts-grid"
                >

                    <?php foreach (
                        $relatedPosts
                        as $index => $related
                    ): ?>

                        <?php

                        $relatedTitle =
                            trim(
                                (string) (
                                    $related['post_title']
                                    ?? ''
                                )
                            );

                        $relatedSlug =
                            trim(
                                (string) (
                                    $related['post_slug']
                                    ?? ''
                                )
                            );

                        $relatedExcerpt =
                            trim(
                                (string) (
                                    $related['post_excerpt']
                                    ?? ''
                                )
                            );

                        $relatedContent =
                            trim(
                                (string) (
                                    $related['post_content']
                                    ?? ''
                                )
                            );

                        $relatedImage =
                            trim(
                                (string) (
                                    $related['post_image']
                                    ?? ''
                                )
                            );

                        $relatedDate =
                            $related['post_published_at']
                            ??
                            $related['post_created_at']
                            ??
                            '';

                        $relatedSummary =
                            $relatedExcerpt !== ''
                                ? $relatedExcerpt
                                : $relatedContent;

                        $relatedUrl =
                            post_url($relatedSlug, (int) ($related['post_id'] ?? 0));

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

                            <a
                                href="<?= e(
                                    $relatedUrl
                                ); ?>"
                                class="post-image"
                            >

                                <?php if (
                                    $relatedImage !== ''
                                ): ?>

                                    <img
                                        src="<?= e(
                                            media_url(
                                                $relatedImage
                                            )
                                        ); ?>"
                                        alt="<?= e(
                                            $relatedTitle
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
                                            background:#eeeeee;
                                            color:#aaa;
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

                                    <?php

                                    $relatedDateLabel = '-';

                                    if (
                                        $relatedDate !== ''
                                    ) {

                                        $relatedTimestamp =
                                            strtotime(
                                                (string) $relatedDate
                                            );

                                        if (
                                            $relatedTimestamp !== false
                                        ) {

                                            $relatedDateLabel =
                                                date(
                                                    'd M Y',
                                                    $relatedTimestamp
                                                );
                                        }
                                    }

                                    ?>

                                    <?= e(
                                        $relatedDateLabel
                                    ); ?>

                                </div>


                                <h3
                                    class="post-title"
                                >

                                    <a
                                        href="<?= e(
                                            $relatedUrl
                                        ); ?>"
                                    >
                                        <?= e(
                                            $relatedTitle
                                        ); ?>
                                    </a>

                                </h3>


                                <?php if (
                                    $relatedSummary !== ''
                                ): ?>

                                    <p
                                        class="post-excerpt"
                                    >
                                        <?= e(
                                            function_exists('excerpt')
                                                ? excerpt(
                                                    strip_tags(
                                                        $relatedSummary
                                                    ),
                                                    110
                                                )
                                                : starparts_news_detail_substr(
                                                    strip_tags(
                                                        $relatedSummary
                                                    ),
                                                    0,
                                                    110
                                                )
                                        ); ?>
                                    </p>

                                <?php endif; ?>


                                <a
                                    href="<?= e(
                                        $relatedUrl
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
                    style="margin-top:32px;"
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

            </div>

        </section>

    <?php endif; ?>


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

                        Butuh Informasi
                        <strong>
                            Lebih Lanjut?
                        </strong>

                    </h2>


                    <p
                        class="cta-text"
                    >
                        Hubungi Star Parts Motor untuk informasi
                        produk dan kerja sama distribusi.
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
                                $shareWhatsAppLink
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


<?php
// =========================================================
// FOOTER
// =========================================================

require_once __DIR__ . '/includes/footer.php';
?>