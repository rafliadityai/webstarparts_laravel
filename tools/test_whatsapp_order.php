<?php
/** Offline tests for order-intent routing and safe product selection references. */
declare(strict_types=1);
define('BASE_URL', 'https://beta.starpartsmotor.com/');
require_once dirname(__DIR__) . '/includes/whatsapp_reply.php';
require_once dirname(__DIR__) . '/includes/whatsapp_order.php';

$cases = 0;
$failures = [];
$assert = static function (bool $ok, string $name) use (&$cases, &$failures): void {
    $cases++;
    if (!$ok) $failures[] = $name;
};

$assert(spm_wh_order_intent_kind('Saya mau beli ban FDR') === 'order', 'buy intent detected');
$assert(spm_wh_order_intent_kind('Mau checkout Genzi PRO') === 'reference', 'checkout treated as contextual product reference');
$assert(spm_wh_order_intent_kind('Linknya yang mana, Kak?') === 'reference', 'asking for link detected');
$assert(spm_wh_order_intent_kind('Selamat pagi') === 'none', 'ordinary greeting not classified as order');
$assert(spm_wh_order_selection_index('nomor 2') === 1, 'numeric choice resolves to zero-based index');
$assert(spm_wh_order_selection_index('yang kedua') === 1, 'ordinal choice resolves');
$assert(spm_wh_order_selection_index('mau yang ketiga') === 2, 'natural language choice resolves');
$assert(spm_wh_order_selection_index('125') === null, 'vehicle variant response not mistaken for product choice');
$assert(spm_wh_order_selection_index('saya pilih nomor 4') === 3, 'explicit fourth option resolves');
$assert(spm_wh_order_selection_index('nomor 2 ya kak') === 1, 'polite suffixes do not break numbered choice');

$history = [[
    'role' => 'assistant',
    'content' => "Silakan pilih produk:\n1. Genzi PRO\nhttps://beta.starpartsmotor.com/produk/genzi-pro\n\n2. Genzi\nhttps://beta.starpartsmotor.com/produk/genzi."
]];
$slugs = spm_wh_order_recent_product_slugs($history);
$assert($slugs === ['genzi-pro', 'genzi'], 'canonical product URLs extracted in displayed order');
$untrusted = [[
    'role' => 'assistant',
    'content' => 'https://evil.example/produk/genzi-pro https://beta.starpartsmotor.com/produk/../admin'
]];
$assert(spm_wh_order_recent_product_slugs($untrusted) === [], 'external or malformed URLs are rejected');
$stale = [
    ['role' => 'assistant', 'content' => 'https://beta.starpartsmotor.com/produk/genzi'],
    ['role' => 'user', 'content' => 'Saya ingin beli'],
];
$assert(spm_wh_order_recent_product_slugs($stale) === [], 'last saved user message invalidates stale assistant selection context');
$assert(str_contains(spm_wh_order_single_product_reply(['name' => 'Genzi PRO']), 'belum membuat pesanan'), 'order message does not claim a transaction was created');

if ($failures) {
    fwrite(STDERR, 'FAIL: ' . count($failures) . ' / ' . $cases . " order scenarios failed\n");
    foreach ($failures as $failure) fwrite(STDERR, ' - ' . $failure . "\n");
    exit(1);
}
echo 'OK: ' . $cases . " WhatsApp order scenarios passed.\n";
