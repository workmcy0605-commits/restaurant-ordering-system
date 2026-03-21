<?php

namespace App\Filters;

use App\Enums\RoleValue;
use Illuminate\Database\Eloquent\Builder;

class BranchFilter
{
    /**
     * Apply filtering to the query
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                $filters['name'] ?? null,
                fn ($q, $name) => $q->where('name', 'like', '%'.$name.'%')
            )
            ->when(
                $filters['branch_id'] ?? null,
                fn ($q, $branchId) => $q->where('code', 'like', '%'.$branchId.'%')
            )
            ->when(
                (!empty($filters['status']) && $filters['status'] !== 'ALL') ? $filters['status'] : null,
                fn ($q, $status) => $q->where('status', $status)
            )
            ->when($filters['username'] ?? null, function ($q, $username) {
                $q->whereHas('adminUser', function ($q) use ($username) {
                    $q->where('name', 'like', '%'.$username.'%')
                        ->where('role_id', RoleValue::BRANCH_ADMIN->value)
                        ->whereNull('restaurant_id');
                });
            });
    }
}
