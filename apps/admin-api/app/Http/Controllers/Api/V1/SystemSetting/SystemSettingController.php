<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Enums\RoleValue;
use App\Enums\Status;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\SystemSettingType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SystemSettingController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);
        $username = $request->input('username');
        $searchUser = null;

        if ($username !== null && $username !== '') {
            $searchUser = User::query()->where('name', $username)->first();
        }

        $query = SystemSetting::query()->with(['systemSettingType', 'targetUser', 'createdBy', 'updatedBy']);

        $query
            ->when($request->filled('setting_type'), fn ($builder) => $builder->where('system_setting_type_uuid', $request->input('setting_type')))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', (int) $request->input('status')))
            ->when($request->filled('role_type'), fn ($builder) => $builder->where('role_type', $request->input('role_type')))
            ->when($username !== null && $username !== '', function ($builder) use ($searchUser) {
                if ($searchUser === null) {
                    $builder->whereRaw('1 = 0');

                    return;
                }

                $builder->where('table_primary_id', $searchUser->id);
            });

        if ($this->currentRoleId() !== RoleValue::SUPER_ADMIN->value) {
            $query->where('role_type', '!=', 'BranchAdmin');
        }

        $settings = $query->orderByDesc('id')->paginate($itemsPerPage)->withQueryString();

        return $this->paginated($settings, 'System settings retrieved successfully.');
    }

    public function options()
    {
        $roles = $this->activeRolesQuery()
            ->when($this->currentRoleId() !== RoleValue::SUPER_ADMIN->value, fn ($query) => $query->where('id', '!=', RoleValue::SUPER_ADMIN->value))
            ->get(['id', 'name']);

        $systemSettingTypes = SystemSettingType::query()
            ->when($this->currentRoleId() !== RoleValue::SUPER_ADMIN->value, fn ($query) => $query->where('is_branchadmin', 0))
            ->orderBy('name')
            ->get(['uuid', 'name', 'data_type', 'is_branchadmin']);

        return $this->success([
            'roles' => $roles->map(fn ($role) => ['label' => $role->name, 'value' => $role->name, 'role_id' => $role->id])->values()->all(),
            'system_setting_types' => $systemSettingTypes->map(fn ($type) => [
                'label' => $type->name,
                'value' => $type->uuid,
                'data_type' => $type->data_type,
                'is_branchadmin' => (bool) $type->is_branchadmin,
            ])->values()->all(),
        ], 'System setting form options retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'system_setting_type_uuid' => ['required', Rule::exists('system_setting_types', 'uuid')],
            'value' => ['required'],
            'status' => ['required', 'boolean'],
            'role_type' => ['nullable', 'string', 'max:50'],
            'username' => ['nullable', 'required_with:role_type', 'string', 'max:64'],
        ]);

        [$roleName, $targetUser] = $this->resolveTargetUser($validated['role_type'] ?? null, $validated['username'] ?? null);

        $duplicateExists = SystemSetting::query()
            ->where('system_setting_type_uuid', $validated['system_setting_type_uuid'])
            ->where('role_type', $roleName)
            ->where('table_primary_id', $targetUser?->id)
            ->exists();

        abort_if($duplicateExists, 422, 'This system setting already exists.');

        $actorId = $this->requireActorId();

        $systemSetting = SystemSetting::create([
            'system_setting_type_uuid' => $validated['system_setting_type_uuid'],
            'value' => $this->normalizeValue($validated['value']),
            'role_type' => $roleName,
            'table_primary_id' => $targetUser?->id,
            'status' => (int) $validated['status'],
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        $systemSetting->load(['systemSettingType', 'targetUser', 'createdBy', 'updatedBy']);

        return $this->created($systemSetting, 'System setting created successfully.');
    }

    public function show(SystemSetting $systemSetting)
    {
        $systemSetting->load(['systemSettingType', 'targetUser', 'createdBy', 'updatedBy']);

        return $this->success($systemSetting, 'System setting retrieved successfully.');
    }

    public function update(Request $request, SystemSetting $systemSetting)
    {
        $validated = $request->validate([
            'value' => ['required'],
            'status' => ['required', 'boolean'],
        ]);

        $systemSetting->update([
            'value' => $this->normalizeValue($validated['value']),
            'status' => (int) $validated['status'],
            'updated_by' => $this->requireActorId(),
        ]);

        $systemSetting->load(['systemSettingType', 'targetUser', 'createdBy', 'updatedBy']);

        return $this->success($systemSetting, 'System setting updated successfully.');
    }

    public function destroy(SystemSetting $systemSetting)
    {
        $systemSetting->update(['deleted_by' => $this->requireActorId()]);
        $systemSetting->delete();

        return $this->deleted('System setting deleted successfully.');
    }

    public function toggleStatus(SystemSetting $systemSetting)
    {
        $systemSetting->update([
            'status' => $systemSetting->status === 1 ? 0 : 1,
            'updated_by' => $this->requireActorId(),
        ]);

        $systemSetting->load(['systemSettingType', 'targetUser', 'createdBy', 'updatedBy']);

        return $this->success($systemSetting, 'System setting status updated successfully.');
    }

    private function normalizeValue(mixed $value): string
    {
        return str_replace(',', '', (string) $value);
    }

    private function resolveTargetUser(?string $roleName, ?string $username): array
    {
        if ($roleName === null || $roleName === '') {
            return [null, null];
        }

        $role = Role::query()->where('name', $roleName)->first();
        abort_if($role === null, 422, 'Selected role type was not found.');

        $targetUser = User::query()
            ->where('role_id', $role->id)
            ->where('name', $username)
            ->first();

        abort_if($targetUser === null, 422, 'Username was not found for the selected role type.');

        return [$roleName, $targetUser];
    }

    private function activeRolesQuery()
    {
        return Role::query()->whereIn('status', [1, '1', Status::ACTIVE->value]);
    }
}
