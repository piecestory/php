@php
    use App\Domain\Content\Models\Post;
    use App\Support\Text\Markdown;

    $title = (string) $post->translate('title');
    $body = $post->translate('body');
    $cover = $post->responsiveImage(Post::MEDIA_COVER);
@endphp

<x-layouts.store :title="$post->translate('meta_title') ?? $title"
    :description="$post->translate('meta_description') ?? $post->translate('excerpt') ?? Markdown::toText($body)"
    :image="$cover['src'] ?? null" og-type="article">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('blog.title'), 'href' => localized_route('blog')], ['label' => $title]]" />

        <article class="mx-auto mt-6 max-w-3xl">
            <header>
                <p class="text-sm text-ink-soft">
                    <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('j F Y') }}</time>
                </p>
                <h1 class="mt-3 text-display-lg">{{ $title }}</h1>
                <x-ui.ornament class="mt-4 w-40" />
            </header>
            @if ($cover)
                <x-ui.image :src="$cover['src']" :srcset="$cover['srcset']" sizes="(min-width: 768px) 48rem, 100vw" :alt="''" ratio="3/2" eager class="mt-8 rounded-xs border border-line" />
            @endif
            <div class="prose-content mt-8">{{ Markdown::toHtml($body) }}</div>
        </article>

        @if ($more->isNotEmpty())
            <section aria-labelledby="more-articles" class="mt-20 border-t border-line pt-12">
                <h2 id="more-articles" class="text-display-md">{{ __('blog.more') }}</h2>
                <div class="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($more as $other)
                        <x-blog.card :post="$other" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.store>
