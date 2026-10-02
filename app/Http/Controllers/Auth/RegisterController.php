<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterCustomer $register): RedirectResponse
    {
        /** @var array{name: string, email: string, phone: string, password: string} $data */
        $data = $request->safe()->only(['name', 'email', 'phone', 'password']);

        $user = $register->handle($data, app()->getLocale());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(localized_route('home'))->with('status', __('auth.welcome', ['name' => $user->name]));
    }
}
