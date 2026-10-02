<?php

declare(strict_types=1);

arch('php preset: no debug helpers or deprecated functions')->preset()->php();

arch('security preset: no unsafe functions')->preset()->security();

arch('application code declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('domain logic is independent of the delivery layer')
    ->expect('App\Domain')
    ->not->toUse(['App\Http', 'App\Livewire', 'App\Filament', 'Illuminate\Http\Request']);
