<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Domain\Shared\Data\UploadedPhoto;
use Illuminate\Http\UploadedFile;

/**
 * Customer photo uploads: JPEG/PNG/WebP checked by content (not by name), 10 MB each, a fixed number
 * of files. iPhones convert HEIC to JPEG when uploading through the browser.
 */
final class PhotoUploads
{
    public const int MAX_KILOBYTES = 10240;

    /** @return array<string, list<string>> */
    public static function rules(string $field, int $min, int $max): array
    {
        return [
            $field => [$min > 0 ? 'required' : 'nullable', 'array', "min:{$min}", "max:{$max}"],
            "{$field}.*" => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.self::MAX_KILOBYTES],
        ];
    }

    /**
     * @param  array<mixed>|null  $files
     * @return list<UploadedPhoto>
     */
    public static function toDomain(?array $files): array
    {
        $photos = [];
        foreach ($files ?? [] as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                // Extension from the detected content type, never from the uploaded file name.
                $photos[] = new UploadedPhoto((string) $file->getRealPath(), $file->guessExtension() ?? 'jpg');
            }
        }

        return $photos;
    }
}
