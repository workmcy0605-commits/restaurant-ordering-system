<?php

namespace App\Http\Middleware;

use App\Models\AdminApiToken;
use App\Support\Auth\AdminApiTokenBroker;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminApiToken
{
    public function __construct(private readonly AdminApiTokenBroker $tokenBroker) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        $resolution = $this->tokenBroker->resolveAccessToken($request->bearerToken());

        if ($resolution['status'] !== 'valid') {
            return $this->authenticationErrorResponse($resolution['status']);
        }

        $user = $resolution['user'];
        $token = $resolution['token'];

        Auth::onceUsingId((int) $user->id);
        $request->setUserResolver(fn () => $user);

        if ($token instanceof AdminApiToken) {
            $request->attributes->set('adminApiToken', $token);
        }

        return $next($request);
    }

    private function authenticationErrorResponse(string $status): JsonResponse
    {
        if ($status === 'expired') {
            return response()->json([
                'code' => '9999',
                'message' => 'Access token expired.',
                'msg' => 'Access token expired.',
                'data' => null,
            ]);
        }

        return response()->json([
            'code' => '8888',
            'message' => 'Please log in again.',
            'msg' => 'Please log in again.',
            'data' => null,
        ]);
    }
}
