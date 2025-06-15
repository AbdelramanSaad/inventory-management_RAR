<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'type' => $this->type,
            'description' => $this->description,
            'warehouse' => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'inventory_item' => $this->when($this->inventory_item_id, function () {
                return [
                    'id' => $this->inventoryItem->id,
                    'name' => $this->inventoryItem->name,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
