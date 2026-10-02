<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Phone\SaudiMobile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SaudiMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || SaudiMobile::normalize($value) === null) {
            $fail('validation.saudi_mobile')->translate();
        }
    }
}
