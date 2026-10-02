<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns;

use App\Support\Media\ResponsiveImage;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Media library support plus WebP renditions (declared in the model's IMAGE_SIZES: name => width,
 * smallest first) for uploaded images, with src/srcset for views. Use instead of InteractsWithMedia.
 */
trait HasWebpRenditions
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (static::IMAGE_SIZES as $name => $width) {
            $this->addMediaConversion($name)
                ->fit(Fit::Max, $width, $width)
                ->format('webp')
                ->quality(82);
        }
    }

    /** @return array{src: string, srcset: ?string, alt: string}|null */
    public function responsiveImage(string $collection): ?array
    {
        return ResponsiveImage::from($this->getFirstMedia($collection), static::IMAGE_SIZES);
    }
}
