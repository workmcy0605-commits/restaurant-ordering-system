<?php

namespace App\Filters;

use App\Enums\RoleValue;
use DB;
use Illuminate\Database\Eloquent\Builder;

class BranchSort
{
    /**
     * Apply sorting to the Branch query
     */
    public static function apply(Builder $query, ?string $sortField, string $sortDirection = 'asc', array $filters = []): Builder
    {

        $query = $query->select('branches.*');

        switch ($sortField) {

            case 'code':
            case 'name':
                return $query->orderBy("branches.$sortField", $sortDirection);

            case 'cname':

                $query->leftJoin('companies as c', 'c.id', '=', 'branches.company_id')
                    ->orderBy('c.name', $sortDirection);

                if (! empty($filters['company_name'])) {
                    $query->where('c.name', 'like', '%'.$filters['company_name'].'%');
                }

                return $query;

            case 'uname':
                $query->addSelect([
                    'first_user_name' => DB::table('users')
                        ->select('name')
                        ->whereColumn('branch_id', 'branches.id')
                        ->where('role_id', RoleValue::BRANCH_ADMIN->value)
                        ->limit(1),
                ])
                    ->orderBy('first_user_name', $sortDirection);

                if (! empty($filters['username'])) {
                    $query->whereExists(function ($q) use ($filters) {
                        $q->selectRaw(1)
                            ->from('users')
                            ->whereColumn('users.branch_id', 'branches.id')
                            ->where('role_id', RoleValue::BRANCH_ADMIN->value)
                            ->where('users.name', 'like', '%'.$filters['username'].'%');
                    });
                }

                return $query;

            default:
                return $query->orderByDesc('branches.id');
        }
    }
}
