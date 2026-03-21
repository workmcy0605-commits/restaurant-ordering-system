<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class MenuItemSort
{
    public static function apply(Builder $query, ?string $sortField, string $sortDirection = 'desc'): Builder
    {
        switch ($sortField) {
            case 'code':
                $query->orderBy('menu_items.code', $sortDirection);
                break;

            case 'name':
                $query->orderBy('menu_items.name', $sortDirection);
                break;

            case 'mname': // MenuCategory.name
                $query->leftJoin('menu_categories', 'menu_categories.id', '=', 'menu_items.menu_category_id')
                    ->orderBy('menu_categories.name', $sortDirection)
                    ->select('menu_items.*');
                break;

            case 'rname': // Restaurant.name
                $query->leftJoin('menu_categories', 'menu_categories.id', '=', 'menu_items.menu_category_id')
                    ->leftJoin('restaurants', 'restaurants.id', '=', 'menu_categories.restaurant_id')
                    ->orderBy('restaurants.name', $sortDirection)
                    ->select('menu_items.*');
                break;

            default:
                $query->orderByDesc('menu_items.id');
                break;
        }

        return $query;
    }
}
