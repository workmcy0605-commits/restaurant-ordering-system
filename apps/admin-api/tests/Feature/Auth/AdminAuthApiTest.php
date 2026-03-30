<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleValue;
use App\Enums\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthApiTest extends TestCase
{
    use RefreshDatabase;

    private const DEFAULT_PASSWORD = 'Pass1234';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seedReferenceData();
    }

    public function test_admin_can_log_in_fetch_profile_and_access_protected_routes(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'userName' => 'superadmin',
            'password' => self::DEFAULT_PASSWORD,
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('code', '0000')
            ->assertJsonPath('message', 'Login successful.');

        $token = $loginResponse->json('data.token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $meResponse = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('data.userId', '1')
            ->assertJsonPath('data.userName', 'Super Admin')
            ->assertJsonPath('data.roles.0', 'R_SUPER')
            ->assertJsonPath('data.buttons.0', 'dashboard.view')
            ->assertJsonFragment(['dashboard.view'])
            ->assertJsonFragment(['users.view']);

        $protectedResponse = $this
            ->withToken($token)
            ->getJson('/api/v1/account-management/roles/options');

        $protectedResponse
            ->assertOk()
            ->assertJsonPath('code', '0000')
            ->assertJsonFragment(['dashboard.view'])
            ->assertJsonFragment(['name' => 'dashboard']);
    }

    public function test_refresh_rotates_token_pair_and_revokes_the_old_access_token(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'userName' => 'superadmin',
            'password' => self::DEFAULT_PASSWORD,
        ]);

        $oldToken = (string) $loginResponse->json('data.token');
        $refreshToken = (string) $loginResponse->json('data.refreshToken');

        $refreshResponse = $this->postJson('/api/v1/auth/refresh', [
            'refreshToken' => $refreshToken,
        ]);

        $refreshResponse
            ->assertOk()
            ->assertJsonPath('code', '0000')
            ->assertJsonPath('message', 'Token refreshed successfully.');

        $newToken = (string) $refreshResponse->json('data.token');
        $newRefreshToken = (string) $refreshResponse->json('data.refreshToken');

        $this->assertNotSame($oldToken, $newToken);
        $this->assertNotSame($refreshToken, $newRefreshToken);

        $oldTokenResponse = $this
            ->withToken($oldToken)
            ->getJson('/api/v1/auth/me');

        $oldTokenResponse
            ->assertOk()
            ->assertJsonPath('code', '8888')
            ->assertJsonPath('message', 'Please log in again.');

        $newTokenResponse = $this
            ->withToken($newToken)
            ->getJson('/api/v1/auth/me');

        $newTokenResponse
            ->assertOk()
            ->assertJsonPath('code', '0000')
            ->assertJsonPath('data.userId', '1');
    }

    public function test_non_admin_role_cannot_log_in_to_admin_portal(): void
    {
        User::query()->create([
            'code' => 'U100007',
            'guard_name' => 'web',
            'name' => 'staffmember',
            'password' => self::DEFAULT_PASSWORD,
            'credit' => 0,
            'initial_credit' => 0,
            'nickname' => 'Staff Member',
            'first_time_login' => 0,
            'fe_lang' => 'en',
            'role_id' => RoleValue::STAFF->value,
            'status' => Status::ACTIVE->value,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'userName' => 'staffmember',
            'password' => self::DEFAULT_PASSWORD,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', '1003')
            ->assertJsonPath('message', 'This user is not allowed to access the admin portal.');
    }

    public function test_protected_routes_require_authentication(): void
    {
        $meResponse = $this->getJson('/api/v1/auth/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('code', '8888')
            ->assertJsonPath('message', 'Please log in again.');

        $protectedResponse = $this->getJson('/api/v1/account-management/roles/options');

        $protectedResponse
            ->assertOk()
            ->assertJsonPath('code', '8888')
            ->assertJsonPath('message', 'Please log in again.');
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
            ['id' => 2, 'name' => 'users.view', 'is_branchadmin' => 0, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 3, 'name' => 'users.create', 'is_branchadmin' => 0, 'created_by' => 1, 'updated_by' => 1, 'deleted_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);

        DB::table('role_permissions')->insert([
            ['role_id' => 1, 'permission_name' => 'dashboard.view', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['role_id' => 1, 'permission_name' => 'users.view', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'code' => 'U100001',
            'guard_name' => 'web',
            'name' => 'superadmin',
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'company_id' => null,
            'branch_id' => null,
            'restaurant_id' => null,
            'credit' => 0,
            'initial_credit' => 0,
            'nickname' => 'Super Admin',
            'contact_number' => null,
            'avatar' => null,
            'first_time_login' => 1,
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
