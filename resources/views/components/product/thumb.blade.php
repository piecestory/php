{{-- Square product thumbnail for lists (cart, checkout, orders). `sizes` is its rendered width, so the browser picks the smallest rendition that fits. --}}
@props(['product' => null, 'sizes' => '4rem', 'alt' => ''])

@php($image = $product?->responsiveImage(\App\Domain\Catalog\Models\Product::MEDIA_GALLERY))

<x-ui.image :src="$image['src'] ?? null" :srcset="$image['srcset'] ?? null" :$sizes :$alt ratio="1/1"
    {{ $attributes->class(['shrink-0 rounded-xs border border-line']) }} />
