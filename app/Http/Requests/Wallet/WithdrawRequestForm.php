<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $rules = [
            'amount' => ['required', 'numeric', 'min:10000'],
            'method' => ['required', Rule::in(['tunai', 'transfer'])],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_holder' => ['nullable', 'string', 'max:100'],
        ];

        if ($this->input('method') === 'transfer') {
            $rules['bank_name'] = ['required', 'string', 'max:100'];
            $rules['account_number'] = ['required', 'string', 'max:50'];
            $rules['account_holder'] = ['required', 'string', 'max:100'];
        }

        return $rules;
    }
}
