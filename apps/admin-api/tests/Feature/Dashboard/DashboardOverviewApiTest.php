<?php

namespace Tests\Feature\Dashboard;

use App\Enums\RoleValue;
use App\Enums\Status;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardOverviewApiTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Pass1234';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createOrderTables();
        $this->seedDashboardData();
    }

    public function test_restaurant_admin_dashboard_is_scoped_to_their_restaurant_orders(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'userName' => 'restaurantadmin',
            'password' => self::PASSWORD,
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('code', '0000');

        $token = (string) $loginResponse->json('data.token');

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/dashboard/overview');

        $response
            ->assertOk()
            ->assertJsonPath('code', '0000')
            ->assertJsonPath('message', 'Dashboard overview retrieved successfully.')
            ->assertJsonPath('data.scope.level', 'restaurant')
            ->assertJsonPath('data.scope.label', 'Restaurant One')
            ->assertJsonPath('data.scope.restaurantId', 10)
            ->assertJsonPath('data.scope.latestOrderDate', '2026-03-20')
            ->assertJsonPath('data.summary.restaurantCount', 1)
            ->assertJsonPath('data.summary.menuItemCount', 1)
            ->assertJsonPath('data.summary.totalOrders', 2)
            ->assertJsonPath('data.summary.totalOrderItems', 2)
            ->assertJsonPath('data.summary.pendingOrderItems', 1)
            ->assertJsonPath('data.summary.completedOrderItems', 1)
            ->assertJsonPath('data.summary.totalOrderValue', 15.5)
            ->assertJsonPath('data.latestActivity.orders', 1)
            ->assertJsonPath('data.latestActivity.items', 1)
            ->assertJsonPath('data.latestActivity.revenue', 10)
            ->assertJsonPath('data.restaurantPerformance.0.restaurantId', 10)
            ->assertJsonPath('data.restaurantPerformance.0.itemQuantity', 2)
            ->assertJsonPath('data.topMenuItems.0.menuItemId', 1000)
            ->assertJsonPath('data.topMenuItems.0.quantity', 2)
            ->assertJsonPath('data.recentOrderItems.0.restaurantName', 'Restaurant One');

        $statusBreakdown = collect($response->json('data.statusBreakdown'));

        $this->assertSame(
            ['CREATED', 'COMPLETED'],
            $statusBreakdown->pluck('status')->all()
        );
    }

    private function createOrderTables(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code', 20)->unique();
            $table->decimal('total_price', 10, 2);
            $table->string('status', 32);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('menu_category_id');
            $table->unsignedBigInteger('menu_item_id');
            $table->string('order_tracking_no', 20)->nullable();
            $table->date('order_date');
            $table->timestamp('order_at')->nullable();
            $table->string('menu_category_name', 64);
            $table->string('meal_time', 64);
            $table->string('name', 64);
            $table->decimal('price', 10, 2);
            $table->string('remark', 100)->nullable();
            $table->unsignedInteger('rating')->nullable();
            $table->string('comment', 255)->nullable();
            $table->string('status', 32)->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    private function seedDashboardData(): void
    {
        $now = now();

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Superadmin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::SUPER_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 5, 'name' => 'Restaurant Admin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::RESTAURANT_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 7, 'name' => 'Staff', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::STAFF->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);

        DB::table('users')->insert([
            [
                'id' => 1,
                'code' => 'U100001',
                'guard_name' => 'web',
                'name' => 'restaurantadmin',
                'password' => Hash::make(self::PASSWORD),
                'company_id' => 1,
                'branch_id' => null,
                'restaurant_id' => 10,
                'credit' => 0,
                'initial_credit' => 0,
                'nickname' => 'Restaurant Admin',
                'contact_number' => null,
                'avatar' => null,
                'first_time_login' => 0,
                'fe_lang' => 'en',
                'last_time_login' => null,
                'last_ip_login' => null,
                'is_two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'role_id' => RoleValue::RESTAURANT_ADMIN->value,
                'fcm_token' => null,
                'status' => Status::ACTIVE->value,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => 11,
                'code' => 'U100011',
                'guard_name' => 'api',
                'name' => 'customer-a',
                'password' => Hash::make(self::PASSWORD),
                'company_id' => 1,
                'branch_id' => null,
                'restaurant_id' => null,
                'credit' => 0,
                'initial_credit' => 0,
                'nickname' => 'Customer A',
                'contact_number' => null,
                'avatar' => null,
                'first_time_login' => 0,
                'fe_lang' => 'en',
                'last_time_login' => null,
                'last_ip_login' => null,
                'is_two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'role_id' => RoleValue::STAFF->value,
                'fcm_token' => null,
                'status' => Status::ACTIVE->value,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => 12,
                'code' => 'U100012',
                'guard_name' => 'api',
                'name' => 'customer-b',
                'password' => Hash::make(self::PASSWORD),
                'company_id' => 1,
                'branch_id' => null,
                'restaurant_id' => null,
                'credit' => 0,
                'initial_credit' => 0,
                'nickname' => 'Customer B',
                'contact_number' => null,
                'avatar' => null,
                'first_time_login' => 0,
                'fe_lang' => 'en',
                'last_time_login' => null,
                'last_ip_login' => null,
                'is_two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'role_id' => RoleValue::STAFF->value,
                'fcm_token' => null,
                'status' => Status::ACTIVE->value,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);

        DB::table('payment_methods')->insert([
            'id' => 1,
            'uuid' => '11111111-1111-1111-1111-111111111111',
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
            'name' => 'Client Company',
            'payment_method_id' => 1,
            'status' => Status::ACTIVE->value,
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
            [
                'id' => 10,
                'code' => 'R100010',
                'company_id' => 1,
                'name' => 'Restaurant One',
                'remark' => null,
                'status' => Status::ACTIVE->value,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => 20,
                'code' => 'R100020',
                'company_id' => 1,
                'name' => 'Restaurant Two',
                'remark' => null,
                'status' => Status::ACTIVE->value,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);

        DB::table('menu_categories')->insert([
            [
                'id' => 100,
                'code' => 'M100100',
                'company_id' => 1,
                'restaurant_id' => 10,
                'name' => 'Salads',
                'repeat' => 'yes',
                'repeat_by' => 'Daily',
                'start_time' => '09:00:00',
                'end_time' => '14:00:00',
                'remark' => null,
                'status' => Status::ACTIVE->value,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => 200,
                'code' => 'M100200',
                'company_id' => 1,
                'restaurant_id' => 20,
                'name' => 'Pasta',
                'repeat' => 'yes',
                'repeat_by' => 'Daily',
                'start_time' => '15:00:00',
                'end_time' => '20:00:00',
                'remark' => null,
                'status' => Status::ACTIVE->value,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);

        DB::table('menu_items')->insert([
            [
                'id' => 1000,
                'code' => 'I101000',
                'company_id' => 1,
                'restaurant_id' => 10,
                'menu_category_id' => 100,
                'name' => 'Caesar Salad',
                'meal_time' => 'Lunch',
                'unit_price' => 5.50,
                'available_quantity' => 30,
                'add_on' => 'no',
                'selection_type' => 'single',
                'image' => null,
                'remark' => null,
                'status' => Status::ACTIVE->value,
                'is_veg' => 'No',
                'contain_egg' => 'No',
                'contain_dairy' => 'No',
                'contain_onion_garlic' => 'No',
                'contain_chili' => 'No',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => 2000,
                'code' => 'I102000',
                'company_id' => 1,
                'restaurant_id' => 20,
                'menu_category_id' => 200,
                'name' => 'Pasta Special',
                'meal_time' => 'Dinner',
                'unit_price' => 8.75,
                'available_quantity' => 20,
                'add_on' => 'no',
                'selection_type' => 'single',
                'image' => null,
                'remark' => null,
                'status' => Status::ACTIVE->value,
                'is_veg' => 'No',
                'contain_egg' => 'No',
                'contain_dairy' => 'No',
                'contain_onion_garlic' => 'No',
                'contain_chili' => 'No',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);

        DB::table('orders')->insert([
            [
                'id' => 10001,
                'user_id' => 11,
                'code' => 'O10001',
                'total_price' => 5.50,
                'status' => 'CREATED',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => '2026-03-19 10:00:00',
                'updated_at' => '2026-03-19 10:00:00',
                'deleted_at' => null,
            ],
            [
                'id' => 10002,
                'user_id' => 12,
                'code' => 'O10002',
                'total_price' => 10.00,
                'status' => 'COMPLETED',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => '2026-03-20 12:00:00',
                'updated_at' => '2026-03-20 12:00:00',
                'deleted_at' => null,
            ],
            [
                'id' => 10003,
                'user_id' => 11,
                'code' => 'O10003',
                'total_price' => 8.75,
                'status' => 'CANCELLED',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => '2026-03-21 09:00:00',
                'updated_at' => '2026-03-21 09:00:00',
                'deleted_at' => null,
            ],
        ]);

        DB::table('order_items')->insert([
            [
                'id' => 50001,
                'code' => 'OI50001',
                'company_id' => 1,
                'restaurant_id' => 10,
                'order_id' => 10001,
                'menu_category_id' => 100,
                'menu_item_id' => 1000,
                'order_tracking_no' => 'T-001',
                'order_date' => '2026-03-19',
                'order_at' => '2026-03-19 10:00:00',
                'menu_category_name' => 'Salads',
                'meal_time' => 'Lunch',
                'name' => 'Caesar Salad',
                'price' => 5.50,
                'remark' => null,
                'rating' => null,
                'comment' => null,
                'status' => 'CREATED',
                'driver_id' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => '2026-03-19 10:00:00',
                'updated_at' => '2026-03-19 10:00:00',
                'deleted_at' => null,
            ],
            [
                'id' => 50002,
                'code' => 'OI50002',
                'company_id' => 1,
                'restaurant_id' => 10,
                'order_id' => 10002,
                'menu_category_id' => 100,
                'menu_item_id' => 1000,
                'order_tracking_no' => 'T-002',
                'order_date' => '2026-03-20',
                'order_at' => '2026-03-20 12:00:00',
                'menu_category_name' => 'Salads',
                'meal_time' => 'Lunch',
                'name' => 'Caesar Salad',
                'price' => 10.00,
                'remark' => null,
                'rating' => null,
                'comment' => null,
                'status' => 'COMPLETED',
                'driver_id' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => '2026-03-20 12:00:00',
                'updated_at' => '2026-03-20 12:00:00',
                'deleted_at' => null,
            ],
            [
                'id' => 50003,
                'code' => 'OI50003',
                'company_id' => 1,
                'restaurant_id' => 20,
                'order_id' => 10003,
                'menu_category_id' => 200,
                'menu_item_id' => 2000,
                'order_tracking_no' => 'T-003',
                'order_date' => '2026-03-21',
                'order_at' => '2026-03-21 09:00:00',
                'menu_category_name' => 'Pasta',
                'meal_time' => 'Dinner',
                'name' => 'Pasta Special',
                'price' => 8.75,
                'remark' => null,
                'rating' => null,
                'comment' => null,
                'status' => 'CANCELLED',
                'driver_id' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
                'created_at' => '2026-03-21 09:00:00',
                'updated_at' => '2026-03-21 09:00:00',
                'deleted_at' => null,
            ],
        ]);
    }
}
