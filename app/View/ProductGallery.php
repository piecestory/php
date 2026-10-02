<?php

declare(strict_types=1);

namespace App\View;

use App\Domain\Catalog\Models\Product;
use App\Support\Media\ResponsiveImage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** Gallery images in the order set by the team (the first is the primary image). */
final class ProductGallery
{
    /** @return list<array{src: string, srcset: ?string, alt: string, large: string, thumb: string}> */
    public static function images(Product $product): array
    {
        return $product->getMedia(Product::MEDIA_GALLERY)
            ->map(function (Media $media): array {
                $image = (array) ResponsiveImage::from($media, Product::IMAGE_SIZES);
                $url = fn (string $conversion) => $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();

                return [
                    'src' => $url('card'),
                    'srcset' => $image['srcset'] ?? null,
                    'alt' => $image['alt'] ?? '',
                    'large' => $url('large'),
                    'thumb' => $url('thumb'),
                ];
            })
            ->values()
            ->all();
    }
}
