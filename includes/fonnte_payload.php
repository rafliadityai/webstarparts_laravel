<?php
/**
 * Normalize inbound Fonnte webhook payloads into a stable internal format.
 * Supports documented fields such as sender, message, text, timestamp, inboxid,
 * plus interactive/poll/location/media-only inbound events. No remote media is fetched.
 */
declare(strict_types=1);

function spm_fonnte_scalar_field(array $data, array $names): string
{
    $indexed = [];
    foreach ($data as $key => $value) {
        if (is_string($key)) $indexed[strtolower($key)] = $value;
    }
    foreach ($names as $name) {
        $key = strtolower($name);
        if (!array_key_exists($key, $indexed) || !is_scalar($indexed[$key])) continue;
        $value = trim((string)$indexed[$key]);
        if ($value !== '') return $value;
    }
    return '';
}

function spm_fonnte_normalize_sender(string $sender): string
{
    $sender = trim($sender);
    // Group JIDs are deliberately not processed as individual customer chats.
    if ($sender === '' || stripos($sender, '@g.us') !== false) return '';
    $sender = preg_replace('/@(c\.us|s\.whatsapp\.net)$/i', '', $sender) ?? $sender;
    $digits = preg_replace('/\D+/', '', $sender) ?? '';
    if ($digits === '') return '';
    if (str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1);
    elseif (str_starts_with($digits, '8')) $digits = '62' . $digits;
    // WhatsApp phone targets should be plausibly telephone-like, not arbitrary IDs.
    if (strlen($digits) < 8 || strlen($digits) > 15) return '';
    return $digits;
}

/**
 * @return array{sender:string,message:string,inbox_id:?string,timestamp:string,message_id:string,kind:string,ignored_reason:?string}
 */
function spm_fonnte_normalize_payload(array $data): array
{
    $senderRaw = spm_fonnte_scalar_field($data, ['sender']);
    $sender = spm_fonnte_normalize_sender($senderRaw);
    $message = spm_fonnte_scalar_field($data, ['message', 'text']);
    $timestamp = spm_fonnte_scalar_field($data, ['timestamp']);
    $inboxRaw = spm_fonnte_scalar_field($data, ['inboxid', 'inbox_id']);
    $inboxId = null;
    // Fonnte expects a numeric inbox message id when quoting a reply. Ignore zero/non-numeric placeholders.
    if (preg_match('/^[1-9][0-9]{0,18}$/', $inboxRaw)) $inboxId = $inboxRaw;
    $messageId = spm_fonnte_scalar_field($data, ['message_id', 'messageid', 'messageId', 'event_id', 'id']);
    if (strlen($messageId) > 180) $messageId = substr($messageId, 0, 180);

    $kind = 'text';
    $ignoredReason = null;
    if ($sender === '') {
        $ignoredReason = (stripos($senderRaw, '@g.us') !== false) ? 'group_message' : 'invalid_sender';
    }

    if ($message === '') {
        $choices = spm_fonnte_scalar_field($data, ['choices', 'choice']);
        $pollName = spm_fonnte_scalar_field($data, ['pollname', 'poll_name']);
        $location = spm_fonnte_scalar_field($data, ['location']);
        $url = spm_fonnte_scalar_field($data, ['url', 'file_url']);
        $filename = spm_fonnte_scalar_field($data, ['filename', 'file_name']);
        $extension = spm_fonnte_scalar_field($data, ['extension', 'file_extension']);

        if ($choices !== '') {
            $kind = 'poll';
            $label = $pollName !== '' ? ' pada polling "' . $pollName . '"' : '';
            $message = 'Customer memilih polling' . $label . ': ' . $choices;
        } elseif ($location !== '') {
            $kind = 'location';
            $message = 'Customer membagikan lokasi tanpa pertanyaan teks.';
        } elseif ($url !== '' || $filename !== '' || $extension !== '') {
            $kind = 'attachment';
            $type = $extension !== '' ? strtoupper($extension) : 'lampiran';
            $message = 'Customer mengirim ' . $type . ' tanpa keterangan teks.';
            // Deliberately do not include/fetch the remote attachment URL in the AI prompt.
        }
    }

    if ($message === '') $ignoredReason = $ignoredReason ?? 'empty_message';
    return [
        'sender' => $sender,
        'message' => $message,
        'inbox_id' => $inboxId,
        'timestamp' => $timestamp,
        'message_id' => $messageId,
        'kind' => $kind,
        'ignored_reason' => $ignoredReason,
    ];
}

/**
 * Stable event seed for idempotency. Inbox id is primary when positive; provider
 * IDs then timestamp are fallbacks. Without a provider identifier, identical
 * text is deduped within a short retry window only.
 */
function spm_fonnte_event_seed(array $event, int $now = 0): string
{
    $sender = (string)($event['sender'] ?? '');
    $message = (string)($event['message'] ?? '');
    $inboxId = $event['inbox_id'] ?? null;
    if (is_scalar($inboxId) && preg_match('/^[1-9][0-9]{0,18}$/', (string)$inboxId)) {
        return 'inbox:' . (string)$inboxId;
    }
    $messageId = trim((string)($event['message_id'] ?? ''));
    if ($messageId !== '') return 'provider-message:' . $messageId;
    $timestamp = trim((string)($event['timestamp'] ?? ''));
    $messageHash = hash('sha256', $message);
    if ($timestamp !== '') return 'message:' . $sender . '|' . $timestamp . '|' . $messageHash;
    if ($now <= 0) $now = time();
    // Rare fallback: Fonnte has documented timestamp/inbox metadata; keep this window
    // only for payload variants that omit both, to reduce immediate retry duplicates.
    return 'fallback-window:' . $sender . '|' . $messageHash . '|' . (string)floor($now / 120);
}

/** Parse request body content before normalization (useful for unit tests). */
function spm_fonnte_parse_request_body(string $rawBody, array $post = [], string $contentType = ''): array
{
    $trimmed = trim($rawBody);
    if ($trimmed !== '') {
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) return $decoded;
        if (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $parsed = [];
            parse_str($trimmed, $parsed);
            if (is_array($parsed) && $parsed) return $parsed;
        }
    }
    return $post;
}
