{{-- Auction teaser: cover, phase, dates, title, number of lots. --}}
@props(['auction', 'eager' => false])

@php
    $image = $auction->responsiveImage(\App\Domain\Auctions\Models\Auction::MEDIA_COVER);
    $phase = $auction->phase();
    $ended = $phase === \App\Domain\Auctions\Enums\AuctionPhase::Ended;
@endphp

<article {{ $attributes->class(['group']) }}>
    <a href="{{ localized_route('auctions.show', $auction) }}" class="block">
        <div class="relative">
            <x-ui.image :src="$image['src'] ?? null" :srcset="$image['srcset'] ?? null" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw"
                :alt="''" ratio="3/2" :$eager @class(['rounded-xs border border-line transition-opacity group-hover:opacity-90', 'grayscale-[40%]' => $ended]) />
            <x-auction.phase :$phase class="absolute start-3 top-3" />
        </div>
        <p class="mt-4 text-xs text-ink-soft">
            @if ($ended)
                {{ __('auctions.ended_on') }} <time datetime="{{ $auction->ends_at->toIso8601String() }}">{{ $auction->ends_at->translatedFormat('j F Y') }}</time>
            @else
                <time datetime="{{ $auction->starts_at->toIso8601String() }}">{{ $auction->starts_at->translatedFormat('j F Y') }}</time>
                –
                <time datetime="{{ $auction->ends_at->toIso8601String() }}">{{ $auction->ends_at->translatedFormat('j F Y') }}</time>
            @endif
        </p>
        <h3 class="mt-2 font-display text-display-sm group-hover:text-bronze">{{ $auction->translate('title') }}</h3>
        @php $lots = $auction->lots_count ?? $auction->lots->count(); @endphp
        @if ($lots > 0 || ! $ended)
            <p class="mt-1 text-sm text-ink-soft">{{ trans_choice('auctions.lots_count', $lots) }}</p>
        @endif
    </a>
</article>
