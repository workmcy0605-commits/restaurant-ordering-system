<?php

namespace App\Http\Controllers\Api\V1\AccountManagement;

use App\Enums\GuardType;
use App\Enums\RoleValue;
use App\Enums\Status;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\AdminRequest;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Traits\GenerateCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends ApiController
{
    use GenerateCode;

    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);
        $sortable = ['code' => 'code', 'uname' => 'name', 'aname' => 'nickname'];
        $sortField = $sortable[$request->input('sort')] ?? null;
        $sortDirection = $request->input('direction', 'desc');

        $query = User::query()
            ->with(['roleName', 'createdBy', 'updatedBy'])
            ->where('guard_name', GuardType::WEB->value)
            ->whereNull('company_id')
            ->when($this->currentUser() !== null, fn ($builder) => $builder->where('id', '!=', $this->currentUser()->id))
            ->when($request->filled('code'), fn ($builder) => $builder->where('code', 'like', '%'.$request->input('code').'%'))
            ->when($request->filled('name'), fn ($builder) => $builder->where('name', 'like', '%'.$request->input('name').'%'))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->input('status')));

        if ($this->currentRoleId() === RoleValue::SYSTEM_ADMIN->value) {
            $query->where('role_id', '!=', RoleValue::SUPER_ADMIN->value);
        }

        if ($sortField !== null) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderByDesc('id');
        }

        return $this->paginated($query->paginate($itemsPerPage)->withQueryString(), 'Admins retrieved successfully.');
    }

    public function options()
    {
        $allowedRoleIds = $this->currentRoleId() === RoleValue::SUPER_ADMIN->value
            ? [RoleValue::SUPER_ADMIN->value, RoleValue::SYSTEM_ADMIN->value]
            : [RoleValue::SYSTEM_ADMIN->value];

        $roles = Role::query()
            ->where('status', Status::ACTIVE->value)
            ->whereIn('id', $allowedRoleIds)
            ->get(['id', 'name']);

        $companies = Company::query()
            ->where('status', Status::ACTIVE->value)
            ->get(['id', 'name']);

        return $this->success([
            'roles' => $this->toOptions($roles),
            'companies' => $this->toOptions($companies),
        ], 'Admin form options retrieved successfully.');
    }

    public function store(AdminRequest $request)
    {
        $actorId = $this->requireActorId();

        $admin = User::create([
            'code' => $this->generateCode(User::query(), 'U'),
            'guard_name' => GuardType::WEB->value,
            'name' => $request->string('name')->toString(),
            'nickname' => $request->input('nickname'),
            'first_time_login' => 0,
            'role_id' => $request->input('role_type'),
            'password' => Hash::make($request->string('password')->toString()),
            'status' => $request->input('status'),
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        $admin->load(['roleName', 'createdBy', 'updatedBy']);

        return $this->created($admin, 'Admin created successfully.');
    }

    public function show(User $admin)
    {
        abort_if($admin->guard_name !== GuardType::WEB->value || $admin->company_id !== null, 404);

        $admin->load(['roleName', 'createdBy', 'updatedBy']);

        return $this->success($admin, 'Admin retrieved successfully.');
    }

    public function update(AdminRequest $request, User $admin)
    {
        abort_if($this->currentUser() !== null && $this->currentUser()->is($admin), 403, 'You cannot edit your own admin record here.');
        abort_if($admin->guard_name !== GuardType::WEB->value || $admin->company_id !== null, 404);

        $data = [
            'nickname' => $request->input('nickname'),
            'status' => $request->input('status'),
            'role_id' => $request->input('role_type', $admin->role_id),
            'updated_by' => $this->requireActorId(),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->string('password')->toString());
        }

        $admin->update($data);
        $admin->load(['roleName', 'createdBy', 'updatedBy']);

        return $this->success($admin, 'Admin updated successfully.');
    }

    public function destroy(User $admin)
    {
        abort_if($this->currentUser() !== null && $this->currentUser()->is($admin), 403, 'You cannot delete your own admin account.');
        abort_if($admin->guard_name !== GuardType::WEB->value || $admin->company_id !== null, 404);

        $admin->update(['deleted_by' => $this->requireActorId()]);
        $admin->delete();

        return $this->deleted('Admin deleted successfully.');
    }
}
