<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs;

use App\Domain\Content\Models\Faq;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $recordTitleAttribute = 'question_ar';

    protected static ?int $navigationSort = 3;

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
        return __('admin.faqs.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.faqs.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('question_ar')->label(__('admin.faqs.question_ar'))->required()->maxLength(500),
            TextInput::make('question_en')->label(__('admin.faqs.question_en'))->required()->maxLength(500)->extraInputAttributes(['dir' => 'ltr']),
            Textarea::make('answer_ar')->label(__('admin.faqs.answer_ar'))->required()->rows(5),
            Textarea::make('answer_en')->label(__('admin.faqs.answer_en'))->required()->rows(5)->extraInputAttributes(['dir' => 'ltr']),
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
                TextColumn::make('question_ar')->label(__('admin.faqs.question'))->searchable(['question_ar', 'question_en'])->wrap(),
                ToggleColumn::make('is_active')->label(__('admin.fields.is_active')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFaqs::route('/')];
    }
}
