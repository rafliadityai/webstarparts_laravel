<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n\n";
echo "Sitemap: " . site_url('sitemap.xml') . "\n";
