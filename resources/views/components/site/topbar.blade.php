@php
    use App\Support\Localization\LocalizedRoute;

    $other = app()->getLocale() === 'ar' ? 'en' : 'ar';
@endphp

<div class="bg-night text-[0.75rem] text-ivory/80">
    <div class="container-page flex h-9 items-center justify-between gap-6">
        <ul class="flex min-w-0 items-center gap-5">
            <li class="truncate">{{ __('site.topbar.delivery') }}</li>
            <li class="hidden truncate md:block"><span class="me-5 text-gold/60" aria-hidden="true">|</span>{{ __('site.topbar.pickup') }}</li>
            <li class="hidden truncate lg:block"><span class="me-5 text-gold/60" aria-hidden="true">|</span>{{ __('site.topbar.response') }}</li>
        </ul>
        <ul class="flex shrink-0 items-center gap-5">
            @foreach (\App\View\Navigation::utility() as $item)
                <li class="hidden sm:block"><a href="{{ $item['url'] }}" class="transition-colors hover:text-gold">{{ $item['label'] }}</a></li>
            @endforeach
            <li>
                <a href="{{ LocalizedRoute::switchTo($other) }}" hreflang="{{ $other }}" lang="{{ $other }}"
                    aria-label="{{ __('site.nav.language_label') }}"
                    class="inline-flex items-center gap-1.5 font-medium text-ivory transition-colors hover:text-gold">
                    <x-ui.icon name="globe" class="size-3.5" />{{ __('site.nav.language') }}
                </a>
            </li>
        </ul>
    </div>
</div>
