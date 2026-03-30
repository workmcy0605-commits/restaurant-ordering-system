<?php

namespace App\Models;

use App\Enums\RoleValue;
use App\Traits\Action\TracksUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, TracksUserActions;

    protected $fillable = [
        'code',
        'name',
        'fe_lang',
        'guard_name',
        'password',
        'credit',
        'first_time_login',
        'last_time_login',
        'last_ip_login',
        'role_id',
        'initial_credit',
        'company_id',
        'branch_id',
        'restaurant_id',
        'amount',
        'fcm_token',
        'nickname',
        'contact_number',
        'avatar',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected $casts = [
        'password' => 'hashed',
        'last_time_login' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'is_two_factor_enabled' => 'boolean',
        'credit' => 'decimal:2',
        'initial_credit' => 'decimal:2',
    ];

    protected $cachedPermissions;

    protected static function booted(): void
    {
        static::updating(function (self $user) {
            if ($user->isDirty('role_id')) {
                $originalRoleId = $user->getOriginal('role_id');

                if ($originalRoleId) {
                    Cache::forget('role_permissions_'.$originalRoleId);
                }

                if ($user->role_id) {
                    Cache::forget('role_permissions_'.$user->role_id);
                }
            }
        });
    }

    public function roleName()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function companyName()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function branchName()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function restaurantName()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(self::class, 'created_by', 'id')->withTrashed();
    }

    public function updatedBy()
    {
        return $this->belongsTo(self::class, 'updated_by', 'id')->withTrashed();
    }

    public function getCachedPermissions()
    {
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }

        if (! $this->role_id) {
            $this->cachedPermissions = collect();

            return $this->cachedPermissions;
        }

        $cacheKey = 'role_permissions_'.$this->role_id;

        $cachedPermissions = Cache::get($cacheKey);

        if ($cachedPermissions === null) {
            $cachedPermissions = DB::table('role_permissions')
                ->where('role_id', $this->role_id)
                ->whereNull('deleted_at')
                ->pluck('permission_name')
                ->values()
                ->all();

            Cache::forever($cacheKey, $cachedPermissions);
        }

        $this->cachedPermissions = $this->normalizeCachedPermissions($cachedPermissions);

        if (! is_array($cachedPermissions) || $cachedPermissions !== $this->cachedPermissions->all()) {
            Cache::forever($cacheKey, $this->cachedPermissions->all());
        }

        return $this->cachedPermissions;
    }

    public function hasPermissionTo($permissionName): bool
    {
        if ($this->role_id === RoleValue::SUPER_ADMIN->value) {
            return true;
        }

        return collect($this->getCachedPermissions())->contains($permissionName);
    }

    public function hasRole($roleName): bool
    {
        return $this->roleName !== null && $this->roleName->name === $roleName;
    }

    public function hasRoleIn(array $roleNames): bool
    {
        if ($this->roleName === null) {
            return false;
        }

        return in_array($this->roleName->name, $roleNames, true);
    }

    public static function refreshRolePermissionsCache($roleId): void
    {
        $cacheKey = 'role_permissions_'.$roleId;

        Cache::forget($cacheKey);

        $permissions = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->whereNull('deleted_at')
            ->pluck('permission_name')
            ->values()
            ->all();

        Cache::forever($cacheKey, $permissions);
    }

    public function getOrderType(): string
    {
        return $this->restaurant_id ? 'restaurant' : 'user';
    }

    private function normalizeCachedPermissions(mixed $permissions): Collection
    {
        if ($permissions instanceof Collection) {
            return $permissions
                ->filter(fn ($permission) => is_string($permission) && $permission !== '')
                ->values();
        }

        if (is_array($permissions)) {
            return collect($permissions)
                ->filter(fn ($permission) => is_string($permission) && $permission !== '')
                ->values();
        }

        if ($permissions instanceof \Traversable) {
            return collect(iterator_to_array($permissions))
                ->filter(fn ($permission) => is_string($permission) && $permission !== '')
                ->values();
        }

        if (is_object($permissions)) {
            $rawPermissions = (array) $permissions;

            foreach ($rawPermissions as $value) {
                if (is_array($value)) {
                    return collect($value)
                        ->filter(fn ($permission) => is_string($permission) && $permission !== '')
                        ->values();
                }
            }
        }

        return collect();
    }
}
