<?php
// =========================================================
// admin/content.php
// Content Center / CMS halaman website Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();

$pageTitle = 'Content Center';
$admin = current_admin();

$siteName = trim((string) get_setting('site_name', 'PT Star Parts Motor'));
$siteLogo = trim((string) get_setting('site_logo', ''));
$siteLogoUrl = $siteLogo !== '' ? media_url($siteLogo) : '';

$baseAdmin = base_url('admin/');

$sections = [
    'home' => [
        'label' => 'HOME',
        'title' => 'Halaman Beranda',
        'icon'  => 'fa-house',
        'description' => 'Kelola hero, konten pengenalan perusahaan, visi, misi, dan SEO homepage.',
        'public_url' => 'index.php',
        'manage' => [
            ['label' => 'Kelola Banner Hero', 'url' => 'banners.php', 'icon' => 'fa-images']
        ]
    ],
    'site' => [
        'label' => 'SITE & FOOTER',
        'title' => 'Identitas, Kontak & CTA',
        'icon'  => 'fa-globe',
        'description' => 'Kelola identitas perusahaan, kontak, sosial media, footer, dan CTA utama tanpa mengubah kode.',
        'public_url' => 'index.php',
        'manage' => [
            ['label' => 'Lihat Homepage', 'url' => 'index.php', 'icon' => 'fa-arrow-up-right-from-square']
        ]
    ],
    'navigation' => [
        'label' => 'NAVIGATION',
        'title' => 'Menu Website',
        'icon'  => 'fa-bars',
        'description' => 'Atur urutan menu, parent menu, URL, status aktif, dan target pembukaan link.',
        'manage' => [
            ['label' => 'Kelola Menu', 'url' => 'menus.php', 'icon' => 'fa-bars-staggered']
        ]
    ],
    'about' => [
        'label' => 'ABOUT US',
        'title' => 'Tentang Kami',
        'icon'  => 'fa-building',
        'description' => 'Kelola profil, visi, misi, foto perusahaan, dan metadata halaman.',
        'public_url' => 'about.php',
        'manage' => []
    ],
    'products' => [
        'label' => 'PRODUCT',
        'title' => 'Produk & Katalog',
        'icon'  => 'fa-box-open',
        'description' => 'Kelola SEO halaman katalog. Produk, kategori, dan gambar produk dikelola melalui modul khusus.',
        'public_url' => 'products.php',
        'manage' => [
            ['label' => 'Produk', 'url' => 'products.php', 'icon' => 'fa-box'],
            ['label' => 'Kategori', 'url' => 'categories.php', 'icon' => 'fa-layer-group'],
            ['label' => 'Brand', 'url' => 'brands.php', 'icon' => 'fa-tags']
        ]
    ],
    'news' => [
        'label' => 'NEWS',
        'title' => 'Berita & Aktivitas',
        'icon'  => 'fa-newspaper',
        'description' => 'Kelola SEO halaman berita. Konten berita dan gambar featured dikelola melalui modul Berita.',
        'public_url' => 'news.php',
        'manage' => [
            ['label' => 'Kelola Berita', 'url' => 'posts.php', 'icon' => 'fa-pen-to-square']
        ]
    ],
    'career' => [
        'label' => 'CAREER',
        'title' => 'Karir',
        'icon'  => 'fa-briefcase',
        'description' => 'Kelola judul SEO, isi halaman karir, poster lowongan, deskripsi, tautan lamar, dan gambar utama.',
        'public_url' => 'career.php',
        'manage' => [
            ['label' => 'Kelola Lowongan', 'url' => 'careers.php', 'icon' => 'fa-user-plus'],
            ['label' => 'Daftar Halaman CMS', 'url' => 'pages.php', 'icon' => 'fa-file-lines']
        ]
    ],
    'contact' => [
        'label' => 'CONTACT',
        'title' => 'Kontak',
        'icon'  => 'fa-envelope',
        'description' => 'Kelola metadata, gambar kontak, dan arahkan data komunikasi ke modul lokasi/pesan.',
        'public_url' => 'contact.php',
        'manage' => [
            ['label' => 'Lokasi', 'url' => 'locations.php', 'icon' => 'fa-map-location-dot'],
            ['label' => 'Pesan Masuk', 'url' => 'messages.php', 'icon' => 'fa-inbox']
        ]
    ],
    'legal' => [
        'label' => 'LEGAL',
        'title' => 'Privacy & Terms',
        'icon'  => 'fa-shield-halved',
        'description' => 'Kelola halaman kebijakan privasi dan syarat penggunaan melalui editor CMS.',
        'public_url' => 'privacy.php',
        'manage' => [
            ['label' => 'Daftar Halaman CMS', 'url' => 'pages.php', 'icon' => 'fa-file-lines']
        ]
    ],
    'seo' => [
        'label' => 'SEO',
        'title' => 'SEO Website',
        'icon'  => 'fa-magnifying-glass',
        'description' => 'Kelola title, description, keywords, dan gambar sosial default website.',
        'manage' => [
            ['label' => 'SEO Health Check', 'url' => 'content.php?section=seo#seo-health', 'icon' => 'fa-gauge-high'],
            ['label' => 'Security Center', 'url' => 'security.php', 'icon' => 'fa-shield-halved']
        ]
    ],
];

function content_setting_save(PDO $pdo, string $key, string $value, string $type = 'text'): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO site_settings (setting_key, setting_value, setting_type, created_at, updated_at)
         VALUES (?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            setting_type = VALUES(setting_type),
            updated_at = NOW()"
    );

    $stmt->execute([$key, $value, $type]);
}

function content_local_file_path(?string $path): ?string
{
    $path = trim((string) $path);
    if ($path === '' || !str_starts_with($path, 'uploads/')) {
        return null;
    }

    $root = realpath(dirname(__DIR__) . '/uploads');
    $file = realpath(dirname(__DIR__) . '/' . ltrim($path, '/'));

    if ($root === false || $file === false || !is_file($file)) {
        return null;
    }

    $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return (str_starts_with($file, $prefix) || $file === $root) ? $file : null;
}

function content_delete_local_file(?string $path): void
{
    $local = content_local_file_path($path);
    if ($local !== null) {
        delete_image_variants($local);
        @unlink($local);
    }
}

