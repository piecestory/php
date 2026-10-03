<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinderRequests\Pages;

use App\Domain\PersonalFinder\Actions\TransitionFinderRequest;
use App\Domain\PersonalFinder\Enums\FinderRequestStatus;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Filament\Resources\FinderRequests\FinderRequestResource;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\Staff;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use LogicException;

class ViewFinderRequest extends ViewRecord
{
    protected static string $resource = FinderRequestResource::class;

    public function getTitle(): string
    {
        return __('admin.requests.finder_title', ['reference' => $this->request()->reference]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->label(__('admin.requests.change_status'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn () => $this->request()->status->allowedTransitions() !== [])
                ->schema([
                    Select::make('status')->label(__('admin.fields.status'))->required()->live()
                        ->options(fn () => EnumLabels::options(FinderRequestStatus::class, $this->request()->status->allowedTransitions())),
                    Textarea::make('message')->label(__('admin.requests.offer_message'))->helperText(__('admin.requests.offer_message_hint'))
                        ->rows(4)->maxLength(1000)
                        ->visible(fn (Get $get) => $get('status') === FinderRequestStatus::OfferSent->value)
                        ->required(fn (Get $get) => $get('status') === FinderRequestStatus::OfferSent->value),
                ])
                ->action(function (array $data, TransitionFinderRequest $transition): void {
                    $transition->handle($this->request(), FinderRequestStatus::from((string) $data['status']), Staff::id(), $data['message'] ?? null);
                    $this->refreshRecord();
                })
                ->successNotificationTitle(__('admin.requests.updated')),
            Action::make('notes')
                ->label(__('admin.requests.admin_notes'))
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->fillForm(fn () => ['admin_notes' => $this->request()->admin_notes])
                ->schema([Textarea::make('admin_notes')->label(__('admin.requests.admin_notes'))->helperText(__('admin.requests.admin_notes_hint'))->rows(5)->maxLength(5000)])
                ->action(function (array $data): void {
                    $this->request()->forceFill(['admin_notes' => $data['admin_notes'] ?: null])->save();
                    $this->refreshRecord();
                })
                ->successNotificationTitle(__('admin.requests.updated')),
        ];
    }

    private function request(): FinderRequest
    {
        $record = $this->getRecord();

        return $record instanceof FinderRequest ? $record : throw new LogicException('Finder page without a request.');
    }

    private function refreshRecord(): void
    {
        $this->request()->refresh()->load(['statusChanges', 'category', 'assignee', 'media']);
    }
}
