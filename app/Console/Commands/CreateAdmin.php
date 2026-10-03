<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates the first system admin (or promotes an existing account) on a new server. The password is
 * typed at a hidden prompt: it never appears in code, shell history or logs.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create a system admin account for the staff panel (/admin)';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);

        $name = text('Name', required: true);
        $email = mb_strtolower(text('Email', required: true, validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email address.'));
        $password = password('Password', required: true, validate: function (string $value): ?string {
            $validator = Validator::make(['password' => $value], ['password' => [Password::min(10)->letters()->numbers()]]);

            return $validator->fails() ? $validator->errors()->first('password') : null;
        });

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill(['name' => $name, 'locale' => $user->locale ?? 'ar']);
        $user->password = $password;
        $user->is_active = true;
        $user->save();
        $user->syncRoles([Role::Admin->value]);

        $this->info("{$email} can now sign in at ".url('/admin'));

        return self::SUCCESS;
    }
}
