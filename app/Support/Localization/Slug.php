<?php

declare(strict_types=1);

namespace App\Support\Localization;

/** URL slugs that keep Arabic letters ("ساعة رف فرنسية" → "ساعة-رف-فرنسية"); Latin is lower-cased. */
final class Slug
{
    public static function make(string $text): string
    {
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);

        return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($text)), '-');
    }
}
