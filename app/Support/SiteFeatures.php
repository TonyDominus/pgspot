<?php

namespace App\Support;

use App\Models\AppSetting;

class SiteFeatures
{
    public static function eventsPublicEnabled(): bool
    {
        return (bool) AppSetting::getValue('features.events_public', true);
    }
}
