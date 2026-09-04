<?php

namespace App\Http\Resources\Trash;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'old_price_sorted' => $this->old_price_sorted,
            'new_price_sorted' => $this->new_price_sorted,
            'old_price_unsorted' => $this->old_price_unsorted,
            'new_price_unsorted' => $this->new_price_unsorted,
            'notes' => $this->notes,
            'changed_by' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'changed_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
