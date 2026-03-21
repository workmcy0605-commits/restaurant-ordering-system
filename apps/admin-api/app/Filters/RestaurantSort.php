<?php

namespace App\Filters;

use App\Enums\RoleValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RestaurantSort
{
    /**
     * Apply sorting to the query
     */
    public static function apply(Builder $query, ?string $sortField, string $sortDirection = 'asc', array $filters = []): Builder
    {
        $query = $query->select('restaurants.*');

        switch ($sortField) {
            case 'name':
            case 'code':
                return $query->orderBy("restaurants.$sortField", $sortDirection);

            case 'uname':

                $query->addSelect([
                    'first_user_name' => DB::table('users')
                        ->select('name')
                        ->whereColumn('restaurant_id', 'restaurants.id')
                        ->where('role_id', RoleValue::RESTAURANT_ADMIN->value)
                        ->limit(1),
                ])
                    ->orderBy('first_user_name', $sortDirection);

                if (! empty($filters['uname'])) {
                    $query->whereExists(function ($q) use ($filters) {
                        $q->selectRaw(1)
                            ->from('users')
                            ->whereColumn('users.restaurant_id', 'restaurants.id')
                            ->where('role_id', RoleValue::RESTAURANT_ADMIN->value)
                            ->where('users.name', 'like', '%'.$filters['uname'].'%');
                    });
                }

                return $query;

            default:
                return $query->orderByDesc('restaurants.id');
        }
    }
}
