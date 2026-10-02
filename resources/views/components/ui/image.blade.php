@props([
    'src' => null,
    'srcset' => null,
    'sizes' => null,
    'alt' => '',
    'ratio' => '4/5',
    'eager' => false,
    'fit' => 'cover',
])

{{-- Fixed aspect ratio prevents layout shift; a missing or broken image falls back to the brand placeholder. --}}
<div {{ $attributes->class(['relative overflow-hidden bg-linen']) }} style="aspect-ratio: {{ $ratio }}">
    <img
        src="{{ $src ?: asset('images/placeholder.svg') }}"
        @if ($src && $srcset) srcset="{{ $srcset }}" sizes="{{ $sizes ?? '100vw' }}" @endif
        alt="{{ $src ? $alt : __('ui.image_unavailable') }}"
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        @if ($eager) fetchpriority="high" @endif
        decoding="async"
        data-fallback="{{ asset('images/placeholder.svg') }}"
        @class(['absolute inset-0 size-full', 'object-cover' => $fit === 'cover', 'object-contain p-4' => $fit === 'contain'])
    >
</div>
