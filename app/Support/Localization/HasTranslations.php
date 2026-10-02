<?php

declare(strict_types=1);

namespace App\Support\Localization;

/**
 * Reads bilingual columns stored as `<attribute>_ar` / `<attribute>_en`.
 * Falls back to Arabic (the primary language) when the requested locale is empty.
 */
trait HasTranslations
{
    public function translate(string $attribute, ?string $locale = null): ?string
    {
        $locale = Locales::resolve($locale);
        $value = $this->getAttribute("{$attribute}_{$locale}");

        if (($value === null || $value === '') && $locale !== Locales::PRIMARY) {
            $value = $this->getAttribute($attribute.'_'.Locales::PRIMARY);
        }

        return $value;
    }
}
