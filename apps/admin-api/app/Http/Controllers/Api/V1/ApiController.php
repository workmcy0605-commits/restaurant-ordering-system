<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleValue;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    protected function success(mixed $data = null, string $message = 'Success.', int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'code' => '0000',
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully.'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function deleted(string $message = 'Deleted successfully.'): JsonResponse
    {
        return $this->success(null, $message);
    }

    protected function paginated(LengthAwarePaginator $paginator, string $message = 'Success.'): JsonResponse
    {
        return $this->success(
            $paginator->items(),
            $message,
            200,
            [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
        );
    }

    protected function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected function currentRoleId(): string
    {
        return (string) ($this->currentUser()?->role_id ?? RoleValue::SUPER_ADMIN->value);
    }

    protected function actorId(): ?int
    {
        if ($this->currentUser() !== null) {
            return (int) $this->currentUser()->id;
        }

        if (! app()->environment(['local', 'testing'])) {
            return null;
        }

        $adminId = User::query()
            ->whereIn('role_id', [RoleValue::SUPER_ADMIN->value, RoleValue::SYSTEM_ADMIN->value])
            ->orderBy('id')
            ->value('id');

        if ($adminId !== null) {
            return (int) $adminId;
        }

        $firstUserId = User::query()->orderBy('id')->value('id');

        return $firstUserId !== null ? (int) $firstUserId : null;
    }

    protected function requireActorId(): int
    {
        $actorId = $this->actorId();

        abort_if($actorId === null, 409, 'No actor user is available. Create or authenticate an admin user first.');

        return $actorId;
    }

    protected function toOptions(Collection $items, string $labelKey = 'name', string $valueKey = 'id'): array
    {
        return $items
            ->map(fn ($item) => [
                'label' => $item->{$labelKey},
                'value' => $item->{$valueKey},
            ])
            ->values()
            ->all();
    }

    protected function permissionActions(bool $includeBranchAdminPermissions = true): array
    {
        $query = Permission::query()->select('id', 'name');

        if (! $includeBranchAdminPermissions) {
            $query->where('is_branchadmin', '!=', 1);
        }

        return $query
            ->get()
            ->groupBy(fn ($item) => explode('.', $item->name)[0])
            ->map(function ($group, $section) {
                return [
                    'name' => $section,
                    'actions' => $group
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'action' => explode('.', $item->name)[1] ?? '',
                        ])
                        ->unique('id')
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
