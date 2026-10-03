{{-- Multiple photo upload (JPEG/PNG/WebP). Errors for the list or for any single file show under the field. --}}
@props(['name', 'label', 'max', 'required' => false, 'hint' => null])

@php
    $id = \App\View\FormField::id($name);
    $error = $errors->first($name) ?: collect($errors->get($name.'.*'))->flatten()->first();
    $hintText = $hint ?? __('requests.photos_hint', ['max' => $max]);
@endphp

<x-form.field :$id :$label :$error :hint="$hintText" :$required {{ $attributes->only('class') }}>
    <label for="{{ $id }}" @class([
        'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xs border border-dashed bg-paper px-6 py-8 text-center text-sm transition-colors hover:border-bronze',
        'border-danger' => $error,
        'border-line-strong' => ! $error,
    ])>
        <x-ui.icon name="plus" class="size-6 text-bronze" />
        <span class="font-medium text-ink">{{ __('requests.photos_choose') }}</span>
        <span class="text-xs text-ink-soft">{{ __('requests.photos_types') }}</span>
        <input id="{{ $id }}" name="{{ $name }}[]" type="file" multiple accept="image/jpeg,image/png,image/webp"
            @required($required)
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @else aria-describedby="{{ $id }}-hint" @endif
            class="mt-2 w-full max-w-xs text-xs text-ink-soft file:me-3 file:rounded-xs file:border-0 file:bg-linen file:px-3 file:py-2 file:text-ink">
    </label>
</x-form.field>
