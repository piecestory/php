@php
    use App\Domain\Identity\Models\User;
    use App\Http\Requests\Storefront\FinderRequestForm;
    use App\Support\Phone\SaudiMobile;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $customer = $user instanceof User ? $user : null;
@endphp

<x-layouts.store :title="__('requests.finder.title')" :description="__('requests.finder.intro')">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('requests.finder.title')]]" />

        <header class="mt-6 max-w-2xl">
            <h1 class="text-display-lg">{{ __('requests.finder.title') }}</h1>
            <x-ui.ornament class="mt-4 w-40" />
            <p class="mt-4 text-ink-soft">{{ __('requests.finder.intro') }}</p>
        </header>

        @if (session('submitted'))
            <x-request-submitted :reference="session('submitted')" kind="finder" class="mt-10 max-w-2xl" />
        @else
            <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <form method="POST" action="{{ localized_route('personal-finder.store') }}" enctype="multipart/form-data" novalidate
                    class="space-y-6 rounded-xs border border-line bg-paper p-6 sm:p-8">
                    @csrf
                    <fieldset class="space-y-5">
                        <legend class="font-sans text-base font-semibold">{{ __('requests.finder.what') }}</legend>
                        <x-form.select name="category_id" :label="Str::ucfirst(__('requests.fields.category_id'))" :placeholder="__('requests.any_category')"
                            :options="$categories->mapWithKeys(fn ($c) => [$c->id => $c->translate('name')])->all()" />
                        <x-form.textarea name="description" :label="Str::ucfirst(__('requests.fields.description'))" :hint="__('requests.finder.description_hint')" rows="5" maxlength="3000" required />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="budget_min" :label="Str::ucfirst(__('requests.fields.budget_min'))" inputmode="decimal" dir="ltr" />
                            <x-form.input name="budget_max" :label="Str::ucfirst(__('requests.fields.budget_max'))" inputmode="decimal" dir="ltr" />
                        </div>
                        <x-form.textarea name="preferences" :label="Str::ucfirst(__('requests.fields.preferences'))" :hint="__('requests.finder.preferences_hint')" rows="2" maxlength="1000" />
                        <x-form.photos name="photos" :label="__('requests.finder.photos')" :max="FinderRequestForm::MAX_PHOTOS" />
                    </fieldset>

                    <fieldset class="space-y-5 border-t border-line pt-6">
                        <legend class="font-sans text-base font-semibold">{{ __('requests.contact') }}</legend>
                        <x-form.input name="name" :label="Str::ucfirst(__('requests.fields.name'))" :value="$customer?->name" autocomplete="name" required />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="phone" type="tel" :label="Str::ucfirst(__('requests.fields.phone'))" :value="$customer?->phone ? SaudiMobile::local($customer->phone) : null"
                                inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" autocomplete="tel" required />
                            <x-form.input name="email" type="email" :label="Str::ucfirst(__('requests.fields.email'))" :value="$customer?->email" dir="ltr" autocomplete="email" :hint="__('checkout.email_hint')" />
                        </div>
                    </fieldset>

                    <div class="sr-only" aria-hidden="true">
                        <label for="finder-website">{{ __('contact.labels.website') }}</label>
                        <input id="finder-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <x-ui.button type="submit">{{ __('requests.finder.submit') }}</x-ui.button>
                </form>

                <aside class="h-fit space-y-4 rounded-xs border border-line bg-paper p-6 text-sm">
                    <h2 class="font-sans text-sm font-semibold">{{ __('requests.how_it_works') }}</h2>
                    <ol class="space-y-3 text-ink-soft">
                        @foreach (__('requests.finder.steps') as $step)
                            <li class="flex gap-3"><span class="numerals grid size-6 shrink-0 place-items-center rounded-full bg-ink text-xs text-ivory">{{ $loop->iteration }}</span><span>{{ $step }}</span></li>
                        @endforeach
                    </ol>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.store>
