<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

function validRegistration(array $overrides = []): array
{
    return [
        'name' => 'نورة',
        'email' => 'Noura@Example.com',
        'phone' => '0512345678',
        'password' => 'secret-pass-1',
        'password_confirmation' => 'secret-pass-1',
        'terms' => '1',
        ...$overrides,
    ];
}

it('registers a customer with a normalized email and phone, then signs them in', function (): void {
    $this->post('/register', validRegistration())->assertRedirect(url('/'));

    $user = User::query()->sole();
    expect($user)->email->toBe('noura@example.com')->phone->toBe('+966512345678')->locale->toBe('ar');
    $this->assertAuthenticatedAs($user);
});

it('keeps the language of the page used to register', function (): void {
    $this->post('/en/register', validRegistration())->assertRedirect(url('/en'));

    expect(User::query()->sole()->locale)->toBe('en');
});

it('rejects invalid registrations with Arabic messages', function (array $overrides, string $field): void {
    User::factory()->create(['email' => 'taken@example.com', 'phone' => '+966500000000']);

    $this->from('/register')->post('/register', validRegistration($overrides))
        ->assertRedirect('/register')
        ->assertSessionHasErrors($field);

    expect(session('errors')->first($field))->toMatch('/\p{Arabic}/u');
})->with([
    'bad phone' => [['phone' => '0112345678'], 'phone'],
    'phone taken' => [['phone' => '0500000000'], 'phone'],
    'email taken' => [['email' => 'TAKEN@example.com'], 'email'],
    'password mismatch' => [['password_confirmation' => 'other'], 'password'],
    'terms not accepted' => [['terms' => null], 'terms'],
]);

it('signs in with email or with mobile number', function (string $login): void {
    $user = User::factory()->create(['email' => 'saad@example.com', 'phone' => '+966511111111', 'password' => 'secret-pass-1']);

    $this->post('/login', ['login' => $login, 'password' => 'secret-pass-1'])->assertRedirect(url('/'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
})->with(['saad@example.com', 'SAAD@example.com', '0511111111', '+966 51 111 1111']);

it('refuses wrong passwords and deactivated accounts with the same message', function (): void {
    User::factory()->create(['email' => 'a@example.com', 'password' => 'secret-pass-1']);
    User::factory()->create(['email' => 'b@example.com', 'password' => 'secret-pass-1', 'is_active' => false]);

    $this->post('/login', ['login' => 'a@example.com', 'password' => 'wrong'])->assertSessionHasErrors(['login' => __('auth.failed')]);
    $this->post('/login', ['login' => 'b@example.com', 'password' => 'secret-pass-1'])->assertSessionHasErrors(['login' => __('auth.failed')]);
    $this->assertGuest();
});

it('locks sign-in after five failed attempts', function (): void {
    User::factory()->create(['email' => 'a@example.com', 'password' => 'secret-pass-1']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['login' => 'a@example.com', 'password' => 'wrong']);
    }

    $this->post('/login', ['login' => 'a@example.com', 'password' => 'secret-pass-1'])->assertSessionHasErrors('login');
    $this->assertGuest();
});

it('signs out and invalidates the session', function (): void {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect(url('/'));
    $this->assertGuest();
});

it('keeps signed-in users away from the sign-in pages', function (): void {
    $this->actingAs(User::factory()->create())->get('/login')->assertRedirect(url('/'));
});

it('answers the same way whether or not an email is registered', function (): void {
    Notification::fake();
    User::factory()->create(['email' => 'known@example.com']);

    $this->post('/forgot-password', ['email' => 'known@example.com'])->assertSessionHas('status', __('auth.reset_link_sent'));
    $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertSessionHas('status', __('auth.reset_link_sent'));

    Notification::assertSentTimes(ResetPassword::class, 1);
});

it('sends reset links in the customer language', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'en@example.com', 'locale' => 'en']);

    $this->post('/en/forgot-password', ['email' => 'en@example.com']);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        return str_contains($notification->toMail($user)->actionUrl, '/en/reset-password/');
    });
});

it('resets the password with a valid token', function (): void {
    $user = User::factory()->create(['email' => 'r@example.com']);
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token, 'email' => 'r@example.com', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123',
    ])->assertRedirect(url('/login'));

    $this->post('/login', ['login' => 'r@example.com', 'password' => 'new-pass-123']);
    $this->assertAuthenticatedAs($user);
});
