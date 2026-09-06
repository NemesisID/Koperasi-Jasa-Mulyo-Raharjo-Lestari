<?php

namespace App\Http\Resources\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member' => $this->whenLoaded('member', fn () => [
                'member_code' => $this->member->member_code,
                'name' => $this->member->name,
            ]),
            'amount' => $this->amount,
            'method' => $this->method,
            'bank_name' => $this->bank_name,
            'account_number' => $this->maskedAccountNumber(),
            'account_holder' => $this->account_holder,
            'status' => $this->status,
            'notes' => $this->notes,
            'processor' => $this->whenLoaded('processor', fn () => $this->processor?->only(['id', 'name'])),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Sembunyikan sebagian nomor rekening di respons.
     */
    private function maskedAccountNumber(): ?string
    {
        if (! $this->account_number) {
            return null;
        }

        return str_pad(substr($this->account_number, -4), strlen($this->account_number), '*', STR_PAD_LEFT);
    }
}
