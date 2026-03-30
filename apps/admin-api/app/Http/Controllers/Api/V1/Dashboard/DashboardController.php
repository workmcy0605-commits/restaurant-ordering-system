<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Branch;
use App\Models\Company;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends ApiController
{
    private const OPEN_ORDER_ITEM_STATUSES = [
        'CREATED',
        'PENDING',
        'PENDING_VERIFICATION',
        'APPROVED',
        'PROCESSING',
        'COOKING',
        'READY',
        'ON_DELIVERY',
    ];

    private const NON_REVENUE_STATUSES = [
        'CANCELLED',
        'CANCEL',
        'EXPIRED',
        'FAIL',
        'REJECTED',
        'ADD_TO_CART',
    ];

    public function overview(): JsonResponse
    {
        $user = $this->currentUser();

        if (! $user instanceof User) {
            return $this->error('8888', 'Please log in again.');
        }

        $scope = $this->resolveScope($user);

        $payload = [
            'scope' => $scope,
            'latestActivity' => null,
            'summary' => [
                'restaurantCount' => $this->restaurantCount($user),
                'menuItemCount' => $this->menuItemCount(),
                'totalOrders' => 0,
                'totalOrderItems' => 0,
                'pendingOrderItems' => 0,
                'completedOrderItems' => 0,
                'totalOrderValue' => 0.0,
            ],
            'statusBreakdown' => [],
            'restaurantPerformance' => [],
            'topMenuItems' => [],
            'recentOrderItems' => [],
        ];

        if (! Schema::hasTable('orders') || ! Schema::hasTable('order_items')) {
            return $this->success($payload, 'Dashboard overview retrieved successfully.');
        }

        $orderItemsQuery = $this->baseOrderItemsQuery($user);

        $payload['summary']['totalOrders'] = $this->countDistinctOrders($orderItemsQuery);
        $payload['summary']['totalOrderItems'] = (int) (clone $orderItemsQuery)->count('oi.id');
        $payload['summary']['pendingOrderItems'] = (int) (clone $orderItemsQuery)
            ->whereIn('oi.status', self::OPEN_ORDER_ITEM_STATUSES)
            ->count('oi.id');
        $payload['summary']['completedOrderItems'] = (int) (clone $orderItemsQuery)
            ->where('oi.status', 'COMPLETED')
            ->count('oi.id');
        $payload['summary']['totalOrderValue'] = $this->sumOrderValue($orderItemsQuery);

        $latestOrderDate = (clone $orderItemsQuery)->max('oi.order_date');
        $payload['scope']['latestOrderDate'] = $latestOrderDate;
        $payload['latestActivity'] = $this->latestActivity($orderItemsQuery, $latestOrderDate);
        $payload['statusBreakdown'] = $this->statusBreakdown($orderItemsQuery);
        $payload['restaurantPerformance'] = $this->restaurantPerformance($orderItemsQuery);
        $payload['topMenuItems'] = $this->topMenuItems($orderItemsQuery);
        $payload['recentOrderItems'] = $this->recentOrderItems($orderItemsQuery);

        return $this->success($payload, 'Dashboard overview retrieved successfully.');
    }

    private function resolveScope(User $user): array
    {
        if ($user->restaurant_id) {
            $restaurant = Restaurant::withoutGlobalScopes()->find($user->restaurant_id);
            $label = $restaurant?->name ?: 'Restaurant';

            return [
                'level' => 'restaurant',
                'label' => $label,
                'companyId' => $user->company_id,
                'branchId' => $user->branch_id,
                'restaurantId' => $user->restaurant_id,
                'contextNote' => sprintf('Showing restaurant order volume, menu demand, and kitchen flow for %s.', $label),
                'latestOrderDate' => null,
            ];
        }

        if ($user->branch_id) {
            $branch = Branch::withoutGlobalScopes()->find($user->branch_id);
            $label = $branch?->name ?: 'Branch';

            return [
                'level' => 'branch',
                'label' => $label,
                'companyId' => $user->company_id,
                'branchId' => $user->branch_id,
                'restaurantId' => null,
                'contextNote' => sprintf('Showing branch-linked customer orders, restaurant demand, and menu activity for %s.', $label),
                'latestOrderDate' => null,
            ];
        }

        if ($user->company_id) {
            $company = Company::withoutGlobalScopes()->find($user->company_id);
            $label = $company?->name ?: 'Company';

            return [
                'level' => 'company',
                'label' => $label,
                'companyId' => $user->company_id,
                'branchId' => null,
                'restaurantId' => null,
                'contextNote' => sprintf('Showing company-wide restaurant performance, order demand, and menu activity for %s.', $label),
                'latestOrderDate' => null,
            ];
        }

        return [
            'level' => 'global',
            'label' => 'All Client Operations',
            'companyId' => null,
            'branchId' => null,
            'restaurantId' => null,
            'contextNote' => 'Showing operational activity across all restaurants and client accounts.',
            'latestOrderDate' => null,
        ];
    }

    private function restaurantCount(User $user): int
    {
        if ($user->restaurant_id) {
            return Restaurant::withoutGlobalScopes()
                ->whereKey($user->restaurant_id)
                ->count();
        }

        return Restaurant::query()->count();
    }

    private function menuItemCount(): int
    {
        return MenuItem::query()->count();
    }

    private function baseOrderItemsQuery(User $user): Builder
    {
        $query = DB::table('order_items as oi')
            ->leftJoin('restaurants as r', 'r.id', '=', 'oi.restaurant_id')
            ->whereNull('oi.deleted_at');

        if ($user->restaurant_id) {
            $query->where('oi.restaurant_id', $user->restaurant_id);

            return $query;
        }

        if ($user->branch_id) {
            $query
                ->join('orders as o', function ($join) {
                    $join->on('o.id', '=', 'oi.order_id')
                        ->whereNull('o.deleted_at');
                })
                ->join('users as order_user', function ($join) {
                    $join->on('order_user.id', '=', 'o.user_id')
                        ->whereNull('order_user.deleted_at');
                })
                ->where('order_user.branch_id', $user->branch_id);

            return $query;
        }

        if ($user->company_id) {
            $query->where('oi.company_id', $user->company_id);
        }

        return $query;
    }

    private function countDistinctOrders(Builder $query): int
    {
        return (int) ((clone $query)
            ->selectRaw('COUNT(DISTINCT oi.order_id) as aggregate')
            ->value('aggregate') ?? 0);
    }

    private function sumOrderValue(Builder $query): float
    {
        $sum = (clone $query)
            ->where(function ($builder) {
                $builder
                    ->whereNull('oi.status')
                    ->orWhereNotIn('oi.status', self::NON_REVENUE_STATUSES);
            })
            ->sum('oi.price');

        return round((float) $sum, 2);
    }

    private function latestActivity(Builder $query, ?string $latestOrderDate): ?array
    {
        if (! $latestOrderDate) {
            return null;
        }

        $latestQuery = (clone $query)->whereDate('oi.order_date', $latestOrderDate);

        return [
            'date' => $latestOrderDate,
            'orders' => $this->countDistinctOrders($latestQuery),
            'items' => (int) (clone $latestQuery)->count('oi.id'),
            'revenue' => $this->sumOrderValue($latestQuery),
        ];
    }

    private function statusBreakdown(Builder $query): array
    {
        return (clone $query)
            ->select('oi.status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('oi.status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) ($row->status ?: 'UNKNOWN'),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function restaurantPerformance(Builder $query): array
    {
        return (clone $query)
            ->select('oi.restaurant_id', 'r.name as restaurant_name')
            ->selectRaw('COUNT(DISTINCT oi.order_id) as order_count')
            ->selectRaw('COUNT(*) as item_quantity')
            ->selectRaw('ROUND(SUM(COALESCE(oi.price, 0)), 2) as revenue')
            ->groupBy('oi.restaurant_id', 'r.name')
            ->orderByDesc('item_quantity')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'restaurantId' => $row->restaurant_id !== null ? (int) $row->restaurant_id : null,
                'restaurantName' => (string) ($row->restaurant_name ?: 'Unknown restaurant'),
                'orderCount' => (int) $row->order_count,
                'itemQuantity' => (int) $row->item_quantity,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();
    }

    private function topMenuItems(Builder $query): array
    {
        return (clone $query)
            ->select('oi.menu_item_id', 'oi.name', 'r.name as restaurant_name')
            ->selectRaw('COUNT(*) as quantity')
            ->selectRaw('ROUND(SUM(COALESCE(oi.price, 0)), 2) as revenue')
            ->groupBy('oi.menu_item_id', 'oi.name', 'r.name')
            ->orderByDesc('quantity')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'menuItemId' => $row->menu_item_id !== null ? (int) $row->menu_item_id : null,
                'itemName' => (string) ($row->name ?: 'Unknown item'),
                'restaurantName' => (string) ($row->restaurant_name ?: 'Unknown restaurant'),
                'quantity' => (int) $row->quantity,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();
    }

    private function recentOrderItems(Builder $query): array
    {
        return (clone $query)
            ->select(
                'oi.id',
                'oi.order_id',
                'oi.name',
                'oi.price',
                'oi.status',
                'oi.order_date',
                'oi.created_at',
                'r.name as restaurant_name'
            )
            ->orderByDesc('oi.created_at')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'orderItemId' => (int) $row->id,
                'orderId' => (int) $row->order_id,
                'itemName' => (string) ($row->name ?: 'Unknown item'),
                'restaurantName' => (string) ($row->restaurant_name ?: 'Unknown restaurant'),
                'status' => (string) ($row->status ?: 'UNKNOWN'),
                'price' => round((float) $row->price, 2),
                'orderDate' => $row->order_date,
                'createdAt' => $row->created_at,
            ])
            ->values()
            ->all();
    }
}
