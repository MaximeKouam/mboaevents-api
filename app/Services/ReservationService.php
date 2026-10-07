<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(private readonly TicketQrService $qrService)
    {
    }

    /**
     * @param  array<int, array{ticket_type_id: int, quantity: int}>  $items
     * @param  array{name: string, email: string, phone: string}|null  $guest
     */
    public function reserve(?User $user, Event $event, array $items, ?array $guest = null): Order
    {
        if (! $user && ! $guest) {
            throw ValidationException::withMessages([
                'guest_email' => 'Connecte-toi ou renseigne tes coordonnées pour réserver.',
            ]);
        }

        return DB::transaction(function () use ($user, $event, $items, $guest) {
            $order = Order::create([
                'reference' => 'MBE-'.Str::upper(Str::random(10)),
                'user_id' => $user?->id,
                'guest_name' => $user ? null : $guest['name'],
                'guest_email' => $user ? null : $guest['email'],
                'guest_phone' => $user ? null : $guest['phone'],
                'event_id' => $event->id,
                'status' => 'pending',
                'total_amount' => 0,
            ]);

            $total = 0;
            $ticketCount = 0;

            foreach ($items as $item) {
                /** @var TicketType $ticketType */
                $ticketType = TicketType::where('event_id', $event->id)
                    ->lockForUpdate()
                    ->findOrFail($item['ticket_type_id']);

                if (! $ticketType->isAvailable() || $ticketType->remaining() < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'ticket_type_id' => "Il ne reste plus assez de places pour \"{$ticketType->name}\".",
                    ]);
                }

                $unitPrice = $ticketType->is_free ? 0 : $ticketType->price;

                $orderItem = $order->items()->create([
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                ]);

                $ticketType->incrementSold($item['quantity']);
                $total += $unitPrice * $item['quantity'];
                $ticketCount += $item['quantity'];

                for ($i = 0; $i < $item['quantity']; $i++) {
                    $ticket = $orderItem->tickets()->create([
                        'ticket_type_id' => $ticketType->id,
                        'event_id' => $event->id,
                        'user_id' => $user?->id,
                        'status' => 'valid',
                        'qr_token' => '',
                    ]);
                    $ticket->update(['qr_token' => $this->qrService->generate($ticket)]);
                }
            }

            $order->update([
                'total_amount' => $total,
                'status' => $total === 0 ? 'paid' : 'pending',
            ]);

            $event->increment('registration_count', $ticketCount);

            return $order->fresh(['items.ticketType', 'items.tickets']);
        });
    }
}
