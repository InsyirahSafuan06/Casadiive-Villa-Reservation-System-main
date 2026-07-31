<?php
declare(strict_types=1);

/** Turns a snake_case enum value like 'checked_in' into 'Checked In' for display. */
function format_status(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

/**
 * Splits a stay into weekday vs. weekend nights (Friday & Saturday count as
 * weekend) and prices each night accordingly. $weekendPrice falls back to
 * $weekdayPrice when null. Public holiday pricing isn't applied here — that
 * would need a holiday calendar this system doesn't have; price_holiday on
 * `accommodation` is display-only for now.
 */
function compute_stay_price(float $weekdayPrice, ?float $weekendPrice, DateTime $checkIn, DateTime $checkOut): array
{
    $weekendPrice ??= $weekdayPrice;
    $weekdayNights = 0;
    $weekendNights = 0;

    $cursor = clone $checkIn;
    while ($cursor < $checkOut) {
        $isWeekend = in_array((int) $cursor->format('N'), [5, 6], true); // Friday, Saturday
        $isWeekend ? $weekendNights++ : $weekdayNights++;
        $cursor->modify('+1 day');
    }

    return [
        'weekday_nights' => $weekdayNights,
        'weekend_nights' => $weekendNights,
        'total' => round($weekdayNights * $weekdayPrice + $weekendNights * $weekendPrice, 2),
    ];
}
