<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    use HasFactory;

    /* ================================================================== */
    /*  FILLABLE                                                          */
    /* ================================================================== */

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'tag',
        'position',
        'is_active',
        'is_free',
        'price',
        'currency',
        'perks',
        'quantity_total',
        'quantity_sold',
        'max_per_order',
        'sales_start_at',
        'sales_end_at',
    ];

    /* ================================================================== */
    /*  CASTS                                                             */
    /* ================================================================== */

    protected $casts = [
        'perks' => 'array',
        'is_free' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'integer',
        'quantity_total' => 'integer',
        'quantity_sold' => 'integer',
        'max_per_order' => 'integer',
        'position' => 'integer',
        'sales_start_at' => 'datetime',
        'sales_end_at' => 'datetime',
    ];

    /* ================================================================== */
    /*  RELATIONS                                                         */
    /* ================================================================== */

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /* ================================================================== */
    /*  SCOPES                                                            */
    /* ================================================================== */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('price');
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_free', true);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_free', false);
    }

    public function scopeForEvent(Builder $query, int $eventId): Builder
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Types dont la fenêtre de vente est ouverte (ou pas de fenêtre du tout).
     */
    public function scopeWithinSalesWindow(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('sales_start_at')
                  ->orWhere('sales_start_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('sales_end_at')
                  ->orWhere('sales_end_at', '>=', $now);
            });
    }

    /* ================================================================== */
    /*  HELPERS — Stock                                                   */
    /* ================================================================== */

    public function remaining(): int
    {
        return max(0, $this->quantity_total - $this->quantity_sold);
    }

    public function isSoldOut(): bool
    {
        return $this->remaining() <= 0;
    }

    public function isNearlySoldOut(int $threshold = 20): bool
    {
        return $this->remaining() > 0 && $this->remaining() <= $threshold;
    }

    public function soldPercentage(): float
    {
        if ($this->quantity_total <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->quantity_sold / $this->quantity_total) * 100, 1));
    }

    /* ================================================================== */
    /*  HELPERS — Disponibilité                                           */
    /* ================================================================== */

    public function isWithinSalesWindow(): bool
    {
        $now = now();
        $startsOk = ! $this->sales_start_at || $this->sales_start_at->lte($now);
        $endsOk = ! $this->sales_end_at || $this->sales_end_at->gte($now);

        return $startsOk && $endsOk;
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->isWithinSalesWindow()
            && $this->remaining() > 0;
    }

    public function hasStartedSales(): bool
    {
        return ! $this->sales_start_at || $this->sales_start_at->isPast();
    }

    public function hasEndedSales(): bool
    {
        return $this->sales_end_at && $this->sales_end_at->isPast();
    }

    /* ================================================================== */
    /*  HELPERS — Prix                                                    */
    /* ================================================================== */

    public function priceLabel(): string
    {
        if ($this->is_free || $this->price === 0) {
            return 'Gratuit';
        }

        return number_format($this->price, 0, ',', ' ').' '.$this->currency;
    }

    /**
     * Retourne le prix en unité mineure si un jour tu passes à une devise
     * avec sous-unité (EUR, USD). Ici XAF n'en a pas, donc renvoie price.
     */
    public function priceInMinorUnits(): int
    {
        return $this->price;
    }

    /* ================================================================== */
    /*  HELPERS — Panier                                                  */
    /* ================================================================== */

    /**
     * Combien d'unités on peut encore mettre dans un panier pour cette
     * quantité demandée, en tenant compte du stock ET du max par commande.
     */
    public function maxOrderableQuantity(int $requested = 1): int
    {
        $byStock = $this->remaining();
        $byLimit = $this->max_per_order ?? PHP_INT_MAX;

        return (int) max(0, min($byStock, $byLimit, $requested));
    }

    /* ================================================================== */
    /*  MUTATIONS                                                         */
    /* ================================================================== */

    /**
     * Incrémente atomiquement le compteur de ventes.
     * À appeler depuis le service de commande, dans une transaction.
     */
    public function incrementSold(int $quantity = 1): self
    {
        $this->increment('quantity_sold', $quantity);

        return $this->refresh();
    }

    /**
     * Décrémente (remboursement, annulation).
     */
    public function decrementSold(int $quantity = 1): self
    {
        $this->decrement('quantity_sold', min($quantity, $this->quantity_sold));

        return $this->refresh();
    }
}
