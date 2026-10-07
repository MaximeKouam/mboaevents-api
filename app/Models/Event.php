<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    /* ================================================================== */
    /*  FILLABLE                                                          */
    /* ================================================================== */

    protected $fillable = [
        // Relations
        'organizer_page_id',
        'category_id',
        'city_id',

        // Contenu
        'title',
        'slug',
        'tagline',
        'description',
        'highlights',
        'faq',
        'tags',

        // Média
        'cover_image_path',
        'cover_video_path',
        'audio_narration_path',
        'gallery',

        // Mode & lieu
        'mode',
        'online_url',
        'venue_name',
        'address',
        'latitude',
        'longitude',

        // Dates
        'timezone',
        'starts_at',
        'ends_at',

        // Publication
        'status',

        // Stats
        'capacity',
        'registration_count',
        'viewers_count',
        'average_rating',
        'review_count',
        'lowest_price',
        'is_free',
    ];

    /* ================================================================== */
    /*  CASTS                                                             */
    /* ================================================================== */

    protected $casts = [
        'highlights' => 'array',
        'faq' => 'array',
        'tags' => 'array',
        'gallery' => 'array',

        'starts_at' => 'datetime',
        'ends_at' => 'datetime',

        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',

        'average_rating' => 'decimal:1',
        'capacity' => 'integer',
        'registration_count' => 'integer',
        'viewers_count' => 'integer',
        'review_count' => 'integer',
        'lowest_price' => 'integer',
        'is_free' => 'boolean',
    ];

    /* ================================================================== */
    /*  ROUTING                                                           */
    /* ================================================================== */

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ================================================================== */
    /*  RELATIONS                                                         */
    /* ================================================================== */

    public function organizerPage(): BelongsTo
    {
        return $this->belongsTo(OrganizerPage::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('position');
    }

    public function programItems(): HasMany
    {
        return $this->hasMany(EventProgramItem::class)
            ->orderBy('day_number')
            ->orderBy('position');
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(EventSpeaker::class)->orderBy('position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(EventReview::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /* ================================================================== */
    /*  SCOPES                                                            */
    /* ================================================================== */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now());
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_free', true);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_free', false);
    }

    public function scopeInCity(Builder $query, int $cityId): Builder
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeInMode(Builder $query, string $mode): Builder
    {
        return $query->where('mode', $mode);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('tagline', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Trie par proximité avec une formule de Haversine directement en SQL.
     * Suffisant tant que le catalogue reste raisonnable ; à remplacer par
     * Meilisearch (filtres géo natifs) si le volume grossit beaucoup.
     */
    public function scopeNearby(
        Builder $query,
        float $lat,
        float $lng,
        float $radiusKm = 80
    ): Builder {
        $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("events.*, {$haversine} AS distance_km")
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km');
    }

    /* ================================================================== */
    /*  HELPERS                                                           */
    /* ================================================================== */

    public function isFree(): bool
    {
        return $this->is_free;
    }

    public function lowestPrice(): int
    {
        return (int) $this->lowest_price;
    }

    public function isSoldOut(): bool
    {
        if ($this->ticketTypes->isEmpty()) {
            return false;
        }

        return $this->ticketTypes->every(
            fn (TicketType $type) => $type->remaining() <= 0
        );
    }

    public function remainingCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->registration_count);
    }

    public function isFull(): bool
    {
        return $this->capacity !== null
            && $this->registration_count >= $this->capacity;
    }

    public function hasStarted(): bool
    {
        return $this->starts_at && $this->starts_at->isPast();
    }

    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function modeLabel(): string
    {
        return match ($this->mode) {
            'online' => 'En ligne',
            'hybrid' => 'Hybride',
            default => 'Présentiel',
        };
    }

    public function priceLabel(): string
    {
        if ($this->is_free || $this->lowest_price === 0) {
            return 'Gratuit';
        }

        return number_format($this->lowest_price, 0, ',', ' ').' FCFA';
    }

    /**
     * Recalcule les statistiques dénormalisées (prix bas, gratuité, notes).
     * À appeler après création/édition d'un événement, ou en job planifié.
     */
    public function refreshStats(): self
    {
        $types = $this->ticketTypes()->get();

        $this->lowest_price = (int) ($types->min('price') ?? 0);
        $this->is_free = $types->isNotEmpty()
            && $types->every(fn (TicketType $type) => $type->is_free);

        if ($this->review_count > 0) {
            $this->average_rating = round(
                $this->reviews()->avg('rating') ?? 0,
                1
            );
        }

        $this->save();

        return $this;
    }
}
