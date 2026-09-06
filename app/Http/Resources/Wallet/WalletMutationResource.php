<?php

namespace App\Http\Resources\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class WalletMutationResource extends JsonResource
{
    /**
     * @param  Collection<int, array<string, mixed>>  $resource
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource['type'],
            'source' => $this->resource['source'],
            'reference' => $this->resource['reference'],
            'description' => $this->resource['description'],
            'amount' => $this->resource['amount'],
            'date' => $this->resource['date'],
        ];
    }
}
