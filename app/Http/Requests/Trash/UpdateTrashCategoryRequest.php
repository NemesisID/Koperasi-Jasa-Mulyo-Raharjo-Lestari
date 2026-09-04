<?php

namespace App\Http\Requests\Trash;

use Illuminate\Validation\Rule;

class UpdateTrashCategoryRequest extends StoreTrashCategoryRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['required', 'string', 'max:100', Rule::unique('trash_categories', 'name')->ignore($this->route('id'))];

        return $rules;
    }
}
