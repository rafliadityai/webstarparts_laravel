<?php
// =========================================================
// includes/functions.php
// Helper & fungsi utama website PT Star Parts Motor
// =========================================================

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| LOAD CONFIG
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {

    function e(?string $value): string
    {
        return htmlspecialchars(
            $value ?? '',
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/*
|--------------------------------------------------------------------------
| CONTACT SETTINGS HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('normalize_indonesian_phone')) {
    function normalize_indonesian_phone(mixed $value, bool $whatsapp = false): string
    {
        $number = preg_replace('/[^0-9]/', '', (string) $value) ?? '';

        if ($whatsapp && str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }

        return $number;
    }
}

if (!function_exists('get_whatsapp_message')) {
    function get_whatsapp_message(): string
    {
        $message = trim((string) get_setting(
            'whatsapp_message',
            'Halo Star Parts Motor, saya dapat nomor ini dari website dan ingin bertanya.'
        ));

        return $message !== ''
            ? $message
            : 'Halo Star Parts Motor, saya dapat nomor ini dari website dan ingin bertanya.';
    }
}

if (!function_exists('get_whatsapp_link')) {
    function get_whatsapp_link(?string $message = null): string
    {
        $number = normalize_indonesian_phone(
            get_setting('whatsapp', ''),
            true
        );

        if ($number === '') {
            return '#';
        }

        return 'https://wa.me/' . $number . '?text=' . rawurlencode(
            $message ?? get_whatsapp_message()
        );
    }
}


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

if (!function_exists('base_url')) {

    function base_url(string $path = ''): string
    {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}



/*
|--------------------------------------------------------------------------
| ABSOLUTE URL
|--------------------------------------------------------------------------
*/

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        $configured = '';
        try {
            $configured = trim((string) get_setting('site_url', ''));
        } catch (Throwable $e) {
            $configured = '';
        }

        $activeHost = strtolower((string) (parse_url(BASE_URL, PHP_URL_HOST) ?: ''));
        $activeScheme = strtolower((string) (parse_url(BASE_URL, PHP_URL_SCHEME) ?: 'http'));
        $configuredHost = strtolower((string) (parse_url($configured, PHP_URL_HOST) ?: ''));
        $configuredScheme = strtolower((string) (parse_url($configured, PHP_URL_SCHEME) ?: 'http'));
        $configuredHostIsLocal = in_array($configuredHost, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($configuredHost, '.localhost')
            || str_ends_with($configuredHost, '.test');
        $activeHostIsLocal = in_array($activeHost, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($activeHost, '.localhost')
            || str_ends_with($activeHost, '.test');

        // Database yang dipindahkan dari XAMPP kadang masih menyimpan site_url localhost.
        if ($configured !== '' && $configuredHostIsLocal && !$activeHostIsLocal) {
            $configured = '';
        } elseif ($configured !== '' && $configuredHost !== '' && $configuredHost === $activeHost && $activeScheme === 'https' && $configuredScheme === 'http') {
            // Hindari canonical/sitemap HTTP saat website aktif melalui HTTPS pada host yang sama.
            $configured = preg_replace('~^http://~i', 'https://', $configured) ?: $configured;
        }

        $root = $configured !== '' ? $configured : BASE_URL;
        $root = rtrim($root, '/') . '/';

        if ($path === '') {
            return $root;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return $root . ltrim($path, '/');
    }
}

if (!function_exists('absolute_url')) {
    function absolute_url(string $path = ''): string
    {
        $path = trim($path);
        if ($path === '') return site_url();
        if (preg_match('~^https?://~i', $path)) return $path;
        return site_url($path);
    }
}

if (!function_exists('product_url')) {
    function product_url(string $slug): string
    {
        return site_url('produk/' . rawurlencode(trim($slug)));
    }
}

if (!function_exists('post_url')) {
    /**
     * Public article URL that does not require Apache mod_rewrite.
     * Prefer the database ID for stable routing; slug remains a fallback
     * for legacy callers and older pretty URLs.
     */
    function post_url(string $slug, ?int $id = null): string
    {
        if ($id !== null && $id > 0) {
            return site_url('news-detail.php?id=' . $id);
        }

        $slug = trim($slug);
        if ($slug !== '') {
            return site_url('news-detail.php?slug=' . rawurlencode($slug));
        }

        return site_url('news.php');
    }
}

/*
|--------------------------------------------------------------------------
| PUBLIC CMS PAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('get_page_by_slug')) {
    function get_page_by_slug(string $slug, bool $publishedOnly = true): ?array
    {
        global $pdo;
        $sql = "SELECT * FROM pages WHERE slug = ?";
        if ($publishedOnly) $sql .= " AND status = 'published'";
        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }
}

/*
|--------------------------------------------------------------------------
| SEO TEXT
|--------------------------------------------------------------------------
*/

if (!function_exists('seo_clean_text')) {
    function seo_clean_text(string $text, int $maxLength = 0): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?: '';
        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = rtrim(mb_substr($text, 0, max(1, $maxLength - 3))) . '...';
        }
        return $text;
    }
}

/*
|--------------------------------------------------------------------------
| IMAGE DIMENSIONS
|--------------------------------------------------------------------------
*/

if (!function_exists('get_image_dimensions')) {
    function get_image_dimensions(?string $relativePath): array
    {
        static $cache = [];
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '') return ['width'=>null,'height'=>null,'ratio'=>null];
        if (array_key_exists($relativePath, $cache)) return $cache[$relativePath];
        $out = ['width'=>null,'height'=>null,'ratio'=>null];
        if (str_starts_with($relativePath, 'uploads/')) {
            $root = realpath(dirname(__DIR__) . '/uploads');
            $file = realpath(dirname(__DIR__) . '/' . ltrim($relativePath, '/'));
            if ($root !== false && $file !== false && is_file($file)) {
                $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                if (str_starts_with($file, $prefix)) {
                    $info = @getimagesize($file);
                    if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
                        $out['width'] = (int)$info[0];
                        $out['height'] = (int)$info[1];
                        $out['ratio'] = $out['width'] / $out['height'];
                    }
                }
            }
        }
        return $cache[$relativePath] = $out;
    }
}

