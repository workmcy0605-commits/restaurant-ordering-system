<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class UserSort
{
    public static function apply(Builder $query, ?string $sortField, string $sortDirection = 'desc'): Builder
    {
        switch ($sortField) {
            case 'code':
                $query->orderBy('code', $sortDirection);
                break;

            case 'name':
                $query->orderBy('name', $sortDirection);
                break;

            case 'dname':
                $query->orderBy('nickname', $sortDirection);
                break;

            case 'departmentname':
                $query->leftJoin('branches', 'branches.id', '=', 'users.branch_id')
                    ->orderBy('branches.name', $sortDirection)
                    ->select('users.*');
                break;

            default:
                $query->orderByDesc('id');
                break;
        }

        return $query;
    }
}
