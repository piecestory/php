<x-layouts.store :title="__('site.faq.title')" :description="__('site.faq.intro')">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('site.faq.title')]]" />

        <header class="mt-6 max-w-2xl">
            <h1 class="text-display-lg">{{ __('site.faq.title') }}</h1>
            <x-ui.ornament class="mt-4 w-40" />
            <p class="mt-4 text-ink-soft">{{ __('site.faq.intro') }}</p>
        </header>

        @if ($faqs->isEmpty())
            <x-ui.empty-state icon="info" :title="__('site.faq.empty')" class="mt-10 rounded-xs border border-line bg-paper" />
        @else
            {{-- Native <details>: accessible and works without JavaScript --}}
            <div class="mt-10 max-w-3xl divide-y divide-line border-y border-line">
                @foreach ($faqs as $faq)
                    <details class="group py-1" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 font-display text-display-sm marker:hidden [&::-webkit-details-marker]:hidden">
                            {{ $faq->translate('question') }}
                            <x-ui.icon name="plus" class="size-5 text-bronze transition-transform group-open:rotate-45" />
                        </summary>
                        <div class="pb-6 leading-relaxed whitespace-pre-line text-ink-soft">{{ $faq->translate('answer') }}</div>
                    </details>
                @endforeach
            </div>
        @endif

        @if (\App\Support\Localization\LocalizedRoute::has('contact'))
            <p class="mt-10 text-sm text-ink-soft">
                {{ __('site.faq.more') }}
                <a href="{{ localized_route('contact') }}" class="font-medium text-bronze hover:underline">{{ __('site.nav.contact') }}</a>
            </p>
        @endif
    </div>
</x-layouts.store>
