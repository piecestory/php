@props(['title' => null, 'description' => null])

@php
    use App\Support\Localization\Locales;
    use App\Support\Localization\LocalizedRoute;

    $locale = Locales::resolve();
    $pageTitle = $title ? $title.' — '.__('ui.brand') : __('ui.brand').' — '.__('site.tagline');
    $named = request()->route()?->getName() !== null;
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
    @if ($named)
        <link rel="canonical" href="{{ url()->current() }}">
        @foreach (Locales::SUPPORTED as $alternate)
            <link rel="alternate" hreflang="{{ $alternate }}" href="{{ LocalizedRoute::switchTo($alternate) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ LocalizedRoute::switchTo(Locales::PRIMARY) }}">
    @endif
    <meta property="og:site_name" content="{{ __('ui.brand') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_SA' : 'en_US' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    {{ $head ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireScriptConfig
</head>
<body {{ $attributes->class(['min-h-dvh']) }}>
    {{ $slot }}
</body>
</html>
