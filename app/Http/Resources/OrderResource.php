<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'refund_amount' => $this->refund_amount,
            'event' => new EventResource($this->whenLoaded('event')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'ticket_type' => $item->ticketType?->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
        ];
    }
}
