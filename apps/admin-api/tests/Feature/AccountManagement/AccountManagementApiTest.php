<?php

namespace Tests\Feature\AccountManagement;

use App\Enums\RoleValue;
use App\Enums\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
        $this->admin = User::query()->findOrFail(1);
    }

    public function test_role_options_and_role_creation_work(): void
    {
        $optionsResponse = $this->actingAs($this->admin)->getJson('/api/v1/account-management/roles/options');

        $optionsResponse
            ->assertOk()
            ->assertJsonFragment(['dashboard.view'])
            ->assertJsonFragment(['name' => 'dashboard']);

        $createResponse = $this->actingAs($this->admin)->postJson('/api/v1/account-management/roles', [
            'name' => 'KitchenAdmin',
            'status' => Status::ACTIVE->value,
            'role_type' => RoleValue::OPERATOR->value,
            'permissions' => [
                'dashboard' => ['view'],
                'users' => ['create', 'view'],
            ],
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'KitchenAdmin');

        $this->assertDatabaseHas('roles', ['name' => 'KitchenAdmin']);
        $this->assertDatabaseHas('role_permissions', ['permission_name' => 'dashboard.view']);
        $this->assertDatabaseHas('role_permissions', ['permission_name' => 'users.create']);
    }

    public function test_can_create_company_with_admin_and_holiday(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/account-management/companies', [
            'name' => 'Acme Foods',
            'username' => 'acmeadmin',
            'password' => 'Pass1234',
            'password_confirmation' => 'Pass1234',
            'payment_method_id' => 1,
            'status' => Status::ACTIVE->value,
            'place_order_weekend' => true,
            'place_order_holiday' => false,
            'period' => 2,
            'day' => '1',
            'holidays' => [
                ['name' => 'Founders Day', 'date' => '2026-01-15'],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Acme Foods');

        $companyId = DB::table('companies')->where('name', 'Acme Foods')->value('id');

        $this->assertNotNull($companyId);
        $this->assertDatabaseHas('users', [
            'name' => 'acmeadmin',
            'company_id' => $companyId,
            'role_id' => RoleValue::COMPANY_ADMIN->value,
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas('holidays', [
            'company_id' => $companyId,
            'name' => 'Founders Day',
            'date' => '2026-01-15',
        ]);
    }

    public function test_can_create_branch_and_fetch_user_options(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'code' => 'C100001',
            'name' => 'Branch Test Co',
            'payment_method_id' => 1,
            'status' => Status::ACTIVE->value,
            'order_limit_per_meal' => null,
            'credit_refresh_period' => null,
            'credit_refresh_value' => null,
            'place_order_weekend' => false,
            'place_order_holiday' => false,
            'remark' => null,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/account-management/branches', [
            'company_id' => $companyId,
            'name' => 'Main Branch',
            'location' => 'HQ',
            'remark' => 'Primary branch',
            'status' => Status::ACTIVE->value,
            'username' => 'branchadmin',
            'password' => 'Pass1234',
            'password_confirmation' => 'Pass1234',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Main Branch');

        $branchId = DB::table('branches')->where('name', 'Main Branch')->value('id');

        $this->assertDatabaseHas('users', [
            'name' => 'branchadmin',
            'branch_id' => $branchId,
            'role_id' => RoleValue::BRANCH_ADMIN->value,
            'guard_name' => 'web',
        ]);

        $optionsResponse = $this->actingAs($this->admin)->getJson('/api/v1/account-management/users/options');

        $optionsResponse
            ->assertOk()
            ->assertJsonCount(3, 'data.roles');
    }

    private function seedReferenceData(): void
    {
        $now = now();

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Superadmin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::SUPER_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 2, 'name' => 'Admin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::SYSTEM_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 3, 'name' => 'Company Admin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::COMPANY_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 4, 'name' => 'Branch Admin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::BRANCH_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 5, 'name' => 'Restaurant Admin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::RESTAURANT_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 6, 'name' => 'Operator', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::OPERATOR->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 7, 'name' => 'Staff', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::STAFF->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 8, 'name' => 'Driver', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::DRIVER->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);

        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'dashboard.view', 'is_branchadmin' => 0, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 2, 'name' => 'users.create', 'is_branchadmin' => 0, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 3, 'name' => 'users.view', 'is_branchadmin' => 0, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
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

        DB::table('selections')->insert([
            ['id' => 1, 'category' => 'PERIOD', 'value' => 'Weekly', 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 2, 'category' => 'DAY', 'value' => 'Monday', 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
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
