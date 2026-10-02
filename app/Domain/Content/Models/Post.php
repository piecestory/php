<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Shared\Concerns\HasPublication;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'author_id', 'title_ar', 'title_en', 'slug_ar', 'slug_en', 'excerpt_ar', 'excerpt_en', 'body_ar', 'body_en',
    'status', 'published_at', 'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en',
])]
class Post extends Model implements HasMedia
{
    use HasPublication, HasTranslations, InteractsWithMedia, SoftDeletes;

    public const string MEDIA_COVER = 'cover';

    protected function casts(): array
    {
        return [
            'status' => PublicationStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COVER)->singleFile();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
