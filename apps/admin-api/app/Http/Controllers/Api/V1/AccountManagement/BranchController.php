<?php

namespace App\Http\Controllers\Api\V1\AccountManagement;

use App\Enums\RoleValue;
use App\Enums\Status;
use App\Filters\BranchFilter;
use App\Filters\BranchSort;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\BranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Traits\GenerateCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BranchController extends ApiController
{
    use GenerateCode;

    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);
        $sortField = $request->input('sort');
        $sortDirection = $request->input('direction', 'desc');
        $filters = $request->only(['name', 'username', 'branch_id', 'status', 'company_name']);

        if (! $request->has('status')) {
            $filters['status'] = Status::ACTIVE->value;
        }

        $query = Branch::query()
            ->with(['companyName', 'username', 'createdBy', 'updatedBy', 'adminUser'])
            ->withCount('usersCount')
            ->whereNull('branches.deleted_at');

        $query = BranchFilter::apply($query, $filters);
        $query = BranchSort::apply($query, $sortField, $sortDirection, $filters);

        return $this->paginated($query->paginate($itemsPerPage)->withQueryString(), 'Branches retrieved successfully.');
    }

    public function options()
    {
        $companies = Company::query()
            ->where('status', Status::ACTIVE->value)
            ->orderByDesc('id')
            ->get(['id', 'name']);

        return $this->success([
            'companies' => $this->toOptions($companies),
        ], 'Branch form options retrieved successfully.');
    }

    public function store(BranchRequest $request)
    {
        $actorId = $this->requireActorId();
        $companyId = $this->currentUser()?->company_id ?? $request->integer('company_id');

        $branch = DB::transaction(function () use ($request, $actorId, $companyId) {
            $branch = Branch::create([
                'company_id' => $companyId,
                'name' => $request->input('name'),
                'location' => $request->input('location'),
                'remark' => $request->input('remark'),
                'status' => $request->input('status'),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            User::create([
                'code' => $this->generateCode(User::query(), 'U'),
                'name' => $request->input('username'),
                'password' => Hash::make($request->string('password')->toString()),
                'company_id' => $companyId,
                'branch_id' => $branch->id,
                'status' => $request->input('status'),
                'role_id' => RoleValue::BRANCH_ADMIN->value,
                'guard_name' => 'web',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            return $branch->load(['companyName', 'username', 'createdBy', 'updatedBy', 'adminUser']);
        });

        return $this->created($branch, 'Branch created successfully.');
    }

    public function show(Branch $branch)
    {
        $branch->load(['companyName', 'username', 'createdBy', 'updatedBy', 'adminUser']);
        $branch->loadCount('usersCount');

        return $this->success($branch, 'Branch retrieved successfully.');
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        $actorId = $this->requireActorId();

        $branch->update([
            'name' => $request->input('name'),
            'location' => $request->input('location'),
            'remark' => $request->input('remark'),
            'status' => $request->input('status'),
            'updated_by' => $actorId,
        ]);

        $userData = array_filter([
            'status' => $request->input('status'),
            'password' => $request->filled('password') ? Hash::make($request->string('password')->toString()) : null,
            'updated_by' => $actorId,
        ], fn ($value) => $value !== null);

        if ($userData !== []) {
            $branch->username?->update($userData);
        }

        $branch->load(['companyName', 'username', 'createdBy', 'updatedBy', 'adminUser']);
        $branch->loadCount('usersCount');

        return $this->success($branch, 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $branch->update(['deleted_by' => $this->requireActorId()]);
        $branch->delete();

        return $this->deleted('Branch deleted successfully.');
    }
}
