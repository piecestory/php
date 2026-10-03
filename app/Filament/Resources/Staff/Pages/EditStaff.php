<?php

declare(strict_types=1);

namespace App\Filament\Resources\Staff\Pages;

use App\Domain\Identity\Models\User;
use App\Filament\Resources\Staff\Concerns\SavesStaffMember;
use App\Filament\Resources\Staff\StaffResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStaff extends EditRecord
{
    use SavesStaffMember;

    protected static string $resource = StaffResource::class;

    /** The role lives in the permissions tables, not on the user row. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        return [...$data, 'role' => $record instanceof User ? $record->roles()->value('name') : null, 'password' => null];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->saveMember($data, $record instanceof User ? $record : null);
    }
}
