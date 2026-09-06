<?php

namespace App\Http\Requests\Shu;

use Illuminate\Foundation\Http\FormRequest;

class PublishShuRequest extends FormRequest
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
            'shu_distribution_id' => ['required', 'integer', 'exists:shu_distributions,id'],
        ];
    }
}
