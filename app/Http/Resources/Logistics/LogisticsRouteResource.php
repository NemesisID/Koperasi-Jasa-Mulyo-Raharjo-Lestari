<?php

namespace App\Http\Resources\Logistics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogisticsRouteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'driver' => $this->whenLoaded('user', fn () => $this->user?->only(['id', 'name', 'username'])),
            'jadwal_angkut' => $this->jadwal_angkut?->toIso8601String(),
            'total_organik' => $this->total_organik,
            'total_anorganik' => $this->total_anorganik,
            'wilayah' => [
                'kecamatan' => $this->kecamatan,
                'desa' => $this->desa,
                'dusun' => $this->dusun,
                'rw' => $this->rw,
                'rt' => $this->rt,
            ],
            'alamat' => $this->alamat,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
