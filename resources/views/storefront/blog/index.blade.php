@php
    use App\Domain\Content\Models\Post;
@endphp

<x-layouts.store :title="__('blog.title')" :description="__('blog.intro')">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('blog.title')]]" />

        <header class="mt-6 max-w-2xl">
            <h1 class="text-display-lg">{{ __('blog.title') }}</h1>
            <x-ui.ornament class="mt-4 w-40" />
            <p class="mt-4 text-ink-soft">{{ __('blog.intro') }}</p>
        </header>

        @if ($posts->isEmpty())
            <x-ui.empty-state icon="info" :title="__('blog.empty')" class="mt-10 rounded-xs border border-line bg-paper" />
        @else
            <div class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-blog.card :$post :eager="$loop->first" />
                @endforeach
            </div>
            <div class="mt-12">{{ $posts->links('partials.pagination') }}</div>
        @endif
    </div>
</x-layouts.store>
