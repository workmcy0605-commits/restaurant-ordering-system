<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class UserFilter
{
    public static function apply(Builder $query, array $filters, ?array $allowedRoleIds = null, ?int $currentCompanyId = null, ?int $currentBranchId = null, ?int $currentRestaurantId = null): Builder
    {
        if (! empty($filters['code'])) {
            $query->where('code', 'like', '%' . $filters['code'] . '%');
        }

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'ALL') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['roleType'])) {
            $query->where('role_id', $filters['roleType']);
        }

        if ($allowedRoleIds) {
            $query->whereIn('role_id', $allowedRoleIds);
        }

        if ($currentCompanyId) {
            $query->where('company_id', $currentCompanyId);
        }

        if ($currentBranchId) {
            $query->where('branch_id', $currentBranchId);
        }

        if ($currentRestaurantId) {
            $query->where('restaurant_id', $currentRestaurantId);
        }

        return $query;
    }
}
