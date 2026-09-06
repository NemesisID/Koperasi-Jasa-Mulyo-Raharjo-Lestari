<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transaction_code,
            'member' => $this->whenLoaded('member', fn () => $this->member?->only(['id', 'member_code', 'name'])),
            'category' => $this->whenLoaded('category', fn () => $this->category?->only(['id', 'name', 'type', 'group_type'])),
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'transaction_date' => $this->transaction_date?->toIso8601String(),
            'handled_by' => $this->whenLoaded('officer', fn () => $this->officer?->only(['id', 'name'])),
        ];
    }
}