function content_get_page(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function content_save_page(PDO $pdo, string $slug, array $data): void
{
    $existing = content_get_page($pdo, $slug);

    if ($existing) {
        $stmt = $pdo->prepare(
            "UPDATE pages
             SET title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?, meta_title = ?, meta_description = ?, status = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([
            $data['title'],
            $slug,
            $data['excerpt'],
            $data['content'],
            $data['featured_image'],
            $data['meta_title'],
            $data['meta_description'],
            $data['status'],
            (int) $existing['id']
        ]);
        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO pages (title, slug, excerpt, content, featured_image, meta_title, meta_description, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->execute([
        $data['title'],
        $slug,
        $data['excerpt'],
        $data['content'],
        $data['featured_image'],
        $data['meta_title'],
        $data['meta_description'],
        $data['status']
    ]);
}

function content_upload_image(string $field, string $folder = 'images'): array
{
    $selected = selected_media_path($field);
    if ($selected !== '') {
        return ['success' => true, 'path' => $selected, 'message' => 'Media dipilih.'];
    }
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return ['success' => false, 'message' => ''];
    }
    return upload_image($_FILES[$field], $folder);
}

$section = strtolower(trim(get_string('section', 'home')));
if (!isset($sections[$section])) {
    $section = 'home';
}

$flashMessage = get_flash();

if (is_post()) {
    if (!verify_csrf()) {
        set_flash('error', 'Permintaan tidak valid. Silakan refresh halaman lalu coba kembali.');
        redirect(base_url('admin/content.php?section=' . rawurlencode($section)));
    }

    $action = post_string('action');
    $postedSection = strtolower(post_string('section', $section));
    if (!isset($sections[$postedSection])) {
        $postedSection = 'home';
    }

    try {
        $pdo->beginTransaction();

        $settingFields = [];

        switch ($postedSection) {
            case 'home':
                $settingFields = [
                    ['key' => 'home_page_title', 'field' => 'home_page_title', 'type' => 'text'],
                    ['key' => 'home_page_description', 'field' => 'home_page_description', 'type' => 'textarea'],
                    ['key' => 'hero_title', 'field' => 'hero_title', 'type' => 'text'],
                    ['key' => 'hero_subtitle', 'field' => 'hero_subtitle', 'type' => 'textarea'],
                    ['key' => 'hero_button_text', 'field' => 'hero_button_text', 'type' => 'text'],
                    ['key' => 'hero_button_url', 'field' => 'hero_button_url', 'type' => 'url'],
                    ['key' => 'about_title', 'field' => 'about_title', 'type' => 'text'],
                    ['key' => 'about_content', 'field' => 'about_content', 'type' => 'html'],
                    ['key' => 'vision', 'field' => 'vision', 'type' => 'textarea'],
                    ['key' => 'mission', 'field' => 'mission', 'type' => 'textarea'],
                    ['key' => 'stat_years', 'field' => 'stat_years', 'type' => 'text'],
                    ['key' => 'stat_partners', 'field' => 'stat_partners', 'type' => 'text'],
                    ['key' => 'stat_products', 'field' => 'stat_products', 'type' => 'text'],
                    ['key' => 'stat_regions', 'field' => 'stat_regions', 'type' => 'text'],
                ];
                break;

            case 'site':
                $settingFields = [
                    ['key' => 'site_name', 'field' => 'site_name', 'type' => 'text'],
                    ['key' => 'site_tagline', 'field' => 'site_tagline', 'type' => 'text'],
                    ['key' => 'site_description', 'field' => 'site_description', 'type' => 'textarea'],
                    ['key' => 'phone_primary', 'field' => 'phone_primary', 'type' => 'phone'],
                    ['key' => 'phone_secondary', 'field' => 'phone_secondary', 'type' => 'phone'],
                    ['key' => 'whatsapp', 'field' => 'whatsapp', 'type' => 'phone'],
                    ['key' => 'email', 'field' => 'email', 'type' => 'email'],
                    ['key' => 'address', 'field' => 'address', 'type' => 'textarea'],
                    ['key' => 'google_maps_url', 'field' => 'google_maps_url', 'type' => 'url'],
                    ['key' => 'facebook_url', 'field' => 'facebook_url', 'type' => 'url'],
                    ['key' => 'instagram_url', 'field' => 'instagram_url', 'type' => 'url'],
                    ['key' => 'youtube_url', 'field' => 'youtube_url', 'type' => 'url'],
                    ['key' => 'tiktok_url', 'field' => 'tiktok_url', 'type' => 'url'],
                    ['key' => 'footer_text', 'field' => 'footer_text', 'type' => 'text'],
                    ['key' => 'cta_title_prefix', 'field' => 'cta_title_prefix', 'type' => 'text'],
                    ['key' => 'cta_title_highlight', 'field' => 'cta_title_highlight', 'type' => 'text'],
                    ['key' => 'cta_text', 'field' => 'cta_text', 'type' => 'textarea'],
                    ['key' => 'cta_whatsapp_text', 'field' => 'cta_whatsapp_text', 'type' => 'text'],
                    ['key' => 'cta_button_text', 'field' => 'cta_button_text', 'type' => 'text'],
                    ['key' => 'cta_button_url', 'field' => 'cta_button_url', 'type' => 'url'],
                ];
                break;

            case 'navigation':
                $settingFields = [];
                break;

            case 'about':
                $settingFields = [
                    ['key' => 'about_page_title', 'field' => 'about_page_title', 'type' => 'text'],
                    ['key' => 'about_page_description', 'field' => 'about_page_description', 'type' => 'textarea'],
                    ['key' => 'about_page_heading', 'field' => 'about_page_heading', 'type' => 'text'],
                    ['key' => 'about_page_intro', 'field' => 'about_page_intro', 'type' => 'textarea'],
                    ['key' => 'about_title', 'field' => 'about_title', 'type' => 'text'],
                    ['key' => 'about_content', 'field' => 'about_content', 'type' => 'html'],
                    ['key' => 'vision', 'field' => 'vision', 'type' => 'textarea'],
                    ['key' => 'mission', 'field' => 'mission', 'type' => 'textarea'],
                    ['key' => 'about_history_title', 'field' => 'about_history_title', 'type' => 'text'],
                    ['key' => 'about_history_content', 'field' => 'about_history_content', 'type' => 'html'],
                    ['key' => 'about_commitment_title', 'field' => 'about_commitment_title', 'type' => 'text'],
                    ['key' => 'about_commitment_content', 'field' => 'about_commitment_content', 'type' => 'html'],
                ];
                break;

            case 'products':
                $settingFields = [
                    ['key' => 'products_page_title', 'field' => 'products_page_title', 'type' => 'text'],
                    ['key' => 'products_page_description', 'field' => 'products_page_description', 'type' => 'textarea'],
                    ['key' => 'meta_keywords', 'field' => 'meta_keywords', 'type' => 'text'],
                    ['key' => 'image_webp_quality', 'field' => 'image_webp_quality', 'type' => 'number'],
                    ['key' => 'image_variant_widths', 'field' => 'image_variant_widths', 'type' => 'text'],
                ];
                break;

            case 'news':
                $settingFields = [
                    ['key' => 'news_page_title', 'field' => 'news_page_title', 'type' => 'text'],
                    ['key' => 'news_page_description', 'field' => 'news_page_description', 'type' => 'textarea'],
                ];
                break;

            case 'contact':
                $settingFields = [
                    ['key' => 'contact_page_title', 'field' => 'contact_page_title', 'type' => 'text'],
                    ['key' => 'contact_page_description', 'field' => 'contact_page_description', 'type' => 'textarea'],
                ];
                break;

            case 'seo':
                $settingFields = [
                    ['key' => 'site_url', 'field' => 'site_url', 'type' => 'url'],
                    ['key' => 'google_site_verification', 'field' => 'google_site_verification', 'type' => 'text'],
                    ['key' => 'meta_title', 'field' => 'meta_title', 'type' => 'text'],
                    ['key' => 'meta_description', 'field' => 'meta_description', 'type' => 'textarea'],
                    ['key' => 'meta_keywords', 'field' => 'meta_keywords', 'type' => 'text'],
                ];
                break;
        }

        foreach ($settingFields as $field) {
            $value = trim(post_string($field['field']));
            if ($field['key'] === 'site_url' && $value !== '') {
                if (!preg_match('~^https?://~i', $value)) {
                    throw new RuntimeException('URL Website Publik harus diawali http:// atau https://.');
                }
                $value = rtrim($value, '/') . '/';
            }
            content_setting_save($pdo, $field['key'], $value, $field['type']);
        }

        $imageFields = [];
        if ($postedSection === 'home') {
            $imageFields = [
                ['key' => 'about_image', 'field' => 'about_image', 'upload_field' => 'about_image_upload'],
            ];
        } elseif ($postedSection === 'about') {
            $imageFields = [
                ['key' => 'about_image', 'field' => 'about_image', 'upload_field' => 'about_image_upload'],
            ];
        }

        foreach ($imageFields as $imageField) {
            $current = trim((string) get_setting($imageField['key'], ''));
            $remove = isset($_POST['remove_' . $imageField['key']]);

            if ($remove) {
                content_delete_local_file($current);
                content_setting_save($pdo, $imageField['key'], '', 'image');
            } elseif (isset($_FILES[$imageField['upload_field']]) && ($_FILES[$imageField['upload_field']]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $upload = content_upload_image($imageField['upload_field'], 'images');
                if (!$upload['success']) {
                    throw new RuntimeException($upload['message'] ?: 'Upload gambar gagal.');
                }
                content_delete_local_file($current);
                content_setting_save($pdo, $imageField['key'], (string) $upload['path'], 'image');
            } elseif (selected_media_path($imageField['upload_field']) !== '') {
                $selected = selected_media_path($imageField['upload_field']);
                content_delete_local_file($current);
                content_setting_save($pdo, $imageField['key'], $selected, 'image');
            }
        }

        if ($postedSection === 'home') {
            $removeOg = isset($_POST['remove_home_og_image']);
            $currentOg = trim((string) get_setting('home_og_image', ''));
            if ($removeOg) {
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'home_og_image', '', 'image');
            } elseif (isset($_FILES['home_og_image_upload']) && ($_FILES['home_og_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $upload = content_upload_image('home_og_image_upload', 'images');
                if (!$upload['success']) {
                    throw new RuntimeException($upload['message'] ?: 'Upload gambar SEO gagal.');
                }
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'home_og_image', (string) $upload['path'], 'image');
            } elseif (selected_media_path('home_og_image_upload') !== '') {
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'home_og_image', selected_media_path('home_og_image_upload'), 'image');
            }
        }

        if ($postedSection === 'products') {
            $currentOg = trim((string) get_setting('products_og_image', ''));
            if (isset($_POST['remove_products_og_image'])) {
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'products_og_image', '', 'image');
            } elseif (isset($_FILES['products_og_image_upload']) && ($_FILES['products_og_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $upload = content_upload_image('products_og_image_upload', 'images');
                if (!$upload['success']) {
                    throw new RuntimeException($upload['message'] ?: 'Upload gambar SEO gagal.');
                }
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'products_og_image', (string) $upload['path'], 'image');
            } elseif (selected_media_path('products_og_image_upload') !== '') {
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'products_og_image', selected_media_path('products_og_image_upload'), 'image');
            }
        }

        if ($postedSection === 'news') {
            $currentOg = trim((string) get_setting('news_og_image', ''));
            if (isset($_POST['remove_news_og_image'])) {
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'news_og_image', '', 'image');
            } elseif (isset($_FILES['news_og_image_upload']) && ($_FILES['news_og_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $upload = content_upload_image('news_og_image_upload', 'images');
                if (!$upload['success']) {
                    throw new RuntimeException($upload['message'] ?: 'Upload gambar SEO gagal.');
                }
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'news_og_image', (string) $upload['path'], 'image');
            } elseif (selected_media_path('news_og_image_upload') !== '') {
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'news_og_image', selected_media_path('news_og_image_upload'), 'image');
            }
        }

        if ($postedSection === 'contact') {
            $currentOg = trim((string) get_setting('contact_image', ''));
            if (isset($_POST['remove_contact_image'])) {
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'contact_image', '', 'image');
            } elseif (isset($_FILES['contact_image_upload']) && ($_FILES['contact_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $upload = content_upload_image('contact_image_upload', 'images');
                if (!$upload['success']) {
                    throw new RuntimeException($upload['message'] ?: 'Upload gambar gagal.');
                }
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'contact_image', (string) $upload['path'], 'image');
            } elseif (selected_media_path('contact_image_upload') !== '') {
                content_delete_local_file($currentOg);
                content_setting_save($pdo, 'contact_image', selected_media_path('contact_image_upload'), 'image');
            }
        }

        if ($postedSection === 'career') {
            $page = content_get_page($pdo, 'career');
            $currentImage = trim((string) ($page['featured_image'] ?? ''));
            $title = trim(post_string('career_title', 'Tumbuh Bersama Star Parts Motor'));
            $excerpt = trim(post_string('career_excerpt'));
            $content = trim(post_string('career_content'));
            $metaTitle = trim(post_string('career_meta_title'));
            $metaDescription = trim(post_string('career_meta_description'));
            $status = post_string('career_status', 'published');
            if (!in_array($status, ['draft', 'published'], true)) {
                $status = 'published';
            }

            $newImage = $currentImage;
            if (isset($_POST['remove_career_image'])) {
                content_delete_local_file($currentImage);
                $newImage = '';
            } elseif (isset($_FILES['career_image_upload']) && ($_FILES['career_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $upload = content_upload_image('career_image_upload', 'images');
                if (!$upload['success']) {
                    throw new RuntimeException($upload['message'] ?: 'Upload gambar karir gagal.');
                }
                content_delete_local_file($currentImage);
                $newImage = (string) $upload['path'];
            } elseif (selected_media_path('career_image_upload') !== '') {
                content_delete_local_file($currentImage);
                $newImage = selected_media_path('career_image_upload');
            }

            content_save_page($pdo, 'career', [
                'title' => $title,
                'excerpt' => $excerpt,
                'content' => $content,
                'featured_image' => $newImage,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'status' => $status,
            ]);
            content_setting_save($pdo, 'career_page_title', $metaTitle !== '' ? $metaTitle : ($title . ' | ' . $siteName), 'text');
            content_setting_save($pdo, 'career_page_description', $metaDescription !== '' ? $metaDescription : ($excerpt !== '' ? $excerpt : content_value('site_description')), 'textarea');
        }

        foreach (['privacy', 'terms'] as $legalSlug) {
            if ($postedSection !== 'legal') {
                break;
            }
            $existing = content_get_page($pdo, $legalSlug);
            $currentImage = trim((string) ($existing['featured_image'] ?? ''));
            $title = trim(post_string($legalSlug . '_title'));
            $excerpt = trim(post_string($legalSlug . '_excerpt'));
            $content = trim(post_string($legalSlug . '_content'));
            $metaTitle = trim(post_string($legalSlug . '_meta_title'));
            $metaDescription = trim(post_string($legalSlug . '_meta_description'));
            $status = post_string($legalSlug . '_status', 'published');
            if (!in_array($status, ['draft', 'published'], true)) {
                $status = 'published';
            }

            content_save_page($pdo, $legalSlug, [
                'title' => $title !== '' ? $title : ucfirst($legalSlug),
                'excerpt' => $excerpt,
                'content' => $content,
                'featured_image' => $currentImage,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'status' => $status,
            ]);
        }

        $pdo->commit();
        set_flash('success', 'Konten halaman berhasil disimpan.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Star Parts Content Center error: ' . $e->getMessage());
        set_flash('error', 'Konten belum tersimpan. ' . $e->getMessage());
    }

    redirect(base_url('admin/content.php?section=' . rawurlencode($postedSection)));
}

$flash = get_flash();

function content_value(string $key, string $default = ''): string
{
    return trim((string) get_setting($key, $default));
}

$aboutImage = content_value('about_image');
$contactImage = content_value('contact_image');
$homeOgImage = content_value('home_og_image');
$productsOgImage = content_value('products_og_image');
$newsOgImage = content_value('news_og_image');
$footerText = content_value('footer_text', '© PT Star Parts Motor');
$ctaTitlePrefix = content_value('cta_title_prefix', 'Siap Menjadi');
$ctaTitleHighlight = content_value('cta_title_highlight', 'Mitra Star Parts Motor?');
$ctaText = content_value('cta_text', 'Mari bangun kerja sama jangka panjang dan tumbuh bersama dalam industri otomotif.');

$careerPage = content_get_page($pdo, 'career');
$privacyPage = content_get_page($pdo, 'privacy');
$termsPage = content_get_page($pdo, 'terms');

$totalBanners = 0;
$totalProducts = 0;
$totalPosts = 0;
try {
    $totalBanners = (int) $pdo->query("SELECT COUNT(*) FROM banners WHERE is_active = 1")->fetchColumn();
    $totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
    $totalPosts = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
} catch (Throwable $e) {}
$missingProductSeo = 0;
$missingPostSeo = 0;
try {
    $missingProductSeo = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND (meta_title IS NULL OR TRIM(meta_title) = '' OR meta_description IS NULL OR TRIM(meta_description) = '')")->fetchColumn();
    $missingPostSeo = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND (meta_title IS NULL OR TRIM(meta_title) = '' OR meta_description IS NULL OR TRIM(meta_description) = '')")->fetchColumn();
} catch (Throwable $e) {}

$seoHealth = [];
$seoHealthAdd = static function(string $label, bool $ok, string $detail, string $tone = 'warning') use (&$seoHealth): void {
    $seoHealth[] = ['label'=>$label,'ok'=>$ok,'detail'=>$detail,'tone'=>$tone];
};
$seoPublicUrl = content_value('site_url');
$seoMetaTitle = content_value('meta_title');
$seoMetaDescription = content_value('meta_description');
$seoPublicIsProduction = $seoPublicUrl !== '' && !preg_match('~^https?://(localhost|127\\.0\\.1)(:\\d+)?(/|$)~i', $seoPublicUrl);
$seoHealthAdd('URL Website Publik', $seoPublicIsProduction, $seoPublicIsProduction ? 'Domain production sudah tersedia.' : 'Isi domain HTTPS/production pada field URL Website Publik sebelum submit ke Search Console.', $seoPublicIsProduction ? 'ok' : 'danger');
$seoHealthAdd('Meta Title', $seoMetaTitle !== '', $seoMetaTitle !== '' ? mb_strlen($seoMetaTitle).' karakter.' : 'Meta title global belum diisi.', $seoMetaTitle !== '' ? 'ok' : 'danger');
$seoHealthAdd('Meta Description', $seoMetaDescription !== '', $seoMetaDescription !== '' ? mb_strlen($seoMetaDescription).' karakter.' : 'Meta description global belum diisi.', $seoMetaDescription !== '' ? 'ok' : 'danger');
$seoHealthAdd('Sitemap & Robots', is_file(dirname(__DIR__).'/sitemap.php') && is_file(dirname(__DIR__).'/robots.txt'), 'sitemap.php dan robots.txt tersedia di project.');
$seoHealthAdd('Konten Indexable', ($totalProducts + $totalPosts) > 0, $totalProducts.' produk aktif dan '.$totalPosts.' berita published.');
$seoHealthAdd('SEO Produk', $missingProductSeo === 0, $missingProductSeo === 0 ? 'Semua produk aktif memiliki SEO title dan description.' : $missingProductSeo.' produk aktif belum lengkap SEO title/description.', $missingProductSeo === 0 ? 'ok' : 'warning');
$seoHealthAdd('SEO Berita', $missingPostSeo === 0, $missingPostSeo === 0 ? 'Semua berita published memiliki SEO title dan description.' : $missingPostSeo.' berita published belum lengkap SEO title/description.', $missingPostSeo === 0 ? 'ok' : 'warning');
$imageOptimizationReady = image_processing_available();
$seoHealthAdd('Responsive WebP', $imageOptimizationReady, $imageOptimizationReady ? 'GD aktif: upload gambar baru dapat membuat varian WebP otomatis.' : 'GD belum aktif; file asli tetap digunakan sampai GD diaktifkan.', $imageOptimizationReady ? 'ok' : 'warning');

function content_admin_active(string $name, string $section): string
{
    return $name === $section ? ' active' : '';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#d90000">
    <title><?= e($pageTitle); ?> | <?= e($siteName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
    <style>
        :root{--red:#d90000;--red-dark:#aa0000;--black:#101010;--muted:#6f6f78;--border:#ececf0;--bg:#f5f6f8;--white:#fff;--shadow:0 10px 30px rgba(10,10,10,.06)}
        *{box-sizing:border-box}
        body{margin:0;font-family:Inter,Arial,sans-serif;background:var(--bg);color:#18181b}
        a{text-decoration:none;color:inherit}
        .admin-app{min-height:100vh;display:flex}
        .sidebar{position:fixed;inset:0 auto 0 0;width:250px;background:#111;color:#fff;padding:22px 15px;overflow:auto;z-index:20}
        .brand{display:flex;align-items:center;gap:12px;padding:8px 10px 26px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:18px}
        .brand img{width:42px;height:42px;border-radius:10px;object-fit:contain;background:#fff}
        .brand-fallback{font-weight:800;line-height:1.05}.brand-fallback small{display:block;opacity:.55;font-size:9px;margin-top:3px;letter-spacing:1.5px}
        .nav-label{font-size:10px;letter-spacing:1.7px;color:rgba(255,255,255,.4);padding:10px 11px}
        .nav{display:grid;gap:5px}
        .nav a{display:flex;align-items:center;gap:11px;padding:11px 12px;border-radius:10px;color:rgba(255,255,255,.72);font-size:13px;font-weight:600}
        .nav a:hover,.nav a.active{color:#fff;background:rgba(217,0,0,.18)}.nav a.active i{color:#fff}.nav i{width:18px;text-align:center;color:#c6c6cb}
        .content{margin-left:250px;width:calc(100% - 250px);min-width:0}
        .topbar{height:72px;background:#fff;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 26px;position:sticky;top:0;z-index:10}
        .topbar-title strong{display:block;font-size:17px}.topbar-title span{font-size:11px;color:var(--muted)}
        .top-actions{display:flex;gap:8px}.top-actions a{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-size:12px;font-weight:700;background:#fff}.top-actions a:hover{border-color:#d5d5db}
        main{padding:25px 26px 45px;max-width:1500px}
        .flash{border-radius:12px;padding:12px 14px;margin-bottom:18px;font-size:13px;font-weight:600}.flash.success{background:#eaf8ef;color:#14763d;border:1px solid #cdebd9}.flash.error{background:#fff0f0;color:#a70000;border:1px solid #f3c7c7}
        .page-intro{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:22px}.eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:10px;letter-spacing:1.7px;font-weight:800;color:var(--red);margin-bottom:8px}.page-intro h1{margin:0;font-size:27px;line-height:1.15}.page-intro p{margin:8px 0 0;color:var(--muted);font-size:13px;max-width:760px}.notice{background:#fff;border:1px solid var(--border);box-shadow:var(--shadow);border-radius:14px;padding:14px 16px;font-size:12px;color:#555;max-width:420px}.notice strong{color:#222}
        .page-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:24px}
        .page-card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:15px;box-shadow:var(--shadow);transition:.2s ease}.page-card:hover{transform:translateY(-1px);border-color:#dedee4}.page-card.active{border-color:rgba(217,0,0,.45);box-shadow:0 12px 30px rgba(217,0,0,.08)}
        .page-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px}.page-icon{width:38px;height:38px;border-radius:10px;background:#fff1f1;color:var(--red);display:flex;align-items:center;justify-content:center}.page-card small{font-size:9px;letter-spacing:1.2px;color:#909098;font-weight:800}.page-card h3{font-size:14px;margin:10px 0 7px}.page-card p{font-size:11px;line-height:1.55;color:var(--muted);min-height:50px;margin:0}.page-link{display:inline-flex;align-items:center;gap:6px;font-size:11px;color:var(--red);font-weight:800;margin-top:11px}.page-public-link{display:inline-flex;align-items:center;gap:6px;font-size:10px;color:#666;font-weight:800;margin-top:8px;cursor:pointer}.page-public-link:hover{color:var(--red)}
        .panel{background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);overflow:hidden}.panel-head{padding:18px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}.panel-head h2{margin:0;font-size:17px}.panel-head p{margin:5px 0 0;color:var(--muted);font-size:11px}.panel-body{padding:20px}
        .grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:15px}.col-12{grid-column:span 12}.col-8{grid-column:span 8}.col-6{grid-column:span 6}.col-4{grid-column:span 4}
        .form-group label{display:block;font-size:11px;font-weight:800;margin-bottom:7px}.form-group label span{color:var(--red)}.form-control{width:100%;border:1px solid #e2e2e7;border-radius:10px;padding:10px 11px;font:inherit;font-size:12px;background:#fff;color:#222;outline:none}.form-control:focus{border-color:rgba(217,0,0,.5);box-shadow:0 0 0 3px rgba(217,0,0,.06)}textarea.form-control{min-height:122px;resize:vertical;line-height:1.6}.small-help{margin-top:6px;color:#888;font-size:10px;line-height:1.5}.counter{font-size:10px;color:#999;margin-top:5px}
        .form-actions{display:flex;gap:9px;align-items:center;flex-wrap:wrap;padding-top:6px}.btn{border:0;border-radius:10px;padding:10px 14px;font-size:12px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:8px}.btn-primary{background:var(--red);color:#fff}.btn-primary:hover{background:var(--red-dark)}.btn-light{background:#f5f5f7;color:#333}.btn-outline{background:#fff;border:1px solid var(--border);color:#333}
        .subhead{font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#777;font-weight:900;margin:24px 0 12px;padding-top:19px;border-top:1px solid var(--border)}.subhead:first-child{margin-top:0;padding-top:0;border-top:0}
        .image-box{border:1px dashed #d7d7de;border-radius:12px;padding:12px;background:#fafafd}.image-preview{width:100%;max-height:270px;display:block;object-fit:contain;background:linear-gradient(45deg,#fafafa 25%,#f2f2f2 25%,#f2f2f2 50%,#fafafa 50%,#fafafa 75%,#f2f2f2 75%);background-size:18px 18px;border-radius:9px}.image-empty{height:150px;display:flex;align-items:center;justify-content:center;color:#999;font-size:11px}.file-input{display:block;width:100%;font-size:11px;margin-top:9px}.check{display:flex;gap:8px;align-items:center;font-size:11px;color:#666;margin-top:8px}
        .audit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:20px}.audit-item{background:#fafafa;border:1px solid var(--border);border-radius:12px;padding:14px}.audit-item strong{display:block;font-size:20px}.audit-item span{display:block;margin-top:4px;font-size:11px;font-weight:800}.audit-item small{display:block;margin-top:5px;color:#888;font-size:10px}.manage-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:13px}.manage-link{display:inline-flex;gap:7px;align-items:center;border:1px solid var(--border);padding:8px 10px;border-radius:9px;font-size:11px;font-weight:800;background:#fff}.manage-link:hover{border-color:#d8d8de}.tip-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:20px}.tip{background:#fafafa;border:1px solid var(--border);border-radius:11px;padding:12px}.tip strong{display:block;font-size:11px;margin-bottom:5px}.tip span{font-size:10px;color:#777;line-height:1.45}
        .status-note{display:inline-flex;align-items:center;gap:6px;font-size:10px;color:#666}.dot{width:7px;height:7px;border-radius:50%;background:#1d9b52;display:inline-block}
        @media (max-width:1100px){.page-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.tip-grid{grid-template-columns:1fr}.col-8,.col-6,.col-4{grid-column:span 12}}
        @media (max-width:780px){.sidebar{width:76px;padding:18px 8px}.brand{justify-content:center;padding-bottom:18px}.brand img{width:40px}.brand-fallback{display:none}.nav-label,.nav a span{display:none}.nav a{justify-content:center;padding:11px}.content{margin-left:76px;width:calc(100% - 76px)}.topbar{padding:0 16px}.top-actions a span{display:none}main{padding:18px 15px 35px}.page-intro{display:block}.notice{max-width:none;margin-top:14px}}
        @media (max-width:560px){.page-grid{grid-template-columns:1fr}.panel-head{display:block}.panel-head .status-note{margin-top:9px}}
    </style>
</head>
<body>
<?php render_admin_sidebar_assets(); ?>
<?php render_admin_sidebar('content.php'); ?>
<div class="admin-app">
    <aside class="sidebar">
        <div class="brand">
            <?php if ($siteLogoUrl !== ''): ?>
                <img src="<?= e($siteLogoUrl); ?>" alt="<?= e($siteName); ?>">
            <?php else: ?>
                <div class="brand-fallback"><strong>STAR PARTS</strong><small>MOTOR</small></div>
            <?php endif; ?>
        </div>
        <div class="nav-label">MAIN MENU</div>
        <nav class="nav">
            <a href="<?= e($baseAdmin); ?>dashboard.php"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
            <a class="active" href="<?= e($baseAdmin); ?>content.php"><i class="fa-solid fa-pen-ruler"></i><span>Content Center</span></a>
            <a href="<?= e($baseAdmin); ?>banners.php"><i class="fa-regular fa-images"></i><span>Banner</span></a>
            <a href="<?= e($baseAdmin); ?>products.php"><i class="fa-solid fa-box-open"></i><span>Produk</span></a>
            <a href="<?= e($baseAdmin); ?>categories.php"><i class="fa-solid fa-layer-group"></i><span>Kategori</span></a>
            <a href="<?= e($baseAdmin); ?>brands.php"><i class="fa-solid fa-tags"></i><span>Brand</span></a>
            <a href="<?= e($baseAdmin); ?>posts.php"><i class="fa-solid fa-newspaper"></i><span>Berita</span></a>
            <a href="<?= e($baseAdmin); ?>media.php"><i class="fa-solid fa-photo-film"></i><span>Media</span></a>
            <div class="nav-label">WEBSITE</div>
            <a href="<?= e($baseAdmin); ?>locations.php"><i class="fa-solid fa-map-location-dot"></i><span>Lokasi</span></a>
            <a href="<?= e($baseAdmin); ?>messages.php"><i class="fa-solid fa-envelope"></i><span>Pesan</span></a>
            <a href="<?= e($baseAdmin); ?>pages.php"><i class="fa-solid fa-file-lines"></i><span>Halaman CMS</span></a>
            <a href="<?= e($baseAdmin); ?>careers.php"><i class="fa-solid fa-briefcase"></i><span>Lowongan Karir</span></a>
            <a href="<?= e($baseAdmin); ?>menus.php"><i class="fa-solid fa-bars"></i><span>Menu Website</span></a>
            <a href="<?= e($baseAdmin); ?>settings.php"><i class="fa-solid fa-gear"></i><span>Pengaturan</span></a>
            <a href="<?= e($baseAdmin); ?>security.php"><i class="fa-solid fa-shield-halved"></i><span>Security</span></a>
            <a href="<?= e($baseAdmin); ?>testimonials.php"><i class="fa-solid fa-comment-dots"></i><span>Testimoni</span></a>
            <a href="<?= e($baseAdmin); ?>logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </nav>
    </aside>

    <div class="content">
        <header class="topbar">
            <div class="topbar-title">
                <strong>Content Center</strong>
                <span>Kelola isi website tanpa menyentuh file PHP</span>
            </div>
            <div class="top-actions">
                <a href="<?= e(base_url('index.php')); ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>Lihat Website</span></a>
                <a href="<?= e($baseAdmin); ?>logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
            </div>
        </header>

        <main>
            <?php if (is_array($flash) && !empty($flash['message'])): ?>
                <div class="flash <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
                    <?= e((string) $flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="page-intro">
                <div>
                    <div class="eyebrow"><i class="fa-solid fa-pen-ruler"></i> WEBSITE CONTENT</div>
                    <h1>Kelola Konten Per Halaman</h1>
                    <p>Pilih halaman di bawah. Field teks, SEO, dan gambar disusun berdasarkan posisi pemakaiannya agar admin tidak perlu menebak gambar harus ditempatkan di mana.</p>
                </div>
                <div class="notice"><strong>Catatan gambar:</strong> gambar asli tidak diregangkan. Area visual mempertahankan komposisi yang sesuai, sedangkan konten gambar mengikuti rasio sumber.</div>
            </div>

            <div class="page-grid">
                <?php foreach ($sections as $key => $info): ?>
                    <a class="page-card<?= content_admin_active($key, $section); ?>" href="<?= e(base_url('admin/content.php?section=' . rawurlencode($key))); ?>">
                        <div class="page-card-head"><div class="page-icon"><i class="fa-solid <?= e($info['icon']); ?>"></i></div><small><?= e($info['label']); ?></small></div>
                        <h3><?= e($info['title']); ?></h3>
                        <p><?= e($info['description']); ?></p>
                        <span class="page-link">Edit konten <i class="fa-solid fa-arrow-right"></i></span>
                        <?php if (!empty($info['public_url'])): ?><span class="page-public-link" onclick="event.preventDefault();event.stopPropagation();window.open('<?= e(site_url($info['public_url'])); ?>','_blank','noopener,noreferrer')"><i class="fa-solid fa-up-right-from-square"></i> Lihat halaman</span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($section === 'home'): ?>
                <form class="panel" method="post" enctype="multipart/form-data">
                    <div class="panel-head"><div><h2>Home / Beranda</h2><p>Konten teks homepage dan SEO. Banner hero tetap dikelola melalui modul Banner agar rotasi lebih mudah.</p></div><span class="status-note"><span class="dot"></span> <?= (int) $totalBanners; ?> banner aktif</span></div>
                    <div class="panel-body">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="action" value="save"><input type="hidden" name="section" value="home">
                        <div class="subhead">SEO Beranda</div>
                        <div class="grid">
                            <div class="form-group col-6"><label>SEO Title</label><input class="form-control" name="home_page_title" maxlength="255" value="<?= e(content_value('home_page_title', content_value('site_name', 'PT Star Parts Motor'))); ?>"><div class="counter">Judul yang ingin ditampilkan pada hasil pencarian.</div></div>
                            <div class="form-group col-6"><label>SEO Description</label><textarea class="form-control" name="home_page_description" maxlength="500"><?= e(content_value('home_page_description', content_value('site_description'))); ?></textarea></div>
                        </div>
                        <div class="subhead">Gambar Sosial / Open Graph</div>
                        <div class="grid"><div class="form-group col-12"><label>Gambar saat Home dibagikan</label><div class="image-box"><?php if ($homeOgImage !== ''): ?><img class="image-preview" src="<?= e(media_url($homeOgImage)); ?>" alt="Home Open Graph image"><label class="check"><input type="checkbox" name="remove_home_og_image"> Hapus gambar saat simpan</label><?php else: ?><div class="image-empty">Belum ada gambar khusus</div><?php endif; ?><input class="file-input" type="file" name="home_og_image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><div class="small-help">Opsional. Gunakan gambar landscape yang jelas untuk preview ketika URL dibagikan.</div></div></div></div>
                        <div class="subhead">Hero</div>
                        <div class="grid">
                            <div class="form-group col-6"><label>Fallback / Judul Hero</label><input class="form-control" name="hero_title" value="<?= e(content_value('hero_title', 'Partner Distribusi Otomotif Terpercaya')); ?>"></div>
                            <div class="form-group col-6"><label>Subjudul Hero</label><textarea class="form-control" name="hero_subtitle"><?= e(content_value('hero_subtitle')); ?></textarea></div>
                            <div class="form-group col-4"><label>Teks Tombol</label><input class="form-control" name="hero_button_text" value="<?= e(content_value('hero_button_text', 'Lihat Produk')); ?>"></div>
                            <div class="form-group col-8"><label>URL Tombol</label><input class="form-control" name="hero_button_url" value="<?= e(content_value('hero_button_url', 'products.php')); ?>"></div>
                        </div>
                        <div class="subhead">Tentang di Homepage</div>
                        <div class="grid">
                            <div class="form-group col-6"><label>Judul</label><input class="form-control" name="about_title" value="<?= e(content_value('about_title', 'Tentang Star Parts Motor')); ?>"></div>
                            <div class="form-group col-6"><label>Gambar Section</label><div class="image-box"><?php if ($aboutImage !== ''): ?><img class="image-preview" src="<?= e(media_url($aboutImage)); ?>" alt="About homepage"><label class="check"><input type="checkbox" name="remove_about_image"> Hapus gambar saat simpan</label><?php else: ?><div class="image-empty">Belum ada gambar</div><?php endif; ?><input class="file-input" type="file" name="about_image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><div class="small-help">Gambar ditempatkan di section Tentang pada Home. Rasio boleh landscape atau portrait; file asli dipertahankan.</div></div></div>
                            <div class="form-group col-12"><label>Konten</label><textarea class="form-control" name="about_content"><?= e(content_value('about_content')); ?></textarea><div class="small-help">Field ini dapat berisi HTML sederhana yang memang sudah dipakai template saat ini.</div></div>
                            <div class="form-group col-6"><label>Visi</label><textarea class="form-control" name="vision"><?= e(content_value('vision')); ?></textarea></div>
                            <div class="form-group col-6"><label>Misi</label><textarea class="form-control" name="mission"><?= e(content_value('mission')); ?></textarea></div>
                        </div>
                        <div class="subhead">Navigasi & Media</div>
                        <div class="manage-row"><a class="manage-link" href="<?= e($baseAdmin); ?>banners.php"><i class="fa-solid fa-images"></i> Kelola Banner Hero</a><a class="manage-link" href="<?= e($baseAdmin); ?>media.php"><i class="fa-solid fa-photo-film"></i> Media Library</a></div>
                                                <div class="subhead">Statistik Homepage</div>
                        <div class="grid"><div class="form-group col-3"><label>Tahun Pengalaman</label><input class="form-control" name="stat_years" value="<?= e(content_value('stat_years', '20+')); ?>"></div><div class="form-group col-3"><label>Mitra Bisnis</label><input class="form-control" name="stat_partners" value="<?= e(content_value('stat_partners', '1000+')); ?>"></div><div class="form-group col-3"><label>Produk</label><input class="form-control" name="stat_products" value="<?= e(content_value('stat_products', '100+')); ?>"></div><div class="form-group col-3"><label>Area Distribusi</label><input class="form-control" name="stat_regions" value="<?= e(content_value('stat_regions', 'Jawa Barat')); ?>"></div></div>

<div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan Home</button></div>
                    </div>
                </form>
            <?php elseif ($section === 'about'): ?>
                <form class="panel" method="post" enctype="multipart/form-data">
                    <div class="panel-head"><div><h2>About Us / Tentang Kami</h2><p>Konten publik halaman About memakai settings yang sama dengan section About di Home.</p></div></div>
                    <div class="panel-body"><?= csrf_input(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="about">
                        <div class="subhead">SEO Halaman</div><div class="grid"><div class="form-group col-6"><label>SEO Title</label><input class="form-control" name="about_page_title" maxlength="255" value="<?= e(content_value('about_page_title', 'Tentang Kami | PT Star Parts Motor')); ?>"></div><div class="form-group col-6"><label>SEO Description</label><textarea class="form-control" name="about_page_description" maxlength="500"><?= e(content_value('about_page_description', content_value('site_description'))); ?></textarea></div></div>
                        <div class="subhead">Hero Halaman About</div><div class="grid"><div class="form-group col-6"><label>Judul Hero</label><input class="form-control" name="about_page_heading" maxlength="255" value="<?= e(content_value('about_page_heading', 'Membangun Distribusi Otomotif yang Lebih Baik')); ?>"></div><div class="form-group col-6"><label>Pengantar Hero</label><textarea class="form-control" name="about_page_intro" maxlength="1000"><?= e(content_value('about_page_intro', content_value('site_description', 'Kenali PT Star Parts Motor dan layanan distribusi produk otomotif.'))); ?></textarea></div></div>
                        <div class="subhead">Konten Utama</div><div class="grid"><div class="form-group col-6"><label>Judul</label><input class="form-control" name="about_title" value="<?= e(content_value('about_title', 'Tentang Star Parts Motor')); ?>"></div><div class="form-group col-6"><label>Gambar Utama</label><div class="image-box"><?php if ($aboutImage !== ''): ?><img class="image-preview" src="<?= e(media_url($aboutImage)); ?>" alt="About image"><label class="check"><input type="checkbox" name="remove_about_image"> Hapus gambar saat simpan</label><?php else: ?><div class="image-empty">Belum ada gambar</div><?php endif; ?><input class="file-input" type="file" name="about_image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><div class="small-help">Tidak ada crop paksa. Gunakan kualitas gambar yang baik dan ukuran file yang wajar.</div></div></div><div class="form-group col-12"><label>Konten</label><textarea class="form-control" name="about_content"><?= e(content_value('about_content')); ?></textarea></div><div class="form-group col-6"><label>Visi</label><textarea class="form-control" name="vision"><?= e(content_value('vision')); ?></textarea></div><div class="form-group col-6"><label>Misi</label><textarea class="form-control" name="mission"><?= e(content_value('mission')); ?></textarea></div><div class="form-group col-6"><label>Judul Riwayat</label><input class="form-control" name="about_history_title" value="<?= e(content_value('about_history_title', 'Membangun Distribusi yang Berkelanjutan')); ?>"></div><div class="form-group col-6"><label>Judul Komitmen</label><input class="form-control" name="about_commitment_title" value="<?= e(content_value('about_commitment_title', 'Komitmen Kami')); ?>"></div><div class="form-group col-6"><label>Isi Riwayat</label><textarea class="form-control" name="about_history_content"><?= e(content_value('about_history_content')); ?></textarea></div><div class="form-group col-6"><label>Isi Komitmen</label><textarea class="form-control" name="about_commitment_content"><?= e(content_value('about_commitment_content')); ?></textarea></div></div>
                        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan About</button></div>
                    </div>
                </form>
            <?php elseif ($section === 'products'): ?>
                <form class="panel" method="post" enctype="multipart/form-data">
                    <div class="panel-head"><div><h2>Product / Katalog</h2><p>SEO halaman katalog ada di sini; visual produk tetap berasal dari data Produk, Kategori, dan Brand.</p></div><span class="status-note"><span class="dot"></span> <?= (int) $totalProducts; ?> produk aktif</span></div><div class="panel-body"><?= csrf_input(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="products">
                        <div class="subhead">SEO Katalog</div><div class="grid"><div class="form-group col-6"><label>SEO Title</label><input class="form-control" name="products_page_title" value="<?= e(content_value('products_page_title', 'Produk | PT Star Parts Motor')); ?>"></div><div class="form-group col-6"><label>SEO Description</label><textarea class="form-control" name="products_page_description" maxlength="500"><?= e(content_value('products_page_description', 'Katalog produk Star Parts Motor.')); ?></textarea></div><div class="form-group col-12"><label>Keyword Internal / Fokus Topik <span style="font-weight:600;color:#999">(bukan faktor ranking Google)</span></label><input class="form-control" name="meta_keywords" value="<?= e(content_value('meta_keywords', 'produk, ban motor, FDR, distributor ban, Star Parts Motor')); ?>"><div class="small-help">Dipertahankan untuk kebutuhan kompatibilitas. Prioritas SEO tetap kualitas konten yang terlihat pengguna.</div></div></div>
                        <div class="subhead">Gambar Sosial / Open Graph</div><div class="grid"><div class="form-group col-12"><label>Gambar saat Katalog dibagikan</label><div class="image-box"><?php if ($productsOgImage !== ''): ?><img class="image-preview" src="<?= e(media_url($productsOgImage)); ?>" alt="Products Open Graph image"><label class="check"><input type="checkbox" name="remove_products_og_image"> Hapus gambar saat simpan</label><?php else: ?><div class="image-empty">Belum ada gambar khusus</div><?php endif; ?><input class="file-input" type="file" name="products_og_image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><div class="small-help">Opsional untuk preview sosial halaman katalog.</div></div></div></div>
                        <div class="subhead">Kelola Konten Produk</div><div class="manage-row"><a class="manage-link" href="<?= e($baseAdmin); ?>products.php"><i class="fa-solid fa-box"></i> Produk</a><a class="manage-link" href="<?= e($baseAdmin); ?>categories.php"><i class="fa-solid fa-layer-group"></i> Kategori</a><a class="manage-link" href="<?= e($baseAdmin); ?>brands.php"><i class="fa-solid fa-tags"></i> Brand</a><a class="manage-link" href="<?= e($baseAdmin); ?>media.php"><i class="fa-solid fa-photo-film"></i> Media Library</a></div>
                        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan SEO Produk</button></div>
                    </div>
                </form>
            <?php elseif ($section === 'news'): ?>
                <form class="panel" method="post" enctype="multipart/form-data">
                    <div class="panel-head"><div><h2>News / Berita</h2><p>SEO halaman daftar berita. Gambar berita dipasang dari modul Berita untuk menjaga konsistensi data.</p></div><span class="status-note"><span class="dot"></span> <?= (int) $totalPosts; ?> berita terbit</span></div><div class="panel-body"><?= csrf_input(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="news">
                        <div class="subhead">SEO Berita</div><div class="grid"><div class="form-group col-6"><label>Judul Halaman</label><input class="form-control" name="news_page_title" value="<?= e(content_value('news_page_title', 'Berita & Aktivitas | PT Star Parts Motor')); ?>"></div><div class="form-group col-6"><label>Deskripsi</label><textarea class="form-control" name="news_page_description" maxlength="500"><?= e(content_value('news_page_description', 'Berita, informasi produk, aktivitas dan perkembangan terbaru PT Star Parts Motor.')); ?></textarea></div></div>
                        <div class="subhead">Gambar Sosial / Open Graph</div><div class="grid"><div class="form-group col-12"><label>Gambar saat Berita dibagikan</label><div class="image-box"><?php if ($newsOgImage !== ''): ?><img class="image-preview" src="<?= e(media_url($newsOgImage)); ?>" alt="News Open Graph image"><label class="check"><input type="checkbox" name="remove_news_og_image"> Hapus gambar saat simpan</label><?php else: ?><div class="image-empty">Belum ada gambar khusus</div><?php endif; ?><input class="file-input" type="file" name="news_og_image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><div class="small-help">Opsional untuk preview sosial halaman berita.</div></div></div></div>
                        <div class="subhead">Konten & Gambar</div><div class="manage-row"><a class="manage-link" href="<?= e($baseAdmin); ?>posts.php"><i class="fa-solid fa-newspaper"></i> Kelola Berita & Featured Image</a><a class="manage-link" href="<?= e($baseAdmin); ?>media.php"><i class="fa-solid fa-photo-film"></i> Media Library</a></div>
                        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan SEO Berita</button></div>
                    </div>
                </form>
            <?php elseif ($section === 'career'): ?>
                <?php $careerImage = trim((string) ($careerPage['featured_image'] ?? '')); ?>
                <form class="panel" method="post" enctype="multipart/form-data">
                    <div class="panel-head"><div><h2>Career / Karir</h2><p>Halaman karir memakai record `pages` dengan slug <strong>career</strong>, sehingga konten dan featured image bisa dikelola langsung.</p></div></div><div class="panel-body"><?= csrf_input(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="career">
                        <div class="subhead">SEO</div><div class="grid"><div class="form-group col-6"><label>SEO Meta Title</label><input class="form-control" name="career_meta_title" value="<?= e((string) ($careerPage['meta_title'] ?? content_value('career_page_title', 'Karir | PT Star Parts Motor'))); ?>"></div><div class="form-group col-6"><label>SEO Meta Description</label><textarea class="form-control" name="career_meta_description" maxlength="500"><?= e((string) ($careerPage['meta_description'] ?? content_value('career_page_description', content_value('site_description')))); ?></textarea></div></div>
                        <div class="subhead">Isi Halaman</div><div class="grid"><div class="form-group col-8"><label>Judul</label><input class="form-control" name="career_title" value="<?= e((string) ($careerPage['title'] ?? 'Tumbuh Bersama Star Parts Motor')); ?>"></div><div class="form-group col-4"><label>Status</label><select class="form-control" name="career_status"><option value="published" <?= (($careerPage['status'] ?? 'published') === 'published') ? 'selected' : ''; ?>>Published</option><option value="draft" <?= (($careerPage['status'] ?? '') === 'draft') ? 'selected' : ''; ?>>Draft</option></select></div><div class="form-group col-8"><label>Ringkasan</label><textarea class="form-control" name="career_excerpt"><?= e((string) ($careerPage['excerpt'] ?? '')); ?></textarea></div><div class="form-group col-4"><label>Gambar Utama</label><div class="image-box"><?php if ($careerImage !== ''): ?><img class="image-preview" src="<?= e(media_url($careerImage)); ?>" alt="Career image"><label class="check"><input type="checkbox" name="remove_career_image"> Hapus gambar</label><?php else: ?><div class="image-empty">Belum ada gambar</div><?php endif; ?><input class="file-input" type="file" name="career_image_upload" accept="image/jpeg,image/png,image/webp,image/gif"></div></div><div class="form-group col-12"><label>Konten Karir</label><textarea class="form-control" name="career_content"><?= e((string) ($careerPage['content'] ?? '')); ?></textarea></div></div>
                        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan Career</button><a class="btn btn-light" href="<?= e($baseAdmin); ?>pages.php"><i class="fa-solid fa-file-lines"></i>Lihat Semua Halaman CMS</a></div>
                    </div>
                </form>
            <?php elseif ($section === 'contact'): ?>
                <form class="panel" method="post" enctype="multipart/form-data">
                    <div class="panel-head"><div><h2>Contact / Hubungi Kami</h2><p>Metadata dan gambar halaman kontak. Nomor telepon, WhatsApp, email, alamat, lokasi, dan pesan dikelola dari modul masing-masing.</p></div></div><div class="panel-body"><?= csrf_input(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="contact">
                        <div class="subhead">SEO Halaman</div><div class="grid"><div class="form-group col-6"><label>Judul</label><input class="form-control" name="contact_page_title" value="<?= e(content_value('contact_page_title', content_value('site_name') . ' | Hubungi Kami')); ?>"></div><div class="form-group col-6"><label>Deskripsi</label><textarea class="form-control" name="contact_page_description" maxlength="500"><?= e(content_value('contact_page_description', 'Hubungi PT Star Parts Motor untuk informasi produk, distribusi dan peluang kerja sama.')); ?></textarea></div><div class="form-group col-12"><label>Gambar Halaman Kontak</label><div class="image-box"><?php if ($contactImage !== ''): ?><img class="image-preview" src="<?= e(media_url($contactImage)); ?>" alt="Contact page image"><label class="check"><input type="checkbox" name="remove_contact_image"> Hapus gambar saat simpan</label><?php else: ?><div class="image-empty">Belum ada gambar</div><?php endif; ?><input class="file-input" type="file" name="contact_image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><div class="small-help">Bisa dipakai sebagai gambar pendamping di halaman kontak pada tahap integrasi publik.</div></div></div></div>
                        <div class="subhead">Data Operasional</div><div class="manage-row"><a class="manage-link" href="<?= e($baseAdmin); ?>settings.php"><i class="fa-solid fa-gear"></i> Telepon / WhatsApp / Email</a><a class="manage-link" href="<?= e($baseAdmin); ?>locations.php"><i class="fa-solid fa-map-location-dot"></i> Lokasi</a><a class="manage-link" href="<?= e($baseAdmin); ?>messages.php"><i class="fa-solid fa-inbox"></i> Pesan Masuk</a></div>
                        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan Contact</button></div>
                    </div>
                </form>
            <?php elseif ($section === 'legal'): ?>
                <form class="panel" method="post">
                    <div class="panel-head"><div><h2>Privacy Policy & Terms</h2><p>Edit dua halaman legal tanpa harus membuka file PHP. Slug dipertahankan sebagai `privacy` dan `terms`.</p></div></div><div class="panel-body"><?= csrf_input(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="legal">
                        <?php foreach (['privacy' => $privacyPage, 'terms' => $termsPage] as $slug => $page): ?>
                            <div class="subhead"><?= strtoupper(e($slug)); ?></div><div class="grid"><div class="form-group col-8"><label>Judul</label><input class="form-control" name="<?= e($slug); ?>_title" value="<?= e((string) ($page['title'] ?? ucfirst($slug))); ?>"></div><div class="form-group col-4"><label>Status</label><select class="form-control" name="<?= e($slug); ?>_status"><option value="published" <?= (($page['status'] ?? 'published') === 'published') ? 'selected' : ''; ?>>Published</option><option value="draft" <?= (($page['status'] ?? '') === 'draft') ? 'selected' : ''; ?>>Draft</option></select></div><div class="form-group col-12"><label>Ringkasan</label><textarea class="form-control" name="<?= e($slug); ?>_excerpt"><?= e((string) ($page['excerpt'] ?? '')); ?></textarea></div><div class="form-group col-6"><label>Meta Title</label><input class="form-control" name="<?= e($slug); ?>_meta_title" value="<?= e((string) ($page['meta_title'] ?? '')); ?>"></div><div class="form-group col-6"><label>Meta Description</label><textarea class="form-control" name="<?= e($slug); ?>_meta_description" maxlength="500"><?= e((string) ($page['meta_description'] ?? '')); ?></textarea></div><div class="form-group col-12"><label>Konten</label><textarea class="form-control" name="<?= e($slug); ?>_content"><?= e((string) ($page['content'] ?? '')); ?></textarea></div></div>
                        <?php endforeach; ?>
                        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan Legal Pages</button><a class="btn btn-light" href="<?= e($baseAdmin); ?>pages.php"><i class="fa-solid fa-file-lines"></i>Halaman CMS</a></div>
                    </div>
                </form>
            <?php elseif ($section === 'seo'): ?>
                <form class="panel" method="post">
                    <div class="panel-head"><div><h2>SEO Global</h2><p>Nilai default yang dapat dipakai oleh halaman ketika metadata khusus belum tersedia.</p></div></div><div class="panel-body"><?= csrf_input(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="seo">
                        <div class="subhead">Domain & Verifikasi</div><div class="grid">
                            <div class="form-group col-8"><label>URL Website Publik</label><input class="form-control" type="url" name="site_url" placeholder="https://www.starpartsmotor.com/" value="<?= e(content_value('site_url', '')); ?>"><div class="small-help">Isi domain production. Nilai ini dipakai untuk canonical, Open Graph, schema, dan sitemap. Biarkan kosong hanya saat development lokal.</div></div>
                            <div class="form-group col-4"><label>Google Site Verification</label><input class="form-control" name="google_site_verification" value="<?= e(content_value('google_site_verification', '')); ?>"><div class="small-help">Opsional. Isi token meta verifikasi dari Google Search Console.</div></div>
                        </div>
                        <div class="subhead">Metadata Dasar</div><div class="grid"><div class="form-group col-12"><label>Meta Title Global</label><input class="form-control" name="meta_title" maxlength="255" value="<?= e(content_value('meta_title', content_value('site_name', 'PT Star Parts Motor'))); ?>"></div><div class="form-group col-12"><label>Meta Description Global</label><textarea class="form-control" name="meta_description" maxlength="500"><?= e(content_value('meta_description', content_value('site_description'))); ?></textarea></div><div class="form-group col-12"><label>Keyword Internal / Fokus Topik</label><input class="form-control" name="meta_keywords" value="<?= e(content_value('meta_keywords', 'ban motor, sparepart motor, distributor otomotif, Star Parts Motor')); ?>"></div></div>
                        <div class="subhead">Optimasi Gambar Otomatis</div><div class="grid">
                            <div class="form-group col-4"><label>Kualitas WebP</label><input class="form-control" type="number" name="image_webp_quality" min="50" max="95" value="<?= e(content_value('image_webp_quality', '82')); ?>"><div class="small-help">Nilai 50–95. Nilai tinggi memberi kualitas lebih baik dengan ukuran file lebih besar.</div></div>
                            <div class="form-group col-8"><label>Lebar Varian Responsive</label><input class="form-control" name="image_variant_widths" value="<?= e(content_value('image_variant_widths', '480,768,1200,1600,1920')); ?>"><div class="small-help">Pisahkan dengan koma. File asli tidak diubah dan tidak di-crop.</div></div>
                        </div>
                        <div class="subhead">Catatan SEO</div><div class="tip-grid"><div class="tip"><strong>Konten manusia</strong><span>Prioritaskan isi yang membantu calon pelanggan, bukan penumpukan kata kunci.</span></div><div class="tip"><strong>Judul & deskripsi unik</strong><span>Setiap halaman utama sebaiknya memiliki fokus topik dan metadata yang jelas.</span></div><div class="tip"><strong>Gambar informatif</strong><span>Gunakan nama file yang masuk akal dan alt text yang menjelaskan konteks gambar.</span></div></div>
                        <div class="subhead" id="seo-health">SEO Health Check</div><div class="tip-grid">
                            <?php foreach ($seoHealth as $health): ?>
                                <div class="tip"><strong><?= e($health['label']); ?></strong><span><?= e($health['detail']); ?></span><div style="margin-top:8px;font-size:10px;font-weight:900;color:<?= $health['ok'] ? '#16733d' : ($health['tone'] === 'danger' ? '#a70000' : '#8a5a00'); ?>;"><?= $health['ok'] ? '● OK' : ($health['tone'] === 'danger' ? '● PERLU DICEK' : '● PERHATIAN'); ?></div></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="subhead" id="seo-audit">Audit Konten</div><div class="audit-grid"><div class="audit-item"><strong><?= (int)$totalProducts; ?></strong><span>Produk aktif</span><small><?= (int)$missingProductSeo; ?> belum lengkap SEO</small></div><div class="audit-item"><strong><?= (int)$totalPosts; ?></strong><span>Berita published</span><small><?= (int)$missingPostSeo; ?> belum lengkap SEO</small></div><div class="audit-item"><strong><?= (int)$totalBanners; ?></strong><span>Banner aktif</span><small>Kelola rotasi di modul Banner</small></div></div>
                        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan SEO Global</button></div>
                    </div>
                </form>
            <?php endif; ?>
        </main>
    </div>
</div>
<script>
document.querySelectorAll('input[type=file]').forEach(function(input){
    input.addEventListener('change', function(){
        const file = input.files && input.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = function(e){
            const box = input.closest('.image-box');
            if (!box) return;
            let preview = box.querySelector('.image-preview');
            if (!preview) {
                const empty = box.querySelector('.image-empty');
                if (empty) empty.remove();
                preview = document.createElement('img');
                preview.className = 'image-preview';
                box.prepend(preview);
            }
            preview.src = e.target.result;
            preview.alt = file.name;
        };
        reader.readAsDataURL(file);
    });
});
</script>
</body>
</html>
