<?php

namespace App\Filters;

use App\Enums\RoleValue;
use Illuminate\Database\Eloquent\Builder;

class CompanyFilter
{
    /**
     * Apply filtering to the query
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (! empty($filters['company_id'])) {
            $query->where('code', 'like', '%' . $filters['company_id'] . '%');
        }

        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['uname'])) {
            $query->whereHas('username', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['uname'] . '%')
                    ->where('role_id', RoleValue::COMPANY_ADMIN->value)
                    ->whereNull('branch_id')
                    ->whereNull('restaurant_id');
            });
        }

        return $query;
    }
}