/*
|--------------------------------------------------------------------------
| IMAGE PROCESSING / RESPONSIVE VARIANTS
|--------------------------------------------------------------------------
*/

if (!function_exists('image_processing_available')) {
    function image_processing_available(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagewebp');
    }
}

if (!function_exists('image_processing_quality')) {
    function image_processing_quality(): int
    {
        $quality = (int) get_setting('image_webp_quality', 82);
        return max(50, min(95, $quality));
    }
}

if (!function_exists('image_variant_widths')) {
    function image_variant_widths(): array
    {
        $configured = trim((string) get_setting('image_variant_widths', '480,768,1200,1600,1920'));
        $parts = preg_split('/\s*,\s*/', $configured) ?: [];
        $widths = [];
        foreach ($parts as $part) {
            $width = (int) $part;
            if ($width >= 320 && $width <= 2400) {
                $widths[] = $width;
            }
        }
        $widths = array_values(array_unique($widths));
        sort($widths);
        return $widths ?: [480, 768, 1200, 1600, 1920];
    }
}

if (!function_exists('image_create_source')) {
    function image_create_source(string $path, string $mime): mixed
    {
        if (!image_processing_available()) return null;
        return match (strtolower($mime)) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : null,
            'image/png'  => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default      => null,
        };
    }
}

if (!function_exists('delete_image_variants')) {
    function delete_image_variants(string $absolutePath): void
    {
        if ($absolutePath === '') return;
        $dir = dirname($absolutePath);
        $base = pathinfo($absolutePath, PATHINFO_FILENAME);
        foreach (image_variant_widths() as $width) {
            $variant = $dir . DIRECTORY_SEPARATOR . $base . '--' . $width . '.webp';
            if (is_file($variant)) @unlink($variant);
        }
    }
}

if (!function_exists('generate_image_variants')) {
    function generate_image_variants(string $absolutePath): array
    {
        if (!is_file($absolutePath) || !image_processing_available()) return [];
        if (!function_exists('getimagesize')) return [];

        $info = @getimagesize($absolutePath);
        if (!is_array($info) || empty($info[0]) || empty($info[1])) return [];
        $width = (int) $info[0];
        $height = (int) $info[1];
        $mime = strtolower((string)($info['mime'] ?? ''));
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) return [];

        $source = image_create_source($absolutePath, $mime);
        if (!$source) return [];

        $dir = dirname($absolutePath);
        $base = pathinfo($absolutePath, PATHINFO_FILENAME);
        $created = [];
        $quality = image_processing_quality();

        foreach (image_variant_widths() as $targetWidth) {
            if ($targetWidth >= $width) continue;
            $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));
            $targetPath = $dir . DIRECTORY_SEPARATOR . $base . '--' . $targetWidth . '.webp';
            if (is_file($targetPath) && (int)@filemtime($targetPath) >= (int)@filemtime($absolutePath)) {
                $created[] = str_replace(DIRECTORY_SEPARATOR, '/', $targetPath);
                continue;
            }

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if (!$canvas) continue;

            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            if (@imagewebp($canvas, $targetPath, $quality)) {
                $created[] = str_replace(DIRECTORY_SEPARATOR, '/', $targetPath);
            }
            imagedestroy($canvas);
        }

        imagedestroy($source);
        return $created;
    }
}

if (!function_exists('media_image_srcset')) {
    function media_image_srcset(?string $relativePath): string
    {
        $relativePath = trim((string)$relativePath);
        if ($relativePath === '' || !str_starts_with($relativePath, 'uploads/')) return '';

        $local = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
        if (!is_file($local)) return '';

        $base = pathinfo($local, PATHINFO_FILENAME);
        $dir = dirname($local);
        $items = [];
        foreach (image_variant_widths() as $width) {
            $candidate = $dir . DIRECTORY_SEPARATOR . $base . '--' . $width . '.webp';
            if (is_file($candidate)) {
                $urlPath = preg_replace('~^.*?[\\/]uploads[\\/]~', 'uploads/', str_replace('\\', '/', $candidate));
                if (is_string($urlPath)) $items[] = media_url($urlPath) . ' ' . $width . 'w';
            }
        }
        return implode(', ', $items);
    }
}

/*
|--------------------------------------------------------------------------
| IMAGE ATTRIBUTES
|--------------------------------------------------------------------------
*/

