<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function formatToBrazilianDate($date): string
    {
        return $date ? Carbon::parse($date)->format('d/m/Y') : '';
    }
}
