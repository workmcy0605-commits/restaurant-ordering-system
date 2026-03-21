<?php

namespace App\Http\Controllers\Api\V1\AccountManagement;

use App\Enums\GuardType;
use App\Enums\RoleValue;
use App\Enums\Status;
use App\Filters\UserFilter;
use App\Filters\UserSort;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\UserRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Traits\GenerateCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends ApiController
{
    use GenerateCode;

    public function index(Request $request)
    {
        $currentUser = $this->currentUser();
        $itemsPerPage = $request->integer('items', 100);
        $filters = [
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'branch_id' => $request->input('branch_id'),
            'status' => $request->has('status') ? $request->input('status') : Status::ACTIVE->value,
            'roleType' => $request->input('roleType'),
        ];

        [$allowedRoleIds, $currentCompanyId, $currentBranchId, $currentRestaurantId] = $this->resolveUserScope($currentUser);

        $query = User::query()->with(['branchName', 'restaurantName', 'createdBy', 'updatedBy', 'roleName']);
        $query = UserFilter::apply($query, $filters, $allowedRoleIds, $currentCompanyId, $currentBranchId, $currentRestaurantId);
        $query = UserSort::apply($query, $request->input('sort'), $request->input('direction', 'desc'));

        return $this->paginated($query->paginate($itemsPerPage)->withQueryString(), 'Users retrieved successfully.');
    }

    public function options()
    {
        return $this->success($this->roleData(), 'User form options retrieved successfully.');
    }

    public function store(UserRequest $request)
    {
        $actorId = $this->requireActorId();
        $roleId = (string) $request->input('role_id');

        $user = User::create([
            'code' => $this->generateCode(User::query(), 'U'),
            'guard_name' => $this->resolveGuardName($roleId),
            'name' => $request->input('name'),
            'password' => Hash::make($request->string('password')->toString()),
            'company_id' => $this->resolveCompanyId($request),
            'branch_id' => $request->input('branch_id'),
            'restaurant_id' => $request->input('restaurant_id'),
            'credit' => $request->input('initial_credit', 0),
            'initial_credit' => $request->input('initial_credit', 0),
            'nickname' => $request->input('nickname'),
            'contact_number' => $request->input('contact_number'),
            'role_id' => $roleId,
            'status' => $request->input('status'),
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        $user->load(['roleName', 'companyName', 'branchName', 'restaurantName', 'createdBy', 'updatedBy']);

        return $this->created($user, 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load(['roleName', 'companyName', 'branchName', 'restaurantName', 'createdBy', 'updatedBy']);

        return $this->success($user, 'User retrieved successfully.');
    }

    public function update(UserRequest $request, User $user)
    {
        $data = [
            'name' => $request->input('name', $user->name),
            'initial_credit' => $request->input('initial_credit', $user->initial_credit),
            'nickname' => $request->input('nickname', $user->nickname),
            'contact_number' => $request->input('contact_number', $user->contact_number),
            'status' => $request->input('status', $user->status),
            'role_id' => $request->input('role_id', $user->role_id),
            'company_id' => $this->resolveCompanyId($request, $user),
            'branch_id' => $request->input('branch_id', $user->branch_id),
            'restaurant_id' => $request->input('restaurant_id', $user->restaurant_id),
            'guard_name' => $this->resolveGuardName((string) $request->input('role_id', $user->role_id)),
            'updated_by' => $this->requireActorId(),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->string('password')->toString());
        }

        $user->update($data);
        $user->load(['roleName', 'companyName', 'branchName', 'restaurantName', 'createdBy', 'updatedBy']);

        return $this->success($user, 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_if($this->currentUser() !== null && $this->currentUser()->is($user), 403, 'You cannot delete your own user account.');

        $user->update(['deleted_by' => $this->requireActorId()]);
        $user->delete();

        return $this->deleted('User deleted successfully.');
    }

    public function searchUsername(Request $request)
    {
        $user = User::query()
            ->where('name', $request->input('username'))
            ->with('companyName')
            ->first();

        if (! $user || $user->companyName?->payment_method_id !== 1) {
            return $this->success(['found' => false], 'User not found.');
        }

        return $this->success([
            'found' => true,
            'credit' => $user->credit,
        ], 'User credit retrieved successfully.');
    }

    public function getPaymentMethod(Request $request)
    {
        $company = Company::query()->find($request->input('id'));

        return $this->success([
            'payment_method_id' => $company?->payment_method_id,
        ], 'Company payment method retrieved successfully.');
    }

    private function roleData(): array
    {
        $authUser = $this->currentUser();
        $roleId = (string) ($authUser?->role_id ?? RoleValue::SUPER_ADMIN->value);

        $roles = Role::query()
            ->where('status', Status::ACTIVE->value)
            ->when(
                in_array($roleId, [RoleValue::SUPER_ADMIN->value, RoleValue::SYSTEM_ADMIN->value, RoleValue::COMPANY_ADMIN->value], true),
                fn ($query) => $query->whereIn('role_type', [
                    RoleValue::OPERATOR->value,
                    RoleValue::STAFF->value,
                    RoleValue::DRIVER->value,
                ])
            )
            ->when(
                $roleId === RoleValue::BRANCH_ADMIN->value,
                fn ($query) => $query->where('role_type', RoleValue::STAFF->value)
            )
            ->when(
                $roleId === RoleValue::RESTAURANT_ADMIN->value,
                fn ($query) => $query->where('role_type', RoleValue::OPERATOR->value)
            )
            ->get(['id', 'name']);

        $companies = Company::query()
            ->when(
                ! in_array($roleId, [RoleValue::SUPER_ADMIN->value, RoleValue::SYSTEM_ADMIN->value], true),
                fn ($query) => $query->where('id', $authUser?->company_id)
            )
            ->where('status', Status::ACTIVE->value)
            ->get(['id', 'name']);

        $branches = Branch::query()
            ->when(
                ! in_array($roleId, [RoleValue::SUPER_ADMIN->value, RoleValue::SYSTEM_ADMIN->value, RoleValue::COMPANY_ADMIN->value], true) && $authUser?->company_id !== null,
                fn ($query) => $query->where('company_id', $authUser->company_id)
            )
            ->when(
                $roleId === RoleValue::BRANCH_ADMIN->value,
                fn ($query) => $query->where('id', $authUser?->branch_id)
            )
            ->where('status', Status::ACTIVE->value)
            ->get(['id', 'name']);

        $restaurants = Restaurant::query()
            ->when(
                $roleId === RoleValue::COMPANY_ADMIN->value && $authUser?->company_id !== null,
                fn ($query) => $query->where('company_id', $authUser->company_id)
            )
            ->when(
                $roleId === RoleValue::RESTAURANT_ADMIN->value,
                fn ($query) => $query->where('id', $authUser?->restaurant_id)
            )
            ->where('status', Status::ACTIVE->value)
            ->get(['id', 'name']);

        return [
            'roles' => $this->toOptions($roles),
            'companies' => $this->toOptions($companies),
            'branches' => $this->toOptions($branches),
            'restaurants' => $this->toOptions($restaurants),
        ];
    }

    private function resolveUserScope(?User $currentUser): array
    {
        $roleId = (string) ($currentUser?->role_id ?? RoleValue::SUPER_ADMIN->value);

        return match ($roleId) {
            RoleValue::COMPANY_ADMIN->value => [
                [RoleValue::OPERATOR->value, RoleValue::STAFF->value, RoleValue::DRIVER->value],
                $currentUser?->company_id ? (int) $currentUser->company_id : null,
                null,
                null,
            ],
            RoleValue::BRANCH_ADMIN->value => [
                [RoleValue::STAFF->value],
                null,
                $currentUser?->branch_id ? (int) $currentUser->branch_id : null,
                null,
            ],
            RoleValue::RESTAURANT_ADMIN->value => [
                [RoleValue::OPERATOR->value],
                null,
                null,
                $currentUser?->restaurant_id ? (int) $currentUser->restaurant_id : null,
            ],
            default => [
                [RoleValue::OPERATOR->value, RoleValue::STAFF->value, RoleValue::DRIVER->value],
                null,
                null,
                null,
            ],
        };
    }

    private function resolveCompanyId(Request $request, ?User $user = null): ?int
    {
        if ($this->currentUser()?->company_id !== null) {
            return (int) $this->currentUser()->company_id;
        }

        if ($request->filled('company_id')) {
            return $request->integer('company_id');
        }

        return $user?->company_id !== null ? (int) $user->company_id : null;
    }

    private function resolveGuardName(string $roleId): string
    {
        return in_array($roleId, [RoleValue::OPERATOR->value, RoleValue::DRIVER->value], true)
            ? GuardType::WEB->value
            : GuardType::API->value;
    }
}
