<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class MenuItemFilter
{
    public static function apply(Builder $query, array $filters): Builder
    {
        if (! empty($filters['code'])) {
            $query->where('menu_items.code', 'like', '%'.$filters['code'].'%');
        }

        if (! empty($filters['name'])) {
            $query->where('menu_items.name', 'like', '%'.$filters['name'].'%');
        }

        if (! empty($filters['menu_name'])) {
            $query->whereHas('menuCategory', function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['menu_name'].'%');
            });
        }

        if (! empty($filters['restaurant_id'])) {
            $query->where('restaurant_id', $filters['restaurant_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
