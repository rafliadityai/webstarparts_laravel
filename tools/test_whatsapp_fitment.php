<?php
/** Offline tests for the pure FDR fitment matcher. No database or API credentials required. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/whatsapp_fitment.php';

$tests = 0;
$failures = [];
$assert = static function (bool $condition, string $name) use (&$tests, &$failures): void {
    $tests++;
    if (!$condition) $failures[] = $name;
};

$fit = spm_wh_fitment('Halo, ban untuk Honda Vario 160 dong');
$assert(($fit['key'] ?? '') === 'honda vario 160', 'specific Vario 160 should beat generic Vario');

$fit = spm_wh_fitment('Ada ban buat Vario?');
$assert(($fit['key'] ?? '') === 'honda vario (model belum disebutkan)', 'generic Vario should require clarification');
$assert(($fit['products'] ?? null) === [], 'ambiguous Vario should not provide products');

$history = [
    ['role' => 'user', 'content' => 'Ada ban FDR buat Vario?'],
    ['role' => 'assistant', 'content' => 'Motornya Honda Vario 125, Vario 160, atau Vario Street, Kak?'],
];
$fit = spm_wh_resolve_fitment_from_history('125', null, $history);
$assert(($fit['key'] ?? '') === 'honda vario 125', 'short reply 125 should resolve recent Vario clarification');

$historyBeat = [
    ['role' => 'user', 'content' => 'Ban buat Beat'],
    ['role' => 'assistant', 'content' => 'Motornya Honda Beat 110 atau Honda Beat Street, Kak?'],
];
$fit = spm_wh_resolve_fitment_from_history('Street', null, $historyBeat);
$assert(($fit['key'] ?? '') === 'honda beat street', 'short reply Street should resolve recent Beat clarification');

$oldHistory = [
    ['role' => 'user', 'content' => 'Saya pakai Vario'],
    ['role' => 'assistant', 'content' => 'Motornya Vario 125, Vario 160, atau Vario Street?'],
    ['role' => 'user', 'content' => 'Terima kasih'],
    ['role' => 'assistant', 'content' => 'Sama-sama, Kak.'],
];
$fit = spm_wh_resolve_fitment_from_history('125', null, $oldHistory);
$assert($fit === null, 'old clarification must not override a later unrelated exchange');

$explicit = spm_wh_fitment('Honda Beat Street');
$assert(($explicit['key'] ?? '') === 'honda beat street', 'explicit Beat Street should resolve directly');

if ($failures) {
    fwrite(STDERR, "FAIL: " . count($failures) . " / {$tests} tests failed\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}
echo "OK: {$tests} offline WhatsApp fitment scenarios passed.\n";
