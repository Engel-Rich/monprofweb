<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CameroonMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! PhoneNumber::isValidCameroonMobile(is_scalar($value) ? (string) $value : null)) {
            $fail('Le champ :attribute doit être un numéro mobile camerounais valide.');
        }
    }
}
