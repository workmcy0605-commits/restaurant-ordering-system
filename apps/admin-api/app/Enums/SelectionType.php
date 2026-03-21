<?php

namespace App\Enums;

enum SelectionType: string
{
    case PERIOD = 'PERIOD';
    case DAY = 'DAY';
    case MEALTIME = 'MEALTIME';

    public static function getOptions(bool $includeAll = true): array
    {
        $options = [];

        if ($includeAll) {
            $options[' '] = __('lang.All');
        }

        $options[self::PERIOD->value] = __('lang.PERIOD');
        $options[self::DAY->value] = __('lang.DAY');
        $options[self::MEALTIME->value] = __('lang.MEALTIME');

        return $options;
    }
}