if (!function_exists('media_image_attrs')) {
    function media_image_attrs(
        ?string $relativePath,
        string $alt = '',
        ?string $class = null,
        string $loading = 'lazy',
        bool $fetchPriorityHigh = false,
        ?string $sizes = null
    ): string {
        $attrs = ['alt="' . e($alt) . '"'];
        $dims = get_image_dimensions($relativePath);
        if ($class !== null && trim($class) !== '') $attrs[] = 'class="' . e($class) . '"';
        if (!empty($dims['width'])) $attrs[] = 'width="' . (int)$dims['width'] . '"';
        if (!empty($dims['height'])) $attrs[] = 'height="' . (int)$dims['height'] . '"';
        $srcset = media_image_srcset($relativePath);
        if ($srcset !== '') {
            $attrs[] = 'srcset="' . e($srcset) . '"';
            $attrs[] = 'sizes="' . e($sizes !== null && trim($sizes) !== '' ? $sizes : '100vw') . '"';
        }
        if ($loading !== '') $attrs[] = 'loading="' . e($loading) . '"';
        $attrs[] = 'decoding="async"';
        if ($fetchPriorityHigh) $attrs[] = 'fetchpriority="high"';
        return implode(' ', $attrs);
    }
}


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

if (!function_exists('redirect')) {

    function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CURRENT URL
|--------------------------------------------------------------------------
*/

if (!function_exists('current_url')) {

    function current_url(): string
    {
        $https = (
            (!empty($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] !== 'off')
            ||
            (
                isset($_SERVER['SERVER_PORT']) &&
                (int) $_SERVER['SERVER_PORT'] === 443
            )
        );

        $protocol = $https ? 'https://' : 'http://';

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri  = $_SERVER['REQUEST_URI'] ?? '';

        return $protocol . $host . $uri;
    }
}


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if (!function_exists('is_post')) {

    function is_post(): bool
    {
        return strtoupper(
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ) === 'POST';
    }
}


/*
|--------------------------------------------------------------------------
| GET PARAMETER
|--------------------------------------------------------------------------
*/

if (!function_exists('get_string')) {

    function get_string(
        string $key,
        string $default = ''
    ): string {

        if (!isset($_GET[$key])) {
            return $default;
        }

        return trim(
            (string) $_GET[$key]
        );
    }
}


/*
|--------------------------------------------------------------------------
| POST PARAMETER
|--------------------------------------------------------------------------
*/

if (!function_exists('post_string')) {

    function post_string(
        string $key,
        string $default = ''
    ): string {

        if (!isset($_POST[$key])) {
            return $default;
        }

        return trim(
            (string) $_POST[$key]
        );
    }
}


/*
|--------------------------------------------------------------------------
| POST INTEGER PARAMETER
|--------------------------------------------------------------------------
| Read integer IDs sent by POST forms (e.g. edit/delete actions). This is
| intentionally separate from get_int(), which only reads query-string data.
|--------------------------------------------------------------------------
*/

if (!function_exists('post_int')) {

    function post_int(
        string $key,
        int $default = 0
    ): int {

        if (!isset($_POST[$key]) || is_array($_POST[$key])) {
            return $default;
        }

        $value = filter_var($_POST[$key], FILTER_VALIDATE_INT);

        return $value === false ? $default : (int) $value;
    }
}


/*
|--------------------------------------------------------------------------
| INTEGER PARAMETER
|--------------------------------------------------------------------------
*/

if (!function_exists('get_int')) {

    function get_int(
        string $key,
        int $default = 0
    ): int {

        if (!isset($_GET[$key])) {
            return $default;
        }

        return (int) $_GET[$key];
    }
}


/*
|--------------------------------------------------------------------------
| SLUG
|--------------------------------------------------------------------------
*/

if (!function_exists('slugify')) {

    function slugify(string $text): string
    {
        $text = trim($text);

        $text = function_exists('iconv')
            ? iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $text
            )
            : $text;

        $text = strtolower($text);

        $text = preg_replace(
            '/[^a-z0-9]+/',
            '-',
            $text
        );

        $text = trim(
            (string) $text,
            '-'
        );

        return $text !== ''
            ? $text
            : 'item';
    }
}


/*
|--------------------------------------------------------------------------
| GET SITE SETTING
|--------------------------------------------------------------------------
*/

if (!function_exists('get_setting')) {

    function get_setting(
        string $key,
        mixed $default = null
    ): mixed {

        static $settings = null;

        if ($settings === null) {

            $settings = [];

            try {

                global $pdo;

                $stmt = $pdo->query(
                    "SELECT setting_key, setting_value
                     FROM site_settings"
                );

                $rows = $stmt->fetchAll();

                foreach ($rows as $row) {

                    $settings[
                        $row['setting_key']
                    ] = $row['setting_value'];
                }

            } catch (Throwable $e) {

                return $default;
            }
        }

        return array_key_exists(
            $key,
            $settings
        )
            ? $settings[$key]
            : $default;
    }
}


/*
|--------------------------------------------------------------------------
| GET MENU
|--------------------------------------------------------------------------
*/

if (!function_exists('get_menus')) {

    function get_menus(
        ?int $parentId = null
    ): array {

        global $pdo;

        if ($parentId === null) {

            $stmt = $pdo->prepare(
                "SELECT *
                 FROM menus
                 WHERE parent_id IS NULL
                 AND is_active = 1
                 ORDER BY sort_order ASC, id ASC"
            );

            $stmt->execute();

        } else {

            $stmt = $pdo->prepare(
                "SELECT *
                 FROM menus
                 WHERE parent_id = ?
                 AND is_active = 1
                 ORDER BY sort_order ASC, id ASC"
            );

            $stmt->execute([
                $parentId
            ]);
        }

        return $stmt->fetchAll();
    }
}


/*
|--------------------------------------------------------------------------
| GET CHILD MENU
|--------------------------------------------------------------------------
*/

