<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // --- infos de base (mêmes clés que la liste) ---
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
                ? asset('storage/' . $this->cover_image_path)
                : null,
            'organizer' => $this->organizerPage?->name ?? 'Organisateur',
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_free' => $this->is_free,

            // --- détail ---
            'description' => $this->description ?? '',
            'highlights' => $this->highlights ?? [],
            'tags' => $this->tags ?? [],
            'gallery' => collect($this->gallery ?? [])
                ->map(fn($p) => asset('storage/' . $p))
                ->values()
                ->all(),
            'address' => $this->address ?? $this->venue_name,
            'venue_name' => $this->venue_name,
            'online_url' => $this->online_url,
            'audio_narration_url' => $this->audio_narration_path
                ? asset('storage/' . $this->audio_narration_path)
                : null,

            // --- stats ---
            'capacity' => $this->capacity,
            'registration_count' => $this->registration_count,
            'viewers_count' => $this->viewers_count,

            // --- programme groupé par jour ---
            'program' => $this->programItems
                ->groupBy('day_number')
                ->map(fn($items) => [
                    'day' => $items->first()->day_label,
                    'date' => $items->first()->date_label,
                    'items' => $items->map(fn($it) => [
                        'time' => $it->starts_at?->format('H:i') ?? '',
                        'title' => $it->title,
                        'speaker' => $it->speaker ?? '—',
                        'tag' => $it->tag ?? 'Session',
                    ])->values()->all(),
                ])
                ->values()
                ->all(),

            // --- intervenants ---
            'speakers' => $this->speakers->map(fn($s) => [
                'name' => $s->name,
                'role' => $s->role ?? '',
                'img' => $s->image_path
                    ? asset('storage/' . $s->image_path)
                    : "https://i.pravatar.cc/200?u={$s->id}",
                'tag' => $s->tag ?? 'Conférence',
                'twitter' => $s->twitter,
                'linkedin' => $s->linkedin,
                'website' => $s->website,
            ])->values()->all(),

            // --- billets (tiers) ---
            'ticket_types' => $this->ticketTypes
                ->where('is_active', true)
                ->map(fn($t) => [
                    'id' => (string) $t->id,
                    'name' => $t->name,
                    'price' => $t->is_free
                        ? 'Gratuit'
                        : number_format((int) $t->price, 0, ',', ' ') . ' FCFA',
                    'perks' => $t->perks ?? [],
                    'left' => max(0, (int) $t->quantity_total - (int) $t->quantity_sold),
                    'tag' => $t->tag,
                ])
                ->values()
                ->all(),

            // --- avis ---
            'reviews' => [
                'average' => (float) ($this->average_rating ?? 0),
                'count' => (int) ($this->review_count ?? 0),
                'distribution' => $this->ratingDistribution(),
                'list' => $this->reviews
                    ->sortByDesc('created_at')              // 👈 tri en mémoire après chargement
                    ->take(5)
                    ->map(fn($r) => [
                        'name' => $r->user?->name ?? 'Anonyme',
                        'avatar' => "https://i.pravatar.cc/120?u={$r->user_id}",
                        'date' => $r->created_at?->diffForHumans() ?? '',
                        'rating' => $r->rating,
                        'text' => $r->text,
                    ])
                    ->values()
                    ->all(),
            ],

            // --- FAQ ---
            'faq' => $this->faq ?? [],
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

        return number_format($this->lowest_price, 0, ',', ' ') . ' FCFA';
    }

    /** Distribution 5★→1★ calculée côté SQL pour éviter 5 requêtes. */
    private function ratingDistribution(): array
    {
        $rows = $this->reviews()
            ->reorder()                                // 👈 purge les ORDER BY hérités
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $count = max(1, (int) $this->review_count);

        return collect([5, 4, 3, 2, 1])->map(fn($stars) => [
            'stars' => $stars,
            'pct' => (int) round((($rows[$stars] ?? 0) / $count) * 100),
        ])->all();
    }
}
