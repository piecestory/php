<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
