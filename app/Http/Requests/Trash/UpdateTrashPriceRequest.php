<?php

namespace App\Http\Requests\Trash;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrashPriceRequest extends FormRequest
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
            'price_sorted' => ['required', 'numeric', 'min:0'],
            'price_unsorted' => ['required', 'numeric', 'min:0'],
            'price_sell' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
