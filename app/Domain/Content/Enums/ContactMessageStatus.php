<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Handled = 'handled';
}
