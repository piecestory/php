<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Shared\Concerns\HasWebpRenditions;
use App\Domain\Shared\Concerns\RecordsChanges;
use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

#[Fillable([
    'title_ar', 'title_en', 'subtitle_ar', 'subtitle_en', 'cta_label_ar', 'cta_label_en', 'cta_url',
    'sort_order', 'is_active', 'starts_at', 'ends_at',
])]
class HeroSlide extends Model implements HasMedia
{
    use HasTranslations, HasWebpRenditions, RecordsChanges;

    public const string MEDIA_DESKTOP = 'desktop';

    /** Optional portrait crop; falls back to the desktop image on phones. */
    public const string MEDIA_MOBILE = 'mobile';

    /** WebP renditions (name => width), smallest first. */
    public const array IMAGE_SIZES = ['w960' => 960, 'w1920' => 1920];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_DESKTOP)->singleFile();
        $this->addMediaCollection(self::MEDIA_MOBILE)->singleFile();
    }
}
