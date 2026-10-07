<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventDetailResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private readonly EventService $events)
    {
    }

    public function index(Request $request)
    {
        return EventResource::collection($this->events->list($request));
    }

public function show(Event $event)
{
    abort_unless($event->status === 'published', 404);

$event->load([
    'category',
    'city',
    'organizerPage',
    'ticketTypes',
    'programItems',
    'speakers',
    'reviews' => fn ($q) => $q->latest(), 
    'reviews.user',
]);
    return new EventDetailResource($event);
}}
