<?php
/**
 * Deterministic order-intent helpers for Starparts Motor WhatsApp.
 * This does not create an order or payment; it helps route a customer to an
 * active product page and interpret a selection from the immediately prior reply.
 */
declare(strict_types=1);

function spm_wh_order_normalize(string $text): string
{
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $text = preg_replace('/[^\\p{L}\\p{N}]+/u', ' ', $text) ?? $text;
    return trim(preg_replace('/\\s+/u', ' ', $text) ?? $text);
}

/** Return 0-based choice number, only for a clear standalone choice utterance. */
function spm_wh_order_selection_index(string $message): ?int
{
    $n = spm_wh_order_normalize($message);
    // Strip one or two polite suffixes (e.g. "nomor 2 ya kak").
    for ($i = 0; $i < 2; $i++) {
        $n = preg_replace('/\\s+(?:ya\\s+kak|ya\\s+dong|ya|dong|deh|kak)$/u', '', $n) ?? $n;
    }
    $numericPatterns = [
        '/^(?:nomor|no|pilihan|opsi|produk)\\s*(?:ke\\s*)?([1-5])$/u',
        '/^(?:ambil|pilih|mau|saya pilih)\\s+(?:yang\\s+)?(?:(?:nomor|no|opsi)\\s*)?([1-5])$/u',
        '/^(?:yang\\s+)?ke\\s*([1-5])$/u',
    ];
    foreach ($numericPatterns as $pattern) {
        if (preg_match($pattern, $n, $m)) return ((int)$m[1]) - 1;
    }
    $ordinals = [
        'pertama' => 0, 'kesatu' => 0, 'ke satu' => 0, 'yang pertama' => 0,
        'kedua' => 1, 'ke dua' => 1, 'yang kedua' => 1,
        'ketiga' => 2, 'ke tiga' => 2, 'yang ketiga' => 2,
        'keempat' => 3, 'ke empat' => 3, 'yang keempat' => 3,
        'kelima' => 4, 'ke lima' => 4, 'yang kelima' => 4,
    ];
    if (array_key_exists($n, $ordinals)) return $ordinals[$n];
    if (preg_match('/^(?:saya\\s+pilih|pilih|ambil|mau)\\s+(?:yang\\s+)?(pertama|kesatu|kedua|ketiga|keempat|kelima)$/u', $n, $m)) {
        return $ordinals[$m[1]] ?? null;
    }
    return null;
}

/** Classify direct buy/order or contextual product-link requests. */
function spm_wh_order_intent_kind(string $message): string
{
    $n = ' ' . spm_wh_order_normalize($message) . ' ';
    if (spm_wh_order_selection_index($message) !== null) return 'reference';
    if (preg_match('/\\b(?:link|linknya|tautan|url|halaman produk|checkout|link produk)\\b/u', $n)) return 'reference';
    if (preg_match('/\\b(?:order|pesan|pesankan|memesan|beli|pembelian|checkout|bayar|pembayaran|transaksi|lanjut beli|mau beli|ingin beli)\\b/u', $n)) return 'order';
    if (preg_match('/\\b(?:yang itu|yang tadi|produk tadi|pilihan tadi|mau itu|saya mau itu)\\b/u', $n)) return 'reference';
    return 'none';
}

/**
 * Extract canonical product slugs only from the most recent assistant turn.
 * URL host/scheme/path must match the configured BASE_URL and URL builder.
 */
function spm_wh_order_recent_product_slugs(array $history): array
{
    if (!$history || !defined('BASE_URL') || !function_exists('spm_wh_product_page_url')) return [];
    $last = $history[count($history) - 1] ?? null;
    if (!is_array($last) || ($last['role'] ?? '') !== 'assistant') return [];
    $content = (string)($last['content'] ?? '');
    if (!str_contains($content, '/produk/')) return [];

    preg_match_all('~https?://[^\\s<>\\x22]+~iu', $content, $matches);
    $slugs = [];
    foreach (($matches[0] ?? []) as $rawUrl) {
        $candidate = rtrim((string)$rawUrl, ".,;!?:'\\\"`]");
        $parts = parse_url($candidate);
        if (!is_array($parts)) continue;
        if (!in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)) continue;
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) continue;
        $path = (string)($parts['path'] ?? '');
        if (!preg_match('~(?:^|/)produk/([a-z0-9]+(?:-[a-z0-9]+)*)$~i', $path, $slugMatch)) continue;
        $slug = strtolower((string)$slugMatch[1]);
        $canonical = spm_wh_product_page_url($slug);
        if ($canonical === null || strcasecmp($candidate, $canonical) !== 0) continue;
        if (!in_array($slug, $slugs, true)) $slugs[] = $slug;
    }
    return $slugs;
}

/** Safe, customer-facing order guidance. No claim that a transaction was placed. */
function spm_wh_order_single_product_reply(array $product): string
{
    $name = trim((string)($product['name'] ?? 'produk yang dipilih'));
    return 'Siap, Kak. Ini halaman produk ' . $name . '. Silakan buka tautannya untuk memeriksa detail produk dan mengikuti langkah pembelian yang tersedia di website. Pesan WhatsApp ini belum membuat pesanan atau pembayaran secara otomatis.';
}

function spm_wh_order_choose_previous_reply(): string
{
    return 'Siap, Kak. Dari rekomendasi sebelumnya ada beberapa pilihan. Kakak ingin melanjutkan produk yang mana? Balas nama produknya atau pilih “nomor 1”, “nomor 2”, dan seterusnya dari daftar yang Tama kirim sebelumnya.';
}

function spm_wh_order_choose_current_reply(): string
{
    return 'Siap, Kak. Saya menemukan beberapa pilihan yang sesuai. Saya kirim halaman produk aktif di bawah ini. Silakan pilih nama produk atau balas nomor pilihannya, lalu saya bantu arahkan ke halaman produk yang tepat.';
}

function spm_wh_order_ask_product_reply(): string
{
    return 'Tentu, Kak. Produk mana yang ingin dibeli? Sebutkan nama produk (misalnya FDR Genzi PRO atau OSRAM) atau tipe motor lengkap agar Tama bisa mengirim halaman produk yang sesuai.';
}
