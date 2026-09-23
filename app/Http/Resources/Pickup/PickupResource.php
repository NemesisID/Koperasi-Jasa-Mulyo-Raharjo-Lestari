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
            'sort_order' => $this->sort_order,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'member_code' => $this->member->member_code,
                'name' => $this->member->name,
                'address' => $this->member->address,
                // Alamat sesuai kategori (rumah/pasar); ?: agar string kosong juga fallback.
                'address_rumah' => $this->member->address_rumah ?: $this->member->address,
                'address_pasar' => $this->member->address_pasar ?: $this->member->address,
                'phone' => $this->member->phone,
            ]),
            'officer' => $this->whenLoaded('officer', fn () => $this->officer?->only(['id', 'name'])),
            'location_type' => $this->location_type,
            'is_sorted' => $this->is_sorted,
            'status' => $this->status,
            'photo_url' => $this->photoUrl(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
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
                'price_per_unit' => $item->price_per_unit,
                'total_value' => $item->total_value,
            ])),
            'receipt' => $this->whenLoaded('receipt', fn () => $this->receipt ? [
                'id' => $this->receipt->id,
                'receipt_number' => $this->receipt->receipt_number,
                'member_name' => $this->receipt->member_name,
                'member_code' => $this->receipt->member_code,
                'officer_name' => $this->receipt->officer_name,
                'location_type' => $this->receipt->location_type,
                'location_label' => $this->receipt->location_label,
                'items' => $this->receipt->items_payload,
                'nota_data' => $this->receipt->nota_data,
                'total_gross' => (float) $this->receipt->total_gross,
                'total_fee' => (float) $this->receipt->total_fee,
                'total_net' => (float) $this->receipt->total_net,
                'issued_at' => $this->receipt->issued_at?->toIso8601String(),
            ] : null),
        ];
    }
}
