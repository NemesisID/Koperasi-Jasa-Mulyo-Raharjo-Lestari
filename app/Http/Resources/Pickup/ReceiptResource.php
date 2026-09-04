<?php

namespace App\Http\Resources\Pickup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    /**
     * Nota digital timbang: rincian berat, potongan 20%, saldo bersih masuk.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pickup_id' => $this->id,
            // ponytail: nomor nota memakai kode transaksi kas; buat kolom sendiri jika perlu format beda
            'receipt_number' => $this->items->first()?->transaction?->transaction_code,
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
                'total_value' => $item->total_value,
            ]),
            'total_gross' => $this->total_gross,
            'operational_fee_percent' => 20,
            'operational_fee' => $this->total_fee,
            'net_earned' => $this->total_net,
            'verify_url' => url("/api/v1/pickups/{$this->id}/receipt"),
        ];
    }
}
