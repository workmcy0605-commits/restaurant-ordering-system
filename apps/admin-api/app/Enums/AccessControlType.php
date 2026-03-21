<?php

namespace App\Enums;

enum AccessControlType: string
{
    case RATING = 'RATING';
    case COMMENTS = 'COMMENTS';

    public static function getOptions(bool $includeAll = true): array
    {
        $options = [];

        if ($includeAll) {
            $options[' '] = __('lang.All');
        }

        $options[self::RATING->value] = __('lang.RATING');
        $options[self::COMMENTS->value] = __('lang.COMMENTS');

        return $options;
    }
}
