<?php

namespace App\Http\Controllers\Api\V1\MenuManagement;

use App\Enums\SelectIngredient;
use App\Enums\SelectionType;
use App\Enums\Status;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\MenuItemRequest;
use App\Models\ImportFile;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemAddOn;
use App\Models\MenuItemAddOnOption;
use App\Models\Restaurant;
use App\Models\Selection;
use App\Services\MenuItemAddonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuItemController extends ApiController
{
    public function __construct(private readonly MenuItemAddonService $addonService) {}

    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);
        $filters = [
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'menu_name' => $request->input('menu_name'),
            'restaurant_id' => $request->input('restaurant_id'),
            'status' => $request->input('status'),
        ];

        $query = MenuItem::query()->with(['addons.options', 'menuCategory.restaurant', 'mealTime', 'createdBy', 'updatedBy']);
        $query = \App\Filters\MenuItemFilter::apply($query, $filters);
        $query = \App\Filters\MenuItemSort::apply($query, $request->input('sort', 'id'), $request->input('direction', 'desc'));

        return $this->paginated($query->paginate($itemsPerPage)->withQueryString(), 'Menu items retrieved successfully.');
    }

    public function options()
    {
        $menuCategories = MenuCategory::query()
            ->where('status', Status::ACTIVE->value)
            ->orderByDesc('id')
            ->get(['id', 'name']);

        $mealTimes = Selection::query()
            ->where('category', SelectionType::MEALTIME->value)
            ->get(['id', 'value']);

        return $this->success([
            'menu_categories' => $this->toOptions($menuCategories),
            'meal_times' => $this->toOptions($mealTimes, 'value', 'id'),
            'ingredient_options' => collect(SelectIngredient::cases())->map(fn ($case) => [
                'label' => $case->value,
                'value' => $case->value,
            ])->values()->all(),
        ], 'Menu item options retrieved successfully.');
    }

    public function store(MenuItemRequest $request)
    {
        $data = $request->validated();
        $actorId = $this->requireActorId();

        $menuItem = DB::transaction(function () use ($request, $data, $actorId) {
            $menuCategory = MenuCategory::query()->with('restaurant')->findOrFail($data['menu_category_id']);
            $imagePath = $this->storeImage($request, $data['code']);
            $ingredients = $this->ingredientFlags($data['select_ingredient'] ?? []);

            $menuItem = MenuItem::create([
                'code' => $data['code'],
                'company_id' => $menuCategory->company_id,
                'restaurant_id' => $menuCategory->restaurant_id,
                'menu_category_id' => $menuCategory->id,
                'name' => $data['name'],
                'meal_time' => $data['meal_time'],
                'unit_price' => $data['unit_price'],
                'available_quantity' => $data['available_quantity'],
                'add_on' => $data['add_on'],
                'selection_type' => $data['selection_type'] ?? 'single',
                'image' => $imagePath,
                'remark' => $data['remark'] ?? null,
                'status' => $data['status'],
                'is_veg' => $data['is_veg'],
                ...$ingredients,
                'import_file_id' => $data['import_file_id'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            if (! empty($data['add_ons'])) {
                $this->addonService->storeAddons($menuItem, $data['add_ons'], $actorId);
            }

            return $menuItem->load(['addons.options', 'menuCategory.restaurant', 'mealTime', 'createdBy', 'updatedBy']);
        });

        return $this->created($menuItem, 'Menu item created successfully.');
    }

    public function show(MenuItem $menuItem, Request $request)
    {
        $itemsPerPage = $request->integer('items', 50);
        $menuItem->load(['menuCategory.restaurant', 'mealTime', 'createdBy', 'updatedBy']);

        $addons = $menuItem->addons()->with('options')->paginate($itemsPerPage)->withQueryString();

        return $this->success([
            'menu_item' => $menuItem,
            'addons' => $addons->items(),
        ], 'Menu item retrieved successfully.', 200, [
            'current_page' => $addons->currentPage(),
            'per_page' => $addons->perPage(),
            'total' => $addons->total(),
            'last_page' => $addons->lastPage(),
        ]);
    }

    public function update(MenuItemRequest $request, MenuItem $menuItem)
    {
        $data = $request->validated();
        $actorId = $this->requireActorId();

        DB::transaction(function () use ($request, $data, $menuItem, $actorId) {
            $menuCategory = MenuCategory::query()->with('restaurant')->findOrFail($data['menu_category_id']);
            $imagePath = $this->storeImage($request, $data['code'], $menuItem->image);
            $ingredients = $this->ingredientFlags($data['select_ingredient'] ?? []);

            $menuItem->update([
                'company_id' => $menuCategory->company_id,
                'restaurant_id' => $menuCategory->restaurant_id,
                'menu_category_id' => $menuCategory->id,
                'name' => $data['name'],
                'meal_time' => $data['meal_time'],
                'unit_price' => $data['unit_price'],
                'available_quantity' => $data['available_quantity'],
                'add_on' => $data['add_on'],
                'selection_type' => $data['selection_type'] ?? ($menuItem->selection_type ?? 'single'),
                'image' => $imagePath,
                'remark' => $data['remark'] ?? null,
                'status' => $data['status'],
                'is_veg' => $data['is_veg'],
                ...$ingredients,
                'import_file_id' => $data['import_file_id'] ?? $menuItem->import_file_id,
                'updated_by' => $actorId,
            ]);

            if ($data['add_on'] === 'no') {
                $menuItem->addons()->each(function ($addon) use ($actorId) {
                    $addon->options()->each(function ($option) use ($actorId) {
                        $option->update(['deleted_by' => $actorId, 'updated_by' => $actorId]);
                        $option->delete();
                    });
                    $addon->update(['deleted_by' => $actorId, 'updated_by' => $actorId]);
                    $addon->delete();
                });
            } else {
                $this->addonService->syncAddons($menuItem, $request->input('add_ons', []), $actorId);
            }
        });

        $menuItem->load(['addons.options', 'menuCategory.restaurant', 'mealTime', 'createdBy', 'updatedBy']);

        return $this->success($menuItem, 'Menu item updated successfully.');
    }

    public function destroy(MenuItem $menuItem)
    {
        $menuItem->update(['deleted_by' => $this->requireActorId()]);
        $menuItem->delete();

        return $this->deleted('Menu item deleted successfully.');
    }

    public function details(MenuCategory $menuCategory)
    {
        $menuCategory->load(['restaurant', 'servicedDates']);

        return $this->success([
            'restaurant' => $menuCategory->restaurant?->name,
            'served_dates' => $menuCategory->servicedDates,
        ], 'Menu category details retrieved successfully.');
    }

    public function importStore(Request $request)
    {
        $validated = $request->validate([
            'import_file' => ['nullable', 'file', 'mimes:json'],
            'menu_items' => ['nullable', 'array'],
            'menu_items.*.code' => ['required_with:menu_items', 'string', 'max:20'],
            'menu_items.*.name' => ['required_with:menu_items', 'string', 'max:64'],
            'menu_items.*.menu_category_id' => ['required_with:menu_items', 'integer', 'exists:menu_categories,id'],
            'menu_items.*.meal_time' => ['required_with:menu_items'],
            'menu_items.*.unit_price' => ['required_with:menu_items', 'numeric'],
            'menu_items.*.available_quantity' => ['required_with:menu_items', 'integer', 'min:0'],
            'add_ons' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
        ]);

        $payload = $this->resolveImportPayload($request, $validated);
        abort_if(($payload['menu_items'] ?? []) === [], 422, 'No menu items were provided for import.');

        $actorId = $this->requireActorId();
        $importFile = ImportFile::create([
            'file_name' => $request->hasFile('import_file') ? $request->file('import_file')->getClientOriginalName() : 'menu-items.json',
            'file_path' => $request->hasFile('import_file') ? $request->file('import_file')->store('menu-imports') : 'inline-payload',
            'imported_by' => $actorId,
            'imported_at' => now(),
        ]);

        $createdItems = DB::transaction(function () use ($payload, $importFile, $actorId) {
            $createdItems = [];
            $addOnCodeMap = [];

            foreach ($payload['menu_items'] as $itemData) {
                $menuCategory = MenuCategory::query()->findOrFail($itemData['menu_category_id']);
                $ingredients = $this->ingredientFlags($itemData['select_ingredient'] ?? []);

                $menuItem = MenuItem::create([
                    'code' => $itemData['code'],
                    'company_id' => $menuCategory->company_id,
                    'restaurant_id' => $menuCategory->restaurant_id,
                    'menu_category_id' => $menuCategory->id,
                    'name' => $itemData['name'],
                    'meal_time' => $itemData['meal_time'],
                    'unit_price' => $itemData['unit_price'],
                    'available_quantity' => $itemData['available_quantity'],
                    'add_on' => $itemData['add_on'] ?? 'no',
                    'selection_type' => $itemData['selection_type'] ?? 'single',
                    'image' => $itemData['image'] ?? null,
                    'remark' => $itemData['remark'] ?? null,
                    'status' => $itemData['status'] ?? Status::ACTIVE->value,
                    'is_veg' => $itemData['is_veg'] ?? 'No',
                    ...$ingredients,
                    'import_file_id' => $importFile->id,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $createdItems[] = $menuItem;
            }

            foreach ($payload['add_ons'] ?? [] as $addOnData) {
                $menuItem = MenuItem::query()->where('code', $addOnData['menu_item_code'])->firstOrFail();
                $addOn = MenuItemAddOn::create([
                    'menu_item_id' => $menuItem->id,
                    'name' => $addOnData['name'],
                    'type' => $addOnData['type'],
                    'min' => $addOnData['min'] ?? 0,
                    'max' => $addOnData['max'] ?? 0,
                    'add_on_required' => $addOnData['required'] ?? 'no',
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                if (! empty($addOnData['addon_code'])) {
                    $addOnCodeMap[$addOnData['addon_code']] = $addOn->id;
                }
            }

            foreach ($payload['options'] ?? [] as $optionData) {
                $addOnId = $addOnCodeMap[$optionData['addon_code'] ?? ''] ?? null;
                abort_if($addOnId === null, 422, 'Option references an unknown addon_code.');

                MenuItemAddOnOption::create([
                    'menu_item_add_on_id' => $addOnId,
                    'name' => $optionData['name'],
                    'surcharge' => $optionData['surcharge'] ?? 0,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            return MenuItem::query()->with(['addons.options', 'menuCategory.restaurant', 'mealTime'])
                ->where('import_file_id', $importFile->id)
                ->get();
        });

        return $this->created([
            'import_file' => $importFile,
            'menu_items' => $createdItems,
            'format' => 'json',
        ], 'Menu items imported successfully.');
    }

    public function export()
    {
        $menuItems = MenuItem::query()->with(['menuCategory.restaurant', 'mealTime', 'addons.options'])->get();

        return $this->success([
            'format' => 'json',
            'menu_items' => $menuItems->map(fn ($menuItem) => [
                'code' => $menuItem->code,
                'name' => $menuItem->name,
                'menu_category_id' => $menuItem->menu_category_id,
                'menu_category_name' => $menuItem->menuCategory?->name,
                'restaurant_id' => $menuItem->restaurant_id,
                'restaurant_name' => $menuItem->menuCategory?->restaurant?->name,
                'meal_time' => $menuItem->meal_time,
                'meal_time_name' => $menuItem->mealTime?->value,
                'unit_price' => $menuItem->unit_price,
                'available_quantity' => $menuItem->available_quantity,
                'add_on' => $menuItem->add_on,
                'selection_type' => $menuItem->selection_type,
                'status' => $menuItem->status,
                'is_veg' => $menuItem->is_veg,
                'contain_egg' => $menuItem->contain_egg,
                'contain_dairy' => $menuItem->contain_dairy,
                'contain_onion_garlic' => $menuItem->contain_onion_garlic,
                'contain_chili' => $menuItem->contain_chili,
                'remark' => $menuItem->remark,
                'image' => $menuItem->image,
                'add_ons' => $menuItem->addons->map(fn ($addon) => [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'type' => $addon->type,
                    'min' => $addon->min,
                    'max' => $addon->max,
                    'required' => $addon->add_on_required,
                    'options' => $addon->options->map(fn ($option) => [
                        'id' => $option->id,
                        'name' => $option->name,
                        'surcharge' => $option->surcharge,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ], 'Menu items exported successfully.');
    }

    private function storeImage(Request $request, string $code, ?string $existingPath = null): ?string
    {
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            $fileName = Str::random(10).'.'.$file->getClientOriginalExtension();
            return $file->storeAs('menu-items/'.Str::slug($code), $fileName);
        }

        if ($request->filled('cropped_image')) {
            $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', $request->string('cropped_image')->toString());
            $base64Image = str_replace(' ', '+', $base64Image);
            $imageData = base64_decode($base64Image, true);
            if ($imageData !== false) {
                $fileName = Str::random(10).'.png';
                $path = 'menu-items/'.Str::slug($code).'/'.$fileName;
                Storage::put($path, $imageData);
                return $path;
            }
        }

        return $existingPath;
    }

    private function ingredientFlags(array $selected): array
    {
        $isAllSelected = in_array(SelectIngredient::ALL->value, $selected, true);
        $mapping = [
            'contain_egg' => SelectIngredient::CONTAIN_EGG->value,
            'contain_dairy' => SelectIngredient::CONTAIN_DAIRY->value,
            'contain_onion_garlic' => SelectIngredient::CONTAIN_ONION_GARLIC->value,
            'contain_chili' => SelectIngredient::CONTAIN_CHILI->value,
        ];

        return collect($mapping)->mapWithKeys(fn ($enumValue, $column) => [
            $column => ($isAllSelected || in_array($enumValue, $selected, true)) ? 'Yes' : 'No',
        ])->all();
    }

    private function resolveImportPayload(Request $request, array $validated): array
    {
        if ($request->hasFile('import_file')) {
            $content = json_decode(file_get_contents($request->file('import_file')->getRealPath()), true);
            abort_if(! is_array($content), 422, 'The import file must contain valid JSON.');
            return $content;
        }

        return [
            'menu_items' => $validated['menu_items'] ?? [],
            'add_ons' => $validated['add_ons'] ?? [],
            'options' => $validated['options'] ?? [],
        ];
    }
}
