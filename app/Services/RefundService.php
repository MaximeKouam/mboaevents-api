<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    private const FULL_REFUND_WINDOW_DAYS = 7;

    private const LATE_REFUND_RATE = 0.85;

    public function cancel(Order $order): Order
    {
        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            throw ValidationException::withMessages([
                'order' => 'Cette réservation est déjà annulée.',
            ]);
        }

        return DB::transaction(function () use ($order) {
            $daysBeforeEvent = now()->diffInDays($order->event->starts_at, false);
            $isWithinFullRefundWindow = $daysBeforeEvent >= self::FULL_REFUND_WINDOW_DAYS;

            $refundAmount = $order->total_amount === 0
                ? 0
                : (int) round($order->total_amount * ($isWithinFullRefundWindow ? 1 : self::LATE_REFUND_RATE));

            if ($refundAmount > 0 && $order->user) {
                $wallet = $order->user->wallet ?? $order->user->wallet()->create(['balance' => 0]);
                $wallet->credit(
                    $refundAmount,
                    'refund',
                    $order,
                    'Remboursement de la réservation '.$order->reference.
                        ($isWithinFullRefundWindow ? '' : ' (85 % — annulation tardive)')
                );
            }

            $ticketCount = 0;

            foreach ($order->items as $item) {
                $item->ticketType->decrementSold($item->quantity);
                $item->tickets()->update(['status' => 'refunded']);
                $ticketCount += $item->quantity;
            }

            $order->event()->decrement('registration_count', $ticketCount);

            $order->update([
                // Une commande invité n'a pas de wallet : le remboursement
                // doit être traité manuellement tant qu'il n'y a pas de compte.
                'status' => ! $order->user
                    ? 'cancelled'
                    : ($refundAmount === $order->total_amount ? 'refunded' : 'partially_refunded'),
                'cancelled_at' => now(),
                'refund_amount' => $order->user ? $refundAmount : null,
            ]);

            return $order->fresh();
        });
    }
}
