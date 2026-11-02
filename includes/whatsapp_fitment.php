<?php
/**
 * Pure motor-fitment lookup helpers shared by the webhook and offline tests.
 * The mapping only repeats recommendations visible on the supplied FDR 2026
 * catalogue cards; it does not infer front/rear tire sizes.
 */
declare(strict_types=1);

function spm_fitment_normalize(string $text): string
{
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $text = preg_replace('/[^\\p{L}\\p{N}]+/u', ' ', $text) ?? $text;
    return trim(preg_replace('/\\s+/u', ' ', $text) ?? $text);
}

function spm_wh_fitment(string $message): ?array
{
    $file = dirname(__DIR__) . '/api/whatsapp/fdr_fitment.json';
    if (!is_readable($file)) return null;
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data) || !isset($data['vehicles']) || !is_array($data['vehicles'])) return null;
    $normalized = ' ' . spm_fitment_normalize($message) . ' ';
    $matches = [];
    foreach ($data['vehicles'] as $vehicle) {
        if (!is_array($vehicle)) continue;
        foreach (($vehicle['aliases'] ?? []) as $alias) {
            $alias = spm_fitment_normalize((string)$alias);
            if ($alias !== '' && strpos($normalized, ' ' . $alias . ' ') !== false) {
                $matches[(string)($vehicle['key'] ?? '')] = [
                    'vehicle' => $vehicle,
                    'alias_length' => strlen($alias),
                ];
            }
        }
    }
    if (!$matches) return null;
    // Longest complete alias wins: Vario 160 must beat generic Vario.
    uasort($matches, static fn(array $a, array $b): int => $b['alias_length'] <=> $a['alias_length']);
    return reset($matches)['vehicle'] ?? null;
}

/**
 * Resolve a short response such as "125", "160", or "Street" after the bot
 * has asked the customer to select a variant. Only resolve from the immediately
 * preceding clarification exchange; unrelated historical mentions are ignored.
 */
function spm_wh_resolve_fitment_from_history(string $message, ?array $currentFitment, array $history): ?array
{
    if ($currentFitment !== null) return $currentFitment;
    if (!$history) return null;

    $lastIndex = count($history) - 1;
    $last = $history[$lastIndex] ?? null;
    if (!is_array($last) || ($last['role'] ?? '') !== 'assistant') return null;
    $assistantText = spm_fitment_normalize((string)($last['content'] ?? ''));
    if ($assistantText === '') return null;

    $previousUser = null;
    for ($i = $lastIndex - 1; $i >= 0; $i--) {
        if (($history[$i]['role'] ?? '') === 'user') {
            $previousUser = (string)($history[$i]['content'] ?? '');
            break;
        }
    }
    if ($previousUser === null || $previousUser === '') return null;

    $ambiguous = spm_wh_fitment($previousUser);
    if (!is_array($ambiguous) || !str_contains((string)($ambiguous['key'] ?? ''), 'model belum disebutkan')) return null;

    $options = $ambiguous['variant_options'] ?? [];
    if (!is_array($options) || count($options) < 2) return null;
    $family = trim(preg_replace('/\\s*\\(model belum disebutkan\\).*$/u', '', (string)($ambiguous['key'] ?? '')) ?? '');
    $family = spm_fitment_normalize($family);
    if ($family === '') return null;

    // Verify the immediately preceding assistant message really surfaced the
    // discriminating variants; this prevents an old unrelated car model from
    // being revived just because it appeared somewhere in history.
    $dataFile = dirname(__DIR__) . '/api/whatsapp/fdr_fitment.json';
    $data = json_decode(is_readable($dataFile) ? (string)file_get_contents($dataFile) : '', true);
    if (!is_array($data) || !is_array($data['vehicles'] ?? null)) return null;
    $candidates = [];
    foreach ($data['vehicles'] as $vehicle) {
        if (!is_array($vehicle)) continue;
        $key = spm_fitment_normalize((string)($vehicle['key'] ?? ''));
        if ($key === '' || str_contains($key, 'model belum disebutkan')) continue;
        if (!str_starts_with($key, $family . ' ')) continue;
        $suffix = trim(substr($key, strlen($family)));
        if ($suffix !== '') $candidates[] = ['vehicle' => $vehicle, 'suffix' => $suffix];
    }
    if (count($candidates) < 2) return null;

    $assistantCovered = 0;
    foreach ($candidates as $candidate) {
        $suffix = (string)$candidate['suffix'];
        // Current suffixes are distinctive variant labels, e.g. 125, 160, 110, street.
        if (str_contains(' ' . $assistantText . ' ', ' ' . $suffix . ' ')) $assistantCovered++;
    }
    if ($assistantCovered < 2) return null;

    $answer = spm_fitment_normalize($message);
    if ($answer === '') return $ambiguous;
    $matched = [];
    foreach ($candidates as $candidate) {
        $suffix = (string)$candidate['suffix'];
        // Match the entire suffix phrase, or any unique variant token (e.g. "yang 125").
        $suffixTokens = explode(' ', $suffix);
        $suffixMatched = str_contains(' ' . $answer . ' ', ' ' . $suffix . ' ');
        if (!$suffixMatched && count($suffixTokens) === 1) {
            $suffixMatched = str_contains(' ' . $answer . ' ', ' ' . $suffixTokens[0] . ' ');
        }
        if ($suffixMatched) $matched[] = $candidate['vehicle'];
    }
    if (count($matched) === 1) return $matched[0];
    return $ambiguous;
}
