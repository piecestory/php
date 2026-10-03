<?php

declare(strict_types=1);

namespace App\Filament\Resources\HeroSlides;

use App\Domain\Content\Models\HeroSlide;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\HeroSlides\Pages\ManageHeroSlides;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** Home page hero. With no active slide the home page shows the built-in showroom slide. */
class HeroSlideResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'title_ar';

    protected static ?int $navigationSort = 1;

    protected static function permission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.content');
    }

    public static function getModelLabel(): string
    {
        return __('admin.hero.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.hero.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $image = fn (string $name, string $collection, string $label) => SpatieMediaLibraryFileUpload::make($name)->label($label)
            ->collection($collection)->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360);

        return $schema->columns(2)->components([
            TextInput::make('title_ar')->label(__('admin.hero.title_ar'))->required()->maxLength(190),
            TextInput::make('title_en')->label(__('admin.hero.title_en'))->required()->maxLength(190)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('subtitle_ar')->label(__('admin.hero.subtitle_ar'))->maxLength(500),
            TextInput::make('subtitle_en')->label(__('admin.hero.subtitle_en'))->maxLength(500)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('cta_label_ar')->label(__('admin.hero.cta_label_ar'))->maxLength(50),
            TextInput::make('cta_label_en')->label(__('admin.hero.cta_label_en'))->maxLength(50)->extraInputAttributes(['dir' => 'ltr']),
            // Same-site paths only ("/store/..."): a slide never sends customers to another site.
            TextInput::make('cta_url')->label(__('admin.hero.cta_url'))->helperText(__('admin.hero.cta_url_hint'))
                ->maxLength(500)->regex('#^/[^/\\\\]#')->requiredWith('cta_label_ar')->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
            $image('desktop', HeroSlide::MEDIA_DESKTOP, __('admin.hero.image_desktop'))->helperText(__('admin.hero.image_desktop_hint'))->required(),
            $image('mobile', HeroSlide::MEDIA_MOBILE, __('admin.hero.image_mobile'))->helperText(__('admin.hero.image_mobile_hint')),
            DateTimePicker::make('starts_at')->label(__('admin.hero.starts_at'))->seconds(false),
            DateTimePicker::make('ends_at')->label(__('admin.hero.ends_at'))->seconds(false)->after('starts_at'),
            TextInput::make('sort_order')->label(__('admin.fields.sort_order'))->integer()->minValue(0)->default(0),
            Toggle::make('is_active')->label(__('admin.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('desktop')->label('')->collection(HeroSlide::MEDIA_DESKTOP)->conversion('w960')->imageHeight(48),
                TextColumn::make('title_ar')->label(__('admin.hero.title'))->searchable(['title_ar', 'title_en']),
                TextColumn::make('ends_at')->label(__('admin.hero.ends_at'))->dateTime('j M Y')->placeholder('—'),
                ToggleColumn::make('is_active')->label(__('admin.fields.is_active')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageHeroSlides::route('/')];
    }
}
