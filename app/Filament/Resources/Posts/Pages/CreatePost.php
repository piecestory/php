<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Filament\Support\Staff;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    /** The writer is whoever creates the article. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'author_id' => Staff::id()];
    }
}
