<?php

namespace Tests\Feature\MenuManagement;

use App\Enums\RoleValue;
use App\Enums\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MenuManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
        $this->admin = User::query()->findOrFail(1);
    }

    public function test_can_create_menu_category_and_read_category_details(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/menu-management/menu-categories', [
            'name' => 'Breakfast Menu',
            'restaurant_id' => 1,
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-22',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'repeat' => 'yes',
            'repeat_by' => 'Daily',
            'status' => Status::ACTIVE->value,
            'remark' => 'Morning service',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Breakfast Menu');

        $menuCategoryId = $response->json('data.id');

        $this->assertDatabaseHas('menu_categories', [
            'id' => $menuCategoryId,
            'restaurant_id' => 1,
            'name' => 'Breakfast Menu',
        ]);
        $this->assertDatabaseCount('menu_served_dates', 3);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/menu-management/menu-categories/'.$menuCategoryId.'/details')
            ->assertOk()
            ->assertJsonPath('data.restaurant', 'Central Kitchen')
            ->assertJsonCount(3, 'data.served_dates');
    }

    public function test_can_create_update_and_export_menu_item_with_addons(): void
    {
        $menuCategoryId = $this->createMenuCategory();

        $createResponse = $this->actingAs($this->admin)->postJson('/api/v1/menu-management/menu-items', [
            'code' => 'SPICY_NOODLES',
            'name' => 'Spicy Noodles',
            'menu_category_id' => $menuCategoryId,
            'meal_time' => '1',
            'unit_price' => '12.50',
            'available_quantity' => 25,
            'add_on' => 'yes',
            'selection_type' => 'multiple',
            'status' => Status::ACTIVE->value,
            'is_veg' => 'Yes',
            'select_ingredient' => ['CONTAIN_CHILI'],
            'remark' => 'Popular lunch item',
            'add_ons' => [
                [
                    'name' => 'Toppings',
                    'type' => 'checkbox',
                    'min' => 0,
                    'max' => 2,
                    'required' => 'no',
                    'options' => [
                        ['optionname' => 'Cheese', 'surcharge' => 2],
                        ['optionname' => 'Egg', 'surcharge' => 1],
                    ],
                ],
            ],
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.code', 'SPICY_NOODLES')
            ->assertJsonPath('data.selection_type', 'multiple')
            ->assertJsonPath('data.addons.0.name', 'Toppings')
            ->assertJsonPath('data.addons.0.options.0.name', 'Cheese');

        $menuItemId = $createResponse->json('data.id');

        $this->actingAs($this->admin)
            ->putJson('/api/v1/menu-management/menu-items/'.$menuItemId, [
                'code' => 'SPICY_NOODLES',
                'name' => 'Spicy Noodles',
                'menu_category_id' => $menuCategoryId,
                'meal_time' => '1',
                'unit_price' => '13.00',
                'available_quantity' => 20,
                'add_on' => 'no',
                'selection_type' => 'single',
                'status' => Status::ACTIVE->value,
                'is_veg' => 'Yes',
                'select_ingredient' => [],
                'remark' => 'Updated lunch item',
            ])
            ->assertOk()
            ->assertJsonPath('data.add_on', 'no')
            ->assertJsonPath('data.selection_type', 'single')
            ->assertJsonCount(0, 'data.addons');

        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItemId,
            'selection_type' => 'single',
            'unit_price' => 13,
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/menu-management/menu-items/export')
            ->assertOk()
            ->assertJsonPath('data.format', 'json')
            ->assertJsonFragment(['code' => 'SPICY_NOODLES']);
    }

    public function test_can_import_menu_items_from_json_and_delete_served_date(): void
    {
        $menuCategoryId = $this->createMenuCategory([
            'code' => 'M100099',
            'name' => 'Lunch Menu',
        ]);
        $servedDateId = $this->createServedDate($menuCategoryId, [
            'start_date' => '2026-03-24',
            'end_date' => '2026-03-24',
            'select_day' => 'Tuesday',
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/menu-management/menu-items/import-store', [
                'menu_items' => [
                    [
                        'code' => 'LUNCH_SOUP',
                        'name' => 'Lunch Soup',
                        'menu_category_id' => $menuCategoryId,
                        'meal_time' => '1',
                        'unit_price' => '6.00',
                        'available_quantity' => 40,
                        'add_on' => 'yes',
                        'selection_type' => 'single',
                        'status' => Status::ACTIVE->value,
                        'is_veg' => 'No',
                        'select_ingredient' => ['CONTAIN_EGG'],
                        'remark' => 'Imported from JSON',
                    ],
                ],
                'add_ons' => [
                    [
                        'addon_code' => 'addon-1',
                        'menu_item_code' => 'LUNCH_SOUP',
                        'name' => 'Sauce',
                        'type' => 'radio',
                        'min' => 0,
                        'max' => 1,
                        'required' => 'no',
                    ],
                ],
                'options' => [
                    [
                        'addon_code' => 'addon-1',
                        'name' => 'Mayo',
                        'surcharge' => 1,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.format', 'json')
            ->assertJsonPath('data.menu_items.0.code', 'LUNCH_SOUP');

        $this->assertDatabaseHas('import_files', ['file_name' => 'menu-items.json']);
        $this->assertDatabaseHas('menu_items', ['code' => 'LUNCH_SOUP']);
        $this->assertDatabaseHas('menu_item_add_ons', ['name' => 'Sauce']);
        $this->assertDatabaseHas('menu_item_add_on_options', ['name' => 'Mayo']);

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/menu-management/menu-served-dates/'.$servedDateId)
            ->assertOk()
            ->assertJsonPath('message', 'Menu served date deleted successfully.');

        $this->assertSoftDeleted('menu_served_dates', ['id' => $servedDateId]);
    }

    private function createMenuCategory(array $overrides = []): int
    {
        $menuCategoryId = DB::table('menu_categories')->insertGetId(array_merge([
            'code' => 'M100001',
            'company_id' => 1,
            'restaurant_id' => 1,
            'name' => 'Default Menu',
            'repeat' => 'no',
            'repeat_by' => null,
            'remark' => null,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => Status::ACTIVE->value,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $overrides));

        $this->createServedDate($menuCategoryId);

        return $menuCategoryId;
    }

    private function createServedDate(int $menuCategoryId, array $overrides = []): int
    {
        return DB::table('menu_served_dates')->insertGetId(array_merge([
            'menu_category_id' => $menuCategoryId,
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-20',
            'select_day' => 'Friday',
            'status' => Status::ACTIVE->value,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $overrides));
    }

    private function seedReferenceData(): void
    {
        $now = now();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'Superadmin',
            'status' => Status::ACTIVE->value,
            'role_type' => RoleValue::SUPER_ADMIN->value,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        DB::table('payment_methods')->insert([
            'id' => 1,
            'uuid' => (string) Str::uuid(),
            'name' => 'Wallet',
            'image' => null,
            'status' => Status::ACTIVE->value,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        DB::table('companies')->insert([
            'id' => 1,
            'code' => 'C100001',
            'name' => 'Acme Foods',
            'payment_method_id' => 1,
            'status' => Status::ACTIVE->value,
            'place_order_weekend' => false,
            'place_order_holiday' => false,
            'order_limit_per_meal' => null,
            'credit_refresh_period' => null,
            'credit_refresh_value' => null,
            'remark' => null,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        DB::table('restaurants')->insert([
            'id' => 1,
            'code' => 'R100001',
            'company_id' => 1,
            'name' => 'Central Kitchen',
            'remark' => null,
            'status' => Status::ACTIVE->value,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        DB::table('selections')->insert([
            'id' => 1,
            'category' => 'MEALTIME',
            'value' => 'Lunch',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'code' => 'U100001',
            'guard_name' => 'web',
            'name' => 'superadmin',
            'password' => Hash::make('Pass1234'),
            'company_id' => null,
            'branch_id' => null,
            'restaurant_id' => null,
            'credit' => 0,
            'initial_credit' => 0,
            'nickname' => 'Super Admin',
            'contact_number' => null,
            'avatar' => null,
            'first_time_login' => 0,
            'fe_lang' => 'en',
            'last_time_login' => null,
            'last_ip_login' => null,
            'is_two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'role_id' => RoleValue::SUPER_ADMIN->value,
            'fcm_token' => null,
            'status' => Status::ACTIVE->value,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
    }
}
