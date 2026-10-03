<?php

declare(strict_types=1);

namespace App\Filament\Support;

use BackedEnum;
use Illuminate\Support\Str;

/**
 * Arabic labels for enum values in the admin. Enums with a storefront label() reuse it, so the
 * customer and the team see the same words; the rest read `admin.enums.<enum>.<value>`.
 */
final class EnumLabels
{
    public static function of(?BackedEnum $case): string
    {
        if ($case === null) {
            return '—';
        }

        if (method_exists($case, 'label')) {
            return (string) $case->label();
        }

        return __('admin.enums.'.Str::snake(class_basename($case)).'.'.$case->value);
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     * @param  list<BackedEnum>|null  $only
     * @return array<string, string> value => label, for selects and filters
     */
    public static function options(string $enum, ?array $only = null): array
    {
        $options = [];
        foreach ($only ?? $enum::cases() as $case) {
            $options[(string) $case->value] = self::of($case);
        }

        return $options;
    }
}
