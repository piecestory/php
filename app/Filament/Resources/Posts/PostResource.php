<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts;

use App\Domain\Content\Models\Post;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\Fields;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/** Journal articles. Draft → published (now or scheduled); archived articles are soft-deleted. */
class PostResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $recordTitleAttribute = 'title_ar';

    /** Admin URLs use the id: storefront slugs can be edited here and must not move the admin page. */
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?int $navigationSort = 5;

    protected static function permission(): Permission
    {
        return Permission::ManageContent;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['forceDelete', 'forceDeleteAny'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.content');
    }

    public static function getModelLabel(): string
    {
        return __('admin.posts.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.posts.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $toolbar = [['heading', 'bold', 'italic', 'link'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']];

        return $schema->components([
            Tabs::make()->columnSpanFull()->tabs([
                Tab::make(__('admin.pages.arabic'))->schema([
                    ...array_filter(Fields::bilingualName(field: 'title'), fn ($f) => str_ends_with($f->getName(), '_ar')),
                    Textarea::make('excerpt_ar')->label(__('admin.posts.excerpt'))->helperText(__('admin.posts.excerpt_hint'))->maxLength(500)->rows(2),
                    MarkdownEditor::make('body_ar')->label(__('admin.pages.body'))->required()->extraAttributes(['dir' => 'rtl'])->toolbarButtons($toolbar),
                ]),
                Tab::make(__('admin.pages.english'))->schema([
                    ...array_filter(Fields::bilingualName(field: 'title'), fn ($f) => str_ends_with($f->getName(), '_en')),
                    Textarea::make('excerpt_en')->label(__('admin.posts.excerpt'))->maxLength(500)->rows(2)->extraInputAttributes(['dir' => 'ltr']),
                    MarkdownEditor::make('body_en')->label(__('admin.pages.body'))->helperText(__('admin.pages.english_hint'))
                        ->extraAttributes(['dir' => 'ltr'])->toolbarButtons($toolbar),
                ]),
                Tab::make(__('admin.posts.cover'))->schema([
                    SpatieMediaLibraryFileUpload::make('cover')->label(__('admin.posts.cover'))->collection(Post::MEDIA_COVER)
                        ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)
                        ->helperText(__('admin.posts.cover_hint')),
                ]),
                Tab::make(__('admin.products.tabs.publishing'))->columns(2)->schema([
                    Select::make('status')->label(__('admin.fields.status'))
                        ->options(EnumLabels::options(PublicationStatus::class))->default(PublicationStatus::Draft->value)->required()->live(),
                    DateTimePicker::make('published_at')->label(__('admin.fields.published_at'))->seconds(false)
                        ->helperText(__('admin.fields.published_at_hint'))
                        ->visible(fn (Get $get) => $get('status') === PublicationStatus::Published->value || $get('status') === PublicationStatus::Published),
                    ...Fields::seo(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->label('')->collection(Post::MEDIA_COVER)->conversion('w800')->imageHeight(40),
                TextColumn::make('title_ar')->label(__('admin.fields.title'))->searchable(['title_ar', 'title_en'])->wrap(),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (PublicationStatus $state) => EnumLabels::of($state))
                    ->color(fn (PublicationStatus $state) => $state === PublicationStatus::Published ? 'success' : 'gray'),
                TextColumn::make('published_at')->label(__('admin.fields.published_at'))->date('j M Y')->placeholder('—')->sortable(),
                TextColumn::make('author.name')->label(__('admin.posts.author'))->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.fields.status'))->options(EnumLabels::options(PublicationStatus::class)),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    /** @return Builder<Model> */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
