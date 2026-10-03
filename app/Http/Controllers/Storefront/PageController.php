<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Content\Models\Page;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** About, services and the policies: fixed URLs, content edited in the admin; unpublished pages are 404s. */
class PageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $page = Page::query()
            ->where('key', (string) $request->route('pageKey'))
            ->where('is_published', true)
            ->firstOrFail();

        return view('storefront.page', ['page' => $page]);
    }
}
