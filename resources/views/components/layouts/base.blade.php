@php($locale = \App\Support\Localization\Locales::resolve())
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ \App\Support\Localization\Locales::isRtl($locale) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f2ea">
    <title>{{ isset($title) ? $title.' — '.__('ui.brand') : __('ui.brand') }}</title>
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
