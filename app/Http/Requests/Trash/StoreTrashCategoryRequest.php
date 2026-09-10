<?php

namespace App\Http\Requests\Trash;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrashCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', 'unique:trash_categories,name'],
            'type' => ['required', Rule::in(['logam', 'besi', 'kertas', 'plastik', 'elektronik', 'organik', 'campur', 'lainnya'])],
            'unit' => ['required', Rule::in(['kg', 'biji', 'unit'])],
            'price_sorted' => ['nullable', 'numeric', 'min:0'],
            'price_unsorted' => ['required', 'numeric', 'min:0'],
            'price_sell' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
