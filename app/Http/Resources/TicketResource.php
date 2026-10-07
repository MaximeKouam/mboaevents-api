<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'qr_token' => $this->qr_token,
            'ticket_type' => $this->ticketType?->name,
            'event' => [
                'title' => $this->event?->title,
                'starts_at' => $this->event?->starts_at,
            ],
        ];
    }
}
