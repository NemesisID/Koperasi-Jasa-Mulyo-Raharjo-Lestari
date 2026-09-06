<?php

namespace App\Http\Requests\Shu;

use Illuminate\Foundation\Http\FormRequest;

class SimulateShuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'net_profit' => ['required', 'numeric', 'min:0'],
            'shu_pool_percentage' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ];
    }
}
