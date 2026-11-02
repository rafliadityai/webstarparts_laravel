<?php
/**
 * Helpers for the FDR catalog presentation (Phase 11).
 * Optional detail rows are stored in database table fdr_product_details.
 */

declare(strict_types=1);

if (!function_exists('fdr_details_table_exists')) {
    function fdr_details_table_exists(): bool
    {
        global $pdo;
        static $exists = null;
        if ($exists !== null) return $exists;
        try {
            $pdo->query('SELECT product_id FROM fdr_product_details LIMIT 0');
            $exists = true;
        } catch (Throwable $e) {
            $exists = false;
        }
        return $exists;
    }
}

if (!function_exists('fdr_get_product_detail')) {
    function fdr_get_product_detail(int $productId): array
    {
        global $pdo;
        if ($productId <= 0 || !fdr_details_table_exists()) return [];
        try {
            $stmt = $pdo->prepare('SELECT * FROM fdr_product_details WHERE product_id = ? LIMIT 1');
            $stmt->execute([$productId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('fdr_line_presentation')) {
    function fdr_line_presentation(string $categorySlug): array
    {
        $slug = strtolower(trim($categorySlug));
        if ($slug === 'fdr-ultimate') {
            return ['slug' => $slug, 'title' => 'FDR Ultimate', 'class' => 'fdr-line-ultimate', 'color' => '#343536', 'theme' => 'ultimate'];
        }
        if ($slug === 'fdr-champion') {
            return ['slug' => $slug, 'title' => 'FDR Champion', 'class' => 'fdr-line-champion', 'color' => '#c4bd36', 'theme' => 'champion'];
        }
        return ['slug' => 'fdr-tire', 'title' => 'FDR Tire', 'class' => 'fdr-line-tire', 'color' => '#e23c40', 'theme' => 'tire'];
    }
}

if (!function_exists('fdr_parse_text_rows')) {
    /** Parse admin text: one row per line, fields separated with |. */
    function fdr_parse_text_rows(?string $text, int $minFields = 2): array
    {
        $text = trim((string)$text);
        if ($text === '') return [];
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $fields = array_map('trim', explode('|', $line));
            if (count($fields) < $minFields) continue;
            if (count(array_filter($fields, static fn($v) => $v !== '')) === 0) continue;
            $rows[] = $fields;
        }
        return $rows;
    }
}

if (!function_exists('fdr_legacy_description_sections')) {
    /** Split existing product HTML into the named sections already present in Phase 9 data. */
    function fdr_legacy_description_sections(?string $html): array
    {
        $html = trim((string)$html);
        $result = ['intro' => '', 'benefits' => '', 'sizes' => '', 'performance' => '', 'technology' => '', 'other' => ''];
        if ($html === '') return $result;
        $pattern = '~<h[2-4][^>]*>.*?</h[2-4]>~is';
        preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[0])) {
            $result['intro'] = $html;
            return $result;
        }
        $first = $matches[0][0];
        $result['intro'] = trim(substr($html, 0, (int)$first[1]));
        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $headingHtml = (string)$matches[0][$i][0];
            $headingOffset = (int)$matches[0][$i][1];
            $contentStart = $headingOffset + strlen($headingHtml);
            $contentEnd = $i + 1 < $count ? (int)$matches[0][$i + 1][1] : strlen($html);
            $content = trim(substr($html, $contentStart, $contentEnd - $contentStart));
            $heading = strtolower(trim(strip_tags($headingHtml)));
            if (str_contains($heading, 'keunggulan') || str_contains($heading, 'advantage') || str_contains($heading, 'feature')) {
                $result['benefits'] .= $content;
            } elseif (str_contains($heading, 'size') || str_contains($heading, 'rims') || str_contains($heading, 'ukuran')) {
                $result['sizes'] .= $content;
            } elseif (str_contains($heading, 'performance') || str_contains($heading, 'rating') || str_contains($heading, 'penilaian')) {
                $result['performance'] .= $content;
            } elseif (str_contains($heading, 'technology') || str_contains($heading, 'teknologi')) {
                $result['technology'] .= $content;
            } else {
                $result['other'] .= $headingHtml . $content;
            }
        }
        return $result;
    }
}

if (!function_exists('fdr_hide_price_amounts')) {
    /** Remove visible currency amounts for the temporary public-catalog price hiding phase. */
    function fdr_hide_price_amounts(?string $text): string
    {
        $text = (string)$text;
        $text = preg_replace('/\b(?:Rp\.?|IDR)\s*[0-9][0-9.,]*(?:\s*(?:-|–|—)\s*(?:(?:Rp\.?|IDR)\s*)?[0-9][0-9.,]*)?/iu', '', $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;
        return $text;
    }
}

if (!function_exists('fdr_safe_content_html')) {
    function fdr_safe_content_html(?string $html): string
    {
        return strip_tags(fdr_hide_price_amounts((string)$html), '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3><h4><h5><h6><hr>');
    }
}


if (!function_exists('fdr_parse_legacy_size_rows')) {
    /** Convert legacy HTML list items such as "100/90-12 TL Rp 300.000 Rp 270.000" into structured rows. */
    function fdr_parse_legacy_size_rows(?string $html): array
    {
        $html = trim((string)$html);
        if ($html === '' || !preg_match_all('~<li\b[^>]*>(.*?)</li>~is', $html, $matches)) return [];
        $rows = [];
        foreach ($matches[1] as $itemHtml) {
            $text = html_entity_decode(strip_tags((string)$itemHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
            if ($text === '') continue;
            if (preg_match('/^(.*?)\s*Rp\.?\s*([0-9][0-9.,]*)(?:\s*Rp\.?\s*([0-9][0-9.,]*))?/i', $text, $m)) {
                $size = trim((string)$m[1]);
                $price1 = trim((string)($m[2] ?? ''));
                $price2 = trim((string)($m[3] ?? ''));
                if ($size === '') $size = $text;
                if ($price2 !== '') $rows[] = [$size, 'Rp ' . $price1, 'Rp ' . $price2, ''];
                elseif ($price1 !== '') $rows[] = [$size, '', 'Rp ' . $price1, ''];
                else $rows[] = [$size, '', '', ''];
            } else {
                $rows[] = [$text, '', '', ''];
            }
        }
        return $rows;
    }
}

if (!function_exists('fdr_product_price_range')) {
    function fdr_product_price_range(?string $text): string
    {
        $text = strip_tags((string)$text);
        if (!preg_match_all('/\bRp\.?\s*([0-9][0-9.,]*)/i', $text, $matches)) return '';
        $prices = [];
        foreach ($matches[1] as $raw) {
            $digits = preg_replace('/[^0-9]/', '', (string)$raw);
            if ($digits !== '' && strlen($digits) <= 12) $prices[] = (int)$digits;
        }
        $prices = array_values(array_unique($prices));
        if (!$prices) return '';
        sort($prices, SORT_NUMERIC);
        $format = static fn(int $n): string => 'Rp ' . number_format($n, 0, ',', '.');
        if (count($prices) === 1) return $format($prices[0]);
        return $format($prices[0]) . ' – ' . $format($prices[count($prices) - 1]);
    }
}

if (!function_exists('fdr_normalize_http_url')) {
    function fdr_normalize_http_url(?string $url): string
    {
        $url = trim((string)$url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return '';
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $url : '';
    }
}
