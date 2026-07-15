<?php

namespace App\Http\Requests\Donation;

use App\Helpers\PhoneHelper;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgId = auth()->user()?->organization_id;

        return [
            // Scope the FK check to the org so a tenant can't reference another
            // org's donor.
            'donor_id' => [
                'nullable',
                'integer',
                Rule::exists('donors', 'id')->where('organization_id', $orgId)->whereNull('deleted_at'),
            ],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => ['nullable', new PhoneRule],
            'donation_type_id' => ['required', 'integer', 'exists:donation_types,id'],
            // Nullable: in-kind donations (food, supplies) may have no cash value.
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'donation_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'donor_phone' => $this->donor_phone ? PhoneHelper::sanitize($this->donor_phone) : null,
        ];

        // Either a linked donor or the inline fields identify the donor
        // (finance.md) — a linked donor wins, so drop the inline fallback.
        if ($this->filled('donor_id')) {
            $merge += ['donor_name' => null, 'donor_email' => null, 'donor_phone' => null];
        }

        $this->merge($merge);
    }

    public function messages(): array
    {
        return [
            'donor_id.exists' => 'O doador informado é inválido.',
            'donor_name.max' => 'O nome do doador não pode ter mais de 255 caracteres.',
            'donor_email.email' => 'O email do doador é inválido.',
            'donation_type_id.required' => 'O tipo da doação é obrigatório.',
            'donation_type_id.exists' => 'O tipo da doação informado é inválido.',
            'amount.numeric' => 'O valor deve ser um número.',
            'amount.min' => 'O valor não pode ser negativo.',
            'amount.max' => 'O valor informado é muito alto.',
            'donation_date.required' => 'A data da doação é obrigatória.',
            'donation_date.date' => 'A data da doação é inválida.',
            'donation_date.before_or_equal' => 'A data da doação não pode ser futura.',
            'notes.string' => 'As observações devem ser um texto.',
        ];
    }
}
