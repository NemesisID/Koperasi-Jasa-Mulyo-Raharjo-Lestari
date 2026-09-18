<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTransactionRequest extends FormRequest
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
        return [
            'category_id' => ['required', 'integer', 'exists:finance_categories,id'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(['tunai', 'transfer'])],
            // Bukti foto opsional — sama seperti foto timbang (jpg/png, maks 5MB).
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
