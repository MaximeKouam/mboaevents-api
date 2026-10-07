<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'category' => $this->category?->name ?? 'Événement',
            'mode' => $this->modeLabel(),
            'city' => $this->city?->name ?? 'En ligne',
            'date' => $this->starts_at?->translatedFormat('d M') ?? '—',
            'time' => $this->starts_at?->format('H:i') ?? '—',
            'price' => $this->priceLabel(),
            'cover_image' => $this->cover_image_path
                ? asset('storage/'.$this->cover_image_path)
                : null,
            'organizer' => $this->organizerPage?->name ?? 'Organisateur',
            'starts_at' => $this->starts_at?->toIso8601String(),
            'is_free' => $this->is_free,
        ];
    }

    private function modeLabel(): string
    {
        return match ($this->mode) {
            'online' => 'En ligne',
            'hybrid' => 'Hybride',
            default => 'Présentiel',
        };
    }

    private function priceLabel(): string
    {
        if ($this->is_free || $this->lowest_price === 0) {
            return 'Gratuit';
        }

        return number_format($this->lowest_price, 0, ',', ' ').' FCFA';
    }
}
