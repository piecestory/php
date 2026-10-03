<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities;

use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

/** Read-only audit trail of changes made to the catalogue, content and settings (who, when, before → after). */
class ActivityResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'change-log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 10;

    protected static function permission(): Permission
    {
        return Permission::ManageUsers;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['create', 'update', 'delete', 'deleteAny', 'replicate'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.settings');
    }

    public static function getModelLabel(): string
    {
        return __('admin.activity.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.activity.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('created_at')->label(__('admin.fields.date'))->dateTime('j M Y، g:i a'),
            TextEntry::make('causer.name')->label(__('admin.activity.by'))->placeholder(__('admin.stock.system')),
            KeyValueEntry::make('old')->label(__('admin.activity.before'))
                ->state(fn (Activity $record) => self::values($record, 'old'))->keyLabel(__('admin.activity.field'))->valueLabel(__('admin.activity.value')),
            KeyValueEntry::make('new')->label(__('admin.activity.after'))
                ->state(fn (Activity $record) => self::values($record, 'attributes'))->keyLabel(__('admin.activity.field'))->valueLabel(__('admin.activity.value')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('admin.fields.date'))->dateTime('j M Y، g:i a'),
                TextColumn::make('causer.name')->label(__('admin.activity.by'))->placeholder(__('admin.stock.system')),
                TextColumn::make('event')->label(__('admin.activity.event'))->badge()
                    ->formatStateUsing(fn (?string $state) => __('admin.activity.events.'.($state ?? 'updated'))),
                TextColumn::make('subject_type')->label(__('admin.activity.subject'))
                    ->formatStateUsing(fn (Activity $record) => __('admin.activity.subjects.'.$record->subject_type).' #'.$record->subject_id),
                TextColumn::make('changed')->label(__('admin.activity.fields'))->wrap()
                    ->state(fn (Activity $record) => implode('، ', array_keys(self::values($record, 'attributes')))),
            ])
            ->filters([
                SelectFilter::make('subject_type')->label(__('admin.activity.subject'))->options((array) __('admin.activity.subjects')),
            ])
            ->recordActions([ViewAction::make()]);
    }

    /** @return array<string, string> */
    private static function values(Activity $record, string $side): array
    {
        $changes = $record->attribute_changes?->get($side) ?? [];

        return collect(is_array($changes) ? $changes : [])
            ->mapWithKeys(fn (mixed $value, string $key) => [$key => is_scalar($value) || $value === null ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE)])
            ->all();
    }

    public static function getPages(): array
    {
        return ['index' => ListActivities::route('/')];
    }
}
