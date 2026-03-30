<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\RoleValue;
use App\Enums\Status;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AdminApiToken;
use App\Models\User;
use App\Support\Auth\AdminApiTokenBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends ApiController
{
    public function __construct(private readonly AdminApiTokenBroker $tokenBroker) {}

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'userName' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->error('1000', $validator->errors()->first());
        }

        $userName = trim((string) $request->input('userName'));
        $password = (string) $request->input('password');

        $user = User::query()
            ->where(function ($query) use ($userName) {
                $query
                    ->where('name', $userName)
                    ->orWhere('nickname', $userName)
                    ->orWhere('code', $userName);
            })
            ->first();

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            return $this->error('1001', 'Invalid username or password.');
        }

        if ($user->status !== Status::ACTIVE->value) {
            return $this->error('1002', 'This account is inactive.');
        }

        if (! $this->canAccessAdminPortal($user)) {
            return $this->error('1003', 'This user is not allowed to access the admin portal.');
        }

        $tokenPair = $this->tokenBroker->issueTokenPair($user, $request->ip(), $request->userAgent());

        $user->forceFill([
            'first_time_login' => 0,
            'last_time_login' => now(),
            'last_ip_login' => $request->ip(),
        ])->save();

        return $this->success([
            'token' => $tokenPair['token'],
            'refreshToken' => $tokenPair['refreshToken'],
        ], 'Login successful.');
    }

    public function me(): JsonResponse
    {
        $user = $this->currentUser();

        if (! $user instanceof User) {
            return $this->error('8888', 'Please log in again.');
        }

        return $this->success($this->transformUserInfo($user));
    }

    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refreshToken' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->error('1000', $validator->errors()->first());
        }

        $resolution = $this->tokenBroker->resolveRefreshToken((string) $request->input('refreshToken'));

        if ($resolution['status'] !== 'valid') {
            return $this->error('8888', 'Refresh token expired. Please log in again.');
        }

        /** @var User $user */
        $user = $resolution['user'];
        /** @var AdminApiToken $token */
        $token = $resolution['token'];

        if (! $this->canAccessAdminPortal($user)) {
            $this->tokenBroker->revoke($token);

            return $this->error('8888', 'Please log in again.');
        }

        $this->tokenBroker->revoke($token);

        $tokenPair = $this->tokenBroker->issueTokenPair($user, $request->ip(), $request->userAgent());

        return $this->success([
            'token' => $tokenPair['token'],
            'refreshToken' => $tokenPair['refreshToken'],
        ], 'Token refreshed successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('adminApiToken');

        if ($token instanceof AdminApiToken) {
            $this->tokenBroker->revoke($token);
        }

        return $this->success(null, 'Logged out successfully.');
    }

    private function canAccessAdminPortal(User $user): bool
    {
        return in_array((string) $user->role_id, [
            RoleValue::SUPER_ADMIN->value,
            RoleValue::SYSTEM_ADMIN->value,
            RoleValue::COMPANY_ADMIN->value,
            RoleValue::BRANCH_ADMIN->value,
            RoleValue::RESTAURANT_ADMIN->value,
            RoleValue::OPERATOR->value,
        ], true);
    }

    private function transformUserInfo(User $user): array
    {
        return [
            'userId' => (string) $user->id,
            'userName' => (string) ($user->nickname ?: $user->name),
            'roles' => [$this->transformRole($user)],
            'buttons' => $user->getCachedPermissions()->values()->all(),
        ];
    }

    private function transformRole(User $user): string
    {
        return match ((string) $user->role_id) {
            RoleValue::SUPER_ADMIN->value => 'R_SUPER',
            RoleValue::SYSTEM_ADMIN->value => 'R_SYSTEM_ADMIN',
            RoleValue::COMPANY_ADMIN->value => 'R_COMPANY_ADMIN',
            RoleValue::BRANCH_ADMIN->value => 'R_BRANCH_ADMIN',
            RoleValue::RESTAURANT_ADMIN->value => 'R_RESTAURANT_ADMIN',
            RoleValue::OPERATOR->value => 'R_OPERATOR',
            RoleValue::STAFF->value => 'R_STAFF',
            RoleValue::DRIVER->value => 'R_DRIVER',
            default => 'R_USER',
        };
    }
}
