{{-- A collapsible group of checkboxes. $options: [value => label]; $selected: list of selected values. --}}
@props(['title', 'name', 'options', 'selected' => []])

@if (count($options) > 0)
    <details class="group border-b border-line py-4" open>
        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold [&::-webkit-details-marker]:hidden">
            {{ $title }}
            <x-ui.icon name="chevron-down" class="size-4 text-ink-soft transition-transform group-open:rotate-180" />
        </summary>
        <ul class="mt-3 space-y-2.5">
            @foreach ($options as $value => $label)
                <li>
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-soft hover:text-ink">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}" x-on:change="changed"
                            @checked(in_array($value, $selected, false)) class="size-4 rounded-xs accent-bronze">
                        {{ $label }}
                    </label>
                </li>
            @endforeach
        </ul>
    </details>
@endif
