<?php

declare(strict_types=1);

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Builds src/srcset from the WebP conversions that already exist for an image.
 * Conversions are generated in the background; until they are ready the original is served.
 */
final class ResponsiveImage
{
    /**
     * @param  array<string, int>  $conversions  conversion name => width in px, smallest first
     * @return array{src: string, srcset: ?string, alt: string}|null
     */
    public static function from(?Media $media, array $conversions): ?array
    {
        if ($media === null) {
            return null;
        }

        $candidates = [];
        foreach ($conversions as $name => $width) {
            if ($media->hasGeneratedConversion($name)) {
                $candidates[] = $media->getUrl($name).' '.$width.'w';
            }
        }

        $largest = array_key_last($conversions);

        return [
            'src' => $largest !== null && $media->hasGeneratedConversion($largest) ? $media->getUrl($largest) : $media->getUrl(),
            'srcset' => $candidates === [] ? null : implode(', ', $candidates),
            'alt' => (string) $media->getCustomProperty('alt_'.app()->getLocale(), $media->getCustomProperty('alt_ar', '')),
        ];
    }
}
