<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\StoreSettings as Settings;
use App\Filament\Support\Staff;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Store-wide details shown to customers (contact email and phone in the footer and messages).
 *
 * @property-read Schema $form
 */
class StoreSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Staff::can(Permission::ManageSettings);
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    public function mount(Settings $settings): void
    {
        $this->form->fill([
            'email' => $settings->get('store.email'),
            'phone' => $settings->get('store.phone'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('admin.settings.contact'))->description(__('admin.settings.contact_hint'))->columns(2)->schema([
                TextInput::make('email')->label(__('admin.fields.email'))->email()->required()->maxLength(190)->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('phone')->label(__('admin.settings.phone'))->helperText(__('admin.settings.phone_hint'))
                    ->tel()->regex('/^[0-9+][0-9 ()-]{6,19}$/')->maxLength(20)->extraInputAttributes(['dir' => 'ltr']),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label(__('admin.settings.save'))->submit('save')]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        Setting::put('store', 'email', mb_strtolower(trim((string) $data['email'])));
        Setting::put('store', 'phone', filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null);

        Notification::make()->success()->title(__('admin.settings.saved'))->send();
    }
}
