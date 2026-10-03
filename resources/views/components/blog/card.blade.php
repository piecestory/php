{{-- Article teaser: cover, date, title, excerpt. --}}
@props(['post', 'eager' => false])

@php
    $image = $post->responsiveImage(\App\Domain\Content\Models\Post::MEDIA_COVER);
    $excerpt = $post->translate('excerpt') ?? \App\Support\Text\Markdown::toText($post->translate('body'), 140);
@endphp

<article {{ $attributes->class(['group']) }}>
    <a href="{{ localized_route('blog.post', $post) }}" class="block">
        <x-ui.image :src="$image['src'] ?? null" :srcset="$image['srcset'] ?? null" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw"
            :alt="''" ratio="3/2" :$eager class="rounded-xs border border-line transition-opacity group-hover:opacity-90" />
        <p class="mt-4 text-xs text-ink-soft">{{ $post->published_at?->translatedFormat('j F Y') }}</p>
        <h2 class="mt-2 font-display text-display-sm group-hover:text-bronze">{{ $post->translate('title') }}</h2>
        @if ($excerpt)
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink-soft">{{ $excerpt }}</p>
        @endif
    </a>
</article>
