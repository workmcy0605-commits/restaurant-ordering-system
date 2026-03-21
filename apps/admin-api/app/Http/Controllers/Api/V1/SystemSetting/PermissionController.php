<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Enums\PermissionType;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);

        $permissions = Permission::query()
            ->when($request->filled('name'), fn ($query) => $query->where('name', 'like', '%'.$request->input('name').'%'))
            ->when($request->filled('is_branchadmin'), fn ($query) => $query->where('is_branchadmin', (int) $request->input('is_branchadmin')))
            ->orderByDesc('id')
            ->paginate($itemsPerPage)
            ->withQueryString();

        return $this->paginated($permissions, 'Permissions retrieved successfully.');
    }

    public function options()
    {
        return $this->success([
            'actions' => PermissionType::all(),
        ], 'Permission options retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'max:100',
            ],
            'actions' => ['nullable', 'array'],
            'actions.*' => ['string', Rule::in(PermissionType::all())],
            'is_branchadmin' => ['nullable', 'boolean'],
        ]);

        $actorId = $this->requireActorId();
        $actions = $validated['actions'] ?? PermissionType::all();
        $created = [];

        foreach ($actions as $action) {
            $created[] = Permission::firstOrCreate(
                [
                    'name' => $validated['name'].'.'.$action,
                ],
                [
                    'is_branchadmin' => (int) ($validated['is_branchadmin'] ?? 0),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]
            );
        }

        return $this->created($created, 'Permission entries created successfully.');
    }

    public function show(Permission $permission)
    {
        return $this->success($permission, 'Permission retrieved successfully.');
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:100', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'is_branchadmin' => ['required', 'boolean'],
        ]);

        RolePermission::query()
            ->where('permission_name', $permission->name)
            ->update([
                'permission_name' => $validated['name'],
                'updated_at' => now(),
            ]);

        $permission->update([
            'name' => $validated['name'],
            'is_branchadmin' => (int) $validated['is_branchadmin'],
            'updated_by' => $this->requireActorId(),
        ]);

        return $this->success($permission, 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $permission->update(['deleted_by' => $this->requireActorId()]);
        $permission->delete();

        return $this->deleted('Permission deleted successfully.');
    }
}
