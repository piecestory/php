<?php

declare(strict_types=1);

namespace App\Domain\PersonalFinder\Models;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\PersonalFinder\Enums\FinderRequestStatus;
use App\Domain\Shared\Concerns\HasStatusHistory;
use App\Domain\Shared\Concerns\IsServiceRequest;
use App\Domain\Shared\Contracts\ServiceRequest;
use Database\Factories\FinderRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'reference', 'user_id', 'name', 'phone', 'email', 'category_id', 'description',
    'budget_min', 'budget_max', 'preferences', 'assigned_to', 'admin_notes', 'locale',
])]
#[UseFactory(FinderRequestFactory::class)]
class FinderRequest extends Model implements HasMedia, ServiceRequest
{
    /** @use HasFactory<FinderRequestFactory> */
    use HasFactory, HasStatusHistory, InteractsWithMedia, IsServiceRequest;

    /** Customer reference photos; stored on a private disk. */
    public const string MEDIA_REFERENCES = 'references';

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return [
            'status' => FinderRequestStatus::class,
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
        ];
    }

    public function requestKind(): string
    {
        return 'finder';
    }

    public static function referencePrefix(): string
    {
        return 'PF';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_REFERENCES)->useDisk('local');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
