<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\MenuItemAddOn;
use App\Models\MenuItemAddOnOption;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MenuItemAddonService
{
    public function storeAddons(MenuItem $menuItem, array $addons, int $userId): void
    {
        DB::transaction(function () use ($menuItem, $addons, $userId) {

            $addOnRows = [];
            $optionRows = [];

            foreach ($addons as $addonData) {

                $fields = ['name', 'type', 'min', 'max', 'add_on_required'];

                $filtered = array_intersect_key($addonData, array_flip($fields));

                if (empty(array_filter($filtered))) {
                    continue;
                }
                $addOnRows[] = [
                    'menu_item_id' => $menuItem->id,
                    'name' => $addonData['name'],
                    'type' => $addonData['type'] ?? null,
                    'min' => $addonData['min'] ?? 0,
                    'max' => $addonData['max'] ?? 0,
                    'add_on_required' => $addonData['required'] ?? 'no',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (empty($addOnRows)) {
                return;
            }

            MenuItemAddOn::insert($addOnRows);

            $insertedAddOns = MenuItemAddOn::query()
                ->where('menu_item_id', $menuItem->id)
                ->orderBy('id')
                ->get();

            try {
                foreach ($addons as $index => $addonData) {
                    $fields = ['optionname', 'type', 'min', 'max', 'add_on_required'];

                    $filtered = array_intersect_key($addonData, array_flip($fields));

                    if (empty(array_filter($filtered))) {
                        continue;
                    }

                    $addOnModel = $insertedAddOns[$index];

                    $options = $addonData['options'] ?? [];

                    foreach ($options as $opt) {

                        if (empty($opt['optionname']) || ! isset($opt['surcharge'])) {
                            throw new \Exception(__('lang.AddOnOptionError').': '.$addonData['optionname']);
                        }

                        $optionRows[] = [
                            'menu_item_add_on_id' => $addOnModel->id,
                            'name' => $opt['optionname'],
                            'surcharge' => $opt['surcharge'],
                            'created_by' => $userId,
                            'updated_by' => $userId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (! empty($optionRows)) {
                    MenuItemAddOnOption::insert($optionRows);
                }
            } catch (\Exception $e) {

                throw new \Exception(__('lang.AddOnOptionError').' : '.$addonData['name']);
            }
        });
    }

    public function syncAddons(MenuItem $menuItem, array $addonsData, int $userId): void
    {
        DB::transaction(function () use ($menuItem, $addonsData, $userId) {
            $addonsToUpsert = [];
            $optionsToUpsert = [];
            $addonIdsToKeep = [];
            $optionIdsToKeep = [];

            foreach ($addonsData as $addon) {
                $addonId = Arr::get($addon, 'id');

                $addonsToUpsert[] = [
                    'id' => $addonId,
                    'menu_item_id' => $menuItem->id,
                    'name' => Arr::get($addon, 'name'),
                    'type' => Arr::get($addon, 'type'),
                    'min' => Arr::get($addon, 'min', 0),
                    'max' => Arr::get($addon, 'max', 0),
                    'add_on_required' => Arr::get($addon, 'required', 'no'),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ];
            }

            MenuItemAddOn::upsert(
                $addonsToUpsert,
                ['id'],
                ['name', 'type', 'min', 'max', 'add_on_required', 'updated_by']
            );

            $updatedAddons = MenuItemAddOn::where('menu_item_id', $menuItem->id)->get()->keyBy('name');

            foreach ($addonsData as $addon) {
                $addonName = Arr::get($addon, 'name');
                $addonId = Arr::get($addon, 'id') ?? ($updatedAddons[$addonName]->id ?? null);
                if (! $addonId) {
                    throw new \Exception('Addon ID not found for: '.$addonName);
                }

                $addonIdsToKeep[] = $addonId;

                MenuItemAddOnOption::where('menu_item_add_on_id', $addonId)->get()->keyBy('id');

                foreach (Arr::get($addon, 'options', []) as $opt) {
                    $optId = Arr::get($opt, 'id');
                    $optName = Arr::get($opt, 'optionname');
                    $optSurcharge = Arr::get($opt, 'surcharge');

                    $optionsToUpsert[] = [
                        'id' => $optId,
                        'menu_item_add_on_id' => $addonId,
                        'name' => $optName,
                        'surcharge' => $optSurcharge,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ];

                    if ($optId) {
                        $optionIdsToKeep[] = $optId;
                    }
                }
            }

            MenuItemAddOn::where('menu_item_id', $menuItem->id)
                ->whereNotIn('id', $addonIdsToKeep)
                ->delete();

            MenuItemAddOnOption::whereNotIn('id', $optionIdsToKeep)
                ->whereIn('menu_item_add_on_id', $addonIdsToKeep)
                ->delete();

            if (! empty($optionsToUpsert)) {
                MenuItemAddOnOption::upsert(
                    $optionsToUpsert,
                    ['id'],
                    ['menu_item_add_on_id', 'name', 'surcharge', 'updated_by']
                );
            }
        });
    }
}
