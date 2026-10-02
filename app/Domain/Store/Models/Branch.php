<?php

declare(strict_types=1);

namespace App\Domain\Store\Models;

use App\Domain\Store\Enums\BranchType;
use App\Support\Localization\HasTranslations;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name_ar', 'name_en', 'type', 'city', 'district', 'address_ar', 'address_en', 'phone', 'map_url',
    'is_pickup_point', 'is_active', 'sort_order',
])]
#[UseFactory(BranchFactory::class)]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'type' => BranchType::class,
            'is_pickup_point' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
