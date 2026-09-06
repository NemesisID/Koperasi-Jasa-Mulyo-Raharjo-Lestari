<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', 'unique:finance_categories,name'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'group_type' => ['required', Rule::in(['simpanan_pokok', 'simpanan_wajib', 'tipping_fee', 'operasional', 'lainnya'])],
        ];
    }
}
