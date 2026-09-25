<?php

namespace App\Enums;

enum ItineraryStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
