<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketQrService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function __construct(private readonly TicketQrService $qrService)
    {
    }

    public function checkIn(Request $request)
    {
        $request->validate(['token' => ['required', 'string']]);

        $payload = $this->qrService->decode($request->string('token'));

        if (! $payload) {
            throw ValidationException::withMessages(['token' => 'QR code invalide.']);
        }

        $ticket = Ticket::find($payload['ticket_id'] ?? null);

        if (! $ticket || $ticket->code !== ($payload['code'] ?? null)) {
            throw ValidationException::withMessages(['token' => 'Billet introuvable.']);
        }

        if ($ticket->status !== 'valid') {
            throw ValidationException::withMessages(['token' => "Ce billet est déjà {$ticket->status}."]);
        }

        $ticket->update([
            'status' => 'used',
            'used_at' => now(),
            'checked_in_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Billet validé.',
            'ticket' => [
                'code' => $ticket->code,
                'holder' => $ticket->user->name,
                'event' => $ticket->event->title,
            ],
        ]);
    }
}
