<?php

namespace App\Http\Requests\Savings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaySavingsRequest extends FormRequest
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
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'label' => ['required', Rule::in(['POKOK', 'WAJIB', 'TIPPING', 'SUKARELA'])],
            'jumlah' => ['required', 'numeric', 'min:1000'],
            'metode' => ['required', Rule::in(['tunai', 'transfer'])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
