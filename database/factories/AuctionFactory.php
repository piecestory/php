<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Models\Auction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Auction>
 */
class AuctionFactory extends Factory
{
    protected $model = Auction::class;

    public function definition(): array
    {
        $titleEn = ucwords(fake()->unique()->words(3, true));

        return [
            'title_ar' => 'مزاد '.$titleEn,
            'title_en' => $titleEn,
            'slug_ar' => 'مزاد-'.Str::slug($titleEn),
            'slug_en' => Str::slug($titleEn),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeeks(2),
            'status' => AuctionStatus::Scheduled,
        ];
    }
}
