<?php

namespace App\Livewire\Device;

use App\Models\DeviceAuthorization;
use App\Services\AuditService;
use App\Services\DeviceAuthorizationCode;
use Livewire\Component;

class DeviceApprove extends Component
{
    public string $userCode = '';

    public ?DeviceAuthorization $authorization = null;

    public bool $approved = false;

    public function mount()
    {
        $code = request()->query('code');
        if (! empty($code)) {
            $this->userCode = strtoupper(trim($code));
            $this->findDevice();
        }
    }

    public function updatedUserCode($value)
    {
        if (strlen(str_replace(['-', ' '], '', trim($value))) >= 8) {
            $this->findDevice();
        }
    }

    public function findDevice()
    {
        $cleanCode = DeviceAuthorizationCode::normalize($this->userCode);

        if (empty($cleanCode)) {
            $this->authorization = null;

            return;
        }

        $hash = DeviceAuthorizationCode::hash($cleanCode);

        $auth = DeviceAuthorization::where('user_code_hash', $hash)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($auth) {
            $this->authorization = $auth;
            $this->resetErrorBag('userCode');
        } else {
            $this->authorization = null;
            $this->addError('userCode', 'Invalid, expired, or already consumed device code.');
        }
    }

    public function approveDevice(AuditService $audit)
    {
        if (! $this->authorization || $this->authorization->status !== 'pending') {
            return;
        }

        $user = auth()->user();
        $workspace = $user->currentWorkspace();

        $this->authorization->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $audit->log(
            workspace: $workspace,
            event: 'device.authorized',
            actor: $user,
            subjectType: DeviceAuthorization::class,
            subjectId: $this->authorization->id,
            metadata: ['device_name' => $this->authorization->device_name]
        );

        $this->approved = true;
    }

    public function revokeDevice(string $deviceAuthId, AuditService $audit)
    {
        $user = auth()->user();
        $auth = DeviceAuthorization::where('id', $deviceAuthId)->firstOrFail();

        $auth->update(['status' => 'revoked']);

        // Delete matching Sanctum token for device
        $user->tokens()->where('name', 'like', "%{$auth->device_name}%")->delete();

        $audit->log(
            workspace: $user->currentWorkspace(),
            event: 'device.revoked',
            actor: $user,
            subjectType: DeviceAuthorization::class,
            subjectId: $auth->id,
            metadata: ['device_name' => $auth->device_name]
        );

        session()->flash('message', "Authorized device '{$auth->device_name}' access revoked.");
    }

    public function render()
    {
        $authorizedDevices = DeviceAuthorization::latest()->take(20)->get();

        return view('livewire.device.device-approve', [
            'authorizedDevices' => $authorizedDevices,
        ])->layout('layouts.app', ['title' => 'Authorize CLI Device']);
    }
}
