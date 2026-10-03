<?php

declare(strict_types=1);

namespace App\View\Seo;

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Models\Product;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** schema.org Product + BreadcrumbList for rich results (price, availability, condition). */
final class ProductSchema
{
    /** @return list<array<string, mixed>> */
    public static function for(Product $product): array
    {
        $url = localized_route('product', $product);
        $images = $product->getMedia(Product::MEDIA_GALLERY)
            ->map(fn (Media $media) => $media->hasGeneratedConversion('large') ? $media->getUrl('large') : $media->getUrl())
            ->values()
            ->all();

        $offer = array_filter([
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => 'SAR',
            'price' => $product->availability === ProductAvailability::OnRequest ? null : $product->effectivePrice(),
            'availability' => match ($product->availability) {
                ProductAvailability::Available => $product->stock_quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                ProductAvailability::Reserved => 'https://schema.org/LimitedAvailability',
                ProductAvailability::Sold => 'https://schema.org/SoldOut',
                ProductAvailability::OnRequest => 'https://schema.org/InStoreOnly',
            },
            'itemCondition' => $product->condition === ProductCondition::Restored
                ? 'https://schema.org/RefurbishedCondition'
                : 'https://schema.org/UsedCondition',
            'seller' => JsonLd::organization(),
        ], fn (mixed $value) => $value !== null);

        $crumbs = [
            ['name' => __('ui.home'), 'item' => localized_route('home')],
            ['name' => __('catalog.store.title'), 'item' => localized_route('store')],
        ];
        if ($product->category !== null) {
            $crumbs[] = ['name' => (string) $product->category->translate('name'), 'item' => localized_route('category', $product->category)];
        }
        $crumbs[] = ['name' => (string) $product->translate('name'), 'item' => $url];

        return [
            array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->translate('name'),
                'description' => self::plain($product->translate('description')),
                'sku' => $product->sku,
                'image' => $images ?: null,
                'category' => $product->category?->translate('name'),
                'offers' => $offer,
            ], fn (mixed $value) => $value !== null),
            JsonLd::breadcrumbList($crumbs),
        ];
    }

    private static function plain(?string $text): ?string
    {
        return $text === null ? null : trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));
    }
}
