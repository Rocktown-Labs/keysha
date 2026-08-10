<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;
use App\Models\Workspace;

class AuditService
{
    public function log(
        Workspace $workspace,
        string $event,
        ?User $actor = null,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $projectId = null,
        ?string $environmentId = null,
        ?array $metadata = null,
        ?string $deviceId = null
    ): AuditEvent {
        // Redact any accidental sensitive keys from metadata
        $safeMetadata = $this->sanitizeMetadata($metadata);

        return AuditEvent::create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $actor?->id,
            'actor_device_id' => $deviceId,
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'project_id' => $projectId,
            'environment_id' => $environmentId,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $safeMetadata,
        ]);
    }

    private function sanitizeMetadata(?array $metadata): ?array
    {
        if ($metadata === null) {
            return null;
        }

        $forbiddenKeys = [
            'password', 'secret', 'token', 'api_key', 'authorization', 'cookie',
            'recovery_key', 'master_key', 'encrypted_value', 'value', 'plaintext',
            'KEYSHA_SENTINEL_SECRET_DO_NOT_LEAK',
        ];

        $cleaned = [];
        foreach ($metadata as $key => $val) {
            $lowerKey = strtolower((string) $key);
            $isForbidden = false;

            foreach ($forbiddenKeys as $forbidden) {
                if (str_contains($lowerKey, strtolower($forbidden))) {
                    $isForbidden = true;
                    break;
                }
            }

            if ($isForbidden) {
                $cleaned[$key] = '[REDACTED]';
            } elseif (is_array($val)) {
                $cleaned[$key] = $this->sanitizeMetadata($val);
            } else {
                $cleaned[$key] = $val;
            }
        }

        return $cleaned;
    }
}
