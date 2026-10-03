<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Domain\Shared\Actions\StoreServiceRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\FinderRequestForm;
use App\Http\Requests\Support\PhotoUploads;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Personal Finder: customers describe a piece they are looking for; the team searches for it. */
class PersonalFinderController extends Controller
{
    public function show(): View
    {
        return view('storefront.personal-finder', [
            'categories' => Category::query()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(FinderRequestForm $form, StoreServiceRequest $store): RedirectResponse
    {
        $user = $form->user();
        $request = new FinderRequest([...$form->details(), 'user_id' => $user instanceof User ? $user->id : null]);

        $store->handle($request, PhotoUploads::toDomain($form->file('photos')), FinderRequest::MEDIA_REFERENCES);

        return redirect()->to(localized_route('personal-finder'))->with('submitted', $request->reference);
    }
}
