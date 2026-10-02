<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Domain\Identity\Actions\DeleteAddress;
use App\Domain\Identity\Actions\SaveAddress;
use App\Domain\Identity\Actions\SetDefaultAddress;
use App\Domain\Identity\Exceptions\AddressLimitReached;
use App\Domain\Identity\Models\Address;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Address book. Addresses are always looked up among the customer's own: another customer's id is a 404. */
class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses.index', [
            'addresses' => AccountController::customer($request)->addresses()->orderByDesc('is_default')->latest('id')->get(),
            'canAdd' => AccountController::customer($request)->addresses()->count() < SaveAddress::MAX_ADDRESSES,
        ]);
    }

    public function create(Request $request): View
    {
        $user = AccountController::customer($request);

        // Pre-filled with the customer's own name and mobile: most addresses are for themselves.
        return view('account.addresses.form', ['address' => new Address([
            'recipient_name' => $user->name,
            'phone' => $user->phone,
        ])]);
    }

    public function store(AddressRequest $request, SaveAddress $save): RedirectResponse
    {
        try {
            $save->handle(AccountController::customer($request), $request->address(), $request->boolean('is_default'));
        } catch (AddressLimitReached $e) {
            return back()->withInput()->with('error', __($e->translationKey(), ['max' => SaveAddress::MAX_ADDRESSES]));
        }

        return redirect()->to(localized_route('account.addresses'))->with('status', __('account.addresses.saved'));
    }

    public function edit(Request $request, int $address): View
    {
        return view('account.addresses.form', ['address' => $this->find($request, $address)]);
    }

    public function update(AddressRequest $request, int $address, SaveAddress $save): RedirectResponse
    {
        $save->handle(AccountController::customer($request), $request->address(), $request->boolean('is_default'), $this->find($request, $address));

        return redirect()->to(localized_route('account.addresses'))->with('status', __('account.addresses.saved'));
    }

    public function makeDefault(Request $request, int $address, SetDefaultAddress $setDefault): RedirectResponse
    {
        $setDefault->handle($this->find($request, $address));

        return redirect()->to(localized_route('account.addresses'))->with('status', __('account.addresses.default_set'));
    }

    public function destroy(Request $request, int $address, DeleteAddress $delete): RedirectResponse
    {
        $delete->handle($this->find($request, $address));

        return redirect()->to(localized_route('account.addresses'))->with('status', __('account.addresses.deleted'));
    }

    private function find(Request $request, int $id): Address
    {
        return AccountController::customer($request)->addresses()->findOrFail($id);
    }
}
