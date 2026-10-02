<?php

declare(strict_types=1);

namespace App\Support\Search;

/**
 * Normalizes Arabic/English text so searches match regardless of hamza forms,
 * taa marbuta, alef maqsura, diacritics, tatweel or letter case.
 * The same normalization is applied to stored text and to search queries.
 */
final class ArabicNormalizer
{
    private const array REPLACEMENTS = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ة' => 'ه',
        'ى' => 'ي',
        'ؤ' => 'و',
        'ئ' => 'ي',
    ];

    public static function normalize(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $text = strip_tags($text);
        // Diacritics (tashkeel), superscript alef and tatweel
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);
        $text = strtr($text, self::REPLACEMENTS);
        $text = mb_strtolower($text);
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return trim($text);
    }
}
