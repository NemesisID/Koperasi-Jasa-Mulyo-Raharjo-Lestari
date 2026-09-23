<?php

namespace App\Http\Resources\Pickup;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    /**
     * Nota digital timbang: data frozen dari tabel receipts atau fallback.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof Receipt) {
            return [
                'id' => $this->id,
                'pickup_id' => $this->pickup_id,
                'receipt_number' => $this->receipt_number,
                'member' => [
                    'member_code' => $this->member_code,
                    'name' => $this->member_name,
                ],
                'officer' => $this->officer_name,
                'location_type' => $this->location_type,
                'location_label' => $this->location_label,
                'issued_at' => $this->issued_at?->toIso8601String(),
                'items' => $this->items_payload ?? [],
                'nota_data' => $this->nota_data,
                'total_gross' => (float) $this->total_gross,
                'operational_fee_percent' => 0,
                'operational_fee' => (float) $this->total_fee,
                'net_earned' => (float) $this->total_net,
                'verify_url' => url("/api/v1/pickups/{$this->pickup_id}/receipt"),
            ];
        }

        // Jika resource adalah Pickup yang memiliki relasi receipt tersimpan
        if ($this->relationLoaded('receipt') && $this->receipt) {
            return (new self($this->receipt))->toArray($request);
        }

        return [
            'pickup_id' => $this->id,
            'receipt_number' => $this->receipt?->receipt_number ?? $this->items->first()?->transaction?->transaction_code,
            'member' => $this->whenLoaded('member', fn () => [
                'member_code' => $this->member->member_code,
                'name' => $this->member->name,
            ]),
            'officer' => $this->whenLoaded('officer', fn () => $this->officer?->name),
            'location_type' => $this->location_type,
            'issued_at' => $this->completed_at?->toIso8601String(),
            'items' => $this->items->map(fn ($item) => [
                'name' => $item->category?->name,
                'unit' => $item->category?->unit,
                'weight_kg' => $item->weight_kg,
                'unit_count' => $item->unit_count,
                'price_per_unit' => $item->price_per_unit,
                'total_value' => $item->total_value,
            ]),
            'total_gross' => $this->total_gross,
            'operational_fee_percent' => 0,
            'operational_fee' => $this->total_fee,
            'net_earned' => $this->total_net,
            'verify_url' => url("/api/v1/pickups/{$this->id}/receipt"),
        ];
    }
}
