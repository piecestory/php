<?php

declare(strict_types=1);

namespace App\View\Components\Site;

use App\Domain\Catalog\Models\Category;
use App\Domain\Settings\StoreSettings;
use App\Domain\Store\Enums\BranchType;
use App\Domain\Store\Models\Branch;
use App\Support\Localization\LocalizedRoute;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Footer extends Component
{
    /** @return Collection<int, Category> */
    public function categories(): Collection
    {
        if (! LocalizedRoute::has('category')) {
            return collect();
        }

        return Category::query()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get();
    }

    /** @return Collection<int, Branch> */
    public function showrooms(): Collection
    {
        return Branch::query()
            ->where('is_active', true)
            ->where('type', BranchType::Showroom)
            ->orderBy('sort_order')
            ->get();
    }

    public function email(): ?string
    {
        return app(StoreSettings::class)->get('store.email');
    }

    public function render(): View
    {
        return view('components.site.footer');
    }
}
