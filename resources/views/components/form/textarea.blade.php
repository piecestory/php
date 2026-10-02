@props([
    'name',
    'label',
    'value' => null,
    'hint' => null,
    'required' => false,
    'rows' => 4,
])

@php
    $id = \App\View\FormField::id($name);
    $error = $errors->first(\App\View\FormField::errorKey($name));
    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-form.field :$id :$label :$error :$hint :$required {{ $attributes->only('class') }}>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        @required($required)
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->class(['form-control h-auto py-3', 'form-control-invalid' => $error]) }}>{{ old(\App\View\FormField::errorKey($name), $value) }}</textarea>
</x-form.field>
