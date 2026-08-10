<?php

namespace App\Livewire\Device;

use App\Models\DeviceAuthorization;
use App\Services\AuditService;
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

    public function findDevice()
    {
        $hash = hash('sha256', strtoupper(trim($this->userCode)));

        $auth = DeviceAuthorization::where('user_code_hash', $hash)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($auth) {
            $this->authorization = $auth;
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
        $workspace = $user->personalWorkspace();

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

    public function render()
    {
        return view('livewire.device.device-approve')
            ->layout('layouts.app', ['title' => 'Authorize CLI Device']);
    }
}
