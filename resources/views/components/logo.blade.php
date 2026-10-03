@php $logo = $file(); @endphp
{{-- Decorative: the accessible name comes from the surrounding link. --}}
<span aria-hidden="true" {{ $attributes->class(['logo-mask inline-block h-full']) }}
    style="--logo: url('{{ $logo['url'] }}'); aspect-ratio: {{ $logo['ratio'] }}"></span>
