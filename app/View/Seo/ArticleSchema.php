<?php

declare(strict_types=1);

namespace App\View\Seo;

use App\Domain\Content\Models\Post;
use App\Support\Text\Markdown;

/** schema.org Article for a journal post (headline, dates, cover, author, publisher). */
final class ArticleSchema
{
    /** @return array<string, mixed> */
    public static function for(Post $post): array
    {
        $cover = $post->getFirstMedia(Post::MEDIA_COVER);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => (string) $post->translate('title'),
            'description' => $post->translate('excerpt') ?? Markdown::toText($post->translate('body'), 200),
            'image' => $cover ? ($cover->hasGeneratedConversion('w1600') ? $cover->getUrl('w1600') : $cover->getUrl()) : null,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'inLanguage' => app()->getLocale(),
            'mainEntityOfPage' => localized_route('blog.post', $post),
            // The store, not the staff member: their name is not shown on the article either.
            'author' => JsonLd::organization(),
            'publisher' => JsonLd::organization(),
        ], fn (mixed $value) => $value !== null);
    }
}
