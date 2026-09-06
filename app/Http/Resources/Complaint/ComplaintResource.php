<?php

namespace App\Http\Resources\Complaint;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pickup' => $this->whenLoaded('pickup', fn () => [
                'id' => $this->pickup->id,
                'status' => $this->pickup->status,
                'total_net' => $this->pickup->total_net,
                'completed_at' => $this->pickup->completed_at?->toIso8601String(),
            ]),
            'member' => $this->whenLoaded('member', fn () => [
                'member_code' => $this->member->member_code,
                'name' => $this->member->name,
            ]),
            'issue_type' => $this->issue_type,
            'description' => $this->description,
            'proof_image' => $this->proof_image,
            'status' => $this->status,
            'adjustment_amount' => $this->adjustment_amount,
            'resolution_note' => $this->resolution_note,
            'resolver' => $this->whenLoaded('resolver', fn () => $this->resolver?->only(['id', 'name'])),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
