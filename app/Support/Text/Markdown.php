<?php

declare(strict_types=1);

namespace App\Support\Text;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Staff write pages and articles in Markdown. Raw HTML is escaped and unsafe links (javascript:, data:)
 * are dropped, so content can never inject scripts into the site.
 */
final class Markdown
{
    public static function toHtml(?string $markdown): HtmlString
    {
        return new HtmlString(Str::markdown((string) $markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]));
    }

    /** Plain text for meta descriptions and excerpts. */
    public static function toText(?string $markdown, int $limit = 160): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags(Str::markdown((string) $markdown, ['html_input' => 'strip']))));

        return Str::limit($text, $limit);
    }
}
