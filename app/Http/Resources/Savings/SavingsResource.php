<?php

namespace App\Http\Resources\Savings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsResource extends JsonResource
{
    /**
     * Baris setoran_koperasi (UUID).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => $this->user?->only(['id', 'name', 'username'])),
            'jenis' => $this->jenis,
            'label' => $this->label,
            'jumlah' => $this->jumlah,
            'status' => $this->status,
            'catatan' => $this->catatan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
