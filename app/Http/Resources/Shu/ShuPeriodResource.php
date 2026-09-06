<?php

namespace App\Http\Resources\Shu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShuPeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'total_shu' => $this->total_shu,
            'reserve_amount' => $this->reserve_amount,
            'distributed_amount' => $this->distributed_amount,
            'recipient_count' => $this->shu_members_count ?? $this->recipient_count,
            'status' => $this->status,
            'distribution_date' => $this->distribution_date?->toDateString(),
            'handled_by' => $this->whenLoaded('handledBy', fn () => $this->handledBy?->only(['id', 'name'])),
        ];
    }
}
