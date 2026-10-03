<?php

declare(strict_types=1);

namespace App\Domain\Shared\Data;

/** A validated image upload handed to the domain as a file path (no HTTP types in the domain). */
final readonly class UploadedPhoto
{
    public function __construct(
        public string $path,
        public string $extension,
    ) {}
}
