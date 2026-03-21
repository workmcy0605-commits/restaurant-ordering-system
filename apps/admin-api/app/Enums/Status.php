<?php

namespace App\Enums;

enum Status: string
{
    case ACTIVE = 'ACT';
    case INACTIVE = 'I_ACT';

    public static function getLangCode($lang): string
    {
        return match ($lang) {
            'ACT' => self::ACTIVE->value,
            'I_ACT' => self::INACTIVE->value,
        };
    }

    public static function getOptions(bool $includeAll = true): array
    {
        $options = [];

        if ($includeAll) {
            $options[' '] = __('lang.All');
        }

        $options[self::ACTIVE->value] = __('lang.Active');
        $options[self::INACTIVE->value] = __('lang.Inactive');

        return $options;
    }

    public static function getStatusOptions(bool $includeAll = true): array
    {
        $options = [];

        if ($includeAll) {
            $options['ALL'] = __('lang.All');
        }

        $options[self::ACTIVE->value] = __('lang.Active');
        $options[self::INACTIVE->value] = __('lang.Inactive');

        return $options;
    }
}
