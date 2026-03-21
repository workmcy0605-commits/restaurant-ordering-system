<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class CompanySort
{
    /**
     * Apply sorting to the query
     */
    public static function apply(Builder $query, ?string $sortField, string $sortDirection = 'asc', array $filters = []): Builder
    {
        $query = $query->select('companies.*');

        switch ($sortField) {
            case 'code':
            case 'name':
                return $query->orderBy($sortField, $sortDirection);

            case 'uname':
                $query->leftJoin('users as u', function ($join) {
                    $join->on('u.company_id', '=', 'companies.id')
                        ->whereNull('u.branch_id')
                        ->whereNull('u.restaurant_id');
                })
                    ->addSelect('u.name as uname');

                $username = $filters['uname'] ?? $filters['username'] ?? null;

                if (! empty($username)) {
                    $query->where('u.name', 'like', '%'.$username.'%');
                }

                return $query->orderBy('uname', $sortDirection);

            default:
                return $query->orderByDesc('companies.id');
        }
    }
}
