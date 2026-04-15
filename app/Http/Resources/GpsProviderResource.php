<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GpsProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'driver' => $this->driver,
            'base_url' => $this->base_url,
            'is_active' => $this->is_active,
            // Munculkan jumlah kendaraan jika ada (withCount)
            'vehicles_count' => $this->whenCounted('vehicles'),
        ];
    }
}
