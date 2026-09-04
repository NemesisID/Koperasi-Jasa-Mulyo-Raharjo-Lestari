<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(['aktif', 'nonaktif', 'suspend'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
