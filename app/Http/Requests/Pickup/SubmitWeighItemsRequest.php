<?php

namespace App\Http\Requests\Pickup;

use Illuminate\Foundation\Http\FormRequest;

class SubmitWeighItemsRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'integer', 'exists:trash_categories,id'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'items.*.unit_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Rincian timbangan wajib diisi minimal satu item.',
            'items.min' => 'Rincian timbangan wajib diisi minimal satu item.',
        ];
    }
}
