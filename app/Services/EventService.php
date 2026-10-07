<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class EventService
{
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Event::query()
            ->published()
            ->upcoming()
            ->with(['category', 'city', 'organizerPage', 'ticketTypes']);

        if ($cityId = $request->integer('city_id')) {
            $query->inCity($cityId);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->inCategory($categoryId);
        }

        if ($mode = $request->string('mode')->toString()) {
            $query->where('mode', $mode);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtre prix
        if ($price = $request->string('price')->toString()) {
            match ($price) {
                'free'    => $query->where('is_free', true),
                'lt5k'    => $query->where('is_free', false)->where('lowest_price', '<', 5000),
                '5k-10k'  => $query->whereBetween('lowest_price', [5000, 10000]),
                'gt10k'   => $query->where('lowest_price', '>', 10000),
                default   => null,
            };
        }

        // Tri
        match ($request->string('sort')->toString()) {
            'price-asc'  => $query->orderBy('lowest_price'),
            'price-desc' => $query->orderByDesc('lowest_price'),
            'popular'    => $query->orderByDesc('registration_count'),
            default      => $query->orderBy('starts_at'),
        };

        if ($request->filled('lat') && $request->filled('lng')) {
            $query->nearby((float) $request->input('lat'), (float) $request->input('lng'));
        }

        return $query->paginate(12);
    }
}
