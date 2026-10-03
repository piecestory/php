<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Auctions\Actions\RegisterAuctionInterest;
use App\Domain\Auctions\Enums\AuctionPhase;
use App\Domain\Auctions\Exceptions\AuctionClosed;
use App\Domain\Auctions\Models\Auction;
use App\Domain\Auctions\Models\AuctionLot;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AuctionInterestForm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Auction showcase (v1): published auctions, their lots, and "register your interest". No online bidding. */
class AuctionController extends Controller
{
    private const int PAST_SHOWN = 12;

    public function index(): View
    {
        $auctions = Auction::query()->visible()->with('media')->withCount('lots')->orderBy('starts_at')->get();
        [$current, $past] = $auctions->partition(fn (Auction $auction): bool => $auction->phase() !== AuctionPhase::Ended);

        return view('storefront.auctions.index', [
            'current' => $current->values(),
            'past' => $past->sortByDesc('ends_at')->take(self::PAST_SHOWN)->values(),
        ]);
    }

    public function show(Auction $auction): View
    {
        abort_unless($auction->status->isPublic(), 404);

        $auction->load(['media', 'lots.media', 'lots.product.media']);

        return view('storefront.auctions.show', [
            'auction' => $auction,
            'phase' => $auction->phase(),
            'selectedLot' => request()->integer('lot') ?: null,
        ]);
    }

    public function interest(AuctionInterestForm $form, Auction $auction, RegisterAuctionInterest $register): RedirectResponse
    {
        abort_unless($auction->status->isPublic(), 404);

        $lot = $form->filled('lot') ? $auction->lots()->find($form->integer('lot')) : null;
        $url = localized_route('auctions.show', $auction).'#interest';

        if ($form->filled('lot') && ! $lot instanceof AuctionLot) {
            return redirect()->to($url)->withInput()->withErrors(['lot' => __('auctions.interest.unknown_lot')]);
        }

        try {
            $register->handle($auction, $lot, $form->contact());
        } catch (AuctionClosed) {
            return redirect()->to($url)->with('interest_closed', true);
        }

        return redirect()->to($url)->with('interest_registered', true);
    }
}
