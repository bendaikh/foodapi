<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'max_distance_km' => (float)$this->max_distance_km,
            'delivery_price' => (float)$this->delivery_price,
            'sort_order' => (int)$this->sort_order,
            'status' => (int)$this->status,
            'branch' => $this->whenLoaded('branch'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


