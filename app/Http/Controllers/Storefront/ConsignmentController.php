<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Models\Category;
use App\Domain\Consignment\Models\ConsignmentRequest;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\Actions\StoreServiceRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ConsignmentRequestForm;
use App\Http\Requests\Support\PhotoUploads;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** "Sell with us": owners offer a piece; the team reviews it and approves or declines. */
class ConsignmentController extends Controller
{
    public function show(): View
    {
        return view('storefront.sell-with-us', [
            'categories' => Category::query()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(ConsignmentRequestForm $form, StoreServiceRequest $store): RedirectResponse
    {
        $user = $form->user();
        $request = new ConsignmentRequest([...$form->details(), 'user_id' => $user instanceof User ? $user->id : null]);

        $store->handle($request, PhotoUploads::toDomain($form->file('photos')), ConsignmentRequest::MEDIA_PHOTOS);

        return redirect()->to(localized_route('sell-with-us'))->with('submitted', $request->reference);
    }
}
