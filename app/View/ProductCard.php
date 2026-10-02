<?php

declare(strict_types=1);

namespace App\View;

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Models\Product;
use App\Support\Localization\LocalizedRoute;

/** Maps a product to the props of <x-product-card>, so every listing presents products identically. */
final class ProductCard
{
    /**
     * @return array{name: string, href: ?string, price: string, compareAt: ?string, image: ?string, srcset: ?string, badges: array<string, string>, purchasable: bool}
     */
    public static function from(Product $product): array
    {
        $onSale = $product->isOnSale();
        $image = $product->responsiveImage(Product::MEDIA_GALLERY);

        return [
            'name' => (string) $product->translate('name'),
            'href' => LocalizedRoute::has('product') ? localized_route('product', $product) : null,
            'price' => $product->effectivePrice(),
            'compareAt' => $onSale ? (string) $product->price : null,
            'image' => $image['src'] ?? null,
            'srcset' => $image['srcset'] ?? null,
            'badges' => self::badges($product, $onSale),
            'purchasable' => $product->availability === ProductAvailability::Available && $product->stock_quantity > 0,
        ];
    }

    /** @return array<string, string> */
    private static function badges(Product $product, bool $onSale): array
    {
        $badges = match ($product->availability) {
            ProductAvailability::Sold => ['sold' => __('ui.badge.sold')],
            ProductAvailability::Reserved => ['reserved' => __('ui.badge.reserved')],
            default => [],
        };

        if ($product->is_rare) {
            $badges['rare'] = __('ui.badge.rare');
        }
        if ($onSale && $badges === []) {
            $badges['sale'] = __('ui.badge.sale');
        }

        return $badges;
    }
}
