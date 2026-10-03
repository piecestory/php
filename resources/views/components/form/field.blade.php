{{-- Label + control slot + hint + validation error. Controls pass the same id so aria-describedby lines up.
     Labels often reuse validation attribute names ("mobile number"), so the first letter is capitalised here. --}}
@props([
    'id',
    'label',
    'error' => null,
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->class(['space-y-1.5']) }}>
    <label for="{{ $id }}" class="flex items-baseline gap-1.5 text-sm font-medium text-ink">
        {{ \Illuminate\Support\Str::ucfirst((string) $label) }}
        @if ($required)
            <span class="text-bronze" aria-hidden="true">*</span>
            <span class="sr-only">({{ __('ui.required') }})</span>
        @endif
    </label>

    {{ $slot }}

    @if ($error)
        <p id="{{ $id }}-error" class="flex items-center gap-1.5 text-xs text-danger">
            <x-ui.icon name="circle-alert" class="size-3.5" />{{ $error }}
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-ink-soft">{{ $hint }}</p>
    @endif
</div>
