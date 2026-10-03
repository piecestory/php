<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Domain\Shared\Contracts\ServiceRequest;
use App\Domain\Shared\Data\UploadedPhoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * Saves a customer request with its photos (private disk, random file names) and a readable reference
 * such as PF-2026-000012, then confirms it to the customer and alerts the team.
 */
final class StoreServiceRequest
{
    public function __construct(private readonly NotifyRequester $notify) {}

    /**
     * @template T of Model&ServiceRequest&HasMedia
     *
     * @param  T  $request  unsaved, already filled
     * @param  list<UploadedPhoto>  $photos
     * @return T
     */
    public function handle(Model&ServiceRequest&HasMedia $request, array $photos, string $collection): Model
    {
        DB::transaction(function () use ($request, $photos, $collection): void {
            $request->setAttribute('reference', 'TMP-'.Str::random(16));
            $request->save();
            $request->forceFill(['reference' => sprintf('%s-%s-%06d', $request::referencePrefix(), now()->format('Y'), $request->getKey())])->save();

            foreach ($photos as $photo) {
                // Never keep the customer's file name: it may contain personal details.
                $request->addMedia($photo->path)
                    ->usingFileName(Str::random(32).'.'.$photo->extension)
                    ->toMediaCollection($collection);
            }
        });

        $this->notify->handle($request, 'received');
        $this->notify->alertStore($request);

        return $request;
    }
}
