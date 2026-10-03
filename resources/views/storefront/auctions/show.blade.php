@php
    use App\Domain\Auctions\Enums\AuctionPhase;
    use App\Domain\Auctions\Models\Auction;
    use App\Domain\Identity\Models\User;
    use App\Support\Money\Money;
    use App\Support\Phone\SaudiMobile;
    use Illuminate\Support\Str;

    $title = (string) $auction->translate('title');
    $description = $auction->translate('description');
    $cover = $auction->responsiveImage(Auction::MEDIA_COVER);
    $open = $phase->acceptsInterest();
    $user = auth()->user();
    $customer = $user instanceof User ? $user : null;
    $currency = Money::currency();
@endphp

<x-layouts.store :title="$title" :description="$description ? Str::limit($description, 160) : __('auctions.intro')" :image="$cover['src'] ?? null">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('auctions.title'), 'href' => localized_route('auctions')], ['label' => $title]]" />

        <header class="mt-6 grid gap-8 lg:grid-cols-2 lg:items-center">
            <div>
                <x-auction.phase :$phase />
                <h1 class="mt-4 text-display-lg">{{ $title }}</h1>
                <x-ui.ornament class="mt-4 w-40" />
                <dl class="mt-6 grid max-w-md grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-ink-soft">{{ __('auctions.starts') }}</dt>
                        <dd class="mt-1 font-medium"><time datetime="{{ $auction->starts_at->toIso8601String() }}">{{ $auction->starts_at->translatedFormat('j F Y — H:i') }}</time></dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">{{ __('auctions.ends') }}</dt>
                        <dd class="mt-1 font-medium"><time datetime="{{ $auction->ends_at->toIso8601String() }}">{{ $auction->ends_at->translatedFormat('j F Y — H:i') }}</time></dd>
                    </div>
                </dl>
                @if ($description)
                    <p class="mt-6 max-w-xl whitespace-pre-line leading-relaxed text-ink-soft">{{ $description }}</p>
                @endif
                @if ($open)
                    <x-ui.button href="#interest" class="mt-6" icon="arrow-right">{{ __('auctions.interest.title') }}</x-ui.button>
                @endif
            </div>
            @if ($cover)
                <x-ui.image :src="$cover['src']" :srcset="$cover['srcset']" sizes="(min-width: 1024px) 45vw, 100vw" :alt="''" ratio="3/2" eager class="rounded-xs border border-line" />
            @endif
        </header>

        {{-- An ended auction without lots has nothing to promise: the section is left out. --}}
        @if ($open || $auction->lots->isNotEmpty())
        <section aria-labelledby="lots" class="mt-16 border-t border-line pt-12">
            <h2 id="lots" class="text-display-md">{{ __('auctions.lots') }}</h2>
            @if ($auction->lots->isEmpty())
                <p class="mt-6 text-ink-soft">{{ __('auctions.no_lots') }}</p>
            @else
                <ul class="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($auction->lots as $lot)
                        @php
                            $image = $lot->coverImage();
                            $lotDescription = $lot->translate('description');
                        @endphp
                        <li id="lot-{{ $lot->lot_number }}" class="flex flex-col">
                            <x-ui.image :src="$image['src'] ?? null" :srcset="$image['srcset'] ?? null" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw"
                                :alt="(string) $lot->translate('title')" ratio="4/5" class="rounded-xs border border-line" />
                            <p class="mt-4 text-xs font-medium tracking-wide text-bronze-deep">{{ __('auctions.lot_number', ['number' => $lot->lot_number]) }}</p>
                            <h3 class="mt-1 font-display text-display-sm">{{ $lot->translate('title') }}</h3>
                            @if ($lotDescription)
                                <p class="mt-2 line-clamp-4 whitespace-pre-line text-sm leading-relaxed text-ink-soft">{{ $lotDescription }}</p>
                            @endif
                            <dl class="mt-3 space-y-1 text-sm">
                                <div class="flex flex-wrap items-baseline gap-x-2">
                                    <dt class="text-ink-soft">{{ __('auctions.starting_price') }}:</dt>
                                    <dd class="font-semibold"><span class="numerals">{{ Money::amount((string) $lot->starting_price) }}</span> {{ $currency }}</dd>
                                </div>
                                @if ($lot->estimate_low !== null && $lot->estimate_high !== null)
                                    <div class="flex flex-wrap items-baseline gap-x-2">
                                        <dt class="text-ink-soft">{{ __('auctions.estimate') }}:</dt>
                                        <dd><span class="numerals">{{ __('auctions.estimate_range', ['low' => Money::amount((string) $lot->estimate_low), 'high' => Money::amount((string) $lot->estimate_high)]) }}</span> {{ $currency }}</dd>
                                    </div>
                                @endif
                            </dl>
                            @if ($open)
                                <a href="{{ localized_route('auctions.show', [$auction, 'lot' => $lot->id]) }}#interest" class="mt-4 text-sm font-medium text-bronze-deep underline-offset-4 hover:underline">
                                    {{ __('auctions.interested_in_lot') }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
        @endif

        <section id="interest" aria-labelledby="interest-title" class="mt-16 grid scroll-mt-24 gap-10 border-t border-line pt-12 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div>
                <h2 id="interest-title" class="text-display-md">{{ __('auctions.interest.title') }}</h2>

                @if (session('interest_registered'))
                    <x-ui.alert variant="success" class="mt-6">{{ __('auctions.interest.registered') }}</x-ui.alert>
                @elseif (session('interest_closed') || ! $open)
                    <x-ui.alert class="mt-6">{{ $phase === AuctionPhase::Ended ? __('auctions.interest.ended_text') : __('auctions.interest.closed') }}</x-ui.alert>
                    <x-ui.button :href="localized_route('auctions')" variant="dark" size="sm" class="mt-4">{{ __('auctions.title') }}</x-ui.button>
                @else
                    <p class="mt-3 max-w-xl text-ink-soft">{{ __('auctions.interest.text') }}</p>
                    <form method="POST" action="{{ localized_route('auctions.interest', $auction) }}" novalidate
                        class="mt-6 space-y-5 rounded-xs border border-line bg-paper p-6 sm:p-8">
                        @csrf
                        @if ($auction->lots->isNotEmpty())
                            <x-form.select name="lot" :label="Str::ucfirst(__('auctions.fields.lot'))" :placeholder="__('auctions.interest.whole_auction')" :value="$selectedLot"
                                :options="$auction->lots->mapWithKeys(fn ($lot) => [$lot->id => __('auctions.lot_number', ['number' => $lot->lot_number]).' — '.$lot->translate('title')])->all()" />
                        @endif
                        <x-form.input name="name" :label="Str::ucfirst(__('auctions.fields.name'))" :value="$customer?->name" autocomplete="name" required />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="phone" type="tel" :label="Str::ucfirst(__('auctions.fields.phone'))" :value="$customer?->phone ? SaudiMobile::local($customer->phone) : null"
                                inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" autocomplete="tel" required />
                            <x-form.input name="email" type="email" :label="Str::ucfirst(__('auctions.fields.email'))" :value="$customer?->email" dir="ltr" autocomplete="email" :hint="__('checkout.email_hint')" />
                        </div>

                        <div class="sr-only" aria-hidden="true">
                            <label for="interest-website">{{ __('contact.labels.website') }}</label>
                            <input id="interest-website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <x-ui.button type="submit">{{ __('auctions.interest.submit') }}</x-ui.button>
                    </form>
                @endif
            </div>

            @if ($open)
                <aside class="h-fit space-y-4 rounded-xs border border-line bg-paper p-6 text-sm">
                    <h2 class="font-sans text-sm font-semibold">{{ __('auctions.how_title') }}</h2>
                    <ol class="space-y-3 text-ink-soft">
                        @foreach (__('auctions.how') as $step)
                            <li class="flex gap-3"><span class="numerals grid size-6 shrink-0 place-items-center rounded-full bg-ink text-xs text-ivory">{{ $loop->iteration }}</span><span>{{ $step }}</span></li>
                        @endforeach
                    </ol>
                </aside>
            @endif
        </section>
    </div>
</x-layouts.store>
