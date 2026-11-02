<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

$root = rtrim(site_url(), '/');

$urls = [];
$add = static function(string $loc, ?string $lastmod = null, array $images = []) use (&$urls): void {
    $urls[] = ['loc'=>$loc,'lastmod'=>$lastmod,'images'=>$images];
};

$add($root . '/', date('Y-m-d'));
foreach (['about.php','products.php','news.php','career.php','contact.php','privacy.php','terms.php'] as $file) {
    $add($root . '/' . $file);
}

try {
    $stmt = $pdo->query("SELECT slug, updated_at, image FROM products WHERE is_active=1 ORDER BY updated_at DESC, id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $images = [];
        if (trim((string)($row['image'] ?? '')) !== '') {
            $images[] = absolute_url((string)$row['image']);
        }
        $add($root . '/produk/' . rawurlencode((string)$row['slug']), !empty($row['updated_at']) ? date('Y-m-d', strtotime((string)$row['updated_at'])) : null, $images);
    }
} catch (Throwable $e) {}

try {
    $stmt = $pdo->query("SELECT id, slug, updated_at, featured_image FROM posts WHERE status='published' ORDER BY COALESCE(published_at, updated_at) DESC, id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $images = [];
        if (trim((string)($row['featured_image'] ?? '')) !== '') {
            $images[] = absolute_url((string)$row['featured_image']);
        }
        $postId = (int)($row['id'] ?? 0);
        if ($postId > 0) {
            $add($root . '/news-detail.php?id=' . $postId, !empty($row['updated_at']) ? date('Y-m-d', strtotime((string)$row['updated_at'])) : null, $images);
        }
    }
} catch (Throwable $e) {}

try {
    $stmt = $pdo->query("SELECT slug, updated_at, featured_image FROM pages WHERE status='published' AND slug NOT IN ('career','privacy','terms') ORDER BY updated_at DESC, id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generic CMS pages are exposed through page.php in the next phase only if created.
        // Keep current sitemap limited to known public routes to avoid non-existent URLs.
    }
} catch (Throwable $e) {}

echo '<?xml version="1.0" encoding="UTF-8"?>\n';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">\n';
foreach ($urls as $item) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($item['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    if (!empty($item['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars((string)$item['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    }
    foreach ($item['images'] as $image) {
        echo "    <image:image>\n";
        echo '      <image:loc>' . htmlspecialchars($image, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</image:loc>\n";
        echo "    </image:image>\n";
    }
    echo "  </url>\n";
}
echo '</urlset>';
