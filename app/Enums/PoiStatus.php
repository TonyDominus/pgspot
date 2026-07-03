<?php

namespace App\Enums;

enum PoiStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Archived = 'archived';
}
