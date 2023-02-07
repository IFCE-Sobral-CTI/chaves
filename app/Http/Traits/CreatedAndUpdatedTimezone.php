<?php

namespace App\Http\Traits;

use Carbon\Carbon;

trait CreatedAndUpdatedTimezone
{
    /**
     * Returns the date in the defined timezone
     */
    public function getCreatedAtAttribute(string $date): string
    {
        return Carbon::parse($date)->setTimezone(env('APP_TIMEZONE'))->format('d/m/Y H:i:s');
    }

    /**
     * Returns the date in the defined timezone
     */
    public function getUpdatedAtAttribute(string $date): string
    {
        return Carbon::parse($date)->setTimezone(env('APP_TIMEZONE'))->format('d/m/Y H:i:s');
    }
}
