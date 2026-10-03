<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Support\Localization\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Brand logo in the surrounding text colour. Drawn as a CSS mask over the SVG file (public/images/brand)
 * rather than inlined: the file is downloaded once and cached, instead of adding ~15–25 KB to every
 * page for each logo (header, mobile menu, footer).
 */
class Logo extends Component
{
    /** @var array<string, array{url: string, ratio: string}> */
    private static array $files = [];

    /** @param 'horizontal'|'compact'|'emblem' $variant */
    public function __construct(public string $variant = 'horizontal') {}

    /** @return array{url: string, ratio: string} versioned URL (cache-busting) and width/height from the viewBox */
    public function file(): array
    {
        $name = match ($this->variant) {
            'emblem' => 'emblem',
            'compact' => 'compact-ar',
            default => Locales::resolve() === 'en' ? 'horizontal-en' : 'horizontal-ar',
        };

        return self::$files[$name] ??= self::describe($name);
    }

    public function render(): View
    {
        return view('components.logo');
    }

    /** @return array{url: string, ratio: string} */
    private static function describe(string $name): array
    {
        $path = public_path("images/brand/{$name}.svg");
        $svg = (string) file_get_contents($path);
        preg_match('/viewBox="\s*[-\d.]+\s+[-\d.]+\s+([\d.]+)\s+([\d.]+)\s*"/', $svg, $box);

        return [
            'url' => asset("images/brand/{$name}.svg").'?v='.hash('xxh3', $svg),
            'ratio' => isset($box[2]) ? "{$box[1]} / {$box[2]}" : '1 / 1',
        ];
    }
}
