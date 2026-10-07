<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Crypt;

/**
 * Le QR code n'encode jamais l'id du billet en clair : on chiffre un
 * payload (via APP_KEY) que seul le backend peut déchiffrer. Un billet
 * copié/photographié ne révèle donc rien d'exploitable, et la vérification
 * se fait toujours côté serveur au moment du scan.
 */
class TicketQrService
{
    public function generate(Ticket $ticket): string
    {
        $payload = [
            'ticket_id' => $ticket->id,
            'code' => $ticket->code,
            'event_id' => $ticket->event_id,
            'issued_at' => now()->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    public function decode(string $token): ?array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }
}
