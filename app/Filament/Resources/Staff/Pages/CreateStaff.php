<?php

declare(strict_types=1);

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\Concerns\SavesStaffMember;
use App\Filament\Resources\Staff\StaffResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStaff extends CreateRecord
{
    use SavesStaffMember;

    protected static string $resource = StaffResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->saveMember($data, null);
    }
}
