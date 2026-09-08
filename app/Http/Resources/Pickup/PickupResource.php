<?php

namespace App\Http\Resources\Pickup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'member_code' => $this->member->member_code,
                'name' => $this->member->name,
                'address' => $this->member->address,
                'phone' => $this->member->phone,
            ]),
            'officer' => $this->whenLoaded('officer', fn () => $this->officer?->only(['id', 'name'])),
            'location_type' => $this->location_type,
            'is_sorted' => $this->is_sorted,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'total_gross' => $this->total_gross,
            'total_fee' => $this->total_fee,
            'total_net' => $this->total_net,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'category' => $item->relationLoaded('category') ? $item->category?->only(['id', 'name', 'type', 'unit']) : null,
                'weight_kg' => $item->weight_kg,
                'unit_count' => $item->unit_count,
                'total_value' => $item->total_value,
            ])),
        ];
    }
}
