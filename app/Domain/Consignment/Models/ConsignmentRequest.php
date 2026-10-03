<?php

declare(strict_types=1);

namespace App\Domain\Consignment\Models;

use App\Domain\Catalog\Models\Category;
use App\Domain\Consignment\Enums\ConsignmentStatus;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\Concerns\HasStatusHistory;
use App\Domain\Shared\Concerns\IsServiceRequest;
use App\Domain\Shared\Contracts\ServiceRequest;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** A piece a customer offers to sell through the store. Photos are private (staff only). */
#[Fillable([
    'user_id', 'name', 'phone', 'email', 'city', 'category_id', 'title', 'description', 'asking_price',
    'admin_notes', 'locale',
])]
class ConsignmentRequest extends Model implements HasMedia, ServiceRequest
{
    use HasStatusHistory, InteractsWithMedia, IsServiceRequest;

    public const string MEDIA_PHOTOS = 'photos';

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return [
            'status' => ConsignmentStatus::class,
            'asking_price' => 'decimal:2',
            'decided_at' => 'datetime',
        ];
    }

    public function requestKind(): string
    {
        return 'consignment';
    }

    public static function referencePrefix(): string
    {
        return 'CS';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_PHOTOS)->useDisk('local');
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
