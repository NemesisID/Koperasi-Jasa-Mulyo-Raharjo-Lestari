<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogisticsRouteRequest extends FormRequest
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
            'jadwal_angkut' => ['required', 'date'],
            'total_organik' => ['nullable', 'numeric', 'min:0'],
            'total_anorganik' => ['nullable', 'numeric', 'min:0'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'desa' => ['nullable', 'string', 'max:100'],
            'dusun' => ['nullable', 'string', 'max:100'],
            'rw' => ['nullable', 'string', 'max:5'],
            'rt' => ['nullable', 'string', 'max:5'],
            'alamat' => ['required', 'string', 'max:1000'],
        ];
    }
}
