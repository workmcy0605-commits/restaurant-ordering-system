<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class MenuCategoryFilter
{
    public static function apply(Builder $query, array $filters): Builder
    {
        if (! empty($filters['code'])) {
            $query->where('menu_categories.code', 'like', '%'.$filters['code'].'%');
        }

        if (! empty($filters['name'])) {
            $query->where('menu_categories.name', 'like', '%'.$filters['name'].'%');
        }

        if (! empty($filters['restaurant_id'])) {
            $query->where('menu_categories.restaurant_id', $filters['restaurant_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('menu_categories.status', $filters['status']);
        }

        return $query;
    }
}