if (!function_exists('get_child_menus')) {

    function get_child_menus(
        int $parentId
    ): array {

        return get_menus(
            $parentId
        );
    }
}


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

if (!function_exists('get_categories')) {

    function get_categories(
        bool $activeOnly = true
    ): array {

        global $pdo;

        if ($activeOnly) {

            $stmt = $pdo->query(
                "SELECT *
                 FROM product_categories
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, name ASC"
            );

        } else {

            $stmt = $pdo->query(
                "SELECT *
                 FROM product_categories
                 ORDER BY sort_order ASC, name ASC"
            );
        }

        return $stmt->fetchAll();
    }
}


/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
*/

if (!function_exists('get_products')) {

    function get_products(
        ?int $categoryId = null,
        bool $featuredOnly = false,
        int $limit = 0
    ): array {

        global $pdo;

        $sql = "
            SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug
            FROM products p
            LEFT JOIN product_categories c
                ON c.id = p.category_id
            WHERE p.is_active = 1
        ";

        $params = [];

        if ($categoryId !== null) {

            $sql .= "
                AND p.category_id = ?
            ";

            $params[] = $categoryId;
        }

        if ($featuredOnly) {

            $sql .= "
                AND p.is_featured = 1
            ";
        }

        $sql .= "
            ORDER BY
                p.sort_order ASC,
                p.id DESC
        ";

        if ($limit > 0) {

            $sql .= "
                LIMIT ?
            ";

            $params[] = $limit;
        }

        $stmt = $pdo->prepare($sql);

        $index = 1;

        foreach ($params as $param) {

            if (
                $limit > 0
                &&
                $index === count($params)
            ) {

                $stmt->bindValue(
                    $index,
                    (int) $param,
                    PDO::PARAM_INT
                );

            } elseif (is_int($param)) {

                $stmt->bindValue(
                    $index,
                    $param,
                    PDO::PARAM_INT
                );

            } else {

                $stmt->bindValue(
                    $index,
                    $param,
                    PDO::PARAM_STR
                );
            }

            $index++;
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }
}


/*
|--------------------------------------------------------------------------
| GET SINGLE PRODUCT
|--------------------------------------------------------------------------
*/

if (!function_exists('get_product')) {

    function get_product(
        int $id
    ): ?array {

        global $pdo;

        $stmt = $pdo->prepare(
            "SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug
             FROM products p
             LEFT JOIN product_categories c
                ON c.id = p.category_id
             WHERE p.id = ?
             LIMIT 1"
        );

        $stmt->execute([
            $id
        ]);

        $product = $stmt->fetch();

        return $product ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT BY SLUG
|--------------------------------------------------------------------------
*/

if (!function_exists('get_product_by_slug')) {

    function get_product_by_slug(
        string $slug
    ): ?array {

        global $pdo;

        $stmt = $pdo->prepare(
            "SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug
             FROM products p
             LEFT JOIN product_categories c
                ON c.id = p.category_id
             WHERE p.slug = ?
             AND p.is_active = 1
             LIMIT 1"
        );

        $stmt->execute([
            $slug
        ]);

        $product = $stmt->fetch();

        return $product ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| GET POSTS
|--------------------------------------------------------------------------
*/

if (!function_exists('get_posts')) {

    function get_posts(
        int $limit = 0
    ): array {

        global $pdo;

        $sql = "
            SELECT *
            FROM posts
            WHERE status = 'published'
            ORDER BY
                COALESCE(
                    published_at,
                    created_at
                ) DESC,
                id DESC
        ";

        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }
}


/*
|--------------------------------------------------------------------------
| GET SINGLE POST
|--------------------------------------------------------------------------
*/

if (!function_exists('get_post')) {

    function get_post(
        int $id
    ): ?array {

        global $pdo;

        $stmt = $pdo->prepare(
            "SELECT *
             FROM posts
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->execute([
            $id
        ]);

        $post = $stmt->fetch();

        return $post ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| GET POST BY SLUG
|--------------------------------------------------------------------------
*/

if (!function_exists('get_post_by_slug')) {

    function get_post_by_slug(
        string $slug
    ): ?array {

        global $pdo;

        $stmt = $pdo->prepare(
            "SELECT *
             FROM posts
             WHERE slug = ?
             AND status = 'published'
             LIMIT 1"
        );

        $stmt->execute([
            $slug
        ]);

        $post = $stmt->fetch();

        return $post ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| GET BRANDS
|--------------------------------------------------------------------------
*/

if (!function_exists('get_brands')) {

    function get_brands(
        bool $activeOnly = true
    ): array {

        global $pdo;

        if ($activeOnly) {

            $stmt = $pdo->query(
                "SELECT *
                 FROM brands
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, name ASC"
            );

        } else {

            $stmt = $pdo->query(
                "SELECT *
                 FROM brands
                 ORDER BY sort_order ASC, name ASC"
            );
        }

        return $stmt->fetchAll();
    }
}


/*
|--------------------------------------------------------------------------
| GET LOCATIONS
|--------------------------------------------------------------------------
*/

if (!function_exists('get_locations')) {

    function get_locations(
        bool $activeOnly = true
    ): array {

        global $pdo;

        if ($activeOnly) {

            $stmt = $pdo->query(
                "SELECT *
                 FROM locations
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, name ASC"
            );

        } else {

            $stmt = $pdo->query(
                "SELECT *
                 FROM locations
                 ORDER BY sort_order ASC, name ASC"
            );
        }

        return $stmt->fetchAll();
    }
}


/*
|--------------------------------------------------------------------------
| GET COMPANY STATS
|--------------------------------------------------------------------------
*/

if (!function_exists('get_company_stats')) {

    function get_company_stats(): array
    {
        global $pdo;

        $stmt = $pdo->query(
            "SELECT *
             FROM company_stats
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC"
        );

        return $stmt->fetchAll();
    }
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('set_flash')) {

    function set_flash(
        string $type,
        string $message
    ): void {

        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}


if (!function_exists('get_flash')) {

    function get_flash(): ?array
    {
        if (
            !isset(
                $_SESSION['flash']
            )
        ) {

            return null;
        }

        $flash =
            $_SESSION['flash'];

        unset(
            $_SESSION['flash']
        );

        return $flash;
    }
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (!function_exists('csrf_token')) {

    function csrf_token(): string
    {
        if (
            empty(
                $_SESSION['csrf_token']
            )
        ) {

            $_SESSION['csrf_token'] =
                bin2hex(
                    random_bytes(32)
                );
        }

        return $_SESSION['csrf_token'];
    }
}


/*
|--------------------------------------------------------------------------
| CSRF INPUT
|--------------------------------------------------------------------------
*/

if (!function_exists('csrf_input')) {

    function csrf_input(): string
    {
        return
            '<input type="hidden" name="csrf_token" value="' .
            e(csrf_token()) .
            '">';
    }
}


/*
|--------------------------------------------------------------------------
| VERIFY CSRF
|--------------------------------------------------------------------------
*/

if (!function_exists('verify_csrf')) {

    function verify_csrf(): bool
    {
        $sessionToken =
            $_SESSION['csrf_token'] ?? '';

        $postToken =
            $_POST['csrf_token'] ?? '';

        return
            !empty($sessionToken)
            &&
            !empty($postToken)
            &&
            hash_equals(
                $sessionToken,
                (string) $postToken
            );
    }
}


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN STATUS
|--------------------------------------------------------------------------
*/

if (!function_exists('is_admin_logged_in')) {

    function is_admin_logged_in(): bool
    {
        $adminId = (int) (
            $_SESSION['admin_id']
            ?? 0
        );

        if ($adminId <= 0) {
            return false;
        }

        global $pdo;

        try {

            $stmt = $pdo->prepare(
                "SELECT id
                 FROM admin_users
                 WHERE id = ?
                   AND status = 1
                 LIMIT 1"
            );

            $stmt->execute([
                $adminId
            ]);

            if ($stmt->fetchColumn() !== false) {
                return true;
            }

        } catch (Throwable $e) {

            error_log(
                'Star Parts admin session validation error: ' .
                $e->getMessage()
            );
        }

        // Session admin lama/tidak valid tidak boleh membuat
        // login page terus mengarahkan kembali ke dashboard.
        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            $_SESSION['admin_username'],
            $_SESSION['admin_role']
        );

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
*/

if (!function_exists('require_admin')) {

    function require_admin(): void
    {
        if (!is_admin_logged_in()) {
            redirect(base_url('admin/index.php'));
        }
        admin_session_guard();
        if (empty($_SESSION['admin_id'])) {
            redirect(base_url('admin/index.php?session_expired=1'));
        }
        admin_security_headers();
    }
}


/*
|--------------------------------------------------------------------------
| ADMIN USER
|--------------------------------------------------------------------------
*/

if (!function_exists('current_admin')) {

    function current_admin(): ?array
    {
        static $admin = false;

        if ($admin !== false) {
            return $admin;
        }

        if (
            empty(
                $_SESSION['admin_id']
            )
        ) {

            $admin = null;

            return null;
        }

        global $pdo;

        $stmt = $pdo->prepare(
            "SELECT
                id,
                username,
                full_name,
                email,
                role,
                status
             FROM admin_users
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->execute([
            (int) $_SESSION['admin_id']
        ]);

        $result = $stmt->fetch();

        $admin =
            $result ?: null;

        return $admin;
    }
}


/*
|--------------------------------------------------------------------------
| FORMAT DATE INDONESIA
|--------------------------------------------------------------------------
*/

if (!function_exists('format_date_id')) {

    function format_date_id(
        ?string $date
    ): string {

        if (empty($date)) {
            return '-';
        }

        $timestamp =
            strtotime($date);

        if ($timestamp === false) {
            return '-';
        }

        $months = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $day =
            date('d', $timestamp);

        $month =
            $months[
                (int) date('m', $timestamp)
            ];

        $year =
            date('Y', $timestamp);

        return
            $day . ' ' .
            $month . ' ' .
            $year;
    }
}


/*
|--------------------------------------------------------------------------
| FORMAT FILE SIZE
|--------------------------------------------------------------------------
*/

if (!function_exists('format_file_size')) {

    function format_file_size(
        int $bytes
    ): string {

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB'
        ];

        $power =
            floor(
                log($bytes, 1024)
            );

        $power =
            min(
                $power,
                count($units) - 1
            );

        return
            number_format(
                $bytes /
                (1024 ** $power),
                2
            )
            . ' '
            . $units[$power];
    }
}


/*
|--------------------------------------------------------------------------
| IMAGE URL
|--------------------------------------------------------------------------
*/

if (!function_exists('media_url')) {

    function media_url(
        ?string $path,
        string $fallback = ''
    ): string {

        if (
            empty($path)
        ) {

            return $fallback;
        }

        /*
         * Jika sudah berupa URL lengkap
         */

        if (
            preg_match(
                '#^https?://#i',
                $path
            )
        ) {

            return $path;
        }

        $relativePath = str_replace('\\', '/', ltrim($path, '/'));

        // Prefer a pre-generated WebP sibling for local PNG/JPEG assets.
        // This keeps original files/DB paths intact while reducing transfer size
        // on mobile. Newly uploaded originals continue to work automatically:
        // a WebP variant is used only when it exists and is at least as recent.
        if (
            $relativePath !== ''
            && !preg_match('~(?:^|/)\.\.(?:/|$)~', $relativePath)
            && preg_match('~\.(?:png|jpe?g)$~i', $relativePath)
        ) {
            $webpRelativePath = preg_replace('~\.(?:png|jpe?g)$~i', '.webp', $relativePath);
            if (is_string($webpRelativePath) && $webpRelativePath !== $relativePath) {
                $projectRoot = dirname(__DIR__);
                $sourceFile = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                $webpFile = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $webpRelativePath);

                if (is_file($webpFile) && (!is_file($sourceFile) || (int) @filemtime($webpFile) >= (int) @filemtime($sourceFile))) {
                    return base_url($webpRelativePath);
                }
            }
        }

        return base_url($relativePath);
    }
}


/*
|--------------------------------------------------------------------------
| TRUNCATE TEXT
|--------------------------------------------------------------------------
*/

if (!function_exists('excerpt')) {

    function excerpt(
        ?string $text,
        int $length = 150
    ): string {

        $text =
            trim(
                strip_tags(
                    $text ?? ''
                )
            );

        $textLength = function_exists('mb_strlen')
            ? mb_strlen($text, 'UTF-8')
            : strlen($text);

        if ($textLength <= $length) {
            return $text;
        }

        $shortText = function_exists('mb_substr')
            ? mb_substr($text, 0, $length, 'UTF-8')
            : substr($text, 0, $length);

        return $shortText . '...';
    }
}


/*
|--------------------------------------------------------------------------
| ACTIVE MENU
|--------------------------------------------------------------------------
*/

if (!function_exists('is_active_menu')) {

    function is_active_menu(
        string $page
    ): bool {

        $current =
            basename(
                $_SERVER['PHP_SELF'] ?? ''
            );

        return
            strtolower($current)
            ===
            strtolower($page);
    }
}


/*
|--------------------------------------------------------------------------
| SAFE FILE NAME
|--------------------------------------------------------------------------
*/

if (!function_exists('safe_filename')) {

    function safe_filename(
        string $filename
    ): string {

        $extension =
            strtolower(
                pathinfo(
                    $filename,
                    PATHINFO_EXTENSION
                )
            );

        $name =
            pathinfo(
                $filename,
                PATHINFO_FILENAME
            );

        $name =
            slugify($name);

        $unique =
            date('YmdHis')
            . '_'
            . bin2hex(
                random_bytes(4)
            );

        return
            $name
            . '_'
            . $unique
            . ($extension
                ? '.' . $extension
                : '');
    }
}


/*
|--------------------------------------------------------------------------
| ALLOWED IMAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('is_allowed_image')) {

    function is_allowed_image(
        string $mime
    ): bool {

        return in_array(
            strtolower($mime),
            [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif'
            ],
            true
        );
    }
}


/*
|--------------------------------------------------------------------------
| ALLOWED VIDEO
|--------------------------------------------------------------------------
*/

if (!function_exists('is_allowed_video')) {

    function is_allowed_video(
        string $mime
    ): bool {

        return in_array(
            strtolower($mime),
            [
                'video/mp4',
                'video/webm',
                'video/ogg'
            ],
            true
        );
    }
}


/*
|--------------------------------------------------------------------------
| UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

if (!function_exists('ensure_directory')) {

    function ensure_directory(
        string $directory
    ): bool {

        if (
            is_dir($directory)
        ) {

            return true;
        }

        return mkdir(
            $directory,
            0755,
            true
        );
    }
}


/*
|--------------------------------------------------------------------------
| UPLOAD IMAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('upload_image')) {

    function upload_image(
        array $file,
        string $folder = 'media'
    ): array {

        if (
            !isset(
                $file['error']
            )
            ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {

            return [
                'success' => false,
                'message' => 'File gambar gagal diupload.'
            ];
        }

        if (
            !isset(
                $file['tmp_name']
            )
            ||
            !is_uploaded_file(
                $file['tmp_name']
            )
        ) {

            return [
                'success' => false,
                'message' => 'File upload tidak valid.'
            ];
        }

        $maxSize =
            10 * 1024 * 1024;

        if (
            (int) $file['size']
            > $maxSize
        ) {

            return [
                'success' => false,
                'message' => 'Ukuran gambar maksimal 5 MB.'
            ];
        }

        $finfo =
            finfo_open(
                FILEINFO_MIME_TYPE
            );

        $mime =
            $finfo
                ? finfo_file(
                    $finfo,
                    $file['tmp_name']
                )
                : '';

        if ($finfo) {
            finfo_close($finfo);
        }

        if (
            !is_allowed_image(
                (string) $mime
            )
        ) {

            return [
                'success' => false,
                'message' => 'Format gambar tidak didukung.'
            ];
        }

        $uploadPath =
            rtrim(
                UPLOAD_DIR,
                '/\\'
            )
            . DIRECTORY_SEPARATOR
            . trim(
                $folder,
                '/\\'
            )
            . DIRECTORY_SEPARATOR;

        if (
            !ensure_directory(
                $uploadPath
            )
        ) {

            return [
                'success' => false,
                'message' => 'Folder upload tidak dapat dibuat.'
            ];
        }

        $filename =
            safe_filename(
                (string) $file['name']
            );

        $destination =
            $uploadPath
            . $filename;

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {

            return [
                'success' => false,
                'message' => 'Gambar gagal disimpan.'
            ];
        }

        $variants = generate_image_variants($destination);

        $relativePath =
            'uploads/'
            . trim($folder, '/')
            . '/'
            . $filename;

        return [
            'success' => true,
            'message' => 'Gambar berhasil diupload.',
            'filename' => $filename,
            'path' => $relativePath,
            'url' => media_url(
                $relativePath
            ),
            'mime' => $mime,
            'size' => (int) $file['size'],
            'variants' => count($variants),
            'optimized' => count($variants) > 0
        ];
    }
}


/*
|--------------------------------------------------------------------------
| UPLOAD VIDEO
|--------------------------------------------------------------------------
*/

if (!function_exists('upload_video')) {

    function upload_video(
        array $file,
        string $folder = 'media'
    ): array {

        if (
            !isset(
                $file['error']
            )
            ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {

            return [
                'success' => false,
                'message' => 'File video gagal diupload.'
            ];
        }

        if (
            !isset(
                $file['tmp_name']
            )
            ||
            !is_uploaded_file(
                $file['tmp_name']
            )
        ) {

            return [
                'success' => false,
                'message' => 'File upload tidak valid.'
            ];
        }

        /*
         * Maksimal 100 MB
         */

        $maxSize =
            100 * 1024 * 1024;

        if (
            (int) $file['size']
            > $maxSize
        ) {

            return [
                'success' => false,
                'message' => 'Ukuran video maksimal 100 MB.'
            ];
        }

        $finfo =
            finfo_open(
                FILEINFO_MIME_TYPE
            );

        $mime =
            $finfo
                ? finfo_file(
                    $finfo,
                    $file['tmp_name']
                )
                : '';

        if ($finfo) {
            finfo_close($finfo);
        }

        if (
            !is_allowed_video(
                (string) $mime
            )
        ) {

            return [
                'success' => false,
                'message' => 'Format video tidak didukung.'
            ];
        }

        $uploadPath =
            rtrim(
                UPLOAD_DIR,
                '/\\'
            )
            . DIRECTORY_SEPARATOR
            . trim(
                $folder,
                '/\\'
            )
            . DIRECTORY_SEPARATOR;

        if (
            !ensure_directory(
                $uploadPath
            )
        ) {

            return [
                'success' => false,
                'message' => 'Folder upload tidak dapat dibuat.'
            ];
        }

        $filename =
            safe_filename(
                (string) $file['name']
            );

        $destination =
            $uploadPath
            . $filename;

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {

            return [
                'success' => false,
                'message' => 'Video gagal disimpan.'
            ];
        }

        $relativePath =
            'uploads/'
            . trim($folder, '/')
            . '/'
            . $filename;

        return [
            'success' => true,
            'message' => 'Video berhasil diupload.',
            'filename' => $filename,
            'path' => $relativePath,
            'url' => media_url(
                $relativePath
            ),
            'mime' => $mime,
            'size' => (int) $file['size']
        ];
    }
}


/*
|--------------------------------------------------------------------------
| SAFE JSON
|--------------------------------------------------------------------------
*/

if (!function_exists('json_response')) {

    function json_response(
        array $data,
        int $status = 200
    ): never {

        http_response_code(
            $status
        );

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| POST REDIRECT
|--------------------------------------------------------------------------
*/

if (!function_exists('old_input')) {

    function old_input(
        string $key,
        string $default = ''
    ): string {

        return e(
            $_SESSION['old_input'][$key]
            ?? $default
        );
    }
}


if (!function_exists('set_old_input')) {

    function set_old_input(
        array $data
    ): void {

        $_SESSION['old_input'] =
            $data;
    }
}


if (!function_exists('clear_old_input')) {

    function clear_old_input(): void
    {
        unset(
            $_SESSION['old_input']
        );
    }
}




/*
|--------------------------------------------------------------------------
| ADMIN SECURITY HELPERS - PHASE 7
|--------------------------------------------------------------------------
*/

if (!function_exists('admin_client_ip')) {
    function admin_client_ip(): string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

if (!function_exists('admin_user_agent')) {
    function admin_user_agent(): string
    {
        $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        return mb_substr($ua, 0, 500);
    }
}

if (!function_exists('admin_audit_log')) {
    function admin_audit_log(
        string $action,
        ?string $targetType = null,
        $targetId = null,
        ?string $details = null
    ): void {
        global $pdo;
        try {
            $adminId = !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
            $stmt = $pdo->prepare(
                "INSERT INTO admin_audit_logs
                 (admin_id, action, target_type, target_id, details, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $adminId,
                mb_substr($action, 0, 100),
                $targetType !== null ? mb_substr($targetType, 0, 100) : null,
                $targetId !== null ? mb_substr((string) $targetId, 0, 100) : null,
                $details !== null ? mb_substr($details, 0, 5000) : null,
                admin_client_ip(),
                admin_user_agent()
            ]);
        } catch (Throwable $e) {
            error_log('Star Parts audit log error: ' . $e->getMessage());
        }
    }
}

if (!function_exists('admin_login_is_allowed')) {
    function admin_login_is_allowed(string $username): array
    {
        global $pdo;
        $username = mb_substr(trim($username), 0, 100);
        $ip = admin_client_ip();
        try {
            $pdo->exec("DELETE FROM admin_login_attempts WHERE last_attempt_at < (NOW() - INTERVAL 2 DAY)");
            $stmt = $pdo->prepare(
                "SELECT attempt_count, blocked_until, window_started_at
                 FROM admin_login_attempts
                 WHERE ip_address = ? AND username = ? LIMIT 1"
            );
            $stmt->execute([$ip, $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return ['allowed' => true, 'retry_after' => 0];
            $blockedUntil = strtotime((string) ($row['blocked_until'] ?? '')) ?: 0;
            if ($blockedUntil > time()) {
                return ['allowed' => false, 'retry_after' => $blockedUntil - time()];
            }
            $windowStart = strtotime((string) ($row['window_started_at'] ?? '')) ?: 0;
            if ($windowStart < time() - 900) {
                $stmt = $pdo->prepare("DELETE FROM admin_login_attempts WHERE ip_address = ? AND username = ?");
                $stmt->execute([$ip, $username]);
            }
            return ['allowed' => true, 'retry_after' => 0];
        } catch (Throwable $e) {
            error_log('Star Parts login rate-limit read error: ' . $e->getMessage());
            return ['allowed' => true, 'retry_after' => 0];
        }
    }
}

if (!function_exists('admin_login_record_failure')) {
    function admin_login_record_failure(string $username): void
    {
        global $pdo;
        $username = mb_substr(trim($username), 0, 100);
        $ip = admin_client_ip();
        try {
            $stmt = $pdo->prepare(
                "SELECT id, attempt_count, window_started_at
                 FROM admin_login_attempts
                 WHERE ip_address = ? AND username = ? LIMIT 1"
            );
            $stmt->execute([$ip, $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $now = time();
            if (!$row || (strtotime((string) $row['window_started_at']) ?: 0) < $now - 900) {
                $stmt = $pdo->prepare(
                    "INSERT INTO admin_login_attempts
                     (ip_address, username, attempt_count, window_started_at, blocked_until, last_attempt_at)
                     VALUES (?, ?, 1, NOW(), NULL, NOW())
                     ON DUPLICATE KEY UPDATE
                       attempt_count = 1,
                       window_started_at = NOW(),
                       blocked_until = NULL,
                       last_attempt_at = NOW()"
                );
                $stmt->execute([$ip, $username]);
                return;
            }
            $attempts = (int) $row['attempt_count'] + 1;
            $blockedUntil = null;
            if ($attempts >= 5) {
                $blockedUntil = date('Y-m-d H:i:s', $now + 900);
            }
            $stmt = $pdo->prepare(
                "UPDATE admin_login_attempts
                 SET attempt_count = ?,
                     blocked_until = ?,
                     last_attempt_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$attempts, $blockedUntil, (int) $row['id']]);
        } catch (Throwable $e) {
            error_log('Star Parts login rate-limit write error: ' . $e->getMessage());
        }
    }
}

if (!function_exists('admin_login_clear_failures')) {
    function admin_login_clear_failures(string $username): void
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM admin_login_attempts WHERE ip_address = ? AND username = ?");
            $stmt->execute([admin_client_ip(), mb_substr(trim($username), 0, 100)]);
        } catch (Throwable $e) {
            error_log('Star Parts login rate-limit clear error: ' . $e->getMessage());
        }
    }
}

if (!function_exists('require_admin_role')) {
    function require_admin_role(array $allowedRoles): void
    {
        if (!is_admin_logged_in()) {
            redirect(base_url('admin/index.php'));
        }
        admin_session_guard();
        if (empty($_SESSION['admin_id'])) {
            redirect(base_url('admin/index.php?session_expired=1'));
        }
        admin_security_headers();
        $role = strtolower((string) ($_SESSION['admin_role'] ?? ''));
        $allowed = array_map('strtolower', $allowedRoles);
        if (!in_array($role, $allowed, true)) {
            http_response_code(403);
            exit('Akses ditolak. Anda tidak memiliki hak untuk membuka halaman ini.');
        }
    }
}

if (!function_exists('admin_security_headers')) {
    function admin_security_headers(): void
    {
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
    }
}

if (!function_exists('admin_password_is_strong')) {
    function admin_password_is_strong(string $password): bool
    {
        if (strlen($password) < 10 || strlen($password) > 255) return false;
        return (bool) preg_match('/[a-z]/', $password)
            && (bool) preg_match('/[A-Z]/', $password)
            && (bool) preg_match('/[0-9]/', $password);
    }
}

if (!function_exists('admin_session_guard')) {
    function admin_session_guard(): void
    {
        if (empty($_SESSION['admin_id'])) return;
        $now = time();
        $last = (int) ($_SESSION['admin_last_activity'] ?? $now);
        $started = (int) ($_SESSION['admin_session_started'] ?? $now);
        if (($now - $last) > 28800 || ($now - $started) > 86400) {
            $adminId = (int) $_SESSION['admin_id'];
            admin_audit_log('session_expired', 'admin_user', $adminId, 'Sesi admin berakhir karena timeout.');
            $_SESSION = [];
            session_regenerate_id(true);
            return;
        }
        $_SESSION['admin_last_activity'] = $now;
        $_SESSION['admin_session_started'] = $started;
    }
}

/*
|--------------------------------------------------------------------------
| END FUNCTIONS
|--------------------------------------------------------------------------
*/