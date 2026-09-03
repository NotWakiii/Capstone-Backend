<?php

namespace App\Helpers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public static function log(
        string $action,
        string $module,
        string $description,
        ?User $actor = null
    ): void {

        try {

            // Use supplied user during login.
            // Otherwise use currently authenticated user.
            $user = $actor ?? auth()->user();

            AuditLog::create([
                'user_id' =>
                    $user?->id,

                'user_name' =>
                    $user?->name ?? 'Unknown User',

                'role' =>
                    $user?->role ?? 'Unknown',

                'action' =>
                    $action,

                'module' =>
                    $module,

                'description' =>
                    $description,

                'ip_address' =>
                    request()->ip(),
            ]);

        } catch (\Throwable $e) {

            Log::warning(
                'Audit log failed: ' .
                $e->getMessage()
            );
        }
    }
}
