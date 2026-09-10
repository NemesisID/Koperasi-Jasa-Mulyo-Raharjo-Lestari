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
            // Harga Kotor (unsorted) & Harga Jual diisi pengurus; Harga Bersih = 80%×jual (dihitung sistem).
            // price_sorted tidak lagi dipakai form — opsional, pertahankan nilai lama bila tidak dikirim.
            'price_sorted' => ['nullable', 'numeric', 'min:0'],
            'price_unsorted' => ['required', 'numeric', 'min:0'],
            'price_sell' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
