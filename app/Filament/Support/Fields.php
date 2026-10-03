<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\Localization\Slug;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/** Field groups that repeat across admin forms (Arabic + English names with their URL slugs, SEO). */
final class Fields
{
    /**
     * name_ar / name_en and their slugs. A slug is suggested from the name while it is still empty;
     * once set, it is kept (changing a public URL breaks links and search results).
     *
     * @return list<TextInput>
     */
    public static function bilingualName(bool $withSlugs = true): array
    {
        $fields = [];

        foreach (['ar', 'en'] as $locale) {
            $fields[] = TextInput::make("name_{$locale}")
                ->label(__("admin.fields.name_{$locale}"))
                ->required()->maxLength(190)
                ->extraInputAttributes($locale === 'en' ? ['dir' => 'ltr'] : [])
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($locale, $withSlugs): void {
                    if ($withSlugs && blank($get("slug_{$locale}")) && filled($state)) {
                        $set("slug_{$locale}", Slug::make($state));
                    }
                });
        }

        if ($withSlugs) {
            foreach (['ar', 'en'] as $locale) {
                $fields[] = TextInput::make("slug_{$locale}")
                    ->label(__("admin.fields.slug_{$locale}"))
                    ->helperText(__('admin.fields.slug_hint'))
                    ->required()->maxLength(190)
                    ->unique(ignoreRecord: true)
                    ->rule('regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u')
                    ->dehydrateStateUsing(fn (?string $state) => Slug::make((string) $state))
                    ->extraInputAttributes(['dir' => $locale === 'en' ? 'ltr' : 'rtl']);
            }
        }

        return $fields;
    }

    /** @return list<TextInput|Textarea> */
    public static function seo(): array
    {
        return [
            TextInput::make('meta_title_ar')->label(__('admin.fields.meta_title_ar'))->maxLength(70)->helperText(__('admin.fields.meta_hint')),
            TextInput::make('meta_title_en')->label(__('admin.fields.meta_title_en'))->maxLength(70)->extraInputAttributes(['dir' => 'ltr']),
            Textarea::make('meta_description_ar')->label(__('admin.fields.meta_description_ar'))->maxLength(170)->rows(2),
            Textarea::make('meta_description_en')->label(__('admin.fields.meta_description_en'))->maxLength(170)->rows(2)->extraInputAttributes(['dir' => 'ltr']),
        ];
    }
}
