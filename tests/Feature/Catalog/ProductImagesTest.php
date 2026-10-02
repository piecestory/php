<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\View\ProductGallery;
use Illuminate\Http\UploadedFile;

it('records the pixel size of uploaded images', function (): void {
    $product = Product::factory()->create();

    $media = $product->addMedia(UploadedFile::fake()->image('clock.jpg', 1200, 1600))->toMediaCollection(Product::MEDIA_GALLERY);

    expect($media->fresh()->getCustomProperty('width'))->toBe(1200)
        ->and($media->fresh()->getCustomProperty('height'))->toBe(1600);
});

it('advertises the real width of each rendition and never an upscaled one', function (): void {
    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('small.jpg', 1000, 1300))->toMediaCollection(Product::MEDIA_GALLERY);

    $srcset = ProductGallery::images($product->fresh())[0]['srcset'];

    expect($srcset)->toContain('400w')->toContain('800w')->toContain('1000w')->not->toContain('1600w');
});

it('only accepts web image formats in the product gallery', function (): void {
    Product::factory()->create()
        ->addMedia(UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
        ->toMediaCollection(Product::MEDIA_GALLERY);
})->throws(Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection::class);
