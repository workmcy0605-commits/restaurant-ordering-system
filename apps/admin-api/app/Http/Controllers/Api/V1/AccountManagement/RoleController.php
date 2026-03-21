<?php

namespace App\Http\Controllers\Api\V1\AccountManagement;

use App\Enums\RoleValue;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoleController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);
        $sortField = $request->input('sort');
        $sortDirection = $request->input('direction', 'desc');

        $query = Role::query()
            ->with('rolePermission')
            ->where('id', '!=', RoleValue::STAFF->value)
            ->when($this->currentUser() !== null, fn ($builder) => $builder->where('id', '!=', $this->currentUser()->role_id))
            ->when(! ($this->currentUser()?->role_id == RoleValue::SUPER_ADMIN->value && $this->currentUser()?->id == 1), fn ($builder) => $builder->where('id', '!=', 1))
            ->when($sortField === 'name', fn ($builder) => $builder->orderBy('name', $sortDirection))
            ->when($request->filled('roleName'), fn ($builder) => $builder->where('name', 'like', '%'.$request->input('roleName').'%'))
            ->when($request->filled('roleType'), fn ($builder) => $builder->where('role_type', $request->input('roleType')))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', 'like', '%'.$request->input('status').'%'))
            ->orderByDesc('id');

        $roles = $query->paginate($itemsPerPage)->withQueryString();

        $roles->setCollection(
            $roles->getCollection()->map(fn (Role $role) => $this->serializeRole($role))
        );

        return $this->paginated($roles, 'Roles retrieved successfully.');
    }

    public function options()
    {
        $includeBranchAdminPermissions = $this->currentRoleId() === RoleValue::SUPER_ADMIN->value;

        return $this->success([
            'permissions' => $this->permissionNames($this->currentRoleId())->all(),
            'permission_actions' => $this->permissionActions($includeBranchAdminPermissions),
        ], 'Role form options retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:20', 'unique:roles,name'],
            'status' => ['required'],
            'permissions' => ['array'],
            'role_type' => ['required'],
        ]);

        $actorId = $this->requireActorId();

        $role = DB::transaction(function () use ($validated, $actorId) {
            $role = Role::create([
                'name' => $validated['name'],
                'status' => $validated['status'],
                'role_type' => $validated['role_type'],
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->syncRolePermissions($role->id, $validated['permissions'] ?? []);

            return $role->load('rolePermission');
        });

        return $this->created($this->serializeRole($role), 'Role created successfully.');
    }

    public function show(Role $role)
    {
        abort_if($role->id == 1 && $this->currentRoleId() !== RoleValue::SUPER_ADMIN->value, 403, 'You are not allowed to view this role.');

        $role->load('rolePermission');

        return $this->success($this->serializeRole($role), 'Role retrieved successfully.');
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->id == 1, 403, 'Super admin role cannot be edited.');

        $validated = $request->validate([
            'status' => ['required'],
            'permissions' => ['array'],
            'role_type' => ['required'],
        ]);

        $actorId = $this->requireActorId();

        DB::transaction(function () use ($role, $validated, $actorId) {
            $role->update([
                'status' => $validated['status'],
                'role_type' => $validated['role_type'],
                'updated_by' => $actorId,
            ]);

            $this->syncRolePermissions($role->id, $validated['permissions'] ?? []);
        });

        User::refreshRolePermissionsCache($role->id);
        $role->load('rolePermission');

        return $this->success($this->serializeRole($role), 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        abort_if($role->id == 1, 403, 'Super admin role cannot be deleted.');

        $role->update(['deleted_by' => $this->requireActorId()]);
        $role->delete();

        return $this->deleted('Role deleted successfully.');
    }

    public function addPermission(Request $request)
    {
        $validated = $request->validate([
            'role_permission' => ['required', 'string', 'max:255'],
            'permission_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z]+$/'],
        ]);

        $actorId = $this->requireActorId();

        $permission = Permission::firstOrCreate(
            ['name' => $validated['role_permission'].'.'.$validated['permission_name']],
            [
                'is_branchadmin' => 0,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]
        );

        return $this->created($permission, 'Permission created successfully.');
    }

    private function permissionNames(string $roleId): Collection
    {
        $query = Permission::query();

        if ($roleId !== RoleValue::SUPER_ADMIN->value) {
            $query->where('is_branchadmin', 0);
        }

        return $query->pluck('name');
    }

    private function serializeRole(Role $role): array
    {
        $permissions = $role->rolePermission
            ->map(function (RolePermission $permission) {
                $parts = explode('.', $permission->permission_name);

                return [
                    'id' => $permission->id,
                    'name' => $permission->permission_name,
                    'module' => $parts[0] ?? '',
                    'action' => $parts[1] ?? '',
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $role->id,
            'name' => $role->name,
            'status' => $role->status,
            'role_type' => $role->role_type,
            'created_by' => $role->created_by,
            'updated_by' => $role->updated_by,
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at,
            'permissions' => $permissions,
        ];
    }

    private function syncRolePermissions(int $roleId, array $permissions): void
    {
        $newPermissions = collect($permissions)
            ->flatMap(fn ($actions, $section) => collect($actions)->map(fn ($action) => $section.'.'.$action))
            ->unique()
            ->values()
            ->toArray();

        $oldPermissions = RolePermission::query()
            ->where('role_id', $roleId)
            ->pluck('permission_name')
            ->toArray();

        $toInsert = array_diff($newPermissions, $oldPermissions);
        $toDelete = array_diff($oldPermissions, $newPermissions);

        if ($toInsert !== []) {
            RolePermission::withTrashed()
                ->where('role_id', $roleId)
                ->whereIn('permission_name', $toInsert)
                ->onlyTrashed()
                ->restore();

            $existing = RolePermission::query()
                ->where('role_id', $roleId)
                ->whereIn('permission_name', $toInsert)
                ->pluck('permission_name')
                ->toArray();

            $insertData = collect($toInsert)
                ->diff($existing)
                ->map(fn ($permissionName) => [
                    'role_id' => $roleId,
                    'permission_name' => $permissionName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

            if ($insertData !== []) {
                RolePermission::insert($insertData);
            }
        }

        if ($toDelete !== []) {
            RolePermission::query()
                ->where('role_id', $roleId)
                ->whereIn('permission_name', $toDelete)
                ->delete();
        }
    }
}
