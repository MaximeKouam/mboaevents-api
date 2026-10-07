<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_free' => $this->is_free,
            'price' => $this->price,
            'currency' => $this->currency,
            'remaining' => $this->remaining(),
            'available' => $this->isAvailable(),
        ];
    }
}
