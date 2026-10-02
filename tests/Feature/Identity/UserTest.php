<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

it('supports accounts created with a mobile number only', function (): void {
    $user = User::factory()->phoneOnly()->create();

    expect($user->fresh())->email->toBeNull()->password->toBeNull()->phone->toStartWith('+9665');
});

it('allows many accounts without email but never two with the same phone', function (): void {
    User::factory()->phoneOnly()->count(2)->create();
    expect(User::query()->whereNull('email')->count())->toBe(2);

    User::factory()->create(['phone' => '+966500000001']);
    User::factory()->create(['phone' => '+966500000001']);
})->throws(UniqueConstraintViolationException::class);

it('hashes passwords and hides them from serialization', function (): void {
    $user = User::factory()->create(['password' => 'secret-pass-1']);

    expect($user->getAttributes()['password'])->not->toBe('secret-pass-1')
        ->and($user->toArray())->not->toHaveKey('password');
});
