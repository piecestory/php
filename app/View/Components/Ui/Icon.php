<?php

declare(strict_types=1);

namespace App\View\Components\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * Inline line icon from resources/svg/icons (Lucide, ISC licence).
 * Icons are drawn for left-to-right; directional ones are mirrored automatically in RTL.
 */
class Icon extends Component
{
    private const array DIRECTIONAL = ['arrow-left', 'arrow-right', 'chevron-left', 'chevron-right'];

    /** @var array<string, string> */
    private static array $paths = [];

    public function __construct(public string $name) {}

    public function paths(): string
    {
        return self::$paths[$this->name] ??= $this->load();
    }

    public function mirrored(): bool
    {
        return in_array($this->name, self::DIRECTIONAL, true);
    }

    public function render(): View
    {
        return view('components.ui.icon');
    }

    private function load(): string
    {
        $file = resource_path("svg/icons/{$this->name}.svg");

        if (! preg_match('/^[a-z0-9-]+$/', $this->name) || ! is_file($file)) {
            throw new InvalidArgumentException("Unknown icon [{$this->name}].");
        }

        return trim((string) file_get_contents($file));
    }
}
