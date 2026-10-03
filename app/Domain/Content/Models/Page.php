<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Content\ContentAvailability;
use App\Domain\Content\Enums\PageKey;
use App\Domain\Shared\Concerns\RecordsChanges;
use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Editable static pages addressed by a fixed key (about, services, policies). Their URLs are fixed per
 * key (PageKey::path), so slug_ar / slug_en simply mirror the key.
 */
#[Fillable([
    'key', 'title_ar', 'title_en', 'slug_ar', 'slug_en', 'body_ar', 'body_en',
    'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en', 'is_published',
])]
class Page extends Model
{
    use HasTranslations, RecordsChanges;

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => ContentAvailability::flush());
        static::deleted(fn () => ContentAvailability::flush());
    }

    public function pageKey(): ?PageKey
    {
        return PageKey::tryFrom($this->key);
    }
}
