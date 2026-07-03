<?php

namespace App\Enums;

enum ContributionType: string
{
    case NewPoi = 'new_poi';
    case Edit = 'edit';
    case Photo = 'photo';
    case Report = 'report';
}
