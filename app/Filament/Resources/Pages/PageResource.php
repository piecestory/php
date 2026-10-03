<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use App\Domain\Content\Models\Page;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\Pages\Pages\ManagePages;
use App\Filament\Support\Fields;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** The fixed pages (about, services, policies): text and publication only; their addresses never change. */
class PageResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Page::class;

    protected static ?string $slug = 'site-pages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'title_ar';

    protected static ?int $navigationSort = 4;

    protected static function permission(): Permission
    {
        return Permission::ManageContent;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['create', 'delete', 'deleteAny', 'replicate'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.content');
    }

    public static function getModelLabel(): string
    {
        return __('admin.pages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.pages.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->tabs([
                Tab::make(__('admin.pages.arabic'))->schema([
                    TextInput::make('title_ar')->label(__('admin.fields.title_ar'))->required()->maxLength(190),
                    MarkdownEditor::make('body_ar')->label(__('admin.pages.body'))->helperText(__('admin.pages.markdown_hint'))
                        ->required()->extraAttributes(['dir' => 'rtl'])
                        ->toolbarButtons([['heading', 'bold', 'italic', 'link'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
                ]),
                Tab::make(__('admin.pages.english'))->schema([
                    TextInput::make('title_en')->label(__('admin.fields.title_en'))->required()->maxLength(190)->extraInputAttributes(['dir' => 'ltr']),
                    MarkdownEditor::make('body_en')->label(__('admin.pages.body'))->helperText(__('admin.pages.english_hint'))
                        ->extraAttributes(['dir' => 'ltr'])
                        ->toolbarButtons([['heading', 'bold', 'italic', 'link'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
                ]),
                Tab::make(__('admin.products.tabs.publishing'))->columns(2)->schema([
                    Toggle::make('is_published')->label(__('admin.pages.is_published'))->helperText(__('admin.pages.is_published_hint'))->columnSpanFull(),
                    ...Fields::seo(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('title_ar')->label(__('admin.fields.title')),
                TextColumn::make('key')->label(__('admin.pages.address'))
                    ->formatStateUsing(fn (Page $record) => $record->pageKey()?->path() ?? '—')->extraAttributes(['dir' => 'ltr']),
                ToggleColumn::make('is_published')->label(__('admin.pages.is_published')),
                TextColumn::make('updated_at')->label(__('admin.fields.updated_at'))->since(),
            ])
            ->recordActions([
                EditAction::make()->modalWidth(Width::FiveExtraLarge),
                Action::make('view')->label(__('admin.actions.view_on_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Page $record) => localized_route($record->key), shouldOpenInNewTab: true)
                    ->visible(fn (Page $record) => $record->is_published && $record->pageKey() !== null),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePages::route('/')];
    }
}
