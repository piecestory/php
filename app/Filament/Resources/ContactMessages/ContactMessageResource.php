<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages;

use App\Domain\Content\Enums\ContactMessageStatus;
use App\Domain\Content\Models\ContactMessage;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\RequiresPermission;
use App\Filament\Support\Staff;
use App\Support\Phone\SaudiMobile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Messages from the contact form. Staff read, reply (by phone or email), then mark them handled. */
class ContactMessageResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = ContactMessage::class;

    protected static ?string $slug = 'messages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 2;

    protected static function permission(): Permission
    {
        return Permission::ManageCustomers;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['create', 'update', 'replicate'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.customers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.messages.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::query()->where('status', ContactMessageStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label(__('contact.labels.name')),
            TextEntry::make('created_at')->label(__('admin.fields.date'))->dateTime('j M Y، g:i a'),
            TextEntry::make('phone')->label(__('contact.labels.phone'))->placeholder('—')
                ->formatStateUsing(fn (?string $state) => $state ? SaudiMobile::local($state) : '—')
                ->url(fn (ContactMessage $record) => $record->phone ? 'tel:'.$record->phone : null),
            TextEntry::make('email')->label(__('contact.labels.email'))->placeholder('—')
                ->url(fn (ContactMessage $record) => $record->email ? 'mailto:'.$record->email : null),
            TextEntry::make('subject')->label(__('contact.fields.subject'))->placeholder('—')->columnSpanFull(),
            TextEntry::make('message')->label(__('contact.fields.message'))->columnSpanFull()->extraAttributes(['class' => 'whitespace-pre-line']),
            TextEntry::make('handler.name')->label(__('admin.messages.handled_by'))->placeholder('—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('contact.labels.name'))->searchable()
                    ->description(fn (ContactMessage $record) => $record->phone ? SaudiMobile::local($record->phone) : $record->email),
                TextColumn::make('subject')->label(__('contact.fields.subject'))->placeholder('—')
                    ->description(fn (ContactMessage $record) => mb_strimwidth($record->message, 0, 80, '…'))->wrap(),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (ContactMessageStatus $state) => EnumLabels::of($state))
                    ->color(fn (ContactMessageStatus $state) => $state === ContactMessageStatus::New ? 'warning' : 'gray'),
                TextColumn::make('created_at')->label(__('admin.fields.date'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.fields.status'))->options(EnumLabels::options(ContactMessageStatus::class))
                    ->default(ContactMessageStatus::New->value),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markHandled')->label(__('admin.messages.mark_handled'))->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (ContactMessage $record) => $record->status === ContactMessageStatus::New)
                    ->action(fn (ContactMessage $record) => $record->forceFill(['status' => ContactMessageStatus::Handled, 'handled_by' => Staff::id()])->save())
                    ->successNotificationTitle(__('admin.messages.handled')),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactMessages::route('/')];
    }
}
