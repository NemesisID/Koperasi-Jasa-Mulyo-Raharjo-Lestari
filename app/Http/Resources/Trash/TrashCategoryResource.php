<?php

namespace App\Http\Resources\Trash;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrashCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'unit' => $this->unit,
            'price_sorted' => $this->price_sorted,
            'price_unsorted' => $this->price_unsorted,
            'price_sell' => $this->price_sell,
            'price_admin' => $this->price_admin,
            'price_member' => $this->price_member,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
