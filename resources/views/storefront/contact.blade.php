@php
    use App\Domain\Identity\Models\User;
    use App\Support\Phone\SaudiMobile;

    $settings = app(\App\Domain\Settings\StoreSettings::class);
    $email = $settings->get('store.email');
    $phone = $settings->get('store.phone');
    $user = auth()->user();
    $customer = $user instanceof User ? $user : null;
@endphp

<x-layouts.store :title="__('contact.title')" :description="__('contact.intro')">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('contact.title')]]" />

        <header class="mt-6 max-w-2xl">
            <h1 class="text-display-lg">{{ __('contact.title') }}</h1>
            <x-ui.ornament class="mt-4 w-40" />
            <p class="mt-4 text-ink-soft">{{ __('contact.intro') }}</p>
        </header>

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <form method="POST" action="{{ localized_route('contact.store') }}" novalidate class="space-y-5 rounded-xs border border-line bg-paper p-6 sm:p-8">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="name" :label="__('contact.labels.name')" :value="$customer?->name" autocomplete="name" required class="sm:col-span-2" />
                    <x-form.input name="phone" type="tel" :label="__('contact.labels.phone')" :value="$customer?->phone ? SaudiMobile::local($customer->phone) : null"
                        inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" autocomplete="tel" />
                    <x-form.input name="email" type="email" :label="__('contact.labels.email')" :value="$customer?->email" dir="ltr" autocomplete="email" />
                </div>
                <p class="-mt-2 text-xs text-ink-soft">{{ __('contact.reply_hint') }}</p>
                <x-form.input name="subject" :label="__('contact.labels.subject')" maxlength="150" />
                <x-form.textarea name="message" :label="__('contact.labels.message')" rows="6" maxlength="3000" required />

                {{-- Left empty by people (hidden from view and from assistive tech); bots fill it in. --}}
                <div class="sr-only" aria-hidden="true">
                    <label for="contact-website">{{ __('contact.labels.website') }}</label>
                    <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <x-ui.button type="submit">{{ __('contact.send') }}</x-ui.button>
            </form>

            <aside class="space-y-6 text-sm">
                <section class="rounded-xs border border-line bg-paper p-6">
                    <h2 class="font-sans text-sm font-semibold">{{ __('contact.reach_us') }}</h2>
                    <ul class="mt-4 space-y-3">
                        @if ($phone)
                            <li class="flex items-center gap-3"><x-ui.icon name="phone" class="size-4 text-bronze" />
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="numerals hover:text-bronze" dir="ltr">{{ $phone }}</a></li>
                        @endif
                        @if ($email)
                            <li class="flex items-center gap-3"><x-ui.icon name="mail" class="size-4 text-bronze" />
                                <a href="mailto:{{ $email }}" class="hover:text-bronze" dir="ltr">{{ $email }}</a></li>
                        @endif
                        <li class="flex items-start gap-3"><x-ui.icon name="clock" class="mt-0.5 size-4 text-bronze" /><span>{{ __('site.footer.hours') }}</span></li>
                        <li class="flex items-start gap-3"><x-ui.icon name="headset" class="mt-0.5 size-4 text-bronze" /><span>{{ __('contact.response_time') }}</span></li>
                    </ul>
                </section>

                @if ($showrooms->isNotEmpty())
                    <section class="rounded-xs border border-line bg-paper p-6">
                        <h2 class="font-sans text-sm font-semibold">{{ __('contact.showrooms') }}</h2>
                        <ul class="mt-4 space-y-4">
                            @foreach ($showrooms as $branch)
                                <li class="flex items-start gap-3">
                                    <x-ui.icon name="map-pin" class="mt-0.5 size-4 text-bronze" />
                                    <span>
                                        <span class="block font-medium text-ink">{{ $branch->translate('name') }}</span>
                                        <span class="block text-ink-soft">{{ $branch->translate('address') ?? "{$branch->district}، {$branch->city}" }}</span>
                                        @if ($branch->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $branch->phone) }}" class="numerals text-ink-soft hover:text-bronze" dir="ltr">{{ $branch->phone }}</a>@endif
                                        @if ($branch->map_url)
                                            <a href="{{ $branch->map_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 block text-bronze hover:underline">{{ __('site.footer.directions') }}</a>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.store>
