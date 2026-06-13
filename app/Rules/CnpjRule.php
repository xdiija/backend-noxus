<?php

namespace App\Rules;

use App\Helpers\CnpjHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CnpjRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!CnpjHelper::isValid($value)) {
            $fail("O campo $attribute é inválido.");
        }
    }
}
