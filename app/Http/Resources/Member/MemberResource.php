<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_code' => $this->member_code,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            // ?: bukan ?? — string kosong ('') juga fallback ke alamat umum.
            'address_rumah' => $this->address_rumah ?: $this->address,
            'address_pasar' => $this->address_pasar ?: $this->address,
            'status' => $this->status,
            'join_date' => $this->join_date?->toDateString(),
            'categories' => $this->categories ?? [],
            'category' => new MemberCategoryResource($this->whenLoaded('category')),
            // Ploting petugas: satu baris per anggota/alamat, jadi anggota dengan
            // 2 tempat muncul 2 kali dengan officer masing-masing.
            'officer' => $this->officer ? [
                'id' => $this->officer->id,
                'name' => $this->officer->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
