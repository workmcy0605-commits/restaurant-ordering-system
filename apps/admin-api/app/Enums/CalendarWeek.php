<?php

namespace App\Enums;

enum CalendarWeek: string
{
    case SUNDAY = 'Sunday';
    case MONDAY = 'Monday';
    case TUESDAY = 'Tuesday';
    case WEDNESDAY = 'Wednesday';
    case THURSDAY = 'Thursday';
    case FRIDAY = 'Friday';
    case SATURDAY = 'Saturday';

    public function label(): string
    {
        return match ($this) {
            self::SUNDAY => __('lang.Sun'),
            self::MONDAY => __('lang.Mon'),
            self::TUESDAY => __('lang.Tue'),
            self::WEDNESDAY => __('lang.Wed'),
            self::THURSDAY => __('lang.Thu'),
            self::FRIDAY => __('lang.Fri'),
            self::SATURDAY => __('lang.Sat'),
        };
    }
}
