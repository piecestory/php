<?php

declare(strict_types=1);

namespace App\Domain\Shared\Listeners;

use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

/**
 * Stores an uploaded image's pixel size, so srcset widths are exact (renditions are never upscaled)
 * and layouts can reserve the right space.
 */
final class RecordImageDimensions
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        $size = @getimagesizefromstring((string) stream_get_contents($media->stream()));
        if ($size === false) {
            return;
        }

        $media->setCustomProperty('width', $size[0])->setCustomProperty('height', $size[1])->saveQuietly();
    }
}
