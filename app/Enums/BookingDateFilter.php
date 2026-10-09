<?php

namespace App\Enums;

enum BookingDateFilter: string
{
    case ALL = 'all';
    case TODAY = 'today';
    case UPCOMING = 'upcoming';
    case PAST = 'past';
}
