<?php

namespace App\Enums;

use Auth;

enum RoleValue: string
{
    case SUPER_ADMIN = '1';
    case SYSTEM_ADMIN = '2';
    case COMPANY_ADMIN = '3';
    case BRANCH_ADMIN = '4';
    case RESTAURANT_ADMIN = '5';
    case OPERATOR = '6';
    case STAFF = '7';
    case DRIVER = '8';

    public static function getOptions(bool $includeAll = true): array
    {
        $roleId = Auth::user()?->role_id;
        $options = [];

        if ($includeAll) {
            $options[' '] = __('lang.All');
        }

        $options[self::SUPER_ADMIN->value] = __('lang.SuperAdmin');
        $options[self::SYSTEM_ADMIN->value] = __('lang.SystemAdmin');
        $options[self::COMPANY_ADMIN->value] = __('lang.CompanyAdmin');
        $options[self::BRANCH_ADMIN->value] = __('lang.BranchAdmin');
        $options[self::RESTAURANT_ADMIN->value] = __('lang.RestaurantAdmin');
        $options[self::OPERATOR->value] = __('lang.Operator');
        $options[self::DRIVER->value] = __('lang.Driver');

        // Filter: show only roles >= current role_id
        $options = array_filter(
            $options,
            fn ($label, $key) => (int) $key >= (int) $roleId,
            ARRAY_FILTER_USE_BOTH
        );

        return $options;
    }

    public static function getRoleOptions(bool $includeAll = true): array
    {
        $options = [];

        if ($includeAll) {
            $options[' '] = __('lang.All');
        }

        $options[self::SUPER_ADMIN->value] = __('lang.SuperAdmin');
        $options[self::SYSTEM_ADMIN->value] = __('lang.SystemAdmin');
        $options[self::COMPANY_ADMIN->value] = __('lang.CompanyAdmin');
        $options[self::BRANCH_ADMIN->value] = __('lang.BranchAdmin');
        $options[self::RESTAURANT_ADMIN->value] = __('lang.RestaurantAdmin');
        $options[self::OPERATOR->value] = __('lang.Operator');

        return $options;
    }

    public static function getUserOptions(bool $includeAll = true): array
    {
        $options = [];

        if ($includeAll) {
            $options[' '] = __('lang.All');
        }

        $options[self::OPERATOR->value] = __('lang.Operator');
        $options[self::STAFF->value] = __('lang.Staff');
        $options[self::DRIVER->value] = __('lang.Driver');

        return $options;
    }

    public function getRoleName(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::SYSTEM_ADMIN => 'System Admin',
            self::COMPANY_ADMIN => 'Company Admin',
            self::BRANCH_ADMIN => 'Branch Admin',
            self::RESTAURANT_ADMIN => 'Restaurant Admin',
            self::OPERATOR => 'Operator',
            self::STAFF => 'Staff',
            self::DRIVER => 'Driver',
        };
    }
}
