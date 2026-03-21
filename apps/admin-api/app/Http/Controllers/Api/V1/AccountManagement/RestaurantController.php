<?php

namespace App\Http\Controllers\Api\V1\AccountManagement;

use App\Enums\RoleValue;
use App\Enums\Status;
use App\Filters\RestaurantFilter;
use App\Filters\RestaurantSort;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\RestaurantRequest;
use App\Models\Company;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Traits\GenerateCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RestaurantController extends ApiController
{
    use GenerateCode;

    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);
        $filters = $request->only(['name', 'username', 'restaurant_id', 'status']);
        $sortField = $request->input('sort');
        $sortDirection = $request->input('direction', 'desc');

        if (! $request->has('status')) {
            $filters['status'] = Status::ACTIVE->value;
        }

        $query = Restaurant::query()
            ->with(['companyName', 'username', 'createdBy', 'updatedBy', 'adminUser'])
            ->whereNull('deleted_at');

        $query = RestaurantFilter::apply($query, $filters);
        $query = RestaurantSort::apply($query, $sortField, $sortDirection, $filters);

        return $this->paginated($query->paginate($itemsPerPage)->withQueryString(), 'Restaurants retrieved successfully.');
    }

    public function options()
    {
        $roles = Role::query()
            ->where('status', Status::ACTIVE->value)
            ->where('role_type', RoleValue::RESTAURANT_ADMIN->value)
            ->get(['id', 'name']);

        $companies = Company::query()
            ->where('status', Status::ACTIVE->value)
            ->orderByDesc('id')
            ->get(['id', 'name']);

        return $this->success([
            'roles' => $this->toOptions($roles),
            'companies' => $this->toOptions($companies),
        ], 'Restaurant form options retrieved successfully.');
    }

    public function store(RestaurantRequest $request)
    {
        $actorId = $this->requireActorId();
        $companyId = $this->currentUser()?->company_id ?? $request->integer('company_id');

        $restaurant = DB::transaction(function () use ($request, $actorId, $companyId) {
            $restaurant = Restaurant::create([
                'company_id' => $companyId,
                'name' => $request->input('name'),
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
                'restaurant_id' => $restaurant->id,
                'status' => $request->input('status'),
                'role_id' => RoleValue::RESTAURANT_ADMIN->value,
                'guard_name' => 'web',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            return $restaurant->load(['companyName', 'username', 'createdBy', 'updatedBy', 'adminUser']);
        });

        return $this->created($restaurant, 'Restaurant created successfully.');
    }

    public function show(Restaurant $restaurant)
    {
        $restaurant->load(['companyName', 'username', 'createdBy', 'updatedBy', 'adminUser']);

        return $this->success($restaurant, 'Restaurant retrieved successfully.');
    }

    public function update(RestaurantRequest $request, Restaurant $restaurant)
    {
        $actorId = $this->requireActorId();

        $restaurant->update([
            'name' => $request->input('name'),
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
            $restaurant->username?->update($userData);
        }

        $restaurant->load(['companyName', 'username', 'createdBy', 'updatedBy', 'adminUser']);

        return $this->success($restaurant, 'Restaurant updated successfully.');
    }

    public function destroy(Restaurant $restaurant)
    {
        $restaurant->update(['deleted_by' => $this->requireActorId()]);
        $restaurant->delete();

        return $this->deleted('Restaurant deleted successfully.');
    }
}
