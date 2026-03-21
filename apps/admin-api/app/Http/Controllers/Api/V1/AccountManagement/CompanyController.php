<?php

namespace App\Http\Controllers\Api\V1\AccountManagement;

use App\Enums\RoleValue;
use App\Enums\SelectionType;
use App\Enums\Status;
use App\Filters\CompanyFilter;
use App\Filters\CompanySort;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\CompanyRequest;
use App\Models\Company;
use App\Models\Holiday;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Selection;
use App\Models\User;
use App\Traits\GenerateCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends ApiController
{
    use GenerateCode;

    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);
        $sortField = $request->input('sort');
        $sortDirection = $request->input('direction', 'desc');
        $filterKeys = ['name', 'uname', 'company_id', 'status'];
        $filters = $request->only($filterKeys);

        if (! $request->has('status')) {
            $filters['status'] = Status::ACTIVE->value;
        }

        $companies = Company::query()
            ->with(['username', 'createdBy', 'updatedBy', 'paymentMethods'])
            ->when($filters !== [], fn ($query) => CompanyFilter::apply($query, $filters))
            ->when($sortField, fn ($query) => CompanySort::apply($query, $sortField, $sortDirection, $filters))
            ->paginate($itemsPerPage)
            ->appends($request->only([...$filterKeys, 'items', 'sort', 'direction']));

        return $this->paginated($companies, 'Companies retrieved successfully.');
    }

    public function options()
    {
        $paymentMethods = PaymentMethod::query()
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        $selections = Selection::query()
            ->whereIn('category', [SelectionType::PERIOD->value, SelectionType::DAY->value])
            ->get()
            ->groupBy('category');

        $roles = Role::query()
            ->where('status', Status::ACTIVE->value)
            ->where('role_type', RoleValue::COMPANY_ADMIN->value)
            ->get(['id', 'name']);

        $periods = $this->toOptions($selections->get(SelectionType::PERIOD->value, collect()), 'value', 'id');
        $days = $this->toOptions($selections->get(SelectionType::DAY->value, collect()), 'value', 'id');

        return $this->success([
            'payment_methods' => $this->toOptions($paymentMethods),
            'periods' => $periods,
            'days' => $days,
            'day_numbers' => collect(range(1, 28))->map(fn ($day) => ['label' => (string) $day, 'value' => $day])->all(),
            'roles' => $this->toOptions($roles),
        ], 'Company form options retrieved successfully.');
    }

    public function store(CompanyRequest $request)
    {
        $validated = $request->validated();
        $actorId = $this->requireActorId();

        $company = DB::transaction(function () use ($request, $validated, $actorId) {
            $creditRefreshDay = $this->resolveCreditRefreshValue($request);

            $company = Company::create([
                'name' => $validated['name'],
                'remark' => $validated['remark'] ?? null,
                'status' => $validated['status'],
                'place_order_weekend' => $request->boolean('place_order_weekend'),
                'place_order_holiday' => $request->boolean('place_order_holiday'),
                'payment_method_id' => $validated['payment_method_id'],
                'order_limit_per_meal' => $validated['order_per_meal_time'] ?? null,
                'credit_refresh_period' => $validated['period'] ?? null,
                'credit_refresh_value' => $creditRefreshDay,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            User::create([
                'code' => $this->generateCode(User::query(), 'U'),
                'name' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'company_id' => $company->id,
                'status' => $validated['status'],
                'role_id' => RoleValue::COMPANY_ADMIN->value,
                'guard_name' => 'web',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->syncHolidays($company, $validated['holidays'] ?? [], $validated['status'], $actorId);

            return $company->load(['username', 'paymentMethods', 'holidays']);
        });

        return $this->created($company, 'Company created successfully.');
    }

    public function show(Company $company)
    {
        $company->loadMissing(['username', 'paymentMethods', 'branches', 'restaurants', 'holidays', 'createdBy', 'updatedBy']);

        return $this->success($company, 'Company retrieved successfully.');
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $validated = $request->validated();
        $actorId = $this->requireActorId();

        DB::transaction(function () use ($request, $validated, $company, $actorId) {
            $creditRefreshDay = $this->resolveCreditRefreshValue($request);

            $company->update([
                'remark' => $validated['remark'] ?? null,
                'payment_method_id' => $validated['payment_method_id'],
                'status' => $validated['status'],
                'place_order_weekend' => $request->boolean('place_order_weekend'),
                'place_order_holiday' => $request->boolean('place_order_holiday'),
                'order_limit_per_meal' => $validated['order_per_meal_time'] ?? null,
                'credit_refresh_period' => $validated['period'] ?? null,
                'credit_refresh_value' => $creditRefreshDay,
                'updated_by' => $actorId,
            ]);

            $userData = array_filter([
                'status' => $validated['status'] ?? null,
                'password' => $request->filled('password') ? Hash::make($validated['password']) : null,
                'updated_by' => $actorId,
            ], fn ($value) => $value !== null);

            if ($userData !== []) {
                $company->username?->update($userData);
            }

            $this->syncHolidays($company, $validated['holidays'] ?? [], $validated['status'], $actorId);
        });

        $company->load(['username', 'paymentMethods', 'holidays', 'branches', 'restaurants', 'createdBy', 'updatedBy']);

        return $this->success($company, 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        $company->update(['deleted_by' => $this->requireActorId()]);
        $company->delete();

        return $this->deleted('Company deleted successfully.');
    }

    private function resolveCreditRefreshValue(Request $request): ?string
    {
        return match ($request->integer('period')) {
            2 => $request->input('day'),
            3 => $request->input('day_number'),
            default => null,
        };
    }

    private function syncHolidays(Company $company, array $holidays, string $status, int $actorId): void
    {
        $payload = collect($holidays)
            ->filter(fn ($holiday) => ! empty($holiday['date']))
            ->map(fn ($holiday) => [
                'id' => $holiday['id'] ?? null,
                'name' => $holiday['name'] ?? '',
                'date' => $holiday['date'],
            ])
            ->values();

        $existingIds = $payload->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $query = $company->holidays();
        if ($existingIds !== []) {
            $query->whereNotIn('id', $existingIds);
        }

        $query->get()->each(function (Holiday $holiday) use ($actorId) {
            $holiday->update(['deleted_by' => $actorId]);
            $holiday->delete();
        });

        $payload->each(function (array $holiday) use ($company, $status, $actorId) {
            $values = [
                'company_id' => $company->id,
                'name' => $holiday['name'],
                'date' => $holiday['date'],
                'status' => $status,
                'updated_by' => $actorId,
            ];

            if (! empty($holiday['id'])) {
                $company->holidays()->where('id', $holiday['id'])->update($values);

                return;
            }

            Holiday::create($values + [
                'created_by' => $actorId,
            ]);
        });
    }
}
