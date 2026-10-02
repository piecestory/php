<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Content\Models\Faq;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function __invoke(): View
    {
        return view('storefront.faq', [
            'faqs' => Faq::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
