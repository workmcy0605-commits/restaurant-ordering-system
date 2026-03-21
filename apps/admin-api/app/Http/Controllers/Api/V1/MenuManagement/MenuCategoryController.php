<?php

namespace App\Http\Controllers\Api\V1\MenuManagement;

use App\Enums\CalendarWeek;
use App\Enums\Status;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\MenuCategoryRequest;
use App\Models\MenuCategory;
use App\Models\MenuServedDate;
use App\Models\Restaurant;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MenuCategoryController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);
        $filters = [
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'restaurant_id' => $request->input('restaurant_id'),
            'status' => $request->input('status'),
        ];

        $query = MenuCategory::query()
            ->with(['restaurant', 'createdBy', 'updatedBy'])
            ->whereNull('menu_categories.deleted_at');

        $query = \App\Filters\MenuCategoryFilter::apply($query, $filters);
        $query = \App\Filters\MenuCategorySort::apply($query, $request->input('sort'), $request->input('direction', 'desc'));

        return $this->paginated($query->paginate($itemsPerPage)->withQueryString(), 'Menu categories retrieved successfully.');
    }

    public function options()
    {
        $restaurants = Restaurant::query()
            ->where('status', Status::ACTIVE->value)
            ->orderByDesc('id')
            ->get(['id', 'name']);

        return $this->success([
            'restaurants' => $this->toOptions($restaurants),
            'repeat_options' => [
                ['label' => 'yes', 'value' => 'yes'],
                ['label' => 'no', 'value' => 'no'],
            ],
            'repeat_by_options' => [
                ['label' => 'Daily', 'value' => 'Daily'],
                ['label' => 'Weekly', 'value' => 'Weekly'],
                ['label' => 'Biweekly', 'value' => 'Biweekly'],
                ['label' => 'Monthly', 'value' => 'Monthly'],
            ],
            'weekdays' => collect(CalendarWeek::cases())->map(fn ($case) => [
                'label' => $case->value,
                'value' => $case->value,
            ])->values()->all(),
        ], 'Menu category options retrieved successfully.');
    }

    public function store(MenuCategoryRequest $request)
    {
        $data = $request->validated();

        $startDate = CarbonImmutable::createFromFormat('Y-m-d', $data['start_date']);
        $endDate = CarbonImmutable::createFromFormat('Y-m-d', $data['end_date']);
        abort_if($endDate->lessThan($startDate), 422, 'End date must be greater than or equal to start date.');

        $dates = $this->generateDates(
            $startDate->toDateString(),
            $endDate->toDateString(),
            $data['repeat'],
            $data['repeat_by'] ?? null,
            $data['select_day'] ?? null
        );

        if (strtolower($data['repeat']) === 'yes' && empty($dates)) {
            abort(422, 'No valid recurring dates were generated.');
        }

        $restaurant = Restaurant::query()->findOrFail($data['restaurant_id']);
        $actorId = $this->requireActorId();

        try {
            $menuCategory = DB::transaction(function () use ($data, $dates, $restaurant, $endDate, $actorId) {
                $menuCategory = MenuCategory::create([
                    'company_id' => $restaurant->company_id,
                    'restaurant_id' => $restaurant->id,
                    'name' => $data['name'],
                    'repeat' => $data['repeat'],
                    'repeat_by' => $data['repeat_by'] ?? null,
                    'remark' => $data['remark'] ?? null,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'status' => $data['status'],
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $menuCategory->servicedDates()->createMany(
                    collect($dates)->map(fn ($item) => [
                        'start_date' => $item['date'],
                        'end_date' => $endDate->toDateString(),
                        'select_day' => $item['weekday'],
                        'status' => $data['status'],
                        'created_by' => $actorId,
                        'updated_by' => $actorId,
                    ])->all()
                );

                return $menuCategory->load(['restaurant', 'servicedDates', 'createdBy', 'updatedBy']);
            });
        } catch (\Throwable $e) {
            Log::error('MenuCategory store failed', ['payload' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }

        return $this->created($menuCategory, 'Menu category created successfully.');
    }

    public function show(MenuCategory $menuCategory, Request $request)
    {
        $itemsPerPage = $request->integer('items', 50);
        $menuCategory->load(['restaurant', 'createdBy', 'updatedBy']);

        $servedDates = MenuServedDate::query()
            ->where('menu_category_id', $menuCategory->id)
            ->where('status', Status::ACTIVE->value)
            ->paginate($itemsPerPage)
            ->withQueryString();

        return $this->success([
            'menu_category' => $menuCategory,
            'served_dates' => $servedDates->items(),
        ], 'Menu category retrieved successfully.', 200, [
            'current_page' => $servedDates->currentPage(),
            'per_page' => $servedDates->perPage(),
            'total' => $servedDates->total(),
            'last_page' => $servedDates->lastPage(),
        ]);
    }

    public function update(MenuCategoryRequest $request, MenuCategory $menuCategory)
    {
        $data = $request->validated();
        $startDate = CarbonImmutable::createFromFormat('Y-m-d', $data['start_date']);
        $endDate = CarbonImmutable::createFromFormat('Y-m-d', $data['end_date']);
        abort_if($endDate->lessThan($startDate), 422, 'End date must be greater than or equal to start date.');

        $previousRepeat = $menuCategory->repeat ?? 'no';
        $previousRepeatBy = $menuCategory->repeat_by ?? null;
        $currentRepeat = $data['repeat'] ?? 'no';
        $currentRepeatBy = $data['repeat_by'] ?? null;
        $currentSelectDay = $data['select_day'] ?? null;

        $dateRange = MenuServedDate::query()
            ->where('menu_category_id', $menuCategory->id)
            ->whereNull('deleted_at')
            ->selectRaw('MIN(start_date) as start_date, MAX(end_date) as end_date')
            ->first();

        $isDateChanged = ($dateRange?->start_date !== $startDate->toDateString()) || ($dateRange?->end_date !== $endDate->toDateString());
        $repeatChanged = $previousRepeat !== $currentRepeat;
        $repeatByChanged = $previousRepeatBy !== $currentRepeatBy;

        $existingDays = $menuCategory->servicedDates()
            ->whereNull('deleted_at')
            ->pluck('select_day')
            ->filter()
            ->flatMap(fn ($day) => explode(',', $day))
            ->map(fn ($day) => trim($day))
            ->sort()
            ->values()
            ->all();

        $inputDays = collect($currentSelectDay ?? [])->sort()->values()->all();
        $selectDayChanged = $existingDays !== $inputDays;
        $needRebuildServedDates = $isDateChanged || $repeatChanged || ($currentRepeat === 'yes' && ($repeatByChanged || $selectDayChanged));

        $dates = [];
        if ($needRebuildServedDates) {
            if ($currentRepeat === 'yes') {
                $dates = $this->generateDates(
                    $data['start_date'],
                    $data['end_date'],
                    $currentRepeat,
                    $currentRepeatBy,
                    $currentSelectDay
                );

                abort_if(empty($dates), 422, 'No valid recurring dates were generated.');
            } else {
                $dates[] = ['date' => $data['start_date'], 'weekday' => Carbon::parse($data['start_date'])->format('l')];
                if ($startDate->toDateString() !== $endDate->toDateString()) {
                    $dates[] = ['date' => $data['end_date'], 'weekday' => Carbon::parse($data['end_date'])->format('l')];
                }
            }
        }

        $restaurant = Restaurant::query()->findOrFail($data['restaurant_id']);
        $actorId = $this->requireActorId();

        try {
            DB::transaction(function () use ($menuCategory, $data, $dates, $needRebuildServedDates, $endDate, $restaurant, $actorId) {
                $menuCategory->update([
                    'company_id' => $restaurant->company_id,
                    'restaurant_id' => $restaurant->id,
                    'name' => $data['name'],
                    'repeat' => $data['repeat'] ?? 'no',
                    'repeat_by' => $data['repeat_by'] ?? null,
                    'remark' => $data['remark'] ?? null,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'status' => $data['status'],
                    'updated_by' => $actorId,
                ]);

                if ($needRebuildServedDates) {
                    $menuCategory->servicedDates()->get()->each(function ($servedDate) use ($actorId) {
                        $servedDate->update(['deleted_by' => $actorId, 'updated_by' => $actorId]);
                        $servedDate->delete();
                    });

                    if ($dates !== []) {
                        $menuCategory->servicedDates()->createMany(
                            collect($dates)->map(fn ($item) => [
                                'start_date' => $item['date'],
                                'end_date' => $endDate->toDateString(),
                                'select_day' => $item['weekday'],
                                'status' => $data['status'],
                                'created_by' => $actorId,
                                'updated_by' => $actorId,
                            ])->all()
                        );
                    }
                } else {
                    $menuCategory->servicedDates()->update(['status' => $data['status'], 'updated_by' => $actorId]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('MenuCategory update failed', ['menuCategory_id' => $menuCategory->id, 'message' => $e->getMessage()]);
            throw $e;
        }

        $menuCategory->load(['restaurant', 'servicedDates', 'createdBy', 'updatedBy']);

        return $this->success($menuCategory, 'Menu category updated successfully.');
    }

    public function destroy(MenuCategory $menuCategory)
    {
        $menuCategory->update(['deleted_by' => $this->requireActorId()]);
        $menuCategory->delete();

        return $this->deleted('Menu category deleted successfully.');
    }

    protected function generateDates(string $sDate, ?string $eDate, string $repeat, ?string $repeatBy, ?array $selectDays): array
    {
        $startDate = Carbon::parse($sDate)->startOfDay();
        $endDate = $eDate ? Carbon::parse($eDate)->endOfDay() : null;

        if ($endDate && $endDate->lt($startDate)) {
            return [];
        }

        if (strtolower($repeat) !== 'yes') {
            return [[
                'date' => $startDate->toDateString(),
                'weekday' => $startDate->format('l'),
            ]];
        }

        if (! $repeatBy) {
            return [];
        }

        return match (strtolower($repeatBy)) {
            'daily' => $this->generateDaily($startDate, $endDate),
            'weekly' => $this->generateWeekly($startDate, $endDate, $selectDays, 1),
            'biweekly' => $this->generateWeekly($startDate, $endDate, $selectDays, 2),
            'monthly' => $this->generateMonthly($startDate, $endDate, $selectDays),
            default => [],
        };
    }

    protected function generateDaily(Carbon $startDate, ?Carbon $endDate): array
    {
        if (! $endDate) {
            return [[
                'date' => $startDate->toDateString(),
                'weekday' => $startDate->format('l'),
            ]];
        }

        if ($endDate->lt($startDate)) {
            return [];
        }

        return collect(CarbonPeriod::create($startDate, '1 day', $endDate))
            ->map(fn (Carbon $date) => ['date' => $date->toDateString(), 'weekday' => $date->format('l')])
            ->values()
            ->all();
    }

    protected function generateWeekly(Carbon $startDate, ?Carbon $endDate, ?array $selectDays, int $weekInterval = 1): array
    {
        if (! $endDate || $endDate->lt($startDate)) {
            return [];
        }

        $daysDiff = $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay());
        $selectDays = collect($selectDays ?? [])->filter()->map(fn ($day) => ucfirst(strtolower(trim($day))))->unique()->values();

        if ($selectDays->isEmpty() || $daysDiff < ($weekInterval * 7)) {
            return [];
        }

        $records = collect();
        foreach ($selectDays as $weekday) {
            $firstDate = $startDate->format('l') === $weekday ? $startDate->copy() : $startDate->copy()->next($weekday);
            foreach (CarbonPeriod::create($firstDate, "{$weekInterval} week", $endDate) as $date) {
                if ($date->lte($endDate)) {
                    $records->push(['date' => $date->toDateString(), 'weekday' => $weekday]);
                }
            }
        }

        return $records->unique('date')->sortBy('date')->values()->all();
    }

    protected function generateMonthly(Carbon $startDate, ?Carbon $endDate, ?array $selectDays): array
    {
        $selectDays = collect($selectDays ?? [])->filter()->map(fn ($day) => ucfirst(strtolower(trim($day))))->unique()->values();

        if ($selectDays->isEmpty() || ! $endDate || $endDate->lt($startDate)) {
            return [];
        }

        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->startOfDay();
        $daysDiff = $startDate->diffInDays($endDate);
        if ($startDate->isSameMonth($endDate) && $daysDiff < 28) {
            return [];
        }

        $records = collect();
        $currentMonth = $startDate->copy()->startOfMonth();

        while ($currentMonth->lte($endDate)) {
            foreach (range(1, $currentMonth->daysInMonth) as $dayNum) {
                $date = $currentMonth->copy()->day($dayNum)->startOfDay();
                if ($date->lt($startDate) || $date->gt($endDate)) {
                    continue;
                }
                foreach ($selectDays as $weekday) {
                    if ($date->format('l') === $weekday) {
                        $records->push(['date' => $date->toDateString(), 'weekday' => $weekday]);
                    }
                }
            }
            $currentMonth->addMonthNoOverflow();
        }

        return $records->unique('date')->sortBy('date')->values()->all();
    }
}
