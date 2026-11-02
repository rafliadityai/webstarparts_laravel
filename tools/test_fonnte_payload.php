<?php
/** Offline tests for the Fonnte payload adapter and idempotency key strategy. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/fonnte_payload.php';

$cases = 0;
$failures = [];
$assert = static function (bool $ok, string $name) use (&$cases, &$failures): void {
    $cases++;
    if (!$ok) $failures[] = $name;
};

$standard = spm_fonnte_normalize_payload([
    'sender' => '083178898805@s.whatsapp.net',
    'message' => 'Ada ban FDR?',
    'timestamp' => '2026-10-22T09:00:00+07:00',
    'inboxid' => '98765',
]);
$assert($standard['sender'] === '6283178898805', 'Indonesian local sender converted to country code');
$assert($standard['message'] === 'Ada ban FDR?' && $standard['kind'] === 'text', 'normal text payload is preserved');
$assert($standard['inbox_id'] === '98765', 'positive inboxid is retained for reply quoting');
$assert(spm_fonnte_event_seed($standard) === 'inbox:98765', 'positive inboxid has idempotency priority');

$jid = spm_fonnte_normalize_payload(['SENDER' => '6283178898805@c.us', 'MESSAGE' => 'Halo', 'MESSAGEID' => 'provider-1']);
$assert($jid['sender'] === '6283178898805', 'uppercase documented field names and c.us suffix accepted');
$assert(spm_fonnte_event_seed($jid) === 'provider-message:provider-1', 'provider message id is a fallback key');

$button = spm_fonnte_normalize_payload(['sender' => '6283178898805', 'message' => '', 'text' => 'Nomor 2']);
$assert($button['message'] === 'Nomor 2', 'empty message falls back to interactive text field');

$group = spm_fonnte_normalize_payload(['sender' => '120363000000000000@g.us', 'message' => 'Halo']);
$assert($group['sender'] === '' && $group['ignored_reason'] === 'group_message', 'group messages are ignored');

$badSender = spm_fonnte_normalize_payload(['sender' => '123', 'message' => 'Halo']);
$assert($badSender['sender'] === '' && $badSender['ignored_reason'] === 'invalid_sender', 'non-phone sender is rejected');

$zeroInbox = spm_fonnte_normalize_payload(['sender' => '6283178898805', 'message' => 'Halo', 'inboxid' => '0', 'timestamp' => 'ts-100']);
$assert($zeroInbox['inbox_id'] === null, 'zero inbox id is ignored as placeholder');
$hugeInbox = spm_fonnte_normalize_payload(['sender' => '6283178898805', 'message' => 'Halo', 'inboxid' => '999999999999999999999']);
$assert($hugeInbox['inbox_id'] === null, 'oversized inbox id is rejected');
$assert(spm_fonnte_event_seed($zeroInbox) === 'message:6283178898805|ts-100|' . hash('sha256', 'Halo'), 'timestamp is used when inbox id is absent/zero');

$attachment = spm_fonnte_normalize_payload([
    'sender' => '6283178898805', 'message' => '', 'url' => 'https://private.example/pelanggan-123.png', 'filename' => 'pelanggan-123.png', 'extension' => 'png'
]);
$assert($attachment['kind'] === 'attachment', 'media-only payload classified as attachment');
$assert(str_contains($attachment['message'], 'PNG') && !str_contains($attachment['message'], 'private.example') && !str_contains($attachment['message'], 'pelanggan-123'), 'attachment prompt does not expose/fetch remote URL or filename');

$location = spm_fonnte_normalize_payload(['sender' => '6283178898805', 'location' => '-6.9,107.6']);
$assert($location['kind'] === 'location' && !str_contains($location['message'], '107.6'), 'location-only payload is not fed as raw coordinates to AI');

$poll = spm_fonnte_normalize_payload(['sender' => '6283178898805', 'pollname' => 'Pilih produk', 'choices' => 'FDR Genzi']);
$assert($poll['kind'] === 'poll' && str_contains($poll['message'], 'FDR Genzi'), 'poll choice becomes a clear synthetic message');

$parsedJson = spm_fonnte_parse_request_body('{"sender":"6283178898805","message":"json"}', [], 'application/json');
$assert(($parsedJson['message'] ?? '') === 'json', 'JSON request body is parsed');
$parsedForm = spm_fonnte_parse_request_body('sender=6283178898805&message=from+form', [], 'application/x-www-form-urlencoded');
$assert(($parsedForm['message'] ?? '') === 'from form', 'raw URL-encoded request body is parsed');
$parsedPost = spm_fonnte_parse_request_body('', ['sender' => '6283178898805', 'message' => 'post'], 'multipart/form-data');
$assert(($parsedPost['message'] ?? '') === 'post', 'PHP parsed POST fallback works');

$fallbackEvent = ['sender' => '6283178898805', 'message' => 'Halo', 'timestamp' => '', 'message_id' => '', 'inbox_id' => null];
$assert(spm_fonnte_event_seed($fallbackEvent, 240) === spm_fonnte_event_seed($fallbackEvent, 300), 'same fallback retry window deduplicates identical immediate events');
$assert(spm_fonnte_event_seed($fallbackEvent, 240) !== spm_fonnte_event_seed($fallbackEvent, 360), 'fallback key rolls over after 120 seconds');

if ($failures) {
    fwrite(STDERR, 'FAIL: ' . count($failures) . ' / ' . $cases . " Fonnte payload scenarios failed\n");
    foreach ($failures as $failure) fwrite(STDERR, ' - ' . $failure . "\n");
    exit(1);
}
echo 'OK: ' . $cases . " Fonnte payload scenarios passed.\n";
