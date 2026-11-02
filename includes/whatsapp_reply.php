<?php
/**
 * Safe link construction and AI output sanitation for WhatsApp replies.
 * These helpers are kept independent from the webhook so they can be tested offline.
 */
declare(strict_types=1);

function spm_wh_product_page_url(string $slug): ?string
{
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/i', $slug)) return null;
    if (!defined('BASE_URL')) return null;
    $base = rtrim((string)BASE_URL, '/');
    if (!filter_var($base, FILTER_VALIDATE_URL)) return null;
    $baseParts = parse_url($base);
    if (!is_array($baseParts) || empty($baseParts['host']) || !in_array(strtolower((string)($baseParts['scheme'] ?? '')), ['http', 'https'], true)) return null;
    if (isset($baseParts['user']) || isset($baseParts['pass']) || isset($baseParts['query']) || isset($baseParts['fragment'])) return null;
    $url = $base . '/produk/' . rawurlencode($slug);
    if (!filter_var($url, FILTER_VALIDATE_URL)) return null;
    $urlParts = parse_url($url);
    if (!is_array($urlParts) || strtolower((string)($urlParts['host'] ?? '')) !== strtolower((string)$baseParts['host'])) return null;
    if (strtolower((string)($urlParts['scheme'] ?? '')) !== strtolower((string)$baseParts['scheme'])) return null;
    return $url;
}

function spm_wh_sanitize_ai_reply(string $reply): string
{
    // All URLs must be server-generated links to active catalog slugs, not model output.
    $reply = preg_replace('~\[[^\]]*\]\(\s*https?://[^)]+\)~iu', '', $reply) ?? $reply;
    $reply = preg_replace('~https?://[^\s<>()]+~iu', '', $reply) ?? $reply;
    $reply = preg_replace('~\bwww\.[^\s<>()]+~iu', '', $reply) ?? $reply;
    $reply = preg_replace('~\b(?:[a-z0-9-]+\.)+[a-z]{2,}(?:/[^\s<>()]*)?~iu', '', $reply) ?? $reply;
    $reply = preg_replace('/[ \t]+\n/u', "\n", $reply) ?? $reply;
    $reply = preg_replace('/\n{3,}/u', "\n\n", trim($reply)) ?? trim($reply);
    if (function_exists('mb_substr')) return mb_substr($reply, 0, 1600, 'UTF-8');
    return substr($reply, 0, 1600);
}
