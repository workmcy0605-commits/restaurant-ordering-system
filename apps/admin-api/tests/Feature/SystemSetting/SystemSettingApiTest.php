<?php

namespace Tests\Feature\SystemSetting;

use App\Enums\RoleValue;
use App\Enums\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemSettingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
        $this->superAdmin = User::query()->findOrFail(1);
    }

    public function test_can_manage_system_setting_types_and_settings(): void
    {
        $typeResponse = $this->actingAs($this->superAdmin)->postJson('/api/v1/system-setting/system-setting-types', [
            'name' => 'WalletLimit',
            'data_type' => 'number',
            'is_branchadmin' => false,
        ]);

        $typeResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'WalletLimit');

        $typeUuid = $typeResponse->json('data.uuid');

        $optionsResponse = $this->actingAs($this->superAdmin)->getJson('/api/v1/system-setting/system-settings/options');

        $optionsResponse
            ->assertOk()
            ->assertJsonFragment(['value' => 'Admin'])
            ->assertJsonFragment(['value' => $typeUuid]);

        $settingResponse = $this->actingAs($this->superAdmin)->postJson('/api/v1/system-setting/system-settings', [
            'system_setting_type_uuid' => $typeUuid,
            'value' => '1,500',
            'status' => true,
            'role_type' => 'Admin',
            'username' => 'settingsadmin',
        ]);

        $settingResponse
            ->assertCreated()
            ->assertJsonPath('data.value', '1500')
            ->assertJsonPath('data.role_type', 'Admin');

        $settingId = $settingResponse->json('data.id');

        $this->actingAs($this->superAdmin)
            ->patchJson('/api/v1/system-setting/system-settings/'.$settingId.'/toggle-status')
            ->assertOk()
            ->assertJsonPath('data.status', 0);

        $this->assertDatabaseHas('system_settings', [
            'id' => $settingId,
            'system_setting_type_uuid' => $typeUuid,
            'value' => '1500',
            'role_type' => 'Admin',
            'status' => 0,
        ]);
    }

    public function test_can_manage_permissions_and_selections(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson('/api/v1/system-setting/permissions/options')
            ->assertOk()
            ->assertJsonFragment(['Read'])
            ->assertJsonFragment(['Create']);

        $permissionResponse = $this->actingAs($this->superAdmin)->postJson('/api/v1/system-setting/permissions', [
            'name' => 'orders',
            'actions' => ['Read', 'Create'],
            'is_branchadmin' => false,
        ]);

        $permissionResponse->assertCreated();

        $permissionId = DB::table('permissions')->where('name', 'orders.Read')->value('id');
        DB::table('role_permissions')->insert([
            'role_id' => 2,
            'permission_name' => 'orders.Read',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $this->actingAs($this->superAdmin)
            ->putJson('/api/v1/system-setting/permissions/'.$permissionId, [
                'name' => 'orders.ReadOnly',
                'is_branchadmin' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'orders.ReadOnly');

        $this->assertDatabaseHas('permissions', [
            'id' => $permissionId,
            'name' => 'orders.ReadOnly',
            'is_branchadmin' => 1,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => 2,
            'permission_name' => 'orders.ReadOnly',
        ]);

        $selectionResponse = $this->actingAs($this->superAdmin)->postJson('/api/v1/system-setting/selections', [
            'value' => 'Friday',
            'category' => 'DAY',
        ]);

        $selectionResponse
            ->assertCreated()
            ->assertJsonPath('data.value', 'Friday');
    }

    public function test_can_manage_locales_and_backend_locales_exports(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/system-setting/locales', [
                'word' => 'Greeting',
                'en' => 'Hello',
                'zh' => 'Ni Hao',
            ])
            ->assertCreated()
            ->assertJsonPath('data.word', 'Greeting');

        $enPhpPath = resource_path('lang/en/lang.php');
        $zhPhpPath = resource_path('lang/zh/lang.php');

        $this->assertFileExists($enPhpPath);
        $this->assertFileExists($zhPhpPath);
        $this->assertStringContainsString("'Greeting' => 'Hello'", File::get($enPhpPath));

        $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/system-setting/backend-locales', [
                'word' => 'validation.required',
                'type' => 'Validation',
                'en' => 'The field is required.',
                'zh' => 'This field is required.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'Validation');

        $enJson = json_decode(File::get(resource_path('lang/en.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('The field is required.', $enJson['Validation']['validation']['required']);
    }

    public function test_can_manage_access_controls_read_audits_and_toggle_preferences(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'code' => 'C200001',
            'name' => 'Preference Co',
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

        $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/system-setting/access-controls', [
                'name' => 'RatingGuard',
                'type' => 'RATING',
                'status' => Status::ACTIVE->value,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'RatingGuard');

        DB::table('audits')->insert([
            'user_type' => User::class,
            'user_id' => 1,
            'event' => 'created',
            'auditable_type' => 'App\\Models\\SystemSetting',
            'auditable_id' => 99,
            'old_values' => null,
            'new_values' => json_encode(['status' => 1]),
            'url' => '/api/v1/system-setting/system-settings',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'tags' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->getJson('/api/v1/system-setting/audits')
            ->assertOk()
            ->assertJsonFragment(['auditable_id' => 99]);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/system-setting/holiday-preferences/toggle-weekend', [
                'company_id' => $companyId,
                'value' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.place_order_weekend', true);

        $this->assertDatabaseHas('companies', [
            'id' => $companyId,
            'place_order_weekend' => 1,
        ]);
    }

    private function seedReferenceData(): void
    {
        $now = now();

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Superadmin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::SUPER_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 2, 'name' => 'Admin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::SYSTEM_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 3, 'name' => 'Company Admin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::COMPANY_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 4, 'name' => 'BranchAdmin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::BRANCH_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 5, 'name' => 'RestaurantAdmin', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::RESTAURANT_ADMIN->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 6, 'name' => 'Operator', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::OPERATOR->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 7, 'name' => 'Staff', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::STAFF->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 8, 'name' => 'Driver', 'status' => Status::ACTIVE->value, 'role_type' => RoleValue::DRIVER->value, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
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

        DB::table('users')->insert([
            [
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
            ],
            [
                'id' => 2,
                'code' => 'U100002',
                'guard_name' => 'web',
                'name' => 'settingsadmin',
                'password' => Hash::make('Pass1234'),
                'company_id' => null,
                'branch_id' => null,
                'restaurant_id' => null,
                'credit' => 0,
                'initial_credit' => 0,
                'nickname' => 'Settings Admin',
                'contact_number' => null,
                'avatar' => null,
                'first_time_login' => 0,
                'fe_lang' => 'en',
                'last_time_login' => null,
                'last_ip_login' => null,
                'is_two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'role_id' => RoleValue::SYSTEM_ADMIN->value,
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
    }
}
