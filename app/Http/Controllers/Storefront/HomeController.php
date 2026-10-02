<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Content\Models\HeroSlide;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    private const int LATEST_PRODUCTS = 10;

    public function __invoke(): View
    {
        $now = now();

        return view('storefront.home', [
            'slides' => HeroSlide::query()
                ->where('is_active', true)
                ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now))
                ->with('media')
                ->orderBy('sort_order')
                ->get(),
            'categories' => Category::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->with('media')
                ->orderBy('sort_order')
                ->get(),
            'latestProducts' => Product::query()
                ->published()
                ->with('media')
                ->latest('published_at')
                ->limit(self::LATEST_PRODUCTS)
                ->get(),
        ]);
    }
}
