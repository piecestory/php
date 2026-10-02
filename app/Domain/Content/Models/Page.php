<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Editable static pages addressed by a fixed key (about, services, policies). */
#[Fillable([
    'key', 'title_ar', 'title_en', 'slug_ar', 'slug_en', 'body_ar', 'body_en',
    'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en', 'is_published',
])]
class Page extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
