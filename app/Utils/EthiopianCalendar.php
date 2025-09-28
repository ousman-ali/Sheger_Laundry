<?php

namespace App\Utils;

use Andegna\DateTimeFactory;
use Carbon\Carbon;

class EthiopianCalendar
{
    /**
     * Convert a Gregorian Carbon/DateTime to Ethiopian date string
     */
    public static function toEthiopian(?string $gregorianDate): ?string
    {
        if (empty($gregorianDate)) {
            return null;
        }

        $carbon = Carbon::parse($gregorianDate);

        // Convert using Andegna
        $ethiopian = DateTimeFactory::fromDateTime($carbon);

        // Format like: 2016-01-02 14:30 (Ethiopian)
        return sprintf(
            '%04d-%02d-%02d %02d:%02d',
            $ethiopian->getYear(),
            $ethiopian->getMonth(),
            $ethiopian->getDay(),
            $ethiopian->getHour(),
            $ethiopian->getMinute()
        );
    }
}
