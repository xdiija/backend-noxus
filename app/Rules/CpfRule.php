<?php

namespace App\Rules;

use App\Helpers\CpfHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!CpfHelper::isValid($value)) {
            $fail("O campo $attribute é inválido.");
        }
    }
}