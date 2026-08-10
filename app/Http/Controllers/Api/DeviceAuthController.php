<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceAuthorization;
use App\Models\User;

class DeviceAuthController extends Controller
{
    public function requestDeviceCode()
    {
        $deviceName = request()->input('device_name', 'CLI Device');
        $requestedHost = request()->input('requested_host', request()->ip());

        // Generate 8-character user code (e.g. M7KC-P2QV)
        $userCodeRaw = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4).'-'.substr(bin2hex(random_bytes(4)), 0, 4));
        $deviceCodeRaw = bin2hex(random_bytes(32));

        $userCodeHash = hash('sha256', str_replace('-', '', $userCodeRaw));
        $deviceCodeHash = hash('sha256', $deviceCodeRaw);

        $authorization = DeviceAuthorization::create([
            'device_code_hash' => $deviceCodeHash,
            'user_code_hash' => $userCodeHash,
            'device_name' => $deviceName,
            'requested_host' => $requestedHost,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'device_code' => $deviceCodeRaw,
            'user_code' => $userCodeRaw,
            'verification_uri' => url('/device'),
            'expires_in' => 600,
            'interval' => 2,
        ]);
    }

    public function pollDeviceToken()
    {
        $deviceCodeRaw = request()->input('device_code');
        if (empty($deviceCodeRaw)) {
            return response()->json(['error' => 'invalid_request'], 400);
        }

        $hash = hash('sha256', $deviceCodeRaw);
        $auth = DeviceAuthorization::where('device_code_hash', $hash)->first();

        if (! $auth) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        if ($auth->expires_at->isPast() || $auth->status === 'expired') {
            $auth->update(['status' => 'expired']);

            return response()->json(['error' => 'expired_token'], 400);
        }

        if ($auth->status === 'denied') {
            return response()->json(['error' => 'access_denied'], 403);
        }

        if ($auth->status === 'pending') {
            return response()->json(['error' => 'authorization_pending'], 400);
        }

        if ($auth->status === 'approved') {
            /** @var User $user */
            $user = User::findOrFail($auth->approved_by);

            // Create Sanctum token for device
            $token = $user->createToken(
                $auth->device_name,
                ['workspace:read', 'project:read', 'metadata:read', 'secret:write', 'secret:use', 'secret:reveal']
            )->plainTextToken;

            $auth->update([
                'status' => 'consumed',
                'consumed_at' => now(),
            ]);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        return response()->json(['error' => 'already_consumed'], 400);
    }
}
