<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\View\Seo\ProductSchema;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    private const int RELATED_LIMIT = 5;

    public function __invoke(Product $product): View
    {
        abort_unless($product->isPublished(), 404);

        $product->load(['category', 'era', 'origin', 'materials', 'media']);

        $related = Product::query()
            ->published()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->with('media')
            ->latest('published_at')
            ->limit(self::RELATED_LIMIT)
            ->get();

        return view('storefront.product.show', [
            'product' => $product,
            'related' => $related,
            'schema' => ProductSchema::for($product),
        ]);
    }
}
