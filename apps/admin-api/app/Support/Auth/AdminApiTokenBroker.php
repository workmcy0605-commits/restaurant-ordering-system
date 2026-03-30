<?php

namespace App\Support\Auth;

use App\Enums\Status;
use App\Models\AdminApiToken;
use App\Models\User;
use Illuminate\Support\Str;

class AdminApiTokenBroker
{
    private const ACCESS_TOKEN_LIFETIME_MINUTES = 120;

    private const REFRESH_TOKEN_LIFETIME_DAYS = 14;

    public function issueTokenPair(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $name = 'admin-portal'
    ): array {
        $plainTextToken = Str::random(80);
        $plainTextRefreshToken = Str::random(80);
        $expiresAt = now()->addMinutes(self::ACCESS_TOKEN_LIFETIME_MINUTES);
        $refreshExpiresAt = now()->addDays(self::REFRESH_TOKEN_LIFETIME_DAYS);

        AdminApiToken::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => $this->hashToken($plainTextToken),
            'refresh_token_hash' => $this->hashToken($plainTextRefreshToken),
            'expires_at' => $expiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
            'last_used_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return [
            'token' => $plainTextToken,
            'refreshToken' => $plainTextRefreshToken,
            'expiresAt' => $expiresAt->toIso8601String(),
            'refreshExpiresAt' => $refreshExpiresAt->toIso8601String(),
        ];
    }

    public function resolveAccessToken(?string $plainTextToken): array
    {
        return $this->resolveToken($plainTextToken, 'token_hash', 'expires_at');
    }

    public function resolveRefreshToken(?string $plainTextToken): array
    {
        return $this->resolveToken($plainTextToken, 'refresh_token_hash', 'refresh_expires_at');
    }

    public function revoke(AdminApiToken $token): void
    {
        if ($token->revoked_at !== null) {
            return;
        }

        $token->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    private function resolveToken(?string $plainTextToken, string $column, string $expiresColumn): array
    {
        if (! is_string($plainTextToken) || trim($plainTextToken) === '') {
            return [
                'status' => 'missing',
                'token' => null,
                'user' => null,
            ];
        }

        $token = AdminApiToken::query()
            ->with('user.roleName')
            ->where($column, $this->hashToken($plainTextToken))
            ->first();

        if (! $token instanceof AdminApiToken) {
            return [
                'status' => 'invalid',
                'token' => null,
                'user' => null,
            ];
        }

        if ($token->revoked_at !== null) {
            return [
                'status' => 'invalid',
                'token' => $token,
                'user' => $token->user,
            ];
        }

        if ($token->{$expiresColumn} !== null && $token->{$expiresColumn}->isPast()) {
            return [
                'status' => 'expired',
                'token' => $token,
                'user' => $token->user,
            ];
        }

        $user = $token->user;

        if (! $user instanceof User || $user->trashed() || $user->status !== Status::ACTIVE->value) {
            $this->revoke($token);

            return [
                'status' => 'invalid',
                'token' => $token,
                'user' => null,
            ];
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        return [
            'status' => 'valid',
            'token' => $token,
            'user' => $user,
        ];
    }

    private function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
