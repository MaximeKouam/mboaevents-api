<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Services\RefundService;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly RefundService $refunds,
    ) {
    }

    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with(['event', 'tickets'])
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function store(StoreReservationRequest $request, Event $event)
    {
        $user = $request->user();

        $guest = $user ? null : [
            'name' => $request->validated('guest_name'),
            'email' => $request->validated('guest_email'),
            'phone' => $request->validated('guest_phone'),
        ];

        $order = $this->reservations->reserve($user, $event, $request->validated('items'), $guest);

        return new OrderResource($order->load(['event', 'items.ticketType', 'items.tickets']));
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order = $this->refunds->cancel($order);

        return new OrderResource($order->load(['event', 'items.tickets']));
    }
}
