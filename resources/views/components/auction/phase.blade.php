{{-- Upcoming / live / ended label for an auction. --}}
@props(['phase'])

<x-ui.badge {{ $attributes }} :variant="match ($phase) { \App\Domain\Auctions\Enums\AuctionPhase::Live => 'sale', \App\Domain\Auctions\Enums\AuctionPhase::Upcoming => 'rare', default => 'neutral' }">
    {{ $phase->label() }}
</x-ui.badge>
