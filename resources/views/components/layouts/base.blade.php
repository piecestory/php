@props(['title' => null, 'description' => null, 'noindex' => false, 'image' => null, 'ogType' => 'website'])

@php
    use App\Support\Localization\Locales;
    use App\Support\Localization\LocalizedRoute;

    $locale = Locales::resolve();
    $pageTitle = $title ? $title.' — '.__('ui.brand') : __('ui.brand').' — '.__('site.tagline');
    $named = request()->route()?->getName() !== null;
    // Filters and sorting point search engines to the plain listing; later pages keep their own address.
    $page = request()->integer('page');
    $canonical = url()->current().($page > 1 ? '?page='.$page : '');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ Locales::isRtl($locale) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#16110e">
    <title>{{ $pageTitle }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @if ($noindex)
        <meta name="robots" content="noindex, follow">
    @endif
    @if ($named)
        <link rel="canonical" href="{{ $canonical }}">
        @foreach (Locales::SUPPORTED as $alternate)
            <link rel="alternate" hreflang="{{ $alternate }}" href="{{ LocalizedRoute::switchTo($alternate, canonical: true) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ LocalizedRoute::switchTo(Locales::PRIMARY, canonical: true) }}">
    @endif
    <meta property="og:site_name" content="{{ __('ui.brand') }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    @if ($named)
        <meta property="og:url" content="{{ $canonical }}">
    @endif
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    {{-- Pages without a picture of their own share the brand card (1200×630). --}}
    <meta property="og:image" content="{{ $image ?? asset('images/og-default.jpg') }}">
    @unless ($image)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endunless
    <meta name="twitter:card" content="summary_large_image">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_SA' : 'en_US' }}">
    <meta property="og:locale:alternate" content="{{ $locale === 'ar' ? 'en_US' : 'ar_SA' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    {{ $head ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body {{ $attributes->class(['min-h-dvh']) }}>
    {{ $slot }}
</body>
</html>
