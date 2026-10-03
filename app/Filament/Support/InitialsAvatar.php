<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Staff avatar drawn locally (initials on the brand colour) instead of Filament's default, which sends
 * each staff member's name to an outside service (ui-avatars.com).
 */
final class InitialsAvatar implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initials = collect(preg_split('/\s+/u', trim(Filament::getNameForDefaultAvatar($record))) ?: [])
            ->map(fn (string $word): string => mb_substr((string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $word), 0, 1))
            ->filter()
            ->take(2)
            ->join('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#16110e"/>'
            .'<text x="32" y="33" fill="#c9a46a" font-family="sans-serif" font-size="26" text-anchor="middle" dominant-baseline="middle">'
            .htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
