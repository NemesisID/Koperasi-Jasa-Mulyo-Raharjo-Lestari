<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveComplaintRequest extends FormRequest
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
            'status' => ['required', Rule::in(['diterima', 'ditolak'])],
            'adjustment_amount' => ['required_if:status,diterima', 'nullable', 'numeric', 'min:0'],
            'resolution_note' => ['required', 'string', 'max:2000'],
        ];
    }
}
