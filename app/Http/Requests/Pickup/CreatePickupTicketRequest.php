<?php

namespace App\Http\Requests\Pickup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePickupTicketRequest extends FormRequest
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
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'scheduled_at' => ['nullable', 'date'],
            'location_type' => ['required', Rule::in(['gudang', 'jemput_rumah', 'jemput_pasar'])],
            'is_sorted' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
