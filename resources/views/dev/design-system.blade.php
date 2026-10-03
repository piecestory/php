@php
    $ar = app()->getLocale() === 'ar';
    $t = fn (string $arText, string $enText) => $ar ? $arText : $enText;

    $products = [
        ['name' => $t('جرامافون أنتيك قديم', 'Antique Gramophone'), 'price' => '2150.00'],
        ['name' => $t('نجفة كريستال ذهبية', 'Gilded Crystal Chandelier'), 'price' => '4200.00', 'badges' => ['rare' => __('ui.badge.rare')]],
        ['name' => $t('كرسي كلاسيك فاخر بتطريز يدوي من القرن التاسع عشر', 'Hand-embroidered 19th-century Bergère Armchair'), 'price' => '3750.00', 'compare' => '4400.00', 'badges' => ['sale' => __('ui.badge.sale')]],
        ['name' => $t('ساعة كلاسيكية فرنسية', 'French Mantel Clock'), 'price' => '2950.00', 'wishlisted' => true],
        ['name' => $t('مزهرية صينية قديمة', 'Chinese Porcelain Vase'), 'price' => '1850.00', 'badges' => ['sold' => __('ui.badge.sold')], 'purchasable' => false],
    ];

    $categories = [
        $t('نجف وإضاءة', 'Chandeliers & Lighting'), $t('أثاث كلاسيك', 'Classic Furniture'), $t('لوحات فنية', 'Art'),
        $t('قطع نادرة', 'Rare Pieces'), $t('أواني منزلية فاخرة', 'Luxury Homeware'), $t('تحف وأنتيك', 'Antiques'),
    ];

    $colors = [
        'ivory' => '#f7f2ea', 'paper' => '#fcfaf6', 'linen' => '#eee6da', 'line' => '#e3d9cb',
        'ink' => '#1c1612', 'ink-soft' => '#5e544b', 'bronze' => '#8a5d2c', 'bronze-deep' => '#7e5428',
        'gold' => '#c9a46a', 'night' => '#16110e',
    ];
@endphp

