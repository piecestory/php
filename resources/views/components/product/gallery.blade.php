{{-- $images: list of ['src', 'srcset', 'alt', 'large', 'thumb'] --}}
@props(['images', 'name'])

@if (count($images) === 0)
    <x-ui.image :alt="$name" ratio="1/1" class="rounded-xs border border-line" />
@else
    <div x-data="gallery" x-on:keydown.window="onKey" x-on:touchstart.passive="touchStart" x-on:touchend="touchEnd"
        aria-label="{{ __('product.gallery.label') }}" role="region">
        {{-- Main image --}}
        <div class="relative overflow-hidden rounded-xs border border-line bg-paper">
            @foreach ($images as $image)
                <figure data-index="{{ $loop->index }}" x-show="isCurrent" @unless ($loop->first) x-cloak @endunless class="m-0">
                    <button type="button" x-on:click="openZoom" class="group block w-full cursor-zoom-in" aria-label="{{ __('product.gallery.open') }}">
                        <img src="{{ $image['src'] }}" @if ($image['srcset']) srcset="{{ $image['srcset'] }}" sizes="(min-width: 1024px) 50vw, 100vw" @endif
                            alt="{{ $image['alt'] ?: $name }}" class="aspect-square w-full object-contain p-2 transition-transform duration-500 group-hover:scale-[1.02]"
                            @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif decoding="async"
                            data-fallback="{{ asset('images/placeholder.svg') }}">
                    </button>
                </figure>
            @endforeach
            <span class="pointer-events-none absolute end-3 bottom-3 grid size-10 place-items-center rounded-full bg-paper/90 text-ink shadow-card">
                <x-ui.icon name="search" class="size-4" />
            </span>
        </div>

        {{-- Thumbnails (links to the full image without JavaScript) --}}
        @if (count($images) > 1)
            <ul class="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-6">
                @foreach ($images as $image)
                    <li>
                        <a href="{{ $image['large'] }}" data-index="{{ $loop->index }}" x-on:click.prevent="select" :aria-current="isCurrent"
                            class="block overflow-hidden rounded-xs border border-line bg-paper opacity-70 transition hover:opacity-100 aria-[current=true]:border-bronze aria-[current=true]:opacity-100">
                            <img src="{{ $image['thumb'] }}" alt="" loading="lazy" decoding="async" class="aspect-square w-full object-cover">
                            <span class="sr-only">{{ __('product.gallery.show', ['number' => $loop->iteration]) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Full-screen zoom --}}
        <div x-show="zoomed" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-label="{{ $name }}"
            class="fixed inset-0 z-50 flex flex-col bg-night/95 text-ivory">
            <div class="flex items-center justify-between p-4">
                <span class="numerals text-sm text-ivory/70" x-text="counter"></span>
                <button type="button" x-on:click="closeZoom" class="grid size-11 place-items-center rounded-full hover:bg-ivory/10">
                    <x-ui.icon name="x" class="size-6" /><span class="sr-only">{{ __('product.gallery.close') }}</span>
                </button>
            </div>
            <div class="relative flex flex-1 items-center justify-center overflow-auto px-4 pb-6 [touch-action:pinch-zoom]">
                @foreach ($images as $image)
                    <img data-index="{{ $loop->index }}" x-show="isCurrent" x-cloak src="{{ $image['large'] }}" alt="{{ $image['alt'] ?: $name }}"
                        loading="lazy" decoding="async" class="max-h-full max-w-full object-contain">
                @endforeach
                @if (count($images) > 1)
                    <button type="button" x-on:click="previous" class="absolute start-3 top-1/2 grid size-12 -translate-y-1/2 place-items-center rounded-full border border-ivory/30 hover:bg-ivory/10">
                        <x-ui.icon name="chevron-left" /><span class="sr-only">{{ __('product.gallery.previous') }}</span>
                    </button>
                    <button type="button" x-on:click="next" class="absolute end-3 top-1/2 grid size-12 -translate-y-1/2 place-items-center rounded-full border border-ivory/30 hover:bg-ivory/10">
                        <x-ui.icon name="chevron-right" /><span class="sr-only">{{ __('product.gallery.next') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
