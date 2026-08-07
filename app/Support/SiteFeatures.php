<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Event;

class SiteFeatures
{
    public static function eventsPublicEnabled(): bool
    {
        return (bool) AppSetting::getValue('features.events_public', true);
    }

    /** Feature flag on AND almeno un evento listabile. */
    public static function eventsNavVisible(): bool
    {
        if (! self::eventsPublicEnabled()) {
            return false;
        }

        return Event::query()->listed()->exists();
    }
}
