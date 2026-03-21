<?php

namespace App\Enums;

enum SelectIngredient: string
{
    case ALL = 'ALL';
    case CONTAIN_EGG = 'CONTAIN_EGG';
    case CONTAIN_DAIRY = 'CONTAIN_DAIRY';
    case CONTAIN_ONION_GARLIC = 'CONTAIN_ONION_GARLIC';
    case CONTAIN_CHILI = 'CONTAIN_CHILI';

    public static function getOptions(bool $includeAll = true): array
    {
        $options = [];

        if ($includeAll) {
            $options[self::ALL->value] = __('lang.All');
        }

        $options[self::CONTAIN_EGG->value] = __('lang.ContainEgg');
        $options[self::CONTAIN_DAIRY->value] = __('lang.ContainDairy');
        $options[self::CONTAIN_ONION_GARLIC->value] = __('lang.ContainsOnionAndGarlic');
        $options[self::CONTAIN_CHILI->value] = __('lang.ContainsChili');

        return $options;
    }
}
