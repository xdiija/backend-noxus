<?php

namespace App\Rules;

use App\Helpers\PhoneHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Phone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!PhoneHelper::isValid($value)) {
            $fail("O campo $attribute é inválido.");
        }
    }
}