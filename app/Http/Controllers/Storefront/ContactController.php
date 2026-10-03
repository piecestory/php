<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Content\Actions\SubmitContactMessage;
use App\Domain\Store\Enums\BranchType;
use App\Domain\Store\Models\Branch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('storefront.contact', [
            'showrooms' => Branch::query()->where('is_active', true)->where('type', BranchType::Showroom)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(ContactRequest $request, SubmitContactMessage $submit): RedirectResponse
    {
        $submit->handle($request->contactMessage(), $request->ip());

        return redirect()->to(localized_route('contact'))->with('status', __('contact.sent'));
    }
}
