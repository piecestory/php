@php
    use App\Support\Text\Markdown;

    $title = (string) $page->translate('title');
    $body = $page->translate('body');
@endphp

<x-layouts.store :title="$page->translate('meta_title') ?? $title" :description="$page->translate('meta_description') ?? Markdown::toText($body)">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => $title]]" />

        <article class="mt-6">
            <header class="max-w-2xl">
                <h1 class="text-display-lg">{{ $title }}</h1>
                <x-ui.ornament class="mt-4 w-40" />
            </header>
            <div class="prose-content mt-8">{{ Markdown::toHtml($body) }}</div>
            <p class="mt-10 text-xs text-ink-faint">{{ __('site.page.updated', ['date' => $page->updated_at?->translatedFormat('j F Y')]) }}</p>
        </article>
    </div>
</x-layouts.store>
