<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsignmentRequests\Pages;

use App\Domain\Consignment\Actions\ReviewConsignment;
use App\Domain\Consignment\Enums\ConsignmentStatus;
use App\Domain\Consignment\Models\ConsignmentRequest;
use App\Filament\Resources\ConsignmentRequests\ConsignmentRequestResource;
use App\Filament\Support\Staff;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use LogicException;

class ViewConsignmentRequest extends ViewRecord
{
    protected static string $resource = ConsignmentRequestResource::class;

    public function getTitle(): string
    {
        return __('admin.requests.consignment_title', ['reference' => $this->request()->reference]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startReview')
                ->label(__('admin.requests.start_review'))
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->visible(fn () => $this->request()->status === ConsignmentStatus::New)
                ->action(function (ReviewConsignment $review): void {
                    $review->startReview($this->request(), Staff::id());
                    $this->refreshRecord();
                })
                ->successNotificationTitle(__('admin.requests.updated')),
            $this->decisionAction('approve', true),
            $this->decisionAction('reject', false),
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

    private function decisionAction(string $name, bool $approve): Action
    {
        return Action::make($name)
            ->label(__("admin.requests.{$name}"))
            ->icon($approve ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
            ->color($approve ? 'success' : 'danger')
            ->visible(fn () => in_array($this->request()->status, [ConsignmentStatus::New, ConsignmentStatus::Reviewing], true))
            ->modalDescription(__("admin.requests.{$name}_hint"))
            ->schema([
                Textarea::make('note')->label(__('admin.requests.decision_note'))->helperText(__('admin.requests.decision_note_hint'))
                    ->required()->rows(4)->maxLength(1000),
            ])
            ->action(function (array $data, ReviewConsignment $review) use ($approve): void {
                $review->decide($this->request(), $approve, (string) $data['note'], Staff::id());
                $this->refreshRecord();
            })
            ->successNotificationTitle(__('admin.requests.updated'));
    }

    private function request(): ConsignmentRequest
    {
        $record = $this->getRecord();

        return $record instanceof ConsignmentRequest ? $record : throw new LogicException('Consignment page without a request.');
    }

    private function refreshRecord(): void
    {
        $this->request()->refresh()->load(['category', 'reviewer', 'media']);
    }
}
