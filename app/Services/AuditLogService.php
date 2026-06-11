<?php

namespace App\Services;

use App\Enums\AuditActionType;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogService
{
    public function log(
        User $user,
        AuditActionType $actionType,
        string $targetType,
        int $targetId,
        ?Request $request = null,
        ?array $extraData = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user->id,
            'action_type' => $actionType->value,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'extra_data' => $extraData,
            'created_at' => now(),
        ]);
    }
}
