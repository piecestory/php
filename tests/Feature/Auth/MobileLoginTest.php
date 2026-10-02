<?php

declare(strict_types=1);

use App\Domain\Identity\Models\OneTimePassword;
use App\Domain\Identity\Models\User;
use App\Domain\Notifications\Sms\SmsGateway;
use App\Domain\Settings\Models\Setting;
use Tests\Support\FakeSmsGateway;

beforeEach(function (): void {
    $this->sms = new FakeSmsGateway;
    $this->app->instance(SmsGateway::class, $this->sms);
});

function enableSms(): void
{
    Setting::put('sms', 'enabled', '1');
}

it('is unavailable until SMS is enabled by the admin', function (): void {
    $this->get('/login/mobile')->assertNotFound();
    $this->get('/login')->assertDontSee(url('/login/mobile'));

    enableSms();

    $this->get('/login/mobile')->assertOk();
    $this->get('/login')->assertSee(url('/login/mobile'));
});

it('signs in an existing customer with the code sent by SMS', function (): void {
    enableSms();
    $user = User::factory()->create(['phone' => '+966512345678', 'phone_verified_at' => null]);

    $this->post('/login/mobile', ['phone' => '0512345678'])->assertRedirect(url('/login/mobile/code'));
    expect($this->sms->sent)->toHaveCount(1)->and($this->sms->sent[0]['phone'])->toBe('+966512345678');

    $this->post('/login/mobile/code', ['code' => $this->sms->lastCode()])->assertRedirect(url('/'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->phone_verified_at)->not->toBeNull();
});

it('stores only a hash of the code', function (): void {
    enableSms();
    $this->post('/login/mobile', ['phone' => '0512345678']);

    expect(OneTimePassword::query()->sole()->code_hash)->not->toContain($this->sms->lastCode());
});

it('creates an account for a new number after the customer gives their name', function (): void {
    enableSms();

    $this->post('/login/mobile', ['phone' => '0598765432']);
    $this->post('/login/mobile/code', ['code' => $this->sms->lastCode()])->assertRedirect(url('/login/mobile/profile'));
    $this->post('/login/mobile/profile', ['name' => 'ريم'])->assertRedirect(url('/'));

    $user = User::query()->sole();
    expect($user)->phone->toBe('+966598765432')->email->toBeNull()->password->toBeNull()->phone_verified_at->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong code and burns the code after five wrong attempts', function (): void {
    enableSms();
    User::factory()->create(['phone' => '+966512345678']);
    $this->post('/login/mobile', ['phone' => '0512345678']);
    $code = $this->sms->lastCode();
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        $this->post('/login/mobile/code', ['code' => $wrong])->assertSessionHasErrors('code');
    }

    $this->post('/login/mobile/code', ['code' => $code])->assertSessionHasErrors(['code' => __('auth.otp.expired')]);
    $this->assertGuest();
});

it('rejects expired codes and codes replaced by a newer one', function (): void {
    enableSms();
    User::factory()->create(['phone' => '+966512345678']);

    $this->post('/login/mobile', ['phone' => '0512345678']);
    $first = $this->sms->lastCode();
    $this->post('/login/mobile', ['phone' => '0512345678']);

    $this->post('/login/mobile/code', ['code' => $first])->assertSessionHasErrors('code');

    $this->travel(6)->minutes();
    $this->post('/login/mobile/code', ['code' => $this->sms->lastCode()])->assertSessionHasErrors(['code' => __('auth.otp.expired')]);
    $this->assertGuest();
});

it('limits how many codes one number can request', function (): void {
    enableSms();

    foreach (range(1, 3) as $request) {
        $this->post('/login/mobile', ['phone' => '0512345678'])->assertSessionHasNoErrors();
    }

    $this->post('/login/mobile', ['phone' => '0512345678'])->assertSessionHasErrors('phone');
    expect($this->sms->sent)->toHaveCount(3);
});

it('does not allow skipping the verification step', function (): void {
    enableSms();

    $this->get('/login/mobile/code')->assertRedirect(url('/login/mobile'));
    $this->post('/login/mobile/profile', ['name' => 'x'])->assertRedirect(url('/login/mobile'));
    expect(User::query()->count())->toBe(0);
});
