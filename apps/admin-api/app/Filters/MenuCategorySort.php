<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class MenuCategorySort
{
    public static function apply(Builder $query, ?string $sortField, string $sortDirection = 'desc'): Builder
    {
        switch ($sortField) {
            case 'code':
                $query->orderBy('menu_categories.code', $sortDirection);
                break;

            case 'name':
                $query->orderBy('menu_categories.name', $sortDirection);
                break;

            case 'RestaurantName':
                $query->leftJoin('restaurants', 'restaurants.id', '=', 'menu_categories.restaurant_id')
                    ->orderBy('restaurants.name', $sortDirection)
                    ->select('menu_categories.*');
                break;

            default:
                $query->orderByDesc('menu_categories.id');
                break;
        }

        return $query;
    }
}
