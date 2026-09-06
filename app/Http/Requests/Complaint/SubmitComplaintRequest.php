<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitComplaintRequest extends FormRequest
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
            'pickup_id' => ['required', 'integer', 'exists:pickups,id'],
            'issue_type' => ['required', Rule::in(['berat_salah', 'kategori_salah', 'harga_salah', 'lainnya'])],
            'description' => ['required', 'string', 'max:2000'],
            'proof_image' => ['nullable', 'image', 'max:4096'], // 4MB
        ];
    }
}
