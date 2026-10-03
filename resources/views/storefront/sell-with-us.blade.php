@php
    use App\Domain\Identity\Models\User;
    use App\Http\Requests\Storefront\ConsignmentRequestForm;
    use App\Support\Phone\SaudiMobile;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $customer = $user instanceof User ? $user : null;
@endphp

<x-layouts.store :title="__('requests.consignment.title')" :description="__('requests.consignment.intro')">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('requests.consignment.title')]]" />

        <header class="mt-6 max-w-2xl">
            <h1 class="text-display-lg">{{ __('requests.consignment.title') }}</h1>
            <x-ui.ornament class="mt-4 w-40" />
            <p class="mt-4 text-ink-soft">{{ __('requests.consignment.intro') }}</p>
        </header>

        @if (session('submitted'))
            <x-request-submitted :reference="session('submitted')" kind="consignment" class="mt-10 max-w-2xl" />
        @else
            <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <form method="POST" action="{{ localized_route('sell-with-us.store') }}" enctype="multipart/form-data" novalidate
                    class="space-y-6 rounded-xs border border-line bg-paper p-6 sm:p-8">
                    @csrf
                    <fieldset class="space-y-5">
                        <legend class="font-sans text-base font-semibold">{{ __('requests.consignment.piece') }}</legend>
                        <x-form.input name="title" :label="Str::ucfirst(__('requests.fields.title'))" :hint="__('requests.consignment.title_hint')" maxlength="190" required />
                        <x-form.select name="category_id" :label="Str::ucfirst(__('requests.fields.category_id'))" :placeholder="__('requests.any_category')"
                            :options="$categories->mapWithKeys(fn ($c) => [$c->id => $c->translate('name')])->all()" />
                        <x-form.textarea name="description" :label="Str::ucfirst(__('requests.fields.description'))" :hint="__('requests.consignment.description_hint')" rows="5" maxlength="3000" required />
                        <x-form.input name="asking_price" :label="Str::ucfirst(__('requests.fields.asking_price'))" :hint="__('requests.consignment.asking_price_hint')" inputmode="decimal" dir="ltr" />
                        <x-form.photos name="photos" :label="__('requests.consignment.photos')" :max="ConsignmentRequestForm::MAX_PHOTOS" required
                            :hint="__('requests.consignment.photos_hint', ['max' => ConsignmentRequestForm::MAX_PHOTOS])" />
                    </fieldset>

                    <fieldset class="space-y-5 border-t border-line pt-6">
                        <legend class="font-sans text-base font-semibold">{{ __('requests.contact') }}</legend>
                        <x-form.input name="name" :label="Str::ucfirst(__('requests.fields.name'))" :value="$customer?->name" autocomplete="name" required />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="phone" type="tel" :label="Str::ucfirst(__('requests.fields.phone'))" :value="$customer?->phone ? SaudiMobile::local($customer->phone) : null"
                                inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" autocomplete="tel" required />
                            <x-form.input name="email" type="email" :label="Str::ucfirst(__('requests.fields.email'))" :value="$customer?->email" dir="ltr" autocomplete="email" :hint="__('checkout.email_hint')" />
                        </div>
                        <x-form.input name="city" :label="Str::ucfirst(__('requests.fields.city'))" autocomplete="address-level2" required />
                    </fieldset>

                    <x-form.checkbox name="ownership" :label="__('requests.consignment.ownership')" required />

                    <div class="sr-only" aria-hidden="true">
                        <label for="consignment-website">{{ __('contact.labels.website') }}</label>
                        <input id="consignment-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <x-ui.button type="submit">{{ __('requests.consignment.submit') }}</x-ui.button>
                </form>

                <aside class="h-fit space-y-4 rounded-xs border border-line bg-paper p-6 text-sm">
                    <h2 class="font-sans text-sm font-semibold">{{ __('requests.how_it_works') }}</h2>
                    <ol class="space-y-3 text-ink-soft">
                        @foreach (__('requests.consignment.steps') as $step)
                            <li class="flex gap-3"><span class="numerals grid size-6 shrink-0 place-items-center rounded-full bg-ink text-xs text-ivory">{{ $loop->iteration }}</span><span>{{ $step }}</span></li>
                        @endforeach
                    </ol>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.store>
