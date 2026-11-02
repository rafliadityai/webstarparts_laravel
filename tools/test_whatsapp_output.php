<?php
/** Offline tests for canonical product links and model-generated URL removal. */
declare(strict_types=1);
define('BASE_URL', 'https://beta.starpartsmotor.com/');
require_once dirname(__DIR__) . '/includes/whatsapp_reply.php';
$cases = 0;
$failures = [];
$assert = static function (bool $ok, string $message) use (&$cases, &$failures): void {
    $cases++;
    if (!$ok) $failures[] = $message;
};
$assert(spm_wh_product_page_url('genzi-pro') === 'https://beta.starpartsmotor.com/produk/genzi-pro', 'canonical product URL uses configured site host');
$assert(spm_wh_product_page_url('../config.local.php') === null, 'slug path traversal is rejected');
$clean = spm_wh_sanitize_ai_reply('Silakan lihat produk ini https://evil.example/phishing dan www.bad.example/x');
$assert(!preg_match('~https?://|www\\.bad\\.example|beta\\.starpartsmotor\\.com~i', $clean), 'model-provided external URLs are removed');
$cleanDomain = spm_wh_sanitize_ai_reply('Gunakan beta.starpartsmotor.com/produk/genzi-pro ya.');
$assert(!str_contains($cleanDomain, 'starpartsmotor.com'), 'bare domains are removed from model output');
$cleanMarkdown = spm_wh_sanitize_ai_reply('Klik [produk](https://evil.example) untuk membeli.');
$assert(!str_contains($cleanMarkdown, 'evil.example'), 'markdown link targets from the model are removed');
if ($failures) {
    fwrite(STDERR, "FAIL: " . count($failures) . " / {$cases} tests failed\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}
echo "OK: {$cases} WhatsApp output/link scenarios passed.\n";
