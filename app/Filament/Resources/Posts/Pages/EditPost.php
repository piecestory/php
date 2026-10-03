<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Pages;

use App\Domain\Content\Models\Post;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('admin.actions.view_on_site'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (Post $record) => localized_route('blog.post', $record), shouldOpenInNewTab: true)
                ->visible(fn (Post $record) => $record->isPublished() && ! $record->trashed()),
            DeleteAction::make()->label(__('admin.actions.archive')),
            RestoreAction::make(),
        ];
    }
}
