<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Domain\Identity\Actions\ChangePassword;
use App\Domain\Identity\Actions\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\PasswordRequest;
use App\Http\Requests\Account\ProfileRequest;
use App\Support\Localization\LocalizedRoute;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['user' => AccountController::customer($request)]);
    }

    public function update(ProfileRequest $request, UpdateProfile $update): RedirectResponse
    {
        $user = $update->handle($request->account(), $request->profile());

        // Shown in the language the customer just chose.
        return redirect()->to(LocalizedRoute::url('account.profile', [], $user->locale))
            ->with('status', __('account.profile.saved', locale: $user->locale));
    }

    public function updatePassword(PasswordRequest $request, ChangePassword $change): RedirectResponse
    {
        $change->handle(AccountController::customer($request), $request->string('password')->toString(), $request->session()->getId());
        $request->session()->regenerate();

        return redirect()->to(localized_route('account.profile'))->with('status', __('account.password.saved'));
    }
}