<x-layouts.base title="Design System">
    <header class="border-b border-line bg-paper">
        <div class="container-page flex h-20 items-center justify-between">
            <a href="#" class="h-12 text-gold-deep" aria-label="{{ __('ui.brand') }}"><x-logo /></a>
            <nav class="flex gap-2 text-sm">
                <x-ui.button href="?lang=ar" :variant="$ar ? 'dark' : 'ghost'" size="sm">العربية</x-ui.button>
                <x-ui.button href="?lang=en" :variant="$ar ? 'ghost' : 'dark'" size="sm">English</x-ui.button>
            </nav>
        </div>
    </header>

    <main class="container-page space-y-20 py-14">
        {{-- Typography --}}
        <section class="space-y-5" aria-labelledby="type">
            <p id="type" class="text-xs tracking-[0.2em] text-ink-faint uppercase">Typography</p>
            <h1 class="text-display-xl">{{ $t('حيث تلتقي الأصالة بالفخامة', 'Where heritage meets luxury') }}</h1>
            <x-ui.ornament />
            <h2 class="text-display-lg">{{ $t('أحدث القطع', 'Latest Pieces') }}</h2>
            <h3 class="text-display-md">{{ $t('المزادات الإلكترونية', 'Online Auctions') }}</h3>
            <p class="max-w-2xl text-ink-soft">
                {{ $t('اكتشف مجموعة مختارة بعناية من التحف والقطع النادرة المصممة لتروي قصة كل عصر. كل قطعة تصل مع وصف دقيق لحالتها وأصلها.', 'Discover a carefully curated collection of antiques and rare pieces, each telling the story of its era. Every piece arrives with an accurate description of its condition and origin.') }}
            </p>
        </section>

        {{-- Colours --}}
        <section class="space-y-5" aria-label="Colours">
            <p class="text-xs tracking-[0.2em] text-ink-faint uppercase">Colour</p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($colors as $name => $hex)
                    <div class="overflow-hidden rounded-xs border border-line bg-paper">
                        <div class="h-16" style="background: {{ $hex }}"></div>
                        <p class="px-3 py-2 text-xs"><span class="font-medium">{{ $name }}</span> <span class="numerals text-ink-soft">{{ $hex }}</span></p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Buttons and small elements --}}
        <section class="space-y-6" aria-label="Buttons">
            <p class="text-xs tracking-[0.2em] text-ink-faint uppercase">Buttons · Badges · Price</p>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button icon="arrow-right">{{ $t('تصفح المتجر', 'Browse the store') }}</x-ui.button>
                <x-ui.button variant="dark">{{ $t('اطلب الآن', 'Request now') }}</x-ui.button>
                <x-ui.button variant="outline">{{ $t('عرض التفاصيل', 'View details') }}</x-ui.button>
                <x-ui.button variant="ghost">{{ $t('إلغاء', 'Cancel') }}</x-ui.button>
                <x-ui.button variant="link">{{ __('ui.view_all') }}</x-ui.button>
                <x-ui.button size="sm">{{ $t('صغير', 'Small') }}</x-ui.button>
                <x-ui.button size="lg">{{ $t('كبير', 'Large') }}</x-ui.button>
                <x-ui.button disabled>{{ $t('غير متاح', 'Disabled') }}</x-ui.button>
                <x-ui.button variant="outline" icon="heart" icon-only>{{ __('ui.add_to_wishlist') }}</x-ui.button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.badge variant="rare">{{ __('ui.badge.rare') }}</x-ui.badge>
                <x-ui.badge variant="sale">{{ __('ui.badge.sale') }}</x-ui.badge>
                <x-ui.badge variant="sold">{{ __('ui.badge.sold') }}</x-ui.badge>
                <x-ui.badge variant="reserved">{{ __('ui.badge.reserved') }}</x-ui.badge>
                <x-ui.badge>{{ __('ui.badge.on_request') }}</x-ui.badge>
                <x-ui.price amount="1850.00" />
                <x-ui.price amount="3750.00" compare-at="4400.00" size="lg" />
            </div>
            <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => '#'], ['label' => $t('المتجر', 'Store'), 'href' => '#'], ['label' => $t('ساعة كلاسيكية فرنسية', 'French Mantel Clock')]]" />
        </section>

        {{-- Category tiles --}}
        <section class="space-y-5" aria-label="Categories">
            <p class="text-xs tracking-[0.2em] text-ink-faint uppercase">Category tiles</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($categories as $category)
                    <x-category-tile :name="$category" href="#" />
                @endforeach
            </div>
        </section>

        {{-- Product cards --}}
        <section class="space-y-6" aria-label="Products">
            <x-ui.section-heading :title="$t('أحدث القطع', 'Latest Pieces')" href="#" />
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($products as $product)
                    <x-product-card :name="$product['name']" href="#" :price="$product['price']" :compare-at="$product['compare'] ?? null"
                        :badges="$product['badges'] ?? []" :wishlisted="$product['wishlisted'] ?? false"
                        :purchasable="$product['purchasable'] ?? true" />
                @endforeach
            </div>
        </section>

        {{-- Promo cards --}}
        <section class="grid gap-4 md:grid-cols-2" aria-label="Promotions">
            <x-promo-card tone="dark" :title="$t('المزادات الإلكترونية', 'Online Auctions')"
                :text="$t('شارك الآن واقتنِ القطع النادرة', 'Take part and acquire rare pieces')"
                :cta="$t('اكتشف المزادات', 'Explore auctions')" href="#" />
            <x-promo-card tone="light" :title="$t('الباحث الشخصي', 'Personal Finder')"
                :text="$t('نبحث لك عن القطعة التي تريدها', 'We search for the piece you have in mind')"
                :cta="$t('اطلب الآن', 'Request now')" href="#" />
        </section>

        {{-- Trust bar --}}
        <section aria-label="Trust" class="grid grid-cols-2 gap-6 rounded-xs border border-line bg-paper p-6 md:grid-cols-3 lg:grid-cols-5">
            <x-trust-item icon="truck" :title="$t('شحن سريع وآمن', 'Fast, secure shipping')" :text="$t('داخل المملكة', 'Within Saudi Arabia')" />
            <x-trust-item icon="gift" :title="$t('تغليف فاخر', 'Luxury packaging')" :text="$t('وحماية مضمونة', 'With guaranteed protection')" />
            <x-trust-item icon="shield-check" :title="$t('منتجات أصلية', 'Authentic pieces')" :text="$t('موثقة 100%', '100% documented')" />
            <x-trust-item icon="headset" :title="$t('دعم العملاء', 'Customer care')" :text="$t('رد خلال 30 دقيقة', 'Reply within 30 minutes')" />
            <x-trust-item icon="refresh-ccw" :title="$t('إرجاع واستبدال', 'Returns & exchanges')" :text="$t('للعيوب وعدم المطابقة', 'For defects or mismatch')" />
        </section>

        {{-- Forms --}}
        <section class="grid gap-10 lg:grid-cols-2" aria-label="Forms">
            <form class="space-y-5" novalidate>
                <p class="text-xs tracking-[0.2em] text-ink-faint uppercase">Forms</p>
                <x-form.input name="name" :label="$t('الاسم الكامل', 'Full name')" required />
                <x-form.input name="email" type="email" :label="$t('البريد الإلكتروني', 'Email')" value="not-an-email" />
                <x-form.input name="phone" type="tel" :label="$t('رقم الجوال', 'Mobile number')" :hint="$t('مثال: 05XXXXXXXX', 'Example: 05XXXXXXXX')" dir="ltr" required />
                <x-form.select name="category" :label="$t('التصنيف', 'Category')" :placeholder="$t('اختر التصنيف', 'Choose a category')"
                    :options="array_combine(range(1, 6), $categories)" />
                <x-form.textarea name="message" :label="$t('الوصف', 'Description')" />
                <x-form.checkbox name="terms" :label="$t('أوافق على الشروط والأحكام', 'I agree to the terms and conditions')" />
                <x-ui.button type="submit">{{ $t('إرسال', 'Submit') }}</x-ui.button>
            </form>
            <div class="space-y-4">
                <p class="text-xs tracking-[0.2em] text-ink-faint uppercase">Feedback</p>
                <x-ui.alert variant="success">{{ $t('تمت إضافة القطعة إلى السلة.', 'The piece was added to your cart.') }}</x-ui.alert>
                <x-ui.alert variant="danger">{{ $t('تعذر إتمام الدفع. حاول مرة أخرى.', 'Payment could not be completed. Please try again.') }}</x-ui.alert>
                <x-ui.alert>{{ $t('هذه القطعة فريدة ومتوفرة بنسخة واحدة.', 'This is a unique piece; only one is available.') }}</x-ui.alert>
                <div class="rounded-xs border border-line bg-paper">
                    <x-ui.empty-state icon="heart" :title="$t('قائمة المفضلة فارغة', 'Your wishlist is empty')"
                        :text="$t('احفظ القطع التي تعجبك لتعود إليها لاحقًا.', 'Save the pieces you love to come back to them later.')">
                        <x-ui.button variant="outline" href="#">{{ $t('تصفح المتجر', 'Browse the store') }}</x-ui.button>
                    </x-ui.empty-state>
                </div>
            </div>
        </section>

        {{-- Logo and icons --}}
        <section class="space-y-6" aria-label="Brand">
            <p class="text-xs tracking-[0.2em] text-ink-faint uppercase">Logo · Icons</p>
            <div class="flex flex-wrap items-center gap-10">
                <span class="h-14 text-gold-deep"><x-logo /></span>
                <span class="h-11 text-ink"><x-logo variant="compact" /></span>
                <span class="h-16 text-gold-deep"><x-logo variant="emblem" /></span>
                <span class="flex h-16 items-center rounded-xs bg-night px-6 text-gold"><x-logo class="h-10" /></span>
            </div>
            <div class="flex flex-wrap gap-4 text-ink-soft">
                @foreach (['search', 'heart', 'shopping-bag', 'user', 'menu', 'arrow-right', 'chevron-right', 'truck', 'gift', 'shield-check', 'headset', 'phone', 'mail', 'map-pin', 'clock', 'gavel', 'store', 'calendar-check'] as $icon)
                    <x-ui.icon :name="$icon" class="size-6" />
                @endforeach
            </div>
        </section>
    </main>
</x-layouts.base>
