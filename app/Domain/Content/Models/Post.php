<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Content\ContentAvailability;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\Concerns\HasPublication;
use App\Domain\Shared\Concerns\HasWebpRenditions;
use App\Domain\Shared\Concerns\RecordsChanges;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Support\Localization\HasLocalizedSlug;
use App\Support\Localization\HasTranslations;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/** Journal article. Body in Markdown; addressed by the slug of the current language. */
#[Fillable([
    'author_id', 'title_ar', 'title_en', 'slug_ar', 'slug_en', 'excerpt_ar', 'excerpt_en', 'body_ar', 'body_en',
    'status', 'published_at', 'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en',
])]
#[UseFactory(PostFactory::class)]
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasLocalizedSlug, HasPublication, HasTranslations, HasWebpRenditions, RecordsChanges, SoftDeletes;

    public const string MEDIA_COVER = 'cover';

    /** WebP renditions (name => width) used for srcset; smallest first. */
    public const array IMAGE_SIZES = ['w800' => 800, 'w1600' => 1600];

    protected function casts(): array
    {
        return [
            'status' => PublicationStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => ContentAvailability::flush());
        static::deleted(fn () => ContentAvailability::flush());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COVER)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
