<?php

namespace App\Support;

use App\Models\SeatType;
use App\Models\TicketType;

class PriceCalculator
{
    public const MODE_AMOUNT = 'amount';
    public const MODE_PERCENT = 'percent';

    public static function mode(?string $mode): string
    {
        return $mode === self::MODE_PERCENT ? self::MODE_PERCENT : self::MODE_AMOUNT;
    }

    public static function apply(float $value, ?string $mode, float $base): float
    {
        if (self::mode($mode) === self::MODE_PERCENT) {
            return round($base * $value / 100, 2);
        }

        return round($value, 2);
    }

    /**
     * @param  iterable<TicketType>  $ticketTypes
     */
    public static function ticketReferenceAmount(iterable $ticketTypes): float
    {
        foreach ($ticketTypes as $type) {
            if (self::mode($type->price_mode ?? null) === self::MODE_AMOUNT) {
                return (float) $type->price;
            }
        }

        return 0.0;
    }

    public static function ticketUnit(?TicketType $type, float $reference): float
    {
        if (!$type) {
            return 0.0;
        }

        return self::apply((float) $type->price, $type->price_mode ?? null, $reference);
    }

    public static function seatUnit(?SeatType $type, float $averageTicket): float
    {
        if (!$type) {
            return 0.0;
        }

        return self::apply((float) $type->price, $type->price_mode ?? null, $averageTicket);
    }

    public static function total(float $ticketsTotal, float $seatsTotal): float
    {
        return max(0, round($ticketsTotal + $seatsTotal, 2));
    }
}
