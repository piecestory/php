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

    /**
     * Text stored in the search index: the normalized words plus each word without its
     * Arabic definite article / attached prefix, so "ساعة" finds "الساعة" and "بالذهب" finds "ذهب".
     */
    public static function forIndex(?string $text): string
    {
        $words = array_filter(explode(' ', self::normalize($text)));
        $extra = array_filter(array_map(self::stripPrefix(...), $words), fn (string $w) => ! in_array($w, $words, true));

        return implode(' ', array_unique([...$words, ...$extra]));
    }

    /** @return list<string> normalized search words without prefixes, de-duplicated */
    public static function queryTerms(?string $query): array
    {
        $words = array_filter(explode(' ', self::normalize($query)));

        return array_values(array_unique(array_map(self::stripPrefix(...), $words)));
    }

    private static function stripPrefix(string $word): string
    {
        $stripped = (string) preg_replace('/^(وال|بال|كال|فال|لل|ال)/u', '', $word);

        // Keep short words intact ("الف" must not become "ف").
        return mb_strlen($stripped) >= 3 ? $stripped : $word;
    }
}
