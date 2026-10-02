<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Support\Localization\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Inline brand logo (inherits the surrounding text colour). Internal SVG ids are made unique per
 * render so the logo can appear several times on one page (header + footer) without id clashes.
 */
class Logo extends Component
{
    private static int $instances = 0;

    /** @var array<string, string> */
    private static array $sources = [];

    /** @param 'horizontal'|'compact'|'emblem' $variant */
    public function __construct(public string $variant = 'horizontal') {}

    public function svg(): string
    {
        $file = match ($this->variant) {
            'emblem' => 'emblem',
            'compact' => 'compact-ar',
            default => Locales::resolve() === 'en' ? 'horizontal-en' : 'horizontal-ar',
        };

        $source = self::$sources[$file] ??= (string) file_get_contents(resource_path("images/brand/{$file}.svg"));
        $suffix = '-'.++self::$instances;

        $svg = (string) preg_replace('/\bid="([^"]+)"/', 'id="$1'.$suffix.'"', $source);
        $svg = (string) preg_replace('/url\(#([^)]+)\)/', 'url(#$1'.$suffix.')', $svg);

        // Accessible name comes from the surrounding link; the SVG itself is decorative.
        return (string) preg_replace(
            ['/<svg /', '/ role="img" aria-label="[^"]*"/', '/<title>.*?<\/title>/'],
            ['<svg aria-hidden="true" focusable="false" class="h-full w-auto" ', '', ''],
            $svg,
            1,
        );
    }

    public function render(): View
    {
        return view('components.logo');
    }
}
